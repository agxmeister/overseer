<?php
namespace Tests\Unit\Schedule\Director;

use Tests\Support\Utils;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\Preserving as PreservingBuilder;
use Watch\Schedule\Director;

class PreservingDirectorTest extends AbstractDirectorTest
{
    /**
     * @dataProvider dataBuild
     */
    public function testBuild($issuesDescription, $scheduleDescription)
    {
        $director = new Director(
            new PreservingBuilder(
                new Context(Utils::getNowDate($scheduleDescription)),
                Utils::getIssues($issuesDescription),
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
