<?php
namespace Tests\Unit;

use Codeception\Test\Unit;
use Watch\Config;

class ConfigTest extends Unit
{
    /**
     * @dataProvider dataGet
     */
    public function testGet($config, $param, $expected, $default = null)
    {
        $config = new Config(json_decode($config));
        self::assertEquals($expected, $config->get($param, $default));
    }

    /**
     * @dataProvider dataGetDefault
     */
    public function testGetDefault($config, $defaults, $param, $expected)
    {
        $config = new Config(json_decode($config), $defaults);
        self::assertEquals($expected, $config->get($param));
    }

    public static function dataGet(): array
    {
        return [
            [
                '{"param-1": "test"}',
                'param-1',
                'test',
            ], [
                '{"param-1": {"param-1-2": "test"}}',
                'param-1.param-1-2',
                'test',
            ], [
                '{"param-1": "test"}',
                'param-2',
                null,
            ], [
                '{"param-1": "test"}',
                'param-2',
                'default',
                'default',
            ],
        ];
    }

    public static function dataGetDefault(): array
    {
        return [
            ['{}', ['param' => 'value'], 'param', 'value']
        ];
    }
}
