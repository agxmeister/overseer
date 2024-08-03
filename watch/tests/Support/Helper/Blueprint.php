<?php

namespace Tests\Support\Helper;

use Codeception\Module;
use Watch\Config;

class Blueprint extends Module
{
    public function getConfig(): Config
    {
        return new Config(null, ['blueprint.drawing.stroke.pattern.key.attributes' => 'attributes']);
    }
}
