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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode == 'update') {
    Registry::set('navigation.tabs.suppliers', array (
        'title' => __('suppliers'),
        'js' => true
    ));

    $shipping_data = Tygh::$app['view']->getTemplateVars('shipping');

    list($suppliers) = fn_get_suppliers();
    if (fn_allowed_for('ULTIMATE') && !fn_get_runtime_company_id()) {
        $suppliers = fn_suppliers_filter_objects_by_sharing(
            $suppliers,
            'suppliers',
            'supplier_id',
            'shippings',
            $shipping_data['shipping_id']
        );
    }

    $linked_suppliers = fn_get_shippings_suppliers($shipping_data['shipping_id']);

    Tygh::$app['view']->assign('suppliers', $suppliers);
    Tygh::$app['view']->assign('linked_suppliers', $linked_suppliers);
}
