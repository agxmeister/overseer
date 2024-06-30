<?php
namespace Tests\Unit\Schedule\Director;

use Watch\Blueprint\Builder\Schedule as ScheduleBlueprintBuilder;
use Watch\Blueprint\Builder\Subject as SubjectBlueprintBuilder;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\LimitStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\ToDate as ToDateScheduleStrategy;
use Watch\Schedule\Director;
use Watch\Schedule\Mapper;
use Watch\Schedule\Serializer\Project as ProjectSerializer;

class ModifyingUnlimitedDirectorTest extends AbstractDirectorTest
{
    /**
     * @dataProvider dataBuild
     */
    public function testBuild($subjectDescription, $scheduleDescription)
    {
        $mapper = new Mapper(['To Do'], ['In Progress'], ['Done'], ["Depends"], ["Follows"]);
        $subjectBlueprintBuilder = new SubjectBlueprintBuilder($mapper);
        $subjectBlueprint = $subjectBlueprintBuilder
            ->clean()
            ->setDrawing($subjectDescription)
            ->setContent()
            ->flush();
        $scheduleBlueprintBuilder = new ScheduleBlueprintBuilder;
        $scheduleBlueprint = $scheduleBlueprintBuilder
            ->clean()
            ->setDrawing($scheduleDescription)
            ->setContent()
            ->flush();
        $director = new Director(
            new Builder(
                new Context($scheduleBlueprint->nowDate),
                $subjectBlueprint->getIssues($mapper),
                $subjectBlueprint->getLinks($mapper),
                $scheduleBlueprint->getProjectName(),
                $scheduleBlueprint->getMilestoneNames(),
                $mapper,
                $this->makeEmpty(LimitStrategy::class),
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
                K-01          |    ....       |
                K-02          |....           | & K-01
                K-03          |.......        |
            ', '
                PB/finish-buf |           ____| @ finish
                K-01          |       xxxx    | @ finish-buf
                K-02          |   xxxx        | & K-01
                FB/K-03-buf   |       ____    | @ finish-buf
                K-03          |*******        | @ K-03-buf
                finish                        ^ # 2023-09-21
            '], ['
                K-01          |       ....      |
                K-02          |....             | & K-01
                K-03          |.......          | & K-01
            ', '
                PB/finish-buf |           ______| @ finish
                K-01          |       xxxx      | @ finish-buf
                FB/K-02-buf   |     __          | @ K-01
                K-02          | ****            | & K-01, @ K-02-buf
                K-03          |xxxxxxx          | & K-01
                finish                          ^ # 2023-09-21
            '],
        ];
    }
}
