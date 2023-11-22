<?php
namespace Tests\Unit\Schedule\Director;

use Watch\Schedule\Description\Utils;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\Modifying as ModifyingBuilder;
use Watch\Schedule\Builder\Strategy\Limit\Basic as BasicLimitStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\ToDate as ToDateScheduleStrategy;
use Watch\Schedule\Director;

class ModifyingBasicDirectorTest extends AbstractDirectorTest
{
    /**
     * @dataProvider dataBuild
     */
    public function testBuild($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new ModifyingBuilder(
                new Context(Utils::getNowDate($scheduleDescription)),
                Utils::getIssues($issuesDescription),
                new BasicLimitStrategy(2),
                new ToDateScheduleStrategy(Utils::getMilestoneEndDate($scheduleDescription)),
            )
        );
        $this->assertSchedule(Utils::getSchedule($scheduleDescription), $director->build()->release());
    }

    protected function dataBuild(): array
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
                finish                 ^ # 2023-09-21
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
                finish                     ^ # 2023-09-21
            '], ['
                K-01          |....        |
                K-02          |....        | & K-01
            ', '
                finish-buffer |        ____| @ finish
                K-01          |    xxxx    | @ finish-buffer
                K-02          |xxxx        | & K-01
                finish                     ^ # 2023-09-21
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
                finish                       ^ # 2023-09-21
            '],
        ];
    }
}
