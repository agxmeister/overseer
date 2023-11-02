<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Tests\Support\Utils;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\FromExisting as FromExistingBuilder;
use Watch\Schedule\Builder\Strategy\Schedule\FromAnchor;

class BuilderTest extends Unit
{
    public function testAddCriticalChain()
    {
        $builder = new FromExistingBuilder(
            Utils::getIssues('
                K-01   |       xxxx|
                K-02   |   ****    | & K-01
                K-03   |xxxxxxx    | @ K-01
                                   ^ # 2023-09-21
            '),
            new \DateTimeImmutable('2023-01-01'),
            new FromAnchor(),
        );
        $builder->run();
        $builder->addMilestone();
        $this->assertEquals(['finish', 'K-01', 'K-03'], $builder->release()[Builder::VOLUME_CRITICAL_CHAIN]);
    }

    public function testAddFeedingBuffers()
    {
        $builder = new FromExistingBuilder(
            Utils::getIssues('
                K-01   |       xxxx|
                K-02   | ****      | & K-01
                K-03   |xxxxxxx    | @ K-01
                                   ^ # 2023-09-21
            '),
            new \DateTimeImmutable('2023-01-01'),
            new FromAnchor(),
        );
        $builder->run();
        $builder
            ->addMilestone()
            ->addFeedingBuffers()
            ->addDates();
        $this->assertEquals([
            [
                'key' => 'K-02-buffer',
                'begin' => '2023-09-15',
                'end' => '2023-09-16',
                'consumption' => 0,
            ]
        ], $builder->release()[Builder::VOLUME_BUFFERS]);
    }
}
