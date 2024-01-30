<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use Watch\Config;
use Watch\Schedule\Builder;
use Watch\Schedule\Description\Utils;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Mapper;
use Watch\Schedule\Serializer\Schedule as ScheduleSerializer;

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
            ['finish'],
            new Mapper(['In Progress'], ['Done'], ["Depends"], ["Follows"]),
        );
        $builder->run();
        $builder->addMilestone();
        $scheduleSerializer = new ScheduleSerializer();
        $this->assertEquals(['finish', 'K-01', 'K-03'], $scheduleSerializer->serialize($builder->release())[ScheduleSerializer::VOLUME_CRITICAL_CHAIN]);
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
            ['finish'],
            new Mapper(["Depends"], ["Follows"], ['In Progress'], ['Done']),
        );
        $builder->run();
        $builder
            ->addMilestone()
            ->addFeedingBuffers()
            ->addDates();
        $scheduleSerializer = new ScheduleSerializer();
        $this->assertEquals([
            [
                'key' => 'K-02-buffer',
                'begin' => '2023-09-15',
                'end' => '2023-09-17',
                'consumption' => 0,
            ]
        ], $scheduleSerializer->serialize($builder->release())[ScheduleSerializer::VOLUME_BUFFERS]);
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
