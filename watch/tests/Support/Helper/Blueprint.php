<?php

namespace Tests\Support\Helper;

use Codeception\Module;
use Watch\Config;

class Blueprint extends Module
{
    public function getConfig(): Config
    {
        return new Config(null, [
            'blueprint.drawing.stroke.pattern.key.attributes' => 'attributes',
            'blueprint.drawing.stroke.pattern.reference' => '/(?<marker>>)\s*(?<csv_attributes>.*)/',
            'blueprint.drawing.stroke.pattern.issue.schedule' => '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-]?)(?<beginMarker>\|)(?<track>[x*.\s]*)(?<endMarker>\|)\s*(?<csv_attributes>.*)/',
            'blueprint.drawing.stroke.pattern.buffer.schedule' => '/\s*(((?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<beginMarker>\|)(?<track>[_!\s]*)(?<endMarker>\|)\s*(?<csv_attributes>.*)/',
            'blueprint.drawing.stroke.pattern.milestone.schedule' => '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<csv_attributes>.*)/',
            'blueprint.drawing.stroke.pattern.issue.subject' => '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+]?)(?<beginMarker>\|)(?<track>[*.\s]*)(?<endMarker>\|)\s*(?<csv_attributes>.*)/',
            'blueprint.drawing.stroke.pattern.milestone.subject' => '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<csv_attributes>.*)/',
        ]);
    }
}
