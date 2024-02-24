<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\Issue;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Model\Project;
use Watch\Schedule\Serializer\Project as ProjectSerializer;
use Watch\Schedule\Utils as ScheduleUtils;
use Watch\Schedule\Description\Utils as DescriptionUtils;
use function PHPUnit\Framework\assertEquals;

class UtilsTest extends Unit
{
    /**
     * @dataProvider dataGetDuplicate
     */
    public function testGetDuplicate($scheduleDescription)
    {
        $serializer = new ProjectSerializer();
        $origin = $serializer->deserialize(DescriptionUtils::getSchedule($scheduleDescription));

        $copy = ScheduleUtils::getDuplicate($origin);

        $originPreceders = $this->getNodes($origin->getPreceders(true));
        $copyPreceders = $this->getNodes($copy->getPreceders(true));

        self::assertSameSize($originPreceders, $copyPreceders, "A count of preceders is different between the origin and the copy.");
        foreach ($originPreceders as $name => $originPreceder) {
            self::assertArrayHasKey($name, $copyPreceders, "The preceder with the name '$name' is missing from the copy.");
            $copyPreceder = $copyPreceders[$name];
            self::assertFalse($originPreceder === $copyPreceder, "Preceders from the origin and the copy are references to the same node '$name'.");
            self::assertEquals($originPreceder->name, $copyPreceder->name, "The name of the copy of the preceder '$name' is not the same as the name of the origin.");
            $originLinks = $this->getLinks($originPreceder->getFollowLinks());
            $copyLinks = $this->getLinks($copyPreceder->getFollowLinks());
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
        $node1 = new Issue("Node1", 10);
        $node1->precede($origin);
        $node11 = new Issue("Node11", 10);
        $node11->precede($node1);
        $buffer = new FeedingBuffer("Buffer", 1);
        $node12 = new Issue("Node12", 2);
        $node12->precede($buffer);
        $buffer->precede($node1);
        $copy = ScheduleUtils::getCriticalChain($origin);
        $this->assertEquals(["Root", "Node1", "Node11"], $this->getNames($copy));
    }

    /**
     * @dataProvider dataGetFeedingChains
     */
    public function testGetFeedingChains($scheduleDescription, $expectedFeedingChains)
    {
        $serializer = new ProjectSerializer();
        $origin = $serializer->deserialize(DescriptionUtils::getSchedule($scheduleDescription));

        $actualFeedingChains = array_reduce(
            array_map(
                fn(Node $feedingChain) => self::getNames($feedingChain),
                ScheduleUtils::getFeedingChains($origin),
            ),
            fn($acc, array $names) => [...$acc, reset($names) => $names],
            []
        );

        $this->assertSameSize($expectedFeedingChains, $actualFeedingChains);
        foreach ($expectedFeedingChains as $key => $expectedFeedingChain) {
            assertEquals($expectedFeedingChain, $actualFeedingChains[$key]);
        }
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

    protected function dataGetDuplicate(): array
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
            '],
        ];
    }

    protected function dataGetFeedingChains(): array
    {
        return [
            [
                '
                    PB/finish-buf |         _____| @ finish
                    K-01          |    xxxxx     | @ finish-buf
                    FB/K-02-buf   |      ___     | @ finish-buf
                    K-02          |******        | @ K-02-buf
                    K-03          |xxxx          | @ K-01
                    finish                       ^ # 2023-09-21
                ',
                ['K-02' => ['K-02']]
            ],
        ];
    }
}
