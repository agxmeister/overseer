<?php
namespace Tests\Unit\Schedule\Serializer;

use Codeception\Test\Unit;
use Watch\Blueprint\Builder\Director;
use Watch\Blueprint\Builder\Schedule as ScheduleBlueprintBuilder;
use Watch\Schedule\Serializer\Project;

class ProjectTest extends Unit
{
    public function testDeserializeSerialize()
    {
        $scheduleBlueprintBuilder = new ScheduleBlueprintBuilder;
        $blueprintDirector = new Director();
        $blueprintDirector->build(
            $scheduleBlueprintBuilder,
            '
                PB/finish-buf |            ______| @ finish
                K-01          |        xxxx      | @ finish-buf
                K-02          |    xxxx          | @ K-01
                K-03          |xxxx              | @ K-02
                finish                           ^ # 2023-09-21
            '
        );
        $blueprint = $scheduleBlueprintBuilder->flush();
        $initialSerializedSchedule = $blueprint->getSchedule();
        $serializer = new Project();
        $schedule = $serializer->deserialize($initialSerializedSchedule);
        $restoredSerializedSchedule = $serializer->serialize($schedule);
        foreach (
            [
                Project::VOLUME_ISSUES,
                Project::VOLUME_BUFFERS,
                Project::VOLUME_MILESTONES,
                Project::VOLUME_LINKS
            ] as $volume
        ) {
            $this->assertSameSize($initialSerializedSchedule[$volume], $restoredSerializedSchedule[$volume]);
        }
    }
}
