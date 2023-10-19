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
use Tygh\Common\Robots;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'view') {
    $robots = new Robots;

    $storefront = Tygh::$app['storefront'];

    $content = $robots->getRobotsTxtContent();

    if (!isset($content)) {
        $robots_data = $robots->getRobotsDataByStorefrontId($storefront->storefront_id);
        $content = isset($robots_data['data']) ? $robots_data['data'] : '';
    }

    header('Content-type: text/plain; charset=utf-8');
    echo($content);
    exit;
}
