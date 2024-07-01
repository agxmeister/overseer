<?php
namespace Tests\Unit\Schedule\Blueprint;

use Codeception\Test\Unit;
use Watch\Blueprint\Builder\Director;
use Watch\Blueprint\Builder\Subject as SubjectBlueprintBuilder;
use Watch\Schedule\Mapper;
use Watch\Subject\Model\Issue;

class SubjectTest extends Unit
{
    /**
     * @dataProvider dataGetIssues
     */
    public function testGetIssues($description, $issueKeys)
    {
        $mapper = new Mapper(['To Do'], ['In Progress'], ['Done'], ["Depends"], ["Follows"]);
        $blueprintBuilder = new SubjectBlueprintBuilder($mapper);
        $blueprintDirector = new Director();
        $blueprintDirector->build($blueprintBuilder, $description);
        $blueprint = $blueprintBuilder->flush();
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
                        >     # 2023-01-01
                    I01 |...|
                    I02 |***|
                            ^ # 2023-01-04
                ',
                ['I01', 'I02'],
            ],
        ];
    }
}
