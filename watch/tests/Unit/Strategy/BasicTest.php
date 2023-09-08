<?php
namespace Tests\Unit\Strategy;

use Codeception\Test\Unit;
use Watch\Schedule\Link;
use Watch\Schedule\Node;
use Watch\Schedule\Strategy\Basic;

class BasicTest extends Unit
{
    public function testSchedule()
    {
        $milestone = new Node("Milestone", 0);
        $node1 = new Node("Test1", 10);
        $node2 = new Node("Test2", 11);
        $node3 = new Node("Test3", 12);
        $node4 = new Node("Test4", 13);
        $node1->precede($milestone);
        $node2->precede($milestone);
        $node3->precede($milestone);
        $node4->precede($milestone);
        $strategy = new Basic();
        $strategy->schedule($milestone);
        $this->assertEquals(24, $milestone->getLength(true));
        $this->assertEquals([$node3], $node1->getPreceders(false, [Link::TYPE_SCHEDULE]));
        $this->assertEquals([$node4], $node2->getPreceders(false, [Link::TYPE_SCHEDULE]));
    }
}
