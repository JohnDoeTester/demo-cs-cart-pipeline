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

use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $mode */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'update') {
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    /** @var array<string, string> $category_data */
    $category_data = $view->getTemplateVars('category_data');
    if (
        empty($category_data['category_id'])
        || (int) $category_data['category_id'] !== fn_vendor_debt_payout_get_payout_category()
    ) {
        return [CONTROLLER_STATUS_OK];
    }

    $tabs = Registry::get('navigation.tabs');
    foreach ($tabs as $id => &$tab) {
        $tab['hidden'] = $id !== 'detailed';
    }
    unset($tab);
    Registry::set('navigation.tabs', $tabs);
    $_REQUEST['show_block_manager'] = false;
}

return [CONTROLLER_STATUS_OK];
