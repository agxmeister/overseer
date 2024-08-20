<?php
namespace Tests\Unit\Schedule\Blueprint\Builder\Asset;

use Codeception\Test\Unit;
use Watch\Blueprint\Builder\Asset\Dash;
use Watch\Blueprint\Builder\Asset\Drawing;
use Watch\Blueprint\Builder\Asset\Parser;
use Watch\Blueprint\Builder\Asset\Stroke;

class DrawingTest extends Unit
{
    /**
     * @dataProvider dataGetStroke
     */
    public function testGetStroke(string $content, string $pattern, ?Stroke $expected): void
    {
        $drawing = new Drawing($content);
        $parser = new Parser($pattern);
        $stroke = $drawing->getStroke($parser);
        $this->assertEquals($expected, $stroke);
    }

    /**
     * @dataProvider dataGetStrokes
     */
    public function testGetStrokes(string $content, string $pattern, array $expected): void
    {
        $drawing = new Drawing($content);
        $parser = new Parser($pattern);
        $strokes = $drawing->getStrokes($parser);
        $this->assertEquals($expected, $strokes);
    }

    static function dataGetStroke(): array
    {
        return [
            [
                '
                    strokeA a
                    strokeB b
                    strokeC c
                ',
                '/strokeB\s+(?<parameter>[\w\d]+)/',
                new Stroke(['parameter' => new Dash('b', 28)]),
            ], [
                '
                    strokeA a
                    strokeB b1
                    strokeC c
                    strokeB b2
                ',
                '/strokeB\s+(?<parameter>[\w\d]+)/',
                new Stroke(['parameter' => new Dash('b2', 28)]),
            ], [
                '
                    strokeA a
                    strokeC c
                ',
                '/strokeB\s+(?<parameter>[\w\d]+)/',
                null,
            ],
        ];
    }

    static function dataGetStrokes(): array
    {
        return [
            [
                '
                    strokeA a
                    strokeB b
                    strokeC c
                ',
                '/strokeB\s+(?<parameter>[\w\d]+)/',
                [
                    new Stroke(['parameter' => new Dash('b', 28)]),
                ],
            ], [
                '
                    strokeA a
                    strokeB b1
                    strokeC c
                    strokeB b2
                ',
                '/strokeB\s+(?<parameter>[\w\d]+)/',
                [
                    new Stroke(['parameter' => new Dash('b1', 28)]),
                    new Stroke(['parameter' => new Dash('b2', 28)]),
                ],
            ], [
                '
                    strokeA a
                    strokeC c
                ',
                '/strokeB\s+(?<parameter>[\w\d]+)/',
                [],
            ],
        ];
    }
}
