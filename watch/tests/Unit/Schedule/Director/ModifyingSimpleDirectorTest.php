<?php
namespace Tests\Unit\Schedule\Director;

use Watch\Action\Util\Schedule as ScheduleUtil;
use Watch\Schedule\Builder;
use Watch\Schedule\Description\Utils;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\Strategy\Limit\Simple as SimpleLimitStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\ToDate as ToDateScheduleStrategy;
use Watch\Schedule\Director;
use Watch\Schedule\Mapper;

class ModifyingSimpleDirectorTest extends AbstractDirectorTest
{
    /**
     * @dataProvider dataBuild
     */
    public function testBuild($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new Builder(
                new Context(Utils::getNowDate($scheduleDescription)),
                Utils::getIssues($issuesDescription),
                Utils::getJoints($issuesDescription),
                Utils::getMilestoneNames($scheduleDescription),
                new Mapper(["Depends"], ["Follows"], ['In Progress'], ['Done']),
                new SimpleLimitStrategy(),
                new ToDateScheduleStrategy(Utils::getProjectEndDate($scheduleDescription)),
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
                K-01          |....        |
                K-02          |....        |
            ', '
                finish-buffer |        ____| @ finish
                K-01          |    xxxx    | @ finish-buffer
                K-02          |xxxx        | @ K-01
                finish                     ^ # 2023-09-21
            '], ['
                K-01          |....              |
                K-02          |....              |
                K-03          |....              |
            ', '
                finish-buffer |            ______| @ finish
                K-01          |        xxxx      | @ finish-buffer
                K-02          |    xxxx          | @ K-01
                K-03          |xxxx              | @ K-02
                finish                           ^ # 2023-09-21
            '],
        ];
    }
}
