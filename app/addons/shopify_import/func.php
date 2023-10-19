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

use Tygh\Addons\ProductVariations\Product\FeaturePurposes;
use Tygh\Common\OperationResult;
use Tygh\Enum\ImagePairTypes;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ProductFeatures;
use Tygh\Enum\ShopifyFields;
use Tygh\Enum\ShopifyImportStatuses;
use Tygh\Enum\YesNo;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

/**
 * Filters shopify products data for importing.
 *
 * @param array<string|array<string>> $import_data Import data
 * @param array<string>               $params      Import params
 *
 * @return Tygh\Common\OperationResult
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_shopify_import_filter_data(array $import_data, array $params = [])
{
    $result = new OperationResult(true);

    if (empty($import_data) || !isset($import_data[0][ShopifyFields::HANDLE])) {
        $result->setSuccess(false);
        $result->setErrors([
            __('shopify_import.invalid_csv_file_format')
        ]);
        return $result;
    }

    $fields_schema = fn_get_schema('shopify_import', 'shopify_fields');
    $filtered_data = [];
    $filtering_params = [
        'products_without_sku' => false
    ];

    list($vendor_id, $vendor) = fn_shopify_import_get_vendor_data($filtering_params, $params);
    $filtering_params['import_company_id'] = $vendor_id ?: Registry::get('runtime.company_id');

    $feature_names_list = fn_shopify_import_get_features_internal_names_list($filtering_params);
    $can_create_feature = true;
    if (
        Registry::get('runtime.company_id') && $vendor_id
        && Registry::get('settings.Vendors.allow_vendor_manage_features') === YesNo::NO
    ) {
        $can_create_feature = false;
    }

    $line_count = 0;
    $group = [];

    foreach ($import_data as $line) {
        fn_shopify_import_group_products($group, $fields_schema, $line);

        fn_shopify_import_filter_simple_fields($filtered_data, $fields_schema, $line, $line_count);

        // Should go after filling simple fields from schema and before product identification
        if (
            !fn_shopify_import_filter_images(
                $filtered_data,
                $group,
                $line,
                $line_count
            )
        ) {
            $line_count++;
            continue;
        }

        fn_shopify_import_filter_sku($filtered_data, $filtering_params, $line, $line_count);

        $filtered_data[$line_count]['lang_code'] = DEFAULT_LANGUAGE;

        if ($vendor) {
            $filtered_data[$line_count]['company'] = $vendor;
        }

        fn_shopify_import_filter_options(
            $filtered_data,
            $group,
            $line,
            $line_count,
            $fields_schema,
            $feature_names_list,
            $can_create_feature,
            $filtering_params,
            $vendor
        );

        $line_count++;
    }

    if ($filtering_params['products_without_sku']) {
        $result->setWarnings([
            __('shopify_import.products_without_sku')
        ]);
    }

    $result->setData([
        'filtered_data' => array_values($filtered_data),
        'filtering_params' => $filtering_params
    ]);

    return $result;
}

/**
 * Gets list of all features internal names.
 *
 * @param array  $filtering_params Filtering params
 * @param string $lang_code        Two letters language code
 *
 * @return array
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_shopify_import_get_features_internal_names_list(array $filtering_params, $lang_code = DESCR_SL)
{
    list($features,) = fn_get_product_features(
        [
            'plain'         => true,
            'exclude_group' => true,
            'company_id'    => $filtering_params['import_company_id']
        ],
        0,
        $lang_code
    );

    return array_column($features, 'internal_name');
}

/**
 * Gets vendor data for shopify import.
 *
 * @param array $filtering_params Filtering params
 * @param array $params           Import params
 *
 * @return array|bool
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_shopify_import_get_vendor_data(array &$filtering_params, array $params)
{
    if (!fn_allowed_for('MULTIVENDOR')) {
        return false;
    }

    if (Registry::get('runtime.company_id')) {
        $vendor_id = Registry::get('runtime.company_id');
    } elseif (
        isset($params['shopify_import']['general.import_mode'])
        && $params['shopify_import']['general.import_mode'] === 'specific_vendor'
        && isset($params['shopify_import']['general.company_id'])
    ) {
        $vendor_id = $params['shopify_import']['general.company_id'];
        $filtering_params['specific_vendor'] = true;
    } else {
        $vendor_id = 0;
        $filtering_params['all_vendors'] = true;
    }

    $vendor = !empty($vendor_id) ? fn_get_company_name($vendor_id) : '~';

    return [$vendor_id, $vendor];
}

/**
 * Link main product and its variations to a group.
 *
 * @param array $group         Products group data
 * @param array $fields_schema Shopify fields schema
 * @param array $line          Single import line data
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_shopify_import_group_products(array &$group, array $fields_schema, array &$line)
{
    if (!isset($group[ShopifyFields::HANDLE]) || $group[ShopifyFields::HANDLE] !== $line[ShopifyFields::HANDLE]) {
        $group = [];
        foreach ($fields_schema['main_product_fields'] as $main_product_field) {
            $group[$main_product_field] = $line[$main_product_field];
        }
        $group['is_main'] = true;
        $group['main_product_image'] = $line[ShopifyFields::IMAGE_SRC];
    } else {
        foreach ($fields_schema['variation_empty_fields'] as $empty_field) {
            $line[$empty_field] = $group[$empty_field];
        }
        $group['is_main'] = false;
    }
}

/**
 * Fill filtering result data with simple fields.
 *
 * @param array $filtered_data Filtered data
 * @param array $fields_schema Shopify fields schema
 * @param array $line          Single import line data
 * @param int   $line_count    Single import line count
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_shopify_import_filter_simple_fields(array &$filtered_data, array $fields_schema, array $line, $line_count)
{
    foreach ($fields_schema['simple_mapping_fields'] as $cscart_field => $shopify_field) {
        if (empty($line[$shopify_field])) {
            continue;
        }
        $filtered_data[$line_count][$cscart_field] = $line[$shopify_field];
    }

    if (empty($line[ShopifyFields::STATUS])) {
        return;
    }
    if ($line[ShopifyFields::STATUS] === 'active') {
        $filtered_data[$line_count]['status'] = ObjectStatuses::ACTIVE;
    } else {
        $filtered_data[$line_count]['status'] = ObjectStatuses::DISABLED;
    }
}

/**
 * Filter images from shopify data.
 *
 * @param array $filtered_data Filtered data
 * @param array $group         Products group data
 * @param array $line          Single import line data
 * @param int   $line_count    Single import line count
 *
 * @return bool
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_shopify_import_filter_images(array &$filtered_data, array $group, array $line, $line_count)
{
    // Filter additional images of main product
    if (
        !empty($line[ShopifyFields::IMAGE_SRC]) && $line[ShopifyFields::IMAGE_POSITION] > 1
        && empty($line[ShopifyFields::VARIANT_INVENTORY_POLICY]) && empty($line[ShopifyFields::VARIANT_FULFILLMENT_SERVICE])
    ) {
        $main_product_line = $line_count - ($line[ShopifyFields::IMAGE_POSITION] - 1);
        if (empty($filtered_data[$main_product_line]['Images'])) {
            $filtered_data[$main_product_line]['Images'] = $line[ShopifyFields::IMAGE_SRC];
        } else {
            $filtered_data[$main_product_line]['Images'] .= '///' . $line[ShopifyFields::IMAGE_SRC];
        }

        unset($filtered_data[$line_count]);

        return false;
    }

    // Add main product image for variations with no images
    if (!empty($line[ShopifyFields::VARIANT_IMAGE])) {
        $filtered_data[$line_count]['Images'] = $line[ShopifyFields::VARIANT_IMAGE];
    } elseif (!$group['is_main'] && isset($group['main_product_image']) && !empty($group['main_product_image'])) {
        $filtered_data[$line_count]['Images'] = $group['main_product_image'];
    }

    return true;
}

/**
 * Identify products by SKU and handle.
 *
 * @param array $filtered_data    Filtered data
 * @param array $filtering_params Filtering params
 * @param array $line             Single import line data
 * @param int   $line_count       Single import line count
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_shopify_import_filter_sku(array &$filtered_data, array &$filtering_params, array $line, $line_count)
{
    if (!empty($line[ShopifyFields::VARIANT_SKU])) {
        $filtered_data[$line_count]['product_code'] = str_replace("'", '', $line[ShopifyFields::VARIANT_SKU]);
    } elseif (!empty($line[ShopifyFields::HANDLE])) {
        $filtered_data[$line_count]['product_code'] = str_replace('-', '', strtoupper($line[ShopifyFields::HANDLE])) . $line_count;
        $filtering_params['products_without_sku'] = true;
    }
}

/**
 * Filter shopify products options and variants.
 *
 * @param array  $filtered_data      Filtered data
 * @param array  $group              Products group data
 * @param array  $line               Single import line data
 * @param int    $line_count         Single import line count
 * @param array  $fields_schema      Shopify fields schema
 * @param array  $feature_names_list Features names list
 * @param bool   $can_create_feature Whether vendor can create feature or not
 * @param array  $filtering_params   Filtering params
 * @param string $vendor             Vendor name
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_shopify_import_filter_options(
    array &$filtered_data,
    array &$group,
    array $line,
    $line_count,
    array $fields_schema,
    array $feature_names_list,
    $can_create_feature,
    array $filtering_params,
    $vendor
) {
    // Remove unnecessary default data from option field when product has no options
    if ($line[$fields_schema['option_name_columns'][0]] === ShopifyFields::TITLE) {
        $line[$fields_schema['option_name_columns'][0]] = '';
    }

    $option_count = 1;
    foreach ($fields_schema['option_name_columns'] as $option_name_column) {
        $option_value_column = 'Option' . $option_count . ' Value';
        $option_name = $line[$option_name_column];
        $option_value = $line[$option_value_column];
        if (!empty($option_name)) {
            // Create new feature if it doesn't exist
            if (!in_array($option_name, $feature_names_list) && $can_create_feature) {
                $new_feature_id = fn_update_product_feature(
                    [
                        'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                        'feature_type' => ProductFeatures::TEXT_SELECTBOX,
                        'company_id' => $filtering_params['import_company_id'],
                        'internal_name' => $option_name
                    ],
                    0
                );
                if ($new_feature_id) {
                    $feature_names_list[] = $option_name;
                }
            }
            $group[$option_name_column] = $option_name;
        } elseif (!empty($group[$option_name_column])) {
            $option_name = $group[$option_name_column];
        }
        if (!empty($option_name) && !empty($option_value)) {
            if (!isset($filtered_data[$line_count]['Features'])) {
                $filtered_data[$line_count]['Features'] = '';
            }
            $filtered_data[$line_count]['Features'] .= '; ' . $option_name . ': S[' . $option_value . ']';
        }
        $option_count++;
    }

    if (!isset($filtered_data[$line_count]['Features']) || empty($filtered_data[$line_count]['Features'])) {
        return;
    }
    $filtered_data[$line_count]['Features'] = ltrim($filtered_data[$line_count]['Features'], '; ');
    if ($vendor) {
        $vendor_group = $vendor !== '~' ? '-' . $vendor : '';
        $filtered_data[$line_count]['Variation group code'] = str_replace(' ', '', strtoupper($line[ShopifyFields::HANDLE] . $vendor_group));
    } else {
        $filtered_data[$line_count]['Variation group code'] = str_replace(' ', '', strtoupper($line[ShopifyFields::HANDLE]));
    }
}

/**
 * Updates product images when importing a product.
 *
 * @param int          $product_id       Product ID
 * @param array|string $images           Images from import file
 * @param string       $images_path      Default dir to search files on server
 * @param string       $images_delimiter Images delimiter
 * @param string       $remove_images    Whether to remove additional images
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_shopify_import_set_product_images($product_id, $images, $images_path, $images_delimiter, $remove_images)
{
    if (
        is_string($images) && !fn_string_not_empty($images)
        || is_array($images) && !$images
    ) {
        return;
    }

    if (is_string($images)) {
        $images = explode($images_delimiter, $images);
    }

    foreach ($images as $i => $image) {
        $type = $i === 0 ? ImagePairTypes::MAIN : ImagePairTypes::ADDITIONAL;

        $image = trim($image);
        if (!$image) {
            continue;
        }

        $options = ['remove_images' => $remove_images];

        fn_exim_import_images(
            $images_path,
            false,
            $image,
            $i * 10,
            $type,
            $product_id,
            'product',
            $options
        );
    }
}

/**
 * Saves import result data to database.
 *
 * @param bool       $is_success Whether import went successfully or not
 * @param int|string $company_id Company ID
 *
 * @return void
 */
function fn_shopify_import_save_import_result($is_success, $company_id)
{
    $data = [];
    $data['company_id'] = $company_id;
    $data['status'] = $is_success ? ShopifyImportStatuses::SUCCESS : ShopifyImportStatuses::FAILURE;
    $data['updated_at'] = TIME;

    db_query('INSERT INTO ?:shopify_imports ?e', $data);
}

/**
 * Gets last shopify import info
 *
 * @param array $params Import results parameters
 *
 * @return array
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_shopify_import_get_last_sync_info(array $params = [])
{
    $company_id = empty($params['company_id']) ? fn_get_runtime_company_id() : $params['company_id'];

    $result = [
        'last_sync_timestamp' => 0,
        'status'              => ''
    ];

    $condition = [
        db_quote('company_id = ?i', $company_id)
    ];

    $last_sync = db_get_row(
        'SELECT * FROM ?:shopify_imports WHERE 1=1 AND ?p ORDER BY updated_at DESC LIMIT 1',
        implode(' AND ', $condition)
    );

    if (empty($last_sync)) {
        return $result;
    }

    $result['status'] = __('shopify_import.last_status.' . $last_sync['status']);
    $result['status_code'] = (string) $last_sync['status'];
    $result['last_sync_timestamp'] = (int) $last_sync['updated_at'];

    return $result;
}
