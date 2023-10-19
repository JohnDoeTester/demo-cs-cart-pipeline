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

/**
 * Provides list of variants for Order status on refund add-on setting.
 *
 * @return array<string, string>
 */
function fn_settings_variants_addons_paypal_commerce_platform_rma_refunded_order_status()
{
    $order_statuses = fn_get_simple_statuses(STATUSES_ORDER);

    return array_merge(
        ['' => __('paypal_commerce_platform.do_not_change')],
        $order_statuses
    );
}
