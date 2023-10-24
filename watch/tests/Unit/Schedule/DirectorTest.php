<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use DateTime;
use Tests\Support\Utils;
use Watch\Schedule\Builder;
use Watch\Schedule\Director;
use Watch\Schedule\Strategy\Basic;
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
        $this->assertSchedule($schedule, $director->create($issues, $date, $strategy));
    }

    /**
     * @dataProvider dataGetScheduleLimited
     */
    public function testGetScheduleLimited($issues, $schedule)
    {
        $director = new Director(new Builder());
        $date = new DateTime('2023-09-21');
        $strategy = new Test();
        $this->assertSchedule($schedule, $director->create($issues, $date, $strategy));
    }

    /**
     * @dataProvider dataGetScheduleBasic
     */
    public function testGetScheduleBasic($issues, $schedule)
    {
        $director = new Director(new Builder());
        $date = new DateTime('2023-09-21');
        $strategy = new Basic();
        $this->assertSchedule($schedule, $director->create($issues, $date, $strategy));
    }

    protected function assertSchedule($expected, $actual)
    {
        $this->assertSameSize($expected, $actual, 'Number of volumes in the schedule is differ from expected.');
        foreach (['issues', 'buffers', 'links'] as $volume) {
            $this->assertScheduleVolume($expected[$volume], $actual[$volume], $volume);
        }
        $this->assertEquals($expected['criticalChain'], $actual['criticalChain'], 'Critical chain of the schedule is differ from expected.');
    }

    protected function assertScheduleVolume($expected, $actual, $volume)
    {
        $this->assertSameSize($expected, $actual, "Number of items in volume '{$volume}' is differ from expected.");
        usort($expected, fn($a, $b) => $a < $b ? -1 : ($a > $b ? 1 : 0));
        usort($actual, fn($a, $b) => $a < $b ? -1 : ($a > $b ? 1 : 0));
        for ($i = 0; $i < sizeof($actual); $i++) {
            $this->assertEquals($expected[$i], $actual[$i], "Items in volume '{$volume}' are mismatched.");
        }
    }

    protected function dataGetScheduleUnlimited(): array
    {
        return [
            [
                Utils::getIssues('
                    K-01          |    ....       |
                    K-02          |....           | & K-01
                    K-03          |.......        |
                '),
                Utils::getSchedule('
                    finish-buffer |           ____| @ finish
                    K-01          |       xxxx    | @ finish-buffer
                    K-02          |   xxxx        | & K-01
                    K-03-buffer   |       ____    | @ finish-buffer
                    K-03          |*******        | @ K-03-buffer
                    finish                        ^ # 2023-09-21
                '),
            ], [
                Utils::getIssues('
                    K-01          |       ....      |
                    K-02          |....             | & K-01
                    K-03          |.......          | & K-01
                '),
                Utils::getSchedule('
                    finish-buffer |           ______| @ finish
                    K-01          |       xxxx      | @ finish-buffer
                    K-02-buffer   |     __          | @ K-01
                    K-02          | ****            | & K-01, @ K-02-buffer
                    K-03          |xxxxxxx          | & K-01
                    finish                          ^ # 2023-09-21
                '),
            ],
        ];
    }

    protected function dataGetScheduleLimited(): array
    {
        return [
            [
                Utils::getIssues('
                    K-01          |....        |
                    K-02          |....        |
                '),
                Utils::getSchedule('
                    finish-buffer |        ____| @ finish
                    K-01          |    xxxx    | @ finish-buffer
                    K-02          |xxxx        | @ K-01
                    finish                     ^ # 2023-09-21
                '),
            ], [
                Utils::getIssues('
                    K-01          |....              |
                    K-02          |....              |
                    K-03          |....              |
                '),
                Utils::getSchedule('
                    finish-buffer |            ______| @ finish
                    K-01          |        xxxx      | @ finish-buffer
                    K-02          |    xxxx          | @ K-01
                    K-03          |xxxx              | @ K-02
                    finish                           ^ # 2023-09-21
                '),
            ],
        ];
    }

    protected function dataGetScheduleBasic(): array
    {
        return [
            [
                Utils::getIssues('
                    K-01          |....    |
                    K-02          |....    |
                '),
                Utils::getSchedule('
                    finish-buffer |      __| @ finish
                    K-01          |  xxxx  | @ finish-buffer
                    K-02-buffer   |    __  | @ finish-buffer
                    K-02          |****    | @ K-02-buffer
                    finish                 ^ # 2023-09-21
                '),
            ], [
                Utils::getIssues('
                    K-01          |....        |
                    K-02          |....        |
                    K-03          |....        |
                '),
                Utils::getSchedule('
                    finish-buffer |        ____| @ finish
                    K-01          |xxxx        | @ K-02
                    K-02          |    xxxx    | @ finish-buffer
                    K-03-buffer   |      __    | @ finish-buffer
                    K-03          |  ****      | @ K-03-buffer
                    finish                     ^ # 2023-09-21
                '),
            ], [
                Utils::getIssues('
                    K-01          |....        |
                    K-02          |....        | & K-01
                '),
                Utils::getSchedule('
                    finish-buffer |        ____| @ finish
                    K-01          |    xxxx    | @ finish-buffer
                    K-02          |xxxx        | & K-01
                    finish                     ^ # 2023-09-21
                '),
            ], [
                Utils::getIssues('
                    K-01          |....          |
                    K-02          |....          | & K-01
                    K-03          |....          | & K-01
                '),
                Utils::getSchedule('
                    finish-buffer |          ____| @ finish
                    K-01          |      xxxx    | @ finish-buffer
                    K-02          |  xxxx        | & K-01
                    K-03-buffer   |    __        | @ K-01
                    K-03          |****          | & K-01, @ K-03-buffer
                    finish                       ^ # 2023-09-21
                '),
            ],
        ];
    }
}
