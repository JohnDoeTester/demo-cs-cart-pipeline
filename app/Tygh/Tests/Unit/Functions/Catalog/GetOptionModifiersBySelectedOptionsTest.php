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

namespace Tygh\Tests\Unit\Functions\Catalog;


use Tygh\Enum\ProductOptionTypes;
use Tygh\Tests\Unit\ATestCase;
use Tygh\Tygh;

class GetOptionModifiersBySelectedOptionsTest extends ATestCase
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
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        Tygh::$app = $this->current_app;
    }

    /**
     * @param $selected_options
     * @param $type
     * @param $fields
     * @param $expected
     * @dataProvider dpGetOptionModifiersBySelectedOptions
     */
    public function testGetOptionModifiersBySelectedOptions($selected_options, $type, $fields, $expected)
    {
        $this->assertEquals($expected, fn_get_option_modifiers_by_selected_options(
            $selected_options,
            $type,
            $fields
        ));
    }

    public function dpGetOptionModifiersBySelectedOptions()
    {
        return array(
            array(
                array(
                    3 => 30,
                    4 => 40,
                    5 => 50
                ),
                'P',
                null,
                array(
                    array(
                        'type' => 'A',
                        'value' => 10
                    ),
                    array(
                        'type' => 'A',
                        'value' => 15
                    ),
                    array(
                        'type' => 'A',
                        'value' => 20
                    )
                )
            ),
            array(
                array(
                    6 => 60,
                    7 => 70,
                    8 => 80
                ),
                'P',
                null,
                array(
                    array(
                        'type' => 'A',
                        'value' => 10
                    ),
                    array(
                        'type' => 'P',
                        'value' => 10
                    )
                )
            ),
            array(
                array(
                    6 => 60,
                    7 => 70,
                    5 => 50
                ),
                'W',
                null,
                array(
                    array(
                        'type' => 'A',
                        'value' => 10
                    ),
                    array(
                        'type' => 'P',
                        'value' => 10
                    ),
                    array(
                        'type' => 'A',
                        'value' => 20
                    )
                )
            ),
            array(
                array(
                    6 => 60,
                    7 => 70,
                    5 => 50
                ),
                'W',
                'a.weight_modifier_type as modifier_type, a.weight_modifier as modifier',
                array(
                    array(
                        'type' => 'A',
                        'value' => 10
                    ),
                    array(
                        'type' => 'P',
                        'value' => 10
                    ),
                    array(
                        'type' => 'A',
                        'value' => 20
                    )
                )
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
            array($sql, $hash_index, array(6, 7, 5), array(
                6 => ProductOptionTypes::SELECTBOX,
                7 => ProductOptionTypes::CHECKBOX,
                5 => ProductOptionTypes::RADIO_GROUP,
            )),
        );
    }

    private function getHashReturnMap()
    {
        $sql = 'SELECT ?p, a.variant_id FROM ?:product_option_variants a ?p WHERE 1 AND ?p';
        $fields_p = 'a.modifier, a.modifier_type';
        $fields_w = 'a.weight_modifier as modifier, a.weight_modifier_type as modifier_type';
        $fields_w1 = 'a.weight_modifier_type as modifier_type, a.weight_modifier as modifier';

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
            array($sql, 'variant_id', $fields_w, '', 'a.variant_id IN (60, 70, 50)', array(
                60 => array('modifier' => 10, 'modifier_type' => 'A'),
                70 => array('modifier' => 10, 'modifier_type' => 'P'),
                50 => array('modifier' => 20, 'modifier_type' => 'A'),
            )),
            array($sql, 'variant_id', $fields_w1, '', 'a.variant_id IN (60, 70, 50)', array(
                60 => array('modifier' => 10, 'modifier_type' => 'A'),
                70 => array('modifier' => 10, 'modifier_type' => 'P'),
                50 => array('modifier' => 20, 'modifier_type' => 'A'),
            )),
        );
    }
}
