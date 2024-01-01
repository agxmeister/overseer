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
}
