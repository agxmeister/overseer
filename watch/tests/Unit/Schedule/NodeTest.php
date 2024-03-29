<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Watch\Schedule\Model\Issue;
use Watch\Schedule\Model\Link;

class NodeTest extends Unit
{
    public function testSingleLengthAndDistance()
    {
        $node = new Issue("Test", 10);
        $this->assertEquals(10, $node->getLength());
        $this->assertEquals(10, $node->getDistance());
        $this->assertEquals(10, $node->getLength(true));
        $this->assertEquals(10, $node->getDistance(true));
    }

    public function testTwoInLineLengthAndDistance()
    {
        $node1 = new Issue("Test1", 10);
        $node2 = new Issue("Test2", 11);
        $node2->precede($node1);
        $this->assertEquals(10, $node1->getLength());
        $this->assertEquals(21, $node1->getLength(true));
        $this->assertEquals(10, $node1->getDistance());
        $this->assertEquals(21, $node1->getDistance(true));
        $this->assertEquals(11, $node2->getLength());
        $this->assertEquals(11, $node2->getLength(true));
        $this->assertEquals(21, $node2->getDistance());
        $this->assertEquals(21, $node2->getDistance(true));
    }

    public function testThreeInLineLengthAndDistance()
    {
        $node1 = new Issue("Test1", 10);
        $node2 = new Issue("Test2", 11);
        $node3 = new Issue("Test3", 12);
        $node3->precede($node2);
        $node2->precede($node1);
        $this->assertEquals(10, $node1->getLength());
        $this->assertEquals(33, $node1->getLength(true));
        $this->assertEquals(10, $node1->getDistance());
        $this->assertEquals(33, $node1->getDistance(true));
        $this->assertEquals(11, $node2->getLength());
        $this->assertEquals(23, $node2->getLength(true));
        $this->assertEquals(21, $node2->getDistance());
        $this->assertEquals(33, $node2->getDistance(true));
        $this->assertEquals(12, $node3->getLength());
        $this->assertEquals(12, $node3->getLength(true));
        $this->assertEquals(33, $node3->getDistance());
        $this->assertEquals(33, $node3->getDistance(true));
    }

    public function testThreeInForkLengthAndDistance()
    {
        $node1 = new Issue("Test1", 10);
        $node2 = new Issue("Test2", 11);
        $node3 = new Issue("Test3", 12);
        $node2->precede($node1);
        $node3->precede($node1);
        $this->assertEquals(10, $node1->getLength());
        $this->assertEquals(22, $node1->getLength(true));
        $this->assertEquals(10, $node1->getDistance());
        $this->assertEquals(22, $node1->getDistance(true));
        $this->assertEquals(11, $node2->getLength());
        $this->assertEquals(11, $node2->getLength(true));
        $this->assertEquals(21, $node2->getDistance());
        $this->assertEquals(21, $node2->getDistance(true));
        $this->assertEquals(12, $node3->getLength());
        $this->assertEquals(12, $node3->getLength(true));
        $this->assertEquals(22, $node3->getDistance());
        $this->assertEquals(22, $node3->getDistance(true));
    }

    public function testFourInForkLengthAndDistance()
    {
        $node1 = new Issue("Test1", 10);
        $node2 = new Issue("Test2", 11);
        $node3 = new Issue("Test3", 12);
        $node4 = new Issue("Test4", 13);
        $node2->precede($node1);
        $node3->precede($node1);
        $node4->precede($node2);
        $this->assertEquals(10, $node1->getLength());
        $this->assertEquals(34, $node1->getLength(true));
        $this->assertEquals(10, $node1->getDistance());
        $this->assertEquals(34, $node1->getDistance(true));
        $this->assertEquals(11, $node2->getLength());
        $this->assertEquals(24, $node2->getLength(true));
        $this->assertEquals(21, $node2->getDistance());
        $this->assertEquals(34, $node2->getDistance(true));
        $this->assertEquals(12, $node3->getLength());
        $this->assertEquals(12, $node3->getLength(true));
        $this->assertEquals(22, $node3->getDistance());
        $this->assertEquals(22, $node3->getDistance(true));
        $this->assertEquals(13, $node4->getLength());
        $this->assertEquals(13, $node4->getLength(true));
        $this->assertEquals(34, $node4->getDistance());
        $this->assertEquals(34, $node4->getDistance(true));
    }

    public function testLengthAndDistanceWithPreceders()
    {
        $node1 = new Issue("Test1", 10);
        $node2 = new Issue("Test2", 11);
        $node3 = new Issue("Test3", 12);
        $node4 = new Issue("Test4", 13);
        $node1->follow($node2);
        $node2->follow($node3);
        $node2->follow($node4, Link::TYPE_SCHEDULE);
        $this->assertEquals(24, $node2->getLength(true));
        $this->assertEquals(23, $node2->getLength(true, [Link::TYPE_SEQUENCE]));
        $this->assertEquals(34, $node2->getDistance(true));
        $this->assertEquals(33, $node2->getDistance(true, [Link::TYPE_SEQUENCE]));
    }

    public function testPreceders()
    {
        $node1 = new Issue("Test1", 10);
        $node2 = new Issue("Test2", 11);
        $node3 = new Issue("Test3", 12);
        $node4 = new Issue("Test4", 13);
        $node5 = new Issue("Test5", 14);
        $node2->precede($node1);
        $node3->precede($node1);
        $node5->precede($node1);
        $node2->precede($node5);
        $node4->precede($node2);
        $node4->precede($node3);
        $this->assertEquals([$node2, $node3, $node5], $node1->getPreceders());
        $this->assertEquals([$node2, $node3, $node5, $node4], $node1->getPreceders(true));
        $this->assertEquals([$node4], $node2->getPreceders());
        $this->assertEquals([$node4], $node2->getPreceders(true));
        $this->assertEquals([$node4], $node3->getPreceders());
        $this->assertEquals([$node4], $node3->getPreceders(true));
        $this->assertEquals([], $node4->getPreceders());
        $this->assertEquals([], $node4->getPreceders(true));
        $this->assertEquals([$node2], $node5->getPreceders());
        $this->assertEquals([$node2, $node4], $node5->getPreceders(true));
    }

    public function testUnlink()
    {
        $nodes = $this->getTestNodes();
        $nodes['Test12']->unlink();
        $this->assertNotContains($nodes['Test12']->name, array_map(
            fn(Issue $node) => $node->name,
            $nodes['Test1']->getPreceders(),
        ));
        $this->assertNotContains($nodes['Test12']->name, array_map(
            fn(Issue $node) => $node->name,
            $nodes['Test121']->getFollowers(),
        ));
        $this->assertNotContains($nodes['Test12']->name, array_map(
            fn(Issue $node) => $node->name,
            $nodes['Test122']->getFollowers(),
        ));
    }

    public function testClone()
    {
        $nodes = $this->getTestNodes();
        $nodes['Test1']->setAttribute('test', 'test');
        $clone = clone $nodes['Test1'];
        $this->assertEquals([], $clone->getPreceders());
        $this->assertEquals([], $clone->getFollowers());
        $this->assertEquals('test', $clone->getAttribute('test'));
    }

    /**
     * @return Issue[]
     */
    protected function getTestNodes(): array
    {
        $node1 = new Issue("Test1", 10);
        $node11 = new Issue("Test11", 11);
        $node12 = new Issue("Test12", 12);
        $node121 = new Issue("Test121", 12);
        $node122 = new Issue("Test122", 12);
        $node121->precede($node12);
        $node122->precede($node12);
        $node11->precede($node1);
        $node12->precede($node1);
        return array_reduce(
            [$node1, ...$node1->getPreceders(true)],
            fn($acc, Issue $node) => [...$acc, $node->name => $node],
            [],
        );
    }
}
