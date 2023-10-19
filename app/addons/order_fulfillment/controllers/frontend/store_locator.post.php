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

use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'search' && !empty($_REQUEST['sl_search'])) {
    $view = Tygh::$app['view'];
    $group_locations = $view->getTemplateVars('store_locations');
    /** @psalm-suppress InvalidArrayOffset */
    if (isset($group_locations['0'])) {
        $marketplace_name = __('marketplace');
        $group_locations[$marketplace_name] = $group_locations['0'];
        unset($group_locations['0']);
        $view->assign('store_locations', $group_locations);
    }
}
