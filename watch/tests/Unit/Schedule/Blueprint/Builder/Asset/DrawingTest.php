<?php
namespace Tests\Unit\Schedule\Blueprint\Builder\Asset;

use Codeception\Test\Unit;
use Watch\Blueprint\Builder\Asset\Drawing;
use Watch\Blueprint\Builder\Asset\Parser;
use Watch\Blueprint\Builder\Asset\Stroke;

class DrawingTest extends Unit
{
    /**
     * @dataProvider dataGetStroke
     */
    public function testGetStroke(string $content, string $pattern, Stroke $expected)
    {
        $drawing = new Drawing($content);
        $parser = new Parser($pattern);
        $stroke = $drawing->getStroke($parser);
        $this->assertEquals($expected, $stroke);
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
                new Stroke(['parameter' => 'b'], ['parameter' => 28]),
            ],
        ];
    }
}
