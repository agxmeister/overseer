<?php
namespace Tests\Unit;

use Codeception\Test\Unit;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Utils;

class UtilsTest extends Unit
{
    public function testLongestAndShortestSequence()
    {
        $node1 = new Node("Test1", 10);
        $node2 = new Node("Test2", 11);
        $node3 = new Node("Test3", 12);
        $node2->precede($node1);
        $node3->precede($node1);
        $this->assertEquals($node3, Utils::getLongestSequence($node1->getPreceders()));
        $this->assertEquals($node2, Utils::getShortestSequence($node1->getPreceders()));
        $node4 = new Node("Test4", 13);
        $node4->precede($node2);
        $this->assertEquals($node2, Utils::getLongestSequence($node1->getPreceders()));
        $this->assertEquals($node3, Utils::getShortestSequence($node1->getPreceders()));
        $node5 = new Node("Test5", 14);
        $node5->precede($node3);
        $this->assertEquals($node3, Utils::getLongestSequence($node1->getPreceders()));
        $this->assertEquals($node2, Utils::getShortestSequence($node1->getPreceders()));
        $node6 = new Node("Test6", 15);
        $node6->precede($node3);
        $node7 = new Node("Test7", 17);
        $node7->precede($node2);
        $this->assertEquals($node2, Utils::getLongestSequence($node1->getPreceders()));
        $this->assertEquals($node3, Utils::getShortestSequence($node1->getPreceders()));
    }
}
