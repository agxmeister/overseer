<?php
namespace Tests\Unit\Schedule\Director;

use Codeception\Test\Unit;
use Watch\Config;

abstract class AbstractDirectorTest extends Unit
{
    protected function assertSchedule($expected, $actual)
    {
        $this->assertSameSize($expected, $actual, 'Number of volumes in the schedule is differ from expected.');
        foreach (['issues', 'buffers', 'milestones', 'links'] as $volume) {
            $this->assertScheduleVolume($expected[$volume], $actual[$volume], $volume);
        }
        $this->assertEquals($expected['criticalChain'], $actual['criticalChain'], 'Critical chain of the schedule is differ from expected.');
    }

    protected function assertScheduleVolume($expected, $actual, $volume)
    {
        $this->assertSameSize($expected, $actual, "Number of items in volume '{$volume}' is differ from expected.");
        usort($expected, fn($a, $b) => $a < $b ? -1 : ($a > $b ? 1 : 0));
        usort($actual, fn($a, $b) => $a < $b ? -1 : ($a > $b ? 1 : 0));
        for ($i = 0; $i < sizeof($actual); $i++) {
            $this->assertEquals($expected[$i], $actual[$i], "Items in volume '{$volume}' are mismatched.");
        }
    }

    protected function getConfig(): Config
    {
        return new Config(json_decode('{}'));
    }
}
