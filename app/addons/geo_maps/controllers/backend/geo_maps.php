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

defined('BOOTSTRAP') or die('Access denied');

/** @var string $mode */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if (
    $mode === 'map'
    && !empty($_REQUEST['provider'])
    && isset($_REQUEST['api_key'])
) {
    $view = Tygh::$app['view'];

    $view->assign([
        'provider'          => $_REQUEST['provider'],
        'api_key'           => $_REQUEST['api_key'],
        'yandex_commercial' => isset($_REQUEST['yandex_commercial']) ? $_REQUEST['yandex_commercial'] : YesNo::NO,
    ]);

    echo $view->fetch('addons/geo_maps/components/map.tpl');

    return [CONTROLLER_STATUS_NO_CONTENT];
}
