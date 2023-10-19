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

use Tygh\Enum\Addons\VendorDebtPayout\VendorDebtStatuses;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\SiteArea;
use Tygh\Enum\VendorStatuses;
use Tygh\Registry;
use Tygh\Tools\Url;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @var array<string, array> $schema
 */

if (Registry::get('runtime.company_id')) {
    fn_vendor_debt_payout_check_vendor_debt(Registry::get('runtime.company_id'));
} else {
    fn_vendor_debt_payout_check_debts();
}

$schema['vendor_status_suspended'] = [
    'type'             => NotificationSeverity::WARNING,
    'area'             => SiteArea::VENDOR_PANEL,
    'content_callback' => static function ($auth) {
        $vendor_plan = fn_vendor_plans_get_vendor_plan_by_company_id($auth['company_id']);

        if (empty($vendor_plan)) {
            return false;
        }

        $data = [];
        $vendor_debt_data = fn_vendor_debt_payout_get_vendor_debt_data($auth['company_id']);
        $amount_to_pay = fn_vendor_debt_payout_get_amount_to_pay($auth['company_id']);

        if (
            $vendor_debt_data['status'] === VendorStatuses::SUSPENDED
            && !$amount_to_pay
        ) {
            $data['text'] = __('vendor_debt_payout.dashboard.todo.suspend_alert');
        }

        return $data;
    }
];

$schema['vendor_debt_payout_vendor_status_suspended'] = [
    'type'             => NotificationSeverity::ERROR,
    'area'             => SiteArea::VENDOR_PANEL,
    'content_callback' => static function ($auth) {
        $vendor_plan = fn_vendor_plans_get_vendor_plan_by_company_id($auth['company_id']);

        if (empty($vendor_plan)) {
            return false;
        }

        $data = [];
        $vendor_debt_data = fn_vendor_debt_payout_get_vendor_debt_data($auth['company_id']);
        $amount_to_pay = fn_vendor_debt_payout_get_amount_to_pay($auth['company_id']);

        if (
            $vendor_debt_data['debt_status'] === VendorDebtStatuses::SUSPENDED
            && $amount_to_pay
        ) {
            /** @var \Tygh\Tools\Formatter $formatter */
            $formatter = Tygh::$app['formatter'];

            $amount = $formatter->asPrice($amount_to_pay);
            $data['text'] = __('vendor_debt_payout.dashboard.todo.vendor_status_changed_to_suspended', ['[amount]' => $amount]);
        }

        return $data;
    }
];

$schema['vendor_debt_payout_negative_balance_reached'] = [
    'type'             => NotificationSeverity::WARNING,
    'area'             => SiteArea::VENDOR_PANEL,
    'content_callback' => static function ($auth) {
        $amount = fn_vendor_debt_payout_get_amount_to_pay($auth['company_id']);
        $date_of_block = fn_vendor_debt_payout_get_date_of_block($auth['company_id'], VendorStatuses::SUSPENDED);

        if (empty($date_of_block)) {
            return false;
        }

        /** @var \Tygh\Tools\Formatter $formatter */
        $formatter = Tygh::$app['formatter'];

        $vendor_plan = fn_vendor_plans_get_vendor_plan_by_company_id($auth['company_id']);

        if (empty($vendor_plan)) {
            return false;
        }

        $vendor_debt_data = fn_vendor_debt_payout_get_vendor_debt_data($auth['company_id']);

        if ($vendor_debt_data['debt_status'] !== VendorDebtStatuses::IN_GRACE_PERIOD) {
            return false;
        }

        $warning_data = [
            'amount' => $formatter->asPrice($amount),
            'date'   => $formatter->asDatetime($date_of_block)
        ];

        $data = [
            'action_text' => __('vendor_debt_payout.dashboard.todo.negative_balance_reached.action_text'),
            'action_url' => fn_vendor_debt_payout_get_pay_url($auth['company_id'], $auth, $amount)
        ];

        $data['text'] = __(
            'vendor_debt_payout.dashboard.todo.negative_balance_reached',
            ['[date]' => $warning_data['date'], '[amount]' => $warning_data['amount']]
        );

        return $data;
    }
];

$schema['vendor_debt_payout_vendors_have_debts'] = [
    'type'             => NotificationSeverity::NOTICE,
    'area'             => SiteArea::ADMIN_PANEL,
    'content_callback' => static function ($auth) {
        $vendors_debt_data = db_get_array('SELECT company_id, status, suspend_date, grace_period_start FROM ?:companies');

        $company_ids = [];
        foreach ($vendors_debt_data as $vendor_debt_data) {
            $debt_status = fn_vendor_debt_payout_get_vendor_debt_status($vendor_debt_data);
            if ($debt_status !== VendorDebtStatuses::IN_GRACE_PERIOD) {
                continue;
            }

            $company_ids[] = $vendor_debt_data['company_id'];
        }

        if (empty($company_ids)) {
            return false;
        }

        return [
            'text' => __('vendor_debt_payout.dashboard.todo.vendors_have_debts', [count($company_ids)]),
            'action_url' => Url::buildUrn(['companies', 'manage'], ['company_id' => $company_ids])
        ];
    }
];

return $schema;
