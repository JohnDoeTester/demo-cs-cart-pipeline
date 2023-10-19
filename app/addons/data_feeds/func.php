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

use Tygh\Enum\ProductFeatures;
use Tygh\Registry;
use Tygh\Enum\NotificationSeverity;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_data_feeds_install()
{
    $layout_data = db_get_row("SELECT cols, options, active FROM ?:exim_layouts WHERE name = 'general' AND pattern_id = 'products'");
    $layout_data['name'] = 'general_data_feeds';
    $layout_data['pattern_id'] = 'data_feeds';

    if (!empty($layout_data)) {
        db_query("INSERT INTO ?:exim_layouts ?e", $layout_data);
    }
}

function fn_data_feeds_uninstall()
{
    $layouts_data = db_get_array("SELECT layout_id FROM ?:exim_layouts WHERE pattern_id = 'data_feeds'");

    if (!empty($layouts_data)) {
        foreach ($layouts_data as $layout_data) {
            db_query("DELETE FROM ?:exim_layouts WHERE layout_id = ?i", $layout_data['layout_id']);
        }
    }
}

function fn_get_data_feeds_company_condition($field)
{
    if (fn_allowed_for('ULTIMATE')) {
        return fn_get_company_condition($field);
    }

    return '';
}

function fn_data_feeds_get_data($params = array(), $lang_code = CART_LANGUAGE)
{
    $condition = fn_get_data_feeds_company_condition('feed.company_id');

    if (!empty($params['datafeed_id'])) {
        $condition .= db_quote(' AND feed.datafeed_id = ?i', $params['datafeed_id']);
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(' AND feed.status = ?s', $params['status']);
    }

    if (!empty($params['cron'])) {
        $condition .= db_quote(' AND (feed.export_location = ?s OR feed.export_location = ?s)', 'S', 'F');
    }

    $feeds = db_get_array(
        'SELECT feed.*, descr.datafeed_name FROM ?:data_feeds AS feed '
        . 'LEFT JOIN ?:data_feed_descriptions AS descr ON (feed.datafeed_id = descr.datafeed_id) '
        . 'WHERE descr.lang_code = ?s ?p',
        $lang_code, $condition
    );

    $features_fields = fn_data_feeds_get_features_fields();

    if (!empty($feeds)) {
        foreach ($feeds as &$feed) {
            $feed['fields'] = unserialize($feed['fields']);
            $feed['export_options'] = unserialize($feed['export_options']);
            $feed['params'] = unserialize($feed['params']);

            if (!empty($params['available_fields'])) {
                foreach ($feed['fields'] as $field_id => $field) {
                    if (!empty($features_fields[$field['field']])) {
                        $feed['fields'][$field_id]['field_id'] = $field['field'];
                        $feed['fields'][$field_id]['field'] = $features_fields[$field['field']]['description'];
                    }

                    if (isset($field['avail'])) {
                        if ($field['avail'] != $params['available_fields']) {
                            unset($feed['fields'][$field_id]);
                        }
                    } else {
                        unset($feed['fields'][$field_id]);
                    }
                }
            }
        }
    }

    if (!empty($params['single'])) {
        if ($params['single']) {
            return array_pop($feeds);
        }
    }

    return $feeds;
}

function fn_data_feeds_export($datafeed_id, $options = array(), $pattern = '')
{
    $name_layout = '';

    if (empty($pattern)) {
        $layout_id = db_get_field("SELECT layout_id FROM ?:data_feeds WHERE datafeed_id = ?i", $datafeed_id);
        $name_layout = db_get_field("SELECT name FROM ?:exim_layouts WHERE layout_id = ?i", $layout_id);

        $pattern = fn_data_feeds_get_pattern_definition('exim', 'products');
        if (!empty($name_layout)) {
            $pattern = fn_data_feeds_get_pattern_definition('exim_data_feeds', $name_layout);
        }
    }

    $params['datafeed_id'] = $datafeed_id;
    $params['single'] = true;
    $params['available_fields'] = 'Y';
    $params = array_merge($params, $options);

    $datafeed_data = fn_data_feeds_get_data($params, DESCR_SL);

    $file_extension = fn_get_file_ext($datafeed_data['file_name']);

    if (!fn_is_file_extension_allowed($file_extension)) {
        fn_set_notification(
            NotificationSeverity::ERROR,
            __('error'),
            __('text_forbidden_file_extension', ['[ext]' => $file_extension])
        );
        return false;
    }

    if (empty($pattern) || empty($params['datafeed_id'])) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('data_feed.error_exim_no_data_exported'));

        return false;
    }

    if ($datafeed_data['exclude_disabled_products'] == 'Y') {
        $params['status'] = 'A';
    }

    if (!empty($datafeed_data['company_id'])) {
        $params['company_ids'] = $datafeed_data['company_id'];
    }

    if (empty($datafeed_data['products']) && empty($datafeed_data['categories'])) {
        $params['cid'] = 0;
        $params['subcats'] = 'Y';
        $params['skip_view'] = 'Y';
        $params['extend'] = array('categories');

    } else {
        if (!empty($datafeed_data['products'])) {
            $params['pid'] = explode(',', $datafeed_data['products']);
        }

        if (!empty($datafeed_data['categories'])) {
            $params['cid'] = explode(',', $datafeed_data['categories']);
            $params['subcats'] = 'Y';
            $params['skip_view'] = 'Y';
            $params['extend'] = array('categories');
        }
    }

    /**
     * Executed before get products.
     * Allows you to affect parameters of exported products.
     *
     * @param array $datafeed_data  Data feed info
     * @param array $pattern        Data of export pattern
     * @param array $params         List of products params
     */
    fn_set_hook('data_feeds_export_before_get_products', $datafeed_data, $pattern, $params);

    list($products, $search) = fn_get_products($params);
    $pids = array_column($products, 'product_id');

    if (empty($pids)) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('data_feed.error_exim_no_data_exported'));

        return false;
    }

    $pattern['condition']['conditions']['product_id'] = $pids;

    $fields = array();

    if (!empty($datafeed_data['fields'])) {
        foreach ($datafeed_data['fields'] as $field) {
            if (!empty($field['field_id'])) {
                $fields[$field['field_id']] = $field['export_field_name'];
            } else {
                $fields[$field['field']] = $field['export_field_name'];
            }
        }
    }

    $features = db_get_array(
        'SELECT feature_id, description FROM ?:product_features_descriptions WHERE lang_code = ?s',
        DESCR_SL
    );

    $features_fields = array();

    if (!empty($features)) {
        foreach ($features as $feature) {
            $pattern['export_fields'][$feature['feature_id']] = array(
                'process_get' => array ('fn_data_feeds_get_product_features', '#key', '#field', '#lang_code', '$lang_code', $feature['feature_id']),
                'linked' => false,
                'multilang' => true
            );
        }
    }

    $options = $datafeed_data['export_options'];
    $options['delimiter'] = $datafeed_data['csv_delimiter'];
    $options['filename'] = $datafeed_data['file_name'];
    $options['fields_names'] = true;
    $options['force_header'] = true;
    $options['export_type'] = $name_layout;
    $pattern['enclosure'] = !empty($datafeed_data['enclosure']) ? $datafeed_data['enclosure'] : '';

    /**
     * Executed before products are exported to a data feed.
     * Allows you to affect the export.
     *
     * @param int   $datafeed_id    Data feed identifier
     * @param array $options        List of export options
     * @param array $pattern        Data of export pattern
     * @param array $fields         List of export fields
     * @param array $datafeed_data  Data feed info
     */
    fn_set_hook('data_feeds_export', $datafeed_id, $options, $pattern, $fields, $datafeed_data);

    if (fn_allowed_for('ULTIMATE')) {
        $revert_company_id = 0;
        if (!empty($datafeed_data['company_id'])) {
            $options['company_id'] = $datafeed_data['company_id'];

            $revert_company_id = Registry::get('runtime.company_id');
            Registry::set('runtime.company_id', $datafeed_data['company_id']);

            if ($datafeed_data['exclude_shared_products'] == 'N') {
                $pattern['condition']['use_company_condition'] = false;

            } elseif (Registry::get('runtime.company_id') == 0) {
                $pattern['condition']['conditions']['company_id'] = $datafeed_data['company_id'];
            }

            if (isset($pattern['references']['seo_names'])) {
                $pattern['references']['seo_names']['reference_fields']['company_id'] = $datafeed_data['company_id'];
            }
        }
    }

    $errors = false;
    if (!empty($fields)) {
        if (fn_export($pattern, $fields, $options)) {

            $export_location = empty($params['location']) ? $datafeed_data['export_location'] : $params['location'];

            if ($export_location == 'S') {
                $datafeed_data['save_dir'] = fn_get_files_dir_path() . $datafeed_data['save_dir'];

                $is_valid_path = fn_is_valid_path(fn_get_files_dir_path(), $datafeed_data['save_dir']);
                if ($is_valid_path && !is_dir($datafeed_data['save_dir'])) {
                    fn_mkdir($datafeed_data['save_dir']);
                }

                if ($is_valid_path && file_exists(fn_get_files_dir_path() . $datafeed_data['file_name']) && is_dir($datafeed_data['save_dir'])) {
                    fn_rename(fn_get_files_dir_path() . $datafeed_data['file_name'], $datafeed_data['save_dir'] . '/' . $datafeed_data['file_name']);
                } else {
                    $errors = true;

                    fn_set_notification(NotificationSeverity::ERROR, __('error'), __('check_server_export_settings'));
                }

            } elseif ($export_location == 'F') {
                if (empty($datafeed_data['ftp_url'])) {
                    $errors = true;

                    fn_set_notification(NotificationSeverity::ERROR, __('error'), __('ftp_connection_problem'));

                } else {
                    preg_match("/[^\/^\\^:]+/", $datafeed_data['ftp_url'], $matches);
                    $host = $matches[0];

                    preg_match("/.*:([0-9]+)/", $datafeed_data['ftp_url'], $matches);
                    $port = empty($matches[1]) ? 21 : $matches[1];

                    preg_match("/[^\/]+(.*)/", $datafeed_data['ftp_url'], $matches);
                    $url = empty($matches[1]) ? '' : $matches[1];

                    $conn_id = @ftp_connect($host, $port);
                    $result = @ftp_login($conn_id, $datafeed_data['ftp_user'], $datafeed_data['ftp_pass']);
                    if (!empty($url)) {
                        @ftp_chdir($conn_id, $url);
                    }

                    $filename = fn_get_files_dir_path() . $datafeed_data['file_name'];

                    if ($result) {
                        if (@ftp_put($conn_id, $datafeed_data['file_name'], $filename, FTP_ASCII)) {
                            unlink($filename);
                        } else {
                            $errors = true;

                            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('ftp_connection_problem'));
                        }
                    } else {
                        $errors = true;

                        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('ftp_connection_problem'));
                    }

                    @ftp_close($conn_id);
                }
            }

            if (!$errors) {
                fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('text_exim_data_exported'));

            } else {
                unlink(fn_get_files_dir_path() . $datafeed_data['file_name']);
                $errors = true;
            }

        } else {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('data_feed.error_exim_no_data_exported'));
            $errors = true;
        }

    } else {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('error_exim_fields_not_selected'));
        $errors = true;
    }

    if (fn_allowed_for('ULTIMATE')) {
        if (!empty($datafeed_data['company_id'])) {
            Registry::set('runtime.company_id', $revert_company_id);
        }
    }

    return !$errors;
}

function fn_data_feeds_get_product_features($product_id, $field, $lang_code, $e_lang_code, $v_feature_id)
{
    $feature_description = db_get_field(
        'SELECT feature_descr.description'
        . ' FROM ?:product_features_descriptions AS feature_descr'
        . ' WHERE feature_descr.feature_id = ?i'
        . ' AND lang_code = ?s',
        $field,
        $lang_code
    );

    if (!empty($feature_description)) {
        $feature_id = $field;
        $field = $feature_description;

    } else {
        $feature_id = db_get_field(
            'SELECT feature_descr.feature_id'
            . ' FROM ?:product_features_descriptions AS feature_descr'
            . ' WHERE feature_descr.description = ?s'
                . ' AND lang_code = ?s',
            $field,
            $lang_code
        );

        if (empty($feature_id)) {
            $lang_code = DESCR_SL;
            $feature_id = db_get_field(
                'SELECT feature_descr.feature_id'
                . ' FROM ?:product_features_descriptions AS feature_descr'
                . ' WHERE feature_descr.description = ?s'
                    . ' AND lang_code = ?s',
                $field,
                $lang_code
            );
        }
    }

    $feature_id = (!empty($v_feature_id)) ? $v_feature_id : $feature_id;

    $lang_code = (!empty($e_lang_code)) ? $e_lang_code : $lang_code;

    $result = false;
    if (!empty($feature_id)) {
        $feature_values = db_get_array(
            'SELECT var_descr.variant, feature_val.value, feature_val.value_int'
            . ' FROM ?:product_feature_variant_descriptions AS var_descr'
            . ' RIGHT JOIN ?:product_features_values AS feature_val'
                . ' ON (feature_val.variant_id = var_descr.variant_id'
                    . ' AND feature_val.lang_code = var_descr.lang_code)'
            . ' WHERE feature_val.feature_id = ?i'
                . ' AND feature_val.product_id = ?i'
                . ' AND feature_val.lang_code = ?s'
            . ' GROUP BY feature_val.variant_id',
            $feature_id,
            $product_id,
            $lang_code
        );

        $variants = array();

        foreach ($feature_values as $value) {
            if ($value['variant']) {
                $variants[] = $value['variant'];
            } elseif ($value['value']) {
                $variants[] = $value['value'];
            } else {
                $variants[] = ($value['value_int'] ? floatval($value['value_int']) : 0);
            }
        }

        if ($field == 'GTIN' && empty($variants)) {
            $variants[] = db_get_field(
                'SELECT product_code FROM ?:products WHERE product_id = ?i',
                $product_id
            );
        }

        if ($variants) {
            $result = implode(', ', $variants);
        }
    }

    return $result;
}

function fn_data_feeds_get_features_fields()
{
    $company_id = 0;
    if (Registry::get('runtime.company_id')) {
        $company_id = Registry::get('runtime.company_id');
    }

    $features = db_get_array(
        'SELECT ?:product_features_descriptions.feature_id, ?:product_features_descriptions.description'
        . ' FROM ?:product_features_descriptions'
        . ' LEFT JOIN ?:product_features'
            . ' ON (?:product_features_descriptions.feature_id = ?:product_features.feature_id)'
        . ' WHERE ?:product_features.feature_type <> ?s AND lang_code = ?s',
        ProductFeatures::GROUP,
        DESCR_SL
    );
    $features_fields = array();

    if (!empty($features)) {
        foreach ($features as $feature) {
            if (empty($company_id) || fn_check_shared_company_id('product_features', $feature['feature_id'], $company_id)) {
                $features_fields[$feature['feature_id']] = array(
                    'description' => $feature['description'],
                    'process_get' => array ('fn_data_feeds_get_product_features', '#key', '#lang_code'),
                    'linked' => false
                );
            }
        }
    }

    return $features_fields;
}

/**
 * Gets data feed title
 *
 * @param int $datafeed_id Data feed identifier
 * @param string $lang_code 2-letter language code (e.g. 'en', 'ru', etc.)
 * @return string Title
 */
function fn_get_data_feed_name($datafeed_id, $lang_code = CART_LANGUAGE)
{
    $datafeed_name = db_get_field(
        'SELECT datafeed_name'
        . ' FROM ?:data_feed_descriptions'
        . ' WHERE datafeed_id = ?i'
            . ' AND lang_code = ?s',
        $datafeed_id,
        $lang_code
    );

    return $datafeed_name;
}

if (fn_allowed_for('ULTIMATE')) {
    function fn_data_feeds_ult_check_store_permission($params, &$object_type, &$object_name, &$table, &$key, &$key_id)
    {
        if (Registry::get('runtime.controller') == 'data_feeds' && !empty($params['datafeed_id'])) {
            $key = 'datafeed_id';
            $key_id = $params[$key];
            $table = 'data_feeds';
            $object_name = fn_get_data_feed_name($key_id, DESCR_SL);
            $object_type = __('data_feed');

        }
    }
}

//
// Get pattern definition by its id
// Parameters:
// @path_pattern - folder pattern
// @pattern_id - pattern ID
function fn_data_feeds_get_pattern_definition($path_pattern, $pattern_id, $get_for = '')
{
    // First, check basic patterns
    $schema = fn_get_schema($path_pattern, $pattern_id);

    if (empty($schema)) {
        fn_set_notification('E', __('error'), __('error_exim_pattern_not_found'));

        return false;
    }

    if ((!empty($schema['export_only']) && $get_for == 'import') || (!empty($schema['import_only']) && $get_for == 'export')) {
        return array();
    }

    $has_alt_keys = false;

    foreach ($schema['export_fields'] as $field_id => $field_data) {
        if (!empty($field_data['table'])) {
            // Table exists in export fields, but doesn't exist in references definition
            if (empty($schema['references'][$field_data['table']])) {
                fn_set_notification('E', __('error'), __('error_exim_pattern_definition_references'));

                return false;
            }
        }

        // Check if schema has alternative keys to import basic data
        if (!empty($field_data['alt_key'])) {
            $has_alt_keys = true;
        }

        if ((!empty($field_data['export_only']) && $get_for == 'import') || (!empty($field_data['import_only']) && $get_for == 'export')) {
            unset($schema['export_fields'][$field_id]);
        }
    }

    if ($has_alt_keys == false) {
        fn_set_notification('E', __('error'), __('error_exim_pattern_definition_alt_keys'));

        return false;
    }

    return $schema;
}

function fn_data_feeds_export_price($product_id, $price, $company_id, $price_dec_sign_delimiter)
{
    if (!empty($company_id) && fn_data_feeds_is_shared_product($product_id, $company_id)) {
        $shared_price = db_get_field(
            'SELECT product_prices.price as price '
            . 'FROM ?:ult_product_prices as product_prices '
            . 'WHERE company_id = ?i AND lower_limit = 1 AND product_id = ?i AND usergroup_id = ?i',
            $company_id, $product_id, USERGROUP_ALL
        );

        if (isset($shared_price)) {
            $price = fn_format_price($shared_price, CART_PRIMARY_CURRENCY, null, false);
        }
    }

    return fn_exim_export_price($price, $price_dec_sign_delimiter);
}

function fn_data_feeds_get_product_categories($product_id, $category_delimiter, $company_id, $lang_code)
{
    $category = fn_exim_get_product_categories($product_id, 'M', $category_delimiter, $lang_code);

    if (!empty($company_id) && fn_data_feeds_is_shared_product($product_id, $company_id)) {
        $joins = ' JOIN ?:categories ON ?:categories.category_id = ?:products_categories.category_id ';
        $category_id = db_get_field(
            'SELECT ?:products_categories.category_id FROM ?:products_categories ?p '
            . 'WHERE product_id = ?i AND link_type = ?s AND company_id = ?i',
            $joins, $product_id, 'A', $company_id
        );

        $category = fn_get_category_path($category_id, $lang_code, $category_delimiter);
    }

    return $category;
}
/**
 * Check is shared product with result cached in runtime.
 *
 * @param int $product_id Product identifier.
 * @param int $company_id Company indentifier.
 *
 * @return bool
 */
function fn_data_feeds_is_shared_product($product_id, $company_id)
{
    static $product_is_shared = array();

    if (!isset($product_is_shared[$product_id])) {
        $product_is_shared[$product_id] = fn_ult_is_shared_product($product_id, $company_id) == 'Y';
    }
    return $product_is_shared[$product_id];
}
