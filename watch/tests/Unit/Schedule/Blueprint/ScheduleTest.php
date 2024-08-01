<?php
namespace Tests\Unit\Schedule\Blueprint;

use Codeception\Test\Unit;
use Watch\Blueprint\Builder\Asset\Drawing;
use Watch\Blueprint\Builder\Director;
use Watch\Blueprint\Builder\Schedule as ScheduleBlueprintBuilder;
use Watch\Config;
use Watch\Schedule\Serializer\Project;

class ScheduleTest extends Unit
{
    /**
     * @dataProvider dataGetMilestoneNames
     */
    public function testGetMilestoneNames($drawingContent, $milestoneNames)
    {
        $drawing = new Drawing($drawingContent);
        $blueprintBuilder = new ScheduleBlueprintBuilder(new Config());
        $blueprintDirector = new Director();
        $blueprintDirector->build($blueprintBuilder, $drawing);
        $blueprint = $blueprintBuilder->flush();
        self::assertEquals($milestoneNames, $blueprint->getMilestoneNames());
    }

    /**
     * @dataProvider dataGetMilestones
     */
    public function testGetMilestones($drawingContent, $milestones)
    {
        $drawing = new Drawing($drawingContent);
        $blueprintBuilder = new ScheduleBlueprintBuilder(new Config());
        $blueprintDirector = new Director();
        $blueprintDirector->build($blueprintBuilder, $drawing);
        $blueprint = $blueprintBuilder->flush();
        self::assertEquals($milestones, $blueprint->getMilestonesData());
    }

    /**
     * @dataProvider dataGetProjectBeginDate
     */
    public function testGetProjectBeginDate($drawingContent, $beginDate)
    {
        $drawing = new Drawing($drawingContent);
        $blueprintBuilder = new ScheduleBlueprintBuilder(new Config());
        $blueprintDirector = new Director();
        $blueprintDirector->build($blueprintBuilder, $drawing);
        $blueprint = $blueprintBuilder->flush();
        self::assertEquals(new \DateTimeImmutable($beginDate), $blueprint->getProjectBeginDate());
    }

    /**
     * @dataProvider dataGetProjectEndDate
     */
    public function testGetProjectEndDate($drawingContent, $endDate)
    {
        $drawing = new Drawing($drawingContent);
        $blueprintBuilder = new ScheduleBlueprintBuilder(new Config());
        $blueprintDirector = new Director();
        $blueprintDirector->build($blueprintBuilder, $drawing);
        $blueprint = $blueprintBuilder->flush();
        self::assertEquals(new \DateTimeImmutable($endDate), $blueprint->getProjectEndDate());
    }

    /**
     * @dataProvider dataGetNowDate
     */
    public function testGetNowDate($drawingContent, $nowDate)
    {
        $drawing = new Drawing($drawingContent);
        $blueprintBuilder = new ScheduleBlueprintBuilder(new Config());
        $blueprintDirector = new Director();
        $blueprintDirector->build($blueprintBuilder, $drawing);
        $blueprint = $blueprintBuilder->flush();
        self::assertEquals(new \DateTimeImmutable($nowDate), $blueprint->nowDate);
    }

    /**
     * @dataProvider dataGetProjectLength
     */
    public function testGetProjectLength($drawingContent, $length)
    {
        $drawing = new Drawing($drawingContent);
        $blueprintBuilder = new ScheduleBlueprintBuilder(new Config());
        $blueprintDirector = new Director();
        $blueprintDirector->build($blueprintBuilder, $drawing);
        $blueprint = $blueprintBuilder->flush();
        self::assertEquals($length, $blueprint->getLength());
    }

    /**
     * @dataProvider dataGetScheduleCriticalChain
     */
    public function testGetScheduleCriticalChain($drawingContent, $criticalChain)
    {
        $drawing = new Drawing($drawingContent);
        $blueprintBuilder = new ScheduleBlueprintBuilder(new Config());
        $blueprintDirector = new Director();
        $blueprintDirector->build($blueprintBuilder, $drawing);
        $blueprint = $blueprintBuilder->flush();
        self::assertEquals($criticalChain, $blueprint->getSchedule()[Project::VOLUME_CRITICAL_CHAIN]);
    }

    /**
     * @dataProvider dataGetBufferConsumption
     */
    public function testGetBufferConsumption($drawingContent, $bufferConsumption)
    {
        $drawing = new Drawing($drawingContent);
        $blueprintBuilder = new ScheduleBlueprintBuilder(new Config());
        $blueprintDirector = new Director();
        $blueprintDirector->build($blueprintBuilder, $drawing);
        $blueprint = $blueprintBuilder->flush();
        foreach ($blueprint->getSchedule()[Project::VOLUME_BUFFERS] as $buffer) {
            self::assertEquals($bufferConsumption[$buffer['key']], $buffer['consumption']);
        }
    }

    public static function dataGetMilestoneNames(): array
    {
        return [
            [
                '
                    K-01 |...| @ M-01
                    M-01     ^ # 2023-01-01
                ',
                [],
            ], [
                '
                    K-01 |xxx   | @ M-01
                    K-02 |   xxx| @ M-02
                    M-01     ^    # 2023-01-01
                    M-02        ^ # 2023-01-04
                ',
                ['M-01'],
            ],
        ];
    }

    public static function dataGetMilestones(): array
    {
        return [
            [
                '
                    K-01 |...| @ M-01
                    M-01     ^ # 2023-01-01
                ',
                [
                    [
                        'key' => 'M-01',
                        'begin' => '2022-12-29',
                        'end' => '2023-01-01',
                    ],
                ],
            ], [
                '
                    K-01 |...| @ M-01
                    M-01 ^     # 2023-01-01
                ',
                [
                    [
                        'key' => 'M-01',
                        'begin' => '2023-01-01',
                        'end' => '2023-01-04',
                    ],
                ],
            ], [
                '
                    K-01 |xxx   | @ M-01
                    K-02 |   xxx| @ M-02
                    M-01     ^    # 2023-01-01
                    M-02        ^ # 2023-01-04
                    PRJ         ^ # 2023-01-04
                ',
                [
                    [
                        'key' => 'M-01',
                        'begin' => '2022-12-29',
                        'end' => '2023-01-01',
                    ], [
                        'key' => 'M-02',
                        'begin' => '2023-01-01',
                        'end' => '2023-01-04',
                    ], [
                        'key' => 'PRJ',
                        'begin' => '2022-12-29',
                        'end' => '2023-01-04',
                    ],
                ],
            ], [
                '
                    K-01 |xxx   | @ M-01
                    K-02 |   xxx| @ M-02
                    M-01 ^        # 2023-01-01
                    M-02     ^    # 2023-01-04
                    PRJ  ^        # 2023-01-01
                ',
                [
                    [
                        'key' => 'M-01',
                        'begin' => '2023-01-01',
                        'end' => '2023-01-04',
                    ], [
                        'key' => 'M-02',
                        'begin' => '2023-01-04',
                        'end' => '2023-01-07',
                    ],  [
                        'key' => 'PRJ',
                        'begin' => '2023-01-01',
                        'end' => '2023-01-07',
                    ],
                ],
            ],
        ];
    }

    public static function dataGetProjectBeginDate(): array
    {
        return [
            [
                '
                    K-01 |...| @ M-01
                    M-01 ^     # 2023-07-15
                ',
                '2023-07-15',
            ], [
                '
                    K-01 |...| @ M-01
                    M-01     ^ # 2023-07-15
                ',
                '2023-07-12',
            ], [
                '
                    K-01 |  ...| @ M-01
                    M-01   ^     # 2023-07-15
                ',
                '2023-07-15',
            ], [
                '
                    K-01 |...  | @ M-01
                    M-01     ^   # 2023-07-15
                ',
                '2023-07-12',
            ],
        ];
    }

    public static function dataGetProjectEndDate(): array
    {
        return [
            [
                '
                    K-01 |...| @ M-01
                    M-01 ^     # 2023-07-15
                ',
                '2023-07-18',
            ], [
                '
                    K-01 |...| @ M-01
                    M-01     ^ # 2023-07-15
                ',
                '2023-07-15',
            ], [
                '
                    K-01 |  ...| @ M-01
                    M-01   ^     # 2023-07-15
                ',
                '2023-07-18',
            ], [
                '
                    K-01 |...  | @ M-01
                    M-01       ^ # 2023-07-15
                ',
                '2023-07-15',
            ], [
                '
                    K-01 |...  | @ M-01
                    M-01     ^   # 2023-07-15
                ',
                '2023-07-15',
            ],
        ];
    }

    public static function dataGetNowDate(): array
    {
        return [
            [
                '
                         >     # 2023-07-15
                    K-01 |...| @ M-01
                    M-01 ^
                ',
                '2023-07-15',
            ], [
                '
                         >
                    K-01 |...| @ M-01
                    M-01 ^     # 2023-07-15
                ',
                '2023-07-15',
            ], [
                '
                         >
                    K-01 |...| @ M-01
                    M-01     ^ # 2023-07-15
                ',
                '2023-07-11',
            ], [
                '
                         >
                    K-01 |  ...| @ M-01
                    M-01   ^     # 2023-07-15
                ',
                '2023-07-13',
            ], [
                '
                            >
                    K-01 |  ...| @ M-01
                    M-01   ^     # 2023-07-15
                ',
                '2023-07-16',
            ], [
                '
                               >
                    K-01 |...  | @ M-01
                    M-01     ^   # 2023-07-15
                ',
                '2023-07-17',
            ], [
                '
                            >
                    K-01 |...  | @ M-01
                    M-01     ^   # 2023-07-15
                ',
                '2023-07-14',
            ],
        ];
    }

    public static function dataGetProjectLength(): array
    {
        return [
            [
                '
                    K-01 |...| @ M-01
                    M-01 ^     # 2023-07-15
                ',
                '3',
            ], [
                '
                    K-01 |...  | @ M-01
                    M-01 ^       # 2023-07-15
                ',
                '3',
            ], [
                '
                    K-01 |  ...| @ M-01
                    M-01   ^     # 2023-07-15
                ',
                '3',
            ], [
                '
                    K-01 |  ...  | @ M-01
                    M-01   ^       # 2023-07-15
                ',
                '3',
            ], [
                '
                    K-01 |      xxxx  | @ M-01
                    K-02 |  xxxx      | @ K-01
                    M-01   ^            # 2023-07-15
                ',
                '8',
            ], [
                '
                    K-01 |      xxxx  | @ M-01
                    K-02 |  xxxx      | @ K-01
                    K-03 |     *****  | @ M-01
                    M-01   ^            # 2023-07-15
                ',
                '8',
            ], [
                '
                    PB    |          ___  | @ M-01
                    K-01  |       xxx     | @ PB
                    K-02  |    xxx        | @ K-01
                    FB-01 |       ___     | @ PB
                    K-03  |  *****        | @ FB-01
                    M-01    ^               # 2023-07-15
                ',
                '11',
            ],
        ];
    }

    public static function dataGetScheduleCriticalChain(): array
    {
        return [
            [
                '
                    K-01 |xxx| @ M-01
                    M-01     ^ # 2023-07-15
                ',
                ['K-01'],
            ], [
                '
                    K-01 |xxx   | @ K-02 @ M-01
                    K-02 |   xxx| @ M-02
                    M-01     ^    # 2023-07-12
                    M-02        ^ # 2023-07-15
                ',
                ['K-02', 'K-01'],
            ], [
                '
                    PB   |   __| @ M-01
                    K-01 |xxx  | @ PB
                    M-01       ^ # 2023-07-15
                ',
                ['K-01'],
            ],
        ];
    }

    public static function dataGetBufferConsumption(): array
    {
        return [
            [
                '
                              > 
                    K-01 |xxxx  | @ M-01
                    PB   |    __| @ M-01
                    PRJ         ^ # 2023-07-15
                ',
                ['PB' => 0],
            ],
            [
                '
                               >
                    K-01 |xxxx  | @ M-01
                    PB   |    !_| @ M-01
                    PRJ         ^ # 2023-07-15
                ',
                ['PB' => 1],
            ]
        ];
    }
}
