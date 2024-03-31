<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Watch\Config;
use Watch\Description\Subject;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Mapper;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Serializer\Project as ProjectSerializer;

class BuilderTest extends Unit
{
    public function testAddCriticalChain()
    {
        $description = new Subject('
            K-01   |       xxxx|
            K-02   |   ****    | & K-01
            K-03   |xxxxxxx    | @ K-01
                               ^ # 2023-09-21
        ');
        $mapper = new Mapper(['To Do'], ['In Progress'], ['Done'], ["Depends"], ["Follows"]);
        $builder = new Builder(
            new Context(new \DateTimeImmutable('2023-01-01')),
            $description->getIssues($mapper),
            $description->getLinks($mapper),
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
        $description = new Subject('
            K-01   |       xxxx|
            K-02   | ****      | & K-01
            K-03   |xxxxxxx    | @ K-01
                               ^ # 2023-09-21
        ');
        $mapper = new Mapper(['To Do'], ['In Progress'], ['Done'], ["Depends"], ["Follows"]);
        $builder = new Builder(
            new Context(new \DateTimeImmutable('2023-01-01')),
            $description->getIssues($mapper),
            $description->getLinks($mapper),
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

    private function getConfig(): Config
    {
        return new Config(json_decode('
            {
                "jira": {
                    "statuses": []
                }
            }
        '));
    }
}
