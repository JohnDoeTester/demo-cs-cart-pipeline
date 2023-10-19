<?php

namespace Tygh\Tests\Unit\Functions\Common;

use Tygh\Tests\Unit\ATestCase;

class FnCheckUtf16 extends ATestCase
{
    public $runTestInSeparateProcess = true;

    public $backupGlobals = false;

    public $preserveGlobalState = false;

    public function dpUtf16le()
    {
        return [
            [
                __DIR__ . '/data/utf16le.txt',
                true
            ],
            [
                __DIR__ . '/data/utf16be.txt',
                false
            ]
        ];
    }

    public function dpUtf16be()
    {
        return [
            [
                __DIR__ . '/data/utf16be.txt',
                true
            ],
            [
                __DIR__ . '/data/utf16le.txt',
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
     * @param bool   $expected Whether string is UTF-16LE encoded
     *
     * @dataProvider dpUtf16le
     */
    public function testIsUtf16le($file, $expected)
    {
        $str = file_get_contents($file);

        $enc = fn_is_utf16le($str);

        $this->assertEquals($expected, $enc);
    }

    /**
     * @param string $file     File to read data from
     * @param bool   $expected Whether string is UTF-16BE encoded
     *
     * @dataProvider dpUtf16be
     */
    public function testIsUtf16be($file, $expected)
    {
        $str = file_get_contents($file);

        $enc = fn_is_utf16be($str);

        $this->assertEquals($expected, $enc);
    }
}