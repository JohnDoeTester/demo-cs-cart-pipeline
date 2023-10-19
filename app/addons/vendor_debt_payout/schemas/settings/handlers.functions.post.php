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

use Tygh\Enum\YesNo;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Returns additional input or label attributes for default_lowers_allowed_balance setting
 *
 * @return array<string, array<string, int|string>> List of the attributes
 */
function fn_settings_handlers_vendor_debt_payout_general_default_lowers_allowed_balance()
{
    $currencies = Registry::get('currencies');
    $input_attributes = [
        'class'       => 'cm-numeric',
        'data-a-sign' => empty($currencies[CART_PRIMARY_CURRENCY]['symbol']) ? '' : $currencies[CART_PRIMARY_CURRENCY]['symbol'],
        'data-a-dec'  => '.',
        'data-a-sep'  => ','
    ];
    if ($currencies[CART_PRIMARY_CURRENCY]['after'] === YesNo::YES) {
        $input_attributes['data-p-sign'] = 's';
    }

    return [
        'input_attributes' => $input_attributes,
        'label_attributes' => [
        ]
    ];
}

/**
 *  Returns additional input or label attributes for default_grace_period_to_refill_balance setting
 *
 * @return array<string, array<string, int|string>> List of the attributes
 */
function fn_settings_handlers_vendor_debt_payout_general_default_grace_period_to_refill_balance()
{
    return [
        'input_attributes' => [
            'class'       => 'cm-numeric',
            'size'        => 4,
            'data-a-sign' => __('vendor_debt_payout.day_or_days'),
            'data-m-dec'  => '0',
            'data-a-sep'  => ',',
            'data-p-sign' => 's'
        ],
        'label_attributes' => [
        ]
    ];
}
