<?php
namespace Tests\Unit\Schedule\Blueprint\Builder\Asset;

use Codeception\Test\Unit;
use Watch\Blueprint\Builder\Asset\Parser;

class ParserTest extends Unit
{
    /**
     * @dataProvider dataGetMatch
     */
    public function testGetMatch($pattern, $line, $expected)
    {
        $parser = new Parser($pattern);
        $this->assertEquals($this->getExpectedMatch($expected), $parser->getMatch($line));
    }

    static public function dataGetMatch(): array
    {
        return [
            [
                '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-])?(?<beginMarker>\|)(?<track>[x*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/',
                ' K-01 |xxx   | @ M-01 ',
                [
                    'project' => null,
                    'milestone' => null,
                    'type' => null,
                    'key' => ['K-01', 1],
                    'modifier' => null,
                    'beginMarker' => ['|', 6],
                    'track' => ['xxx   ', 7],
                    'endMarker' => ['|', 13],
                    'attributes' => ['@ M-01 ', 15],
                ],
            ], [
                '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-])?(?<beginMarker>\|)(?<track>[x*.\s]*)(?<endMarker>\|)\s*(?<attributes_csv>.*)/',
                ' K-01 |xxx   | @ M-01 ',
                [
                    'project' => null,
                    'milestone' => null,
                    'type' => null,
                    'key' => ['K-01', 1],
                    'modifier' => null,
                    'beginMarker' => ['|', 6],
                    'track' => ['xxx   ', 7],
                    'endMarker' => ['|', 13],
                    'attributes' => [['@ M-01'], 15],
                ],
            ],
        ];
    }

    private function getExpectedMatch($expected): array
    {
        return [
            array_map(
                fn($value) => $value[0] ?? null,
                $expected,
            ),
            array_map(
                fn($value) => $value[1] ?? -1,
                $expected,
            ),
        ];
    }
}
