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

/** @var string $mode */
if ($_SERVER['REQUEST_METHOD'] == 'GET'
    && $mode == 'update'
    && $_REQUEST['addon'] == 'stripe_connect'
) {
    $options = Tygh::$app['view']->getTemplateVars('options');

    foreach ($options['general'] as $setting_id => $option_item) {
        if ($option_item['name'] == 'rma_refunded_order_status') {
            Tygh::$app['view']->assign('rma_refunded_order_status_id', $setting_id);
        }
    }

    Tygh::$app['view']->assign('order_statuses', fn_get_simple_statuses(STATUSES_ORDER));
}