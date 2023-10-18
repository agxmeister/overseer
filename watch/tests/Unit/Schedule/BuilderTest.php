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
                K-01 |       xxxx|
                K-02 |   xxxx    | K-01
                K-03 |xxxxxxx    | K-01
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
                K-01 |       xxxx|
                K-02 | xxxx      | K-01
                K-03 |xxxxxxx    | K-01
            ')
        );
        $builder
            ->addFeedingBuffers()
            ->addIssuesDates()
            ->addBuffersDates();
        $this->assertEquals([
            [
                'key' => 'K-02-buffer',
                'begin' => '2023-10-24',
                'end' => '2023-10-25',
            ]
        ], $builder->release()[Builder::VOLUME_BUFFERS]);
    }
}
