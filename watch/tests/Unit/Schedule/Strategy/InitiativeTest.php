<?php
namespace Tests\Unit\Schedule\Strategy;

use Codeception\Test\Unit;
use Watch\Schedule\Builder\Strategy\Limit\Initiative;
use Watch\Schedule\Model\Task;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Milestone;

class InitiativeTest extends Unit
{
    public function testSchedule()
    {
        $milestone = new Milestone("Milestone", 0);
        $node1 = new Task("Test1", 10);
        $node2 = new Task("Test2", 11);
        $node3 = new Task("Test3", 12);
        $node4 = new Task("Test4", 13);
        $node1->precede($milestone);
        $node2->precede($milestone);
        $node3->precede($milestone);
        $node4->precede($milestone);
        $strategy = new Initiative(2);
        $strategy->apply($milestone);
        $this->assertEquals(24, $milestone->getLength(true));
        $this->assertEquals([$node3], $node1->getPreceders(false, [Link::TYPE_SCHEDULE]));
        $this->assertEquals([$node4], $node2->getPreceders(false, [Link::TYPE_SCHEDULE]));
    }
}
