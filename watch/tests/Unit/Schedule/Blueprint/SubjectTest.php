<?php
namespace Tests\Unit\Schedule\Blueprint;

use Codeception\Test\Unit;
use Watch\Blueprint\Subject;
use Watch\Schedule\Mapper;
use Watch\Subject\Model\Issue;

class SubjectTest extends Unit
{
    /**
     * @dataProvider dataGetIssues
     */
    public function testGetIssues($description, $issueKeys)
    {
        $subject = new Subject($description);
        $mapper = new Mapper(['To Do'], ['In Progress'], ['Done'], ["Depends"], ["Follows"]);
        self::assertEquals(
            $issueKeys,
            array_map(
                fn(Issue $issue) => $issue->key,
                $subject->getIssues($mapper)
            ),
        );
    }

    public static function dataGetIssues(): array
    {
        return [
            [
                '
                    I01 |...|
                    I02 |***|
                            ^ # 2023-01-01
                ',
                ['I01', 'I02'],
            ],
        ];
    }
}
