<?php
namespace Tests\Unit\Schedule\Description;

use Codeception\Test\Unit;
use Watch\Description\Schedule;

class UtilsTest extends Unit
{
    /**
     * @dataProvider dataGetMilestoneNames
     */
    public function testGetMilestoneNames($description, $milestoneNames)
    {
        $schedule = new Schedule($description);
        self::assertEquals($milestoneNames, $schedule->getMilestoneNames());
    }

    /**
     * @dataProvider dataGetMilestones
     */
    public function testGetMilestones($description, $milestones)
    {
        $schedule = new Schedule($description);
        self::assertEquals($milestones, $schedule->getMilestones());
    }

    /**
     * @dataProvider dataGetProjectBeginDate
     */
    public function testGetProjectBeginDate($description, $beginDate)
    {
        $schedule = new Schedule($description);
        self::assertEquals(new \DateTimeImmutable($beginDate), $schedule->getProjectBeginDate());
    }

    /**
     * @dataProvider dataGetProjectEndDate
     */
    public function testGetProjectEndDate($description, $endDate)
    {
        $schedule = new Schedule($description);
        self::assertEquals(new \DateTimeImmutable($endDate), $schedule->getProjectEndDate());
    }

    /**
     * @dataProvider dataGetNowDate
     */
    public function testGetNowDate($description, $nowDate)
    {
        $schedule = new Schedule($description);
        self::assertEquals(new \DateTimeImmutable($nowDate), $schedule->getNowDate());
    }

    /**
     * @dataProvider dataGetProjectLength
     */
    public function testGetProjectLength($description, $length)
    {
        $schedule = new Schedule($description);
        self::assertEquals($length, $schedule->getProjectLength());
    }

    /**
     * @dataProvider dataGetScheduleCriticalChain
     */
    public function testGetScheduleCriticalChain($description, $criticalChain)
    {
        $schedule = new Schedule($description);
        self::assertEquals($criticalChain, $schedule->getSchedule()['criticalChain']);
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
                    ],
                ],
            ], [
                '
                    K-01 |xxx   | @ M-01
                    K-02 |   xxx| @ M-02
                    M-01 ^        # 2023-01-01
                    M-02     ^    # 2023-01-04
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
                    M-01   ^   # 2023-07-15
                ',
                '2023-07-18',
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
}
