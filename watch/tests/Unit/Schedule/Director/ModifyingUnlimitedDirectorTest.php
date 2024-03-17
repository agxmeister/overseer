<?php
namespace Tests\Unit\Schedule\Director;

use Watch\Schedule\Builder;
use Watch\Schedule\Description\Utils;
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
    public function testBuild($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new Builder(
                new Context(Utils::getNowDate($scheduleDescription)),
                Utils::getIssues($issuesDescription),
                Utils::getLinks($issuesDescription),
                Utils::getProjectName($scheduleDescription),
                Utils::getMilestoneNames($scheduleDescription),
                new Mapper(['In Progress'], ['Done'], ["Depends"], ["Follows"]),
                $this->makeEmpty(LimitStrategy::class),
                new ToDateScheduleStrategy(Utils::getProjectEndDate($scheduleDescription)),
            )
        );
        $projectSerializer = new ProjectSerializer();
        $this->assertSchedule(
            Utils::getSchedule($scheduleDescription),
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
