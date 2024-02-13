<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Watch\Schedule\Model\Issue;
use Watch\Schedule\Utils;

class UtilsTest extends Unit
{
    public function testLongestAndShortestSequence()
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
}
