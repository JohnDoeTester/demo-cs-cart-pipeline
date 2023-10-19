<?php

namespace Tygh\Tests\Unit\Functions\Fs;

use Tygh\Tests\Unit\ATestCase;

class FnFgetcsvTest extends ATestCase
{
    public $runTestInSeparateProcess = true;

    public $backupGlobals = false;

    public $preserveGlobalState = false;

    public function dpGeneral()
    {

        return array(
            array(
                __DIR__ . '/data/fn_fgetcsv.csv',
                array(
                    array('1A-1',     '1-2',     ' 1-3 ',     ),
                    array('1B-1',     '1-2',     ' 1-3 ',   ''),
                    array('2A-1',     '2-2',     '2-3',       ),
                    array('2B-1',     '2-2',     '2-3',     ''),
                    array('  3A-1  ', '  3-2  ', '  3-3',     ),
                    array('  3B-1  ', '  3-2  ', '  3-3  ', ''),
                    array('4A-1',     '4-2',     '4-3',       ),
                    array('4B-1',     '4-2',     '4-3',     ''),
                    array('5A-1',     '5-2',     '',          ),
                    array('5B-1',     '5-2',     '',        ''),
                    array('6A-1',     '6-2',     '',          ),
                    array('6B-1',     '6-2',     '',        ''),
                    array('7A-1',     '  7-2',   '7-3',       ),
                    array('7B-1',     '  7-2',   '7-3',     ''),
                ),
            ),
        );
    }

    protected function setUp(): void
    {
        $this->requireCore('functions/fn.fs.php');
    }

    /**
     * @param string $file      File to read data from
     * @param array  $expected  Expected result
     * @param int    $length    Max string length
     * @param string $delimiter CSV delimiter
     * @param string $enclosure CSV field enclosure
     *
     * @dataProvider dpGeneral
     */
    public function testGeneral($file, $expected, $length = 65536, $delimiter = ';', $enclosure = '"')
    {
        $fp = fopen($file, 'r');

        $actual = array();

        do {
            $_act = fn_fgetcsv($fp, $length, $delimiter, $enclosure);
            if ($_act !== false) {
                $actual[] = $_act;
            }
        } while ($_act !== false);

        fclose($fp);

        $this->assertEquals($expected, $actual);
    }
}