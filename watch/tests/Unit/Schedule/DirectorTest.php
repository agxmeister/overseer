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
            $this->getIssues('
                K-01     xxxx
                K-02 xxxx     K-01
                K-03 xxxxxxx
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
            $this->getIssues('
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

    private function getIssues($description): array
    {
        $lines = [...array_filter(array_map(fn($line) => trim($line), explode("\n", $description)), fn($line) => strlen($line) > 0)];

        $issues = [];
        $links = [];
        foreach ($lines as $line) {
            $issueData = preg_split("/\s+/", $line);
            $key = $issueData[0];
            $duration = strlen($issueData[1]);
            $linkName = $issueData[2] ?? null;
            $issues[$key] = [
                'key' => $key,
                'duration' => $duration,
                'begin' => null,
                'end' => null,
                'links' => [
                    'inward' => [],
                    'outward' => [],
                ]
            ];
            if (!is_null($linkName)) {
                $links[] = ['from' => $key, 'to' => $linkName];
            }
        }
        foreach($links as $link) {
            $issues[$link['from']]['links']['inward'][] = [
                'key' => $link['to'],
                'type' => 'sequence',
            ];
            $issues[$link['to']]['links']['outward'][] = [
                'key' => $link['from'],
                'type' => 'sequence',
            ];
        }

        return array_values($issues);
    }
}
