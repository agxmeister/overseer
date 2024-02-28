<?php
namespace Tests\Unit\Schedule\Director;

use Watch\Schedule\Builder;
use Watch\Schedule\Description\Utils;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Director;
use Watch\Schedule\Mapper;
use Watch\Schedule\Serializer\Project as ProjectSerializer;

class PreservingDirectorTest extends AbstractDirectorTest
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
            )
        );
        $projectSerializer = new ProjectSerializer();
        $this->assertSchedule(
            Utils::getSchedule($scheduleDescription),
            $projectSerializer->serialize($director->build()->release()->getProject())
        );
    }

    protected function dataBuild(): array
    {
        return [
            ['
                K-01          |        ****      |
                K-02          |    ****          | @ K-01
                K-03         +|****              | @ K-02
                              ^                    # 2023-09-03
            ', '
                                         >
                PB/finish-buf |            !!____| @ finish
                K-01          |        xxxx      | @ finish-buf
                K-02          |    xxxx          | @ K-01
                K-03          |xxxx              | @ K-02
                finish                           ^ # 2023-09-21
            '], ['
                K-01          |      ****    |
                K-02          |  ****        | & K-01
                K-03          |****          | & K-01
                              ^                # 2023-09-07
            ', '
                                       >
                PB/finish-buf |          !!__| @ finish
                K-01          |      xxxx    | @ finish-buf
                K-02          |  xxxx        | & K-01
                FB/K-03-buf   |    !!        | @ K-01
                K-03          |****          | & K-01, @ K-03-buf
                finish                       ^ # 2023-09-21
            '], ['
                K-01          |        ****      |
                K-02          |    ****          | @ K-01
                K-03          |****              | @ K-02
                              ^                    # 2023-09-03
            ', '
                                     >
                PB/finish-buf |            !!____| @ finish
                K-01          |        xxxx      | @ finish-buf
                K-02          |    xxxx          | @ K-01
                K-03          |xxxx              | @ K-02
                finish                           ^ # 2023-09-21
            ']
        ];
    }
}
