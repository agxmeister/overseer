<?php
namespace Tests\Unit;

use Codeception\Test\Unit;
use DateTime;
use Exception;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Builder;
use Watch\Schedule\Director;
use Watch\Schedule\Strategy\Strategy;

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
                    'estimatedDuration' => '4',
                    'estimatedBeginDate' => null,
                    'estimatedEndDate' => null,
                    'links' => [
                        'inward' => [],
                    ],
                ], [
                    'key' => 'K-02',
                    'estimatedDuration' => '4',
                    'estimatedBeginDate' => null,
                    'estimatedEndDate' => null,
                    'links' => [
                        'inward' => [
                            [
                                'key' => 'K-01',
                                'type' => 'Depends',
                            ]
                        ],
                    ],
                ], [
                    'key' => 'K-03',
                    'estimatedDuration' => '8',
                    'estimatedBeginDate' => null,
                    'estimatedEndDate' => null,
                    'links' => [
                        'inward' => [],
                    ],
                ]
            ],
            new DateTime('2023-09-09'),
            $this->makeEmpty(Strategy::class),
        );
        $this->assertEquals(
            [
                [
                    'key' => 'K-01',
                    'estimatedBeginDate' => '2023-09-05',
                    'estimatedEndDate' => '2023-09-08',
                    'isCritical' => true,
                ], [
                    'key' => 'K-02',
                    'estimatedBeginDate' => '2023-09-01',
                    'estimatedEndDate' => '2023-09-04',
                    'isCritical' => true,
                ], [
                    'key' => 'K-03',
                    'estimatedBeginDate' => '2023-09-01',
                    'estimatedEndDate' => '2023-09-08',
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
                    'estimatedDuration' => '4',
                    'estimatedBeginDate' => null,
                    'estimatedEndDate' => null,
                    'links' => [
                        'inward' => [],
                    ],
                ], [
                    'key' => 'K-02',
                    'estimatedDuration' => '4',
                    'estimatedBeginDate' => null,
                    'estimatedEndDate' => null,
                    'links' => [
                        'inward' => [],
                    ],
                ],
            ],
            new DateTime('2023-09-09'),
            $this->makeEmpty(Strategy::class, ['schedule' => function (Node $node) {
                $preceders = $node->getPreceders();
                $preceders[1]->unprecede($node);
                $preceders[1]->precede($preceders[0], Link::TYPE_SCHEDULE);
            }]),
        );
        $this->assertEquals(
            [
                [
                    'key' => 'K-01',
                    'estimatedBeginDate' => '2023-09-05',
                    'estimatedEndDate' => '2023-09-08',
                    'links' => [
                        'outward' => [
                            [
                                'key' => 'K-02',
                                'type' => 'Follows',
                            ],
                        ],
                    ],
                    'isCritical' => true,
                ], [
                    'key' => 'K-02',
                    'estimatedBeginDate' => '2023-09-01',
                    'estimatedEndDate' => '2023-09-04',
                    'links' => [
                        'inward' => [
                            [
                                'key' => 'K-01',
                                'type' => 'Follows',
                            ],
                        ],
                    ],
                    'isCritical' => true,
                ],
            ],
            $schedule,
        );
    }
}
