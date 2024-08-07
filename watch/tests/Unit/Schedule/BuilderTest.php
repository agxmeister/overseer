<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Tests\Support\UnitTester;
use Watch\Blueprint\Builder\Asset\Drawing;
use Watch\Blueprint\Builder\Director;
use Watch\Blueprint\Builder\Subject as SubjectBlueprintBuilder;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Mapper;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Serializer\Project as ProjectSerializer;

class BuilderTest extends Unit
{
    protected UnitTester $tester;

    public function testAddCriticalChain()
    {
        $drawing = new Drawing('
            K-01   |       ****|
            K-02   |   ****    | & K-01
            K-03   |*******    | @ K-01
                               ^ # 2023-09-21
        ');
        $mapper = new Mapper(['To Do'], ['In Progress'], ['Done'], ["Depends"], ["Follows"]);
        $blueprintBuilder = new SubjectBlueprintBuilder($this->tester->getConfig(), $mapper);
        $blueprintDirector = new Director();
        $blueprintDirector->build($blueprintBuilder, $drawing);
        $blueprint = $blueprintBuilder->flush();
        $builder = new Builder(
            new Context(new \DateTimeImmutable('2023-01-01')),
            $blueprint->getIssues($mapper),
            $blueprint->getLinks(),
            'finish',
            [],
            $mapper,
        );
        $builder
            ->run()
            ->addProject()
            ->addProjectBuffer();
        $projectSerializer = new ProjectSerializer();
        $this->assertEquals(['K-01', 'K-03'], $projectSerializer->serialize($builder->release()->getProject())[ProjectSerializer::VOLUME_CRITICAL_CHAIN]);
    }

    public function testAddFeedingBuffers()
    {
        $drawing = new Drawing('
            K-01   |       ****|
            K-02   | ****      | & K-01
            K-03   |*******    | @ K-01
                               ^ # 2023-09-21
        ');
        $mapper = new Mapper(['To Do'], ['In Progress'], ['Done'], ["Depends"], ["Follows"]);
        $blueprintBuilder = new SubjectBlueprintBuilder($this->tester->getConfig(), $mapper);
        $blueprintDirector = new Director();
        $blueprintDirector->build($blueprintBuilder, $drawing);
        $blueprint = $blueprintBuilder->flush();
        $builder = new Builder(
            new Context(new \DateTimeImmutable('2023-01-01')),
            $blueprint->getIssues($mapper),
            $blueprint->getLinks(),
            'finish',
            [],
            $mapper,
        );
        $builder->run();
        $builder
            ->addProject()
            ->addProjectBuffer()
            ->addFeedingBuffers()
            ->addDates();
        $scheduleSerializer = new ProjectSerializer();
        $this->assertEquals(
            [
                [
                    'key' => 'K-02-buf',
                    'length' => 2,
                    'type' => Buffer::TYPE_FEEDING,
                    'begin' => '2023-09-15',
                    'end' => '2023-09-17',
                    'consumption' => 0,
                ]
            ],
            array_values(array_filter(
                $scheduleSerializer->serialize($builder->release()->getProject())[ProjectSerializer::VOLUME_BUFFERS],
                fn($data) => $data['type'] === Buffer::TYPE_FEEDING,
            )),
        );
    }
}
