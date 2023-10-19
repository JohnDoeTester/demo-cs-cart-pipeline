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

namespace Tygh\Tests\Unit\Addons\VendorCategoryFee;

use Tygh\Tests\Unit\ATestCase;

class CalculateProductTaxesBasedOnSubtotalTest extends ATestCase
{
    public function setUp(): void
    {
        $this->requireCore('addons/vendor_categories_fee/func.php');
    }
    /**
     * @dataProvider dpCalculationData
     */
    public function testCalculateProductTaxesBasedOnSubtotal($calculation_data, $expected)
    {
        list($subtotal, $taxes) = $calculation_data;
        $product_taxes = fn_vendor_categories_fee_get_included_product_taxes_based_on_subtotal($subtotal, $taxes);

        $this->assertEquals($expected, $product_taxes);
    }

    public function dpCalculationData()
    {
        return [
            [
                // calculation based on subtotal, tax included to price
                array(
                    array(
                        2575470644 => 100.0,
                        9159054    => 200.0,
                    ),
                    array(
                        6 => array(
                            'rate_type'          => 'P',
                            'rate_value'         => '10.000',
                            'price_includes_tax' => 'Y',
                            'regnumber'          => '1234242',
                            'priority'           => 0,
                            'tax_subtotal'       => 29.82,
                            'description'        => 'VAT',
                            'applies'            => array(
                                'P'     => 27.27,
                                'S'     => 2.55,
                                'items' => array(
                                    'S' => array(
                                        0 => array(
                                            1 => true,
                                        ),
                                    ),
                                    'P' => array(
                                        2575470644 => true,
                                        9159054    => true,
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    2575470644 => 9.09,
                    9159054    => 18.18,
                ),
            ],
            // calculation based on subtotal, tax not included to price
            [
                array(
                    array(
                        2575470644 => 100.0,
                        9159054    => 200.0,
                    ),
                    array(
                        6 => array(
                            'rate_type'          => 'P',
                            'rate_value'         => '10.000',
                            'price_includes_tax' => 'N',
                            'regnumber'          => '1234242',
                            'priority'           => 0,
                            'tax_subtotal'       => 29.82,
                            'description'        => 'VAT',
                            'applies'            => array(
                                'P'     => 27.27,
                                'S'     => 2.55,
                                'items' => array(
                                    'S' => array(
                                        0 => array(
                                            1 => true,
                                        ),
                                    ),
                                    'P' => array(
                                        2575470644 => true,
                                        9159054    => true,
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    2575470644 => 0.0,
                    9159054    => 0.0,
                ),
            ],
            // calculation based on subtotal, tax included to price, two taxes
            [
                array(
                    array(
                        2575470644 => 300.0,
                        9159054    => 200.0,
                    ),
                    array(
                        6 => array(
                            'rate_type'          => 'P',
                            'rate_value'         => '10.000',
                            'price_includes_tax' => 'Y',
                            'regnumber'          => '1234242',
                            'priority'           => 0,
                            'tax_subtotal'       => 48.0,
                            'description'        => 'VAT',
                            'applies'            => array(
                                'P'     => 45.45,
                                'S'     => 2.55,
                                'items' => array(
                                    'S' => array(
                                        0 => array(
                                            1 => true,
                                        ),
                                    ),
                                    'P' => array(
                                        2575470644 => true,
                                        9159054    => true,
                                    ),
                                ),
                            ),
                        ),
                        7 => array(
                            'rate_type'          => 'P',
                            'rate_value'         => '5.000',
                            'price_includes_tax' => 'Y',
                            'regnumber'          => '321321',
                            'priority'           => 0,
                            'tax_subtotal'       => 23.81,
                            'description'        => 'WTF',
                            'applies'            => array(
                                'P'     => 23.81,
                                'S'     => 0,
                                'items' => array(
                                    'S' => array(),
                                    'P' => array(
                                        2575470644 => true,
                                        9159054    => true,
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    2575470644 => 41.56,
                    9159054    => 27.7,
                ),
            ],
            // calculation based on subtotal, tax included to price, one (of two) tax is fixed
            [
                array(
                    array(
                        2575470644 => 300.0,
                        9159054    => 200.0,
                    ),
                    array(
                        6 => array(
                            'rate_type'          => 'P',
                            'rate_value'         => '10.000',
                            'price_includes_tax' => 'Y',
                            'regnumber'          => '1234242',
                            'priority'           => 0,
                            'tax_subtotal'       => 48.0,
                            'description'        => 'VAT',
                            'applies'            => array(
                                'P'     => 45.45,
                                'S'     => 2.55,
                                'items' => array(
                                    'S' => array(
                                        0 => array(
                                            1 => true,
                                        ),
                                    ),
                                    'P' => array(
                                        2575470644 => true,
                                        9159054    => true,
                                    ),
                                ),
                            ),
                        ),
                        7 => array(
                            'rate_type'          => 'F',
                            'rate_value'         => '5.000',
                            'price_includes_tax' => 'Y',
                            'regnumber'          => '321321',
                            'priority'           => 0,
                            'tax_subtotal'       => 5.0,
                            'description'        => 'WTF',
                            'applies'            => array(
                                'P'     => 5.0,
                                'S'     => 0,
                                'items' =>
                                    array(
                                        'S' => array(),
                                        'P' => array(
                                            2575470644 => true,
                                            9159054    => true,
                                        ),
                                    ),
                            ),
                        ),
                    ),
                ),
                array(
                    2575470644 => 30.27,
                    9159054    => 20.18,
                ),
            ],
            // calculation based on subtotal, tax included to price, each product has its own tax
            [
                array(
                    array(
                        2575470644 => 300.0,
                        9159054    => 200.0,
                    ),
                    array(
                        6 => array(
                            'rate_type'          => 'P',
                            'rate_value'         => '10.000',
                            'price_includes_tax' => 'Y',
                            'regnumber'          => '1234242',
                            'priority'           => 0,
                            'tax_subtotal'       => 16.19,
                            'description'        => 'VAT',
                            'applies'            => array(
                                'P'     => 13.64,
                                'S'     => 2.55,
                                'items' => array(
                                    'S' => array(
                                        0 => array(
                                            1 => true,
                                        ),
                                    ),
                                    'P' => array(
                                        2575470644 => true,
                                    ),
                                ),
                            ),
                        ),
                        7 => array(
                            'rate_type'          => 'P',
                            'rate_value'         => '5.000',
                            'price_includes_tax' => 'Y',
                            'regnumber'          => '321321',
                            'priority'           => 0,
                            'tax_subtotal'       => 10.85,
                            'description'        => 'WTF',
                            'applies'            => array(
                                'P'     => 9.52,
                                'S'     => 1.33,
                                'items' => array(
                                    'S' => array(
                                        0 => array(
                                            1 => true,
                                        ),
                                    ),
                                    'P' => array(
                                        9159054 => true,
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    2575470644 => 13.64,
                    9159054    => 9.52,
                ),
            ],
        ];
    }
}
