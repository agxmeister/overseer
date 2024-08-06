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
        $this->assertEquals($expected, $parser->getMatch($line));
    }

    static public function dataGetMatch(): array
    {
        return [
            [
                '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-]?)(?<beginMarker>\|)(?<track>[x*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/',
                ' K-01 |xxx   | @ M-01 ',
                [
                    [
                        'project' => null,
                        'milestone' => null,
                        'type' => null,
                        'key' => 'K-01',
                        'modifier' => '',
                        'beginMarker' => '|',
                        'track' => 'xxx   ',
                        'endMarker' => '|',
                        'attributes' => '@ M-01 ',
                    ],
                    [
                        'project' => -1,
                        'milestone' => -1,
                        'type' => -1,
                        'key' => 1,
                        'modifier' => 6,
                        'beginMarker' => 6,
                        'track' => 7,
                        'endMarker' => 13,
                        'attributes' => 15,
                    ],
                ],
            ], [
                '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-]?)(?<beginMarker>\|)(?<track>[x*.\s]*)(?<endMarker>\|)\s*(?<csv_attributes>.*)/',
                ' K-01 |xxx   | @ M-01 ',
                [
                    [
                        'project' => null,
                        'milestone' => null,
                        'type' => null,
                        'key' => 'K-01',
                        'modifier' => '',
                        'beginMarker' => '|',
                        'track' => 'xxx   ',
                        'endMarker' => '|',
                        'attributes' => ['@ M-01'],
                    ],
                    [
                        'project' => -1,
                        'milestone' => -1,
                        'type' => -1,
                        'key' => 1,
                        'modifier' => 6,
                        'beginMarker' => 6,
                        'track' => 7,
                        'endMarker' => 13,
                        'attributes' => 15,
                    ],
                ],
            ],
        ];
    }
}
