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
use Tygh\Languages\Languages;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/** Body **/

if (empty($_REQUEST['country_code'])) {
    $_REQUEST['country_code'] = Registry::get('settings.Checkout.default_country');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    //
    // Create/update state
    //
    //
    if ($mode == 'update') {
        fn_update_state($_REQUEST['state_data'], $_REQUEST['state_id'], DESCR_SL);
    }

    // Updating existing states
    //
    if ($mode == 'm_update') {
        foreach ($_REQUEST['states'] as $key => $_data) {
            if (!empty($_data)) {
                fn_update_state($_data, $key, DESCR_SL);
            }
        }
    }

    //
    // Delete selected states
    //
    if ($mode == 'm_delete') {

        if (!empty($_REQUEST['state_ids'])) {
            foreach ($_REQUEST['state_ids'] as $v) {
                db_query("DELETE FROM ?:states WHERE state_id = ?i", $v);
                db_query("DELETE FROM ?:state_descriptions WHERE state_id = ?i", $v);
            }
        }
    }

    if ($mode == 'delete') {

        if (!empty($_REQUEST['state_id'])) {
            db_query("DELETE FROM ?:states WHERE state_id = ?i", $_REQUEST['state_id']);
            db_query("DELETE FROM ?:state_descriptions WHERE state_id = ?i", $_REQUEST['state_id']);
        }
    }

    if ($mode === 'update_status') {
        if (!empty($_REQUEST['state_ids'])) {
            foreach ($_REQUEST['state_ids'] as $state_id) {
                fn_tools_update_status([
                    'table'             => 'states',
                    'status'            => $_REQUEST['status'],
                    'id_name'           => 'state_id',
                    'id'                => $state_id,
                    'show_error_notice' => false
                ]);
            }
        }

        return [CONTROLLER_STATUS_OK, $_REQUEST['redirect_url']];
    }

    return array(CONTROLLER_STATUS_OK, 'states.manage?country_code=' . $_REQUEST['country_code']);
}

if ($mode == 'manage') {

    $params = $_REQUEST;
    if (empty($params['country_code'])) {
        $params['country_code'] = Registry::get('settings.Checkout.default_country');
    }

    list($states, $search) = fn_get_states($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);

    Tygh::$app['view']->assign('states', $states);
    Tygh::$app['view']->assign('search', $search);

    Tygh::$app['view']->assign('countries', fn_get_simple_countries(false, DESCR_SL));

}

function fn_update_state($state_data, $state_id = 0, $lang_code = DESCR_SL)
{
    if (empty($state_id)) {
        if (!empty($state_data['code']) && !empty($state_data['state'])) {
            $state_data['state_id'] = $state_id = db_query("REPLACE INTO ?:states ?e", $state_data);

            foreach (Languages::getAll() as $state_data['lang_code'] => $_v) {
                db_query('REPLACE INTO ?:state_descriptions ?e', $state_data);
            }
        }
    } else {
        db_query("UPDATE ?:state_descriptions SET ?u WHERE state_id = ?i AND lang_code = ?s", $state_data, $state_id, $lang_code);
    }

    return $state_id;

}

/** /Body **/
