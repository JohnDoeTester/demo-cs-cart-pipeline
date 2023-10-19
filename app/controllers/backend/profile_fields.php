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
use Tygh\Enum\ProfileTypes;
use Tygh\Tools\Url;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// ----------
// fields types:
// I - input
// T - textarea
// C - checkbox
// S - selectbox
// R - radiobutton
// H - header
// D - data
// P - phone
// --
// A - states
// O - country
// M - usergroup
// W - password
// N - address_type

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $dispatch = [
        'controller' => 'profile_fields',
        'mode'       => 'manage',
    ];

    $query_string = [];
    if (!empty($_REQUEST['profile_type']) || !empty($_REQUEST['field_data']['profile_type'])) {
        $query_string['profile_type'] = !empty($_REQUEST['profile_type'])
            ? $_REQUEST['profile_type']
            : $_REQUEST['field_data']['profile_type'];
    }

    if ($mode == 'update') {

        $field_data = $_REQUEST['field_data'];
        fn_save_post_data('field_data');
        $field_id = fn_update_profile_field($field_data, $_REQUEST['field_id'], DESCR_SL);

        if ($field_id) {
            fn_restore_post_data('field_data');
            $field_id_redirect_to = $field_id;

            if (isset($field_data['section']) && $field_data['section'] == 'BS') {
                $field_id_redirect_to = fn_get_matching_profile_field_id($field_id);
            }

            $dispatch['mode'] = 'update';
            $query_string['field_id'] = $field_id_redirect_to;
        } elseif (!empty($_REQUEST['field_id'])) {
            $dispatch['mode'] = 'update';
            $query_string['field_id'] = $_REQUEST['field_id'];
        } else {
            $dispatch['mode'] = 'add';
        }
    }

    if ($mode == 'm_update') {
        if (!empty($_REQUEST['fields_data'])) {
            $fields_data = $_REQUEST['fields_data'];
            if (isset($fields_data['email'])) {
                foreach ($fields_data['email'] as $enable_for => $field_id) {
                    $fields_data[$field_id][$enable_for] = 'Y';
                }

                unset($fields_data['email']);
            }

            foreach ($fields_data as $field_id => $data) {
                fn_update_profile_field($data, $field_id, DESCR_SL);
            }
        }

        if (!empty($_REQUEST['profile_field_sections'])) {
            fn_update_profile_field_sections($_REQUEST['profile_field_sections']);
        }
    }

    if ($mode == 'm_delete') {
        if (!empty($_REQUEST['field_ids'])) {
            foreach ($_REQUEST['field_ids'] as $field_id) {
                fn_delete_profile_field($field_id);
            }
        }

        if (!empty($_REQUEST['value_ids'])) {
            foreach ($_REQUEST['value_ids'] as $value_id) {
                db_query("DELETE FROM ?:profile_field_descriptions WHERE object_id = ?i AND object_type = 'V'", $value_id);
                db_query("DELETE FROM ?:profile_field_values WHERE value_id = ?i", $value_id);
            }
        }
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['field_id'])) {
            fn_delete_profile_field($_REQUEST['field_id']);
        }
    }

    return array(CONTROLLER_STATUS_OK, Url::buildUrn($dispatch, $query_string));
}

if ($mode == 'manage') {

    $profile_types = (array) fn_get_schema('profiles', 'profile_types');
    $profile_type = isset($_REQUEST['profile_type']) ? $_REQUEST['profile_type'] : key($profile_types);
    $params = array(
        'profile_type' => ProfileTypes::CODE_USER,
    );

    if (count($profile_types) > 1) {
        $sections = array();

        if ($profile_type && !isset($profile_types[$profile_type])) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        foreach ($profile_types as $code => $type) {
            $sections[$code] = array(
                'title' => __("profile_types_section_{$type['name']}"),
                'href'  => fn_url("profile_fields.manage?profile_type={$code}"),
            );
        }

        $params = array(
            'profile_type' => $profile_type,
        );

        Registry::set('navigation.dynamic.sections', $sections);
        Registry::set('navigation.dynamic.active_section', $profile_type);
    }

    $profile_fields = fn_get_profile_fields('ALL', array(), DESCR_SL, $params);

    $profile_fields_sections = fn_get_profile_fields_sections();
    $profile_fields = fn_sort_profile_fields_by_section_position($profile_fields, $profile_fields_sections);

    $profile_fields_areas = fn_profile_fields_areas();

    Tygh::$app['view']->assign([
        'profile_type'            => $profile_type,
        'profile_types'           => $profile_types,
        'profile_fields'          => $profile_fields,
        'profile_fields_areas'    => $profile_fields_areas,
        'profile_fields_sections' => $profile_fields_sections,
    ]);
} elseif ($mode == 'update' || $mode == 'add') {
    $field = array();
    $field_name = $profile_type = '';
    $profile_types = fn_get_schema('profiles', 'profile_types');

    if ($mode == 'update') {
        $params['field_id'] = $_REQUEST['field_id'];
        $field = fn_get_profile_fields('ALL', array(), DESCR_SL, $params);
        $profile_type = isset($field['profile_type']) ? $field['profile_type'] : '';

        if (isset($field['field_name'])) {
            $field_name = $field['field_name'];
        }
    } else {
        $profile_type = isset($_REQUEST['profile_type']) ? $_REQUEST['profile_type'] : key($profile_types);
    }

    if ($profile_type && !isset($profile_types[$profile_type])) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $post_data = fn_restore_post_data('field_data');

    if (!empty($post_data)) {
        $field = array_merge($field, $post_data);
    }

    Tygh::$app['view']->assign(array(
        'field'                 => $field,
        'field_name'            => $field_name,
        'profile_fields_areas'  => fn_profile_fields_areas(),
        'profile_types'         => $profile_types,
        'profile_type'          => $profile_type,
    ));
} elseif ($mode == 'picker') {
    $section = !empty($_REQUEST['section']) ? $_REQUEST['section'] : null;
    $params = [
        'section'       => $section,
        'exclude_names' => !empty($_REQUEST['exclude_names']) ? explode(',', $_REQUEST['exclude_names']) : [],
        'include_names' => !empty($_REQUEST['include_names']) ? explode(',', $_REQUEST['include_names']) : [],
        'exclude_types' => !empty($_REQUEST['exclude_types']) ? explode(',', $_REQUEST['exclude_types']) : [],
    ];

    $section_profile_fields = fn_get_profile_fields('all', [], DESCR_SL, $params);
    $profile_fields = isset($section_profile_fields[$section]) ? $section_profile_fields[$section] : [];

    Tygh::$app['view']->assign('profile_fields', $profile_fields);
    Tygh::$app['view']->display('pickers/profile_fields/picker_contents.tpl');
    exit;
}
