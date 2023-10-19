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
use Tygh\Addons\AdvancedImport\Presets\Importer;

class ImporterTest extends ATestCase
{
    public $runTestInSeparateProcess = false;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /** @var \Tygh\Addons\AdvancedImport\SchemasManager $schema_manager */
    protected $stub_schema_manager;

    /** @var \Tygh\Addons\AdvancedImport\Modifiers\Parsers\IModifierParser $schema_manager */
    protected $stub_modifier_parser;

    /** @var  \Tygh\Addons\AdvancedImport\Presets\Importer $importer */
    protected $importer;

    protected function setUp(): void
    {
        $this->stub_modifier_parser = $this->getMockBuilder('Tygh\Addons\AdvancedImport\Modifiers\Parsers\SinglePassModifierParser')->getMock();
        $this->stub_schema_manager = $this->getMockBuilder('Tygh\Addons\AdvancedImport\SchemasManager')->getMock();
        $this->requireMockFunction('fn_set_notification');
        $this->requireMockFunction('__');

        $this->importer = new Importer($this->stub_schema_manager, $this->stub_modifier_parser);
    }

    /**
     * @dataProvider dpApplyModifier
     */
    public function testApplyModifier($value, $modifier, $expected, $stubbed_parsed, $stubbed_schema)
    {
        $this->stub_modifier_parser->method('parse')->willReturn($stubbed_parsed);
        $this->stub_schema_manager->method('getModifiers')->willReturn($stubbed_schema);

        $value = $this->importer->applyModifier($value, $modifier, array());
        $this->assertEquals((float) $value, $expected);
    }

    public function dpApplyModifier()
    {
        return array(
            array(
                33,
                'div($value, 0)',
                '33.00',
                array(
                    'function' => 'div',
                    'parameters' => array('$value', 'zero'),
                ),
                $this->getModifiersSchema()
            ),
            array(
                7777.77,
                'div($value, 0.01)',
                '777777.00',
                array(
                    'function' => 'div',
                    'parameters' => array('$value', '0.01'),
                ),
                $this->getModifiersSchema()
            ),
            array(
                999999999,
                'mul($value, 3.33)',
                '3329999996.67',
                array(
                    'function' => 'mul',
                    'parameters' => array('$value', '3.33'),
                ),
                $this->getModifiersSchema()
            ),
            array(
                33.77,
                'sum($value, 44.23)',
                '78.00',
                array(
                    'function' => 'sum',
                    'parameters' => array('$value', '44.23'),
                ),
                $this->getModifiersSchema()
            ),
            array(
                99.99,
                'sub($value, 22.22)',
                '77.77',
                array(
                    'function' => 'sub',
                    'parameters' => array('$value', '22.22'),
                ),
                $this->getModifiersSchema()
            ),
            array(
                1,
                'dummy($value, 3)',
                '1.00',
                array(
                    'function' => 'dummy',
                    'parameters' => array('$value', '3'),
                ),
                $this->getModifiersSchema()
            ),
        );
    }

    protected function getModifiersSchema()
    {
        return array(
            'operations' => array(
                'sum' => array(
                    'current'    => '$value',
                    'parameters' => 2,
                    'operation'  => function ($a = 0, $b = 0) {
                        return number_format((float) $a + (float) $b, 2, '.', '');
                    },
                ),
                'sub' => array(
                    'current'    => '$value',
                    'parameters' => 2,
                    'operation'  => function ($a = 0, $b = 0) {
                        return number_format((float) $a - (float) $b, 2, '.', '');
                    },
                ),
                'mul' => array(
                    'current'    => '$value',
                    'parameters' => 2,
                    'operation'  => function ($a = 0, $b = 0) {
                        return number_format((float) $a * (float) $b, 2, '.', '');
                    },
                ),
                'div' => array(
                    'current'    => '$value',
                    'parameters' => 2,
                    'operation'  => function ($a = 0, $b = 0) {
                        $b = (float) $b;

                        if ($b != 0) {
                            return number_format((float) $a / $b, 2, '.', '');
                        }

                        return $a;
                    },
                ),
            ),
        );
    }
}
