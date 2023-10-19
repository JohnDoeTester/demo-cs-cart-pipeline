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

use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode === 'export_datafeed') {
    if (!empty($_REQUEST['datafeed_ids'])) {
        if (is_array($_REQUEST['datafeed_ids'])) {
            foreach ($_REQUEST['datafeed_ids'] as $datafeed_id) {
                $params = [];

                if (!empty($_REQUEST['location'])) {
                    $params['location'] = $_REQUEST['location'];
                }

                if (fn_allowed_for('MULTIVENDOR') && !empty($_REQUEST['s_storefront'])) {
                    $params['company_ids'] = StorefrontProvider::getStorefront()->getCompanyIds();
                }

                if (fn_data_feeds_export($datafeed_id, $params)) {
                    $location = empty($_REQUEST['location'])
                        ? db_get_field('SELECT export_location FROM ?:data_feeds WHERE datafeed_id = ?i', $datafeed_id)
                        : $_REQUEST['location'];

                    if ($location === 'L') {
                        $meta_url = urlencode(fn_url('data_feeds.download?datafeed_id=' . $datafeed_id, SiteArea::ADMIN_PANEL, 'rel'));
                        $_suffix = '&meta_redirect_url=' . $meta_url;

                        $url = (empty($_SERVER['HTTP_REFERER'])
                                ? fn_url('data_feeds.manage')
                                : fn_query_remove($_SERVER['HTTP_REFERER'], 'meta_redirect_url')) . $_suffix;
                        Tygh::$app['ajax']->assign('force_redirection', $url);
                    }
                }
            }
        }
    }

    exit;

} elseif ($mode === 'cron_export') {
    $cron_password = Registry::get('addons.data_feeds.cron_password');

    if (
        (
            !isset($_REQUEST['cron_password'])
            || $cron_password !== $_REQUEST['cron_password']
        )
        && (!empty($cron_password))
    ) {
        die(__('access_denied'));
    }

    $params = [
        'status' => ObjectStatuses::ACTIVE,
        'cron'   => YesNo::YES,
    ];

    $datafeeds = fn_data_feeds_get_data($params);

    if (!empty($datafeeds)) {
        $params = [];

        if (fn_allowed_for('MULTIVENDOR') && !empty($_REQUEST['s_storefront'])) {
            $params['company_ids'] = StorefrontProvider::getStorefront()->getCompanyIds();
        }

        foreach ($datafeeds as $datafeed) {
            fn_data_feeds_export($datafeed['datafeed_id'], $params);
        }
    }

    exit();
}
