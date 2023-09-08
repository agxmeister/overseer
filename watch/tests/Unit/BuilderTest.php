<?php
namespace Tests\Unit;

use Exception;
use Codeception\Test\Unit;
use Watch\Schedule\Builder;
use Watch\Schedule\Strategy\Strategy;

class BuilderTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetSchedule()
    {
        $builder = new Builder();
        $schedule = $builder->getSchedule(
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
                            ['key' => 'K-01']
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
            '2023-09-09',
            $this->makeEmpty(Strategy::class),
        );
        $this->assertEquals(
            [
                [
                    'key' => 'K-01',
                    'estimatedBeginDate' => '2023-09-05',
                    'estimatedEndDate' => '2023-09-08',
                ], [
                    'key' => 'K-02',
                    'estimatedBeginDate' => '2023-09-01',
                    'estimatedEndDate' => '2023-09-04',
                ], [
                    'key' => 'K-03',
                    'estimatedBeginDate' => '2023-09-01',
                    'estimatedEndDate' => '2023-09-08',
                ]
            ],
            $schedule,
        );
    }
}
