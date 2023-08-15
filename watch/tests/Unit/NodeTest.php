<?php
namespace Tests\Unit;

use Codeception\Test\Unit;
use Watch\Schedule\Node;

class NodeTest extends Unit
{
    public function testSingleLengthAndDistance()
    {
        $node = new Node("Test", 10);
        $this->assertEquals(10, $node->getLength());
        $this->assertEquals(10, $node->getDistance());
        $this->assertEquals(10, $node->getLength(true));
        $this->assertEquals(10, $node->getDistance(true));
    }

    public function testTwoInLineLengthAndDistance()
    {
        $node1 = new Node("Test1", 10);
        $node2 = new Node("Test2", 11);
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
        $node1 = new Node("Test1", 10);
        $node2 = new Node("Test2", 11);
        $node3 = new Node("Test3", 12);
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
        $node1 = new Node("Test1", 10);
        $node2 = new Node("Test2", 11);
        $node3 = new Node("Test3", 12);
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
        $node1 = new Node("Test1", 10);
        $node2 = new Node("Test2", 11);
        $node3 = new Node("Test3", 12);
        $node4 = new Node("Test4", 13);
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
}
