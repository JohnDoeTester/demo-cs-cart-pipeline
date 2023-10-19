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

use Tygh\Addons\VendorRating\ServiceProvider;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'update') {
    if (!fn_get_runtime_company_id()) {
        $tabs = Registry::ifGet('navigation.tabs', []);

        $tabs['rating'] = [
            'title' => __('vendor_rating.rating'),
            'js'    => true,
        ];

        Registry::set('navigation.tabs', $tabs);

        $schema = ServiceProvider::getCriteriaSchema();
        if (!empty($schema['manual_vendor_rating'])) {
            /** @var \Tygh\SmartyEngine\Core $view */
            $view = Tygh::$app['view'];
            $view->assign('manual_rating_criterion', $schema['manual_vendor_rating']);
        }
    }
}

return [CONTROLLER_STATUS_OK];

