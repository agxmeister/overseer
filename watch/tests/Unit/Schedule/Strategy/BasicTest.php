<?php
namespace Tests\Unit\Schedule\Strategy;

use Codeception\Test\Unit;
use Watch\Schedule\Builder\Strategy\Limit\Basic;
use Watch\Schedule\Model\Issue;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Milestone;

class BasicTest extends Unit
{
    public function testSchedule()
    {
        $milestone = new Milestone("Milestone", 0);
        $node1 = new Issue("Test1", 10);
        $node2 = new Issue("Test2", 11);
        $node3 = new Issue("Test3", 12);
        $node4 = new Issue("Test4", 13);
        $node1->precede($milestone);
        $node2->precede($milestone);
        $node3->precede($milestone);
        $node4->precede($milestone);
        $strategy = new Basic(2);
        $strategy->apply($milestone);
        $this->assertEquals(24, $milestone->getLength(true));
        $this->assertEquals([$node3], $node1->getPreceders(false, [Link::TYPE_SCHEDULE]));
        $this->assertEquals([$node4], $node2->getPreceders(false, [Link::TYPE_SCHEDULE]));
    }
}
