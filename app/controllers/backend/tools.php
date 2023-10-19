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
use Tygh\Snapshot;
use Tygh\Languages\Languages;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @var string $mode
 * @var array $auth
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'update_quick_menu_item') {
        $_data = $_REQUEST['item'];

        if (empty($_data['position'])) {
            $_data['position'] = db_get_field('SELECT max(position) FROM ?:quick_menu WHERE parent_id = ?i', $_data['parent_id']);
            $_data['position'] = $_data['position'] + 10;
        }

        $_data['user_id'] = $auth['user_id'];
        $_data['url'] = fn_qm_parse_url($_data['url']);

        if (empty($_data['id'])) {
            $id = db_query('INSERT INTO ?:quick_menu ?e', $_data);

            $_data = [
                'object_id'     => $id,
                'description'   => $_data['name'],
                'object_holder' => 'quick_menu'
            ];

            foreach (array_keys(Languages::getAll()) as $_data['lang_code']) {
                db_query('INSERT INTO ?:common_descriptions ?e', $_data);
            }
        } else {
            db_query('UPDATE ?:quick_menu SET ?u WHERE menu_id = ?i', $_data, $_data['id']);

            $__data = [
                'description' => $_data['name']
            ];
            db_query("UPDATE ?:common_descriptions SET ?u WHERE object_id = ?i AND object_holder = 'quick_menu' AND lang_code = ?s", $__data, $_data['id'], DESCR_SL);
        }

        return [CONTROLLER_STATUS_OK, 'tools.show_quick_menu.edit?no_popup=1'];
    }

    if ($mode === 'view_changes') {
        if (!empty($_REQUEST['compare_data']['db_name'])) {
            Snapshot::createDb();
            Snapshot::createDb($_REQUEST['compare_data']['db_name']);
        }

        return [CONTROLLER_STATUS_OK, 'tools.view_changes?db_ready=Y'];
    }

    if ($mode === 'update_status') {
        fn_tools_update_status($_REQUEST);

        if (empty($_REQUEST['redirect_url'])) {
            exit;
        }
    }

    return;
}

if ($mode === 'phpinfo') {
    phpinfo();
    exit;
}

if ($mode === 'show_quick_menu') {
    if (Registry::get('runtime.action') === 'edit') {
        Tygh::$app['view']->assign('edit_quick_menu', true);
    } else {
        Tygh::$app['view']->assign('expand_quick_menu', true);
    }

    if (empty($_REQUEST['no_popup'])) {
        Tygh::$app['view']->assign('show_quick_popup', true);
    }
    Tygh::$app['view']->display('common/quick_menu.tpl');
    exit;
}

if ($mode === 'get_quick_menu_variant') {
    if (!defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_REDIRECT, 'index.index'];
    }

    Tygh::$app['ajax']->assign('description', db_get_field("SELECT description FROM ?:common_descriptions WHERE object_id = ?i AND object_holder = 'quick_menu' AND lang_code = ?s", $_REQUEST['id'], DESCR_SL));
    exit;
}

if ($mode === 'remove_quick_menu_item') {
    $where = '';

    if ((int) $_REQUEST['parent_id'] === 0) {
        $where = db_quote(' OR parent_id = ?i', $_REQUEST['id']);
        $delete_ids = db_get_fields('SELECT menu_id FROM ?:quick_menu WHERE parent_id = ?i', $_REQUEST['id']);
        db_query("DELETE FROM ?:common_descriptions WHERE object_id IN (?n) AND object_holder = 'quick_menu'", $delete_ids);
    }

    db_query('DELETE FROM ?:quick_menu WHERE menu_id = ?i ?p', $_REQUEST['id'], $where);
    db_query("DELETE FROM ?:common_descriptions WHERE object_id = ?i AND object_holder = 'quick_menu'", $_REQUEST['id']);

    Tygh::$app['view']->assign('edit_quick_menu', true);
    Tygh::$app['view']->assign('quick_menu', fn_get_quick_menu_data());
    Tygh::$app['view']->display('common/quick_menu.tpl');
    exit;
}

if ($mode === 'cleanup_history') {
    Tygh::$app['session']['last_edited_items'] = [];

    fn_save_user_additional_data('L', '');

    Tygh::$app['view']->assign('last_edited_items', '');
    Tygh::$app['view']->display('common/last_viewed_items.tpl');
    exit;
}

if ($mode === 'store_mode') { // Open/close the store
    fn_set_store_mode($_REQUEST['state']);
    exit;
}

if ($mode === 'update_position') {
    if (db_has_table($_REQUEST['table'])) {
        $table_name = $_REQUEST['table'];
    } else {
        exit;
    }

    $table_fields = fn_get_table_fields($table_name);
    $id_name = $_REQUEST['id_name'];
    $ids = explode(',', $_REQUEST['ids']);
    $positions = explode(',', $_REQUEST['positions']);
    $fields = [$id_name, 'position'];

    if (empty($table_fields) || count(array_intersect($table_fields, $fields)) !== count($fields)) {
        exit;
    }

    foreach ($ids as $k => $id) {
        db_query("UPDATE ?:$table_name SET position = ?i WHERE ?w", $positions[$k], [$id_name => $id]);
    }

    fn_set_notification('N', __('notice'), __('positions_updated'));

    exit;
}

if ($mode === 'view_changes') {
    fn_delete_notification('core_files_have_been_modified');

    Tygh::$app['view']->assign(Snapshot::changes($_REQUEST));
} elseif ($mode === 'create_snapshot') {
    Snapshot::create([
        'theme_rel_backend' => fn_get_theme_path('[relative]', 'A'),
        'themes_frontend'   => fn_get_theme_path('[themes]', 'C'),
        'themes_repo'       => fn_get_theme_path('[repo]', 'C')
    ]);

    return [CONTROLLER_STATUS_OK, 'tools.view_changes'];
}

/**
 * Parse URL for quick menu
 *
 * @param string $url Raw URL
 *
 * @return string
 */
function fn_qm_parse_url($url)
{
    if (strpos($url, '?') !== false) {
        [, $query_string] = explode('?', $url);
        parse_str($query_string, $params);
        if (!empty($params['dispatch'])) {
            $dispatch = $params['dispatch'];
            unset($params['dispatch']);
            $url = $dispatch . '?' . http_build_query($params);
        }
    }

    return $url;
}
