<?php
namespace Tests\Unit;

use Codeception\Test\Unit;
use Watch\Schedule\Node;

class NodeTest extends Unit
{
    public function testGetSingleNodeLength()
    {
        $node = new Node("Test", 10);
        $this->assertEquals(10, $node->getLength());
    }
}
