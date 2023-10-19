<?php

namespace Tygh\Tests\Unit\Functions\SmartyPlugins;

use Tygh\Tests\Unit\ATestCase;

class ModifierToRelativeUrl extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->requireCore('functions/smarty_plugins/modifier.to_relative_url.php');
    }

    /**
     * @param $value
     * @param $expected
     * @dataProvider dpModifierToRelativeUrl
     */
    public function testModifierToRelativeUrl($value, $expected)
    {
        $this->assertEquals($expected, smarty_modifier_to_relative_url($value));
    }

    public function dpModifierToRelativeUrl()
    {
        return [
            ['order_management.add', 'order_management.add?'],
            ['order_management.select_customer?page=2', 'order_management.select_customer?page=2'],
            ['/\asaplab.io?host321.net', '\asaplab.io?host321.net'],
            ['\/asaplab.io?host321.net', 'asaplab.io?host321.net'],
            ['https://asaplab.io?host321.net', '?host321.net'],
            ['https://asaplab.io', '']
        ];
    }
}