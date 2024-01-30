<?php
namespace Tests\Unit\Schedule\Director;

use Watch\Schedule\Description\Utils;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\Strategy\Limit\Corrective as CorrectiveLimitStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\FromDate as FromDateScheduleStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\ToDate as ToDateScheduleStrategy;
use Watch\Schedule\Director;
use Watch\Schedule\Mapper;
use Watch\Schedule\Serializer\Schedule as ScheduleSerializer;

class ModifyingCorrectiveDirectorTest extends AbstractDirectorTest
{
    /**
     * @dataProvider dataBuildFromDate
     */
    public function testBuildFromDate($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new Builder(
                new Context(Utils::getNowDate($scheduleDescription)),
                Utils::getIssues($issuesDescription),
                Utils::getLinks($issuesDescription),
                Utils::getMilestoneNames($scheduleDescription),
                new Mapper(['In Progress'], ['Done'], ["Depends"], ["Follows"]),
                new CorrectiveLimitStrategy(2),
                new FromDateScheduleStrategy(Utils::getProjectBeginDate($scheduleDescription)),
            )
        );
        $scheduleSerializer = new ScheduleSerializer();
        $this->assertSchedule(
            Utils::getSchedule($scheduleDescription),
            $scheduleSerializer->serialize($director->build()->release())
        );
    }

    /**
     * @dataProvider dataBuildToDate
     */
    public function testBuildToDate($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new Builder(
                new Context(Utils::getNowDate($scheduleDescription)),
                Utils::getIssues($issuesDescription),
                Utils::getLinks($issuesDescription),
                Utils::getMilestoneNames($scheduleDescription),
                new Mapper(['In Progress'], ['Done'], ["Depends"], ["Follows"]),
                new CorrectiveLimitStrategy(2),
                new ToDateScheduleStrategy(Utils::getProjectEndDate($scheduleDescription)),
            )
        );
        $scheduleSerializer = new ScheduleSerializer();
        $this->assertSchedule(
            Utils::getSchedule($scheduleDescription),
            $scheduleSerializer->serialize($director->build()->release())
        );
    }

    protected function dataBuildFromDate(): array
    {
        return [
            ['
                K-01          |   ******      |
                K-02          |  *****        |
                K-03          |****           |
                              ^                 # 2023-08-21
            ', '
                finish-buffer |          _____| @ finish
                K-01          |    xxxxxx     | @ finish-buffer
                K-02-buffer   |       ___     | @ finish-buffer
                K-02          |  *****        | @ K-02-buffer
                K-03          |xxxx           | @ K-01
                finish        ^                 # 2023-08-21
            '], ['
                K-01          |   *****        |
                K-02         ~|  ******        |
                K-03          |****            |
                              ^                  # 2023-08-21
            ', '
                finish-buffer |           _____| @ finish
                K-01          |      xxxxx     | @ finish-buffer
                K-02-buffer   |        ___     | @ finish-buffer
                K-02          |  ******        | @ K-02-buffer
                K-03          |  xxxx          | @ K-01
                finish          ^                # 2023-08-23
            '], ['
                K-01          |   ***** |
                K-02         +|  ****** |
                K-03          |****     |
                              ^           # 2023-08-21
            ', '
                finish-buffer |      ___| @ finish
                K-01          | xxxxx   | @ finish-buffer
                K-02         -|******   | @ finish-buffer
                K-03-buffer   |    __   | @ finish-buffer
                K-03          |****     | @ K-03-buffer
                finish        ^           # 2023-08-21
            '],
        ];
    }

    protected function dataBuildToDate(): array
    {
        return [
            ['
                K-01          |......         |
                K-02          |.....          |
                K-03          |....           |
            ', '
                finish-buffer |          _____| @ finish
                K-01          |    xxxxxx     | @ finish-buffer
                K-02-buffer   |       ___     | @ finish-buffer
                K-02          |  *****        | @ K-02-buffer
                K-03          |xxxx           | @ K-01
                finish                        ^ # 2023-09-21
            '], ['
                K-01          |   *****       |
                K-02          |  ******       |
                K-03          |****           |
                              ^                 # 2023-09-06
            ', '
                finish-buffer |          _____| @ finish
                K-01-buffer   |       ___     | @ finish-buffer
                K-01          |  *****        | @ K-01-buffer
                K-02          |    xxxxxx     | @ finish-buffer
                K-03          |xxxx           | @ K-02
                finish                        ^ # 2023-09-21
            '], ['
                K-01         +|   *****   |
                K-02          |  ******   |
                K-03          |****       |
                              ^             # 2023-09-10
            ', '
                finish-buffer |        ___| @ finish
                K-01         -|   *****   | @ finish-buffer
                K-02          |  xxxxxx   | @ finish-buffer
                K-03-buffer   |      __   | @ finish-buffer
                K-03          |  ****     | @ K-03-buffer
                finish                    ^ # 2023-09-21
            '], ['
                K-01          |   *****      |
                K-02         ~|  ******      |
                K-03          |****          |
                              ^                # 2023-09-07
            ', '
                finish-buffer |         _____| @ finish
                K-01          |    xxxxx     | @ finish-buffer
                K-02-buffer   |      ___     | @ finish-buffer
                K-02          |******        | @ K-02-buffer
                K-03          |xxxx          | @ K-01
                finish                       ^ # 2023-09-21
            '],
        ];
    }
}
