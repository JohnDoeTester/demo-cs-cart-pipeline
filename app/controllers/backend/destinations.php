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

$_REQUEST['destination_id'] = empty($_REQUEST['destination_id']) ? 0 : $_REQUEST['destination_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $suffix = '';

    if ($mode === 'update') {
        $destination_id = fn_update_destination($_REQUEST['destination_data'], $_REQUEST['destination_id'], DESCR_SL);
        $suffix = ".update?destination_id=$destination_id";
    }

    if ($mode === 'm_delete') {

        if (!empty($_REQUEST['destination_ids'])) {
            fn_delete_destinations($_REQUEST['destination_ids']);
        }

        $suffix = ".manage";
    }

    if ($mode === 'delete') {

        if (!empty($_REQUEST['destination_id'])) {
            fn_delete_destinations((array) $_REQUEST['destination_id']);
        }

        $suffix = ".manage";
    }

    if ($mode === 'update_status') {
        if (!empty($_REQUEST['destination_ids'])) {
            foreach ($_REQUEST['destination_ids'] as $destination_id) {
                fn_tools_update_status([
                    'table'             => 'destinations',
                    'status'            => $_REQUEST['status'],
                    'id_name'           => 'destination_id',
                    'id'                => $destination_id,
                    'show_error_notice' => false
                ]);
            }
        }

        return [CONTROLLER_STATUS_OK, $_REQUEST['redirect_url']];
    }

    return array(CONTROLLER_STATUS_OK, 'destinations' . $suffix);
}

if ($mode === 'update') {

    $destination = db_get_row("SELECT a.destination_id, a.status, destination, a.localization FROM ?:destinations as a LEFT JOIN ?:destination_descriptions as b ON b.destination_id = a.destination_id AND b.lang_code = ?s WHERE a.destination_id = ?i", DESCR_SL, $_REQUEST['destination_id']);

    if (empty($destination)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $destination_data = array();

    $destination_data['states'] = db_get_hash_single_array("SELECT a.state_id, CONCAT(d.country, ': ', b.state) as state FROM ?:states as a LEFT JOIN ?:state_descriptions as b ON b.state_id = a.state_id AND b.lang_code = ?s LEFT JOIN ?:destination_elements as c ON c.element_type = 'S' AND c.element = a.state_id LEFT JOIN ?:country_descriptions as d ON d.code = a.country_code AND d.lang_code = ?s WHERE c.destination_id = ?i", array('state_id', 'state'), DESCR_SL, DESCR_SL, $_REQUEST['destination_id']);

    $destination_data['countries'] = db_get_hash_single_array("SELECT a.code, b.country FROM ?:countries as a LEFT JOIN ?:country_descriptions as b ON b.code = a.code AND b.lang_code = ?s LEFT JOIN ?:destination_elements as c ON c.element_type = 'C' AND c.element = a.code WHERE c.destination_id = ?i", array('code', 'country'), DESCR_SL, $_REQUEST['destination_id']);

    $destination_data['zipcodes'] = db_get_hash_single_array("SELECT element_id, element FROM ?:destination_elements WHERE element_type = 'Z' AND destination_id = ?i", array('element_id', 'element'), $_REQUEST['destination_id']);
    $destination_data['zipcodes'] = implode("\n", $destination_data['zipcodes']);

    $destination_data['cities'] = db_get_hash_single_array("SELECT element_id, element FROM ?:destination_elements WHERE element_type = 'T' AND destination_id = ?i", array('element_id', 'element'), $_REQUEST['destination_id']);
    $destination_data['cities'] = implode("\n", $destination_data['cities']);

    $destination_data['addresses'] = db_get_hash_single_array("SELECT element_id, element FROM ?:destination_elements WHERE element_type = 'A' AND destination_id = ?i", array('element_id', 'element'), $_REQUEST['destination_id']);
    $destination_data['addresses'] = implode("\n", $destination_data['addresses']);

    $all_countries = fn_get_simple_countries(true, DESCR_SL);
    $all_countries = array_diff_assoc($all_countries, $destination_data['countries']);

    $all_states = fn_destination_get_states(DESCR_SL, ['only_avail' => true]);
    $all_states = array_diff_assoc($all_states, $destination_data['states']);

    Tygh::$app['view']->assign('destination_data', $destination_data);
    Tygh::$app['view']->assign('destination', $destination);

    Tygh::$app['view']->assign('states', $all_states);
    Tygh::$app['view']->assign('countries', $all_countries);

    $tabs = Registry::ifGet('navigation.tabs', []);
    $tabs['detailed'] = [
        'title' => __('general'),
        'js'    => true,
    ];
    Registry::set('navigation.tabs', $tabs);

} elseif ($mode === 'add') {

    Tygh::$app['view']->assign('states', fn_destination_get_states(DESCR_SL, ['only_avail' => true]));
    Tygh::$app['view']->assign('countries', fn_get_simple_countries(true, DESCR_SL));

    Registry::set('navigation.tabs', [
        'detailed' => [
            'title' => __('general'),
            'js' => true
        ]
    ]);
} elseif ($mode === 'manage') {

    $destinations = fn_get_destinations(DESCR_SL);
    Tygh::$app['view']->assign('destinations', $destinations);  
} elseif ($mode === 'selector') {
    list($objects, $total_objects) = fn_get_destinations_for_picker($_REQUEST);

    /** @var \Tygh\Ajax $ajax */
    $ajax = Tygh::$app['ajax'];
    $ajax->assign('objects', $objects);
    $ajax->assign('total_objects', $total_objects);
    return [CONTROLLER_STATUS_NO_CONTENT];
}

if (in_array($mode, ['add', 'update', 'manage'])) {
    $dynamic_sections = Registry::ifGet('navigation.dynamic.sections', []);
    $dynamic_sections['shippings'] = [
        'title' => __('shipping_methods'),
        'href'  => 'shippings.manage',
    ];
    $dynamic_sections['destinations'] = [
        'title' => __('rate_areas'),
        'href'  => 'destinations.manage',
    ];

    Registry::set('navigation.dynamic.active_section', 'destinations');
    Registry::set('navigation.dynamic.sections', $dynamic_sections);
}
