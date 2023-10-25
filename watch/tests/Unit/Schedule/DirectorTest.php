<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Watch\Schedule\Strategy\Limit\Basic;
use Watch\Schedule\Strategy\Limit\Simple;
use Watch\Schedule\Strategy\Limit\Strategy;
use Watch\Schedule\Strategy\Schedule\LateStart;
use Tests\Support\Utils;
use Watch\Schedule\Builder;
use Watch\Schedule\Director;

class DirectorTest extends Unit
{
    /**
     * @dataProvider dataCreateScheduleUnlimited
     */
    public function testCreateScheduleUnlimited($issues, $schedule)
    {
        $director = new Director(new Builder());
        $limitStrategy = $this->makeEmpty(Strategy::class);
        $scheduleStrategy = new LateStart(new \DateTimeImmutable('2023-09-21'));
        $this->assertSchedule($schedule, $director->create($issues, $limitStrategy, $scheduleStrategy));
    }

    /**
     * @dataProvider dataCreateScheduleSimple
     */
    public function testCreateScheduleSimple($issues, $schedule)
    {
        $director = new Director(new Builder());
        $limitStrategy = new Simple();
        $scheduleStrategy = new LateStart(new \DateTimeImmutable('2023-09-21'));
        $this->assertSchedule($schedule, $director->create($issues, $limitStrategy, $scheduleStrategy));
    }

    /**
     * @dataProvider dataCreateScheduleBasic
     */
    public function testCreateScheduleBasic($issues, $schedule)
    {
        $director = new Director(new Builder());
        $limitStrategy = new Basic();
        $scheduleStrategy = new LateStart(new \DateTimeImmutable('2023-09-21'));
        $this->assertSchedule($schedule, $director->create($issues, $limitStrategy, $scheduleStrategy));
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

    protected function dataCreateScheduleUnlimited(): array
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

    protected function dataCreateScheduleSimple(): array
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

    protected function dataCreateScheduleBasic(): array
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
