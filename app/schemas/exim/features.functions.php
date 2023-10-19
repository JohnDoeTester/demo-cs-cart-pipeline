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

use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ProductFeatures;
use Tygh\Registry;

function fn_exim_get_product_feature_categories($data, $lang_code, $category_delimiter)
{
    $categories = '';
    if (empty($data['Group']) && !empty($data['Categories'])) {
        $set_delimiter = ', ';
        $result = array();
        $categories = explode(',', $data['Categories']);

        foreach ($categories as $category_id) {
            $result[] = fn_get_category_path($category_id, $lang_code, $category_delimiter);
        }

        $categories = implode($set_delimiter, fn_exim_wrap_value($result, "'", $set_delimiter));
    }

    return $categories;
}

/**
 * Convert Category names to its IDs
 * Example:
 *      IN array(
 *          'some_data' => ...,
 *          'categories_path' => 'Electronics,Processors'
 *      )
 *      OUT '12,52'
 *
 * @param array<string, string> $feature_data List of feature properties
 * @param string                $lang_code    Two letters lang code
 *
 * @return string Converted categories_path
 */
function fn_exim_get_features_convert_category_path($feature_data, $lang_code, $category_delimiter = '///')
{
    $categories_path = '';

    if (empty($feature_data['company_id'])) {
        $feature_data['company_id'] = empty($feature_data['company'])
            ? Registry::get('runtime.company_id')
            : fn_get_company_id_by_name($feature_data['company']);
    }

    if (!empty($feature_data['parent_id'])) {

        $categories_path = '';
        $parent_feature = fn_get_product_feature_data($feature_data['parent_id']);

        if (!empty($parent_feature['categories_path'])) {
            $categories_path = $parent_feature['categories_path'];
        }

    } else {
        if (!empty($feature_data['categories_path'])) {

            $categories_path = array();

            $_categories_paths = str_getcsv($feature_data['categories_path'], ',', "'");
            if (!empty($_categories_paths)) {
                foreach ($_categories_paths as $category_path) {
                    $categories = explode($category_delimiter, $category_path);
                    array_walk($categories, 'fn_trim_helper');
                    $categories = fn_get_categories_from_path($categories, $feature_data['company_id'], $lang_code);
                    if (end($categories)['id']) {
                        $categories_path[] = end($categories)['id'];
                    }
                }
            }
            $categories_path = implode(',', array_unique($categories_path));
        }
    }

    return $categories_path;
}

function fn_exim_set_product_feature_categories($feature_id, $feature_data, $lang_code, $category_delimiter = '///')
{
    static $categories_ids;

    $categories_path = fn_exim_get_features_convert_category_path($feature_data, $lang_code, $category_delimiter);
    db_query("UPDATE ?:product_features SET categories_path = ?s WHERE feature_id = ?i", $categories_path, $feature_id);

    if ($feature_data['feature_type'] == ProductFeatures::GROUP) {
        db_query("UPDATE ?:product_features SET categories_path = ?s WHERE parent_id = ?i", $categories_path, $feature_id);
    }

    return true;
}

function fn_exim_get_product_feature_group($group_id, $lang_code = CART_LANGUAGE)
{
    $group_name = false;

    if (!empty($group_id)) {
        $group_name = db_get_field('SELECT internal_name FROM ?:product_features_descriptions WHERE feature_id = ?i AND lang_code = ?s', $group_id, $lang_code);
    }

    return $group_name;
}

function fn_exim_get_product_feature_group_id($group_name, $company_id, &$created_group_ids, $lang_code = CART_LANGUAGE)
{
    $group_id = false;

    if (!empty($group_name)) {
        $group = fn_exim_features_find_feature($group_name, ProductFeatures::GROUP, 0, $company_id, $lang_code);

        if (empty($group)) {
            $group_data = array(
                'feature_id' => 0,
                'description' => $group_name,
                'lang_code' => $lang_code,
                'feature_type' => ProductFeatures::GROUP,
                'status' => 'A',
                'company_id' => $company_id
            );

            $group_id = fn_update_product_feature($group_data, 0, $lang_code);
            $created_group_ids[] = $group_id;

            if (fn_allowed_for('ULTIMATE') && !empty($company_id)) {
                fn_exim_update_share_feature($group_id, $company_id);
            }
        } else {
            $group_id = $group['feature_id'];
        }
    }

    return $group_id;
}

function fn_import_get_feature_id(&$primary_object_id, $object, &$skip_get_primary_object_id)
{

    $feature_id = db_get_field('SELECT feature_id FROM ?:product_features_descriptions WHERE intenral_name = ?s AND lang_code = ?s', $object['description'], $object['lang_code']);

    if ($feature_id) {
        $primary_object_id = array(
            'feature_id' => $feature_id
        );
        $skip_get_primary_object_id = true;
    }
}

function fn_import_feature($data, &$processed_data, &$skip_record, $category_delimiter = '///')
{
    static $created_group_ids = array();

    $skip_record = true;
    $skip_process = false;

    $feature = reset($data);
    $langs = array_keys($data);
    $main_lang = reset($langs);

    if (Registry::get('runtime.company_id')) {
        $company_id = Registry::get('runtime.company_id');
    } elseif (!empty($feature['company'])) {
        $company_id = fn_get_company_id_by_name($feature['company']);
    } else {
        $company_id = isset($feature['company_id']) ? $feature['company_id'] : Registry::get('runtime.company_id');
    }

    if (!empty($feature['parent_id'])) {
        $feature['parent_id'] = fn_exim_get_product_feature_group_id(
            $feature['parent_id'],
            $company_id,
            $created_group_ids,
            $main_lang
        );
    }

    $feature_id = 0;

    if (!empty($feature['feature_id'])) {
        Registry::set('runtime.skip_sharing_selection', true);

        $feature_data = db_get_row(
            'SELECT feature_id, company_id FROM ?:product_features WHERE feature_id = ?i',
            $feature['feature_id']
        );

        Registry::set('runtime.skip_sharing_selection', false);

        if ($feature_data) {
            if (fn_allowed_for('ULTIMATE') && !empty($company_id)) {
                if ($feature_data['company_id'] == $company_id) {
                    $feature_id = $feature_data['feature_id'];
                } elseif (fn_ult_is_shared_object('product_features', $feature_data['feature_id'], $company_id)) {
                    $skip_process = true;
                    $feature_id = $feature_data['feature_id'];
                } else {
                    unset($feature['feature_id']);
                }
            } else {
                $feature_id = $feature_data['feature_id'];
            }
        }
    }

    if (!$feature_id) {
        $feature_data = fn_exim_features_find_feature(
            $feature['internal_name'],
            $feature['feature_type'],
            isset($feature['parent_id']) ? $feature['parent_id'] : 0,
            $company_id,
            $main_lang
        );

        if ($feature_data) {
            $feature_id = $feature_data['feature_id'];
            $skip_process = fn_allowed_for('ULTIMATE') && $feature_data['company_id'] != $company_id;
        }
    }

    if (!empty($feature['purpose'])) {
        $is_exist = fn_check_product_feature_by_feature_params(
            $feature['feature_type'],
            $feature['purpose'],
            $feature['feature_style'],
            $feature['filter_style']
        );
        if (!$is_exist) {
            $incorrect_type = true;
        }
    } elseif (
        isset($feature['feature_type'])
        && $feature['feature_type'] !== ProductFeatures::GROUP
    ) {
        $purpose = fn_get_product_feature_purpose_by_type($feature['feature_type']);
        if (empty($purpose)) {
            $incorrect_type = true;
        }
    }

    if (isset($incorrect_type)) {
        $skip_process = true;
        fn_set_notification(
            NotificationSeverity::ERROR,
            __('error'),
            __('exim_error_incorrect_concrete_feature_type', [
                '[feature_type]' => $feature['feature_type'],
                '[feature_name]' => $feature['internal_name']
            ])
        );
    }

    if ($skip_process) {
        $processed_data['S']++;
        return $feature_id;
    }

    $feature['variants'] = array();

    if (!empty($feature['Variants'])) {
        $variants = str_getcsv($feature['Variants'], ',', "'");
        array_walk($variants, 'fn_trim_helper');

        list($origin_variants) = fn_get_product_feature_variants(array('feature_id' => $feature_id), 0, $main_lang);
        $feature['original_var_ids'] = implode(',', array_keys($origin_variants));

        foreach ($variants as $variant) {
            $feature['variants'][]['variant'] = $variant;
        }
    }

    // Convert categories from Names to C_IDS: Electronics,Processors -> 3,45
    if (isset($feature['categories_path'])) {
        $feature['categories_path'] = fn_exim_get_features_convert_category_path($feature, $main_lang, $category_delimiter);
    }

    if (empty($feature_id)) {
        $feature['company_id'] = $company_id;

        $feature_id = fn_update_product_feature($feature, 0, $main_lang);
        $processed_data['N']++;

        fn_set_progress('echo', __('creating') . ' features <b>' . $feature_id . '</b>. ', false);

        if (fn_allowed_for('ULTIMATE') && !empty($company_id)) {
            fn_exim_update_share_feature($feature_id, $company_id);
        }
    } else {
        unset($feature['feature_id']);

        fn_update_product_feature(
            $feature,
            $feature_id,
            $main_lang
        );

        if (in_array($feature_id, $created_group_ids)) {
            $processed_data['N']++;
        } else {
            $processed_data['E']++;
            fn_set_progress('echo', __('updating') . ' features <b>' . $feature_id . '</b>. ', false);
        }
    }

    foreach ($data as $lang_code => $feature_data) {
        unset($feature_data['feature_id']);

        db_query(
            'UPDATE ?:product_features_descriptions SET ?u WHERE feature_id = ?i AND lang_code = ?s',
            $feature_data, $feature_id, $lang_code
        );
    }

    return $feature_id;
}

function fn_exim_get_product_features_variants($feature_id, $lang_code)
{
    list($feature_variants) = fn_get_product_feature_variants(array('feature_id' => $feature_id), 0, $lang_code);

    $variants = array();
    foreach ($feature_variants as $variant) {
        $variants[] = fn_exim_wrap_value($variant['variant'], "'", ',');
    }

    $variants = implode(', ', $variants);

    return $variants;
}

if (fn_allowed_for('ULTIMATE')) {

    function fn_exim_update_share_feature($feature_id, $company_id)
    {
        static $feature = array();

        if (!isset($feature[$company_id . '_' .$feature_id]) && !fn_check_shared_company_id('product_features', $feature_id, $company_id)) {
            fn_ult_update_share_object($feature_id, 'product_features', $company_id);
            $feature[$company_id . '_' .$feature_id] = true;
        }
    }

}

/**
 * Finds product feature available to company.
 *
 * @param string    $feature_name       Product feature name
 * @param string    $feature_type       Product feature type
 * @param int       $feature_parent_id  Product feature group identifier
 * @param int       $company_id         Company identifier
 * @param string    $lang_code          Language code (ru, en, etc)
 *
 * @return array  Returns the product feature data on success otherwise empty array
 */
function fn_exim_features_find_feature($feature_name, $feature_type, $feature_parent_id, $company_id, $lang_code)
{
    $feature = array();
    $features = db_get_hash_array(
        'SELECT pf.feature_id, pf.company_id FROM ?:product_features_descriptions AS pfd'
        . ' LEFT JOIN ?:product_features AS pf ON pf.feature_id = pfd.feature_id'
        . ' WHERE internal_name = ?s AND lang_code = ?s AND feature_type = ?s AND parent_id = ?i',
        'company_id',
        $feature_name, $lang_code, $feature_type, $feature_parent_id
    );

    if (fn_allowed_for('ULTIMATE')) {
        if (isset($features[$company_id])) {
            $feature = $features[$company_id];
        } else {
            foreach ($features as $item) {
                if (fn_exim_features_is_shared_feature($item['feature_id'], $company_id)) {
                    $feature = $item;
                    break;
                }
            }
        }
    } elseif ($features) {
        $feature = reset($features);
    }

    return $feature;
}

/**
 * Checks if the product feature is shared to company.
 *
 * @param int $feature_id   Product feature identifier
 * @param int $company_id   Company identifier
 *
 * @return bool
 */
function fn_exim_features_is_shared_feature($feature_id, $company_id)
{
    static $shared_features = array();

    if (!isset($shared_features[$feature_id][$company_id])) {
        $shared_features[$feature_id][$company_id] = fn_ult_is_shared_object('product_features', $feature_id, $company_id);
    }

    return $shared_features[$feature_id][$company_id];
}