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

namespace Tygh\Tests\Unit\Addons\AdvancedImport;

use Tygh\Tests\Unit\ATestCase;
use Tygh\Addons\AdvancedImport\Modifiers\Parsers\SinglePassModifierParser;

class SinglePassModifierParserTest extends ATestCase
{
    /** @var SinglePassModifierParser */
    protected $parser;

    protected function setUp(): void
    {
        $this->parser = new SinglePassModifierParser();
        $this->requireMockFunction('__');
    }

    /**
     * @dataProvider dpParse
     */
    public function testParse($modifier, $expected)
    {
        $parsed = $this->parser->parse($modifier);
        $this->assertEquals($parsed, $expected);
    }

    public function dpParse()
    {
        return array(
            array(
                '   r and   (     )    sum()',
                array(
                    'function' => 'rand',
                    'parameters' => array(),
                )
            ),
            array(
                'sum(123)',
                array(
                    'function' => 'sum',
                    'parameters' => array('123'),
                )
            ),
            array(
                'concat("  test string  ")',
                array(
                    'function' => 'concat',
                    'parameters' => array('  test string  '),
                )
            ),
            array(
                'concat(  test string  )',
                array(
                    'function' => 'concat',
                    'parameters' => array('test string'),
                )
            ),
            array(
                'sum(,    1      , 2 ,,    , 3, ,4,,5      );',
                array(
                    'function' => 'sum',
                    'parameters' => array('1', '2', '3', '4', '5'),
                )
            ),
            array(
                'concat($value, "     t,est str,ing     ", \'sum("1", "2")\', t,e""st str,ing)',
                array(
                    'function' => 'concat',
                    'parameters' => array('$value', '     t,est str,ing     ', 'sum("1", "2")', 't', 'e""st str', 'ing'),
                )
            ),
            array(
                'w_replace(10., \'TEST\', "$value")',
                array(
                    'function' => 'w_replace',
                    'parameters' => array('10.', 'TEST', '$value'),
                )
            ),
            array(
                'su)m(1, "2", 3)',
                array(
                    'function' => 'su)m',
                    'parameters' => array('1', '2', '3'),
                )
            ),
            array(
                'if ($value="yes", 100, 0)',
                array(
                    'function' => 'if',
                    'parameters' => array('$value="yes"', '100', '0'),
                )
            ),
            array(
                'case("$value=*", 10, $value="**", 20, $value=\'***\', 100, $value=-, 0)',
                array(
                    'function' => 'case',
                    'parameters' => array('$value=*', '10', '$value="**"', '20', '$value=\'***\'', '100', '$value=-', '0'),
                )
            ),
            array(
                'case($value="test \'sub\' (test)", "newcat", $value="test (sub (test))", "newcat1", $value="test, test", "newcat2")',
                array(
                    'function' => 'case',
                    'parameters' => array('$value="test \'sub\' (test)"', 'newcat', '$value="test (sub (test))"', 'newcat1', '$value="test, test"', 'newcat2'),
                )
            ),
        );
    }

    /**
     * @expectedException \Tygh\Addons\AdvancedImport\Exceptions\InvalidModifierFormatException
     */
    public function testParseFunctionNameException()
    {
        $this->parser->parse('(sum(1,3)');
    }

    /**
     * @expectedException \Tygh\Addons\AdvancedImport\Exceptions\InvalidModifierParameterException
     */
    public function testParseParameterException()
    {
        $this->parser->parse('concat(a,b,"c,"d,e)');
    }

    /**
     * @expectedException \Tygh\Addons\AdvancedImport\Exceptions\InvalidModifierFormatException
     */
    public function testParseMissingParametersListCloser()
    {
        $this->parser->parse('sum(5,11,');
    }
}
