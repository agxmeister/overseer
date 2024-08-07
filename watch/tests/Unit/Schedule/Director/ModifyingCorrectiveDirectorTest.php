<?php
namespace Tests\Unit\Schedule\Director;

use Watch\Blueprint\Builder\Asset\Drawing;
use Watch\Blueprint\Builder\Schedule as ScheduleBlueprintBuilder;
use Watch\Blueprint\Builder\Subject as SubjectBlueprintBuilder;
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
    public function testBuildFromDate($subjectDrawingContent, $scheduleDrawingContent)
    {
        $blueprintDirector = new \Watch\Blueprint\Builder\Director();

        $subjectDrawing = new Drawing($subjectDrawingContent);
        $mapper = new Mapper(['To Do'], ['In Progress'], ['Done'], ["Depends"], ["Follows"]);
        $subjectBlueprintBuilder = new SubjectBlueprintBuilder($this->tester->getConfig(), $mapper);
        $blueprintDirector->build($subjectBlueprintBuilder, $subjectDrawing);
        $subjectBlueprint = $subjectBlueprintBuilder->flush();

        $scheduleDrawing = new Drawing($scheduleDrawingContent);
        $scheduleBlueprintBuilder = new ScheduleBlueprintBuilder($this->tester->getConfig());
        $blueprintDirector->build($scheduleBlueprintBuilder, $scheduleDrawing);
        $scheduleBlueprint = $scheduleBlueprintBuilder->flush();
        $director = new Director(
            new Builder(
                new Context($scheduleBlueprint->nowDate),
                $subjectBlueprint->getIssues($mapper),
                $subjectBlueprint->getLinks(),
                $scheduleBlueprint->getProjectName(),
                $scheduleBlueprint->getMilestoneNames(),
                $mapper,
                new CorrectiveLimitStrategy(2),
                new FromDateScheduleStrategy($scheduleBlueprint->getProjectBeginDate()),
            )
        );
        $projectSerializer = new ProjectSerializer();
        $this->assertSchedule(
            $scheduleBlueprint->getSchedule(),
            $projectSerializer->serialize($director->build()->release()->getProject())
        );
    }

    /**
     * @dataProvider dataBuildToDate
     */
    public function testBuildToDate($subjectDrawingContent, $scheduleDrawingContent)
    {
        $blueprintDirector = new \Watch\Blueprint\Builder\Director();

        $subjectDrawing = new Drawing($subjectDrawingContent);
        $mapper = new Mapper(['To Do'], ['In Progress'], ['Done'], ["Depends"], ["Follows"]);
        $subjectBlueprintBuilder = new SubjectBlueprintBuilder($this->tester->getConfig(), $mapper);
        $blueprintDirector->build($subjectBlueprintBuilder, $subjectDrawing);
        $subjectBlueprint = $subjectBlueprintBuilder->flush();

        $scheduleDrawing = new Drawing($scheduleDrawingContent);
        $scheduleBlueprintBuilder = new ScheduleBlueprintBuilder($this->tester->getConfig());
        $blueprintDirector->build($scheduleBlueprintBuilder, $scheduleDrawing);
        $scheduleBlueprint = $scheduleBlueprintBuilder->flush();
        $director = new Director(
            new Builder(
                new Context($scheduleBlueprint->nowDate),
                $subjectBlueprint->getIssues($mapper),
                $subjectBlueprint->getLinks($mapper),
                $scheduleBlueprint->getProjectName(),
                $scheduleBlueprint->getMilestoneNames(),
                $mapper,
                new CorrectiveLimitStrategy(2),
                new ToDateScheduleStrategy($scheduleBlueprint->getProjectEndDate()),
            )
        );
        $scheduleSerializer = new ProjectSerializer();
        $this->assertSchedule(
            $scheduleBlueprint->getSchedule(),
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
