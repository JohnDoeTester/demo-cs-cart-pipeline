<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Tests\Unit\Functions\Control;


use Tygh\Tests\Unit\ATestCase;

class GetDispatchRoutingTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->requireCore('functions/fn.control.php');
    }

    /**
     * @dataProvider dpTestGeneral
     */
    public function testGeneral($request, $expected)
    {
        $actual = fn_get_dispatch_routing($request);

        $this->assertEquals($expected, $actual);
    }

    public function dpTestGeneral()
    {
        return array(
            array('',                                           array('index.index', 'index', 'index', '', '')),
            array(array(),                                      array('index.index', 'index', 'index', '', '')),
            array(array('dispatch' => ''),                      array('index.index', 'index', 'index', '', '')),
            array(array('dispatch' => array()),                 array('index.index', 'index', 'index', '', '')),
            array(array('dispatch' => array('')),               array('index.index', 'index', 'index', '', '')),

            array(array('dispatch' => 'C.M'),                   array('C.M',     'C', 'M', '',  '')),
            array(array('dispatch' => array('C.M.' => 'Y')),    array('C.M',     'C', 'M', '',  '')),

            array(array('dispatch' => 'C.M.A'),                 array('C.M.A',   'C', 'M', 'A', '')),
            array(array('dispatch' => array('C.M.A/' => 'Y')),  array('C.M.A',   'C', 'M', 'A', '')),

            array(array('dispatch' => 'C.M.A.E'),               array('C.M.A.E', 'C', 'M', 'A', 'E')),
            array(array('dispatch' => array('C.M/A.E' => 'Y')), array('C.M.A.E', 'C', 'M', 'A', 'E')),
        );
    }
}