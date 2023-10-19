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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($mode === 'update_location') {
        Tygh::$app['view']->assign('tilda_page_list', fn_tilda_pages_get_page_list_from_tilda());
    } elseif ($mode === 'manage_in_tab' || $mode === 'manage') {
        if ($mode === 'manage_in_tab') {
            $inner_resource_id = isset($_REQUEST['page_id']) ? $_REQUEST['page_id'] : 0;
            $inner_resource_name = 'inner_page_id';
            $tilde_db_table = 'tilda_pages';
        } else {
            $inner_resource_id = isset($_REQUEST['selected_location']) ? $_REQUEST['selected_location'] : 0;
            $inner_resource_name = 'inner_location_id';
            $tilde_db_table = 'tilda_locations';
        }

        $is_only_content = db_get_field(
            'SELECT is_only_content FROM ?:?p as tilda WHERE tilda.?p = ?i',
            $tilde_db_table,
            $inner_resource_name,
            $inner_resource_id
        );

        if (empty($is_only_content)) {
            return;
        }

        if (YesNo::toBool($is_only_content)) {
            Tygh::$app['view']->assign('is_container_override', true);
        } else {
            Tygh::$app['view']->assign('is_location_override', true);
        }
    }
}
