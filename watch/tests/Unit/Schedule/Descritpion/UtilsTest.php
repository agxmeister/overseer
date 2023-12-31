<?php
namespace Tests\Unit\Schedule\Description;

use Codeception\Test\Unit;
use Watch\Schedule\Description\Utils;

class UtilsTest extends Unit
{
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

    protected function dataGetMilestones(): array
    {
        return [
            [
                '
                    K-01 |...| @ M-01
                    M-01     ^ # 2023-01-01
                ',
                ['M-01'],
            ], [
                '
                    K-01 |xxx   | @ M-01
                    K-02 |   xxx| @ M-02
                    M-01     ^    # 2023-01-01
                    M-02        ^ # 2023-01-04
                ',
                ['M-01', 'M-02'],
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
                    M-01 ^     # 2023-07-15
                ',
                '2023-07-17',
            ], [
                '
                    K-01 |...  | @ M-01
                    M-01       ^ # 2023-07-15
                ',
                '2023-07-10',
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
                    M-01 ^     # 2023-07-15
                ',
                '2023-07-20',
            ], [
                '
                    K-01 |...  | @ M-01
                    M-01       ^ # 2023-07-15
                ',
                '2023-07-13',
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
                    M-01 ^     # 2023-07-15
                ',
                '2023-07-15',
            ], [
                '
                            >
                    K-01 |  ...| @ M-01
                    M-01 ^     # 2023-07-15
                ',
                '2023-07-18',
            ], [
                '
                               >
                    K-01 |...  | @ M-01
                    M-01       ^ # 2023-07-15
                ',
                '2023-07-15',
            ], [
                '
                            >
                    K-01 |...  | @ M-01
                    M-01       ^ # 2023-07-15
                ',
                '2023-07-12',
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
                    M-01 ^       # 2023-07-15
                ',
                '3',
            ], [
                '
                    K-01 |  ...  | @ M-01
                    M-01 ^         # 2023-07-15
                ',
                '3',
            ], [
                '
                    K-01 |      xxxx  | @ M-01
                    K-02 |  xxxx      | @ K-01
                    M-01 ^              # 2023-07-15
                ',
                '8',
            ], [
                '
                    K-01 |      xxxx  | @ M-01
                    K-02 |  xxxx      | @ K-01
                    K-03 |     *****  | @ M-01
                    M-01 ^              # 2023-07-15
                ',
                '8',
            ], [
                '
                    PB    |          ___  | @ M-01
                    K-01  |       xxx     | @ PB
                    K-02  |    xxx        | @ K-01
                    FB-01 |       ___     | @ PB
                    K-03  |  *****        | @ FB-01
                    M-01  ^                 # 2023-07-15
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
                ['M-01', 'K-01'],
            ], [
                '
                    K-01 |xxx   | @ K-02 @ M-01
                    K-02 |   xxx| @ M-02
                    M-01     ^    # 2023-07-12
                    M-02        ^ # 2023-07-15
                ',
                ['M-02', 'K-02', 'K-01'],
            ], [
                '
                    PB   |   __| @ M-01
                    K-01 |xxx  | @ PB
                    M-01       ^ # 2023-07-15
                ',
                ['M-01', 'K-01'],
            ],
        ];
    }
}
