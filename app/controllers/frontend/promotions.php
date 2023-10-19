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

if ($mode == 'list') {

    fn_add_breadcrumb(__('promotions'));

    $params = [
        'active'     => true,
        /*'zone' => 'catalog',*/
        'get_hidden' => false,
        'mode'       => 'list',
        'extend'     => ['get_images'],
        'sort_by' => 'priority',
        'sort_order' => 'asc',
    ];

    list($promotions, $search) = fn_get_promotions($params);

    Tygh::$app['view']->assign('promotions', $promotions);
    Tygh::$app['view']->assign('search', $search);
}
