<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Watch\Schedule\Builder;
use Watch\Schedule\Description\Utils;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Formatter;
use Watch\Subject\Adapter;

class BuilderTest extends Unit
{
    public function testAddCriticalChain()
    {
        $builder = new Builder(
            new Context(new \DateTimeImmutable('2023-01-01'), new Adapter()),
            Utils::getIssues('
                K-01   |       xxxx|
                K-02   |   ****    | & K-01
                K-03   |xxxxxxx    | @ K-01
                                   ^ # 2023-09-21
            '),
        );
        $builder->run();
        $builder->addMilestone();
        $adapter = new Formatter();
        $this->assertEquals(['finish', 'K-01', 'K-03'], $adapter->getSchedule($builder->release())[$adapter::VOLUME_CRITICAL_CHAIN]);
    }

    public function testAddFeedingBuffers()
    {
        $builder = new Builder(
            new Context(new \DateTimeImmutable('2023-01-01'), new Adapter()),
            Utils::getIssues('
                K-01   |       xxxx|
                K-02   | ****      | & K-01
                K-03   |xxxxxxx    | @ K-01
                                   ^ # 2023-09-21
            '),
        );
        $builder->run();
        $builder
            ->addMilestone()
            ->addFeedingBuffers()
            ->addDates();
        $adapter = new Formatter();
        $this->assertEquals([
            [
                'key' => 'K-02-buffer',
                'begin' => '2023-09-15',
                'end' => '2023-09-17',
                'consumption' => 0,
            ]
        ], $adapter->getSchedule($builder->release())[$adapter::VOLUME_BUFFERS]);
    }
}
