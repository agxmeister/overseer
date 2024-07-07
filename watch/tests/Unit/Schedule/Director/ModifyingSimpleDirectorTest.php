<?php
namespace Tests\Unit\Schedule\Director;

use Watch\Blueprint\Builder\Drawing;
use Watch\Blueprint\Builder\Context as BuilderContext;
use Watch\Blueprint\Builder\Schedule as ScheduleBlueprintBuilder;
use Watch\Blueprint\Builder\Subject as SubjectBlueprintBuilder;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\Strategy\Limit\Simple as SimpleLimitStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\ToDate as ToDateScheduleStrategy;
use Watch\Schedule\Director;
use Watch\Schedule\Mapper;
use Watch\Schedule\Serializer\Project as ProjectSerializer;

class ModifyingSimpleDirectorTest extends AbstractDirectorTest
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
            new BuilderContext(),
            $mapper,
        );
        $blueprintDirector->build($subjectBlueprintBuilder);
        $subjectBlueprint = $subjectBlueprintBuilder->flush();
        $scheduleBlueprintBuilder = new ScheduleBlueprintBuilder(new Drawing($scheduleDrawing), new BuilderContext());
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
                new SimpleLimitStrategy(),
                new ToDateScheduleStrategy($scheduleBlueprint->getProjectEndDate()),
            )
        );
        $projectSerializer = new ProjectSerializer();
        $this->assertSchedule(
            $scheduleBlueprint->getSchedule(),
            $projectSerializer->serialize($director->build()->release()->getProject())
        );
    }

    public static function dataBuild(): array
    {
        return [
            ['
                K-01          |....        |
                K-02          |....        |
            ', '
                PB/finish-buf |        ____| @ finish
                K-01          |    xxxx    | @ finish-buf
                K-02          |xxxx        | @ K-01
                finish                     ^ # 2023-09-21
            '], ['
                K-01          |....              |
                K-02          |....              |
                K-03          |....              |
            ', '
                PB/finish-buf |            ______| @ finish
                K-01          |        xxxx      | @ finish-buf
                K-02          |    xxxx          | @ K-01
                K-03          |xxxx              | @ K-02
                finish                           ^ # 2023-09-21
            '],
        ];
    }
}
