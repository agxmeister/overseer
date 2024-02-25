<?php
namespace Tests\Unit\Schedule\Description;

use Codeception\Test\Unit;
use Watch\Schedule\Description\Utils;

class UtilsTest extends Unit
{
    /**
     * @dataProvider dataGetMilestoneNames
     */
    public function testGetMilestoneNames($description, $milestoneNames)
    {
        self::assertEquals($milestoneNames, Utils::getMilestoneNames($description));
    }

    /**
     * @dataProvider dataGetMilestones
     */
    public function testGetMilestones($description, $milestones)
    {
        self::assertEquals($milestones, Utils::getMilestones($description));
    }

    /**
     * @dataProvider dataGetProjectBeginDate
     */
    public function testGetProjectBeginDate($description, $beginDate)
    {
        self::assertEquals(new \DateTimeImmutable($beginDate), Utils::getProjectBeginDate($description));
    }

    /**
     * @dataProvider dataGetProjectEndDate
     */
    public function testGetProjectEndDate($description, $endDate)
    {
        self::assertEquals(new \DateTimeImmutable($endDate), Utils::getProjectEndDate($description));
    }

    /**
     * @dataProvider dataGetNowDate
     */
    public function testGetNowDate($description, $nowDate)
    {
        self::assertEquals(new \DateTimeImmutable($nowDate), Utils::getNowDate($description));
    }

    /**
     * @dataProvider dataGetProjectLength
     */
    public function testGetProjectLength($description, $length)
    {
        self::assertEquals($length, Utils::getProjectLength($description));
    }

    /**
     * @dataProvider dataGetScheduleCriticalChain
     */
    public function testGetScheduleCriticalChain($description, $criticalChain)
    {
        self::assertEquals($criticalChain, Utils::getSchedule($description)['criticalChain']);
    }

    protected function dataGetMilestoneNames(): array
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

    protected function dataGetMilestones(): array
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

    protected function dataGetProjectBeginDate(): array
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

    protected function dataGetProjectEndDate(): array
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

    protected function dataGetNowDate(): array
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

    protected function dataGetProjectLength(): array
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

    protected function dataGetScheduleCriticalChain(): array
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
