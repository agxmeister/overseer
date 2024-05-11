<?php
namespace Tests\Unit\Schedule\Blueprint;

use Codeception\Test\Unit;
use Watch\Blueprint\Factory\Subject as SubjectBlueprintFactory;
use Watch\Schedule\Mapper;
use Watch\Subject\Model\Issue;

class SubjectTest extends Unit
{
    /**
     * @dataProvider dataGetIssues
     */
    public function testGetIssues($description, $issueKeys)
    {
        $blueprintFactory = new SubjectBlueprintFactory;
        $blueprint = $blueprintFactory->create($description);
        $mapper = new Mapper(['To Do'], ['In Progress'], ['Done'], ["Depends"], ["Follows"]);
        self::assertEquals(
            $issueKeys,
            array_map(
                fn(Issue $issue) => $issue->key,
                $blueprint->getIssues($mapper)
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
