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

use Tygh\BackendCustomMenu;
use Tygh\BackendMenu;
use Tygh\Enum\BackendMenuLocations;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

/** @var array<string, int|string|array> $auth */
$auth = Tygh::$app['session']['auth'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $return_url = !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : 'index.index';

    if (defined('AJAX_REQUEST') && !empty($_REQUEST['block_data']['block_id'])) {
        $ajax_data = ['id_path' => $_REQUEST['block_data']['block_id']];
        Tygh::$app['ajax']->assign('data', $ajax_data);
    }

    if ($mode === 'update_block' && !empty($_REQUEST['block_data'])) {
        $data = $_REQUEST['block_data'];

        // Block id for custom menu is actually 'id_path'
        if (!empty($data['block_id'])) {
            $data['id_path'] = $data['block_id'];
            $id_path_array = explode('/', $data['id_path']);
            $data['block_id'] = end($id_path_array);
        }

        if (!empty($data['href'])) {
            $data['href'] = fn_url($data['href']);
        }

        $data['area'] = UserTypes::isVendor($auth['user_type']) ? SiteArea::VENDOR_PANEL : SiteArea::ADMIN_PANEL;

        $data['location'] = !empty($data['location']) ? $data['location'] : BackendMenuLocations::CENTRAL;

        if (empty($_REQUEST['block_data']['block_id']) && empty($_REQUEST['block_data']['position'])) {
            $last_position = 0;

            $menu_schema = fn_get_schema(
                'menu',
                BackendMenu::instance(
                    Registry::get('runtime.controller'),
                    Registry::get('runtime.mode')
                )->getSchemaName()
            );

            if (!empty($menu_schema[BackendMenuLocations::CENTRAL])) {
                $last_main_menu_position = max(array_column($menu_schema[BackendMenuLocations::CENTRAL], 'position'));
            }
            unset($menu_schema);

            $last_position = db_get_field('SELECT max(position) FROM ?:custom_menu');

            if (!empty($last_main_menu_position)) {
                $last_position = max($last_main_menu_position, $last_position);
            }

            $data['position'] = !empty($last_position) ? (int) $last_position + 10 : 0;
        }

        BackendCustomMenu::updateCustomMenu($data);
    }

    if ($mode === 'delete_block' && !empty($_REQUEST['block_data']['block_id'])) {
        $items_deleted = BackendCustomMenu::deleteCustomMenu($_REQUEST['block_data']['block_id']);

        if ($items_deleted) {
            fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('text_changes_saved'));
        }
    }

    return [CONTROLLER_STATUS_OK, $return_url];
}

if ($mode === 'update_position') {
    $id_paths = explode(',', $_REQUEST['ids']);
    $positions = array_map(
        static function ($pos) {
            return ((int) $pos + 1) * 100;
        },
        explode(',', $_REQUEST['positions'])
    );

    foreach ($id_paths as $k => $id_path) {
        $id_path_array = explode('/', $id_path);
        $item = [
            'item_id' => end($id_path_array),
            'id_path' => $id_path
        ];

        BackendCustomMenu::addCustomMenuItemParents($item['item_id'], $item['id_path']);

        $custom_item_exists = db_get_field('SELECT COUNT(*) FROM ?:custom_menu WHERE id_path = ?s', $id_path);

        if (!$custom_item_exists) {
            if (count($id_path_array) < 2) {
                $item['parent_id'] = 0;
            } else {
                array_pop($id_path_array);
                $item['parent_id'] = end($id_path_array);
            }
            db_query('INSERT INTO ?:custom_menu ?e', $item);
        }

        db_query('UPDATE ?:custom_menu SET position = ?i WHERE ?w', $positions[$k], ['id_path' => $id_path]);
    }

    fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('positions_updated'));

    exit;
}
