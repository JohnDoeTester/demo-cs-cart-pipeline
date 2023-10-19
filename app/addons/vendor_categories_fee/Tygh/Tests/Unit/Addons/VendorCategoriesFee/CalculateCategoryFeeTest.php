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

class CalculateCategoryFeeTest extends ATestCase
{
    public function setUp(): void
    {
        $this->requireCore('addons/vendor_categories_fee/func.php');
    }
    /**
     * @dataProvider dpCalculationData
     */
    public function testCalculateCategoryFee($calculation_data, $expected_fee_amount)
    {
        list($order_total, $payout_data, $products, $main_categories_fee, $parent_categories_fee, $payouts_history) = $calculation_data;
        $category_fee_payout_data = fn_vendor_categories_fee_calculate_payout($order_total, $payout_data, $products, $main_categories_fee, $parent_categories_fee, $payouts_history);

        $this->assertEqualsWithDelta($expected_fee_amount, $category_fee_payout_data['commission_amount'], 0.001);
    }

    public function dpCalculationData()
    {
        return [
            [
                'calculation_data' => require_once(__DIR__ . '/fixtures/order_placed.php'),
                'expected_fee_amount' => 53.35,
            ],
            [
                'calculation_data' => require_once(__DIR__ . '/fixtures/order_edited.php'),
                'expected_fee_amount' => -26.11,
            ],
            [
                'calculation_data' => require_once(__DIR__ . '/fixtures/order_reverted.php'),
                'expected_fee_amount' => 26.11,
            ],
        ];
    }
}