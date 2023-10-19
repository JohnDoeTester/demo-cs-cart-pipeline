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
if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
    && $mode === 'update'
    && $_REQUEST['addon'] === 'vendor_debt_payout'
) {
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $options = $view->getTemplateVars('options');
    $addon_setting_ids = [];
    foreach ($options['general'] as $setting_id => $option_item) {
        $addon_setting_ids[$option_item['name']] = $setting_id;
    }

    $view->assign([
        'addon_setting_ids'  => $addon_setting_ids,
        'payout_product_id'  => fn_vendor_debt_payout_get_payout_product(),
        'payout_category_id' => fn_vendor_debt_payout_get_payout_category(),
    ]);
}

return [CONTROLLER_STATUS_OK];
