<?php

namespace Tygh\Tests\Unit\Functions\Cart;

use Tygh\Registry;
use Tygh\Tests\Unit\ATestCase;

class FnConvertWeightToImperialUnitsTest extends ATestCase
{
    public $runTestInSeparateProcess = true;

    public $backupGlobals = false;

    public $preserveGlobalState = false;

    public function dpKilograms()
    {
        return [
            [
                '10.0',
                [
                    'full_ounces' => 353.0,
                    'full_pounds' => 22.1,
                    'pounds'      => 22.0,
                    'ounces'      => 1.0,
                    'plain'       => 10.0,
                ]
            ],
            [
                '10.2',
                [
                    'full_ounces' => 360.0,
                    'full_pounds' => 22.5,
                    'pounds'      => 22.0,
                    'ounces'      => 8.0,
                    'plain'       => 10.2,
                ]
            ],
            [
                '1.9',
                [
                    'full_ounces' => 68.0,
                    'full_pounds' => 4.2,
                    'pounds'      => 4.0,
                    'ounces'      => 4.0,
                    'plain'       => 1.9,
                ]
            ],
            [
                '0.4',
                [
                    'full_ounces' => 15.0,
                    'full_pounds' => 0.9,
                    'pounds'      => 0,
                    'ounces'      => 15.0,
                    'plain'       => 0.4,
                ]
            ]
            ,
            [
                '0',
                [
                    'full_ounces' => 0.0,
                    'full_pounds' => 0.0,
                    'pounds'      => 0.0,
                    'ounces'      => 0.0,
                    'plain'       => 0.0,
                ]
            ]
        ];
    }

    public function dpPounds()
    {
        return [
            [
                '10.0',
                [
                    'full_ounces' => 160.0,
                    'full_pounds' => 10.0,
                    'pounds'      => 10.0,
                    'ounces'      => 0.0,
                    'plain'       => 10.0,
                ]
            ],
            [
                '10.2',
                [
                    'full_ounces' => 164.0,
                    'full_pounds' => 10.2,
                    'pounds'      => 10.0,
                    'ounces'      => 4.0,
                    'plain'       => 10.2,
                ]
            ],
            [
                '1.9',
                [
                    'full_ounces' => 31.0,
                    'full_pounds' => 1.9,
                    'pounds'      => 1.0,
                    'ounces'      => 15.0,
                    'plain'       => 1.9,
                ]
            ],
            [
                '1.003',
                [
                    'full_ounces' => 17.0,
                    'full_pounds' => 1.1,
                    'pounds'      => 1.0,
                    'ounces'      => 1.0,
                    'plain'       => 1.003,
                ]
            ],
            [
                '0.4',
                [
                    'full_ounces' => 7.0,
                    'full_pounds' => 0.4,
                    'pounds'      => 0.0,
                    'ounces'      => 7.0,
                    'plain'       => 0.4,
                ]
            ],
            [
                '0',
                [
                    'full_ounces' => 0.0,
                    'full_pounds' => 0.0,
                    'pounds'      => 0.0,
                    'ounces'      => 0.0,
                    'plain'       => 0.0,
                ]
            ]
        ];
    }

    protected function setUp(): void
    {
        $this->requireCore('functions/fn.cart.php');
    }

    /**
     * @param string $weight   Weight in kilograms
     * @param array  $expected Whether weight converted to pounds and ounces
     *
     * @dataProvider dpKilograms
     */
    public function testConvertKilograms($weight, $expected)
    {
        Registry::set('settings.General.weight_symbol_grams', 1000);

        $converted_weight = fn_convert_weight_to_imperial_units($weight);

        $this->assertEquals($expected, $converted_weight);
    }

    /**
     * @param string $weight   Weight in pounds
     * @param array  $expected Whether weight converted to pounds and ounces
     *
     * @dataProvider dpPounds
     */
    public function testConvertPounds($weight, $expected)
    {
        Registry::set('settings.General.weight_symbol_grams', 453.6);

        $converted_weight = fn_convert_weight_to_imperial_units($weight);

        $this->assertEquals($expected, $converted_weight);
    }
}