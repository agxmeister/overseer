<?php
namespace Tests\Unit\Schedule\Director;

use Tests\Support\Utils;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\Modifying as ModifyingBuilder;
use Watch\Schedule\Builder\LimitStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\ToDate as ToDateScheduleStrategy;
use Watch\Schedule\Director;

class UnlimitedFromScratchDirectorTest extends AbstractDirectorTest
{
    /**
     * @dataProvider dataBuild
     */
    public function testBuild($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new ModifyingBuilder(
                new Context(Utils::getNowDate($scheduleDescription)),
                Utils::getIssues($issuesDescription),
                new ToDateScheduleStrategy(Utils::getMilestoneEndDate($scheduleDescription)),
                $this->makeEmpty(LimitStrategy::class),
            )
        );
        $this->assertSchedule(Utils::getSchedule($scheduleDescription), $director->build()->release());
    }

    protected function dataBuild(): array
    {
        return [
            ['
                K-01          |    ....       |
                K-02          |....           | & K-01
                K-03          |.......        |
            ', '
                finish-buffer |           ____| @ finish
                K-01          |       xxxx    | @ finish-buffer
                K-02          |   xxxx        | & K-01
                K-03-buffer   |       ____    | @ finish-buffer
                K-03          |*******        | @ K-03-buffer
                finish        ^               ^ # 2023-09-21
            '], ['
                K-01          |       ....      |
                K-02          |....             | & K-01
                K-03          |.......          | & K-01
            ', '
                finish-buffer |           ______| @ finish
                K-01          |       xxxx      | @ finish-buffer
                K-02-buffer   |     __          | @ K-01
                K-02          | ****            | & K-01, @ K-02-buffer
                K-03          |xxxxxxx          | & K-01
                finish        ^                 ^ # 2023-09-21
            '],
        ];
    }
}
