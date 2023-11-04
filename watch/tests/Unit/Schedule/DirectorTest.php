<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Tests\Support\Utils;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\FromScratch as FromScratchBuilder;
use Watch\Schedule\Builder\FromExisting as FromExistingBuilder;
use Watch\Schedule\Builder\LimitStrategy;
use Watch\Schedule\Builder\Strategy\Limit\Basic as BasicLimitStrategy;
use Watch\Schedule\Builder\Strategy\Limit\Simple as SimpleLimitStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\LeftToRight  as LeftToRightScheduleStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\RightToLeft as RightToLeftScheduleStrategy;
use Watch\Schedule\Director;

class DirectorTest extends Unit
{
    /**
     * @dataProvider dataCreateScheduleUnlimited
     */
    public function testCreateScheduleUnlimited($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new FromScratchBuilder(
                new Context(Utils::getNowDate($scheduleDescription)),
                Utils::getIssues($issuesDescription),
                new RightToLeftScheduleStrategy(Utils::getMilestoneDate($scheduleDescription)),
                $this->makeEmpty(LimitStrategy::class),
            )
        );
        $this->assertSchedule(Utils::getSchedule($scheduleDescription), $director->build()->release());
    }

    /**
     * @dataProvider dataCreateScheduleSimple
     */
    public function testCreateScheduleSimple($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new FromScratchBuilder(
                new Context(Utils::getNowDate($scheduleDescription)),
                Utils::getIssues($issuesDescription),
                new RightToLeftScheduleStrategy(Utils::getMilestoneDate($scheduleDescription)),
                new SimpleLimitStrategy(),
            )
        );
        $this->assertSchedule(Utils::getSchedule($scheduleDescription), $director->build()->release());
    }

    /**
     * @dataProvider dataCreateScheduleBasic
     */
    public function testCreateScheduleBasic($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new FromScratchBuilder(
                new Context(Utils::getNowDate($scheduleDescription)),
                Utils::getIssues($issuesDescription),
                new RightToLeftScheduleStrategy(Utils::getMilestoneDate($scheduleDescription)),
                new BasicLimitStrategy(),
            )
        );
        $this->assertSchedule(Utils::getSchedule($scheduleDescription), $director->build()->release());
    }

    /**
     * @dataProvider dataGetScheduleBasic
     */
    public function testGetScheduleBasic($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new FromExistingBuilder(
                new Context(Utils::getNowDate($scheduleDescription)),
                Utils::getIssues($issuesDescription),
                new LeftToRightScheduleStrategy(),
            )
        );
        $this->assertSchedule(Utils::getSchedule($scheduleDescription), $director->build()->release());
    }

    protected function assertSchedule($expected, $actual)
    {
        $this->assertSameSize($expected, $actual, 'Number of volumes in the schedule is differ from expected.');
        foreach (['issues', 'buffers', 'milestones', 'links'] as $volume) {
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
            ['
                K-01          |    ....       |
                K-02          |....           | & K-01
                K-03          |.......        |
            ', '
                finish-buffer |           ____| @ finish
                K-01          |       xxxx    | @ finish-buffer
                K-02          |   xxxx        | & K-01
                K-03-buffer   |       ____    | @ finish-buffer
                K-03          |*******        | @ K-03-buffer
                finish        ^               ^ # 2023-09-21
            '], ['
                K-01          |       ....      |
                K-02          |....             | & K-01
                K-03          |.......          | & K-01
            ', '
                finish-buffer |           ______| @ finish
                K-01          |       xxxx      | @ finish-buffer
                K-02-buffer   |     __          | @ K-01
                K-02          | ****            | & K-01, @ K-02-buffer
                K-03          |xxxxxxx          | & K-01
                finish        ^                 ^ # 2023-09-21
            '],
        ];
    }

    protected function dataCreateScheduleSimple(): array
    {
        return [
            ['
                K-01          |....        |
                K-02          |....        |
            ', '
                finish-buffer |        ____| @ finish
                K-01          |    xxxx    | @ finish-buffer
                K-02          |xxxx        | @ K-01
                finish        ^            ^ # 2023-09-21
            '], ['
                K-01          |....              |
                K-02          |....              |
                K-03          |....              |
            ', '
                finish-buffer |            ______| @ finish
                K-01          |        xxxx      | @ finish-buffer
                K-02          |    xxxx          | @ K-01
                K-03          |xxxx              | @ K-02
                finish        ^                  ^ # 2023-09-21
            '],
        ];
    }

    protected function dataCreateScheduleBasic(): array
    {
        return [
            ['
                K-01          |....    |
                K-02          |....    |
            ', '
                finish-buffer |      __| @ finish
                K-01          |  xxxx  | @ finish-buffer
                K-02-buffer   |    __  | @ finish-buffer
                K-02          |****    | @ K-02-buffer
                finish        ^        ^ # 2023-09-21
            '], ['
                K-01          |....        |
                K-02          |....        |
                K-03          |....        |
            ', '
                finish-buffer |        ____| @ finish
                K-01          |xxxx        | @ K-02
                K-02          |    xxxx    | @ finish-buffer
                K-03-buffer   |      __    | @ finish-buffer
                K-03          |  ****      | @ K-03-buffer
                finish        ^            ^ # 2023-09-21
            '], ['
                K-01          |....        |
                K-02          |....        | & K-01
            ', '
                finish-buffer |        ____| @ finish
                K-01          |    xxxx    | @ finish-buffer
                K-02          |xxxx        | & K-01
                finish        ^            ^ # 2023-09-21
            '], ['
                K-01          |....          |
                K-02          |....          | & K-01
                K-03          |....          | & K-01
            ', '
                finish-buffer |          ____| @ finish
                K-01          |      xxxx    | @ finish-buffer
                K-02          |  xxxx        | & K-01
                K-03-buffer   |    __        | @ K-01
                K-03          |****          | & K-01, @ K-03-buffer
                finish        ^              ^ # 2023-09-21
            '],
        ];
    }

    protected function dataGetScheduleBasic(): array
    {
        return [
            ['
                K-01          |        ****      |
                K-02          |    ****          | @ K-01
                K-03         +|****              | @ K-02
                                                 ^ # 2023-09-21
            ', '
                finish-buffer |            !!____| @ finish
                K-01          |        xxxx      | @ finish-buffer
                K-02          |    xxxx          | @ K-01
                K-03          |xxxx              | @ K-02
                finish                   ^       ^ # 2023-09-21
            '], ['
                K-01          |      ****    |
                K-02          |  ****        | & K-01
                K-03          |****          | & K-01
                                             ^ # 2023-09-21
            ', '
                finish-buffer |          !!__| @ finish
                K-01          |      xxxx    | @ finish-buffer
                K-02          |  xxxx        | & K-01
                K-03-buffer   |    !!        | @ K-01
                K-03          |****          | & K-01, @ K-03-buffer
                finish                 ^     ^ # 2023-09-21
            ']
        ];
    }
}
