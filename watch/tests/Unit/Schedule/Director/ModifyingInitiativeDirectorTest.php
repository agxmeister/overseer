<?php
namespace Tests\Unit\Schedule\Director;

use Watch\Schedule\Description\Utils;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\Strategy\Limit\Initiative as InitiativeLimitStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\ToDate as ToDateScheduleStrategy;
use Watch\Schedule\Director;
use Watch\Schedule\Mapper;
use Watch\Schedule\Serializer\Project as ProjectSerializer;

class ModifyingInitiativeDirectorTest extends AbstractDirectorTest
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
                Utils::getLinks($issuesDescription),
                Utils::getMilestoneNames($scheduleDescription),
                new Mapper(['In Progress'], ['Done'], ["Depends"], ["Follows"]),
                new InitiativeLimitStrategy(2),
                new ToDateScheduleStrategy(Utils::getProjectEndDate($scheduleDescription)),
            )
        );
        $scheduleSerializer = new ProjectSerializer();
        $this->assertSchedule(
            Utils::getSchedule($scheduleDescription),
            $scheduleSerializer->serialize($director->build()->release())
        );
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
