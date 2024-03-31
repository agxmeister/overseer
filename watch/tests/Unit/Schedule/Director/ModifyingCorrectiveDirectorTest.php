<?php
namespace Tests\Unit\Schedule\Director;

use Watch\Description\Schedule;
use Watch\Description\Subject;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\Strategy\Limit\Corrective as CorrectiveLimitStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\FromDate as FromDateScheduleStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\ToDate as ToDateScheduleStrategy;
use Watch\Schedule\Director;
use Watch\Schedule\Mapper;
use Watch\Schedule\Serializer\Project as ProjectSerializer;

class ModifyingCorrectiveDirectorTest extends AbstractDirectorTest
{
    /**
     * @dataProvider dataBuildFromDate
     */
    public function testBuildFromDate($subjectDescription, $scheduleDescription)
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
                new CorrectiveLimitStrategy(2),
                new FromDateScheduleStrategy($schedule->getProjectBeginDate()),
            )
        );
        $projectSerializer = new ProjectSerializer();
        $this->assertSchedule(
            $schedule->getSchedule(),
            $projectSerializer->serialize($director->build()->release()->getProject())
        );
    }

    /**
     * @dataProvider dataBuildToDate
     */
    public function testBuildToDate($subjectDescription, $scheduleDescription)
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
                new CorrectiveLimitStrategy(2),
                new ToDateScheduleStrategy($schedule->getProjectEndDate()),
            )
        );
        $scheduleSerializer = new ProjectSerializer();
        $this->assertSchedule(
            $schedule->getSchedule(),
            $scheduleSerializer->serialize($director->build()->release()->getProject())
        );
    }

    public static function dataBuildFromDate(): array
    {
        return [
            ['
                K-01          |   ******      |
                K-02          |  *****        |
                K-03          |****           |
                              ^                 # 2023-08-21
            ', '
                PB/finish-buf |          _____| @ finish
                K-01          |    xxxxxx     | @ finish-buf
                FB/K-02-buf   |       ___     | @ finish-buf
                K-02          |  *****        | @ K-02-buf
                K-03          |xxxx           | @ K-01
                finish        ^                 # 2023-08-21
            '], ['
                K-01          |   *****        |
                K-02         ~|  ******        |
                K-03          |****            |
                              ^                  # 2023-08-21
            ', '
                PB/finish-buf |           _____| @ finish
                K-01          |      xxxxx     | @ finish-buf
                FB/K-02-buf   |        ___     | @ finish-buf
                K-02          |  ******        | @ K-02-buf
                K-03          |  xxxx          | @ K-01
                finish          ^                # 2023-08-23
            '], ['
                K-01          |   ***** |
                K-02         +|  ****** |
                K-03          |****     |
                              ^           # 2023-08-21
            ', '
                PB/finish-buf |      ___| @ finish
                K-01          | xxxxx   | @ finish-buf
                K-02         -|******   | @ finish-buf
                FB/K-03-buf   |    __   | @ finish-buf
                K-03          |****     | @ K-03-buf
                finish        ^           # 2023-08-21
            '],
        ];
    }

    public static function dataBuildToDate(): array
    {
        return [
            ['
                K-01          |......         |
                K-02          |.....          |
                K-03          |....           |
            ', '
                PB/finish-buf |          _____| @ finish
                K-01          |    xxxxxx     | @ finish-buf
                FB/K-02-buf   |       ___     | @ finish-buf
                K-02          |  *****        | @ K-02-buf
                K-03          |xxxx           | @ K-01
                finish                        ^ # 2023-09-21
            '], ['
                K-01          |   *****       |
                K-02          |  ******       |
                K-03          |****           |
                              ^                 # 2023-09-06
            ', '
                PB/finish-buf |          _____| @ finish
                FB/K-01-buf   |       ___     | @ finish-buf
                K-01          |  *****        | @ K-01-buf
                K-02          |    xxxxxx     | @ finish-buf
                K-03          |xxxx           | @ K-02
                finish                        ^ # 2023-09-21
            '], ['
                K-01         +|   *****   |
                K-02          |  ******   |
                K-03          |****       |
                              ^             # 2023-09-10
            ', '
                PB/finish-buf |        ___| @ finish
                K-01         -|   *****   | @ finish-buf
                K-02          |  xxxxxx   | @ finish-buf
                FB/K-03-buf   |      __   | @ finish-buf
                K-03          |  ****     | @ K-03-buf
                finish                    ^ # 2023-09-21
            '], ['
                K-01          |   *****      |
                K-02         ~|  ******      |
                K-03          |****          |
                              ^                # 2023-09-07
            ', '
                PB/finish-buf |         _____| @ finish
                K-01          |    xxxxx     | @ finish-buf
                FB/K-02-buf   |      ___     | @ finish-buf
                K-02          |******        | @ K-02-buf
                K-03          |xxxx          | @ K-01
                finish                       ^ # 2023-09-21
            '],
        ];
    }
}
