<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Watch\Description\Schedule;
use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\Issue;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Model\Project;
use Watch\Schedule\Model\ProjectBuffer;
use Watch\Schedule\Serializer\Project as ProjectSerializer;
use Watch\Schedule\Utils as ScheduleUtils;

class UtilsTest extends Unit
{
    /**
     * @dataProvider dataGetDuplicate
     */
    public function testGetDuplicate($scheduleDescription)
    {
        $description = new Schedule($scheduleDescription);
        $serializer = new ProjectSerializer();
        $origin = $serializer->deserialize($description->getSchedule());

        /** @var Project $copy */
        $copy = ScheduleUtils::getDuplicate($origin);

        $originTree = $origin->getLinkedNodes();
        $copyTree = $copy->getLinkedNodes();

        self::assertSameSize($originTree, $copyTree, "A count of nodes is different between the origin and the copy.");
        foreach ($originTree as $name => $originNode) {
            self::assertArrayHasKey($name, $copyTree, "The node with the name '$name' is missing from the copy.");
            $copyNode = $copyTree[$name];
            self::assertFalse($originNode === $copyNode, "Nodes from the origin and the copy are references to the same node '$name'.");
            self::assertEquals($originNode->name, $copyNode->name, "The name of the copy of the node '$name' is not the same as the name of the origin.");
            $originLinks = $this->getLinks($originNode->getFollowLinks());
            $copyLinks = $this->getLinks($copyNode->getFollowLinks());
            self::assertSameSize($originLinks, $copyLinks, "A count of links from the node '$name' differs between the origin and the copy.");
            $originLinkNames = array_keys($originLinks);
            sort($originLinkNames);
            $copyLinkNames = array_keys($copyLinks);
            sort($copyLinkNames);
            self::assertEquals($originLinkNames, $copyLinkNames, "Links from the node '$name' differs between the origin and the copy.");
            foreach ($originLinks as $to => $link) {
                self::assertEquals($link->type, $copyLinks[$to]->type, "A type of one of the links from the node '$name' differs between the origin and the copy.");
            }
        }
    }

    public function testGetCriticalChain()
    {
        $origin = new Project("Root");
        $projectBuffer = new ProjectBuffer("PB", 1);
        $projectBuffer->precede($origin);
        $node1 = new Issue("Node1", 10);
        $node1->precede($projectBuffer);
        $node11 = new Issue("Node11", 10);
        $node11->precede($node1);
        $feedingBuffer = new FeedingBuffer("FB1", 1);
        $node12 = new Issue("Node12", 2);
        $node12->precede($feedingBuffer);
        $feedingBuffer->precede($node1);
        $this->assertSame([$node1, $node11], ScheduleUtils::getCriticalChain($origin)->nodes);
    }

    /**
     * @dataProvider dataGetMilestoneChain
     */
     public function testGetMilestoneChain($scheduleDescription, $expectedMilestoneChain)
     {
         $description = new Schedule($scheduleDescription);
         $serializer = new ProjectSerializer();
         $origin = $serializer->deserialize($description->getSchedule());
         $originNodes = $origin->getLinkedNodes();
         $milestone = current($origin->getMilestones());
         $this->assertSame(
             array_map(
                 fn(string $nodeName) => $originNodes[$nodeName],
                 $expectedMilestoneChain,
             ),
             ScheduleUtils::getMilestoneChain($milestone)->nodes,
         );
     }

    /**
     * @dataProvider dataGetFeedingChains
     */
    public function testGetFeedingChains($scheduleDescription, $expectedFeedingChains)
    {
        $description = new Schedule($scheduleDescription);
        $serializer = new ProjectSerializer();
        $origin = $serializer->deserialize($description->getSchedule());
        $actualFeedingChains = ScheduleUtils::getFeedingChains($origin);
        $this->assertSameSize($expectedFeedingChains, $actualFeedingChains);
        $originNodes = $origin->getLinkedNodes();
        foreach ($expectedFeedingChains as $key => $expectedFeedingChain) {
            $this->assertSame(
                array_map(
                    fn(string $nodeName) => $originNodes[$nodeName],
                    $expectedFeedingChain,
                ),
                $actualFeedingChains[$key]->nodes,
            );
        }
    }

    /**
     * @dataProvider dataGetLongestChainNodes
     */
    public function testGetLongestChainNodes($scheduleDescription, $expectedChainNodeNames)
    {
        $description = new Schedule($scheduleDescription);
        $serializer = new ProjectSerializer();
        $project = $serializer->deserialize($description->getSchedule());
        $this->assertEquals($expectedChainNodeNames, array_map(
            fn(Node $node) => $node->name,
            ScheduleUtils::getLongestChainNodes($project),
        ));
    }

    public function testMostAndLeastDistantNodes()
    {
        $node1 = new Issue("Test1", 10);
        $node2 = new Issue("Test2", 11);
        $node3 = new Issue("Test3", 12);
        $node2->precede($node1);
        $node3->precede($node1);
        $this->assertEquals($node3, ScheduleUtils::getMostDistantNode($node1->getPreceders()));
        $this->assertEquals($node2, ScheduleUtils::getLeastDistantNode($node1->getPreceders()));
        $node4 = new Issue("Test4", 13);
        $node4->precede($node2);
        $this->assertEquals($node2, ScheduleUtils::getMostDistantNode($node1->getPreceders()));
        $this->assertEquals($node3, ScheduleUtils::getLeastDistantNode($node1->getPreceders()));
        $node5 = new Issue("Test5", 14);
        $node5->precede($node3);
        $this->assertEquals($node3, ScheduleUtils::getMostDistantNode($node1->getPreceders()));
        $this->assertEquals($node2, ScheduleUtils::getLeastDistantNode($node1->getPreceders()));
        $node6 = new Issue("Test6", 15);
        $node6->precede($node3);
        $node7 = new Issue("Test7", 17);
        $node7->precede($node2);
        $this->assertEquals($node2, ScheduleUtils::getMostDistantNode($node1->getPreceders()));
        $this->assertEquals($node3, ScheduleUtils::getLeastDistantNode($node1->getPreceders()));
    }

    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    protected function getNodes(array $nodes): array
    {
        return array_reduce(
            $nodes,
            fn($acc, Node $node) => [...$acc, $node->name => $node],
            [],
        );
    }

    /**
     * @param Link[] $links
     * @return Link[]
     */
    protected function getLinks(array $links): array
    {
        return array_reduce(
            $links,
            fn($acc, Link $link) => [...$acc, $link->node->name => $link],
            [],
        );
    }

    /**
     * @param Node $node
     * @return string[]
     */
    protected function getNames(Node $node): array
    {
        return [
            $node->name,
            ...array_map(
                fn(Node $node) => $node->name,
                $node->getPreceders(true),
            ),
        ];
    }

    public static function dataGetDuplicate(): array
    {
        return [
            ['
                PB/finish-buf |         _____| @ finish
                K-01          |    xxxxx     | @ finish-buf
                FB/K-02-buf   |      ___     | @ finish-buf
                K-02          |******        | @ K-02-buf
                K-03          |xxxx          | @ K-01
                finish                       ^ # 2023-09-21
            '], ['
                PB/finish-buf |           ______| @ finish
                K-01          |       xxxx      | @ finish-buf
                FB/K-02-buf   |     __          | @ K-01
                K-02          | ****            | & K-01, @ K-02-buf
                K-03          |xxxxxxx          | & K-01
                finish                          ^ # 2023-09-21
            '], ['
                PB/finish-buf |            ______| @ finish
                PRJ/T/K-01    |        xxxx      | @ finish-buf
                PRJ/T/K-02    |    xxxx          | @ K-01
                PRJ#M1/T/K-03 |xxxx              | @ K-02, @ M1-buf
                MB/M1-buf     |    __            | @ M1
                M1                   ^             # 2023-09-09
                finish                           ^ # 2023-09-21
            '],
        ];
    }

    public static function dataGetMilestoneChain(): array
    {
        return [
            [
                '
                    PB/finish-buf |            ______| @ finish
                    K01           |        xxxx      | @ finish-buf
                    PRJ#M01/T/K02 |    xxxx          | @ K01, @ M01-buf
                    PRJ#M01/T/K03 |xxxx              | @ K02
                    MB/M01-buf    |        ____      | @ M01
                    M01                        ^       # 2023-09-15
                    finish                           ^ # 2023-09-21
                ',
                ['K02', 'K03'],
            ],
        ];
    }

    public static function dataGetFeedingChains(): array
    {
        return [
            [
                '
                    PB/finish-buf |         _____| @ finish
                    K01           |    xxxxx     | @ finish-buf
                    FB/K02-buf    |      ___     | @ finish-buf
                    K02           |******        | @ K02-buf
                    K03           |xxxx          | @ K01
                    finish                       ^ # 2023-09-21
                ',
                ['K02-buf' => ['K02']]
            ],
        ];
    }

    public static function dataGetLongestChainNodes(): array
    {
        return [
            [
                '
                    K01          |         xxxxx| @ PRJ
                    K02          |    xxxxx     | @ K01
                    K03          |xxxx          | @ K02
                    K04          |   ******     | @ K01
                    K05          | **           | @ K04
                    PRJ                         ^ # 2023-09-21
                ',
                ['PRJ', 'K01', 'K02', 'K03'],
            ], [
                '
                    K01          |          xxxxx| @ PRJ
                    K02          |     *****     | @ K01
                    K03          | ****          | @ K02
                    K04          |    xxxxxx     | @ K01
                    K05          |xxxx           | @ K04
                    PRJ                         ^ # 2023-09-21
                ',
                ['PRJ', 'K01', 'K04', 'K05'],
            ], [
                '
                    K01          |          xxxxx| @ PRJ
                    K02          |     *****     | @ K01
                    K03          | ****          | @ K02
                    K04          |      xxxx     | @ K01
                    K05          |xxxxxx         | @ K04
                    PRJ                         ^ # 2023-09-21
                ',
                ['PRJ', 'K01', 'K04', 'K05'],
            ], [
                '
                    K01          |          xxxxx| @ PRJ
                    K02          |     *****     | @ K01
                    K03          | ****          | @ K02
                    K04          |    ******     | @ K01
                    K05          |  **           | @ K04
                    K06          |xxxxxxxxxx     | @ K01
                    PRJ                         ^ # 2023-09-21
                ',
                ['PRJ', 'K01', 'K06'],
            ],
        ];
    }
}
