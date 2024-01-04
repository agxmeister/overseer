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
     * @dataProvider dataGetMilestoneBeginDate
     */
    public function testGetMilestoneBeginDate($description, $beginDate)
    {
        self::assertEquals(new \DateTimeImmutable($beginDate), Utils::getMilestoneBeginDate($description));
    }

    /**
     * @dataProvider dataGetMilestoneEndDate
     */
    public function testGetMilestoneEndDate($description, $endDate)
    {
        self::assertEquals(new \DateTimeImmutable($endDate), Utils::getMilestoneEndDate($description));
    }

    /**
     * @dataProvider dataGetNowDate
     */
    public function testGetNowDate($description, $nowDate)
    {
        self::assertEquals(new \DateTimeImmutable($nowDate), Utils::getNowDate($description));
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

    protected function dataGetMilestoneBeginDate(): array
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

    protected function dataGetMilestoneEndDate(): array
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
}
