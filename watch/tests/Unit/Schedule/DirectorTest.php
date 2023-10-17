<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use DateTime;
use Exception;
use Tests\Support\Utils;
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
            Utils::getIssues('
                K-01     xxxx
                K-02 xxxx     K-01
                K-03  xxxxxxx
            '),
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
                        'begin' => '2023-09-06',
                        'end' => '2023-09-12',
                    ],
                ],
                'criticalChain' => ['finish', 'K-01', 'K-02'],
                'buffers' => [
                    [
                        'key' => 'finish-buffer',
                        'begin' => '2023-09-17',
                        'end' => '2023-09-20',
                    ],
                    [
                        'key' => 'K-03-buffer',
                        'begin' => '2023-09-13',
                        'end' => '2023-09-16',
                    ],
                ],
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
                        'from' => "K-03-buffer",
                        'to' => 'finish-buffer',
                        'type' => 'schedule',
                    ],
                    [
                        'from' => 'K-02',
                        'to' => 'K-01',
                        'type' => 'sequence',
                    ],
                    [
                        'from' => 'K-03',
                        'to' => 'K-03-buffer',
                        'type' => 'schedule',
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
            Utils::getIssues('
                K-01 xxxx
                K-02 xxxx
            '),
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
                'criticalChain' => ['finish', 'K-01', 'K-02'],
                'buffers' => [
                    [
                        'key' => 'finish-buffer',
                        'begin' => '2023-09-17',
                        'end' => '2023-09-20',
                    ],
                ],
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
