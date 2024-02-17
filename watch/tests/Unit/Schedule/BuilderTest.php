<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Watch\Config;
use Watch\Schedule\Builder;
use Watch\Schedule\Description\Utils;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Mapper;
use Watch\Schedule\Serializer\Project as ProjectSerializer;

class BuilderTest extends Unit
{
    public function testAddCriticalChain()
    {
        $issuesDescription = '
            K-01   |       xxxx|
            K-02   |   ****    | & K-01
            K-03   |xxxxxxx    | @ K-01
                               ^ # 2023-09-21
        ';
        $builder = new Builder(
            new Context(new \DateTimeImmutable('2023-01-01')),
            Utils::getIssues($issuesDescription),
            Utils::getLinks($issuesDescription),
            'finish',
            [],
            new Mapper(['In Progress'], ['Done'], ["Depends"], ["Follows"]),
        );
        $builder->run();
        $builder->addProject();
        $projectSerializer = new ProjectSerializer();
        $this->assertEquals(['finish', 'K-01', 'K-03'], $projectSerializer->serialize($builder->release()->getProject())[ProjectSerializer::VOLUME_CRITICAL_CHAIN]);
    }

    public function testAddFeedingBuffers()
    {
        $issuesDescription = '
            K-01   |       xxxx|
            K-02   | ****      | & K-01
            K-03   |xxxxxxx    | @ K-01
                               ^ # 2023-09-21
        ';
        $builder = new Builder(
            new Context(new \DateTimeImmutable('2023-01-01')),
            Utils::getIssues($issuesDescription),
            Utils::getLinks($issuesDescription),
            'finish',
            [],
            new Mapper(["Depends"], ["Follows"], ['In Progress'], ['Done']),
        );
        $builder->run();
        $builder
            ->addProject()
            ->addFeedingBuffers()
            ->addDates();
        $scheduleSerializer = new ProjectSerializer();
        $this->assertEquals([
            [
                'key' => 'K-02-buffer',
                'length' => 2,
                'begin' => '2023-09-15',
                'end' => '2023-09-17',
                'consumption' => 0,
            ]
        ], $scheduleSerializer->serialize($builder->release()->getProject())[ProjectSerializer::VOLUME_BUFFERS]);
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
