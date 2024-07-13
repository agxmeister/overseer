<?php
namespace Tests\Unit\Schedule\Director;

use Watch\Blueprint\Builder\Asset\Drawing;
use Watch\Blueprint\Builder\Schedule as ScheduleBlueprintBuilder;
use Watch\Blueprint\Builder\Subject as SubjectBlueprintBuilder;
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
    public function testBuild($subjectDrawing, $scheduleDrawing)
    {
        $blueprintDirector = new \Watch\Blueprint\Builder\Director();
        $mapper = new Mapper(['To Do'], ['In Progress'], ['Done'], ["Depends"], ["Follows"]);
        $subjectBlueprintBuilder = new SubjectBlueprintBuilder(
            new Drawing($subjectDrawing),
            $mapper,
        );
        $blueprintDirector->build($subjectBlueprintBuilder);
        $subjectBlueprint = $subjectBlueprintBuilder->flush();
        $scheduleBlueprintBuilder = new ScheduleBlueprintBuilder(new Drawing($scheduleDrawing));
        $blueprintDirector->build($scheduleBlueprintBuilder);
        $scheduleBlueprint = $scheduleBlueprintBuilder->flush();
        $director = new Director(
            new Builder(
                new Context($scheduleBlueprint->nowDate),
                $subjectBlueprint->getIssues($mapper),
                $subjectBlueprint->getLinks($mapper),
                $scheduleBlueprint->getProjectName(),
                $scheduleBlueprint->getMilestoneNames(),
                $mapper,
                new InitiativeLimitStrategy(2),
                new ToDateScheduleStrategy($scheduleBlueprint->getProjectEndDate()),
            )
        );
        $scheduleSerializer = new ProjectSerializer();
        $this->assertSchedule(
            $scheduleBlueprint->getSchedule(),
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
