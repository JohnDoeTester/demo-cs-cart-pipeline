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
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Models\VendorPlan;
use Tygh\Shippings\Shippings;

class CompaniesHookHandler
{
    /**
     * The 'are_company_orders_fulfilled_by_marketplace' hook handler.
     *
     * Action performed:
     *     - Changes specified for company orders fulfillment status.
     *
     * @param int  $company_id         Company identifier.
     * @param bool $fulfillment_status Company's order fulfillment by marketplace status.
     *
     * @see \fn_are_company_orders_fulfilled_by_marketplace()
     *
     * @return void
     */
    public function onAreCompanyOrdersFulfilledByMarketplace($company_id, &$fulfillment_status)
    {
        $plan = VendorPlan::model()->find(['company_id' => $company_id]);
        if (!($plan instanceof VendorPlan && isset($plan->is_fulfillment_by_marketplace))) {
            return;
        }
        $fulfillment_status = YesNo::toBool($plan->is_fulfillment_by_marketplace);
    }

    /**
     * The `update_company_pre` hook handler.
     *
     * Action performed:
     *     - Changes selected shippings for vendor if new vendor plan wont allow it.
     *
     * @param array<array<string>|string> $company_data Company data.
     * @param int                         $company_id   Company identifier.
     * @param string                      $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.).
     * @param bool                        $can_update   Flag, allows addon to forbid to create/update company.
     *
     * @see \fn_update_company()
     *
     * @return void
     */
    public function onPreCompanyUpdate(array &$company_data, $company_id, $lang_code, $can_update)
    {
        $plan = VendorPlan::model()->find($company_data['plan_id']);
        if (!($plan instanceof VendorPlan && isset($plan->is_fulfillment_by_marketplace))) {
            return;
        }
        if ($company_id) {
            /** @var VendorPlan $original_plan */
            $original_plan = VendorPlan::model()->find(['company_id' => $company_id]);
            if (!($original_plan instanceof VendorPlan) || $original_plan->plan_id === $plan->plan_id) {
                return;
            }
        }

        $all_shippings = fn_get_shippings(false);
        $marketplace_shippings = array_filter($all_shippings, static function ($shipping) {
            return !$shipping['company_id'] && $shipping['status'] === ObjectStatuses::ACTIVE;
        });
        $plan_fulfillment_status = YesNo::toBool($plan->is_fulfillment_by_marketplace);
        if (isset($original_plan)) {
            $original_plan_fulfilllment_status = YesNo::toBool($original_plan->is_fulfillment_by_marketplace);
            if ($plan_fulfillment_status === $original_plan_fulfilllment_status) {
                return;
            }
        }

        $company_shippings = array_filter($marketplace_shippings, static function ($shipping) use ($plan_fulfillment_status) {
            $is_shipping_sent_by_marketplace = Shippings::isSentByMarketplace($shipping);
            return $is_shipping_sent_by_marketplace === $plan_fulfillment_status;
        });
        /** @var array<string> $company_shippings */
        $company_shippings = array_column($company_shippings, 'shipping_id');
        $original_data = fn_get_company_data($company_id);
        if (empty($original_data) || $company_shippings !== $original_data['shippings_ids']) {
            $company_data['shippings'] = $company_shippings;
        }
        $current_company_id = fn_get_runtime_company_id();
        if ($plan_fulfillment_status) {
            $message = $current_company_id
                ? __('order_fulfillment.you_moved_to_fulfillment')
                : __('order_fulfillment.company_moved_to_fulfillment', ['[name]' => $company_data['company']]);
        } else {
            $message = $current_company_id
                ? __('order_fulfillment.you_moved_from_fulfillment')
                : __('order_fulfillment.company_moved_from_fulfillment', ['[name]' => $company_data['company']]);
        }
        if (SiteArea::isStorefront(AREA)) {
            return;
        }
        fn_set_notification(NotificationSeverity::WARNING, __('notice'), $message, 'S');
    }

    /**
     * The `what_companies_orders_are_fulfilled_by_marketplace` hook handler.
     *
     * Action performed:
     *      - Returns company ids which are using fulfillment by marketplace.
     *
     * @param array<int> $company_ids Fulfilled company ids.
     *
     * @return void
     */
    public function onWhatCompaniesOrdersAreFulfilledByMarketplace(array &$company_ids)
    {
        $plan_ids = VendorPlan::model()->findMany(['get_ids' => true, 'is_fulfillment_by_marketplace' => YesNo::YES]);
        $company_ids = db_get_fields('SELECT company_id as companies FROM ?:companies WHERE plan_id IN (?n)', $plan_ids);
    }
}
