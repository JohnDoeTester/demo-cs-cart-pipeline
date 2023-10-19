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

namespace Tygh\Addons\OrderFulfillment\HookHandlers;


use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\VendorPayoutTypes;
use Tygh\Enum\YesNo;
use Tygh\Models\VendorPlan;
use Tygh\Registry;
use Tygh\Shippings\Shippings;

class VendorPlansHookHandler
{
    /**
     * The 'vendor_plan_update' hook handler.
     *
     * Action performed:
     *     - Updates shippings at companies with currently updating vendor plan. Accordingly to new fulfillment status.
     *
     * @param VendorPlan $plan      Instance of Vendor plan.
     * @param bool       $result    Can save flag.
     * @param int[]      $companies Companies.
     *
     * @see VendorPlan::update()
     *
     * @return void
     */
    public function onVendorPlanUpdate(VendorPlan $plan, $result, array $companies)
    {
        if (!$result) {
            return;
        }
        $extended_plan = VendorPlan::model()->find($plan->plan_id);
        if (!($extended_plan instanceof VendorPlan && isset($extended_plan->is_fulfillment_by_marketplace))) {
            return;
        }

        $fulfillment_status = $extended_plan->is_fulfillment_by_marketplace;
        $all_shippings = fn_get_shippings(false);
        $marketplace_shippings = array_filter($all_shippings, static function ($shipping) {
            return !$shipping['company_id'] && $shipping['status'] === ObjectStatuses::ACTIVE;
        });
        foreach ($companies as $company_id) {
            /** @var false|array<string, string|array<string>> $company_data */
            $company_data = fn_get_company_data($company_id);
            if (!$company_data) {
                continue;
            }
            $company_current_shippings = isset($company_data['shippings_ids']) ? $company_data['shippings_ids'] : [];
            $company_shippings = array_filter($marketplace_shippings, static function ($shipping) use ($fulfillment_status) {
                $is_shipping_sent_by_marketplace = Shippings::isSentByMarketplace($shipping);
                return $is_shipping_sent_by_marketplace === YesNo::toBool($fulfillment_status);
            });
            $company_shippings = array_column($company_shippings, 'shipping_id');

            if ($company_shippings === $company_current_shippings) {
                continue;
            }
            $company_data['shippings'] = $company_shippings;
            fn_update_company($company_data, $company_id);
        }
    }

    /**
     * The `vendor_plans_calculate_commission_for_payout_before` hook handler.
     *
     * Action performed:
     *   - Removes from payout to vendor shipping taxes that was not included into shipping cost.
     *
     * @param array<string>               $order_info              Order information
     * @param array<int>                  $company_data            Company to which order belongs to
     * @param array<array<string, float>> $payout_data             Payout data to be written to database
     * @param float                       $total                   Order total amount
     * @param float                       $shipping_cost           Order shipping cost amount
     * @param float                       $surcharge_from_total    Order payment surcharge to be subtracted from total
     * @param float                       $surcharge_to_commission Order payment surcharge to be added to commission amount
     * @param float                       $commission              The transaction percent value
     * @param float                       $taxes                   Order taxes amount
     * @param float                       $vendor_taxes            All taxes that go to vendor.
     *
     * @psalm-param array{
     *     taxes: array{
     *              array{
     *                  price_includes_tax: string,
     *                  applies: array<string, float>
     *              }
     *            }
     * } $order_info
     *
     * @param-out array<float> $payout_data
     *
     * @see \fn_calculate_commission_for_payout()
     *
     * @return void
     */
    public function onVendorPlansCalculateCommissionForPayoutBefore(
        array $order_info,
        array $company_data,
        array $payout_data,
        $total,
        &$shipping_cost,
        &$surcharge_from_total,
        &$surcharge_to_commission,
        $commission,
        &$taxes,
        &$vendor_taxes
    ) {
        if (!fn_are_company_orders_fulfilled_by_marketplace($company_data['company_id'])) {
            return;
        }
        $vendor_plan_settings = Registry::get('addons.vendor_plans');
        if ($payout_data['payout_type'] === VendorPayoutTypes::ORDER_REFUNDED) {
            $changed_shipping_tax = $payout_data['details']['shipping_tax_amount'] - $payout_data['old_details']['shipping_tax_amount'];
            $surcharge_from_total += $shipping_cost + $changed_shipping_tax;
            $surcharge_to_commission += $shipping_cost + $changed_shipping_tax;
            $shipping_cost = 0.0;
            if (YesNo::toBool($vendor_plan_settings['include_shipping'])) {
                $vendor_taxes -= $changed_shipping_tax;
                $taxes -= $changed_shipping_tax;
            }
            return;
        }
        $surcharge_from_total += $shipping_cost;
        $surcharge_to_commission += $shipping_cost;
        $shipping_cost = 0.0;
        $vendor_taxes -= $payout_data['details']['shipping_tax_amount'];
        //phpcs:ignore
        if (YesNo::isTrue($vendor_plan_settings['include_shipping'])) {
            $taxes -= $payout_data['details']['shipping_tax_amount'];
        }
    }
}
