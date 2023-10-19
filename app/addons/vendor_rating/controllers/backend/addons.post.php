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
use Tygh\Enum\YesNo;
use Tygh\Settings;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'update' && $_REQUEST['addon'] === 'vendor_rating') {
        $formula = $_REQUEST['addon_settings']['formula'];
        $formula = trim($formula);
        Settings::instance()->updateValue('formula', $formula, 'vendor_rating');

        $start_rating_period = $_REQUEST['addon_settings']['start_rating_period'];
        if ($start_rating_period) {
            $start_rating_period = fn_parse_date($start_rating_period);
        }
        Settings::instance()->updateValue('start_rating_period', (int) $start_rating_period, 'vendor_rating');

        $rating_above_price = isset($_REQUEST['addon_settings']['rating_above_price']) ? $_REQUEST['addon_settings']['rating_above_price'] : YesNo::NO;
        Settings::instance()->updateValue('rating_above_price', $rating_above_price, 'vendor_rating');

        foreach (['bronze', 'silver', 'gold'] as $range) {
            $limit_value = $_REQUEST['addon_settings']["{$range}_rating_lower_limit"];
            Settings::instance()->updateValue("{$range}_rating_lower_limit", (int) $limit_value, 'vendor_rating');
        }
    }

    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'update' && $_REQUEST['addon'] === 'vendor_rating') {
    $criteria = ServiceProvider::getCriteriaSchema();
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $view->assign('criteria', $criteria);
}
