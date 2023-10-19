<?php


namespace Tygh\Tests\Unit\Functions\Catalog;

use Tygh\Enum\ProductOptionTypes;
use Tygh\Tests\Unit\ATestCase;
use Tygh\Tygh;

class ApplyOptionsModifiersTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    private $current_app;
    private $app;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->requireCore('functions/fn.options.php');
        $this->requireMockFunction('fn_set_hook');

        $this->current_app = Tygh::$app;
        $this->app = Tygh::createApplication();

        $db = $this->getMockBuilder('\Tygh\Database\Connection')
            ->disableOriginalConstructor()
            ->setMethods(array('getSingleHash', 'getHash'))
            ->getMock();

        $db->method('getSingleHash')->willReturnMap($this->getSingleHashReturnMap());
        $db->method('getHash')->willReturnMap($this->getHashReturnMap());

        $this->app['db'] = $db;

        parent::setUp();
    }

    /**
     * @param $selected_options
     * @param $value
     * @param $type
     * @param $stored_options
     * @param $expected
     * @dataProvider dpApplyOptionsModifiers
     */
    public function testApplyOptionsModifiers($selected_options, $value, $type, $stored_options, $expected)
    {
        $this->assertEquals($expected, fn_apply_options_modifiers(
            $selected_options,
            $value,
            $type,
            $stored_options
        ));
    }

    public function dpApplyOptionsModifiers()
    {
        return array(
            array(
                array(
                    3 => 30,
                    4 => 40,
                    5 => 50
                ),
                100,
                'P',
                array(),
                145
            ),
            array(
                array(
                    3 => 30,
                    4 => 40,
                    5 => 50
                ),
                100,
                'P',
                array(
                    array(
                        'option_id' => 3,
                        'option_type' => ProductOptionTypes::SELECTBOX,
                        'modifier' => 5,
                        'modifier_type' => 'A',
                        'value' => 30
                    ),
                    array(
                        'option_id' => 4,
                        'option_type' => ProductOptionTypes::CHECKBOX,
                        'modifier' => 10,
                        'modifier_type' => 'A',
                        'value' => 40
                    ),
                    array(
                        'option_id' => 5,
                        'option_type' => ProductOptionTypes::RADIO_GROUP,
                        'modifier' => 15,
                        'modifier_type' => 'A',
                        'value' => 50
                    ),
                ),
                130
            ),
            array(
                array(
                    6 => 60,
                    7 => 70,
                    8 => 80
                ),
                100,
                'P',
                array(),
                120
            ),
            array(
                array(
                    6 => 60,
                    7 => 70,
                    8 => 80
                ),
                100,
                'P',
                array(
                    array(
                        'option_id' => 6,
                        'option_type' => ProductOptionTypes::SELECTBOX,
                        'modifier' => 5,
                        'modifier_type' => 'A',
                        'value' => 60
                    ),
                    array(
                        'option_id' => 7,
                        'option_type' => ProductOptionTypes::CHECKBOX,
                        'modifier' => 10,
                        'modifier_type' => 'P',
                        'value' => 70
                    ),
                    array(
                        'option_id' => 8,
                        'option_type' => ProductOptionTypes::INPUT,
                        'modifier' => 5,
                        'modifier_type' => 'A',
                        'value' => 80
                    ),
                ),
                115
            ),
        );
    }

    private function getSingleHashReturnMap()
    {
        $sql = 'SELECT option_type as type, option_id FROM ?:product_options WHERE option_id IN (?n)';
        $hash_index = array('option_id', 'type');

        return array(
            array($sql, $hash_index, array(3, 4, 5), array(
                3 => ProductOptionTypes::SELECTBOX,
                4 => ProductOptionTypes::CHECKBOX,
                5 => ProductOptionTypes::RADIO_GROUP,
            )),
            array($sql, $hash_index, array(6, 7, 8), array(
                6 => ProductOptionTypes::SELECTBOX,
                7 => ProductOptionTypes::CHECKBOX,
                8 => ProductOptionTypes::INPUT,
            )),
        );
    }

    private function getHashReturnMap()
    {
        $sql = 'SELECT ?p, a.variant_id FROM ?:product_option_variants a ?p WHERE 1 AND ?p';
        $fields_p = 'a.modifier, a.modifier_type';

        return array(
            array($sql, 'variant_id', $fields_p, '', 'a.variant_id IN (30, 40, 50)', array(
                30 => array('modifier' => 10, 'modifier_type' => 'A'),
                40 => array('modifier' => 15, 'modifier_type' => 'A'),
                50 => array('modifier' => 20, 'modifier_type' => 'A'),
            )),
            array($sql, 'variant_id', $fields_p, '', 'a.variant_id IN (60, 70, 80)', array(
                60 => array('modifier' => 10, 'modifier_type' => 'A'),
                70 => array('modifier' => 10, 'modifier_type' => 'P'),
                80 => array('modifier' => 5, 'modifier_type' => 'A'),
            )),
        );
    }
}
