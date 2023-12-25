<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Watch\Action\Util\Schedule as ScheduleUtil;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Strategy\State\MapByStatus as MapByStatusStateStrategy;
use Watch\Schedule\Description\Utils;
use Watch\Schedule\Builder\Context;
use Watch\Subject\Decorator\Factory;

class BuilderTest extends Unit
{
    public function testAddCriticalChain()
    {
        $description = '
            K-01   |       xxxx|
            K-02   |   ****    | & K-01
            K-03   |xxxxxxx    | @ K-01
                               ^ # 2023-09-21
        ';
        $builder = new Builder(
            new Context(new \DateTimeImmutable('2023-01-01'), new Factory()),
            Utils::getIssues($description),
            Utils::getMilestones($description),
            new MapByStatusStateStrategy(),
        );
        $builder->run();
        $builder->addMilestone();
        $scheduleUtil = new ScheduleUtil();
        $this->assertEquals(['finish', 'K-01', 'K-03'], $scheduleUtil->serialize($builder->release())[ScheduleUtil::VOLUME_CRITICAL_CHAIN]);
    }

    public function testAddFeedingBuffers()
    {
        $description = '
            K-01   |       xxxx|
            K-02   | ****      | & K-01
            K-03   |xxxxxxx    | @ K-01
                               ^ # 2023-09-21
        ';
        $builder = new Builder(
            new Context(new \DateTimeImmutable('2023-01-01'), new Factory()),
            Utils::getIssues($description),
            Utils::getMilestones($description),
            new MapByStatusStateStrategy(),
        );
        $builder->run();
        $builder
            ->addMilestone()
            ->addFeedingBuffers()
            ->addDates();
        $scheduleUtil = new ScheduleUtil();
        $this->assertEquals([
            [
                'key' => 'K-02-buffer',
                'begin' => '2023-09-15',
                'end' => '2023-09-17',
                'consumption' => 0,
            ]
        ], $scheduleUtil->serialize($builder->release())[ScheduleUtil::VOLUME_BUFFERS]);
    }
}
