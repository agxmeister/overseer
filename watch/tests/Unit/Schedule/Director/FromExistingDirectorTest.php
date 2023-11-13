<?php
namespace Tests\Unit\Schedule\Director;

use Tests\Support\Utils;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\FromExisting as FromExistingBuilder;
use Watch\Schedule\Builder\Strategy\Schedule\KeepDates as LeftToRightScheduleStrategy;
use Watch\Schedule\Director;

class FromExistingDirectorTest extends AbstractDirectorTest
{
    /**
     * @dataProvider dataBuild
     */
    public function testBuild($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new FromExistingBuilder(
                new Context(Utils::getNowDate($scheduleDescription)),
                Utils::getIssues($issuesDescription),
                new LeftToRightScheduleStrategy(),
            )
        );
        $this->assertSchedule(Utils::getSchedule($scheduleDescription), $director->build()->release());
    }

    protected function dataBuild(): array
    {
        return [
            ['
                K-01          |        ****      |
                K-02          |    ****          | @ K-01
                K-03         +|****              | @ K-02
                                                 ^ # 2023-09-21
            ', '
                finish-buffer |            !!____| @ finish
                K-01          |        xxxx      | @ finish-buffer
                K-02          |    xxxx          | @ K-01
                K-03          |xxxx              | @ K-02
                finish                   ^       ^ # 2023-09-21
            '], ['
                K-01          |      ****    |
                K-02          |  ****        | & K-01
                K-03          |****          | & K-01
                                             ^ # 2023-09-21
            ', '
                finish-buffer |          !!__| @ finish
                K-01          |      xxxx    | @ finish-buffer
                K-02          |  xxxx        | & K-01
                K-03-buffer   |    !!        | @ K-01
                K-03          |****          | & K-01, @ K-03-buffer
                finish                 ^     ^ # 2023-09-21
            ']
        ];
    }
}
