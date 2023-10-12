<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use DateTime;
use Exception;
use Watch\Schedule\Builder;
use Watch\Schedule\Director;
use Watch\Schedule\Strategy\Strategy;
use Watch\Schedule\Strategy\Test;

class DirectorTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetScheduleUnlimited()
    {
        $director = new Director(new Builder());
        $schedule = $director->create(
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
                        'inward' => [],
                        'outward' => [],
                    ],
                ],
            ],
            new DateTime('2023-09-21'),
            $this->makeEmpty(Strategy::class),
        );
        $this->assertEquals(
            [
                'issues' => [
                    [
                        'key' => 'K-01',
                        'begin' => '2023-09-13',
                        'end' => '2023-09-16',
                    ], [
                        'key' => 'K-02',
                        'begin' => '2023-09-09',
                        'end' => '2023-09-12',
                    ], [
                        'key' => 'K-03',
                        'begin' => '2023-09-10',
                        'end' => '2023-09-16',
                    ],
                ],
                'criticalChain' => ['K-01', 'K-02'],
                'buffers' => [],
                'links' => [
                    [
                        'from' => "finish-buffer",
                        'to' => 'finish',
                        'type' => 'schedule',
                    ],
                    [
                        'from' => "K-01",
                        'to' => 'finish-buffer',
                        'type' => 'schedule',
                    ],
                    [
                        'from' => "K-03",
                        'to' => 'finish-buffer',
                        'type' => 'schedule',
                    ],
                    [
                        'from' => 'K-02',
                        'to' => 'K-01',
                        'type' => 'sequence',
                    ],
                ]
            ],
            $schedule,
        );
    }

    /**
     * @throws Exception
     */
    public function testGetScheduleLimited()
    {
        $builder = new Director(new Builder());
        $schedule = $builder->create(
            [
                [
                    'key' => 'K-01',
                    'duration' => '4',
                    'begin' => null,
                    'end' => null,
                    'links' => [
                        'inward' => [],
                        'outward' => [],
                    ],
                ], [
                    'key' => 'K-02',
                    'duration' => '4',
                    'begin' => null,
                    'end' => null,
                    'links' => [
                        'inward' => [],
                        'outward' => [],
                    ],
                ],
            ],
            new DateTime('2023-09-21'),
            new Test(),
        );
        $this->assertEquals(
            [
                'issues' => [
                    [
                        'key' => 'K-01',
                        'begin' => '2023-09-13',
                        'end' => '2023-09-16',
                    ], [
                        'key' => 'K-02',
                        'begin' => '2023-09-09',
                        'end' => '2023-09-12',
                    ],
                ],
                'criticalChain' => ['K-01', 'K-02'],
                'buffers' => [],
                'links' => [
                    [
                        'from' => "finish-buffer",
                        'to' => 'finish',
                        'type' => 'schedule',
                    ],
                    [
                        'from' => "K-01",
                        'to' => 'finish-buffer',
                        'type' => 'schedule',
                    ],
                    [
                        'from' => 'K-02',
                        'to' => 'K-01',
                        'type' => 'schedule',
                    ],
                ],
            ],
            $schedule,
        );
    }
}
