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

use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ProductFeatures;
use Tygh\Enum\ProfileTypes;
use Tygh\Registry;
use Tygh\Enum\YesNo;
use Tygh\Enum\ProfileFieldSections;

function fn_blocks_get_vendor_info()
{
    $company_id = isset($_REQUEST['company_id']) ? $_REQUEST['company_id'] : null;

    $company_data = fn_get_company_data($company_id);
    $company_data['logos'] = fn_get_logos($company_id);

    return $company_data;
}

/**
 * Decides whether to disable cache for "products" block.
 *
 * @param $block_data
 *
 * @return bool Whether to disable cache
 */
function fn_block_products_disable_cache($block_data)
{
    // Disable cache for "Recently viewed" filling
    if (isset($block_data['content']['items']['filling'])
        && $block_data['content']['items']['filling'] == 'recent_products'
    ) {
        return true;
    }

    return false;
}

/**
 * Gets the data of companies by parameters.
 *
 * @param array $params An array of search parameters.
 *
 * @return array An array of companies
 */
function fn_blocks_get_vendors(array $params = [])
{
    $params['company_id'] = empty($params['item_ids']) ? [] : fn_explode(',', $params['item_ids']);

    $products_count = !empty($params['block_data']['properties']['show_products_count'])
        && YesNo::toBool($params['block_data']['properties']['show_products_count']);
    $params['extend'] = [
        'products_count' => $products_count,
        'logos'          => true,
        'placement_info' => true,
    ];

    if (fn_allowed_for('MULTIVENDOR')) {
        $params['use_i18n_fields'] = true;
    }

    $displayed_vendors = empty($params['block_data']['properties']['displayed_vendors']) ? 0 : $params['block_data']['properties']['displayed_vendors'];

    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];
    if ($storefront->getCompanyIds()) {
        $params['company_id'] = $params['company_id']
            ? array_intersect($params['company_id'], $storefront->getCompanyIds())
            : $storefront->getCompanyIds();

        if (!$params['company_id']) {
            return [[]];
        }
    }

    list($companies,) = fn_get_companies($params, Tygh::$app['session']['auth'], $displayed_vendors);

    if ($companies) {
        foreach ($companies as $key => $company_data) {
            $companies[$key] = fn_filter_company_data_by_profile_fields($company_data);
        }

        $companies = fn_array_combine(array_column($companies, 'company_id'), $companies);
    }

    return [$companies];
}

/**
 * Provides list of languages for the Languages block.
 *
 * @return array
 */
function fn_blocks_get_languages()
{
    // there is no need to get languages from the database as they are already initialized
    return Registry::get('languages');
}

/**
 * Fetches profile fields in saved order
 *
 * @param array $params Search parameters
 *
 * @return array
 */
function fn_blocks_get_lite_checkout_profile_fields($params)
{
    $item_ids = isset($params['item_ids']) ? explode(',', $params['item_ids']) : '';
    if (empty($item_ids)) {
        return [];
    }

    $profile_fields = fn_get_profile_fields('ALL', [], DESCR_SL, ['include_ids' => $item_ids]);
    unset($profile_fields[ProfileFieldSections::ESSENTIALS]);

    $section = key($profile_fields);
    $sorted_fields = fn_sort_by_ids($profile_fields[$section], $item_ids, 'field_id');

    $position = 0;
    foreach ($sorted_fields as $field_id => $field) {
        $sorted_fields[$field_id]['position'] = $position;
        $position += 10;
    }

    $prepared_profile_fields = [$section => $sorted_fields];

    return [$prepared_profile_fields];
}

/**
 * Synchronises customer location profile fields visibility
 *
 * @param $block_data
 */
function fn_blocks_update_customer_location_profile_fields_visibility($block_data)
{
    $params = [
        'section'            => ProfileFieldSections::SHIPPING_ADDRESS,
        'force_set_required' => ['s_country', 's_city', 's_state']
    ];

    fn_blocks_update_profile_fields_visibility($block_data, $params);
}

function fn_blocks_update_contact_information_check_required_fields(&$block_data)
{
    if (!isset($block_data['content']['items']['item_ids'])) {
        return;
    }

    $required_field_ids = db_get_hash_single_array(
        'SELECT field_id, field_name FROM ?:profile_fields WHERE field_name IN (?a) AND section = ?s AND profile_type = ?s',
        ['field_name', 'field_id'],
        ['email', 'phone'], ProfileFieldSections::CONTACT_INFORMATION, ProfileTypes::CODE_USER
    );

    if (isset($required_field_ids['email'])) {
        $field_ids = fn_explode(',', $block_data['content']['items']['item_ids']);

        $is_email_exists = in_array($required_field_ids['email'], $field_ids, true);
        $is_phone_exists = isset($required_field_ids['phone']) && in_array($required_field_ids['phone'], $field_ids, true);

        $is_email_required = isset($block_data['content']['items']['required'][sprintf('field_id_%s', $required_field_ids['email'])])
            && $block_data['content']['items']['required'][sprintf('field_id_%s', $required_field_ids['email'])] == YesNo::YES;

        $is_phone_required = isset($block_data['content']['items']['required'][sprintf('field_id_%s', $required_field_ids['phone'])])
            && $block_data['content']['items']['required'][sprintf('field_id_%s', $required_field_ids['phone'])] == YesNo::YES;

        if (!$is_email_exists && !$is_phone_exists) {
            $field_ids[] = $required_field_ids['email'];
            $block_data['content']['items']['required'][sprintf('field_id_%s', $required_field_ids['email'])] = YesNo::YES;

            $field = fn_get_profile_field($required_field_ids['email']);

            fn_set_notification('W', __('warning'),
                implode(PHP_EOL, [
                    __('bm.customer_information_block.warning.email_or_phome_must_be_required'),
                    __('bm.customer_information_block.warning.field_automaticly_added', ['[field_name]' => $field['description']])
                ])
            );
        } elseif ($is_email_exists && !$is_email_required && !$is_phone_required) {
            $block_data['content']['items']['required'][sprintf('field_id_%s', $required_field_ids['email'])] = YesNo::YES;

            $field = fn_get_profile_field($required_field_ids['email']);

            fn_set_notification('W', __('warning'),
                implode(PHP_EOL, [
                    __('bm.customer_information_block.warning.email_or_phome_must_be_required'),
                    __('bm.customer_information_block.warning.field_marked_as_required', ['[field_name]' => $field['description']])
                ])
            );
        } elseif (!$is_email_exists && $is_phone_exists && !$is_phone_required) {
            $block_data['content']['items']['required'][sprintf('field_id_%s', $required_field_ids['phone'])] = YesNo::YES;

            $field = fn_get_profile_field($required_field_ids['phone']);

            fn_set_notification('W', __('warning'),
                implode(PHP_EOL, [
                    __('bm.customer_information_block.warning.email_or_phome_must_be_required'),
                    __('bm.customer_information_block.warning.field_marked_as_required', ['[field_name]' => $field['description']])
                ])
            );
        }

        $block_data['content']['items']['item_ids'] = implode(',', $field_ids);

        if (isset($block_data['content_data']['content'])) {
            $block_data['content_data']['content'] = $block_data['content'];
        }
    }
}

/**
 * Synchronises contact information profile fields visibility
 *
 * @param $block_data
 */
function fn_blocks_update_contact_information_profile_fields_visibility($block_data)
{
    $params = [
        'section' => ProfileFieldSections::CONTACT_INFORMATION,
    ];
    fn_blocks_update_profile_fields_visibility($block_data, $params);
}

/**
 * Synchronises shipping address profile fields visibility
*
* @param $block_data
*/
function fn_blocks_update_shipping_address_profile_fields_visibility($block_data)
{
    $params = [
        'section'       => ProfileFieldSections::SHIPPING_ADDRESS,
        'exclude_names' => ['s_country', 's_city', 's_state'],
    ];
    fn_blocks_update_profile_fields_visibility($block_data, $params);
}

/**
 * Synchronises billing address profile fields visibility
*
* @param $block_data
*/
function fn_blocks_update_billing_address_profile_fields_visibility($block_data)
{
    $params = [
        'section' => ProfileFieldSections::BILLING_ADDRESS,
    ];
    fn_blocks_update_profile_fields_visibility($block_data, $params);
}

/**
 * Synchronises profile fields checkout visibility
 *
 * @param array $block_data Block data
 * @param array $params Picker parameters
 */
function fn_blocks_update_profile_fields_visibility($block_data, $params)
{
    if (!isset($block_data['content']['items']['item_ids'])) {
        return;
    }

    $block_contents = db_get_array(
        'SELECT content FROM ?:bm_blocks_content'
        . ' LEFT JOIN ?:bm_blocks ON ?:bm_blocks.block_id = ?:bm_blocks_content.block_id'
        . ' WHERE ?:bm_blocks.type IN (?a)',
        [
            'lite_checkout_location',
            'lite_checkout_customer_address',
            'lite_checkout_customer_information',
            'lite_checkout_customer_billing'
        ]
    );

    $used_field_ids = [];

    foreach ($block_contents as $block_content) {
        $content = (array) @unserialize($block_content['content']);
        $block_field_ids = isset($content['items']['item_ids']) ? fn_explode(',', $content['items']['item_ids']) : [];

        if (empty($block_field_ids)) {
            continue;
        }

        foreach ($block_field_ids as $field_id) {
            $used_field_ids[$field_id] = $field_id;
        }
    }

    $field_ids = explode(',', $block_data['content']['items']['item_ids']);

    $conditions['section'] = db_quote('section = ?s', $params['section']);
    $conditions['profile_type'] = db_quote('profile_type = ?s', ProfileTypes::CODE_USER);

    if (!empty($params['exclude_names'])) {
        $conditions['exclude_names'] = db_quote('field_name NOT IN (?a)', $params['exclude_names']);
    } elseif (!empty($params['include_names'])) {
        $conditions['include_names'] = db_quote('field_name IN (?a)', $params['include_names']);
    }

    if ($used_field_ids) {
        $conditions['field_ids'] = db_quote('field_id NOT IN (?n)', $used_field_ids);
    }

    db_query(
        'UPDATE ?:profile_fields SET checkout_show = ?s, checkout_required = ?s WHERE ?p',
        YesNo::NO,
        YesNo::NO,
        implode(' AND ', $conditions)
    );

    if (!empty($params['force_set_required']) && is_array($params['force_set_required'])) {
        $params['force_set_required'] = db_get_fields(
            'SELECT field_id FROM ?:profile_fields WHERE field_name IN (?a) AND section = ?s AND profile_type = ?s',
            $params['force_set_required'], $params['section'], ProfileTypes::CODE_USER
        );
    }

    foreach ($field_ids as $field_id) {
        $raw_required_flag = isset($block_data['content']['items']['required']["field_id_{$field_id}"])
            ? $block_data['content']['items']['required']["field_id_{$field_id}"] : null;

        if (!empty($params['force_set_required'])
            && (is_bool($params['force_set_required'])
            || in_array($field_id, $params['force_set_required'])
        )) {
            $required_flag = YesNo::YES;
        } else {
            $required_flag = $raw_required_flag == YesNo::YES ? YesNo::YES : YesNo::NO;
        }

        db_query(
            'UPDATE ?:profile_fields SET checkout_show = ?s, checkout_required = ?s WHERE field_id = ?i',
            YesNo::YES,
            $required_flag,
            $field_id
        );
    }
}

/**
 * Provides storefront ID for caching.
 *
 * @return int
 */
function fn_blocks_get_current_storefront_id()
{
    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];

    return $storefront->storefront_id;
}


function fn_block_get_block_with_items_info(array $block, $lang_code = CART_LANGUAGE)
{
    $items = isset($block['content']['items']) ? $block['content']['items'] : [];
    $filling = isset($items['filling']) ? $items['filling'] : null;
    $schema_items = isset($block['schema']['content']['items']) ? $block['schema']['content']['items'] : [];
    $type = isset($schema_items['object']) ? (string) $schema_items['object'] :  '';

    if ($filling === 'manually') {
        $item_ids = isset($items['item_ids']) ? array_filter(fn_explode(',', $items['item_ids'])) : [];
        $content = (count($item_ids) === 0 && isset($schema_items['fillings'][$filling]['picker_params']['no_item_text']))
            ? $schema_items['fillings'][$filling]['picker_params']['no_item_text']
            : __('n_' . $type, [count($item_ids)], $lang_code);
    } else {
        $limit = isset($items['limit']) ? (int) $items['limit'] : 0;
        $filling_text = fn_is_lang_var_exists($filling) ? __($filling, [], $lang_code) : '';
        $content = ($limit === 0)
            ? $filling_text
            : (
                $filling_text
                    ? sprintf('%s, %s', $filling_text, __('n_' . $type, [$limit], $lang_code))
                    : __('n_' . $type, [$limit], $lang_code)
            );
    }

    return [
        'content' => $content,
    ];
}

function fn_block_get_menu_info(array $block)
{
    $schema_menu_list = isset($block['schema']['content']['menu']['values']) ? (array) $block['schema']['content']['menu']['values'] : [];
    $menu_id = isset($block['content']['menu']) ? (int) $block['content']['menu'] : 0;
    $content = isset($schema_menu_list[$menu_id]) ? (string) $schema_menu_list[$menu_id] : '';

     return [
         'content' => $content,
     ];
}

function fn_block_get_template_info(array $block)
{
    $schema_template_list = isset($block['schema']['templates']) ? (array) $block['schema']['templates'] : [];
    $template_name = isset($block['properties']['template']) ? (string) $block['properties']['template'] : '';
    $content = isset($schema_template_list[$template_name]['name']) ? (string) $schema_template_list[$template_name]['name'] : '';

    return [
        'content' => $content,
    ];
}

function fn_block_get_vendors_info(array $block, $lang_code = CART_LANGUAGE)
{
    $items = isset($block['content']['items']) ? $block['content']['items'] : [];
    $filling = isset($items['filling']) ? (string) $items['filling'] : '' ;
    $item_ids = isset($items['item_ids']) ? array_filter(fn_explode(',', $items['item_ids'])) : null;
    $limit = isset($item_ids) ? count($item_ids) : (isset($block['properties']['displayed_vendors']) ? $block['properties']['displayed_vendors'] : 0);
    $filling_text = fn_is_lang_var_exists($filling) ? __($filling, [], $lang_code) : '';
    $content = ($filling_text) ? sprintf('%s, %s', $filling_text, __('n_vendors', [$limit], $lang_code)) : __('n_vendors', [$limit], $lang_code);

    return [
        'content' => $content,
    ];
}

function fn_blocks_get_brands($value, $block, $schema)
{
    $items_per_page = empty($block['properties']['total_items'])
        ? 0
        : $block['properties']['total_items'];

    $params = [
        'exclude_group'           => true,
        'get_descriptions'        => true,
        'feature_types'           => [ProductFeatures::EXTENDED],
        'variants'                => true,
        'variants_items_per_page' => $items_per_page,
        'variants_page'           => 1,
        'plain'                   => true,
    ];

    list($features) = fn_get_product_features($params, 0);

    $variants = [];
    foreach ($features as $feature) {
        if (!empty($feature['variants'])) {
            $variants = array_merge($variants, $feature['variants']);
        }
    }

    return $variants;
}

function fn_blocks_menu_get_request_hash(array $block, array $request, array $server)
{
    $menu_id = isset($block['content']['menu']) ? (int) $block['content']['menu'] : null;

    if (!$menu_id || empty($request['dispatch'])) {
        return null;
    }

    $menu_items_depedncies = fn_menu_get_menu_items_dependencies($menu_id);

    $depedncies = [
        'dispatch' => null,
        'request'  => null,
        'runtime'  => [],
    ];

    if (Registry::get('runtime.controller_status') !== CONTROLLER_STATUS_NO_PAGE) {
        $dispatch_requests = [];

        if (isset($menu_items_depedncies['request'][$request['dispatch']])) {
            $dispatch_requests = $menu_items_depedncies['request'][$request['dispatch']];
            $dispatch = $request['dispatch'];
        } else {
            $request_uri = parse_url($server['REQUEST_URI'], PHP_URL_PATH);

            if (isset($menu_items_depedncies['request'][$request_uri])) {
                $dispatch_requests = $menu_items_depedncies['request'][$request_uri];
                $dispatch = $request_uri;
            }
        }

        foreach ($dispatch_requests as $dispatch_request) {
            $is_request_equal = true;

            foreach ($dispatch_request as $request_key => $request_value) {
                if (
                    isset($request[$request_key])
                    && ($request[$request_key] == $request_value || $request_value === '*')
                ) {
                    $dispatch_request[$request_key] = $request[$request_key];
                    continue;
                }

                $is_request_equal = false;
                break;
            }

            if ($is_request_equal) {
                $depedncies['dispatch'] = $dispatch;
                $depedncies['request'] = $dispatch_request;
                break;
            }
        }
    }

    foreach ($menu_items_depedncies['runtime'] as $key) {
        $depedncies['runtime'] = array_merge($depedncies['runtime'], [$key => Registry::get('runtime.' . $key)]);
    }

    return md5(serialize($depedncies));
}
