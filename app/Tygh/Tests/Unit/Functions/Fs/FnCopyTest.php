<?php

namespace Tygh\Tests\Unit\Functions\Fs;

use Tygh\Tests\Unit\ATestCase;

class FnCopyTest extends ATestCase
{
    public $runTestInSeparateProcess = true;

    public $backupGlobals = false;

    public $preserveGlobalState = false;

    public function dpGeneral()
    {

        return array(
            [
                __DIR__ . '/data/',
                __DIR__ . '/copy_data/',
                true,
            ],
            [
                __DIR__ . '/data/fn_fgetcsv.csv',
                __DIR__ . '/copy_data/fn.csv',
                false,
            ],
            [
                __DIR__ . '/data/fn_fgetcsv.csv',
                __DIR__ . '/fn.csv',
                true,
            ],
            [
                __DIR__ . '/data/fn_fget.csv',
                __DIR__ . '/fn.csv',
                false,
            ],
            [
                __DIR__ . '/datafn/',
                __DIR__ . '/copy_data/',
                false,
            ],
        );
    }

    protected function setUp(): void
    {
        define('DEFAULT_DIR_PERMISSIONS', 0777);
        define('DEFAULT_FILE_PERMISSION', 0666);

        $this->requireCore('functions/fn.fs.php');
        $this->requireMockFunction('fn_set_hook');
        $this->requireMockFunction('fn_is_empty');
    }

    /**
     * @param string $file      File to read data from
     * @param array  $expected  Expected result
     * @dataProvider dpGeneral
     */
    public function testGeneral($source, $dest, $expected)
    {

        $actual = fn_copy($source, $dest);

        $this->assertEquals($expected, $actual);

        if ($actual) {
            fn_rm($dest);
        }
    }
}