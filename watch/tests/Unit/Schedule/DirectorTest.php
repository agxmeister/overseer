<?php
namespace Tests\Unit\Schedule;

use Codeception\Test\Unit;
use DateTime;
use Exception;
use Tests\Support\Utils;
use Watch\Schedule\Builder;
use Watch\Schedule\Director;
use Watch\Schedule\Strategy\Strategy;
use Watch\Schedule\Strategy\Test;

class DirectorTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetScheduleUnlimited()
    {
        $director = new Director(new Builder());
        $schedule = $director->create(
            Utils::getIssues('
                K-01          |    ....|
                K-02          |....    |          -> K-01
                K-03          |....... |
            '),
            new DateTime('2023-09-21'),
            $this->makeEmpty(Strategy::class),
        );

        $this->assertEquals(
            Utils::getSchedule('
                finish        |                !| 2023-09-21
                finish-buffer |            ____ | ~> finish
                K-01          |        xxxx     | ~> finish-buffer
                K-03-buffer   |        ____     | ~> finish-buffer
                K-02          |    xxxx         | -> K-01
                K-03          | *******         | ~> K-03-buffer
            '),
            $schedule,
        );
    }

    /**
     * @throws Exception
     */
    public function testGetScheduleLimited()
    {
        $builder = new Director(new Builder());
        $schedule = $builder->create(
            Utils::getIssues('
                K-01          |....|
                K-02          |....|
            '),
            new DateTime('2023-09-21'),
            new Test(),
        );
        $this->assertEquals(
            Utils::getSchedule('
                finish        |                !| 2023-09-21
                finish-buffer |            ____ | ~> finish
                K-01          |        xxxx     | ~> finish-buffer
                K-02          |    xxxx         | ~> K-01
            '),
            $schedule,
        );
    }
}
