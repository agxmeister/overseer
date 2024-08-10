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
                '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/',
                '    M1                   ^             # 2023-09-09    ',
                [
                    'key' => ['M1', 4],
                    'marker' => ['^', 25],
                    'attributes' => ['# 2023-09-09    ', 39],
                ],
            ], [
                '/(?<marker>>)\s*(?<attributes_csv>.*)/',
                '    >     # 2023-07-15    ',
                [
                    'marker' => ['>', 4],
                    'attributes' => [['# 2023-07-15'], 10],
                ],
            ], [
                '/(?<marker>>)\s*(?<attributes_csv>.*)/',
                '    >    ',
                [
                    'marker' => ['>', 4],
                    'attributes' => [[], 9],
                ],
            ], [
                '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-])?(?<beginMarker>\|)(?<track>[x*.\s]*)(?<endMarker>\|)\s*(?<attributes_csv>.*)/',
                '    K-02          |    xxxx          | @ K-01    ',
                [
                    'project' => null,
                    'milestone' => null,
                    'type' => null,
                    'key' => ['K-02', 4],
                    'modifier' => null,
                    'beginMarker' => ['|', 18],
                    'track' => ['    xxxx          ', 19],
                    'endMarker' => ['|', 37],
                    'attributes' => [['@ K-01'], 39],
                ],
            ], [
                '/\s*(((?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<beginMarker>\|)(?<track>[_!\s]*)(?<endMarker>\|)\s*(?<attributes_csv>.*)/',
                '    PB/finish-buf |            !!____| @ finish    ',
                [
                    'type' => ['PB', 4],
                    'key' => ['finish-buf', 7],
                    'beginMarker' => ['|', 18],
                    'track' => ['            !!____', 19],
                    'endMarker' => ['|', 37],
                    'attributes' => [['@ finish'], 39],
                ],
            ], [
                '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes_csv>.*)/',
                '    M1                   ^             # 2023-09-09    ',
                [
                    'key' => ['M1', 4],
                    'marker' => ['^', 25],
                    'attributes' => [['# 2023-09-09'], 39],
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
