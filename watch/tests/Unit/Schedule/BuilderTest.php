<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Tests\Support\Utils;
use Watch\Schedule\Builder;

class BuilderTest extends Unit
{
    public function testAddCriticalChain()
    {
        $builder = new Builder();
        $builder->run(
            Utils::getIssues('
                finish |           !| 2023-09-21
                K-01   |       **** |
                K-02   |   ****     | -> K-01
                K-03   |*******     | -> K-01
            '),
        );
        $builder->addCriticalChain();
        $this->assertEquals(['finish', 'K-01', 'K-03'], $builder->release()[Builder::VOLUME_CRITICAL_CHAIN]);
    }

    public function testAddFeedingBuffers()
    {
        $builder = new Builder();
        $builder->run(
            Utils::getIssues('
                finish |           !| 2023-09-21
                K-01   |       **** |
                K-02   | ****       | -> K-01
                K-03   |*******     | -> K-01
            ')
        );
        $builder
            ->addFeedingBuffers()
            ->addIssuesDates()
            ->addBuffersDates();
        $this->assertEquals([
            [
                'key' => 'K-02-buffer',
                'begin' => '2023-09-15',
                'end' => '2023-09-16',
            ]
        ], $builder->release()[Builder::VOLUME_BUFFERS]);
    }
}
