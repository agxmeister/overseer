<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use DateTime;
use Tests\Support\Utils;
use Watch\Schedule\Builder;
use Watch\Schedule\Director;
use Watch\Schedule\Strategy\Strategy;
use Watch\Schedule\Strategy\Test;

class DirectorTest extends Unit
{
    /**
     * @dataProvider dataGetScheduleUnlimited
     */
    public function testGetScheduleUnlimited($issues, $schedule)
    {
        $director = new Director(new Builder());
        $date = new DateTime('2023-09-21');
        $strategy = $this->makeEmpty(Strategy::class);
        $this->assertEquals($schedule, $director->create($issues, $date, $strategy));
    }

    /**
     * @dataProvider dataGetScheduleLimited
     */
    public function testGetScheduleLimited($issues, $schedule)
    {
        $director = new Director(new Builder());
        $date = new DateTime('2023-09-21');
        $strategy = new Test();
        $this->assertEquals($schedule, $director->create($issues, $date, $strategy));
    }

    protected function dataGetScheduleUnlimited(): array
    {
        return [
            [
                Utils::getIssues('
                    K-01          |    ....        |
                    K-02          |....            |          & K-01
                    K-03          |.......         |
                '),
                Utils::getSchedule('
                    finish        |               !| # 2023-09-21
                    finish-buffer |           ____ | @ finish
                    K-01          |       xxxx     | @ finish-buffer
                    K-03-buffer   |       ____     | @ finish-buffer
                    K-02          |   xxxx         | & K-01
                    K-03          |*******         | @ K-03-buffer
                '),
            ], [
                Utils::getIssues('
                    K-01          |       ....       |
                    K-03          |.......           | & K-01
                    K-02          |....              | & K-01
                '),
                Utils::getSchedule('
                    finish        |                 !| # 2023-09-21
                    finish-buffer |           ______ | @ finish
                    K-01          |       xxxx       | @ finish-buffer
                    K-03          |xxxxxxx           | & K-01
                    K-02          | ****             | & K-01, @ K-02-buffer
                    K-02-buffer   |     __           | @ K-01
                '),
            ],
        ];
    }

    protected function dataGetScheduleLimited(): array
    {
        return [
            [
                Utils::getIssues('
                    K-01          |....         |
                    K-02          |....         |
                '),
                Utils::getSchedule('
                    finish        |            !| # 2023-09-21
                    finish-buffer |        ____ | @ finish
                    K-01          |    xxxx     | @ finish-buffer
                    K-02          |xxxx         | @ K-01
                '),
            ],
        ];
    }
}
