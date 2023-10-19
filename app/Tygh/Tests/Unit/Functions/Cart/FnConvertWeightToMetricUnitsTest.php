<?php

namespace Tygh\Tests\Unit\Functions\Cart;

use Tygh\Registry;
use Tygh\Tests\Unit\ATestCase;

class FnConvertWeightToMetricUnitsTest extends ATestCase
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
                    'full_grams'     => 10000.0,
                    'full_kilograms' => 10.0,
                    'kilograms'      => 10.0,
                    'grams'          => 0.0,
                    'plain'          => 10.0,
                ]
            ],
            [
                '10.2',
                [
                    'full_grams'     => 10200.0,
                    'full_kilograms' => 10.2,
                    'kilograms'      => 10.0,
                    'grams'          => 200.0,
                    'plain'          => 10.2,
                ]
            ],
            [
                '1.9',
                [
                    'full_grams'     => 1900.0,
                    'full_kilograms' => 1.9,
                    'kilograms'      => 1.0,
                    'grams'          => 900.0,
                    'plain'          => 1.9,
                ]
            ],
            [
                '0.4',
                [
                    'full_grams'     => 400.0,
                    'full_kilograms' => 0.4,
                    'kilograms'      => 0.0,
                    'grams'          => 400.0,
                    'plain'          => 0.4,
                ]
            ]
            ,
            [
                '0',
                [
                    'full_grams'     => 0.0,
                    'full_kilograms' => 0.0,
                    'kilograms'      => 0.0,
                    'grams'          => 0.0,
                    'plain'          => 0.0,
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
                    'full_grams'     => 4536.0,
                    'full_kilograms' => 4.5,
                    'kilograms'      => 4.0,
                    'grams'          => 536.0,
                    'plain'          => 10.0,
                ]
            ],
            [
                '10.2',
                [
                    'full_grams'     => 4627.0,
                    'full_kilograms' => 4.6,
                    'kilograms'      => 4.0,
                    'grams'          => 627.0,
                    'plain'          => 10.2,
                ]
            ],
            [
                '1.9',
                [
                    'full_grams'     => 862.0,
                    'full_kilograms' => 0.9,
                    'kilograms'      => 0.0,
                    'grams'          => 862.0,
                    'plain'          => 1.9,
                ]
            ],
            [
                '1.003',
                [
                    'full_grams'     => 455.0,
                    'full_kilograms' => 0.5,
                    'kilograms'      => 0.0,
                    'grams'          => 455.0,
                    'plain'          => 1.003,
                ]
            ],
            [
                '0.4',
                [
                    'full_grams'     => 182.0,
                    'full_kilograms' => 0.2,
                    'kilograms'      => 0.0,
                    'grams'          => 182.0,
                    'plain'          => 0.4,
                ]
            ],
            [
                '0',
                [
                    'full_grams'     => 0.0,
                    'full_kilograms' => 0.0,
                    'kilograms'      => 0.0,
                    'grams'          => 0.0,
                    'plain'          => 0.0,
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
     * @param array  $expected Whether weight converted to kilograms and grams
     *
     * @dataProvider dpKilograms
     */
    public function testConvertKilograms($weight, $expected)
    {
        Registry::set('settings.General.weight_symbol_grams', 1000);

        $converted_weight = fn_convert_weight_to_metric_units($weight);

        $this->assertEquals($expected, $converted_weight);
    }

    /**
     * @param string $weight   Weight in pounds
     * @param array  $expected Whether weight converted to kilograms and grams
     *
     * @dataProvider dpPounds
     */
    public function testConvertPounds($weight, $expected)
    {
        Registry::set('settings.General.weight_symbol_grams', 453.6);

        $converted_weight = fn_convert_weight_to_metric_units($weight);

        $this->assertEquals($expected, $converted_weight);
    }
}