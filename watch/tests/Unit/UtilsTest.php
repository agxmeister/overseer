<?php
namespace Tests\Unit;

use Codeception\Test\Unit;
use Watch\Utils;

class UtilsTest extends Unit
{
    /**
     * @dataProvider dataGetUnique
     */
    public function testGetUnique($input, $output)
    {
        $this->assertEquals($output, Utils::getUnique($input, fn($item) => $item));
    }
    protected function dataGetUnique(): array
    {
        return [
            [[], []],
            [[1], [1]],
            [[1, 2, 2], [1, 2]],
            [['a'], ['a']],
            [['a', 'b', 'b'], ['a', 'b']],
            [['a', 'b', 'b', 'c'], ['a', 'b', 'c']],
        ];
    }
}
