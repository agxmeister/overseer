<?php
namespace Tests\Unit\Schedule\Director;

use Tests\Support\Utils;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\FromScratch as FromScratchBuilder;
use Watch\Schedule\Builder\Strategy\Limit\Corrective as CorrectiveLimitStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\LeftToRight as LeftToRightScheduleStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\RightToLeft as RightToLeftScheduleStrategy;
use Watch\Schedule\Director;

class CorrectiveFromScratchDirectorTest extends AbstractDirectorTest
{
    /**
     * @dataProvider dataBuildLeftToRight
     */
    public function testBuildLeftToRight($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new FromScratchBuilder(
                new Context(Utils::getNowDate($scheduleDescription)),
                Utils::getIssues($issuesDescription),
                new LeftToRightScheduleStrategy(),
                new CorrectiveLimitStrategy(2),
            )
        );
        $this->assertSchedule(Utils::getSchedule($scheduleDescription), $director->build()->release());
    }

    /**
     * @dataProvider dataBuildRightToLeft
     */
    public function testBuildRightToLeft($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new FromScratchBuilder(
                new Context(Utils::getNowDate($scheduleDescription)),
                Utils::getIssues($issuesDescription),
                new RightToLeftScheduleStrategy(Utils::getMilestoneDate($scheduleDescription)),
                new CorrectiveLimitStrategy(2),
            )
        );
        $this->assertSchedule(Utils::getSchedule($scheduleDescription), $director->build()->release());
    }

    protected function dataBuildLeftToRight(): array
    {
        return [
            ['
                K-01          |   ******      |
                K-02          |  *****        |
                K-03          |****           |
                                              ^ # 2023-09-21
            ', '
                finish-buffer |          _____| @ finish
                K-01          |    xxxxxx     | @ finish-buffer
                K-02-buffer   |       ___     | @ finish-buffer
                K-02          |  *****        | @ K-02-buffer
                K-03          |xxxx           | @ K-01
                finish        ^               ^ # 2023-09-21
            '],
        ];
    }

    protected function dataBuildRightToLeft(): array
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
                finish        ^               ^ # 2023-09-21
            '], ['
                K-01          |   *****       |
                K-02          |  ******       |
                K-03          |****           |
                                              ^ # 2023-09-21
            ', '
                finish-buffer |          _____| @ finish
                K-01-buffer   |       ___     | @ finish-buffer
                K-01          |  *****        | @ K-01-buffer
                K-02          |    xxxxxx     | @ finish-buffer
                K-03          |xxxx           | @ K-02
                finish        ^               ^ # 2023-09-21
            '], ['
                K-01         +|   *****   |
                K-02          |  ******   |
                K-03          |****       |
                                          ^ # 2023-09-21
            ', '
                finish-buffer |        ___| @ finish
                K-01          |   *****   | @ finish-buffer
                K-02          |  xxxxxx   | @ finish-buffer
                K-03-buffer   |      __   | @ finish-buffer
                K-03          |  ****     | @ K-03-buffer
                finish        ^           ^ # 2023-09-21
            '], ['
                K-01          |   *****      |
                K-02         ~|  ******      |
                K-03          |****          |
                                             ^ # 2023-09-21
            ', '
                finish-buffer |         _____| @ finish
                K-01          |    xxxxx     | @ finish-buffer
                K-02-buffer   |      ___     | @ finish-buffer
                K-02          |******        | @ K-02-buffer
                K-03          |xxxx          | @ K-01
                finish        ^              ^ # 2023-09-21
            '],
        ];
    }
}
