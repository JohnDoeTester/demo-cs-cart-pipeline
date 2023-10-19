<?php

namespace Tygh\Tests\Unit\Functions\Common;

use Tygh\Tests\Unit\ATestCase;

class FnCheckUtf8 extends ATestCase
{
    public $runTestInSeparateProcess = true;

    public $backupGlobals = false;

    public $preserveGlobalState = false;

    public function dpUtf8()
    {
        return [
            [
                __DIR__ . '/data/utf8_without_bom.txt',
                true
            ],
            [
                __DIR__ . '/data/utf8_with_bom.txt',
                true
            ],
            [
                __DIR__ . '/data/utf16le.txt',
                false
            ],
            [
                __DIR__ . '/data/utf16be.txt',
                false
            ]
        ];
    }

    protected function setUp(): void
    {
        $this->requireCore('functions/fn.common.php');
    }

    /**
     * @param string $file     File to read data from
     * @param bool   $expected Whether string is UTF-8 encoded
     *
     * @dataProvider dpUtf8
     */
    public function testIsUtf8($file, $expected)
    {
        $str = file_get_contents($file);

        $enc = fn_is_utf8($str);

        $this->assertEquals($expected, $enc);
    }
}
