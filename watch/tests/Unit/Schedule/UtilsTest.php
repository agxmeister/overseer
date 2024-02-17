<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\Issue;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Utils;

class UtilsTest extends Unit
{
    public function testDuplicateNode()
    {
        $origin = new Issue("Root", 10);
        $node1 = new Issue("Node1", 10);
        $node1->precede($origin);
        $node11 = new Issue("Node11", 10);
        $node11->precede($node1);
        $node12 = new Issue("Node12", 10);
        $node12->precede($node1, Link::TYPE_SCHEDULE);
        $node2 = new Issue("Node2", 10);
        $node2->precede($origin);

        $copy = clone $origin;
        Utils::duplicateNode($origin, $copy);

        $originPreceders = $this->getPreceders($origin);
        $copyPreceders = $this->getPreceders($copy);

        self::assertSameSize($originPreceders, $copyPreceders, "A count of preceders is different between the origin and the copy.");
        foreach ($originPreceders as $name => $originPreceder) {
            self::assertArrayHasKey($name, $copyPreceders, "The preceder with the name '$name' is missing from the copy.");
            $copyPreceder = $copyPreceders[$name];
            self::assertFalse($originPreceder === $copyPreceder, "Preceders from the origin and the copy are references to the same node '$name'.");
            self::assertEquals($originPreceder->name, $copyPreceder->name, "The name of the copy of the preceder '$name' is not the same as the name of the origin.");
            $originLinks = $originPreceder->getFollowLinks();
            $copyLinks = $copyPreceder->getFollowLinks();
            self::assertSameSize($originLinks, $copyLinks, "A count of links from the node '$name' differs between the origin and the copy.");
            for ($i = 0; $i < count($originLinks); $i++) {
                self::assertEquals($originLinks[$i]->type, $copyLinks[$i]->type, "A type of one of the links from the node '$name' differs between the origin and the copy.");
            }
        }
    }

    public function testCropFeedingChains()
    {
        $origin = new Issue("Root", 10);
        $node1 = new Issue("Node1", 10);
        $node1->precede($origin);
        $node11 = new Issue("Node11", 10);
        $node11->precede($node1);
        $buffer = new FeedingBuffer("Buffer", 1);
        $node12 = new Issue("Node12", 2);
        $node12->precede($buffer);
        $buffer->precede($node1);
        $copy = Utils::cropFeedingChains($origin);
        $this->assertEquals(["Root", "Node1", "Node11"], $this->getNames($copy));
    }

    public function testMostAndLeastDistantNodes()
    {
        $node1 = new Issue("Test1", 10);
        $node2 = new Issue("Test2", 11);
        $node3 = new Issue("Test3", 12);
        $node2->precede($node1);
        $node3->precede($node1);
        $this->assertEquals($node3, Utils::getMostDistantNode($node1->getPreceders()));
        $this->assertEquals($node2, Utils::getLeastDistantNode($node1->getPreceders()));
        $node4 = new Issue("Test4", 13);
        $node4->precede($node2);
        $this->assertEquals($node2, Utils::getMostDistantNode($node1->getPreceders()));
        $this->assertEquals($node3, Utils::getLeastDistantNode($node1->getPreceders()));
        $node5 = new Issue("Test5", 14);
        $node5->precede($node3);
        $this->assertEquals($node3, Utils::getMostDistantNode($node1->getPreceders()));
        $this->assertEquals($node2, Utils::getLeastDistantNode($node1->getPreceders()));
        $node6 = new Issue("Test6", 15);
        $node6->precede($node3);
        $node7 = new Issue("Test7", 17);
        $node7->precede($node2);
        $this->assertEquals($node2, Utils::getMostDistantNode($node1->getPreceders()));
        $this->assertEquals($node3, Utils::getLeastDistantNode($node1->getPreceders()));
    }

    /**
     * @param Node $node
     * @return Node[]
     */
    protected function getPreceders(Node $node): array
    {
        return array_reduce(
            $node->getPreceders(true),
            fn($acc, Node $node) => [...$acc, $node->name => $node],
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
}
