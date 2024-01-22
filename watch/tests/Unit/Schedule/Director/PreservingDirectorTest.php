<?php
namespace Tests\Unit\Schedule\Director;

use Watch\Action\Util\Schedule as ScheduleUtil;
use Watch\Schedule\Builder;
use Watch\Schedule\Description\Utils;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Director;
use Watch\Schedule\Mapper;

class PreservingDirectorTest extends AbstractDirectorTest
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
                K-01          |        ****      |
                K-02          |    ****          | @ K-01
                K-03         +|****              | @ K-02
                              ^                    # 2023-09-03
            ', '
                                         >
                finish-buffer |            !!____| @ finish
                K-01          |        xxxx      | @ finish-buffer
                K-02          |    xxxx          | @ K-01
                K-03          |xxxx              | @ K-02
                finish                           ^ # 2023-09-21
            '], ['
                K-01          |      ****    |
                K-02          |  ****        | & K-01
                K-03          |****          | & K-01
                              ^                # 2023-09-07
            ', '
                                       >
                finish-buffer |          !!__| @ finish
                K-01          |      xxxx    | @ finish-buffer
                K-02          |  xxxx        | & K-01
                K-03-buffer   |    !!        | @ K-01
                K-03          |****          | & K-01, @ K-03-buffer
                finish                       ^ # 2023-09-21
            ']
        ];
    }
}
