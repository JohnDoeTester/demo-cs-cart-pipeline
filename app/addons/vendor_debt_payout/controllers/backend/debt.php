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

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Enum\UserTypes;
use Tygh\Models\VendorPlan;
use Tygh\Enum\NotificationSeverity;
use Tygh\Registry;
use Tygh\Settings;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'refill_balance') {
        if (
            $auth['user_type'] !== UserTypes::VENDOR
            || !isset($auth['company_id'])
            || !isset($_REQUEST['refill_amount'])
        ) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $refill_amount = fn_parse_price($_REQUEST['refill_amount']);
        $pay_debt_url = fn_vendor_debt_payout_get_pay_url($auth['company_id'], Tygh::$app['session']['auth'], $refill_amount);

        return [CONTROLLER_STATUS_REDIRECT, $pay_debt_url];
    }
}

if ($mode === 'drop_plans_lowers_balance') {
    if (Registry::get('addons.vendor_debt_payout.global_lowers_allowed_balance') !== null) {
        Settings::instance()->updateValue('global_lowers_allowed_balance', '0', 'vendor_debt_payout');

        fn_set_notification(
            NotificationSeverity::NOTICE,
            __('notice'),
            __('vendor_debt_payout.minimum_allowed_balance_to_zero'),
            'S'
        );

        return [CONTROLLER_STATUS_REDIRECT, fn_url('addons.manage#groupvendor_debt_payout')];
    }

    $vendor_plans = VendorPlan::model()->findAll();

    foreach ($vendor_plans as $plan) {
        $plan->attributes(['lowers_allowed_balance' => null]);
        $plan->save();
    }

    fn_set_notification(
        NotificationSeverity::NOTICE,
        __('notice'),
        __('vendor_debt_payout.minimum_allowed_balance_to_zero'),
        'S'
    );

    return [CONTROLLER_STATUS_REDIRECT, fn_url('vendor_plans.manage')];
}
