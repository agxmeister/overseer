<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Watch\Schedule\Builder;

class BuilderTest extends Unit
{
    public function testAddCriticalChain()
    {
        $builder = new Builder();
        $builder->run(
            [
                [
                    'key' => 'K-01',
                    'duration' => '4',
                    'begin' => null,
                    'end' => null,
                    'links' => [
                        'inward' => [],
                        'outward' => [
                            [
                                'key' => 'K-02',
                                'type' => 'sequence',
                            ],
                        ],
                    ],
                ], [
                    'key' => 'K-02',
                    'duration' => '4',
                    'begin' => null,
                    'end' => null,
                    'links' => [
                        'inward' => [
                            [
                                'key' => 'K-01',
                                'type' => 'sequence',
                            ],
                        ],
                        'outward' => [],
                    ],
                ], [
                    'key' => 'K-03',
                    'duration' => '7',
                    'begin' => null,
                    'end' => null,
                    'links' => [
                        'inward' => [
                            [
                                'key' => 'K-01',
                                'type' => 'sequence',
                            ],
                        ],
                        'outward' => [],
                    ],
                ],
            ],
        );
        $builder->addCriticalChain();
        $this->assertEquals(['K-01', 'K-03'], $builder->release()[Builder::VOLUME_CRITICAL_CHAIN]);
    }
}
