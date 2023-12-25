<?php
namespace Tests\Unit\Schedule\Director;

use Watch\Action\Util\Schedule as ScheduleUtil;
use Watch\Schedule\Builder;
use Watch\Schedule\Description\Utils;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\LimitStrategy;
use Watch\Schedule\Builder\Strategy\State\MapByStatus as MapByStatusStateStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\ToDate as ToDateScheduleStrategy;
use Watch\Schedule\Director;
use Watch\Subject\Decorator\Factory;

class ModifyingUnlimitedDirectorTest extends AbstractDirectorTest
{
    /**
     * @dataProvider dataBuild
     */
    public function testBuild($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new Builder(
                new Context(Utils::getNowDate($scheduleDescription), new Factory()),
                Utils::getIssues($issuesDescription),
                Utils::getMilestones($issuesDescription),
                new MapByStatusStateStrategy(),
                $this->makeEmpty(LimitStrategy::class),
                new ToDateScheduleStrategy(Utils::getMilestoneEndDate($scheduleDescription)),
            )
        );
        $scheduleUtil = new ScheduleUtil();
        $this->assertSchedule(
            Utils::getSchedule($scheduleDescription),
            $scheduleUtil->serialize($director->build()->release())
        );
    }

    protected function dataBuild(): array
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
                finish                        ^ # 2023-09-21
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
                finish                          ^ # 2023-09-21
            '],
        ];
    }
}
