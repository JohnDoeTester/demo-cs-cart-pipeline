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

/** @var string $mode */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if (in_array($mode, ['add', 'update', 'manage'])) {
    $dynamic_sections = Registry::ifGet('navigation.dynamic.sections', []);
    $dynamic_sections['store_locator'] = [
        'title' => __('store_locator'),
        'href'  => 'store_locator.manage',
    ];
    Registry::set('navigation.dynamic.sections', $dynamic_sections);
}

if ($mode === 'update') {
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    /** @var array $shipping */
    $shipping = $view->getTemplateVars('shipping');
    /** @var array $services */
    $services = $view->getTemplateVars('services');
    $module = null;
    if (!empty($shipping['service_id']) && !empty($services[$shipping['service_id']]['module'])) {
        $module = $services[$shipping['service_id']]['module'];
    }

    if ($module === 'store_locator') {
        $show_store_locator_configure_tab = isset($_REQUEST['show_all_settings']);
        if (!$show_store_locator_configure_tab) {
            $tabs = Registry::ifGet('navigation.tabs', []);
            unset($tabs['configure']['href']);
            $tabs['configure']['hidden'] = YesNo::YES;
            Registry::set('navigation.tabs', $tabs);
        }

        $view->assign('show_store_locator_configure_tab', $show_store_locator_configure_tab);
        $view->assign('store_locator_shipping', true);
    }
}

if ($mode == 'configure') {

    if (!empty($_REQUEST['shipping_id'])) {

        $module = !empty($_REQUEST['module']) ? $_REQUEST['module'] : '';
        if ($module == 'store_locator') {

            $shipping = Tygh::$app['view']->getTemplateVars('shipping');

            $params = [];
            if (fn_allowed_for('MULTIVENDOR') && !empty($shipping['company_id'])) {
                $params['company_id'] = $shipping['company_id'] ?: Registry::get('runtime.company_id');
            }
            list($locations, $params) = fn_get_store_locations($params);

            $active_stores = array();
            if (!empty($shipping['service_params']['active_stores']) && is_array($shipping['service_params']['active_stores'])) {
                $_active_stores = $shipping['service_params']['active_stores'];

                foreach($_active_stores as $store_location_id) {
                    $active_stores[$store_location_id] = $locations[$store_location_id]['city'] . ' (' . $locations[$store_location_id]['name'] .')';
                }
            }

            if (!empty($locations)) {
                $stores = $all_stores = $select_stores = array();

                foreach ($locations as $location) {
                    $available_for_pickup = $location['main_destination_id'] !== null;
                    if ($available_for_pickup) {
                        $result = array_search($location['store_location_id'], $active_stores);
                        if ($result === false) {
                            $select_stores[$location['store_location_id']] = $location['city'] . ' (' . $location['name'] .')';
                        }
                        $all_stores[$location['store_location_id']] = $location['city'] . ' (' . $location['name'] .')';
                    }
                }

                asort($select_stores);
                asort($active_stores);

                Tygh::$app['view']->assign('all_stores', $all_stores);
                Tygh::$app['view']->assign('select_stores', $select_stores);
                Tygh::$app['view']->assign('active_stores', $active_stores);
            }
        }
    }
}
