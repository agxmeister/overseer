<?php
namespace Tests\Unit\Schedule\Director;

use Watch\Description\Schedule;
use Watch\Description\Subject;
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
    public function testBuild($subjectDescription, $scheduleDescription)
    {
        $subject = new Subject($subjectDescription);
        $schedule = new Schedule($scheduleDescription);
        $mapper = new Mapper(['To Do'], ['In Progress'], ['Done'], ["Depends"], ["Follows"]);
        $director = new Director(
            new Builder(
                new Context($schedule->getNowDate()),
                $subject->getIssues($mapper),
                $subject->getLinks($mapper),
                $schedule->getProjectName(),
                $schedule->getMilestoneNames(),
                $mapper,
                new InitiativeLimitStrategy(2),
                new ToDateScheduleStrategy($schedule->getProjectEndDate()),
            )
        );
        $scheduleSerializer = new ProjectSerializer();
        $this->assertSchedule(
            $schedule->getSchedule(),
            $scheduleSerializer->serialize($director->build()->release()->getProject())
        );
    }

    public static function dataBuild(): array
    {
        return [
            ['
                K-01          |....    |
                K-02          |....    |
            ', '
                PB/finish-buf |      __| @ finish
                K-01          |  xxxx  | @ finish-buf
                FB/K-02-buf   |    __  | @ finish-buf
                K-02          |****    | @ K-02-buf
                finish                 ^ # 2023-09-21
            '], ['
                K-01          |....        |
                K-02          |....        |
                K-03          |....        |
            ', '
                PB/finish-buf |        ____| @ finish
                K-01          |xxxx        | @ K-02
                K-02          |    xxxx    | @ finish-buf
                FB/K-03-buf   |      __    | @ finish-buf
                K-03          |  ****      | @ K-03-buf
                finish                     ^ # 2023-09-21
            '], ['
                K-01          |....        |
                K-02          |....        | & K-01
            ', '
                PB/finish-buf |        ____| @ finish
                K-01          |    xxxx    | @ finish-buf
                K-02          |xxxx        | & K-01
                finish                     ^ # 2023-09-21
            '], ['
                K-01          |....          |
                K-02          |....          | & K-01
                K-03          |....          | & K-01
            ', '
                PB/finish-buf |          ____| @ finish
                K-01          |      xxxx    | @ finish-buf
                K-02          |  xxxx        | & K-01
                FB/K-03-buf   |    __        | @ K-01
                K-03          |****          | & K-01, @ K-03-buf
                finish                       ^ # 2023-09-21
            '],
        ];
    }
}
