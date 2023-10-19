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
 * 'copyright.txt' FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

use Tygh\Addons\ProductVariations\Product\Group\Repository as GroupRepository;
use Tygh\Addons\ProductVariations\Product\Repository as ProductRepository;
use Tygh\Addons\ProductVariations\Product\Group\Group;
use Tygh\Addons\ProductVariations\Product\FeaturePurposes;
use Tygh\Common\OperationResult;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ProductFeatures;
use Tygh\Registry;
use Tygh\Enum\YesNo;
use Tygh\Enum\NotificationSeverity;
use Tygh\Addons\ProductVariations\ServiceProvider;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

define('PRODUCT_VARIATION_EXIM_CODE_FIELD', 'Variation group code');
define('PRODUCT_VARIATION_EXIM_ID_FIELD', 'Variation group id');
define('PRODUCT_VARIATION_EXIM_PARENT_PRODUCT_ID', 'Variation parent product id');
define('PRODUCT_VARIATION_EXIM_DEFAULT_VARIATION', 'Variation set as default');
define('PRODUCT_VARIATION_EXIM_SUB_GROUP_ID', 'Variation sub group id');
define('PRODUCT_VARIATION_EXIM_FEATURE_FIELD_TEMPLATE', '%s (Variation feature)');

/**
 * phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
 *
 * Product variations pre processing
 *
 * @param array<string, mixed>           $pattern       Pattern
 * @param array<array-key, string>       $export_fields Export fields
 * @param array<string, string|int|bool> $options       Options
 *
 * @return void
 *
 * phpcs:enable
 */
function fn_product_variations_exim_pre_processing(array &$pattern, array $export_fields, array $options = [])
{
    $feature_ids = [];

    if (isset($options['fields_names']) && $options['fields_names']) {
        $export_fields = array_keys($export_fields);
    }

    foreach ($export_fields as $field) {
        if (isset($pattern['export_fields'][$field]['feature_id'])) {
            $feature_id = (int) $pattern['export_fields'][$field]['feature_id'];
            $feature_ids[$feature_id] = $feature_id;
        }
    }

    Registry::set('runtime.exim.exported_variation_feature_ids', $feature_ids);

    if (!in_array(PRODUCT_VARIATION_EXIM_CODE_FIELD, $export_fields, true)
        && !in_array(PRODUCT_VARIATION_EXIM_ID_FIELD, $export_fields, true)
        && !isset($export_fields[PRODUCT_VARIATION_EXIM_CODE_FIELD])
        && !isset($export_fields[PRODUCT_VARIATION_EXIM_ID_FIELD])
        && empty($pattern['is_data_feeds'])
    ) {
        return;
    }

    $pattern['references'][GroupRepository::TABLE_GROUP_PRODUCTS] = [
        'reference_fields'          => [
            'product_id' => '#key'
        ],
        'join_type'                 => 'LEFT',
        'import_skip_db_processing' => true
    ];

    $pattern['references'][GroupRepository::TABLE_GROUPS] = [
        'reference_fields'          => [
            'id' => sprintf('#%s.group_id', GroupRepository::TABLE_GROUP_PRODUCTS)
        ],
        'join_type'                 => 'LEFT',
        'import_skip_db_processing' => true
    ];

    $pattern['export_fields'][PRODUCT_VARIATION_EXIM_CODE_FIELD] = [
        'table'    => GroupRepository::TABLE_GROUPS,
        'db_field' => 'code',
    ];

    $pattern['export_fields'][PRODUCT_VARIATION_EXIM_ID_FIELD] = [
        'table'    => GroupRepository::TABLE_GROUPS,
        'db_field' => 'id',
    ];

    $pattern['export_fields'][PRODUCT_VARIATION_EXIM_PARENT_PRODUCT_ID] = [
        'table'    => GroupRepository::TABLE_GROUP_PRODUCTS,
        'db_field' => 'parent_product_id',
    ];
}

function fn_product_variations_exim_pre_export_process($pattern, &$table_fields)
{
    if (!isset($pattern['references'][GroupRepository::TABLE_GROUPS])) {
        return;
    }

    $field_id = sprintf('%s.id', GroupRepository::TABLE_GROUPS);
    $field_code = sprintf('%s.code', GroupRepository::TABLE_GROUPS);
    $field_product_id = sprintf('%s.product_id', GroupRepository::TABLE_GROUP_PRODUCTS);
    $field_parent_id = sprintf('%s.parent_product_id', GroupRepository::TABLE_GROUP_PRODUCTS);
    $field_default_variation = sprintf('%s.variation_set_as_default', GroupRepository::TABLE_GROUP_PRODUCTS);
    $field_sub_group_id = sprintf('%s.variation_sub_group_id', GroupRepository::TABLE_GROUP_PRODUCTS);

    $table_fields[$field_id] = sprintf("%s AS '%s'", $field_id, 'variation_group_id');
    $table_fields[$field_code] = sprintf("%s AS '%s'", $field_code, 'variation_group_code');
    $table_fields[$field_parent_id] = sprintf("%s AS '%s'", $field_parent_id, 'variation_parent_product_id');
    $table_fields[$field_default_variation] = sprintf("(CASE WHEN %s THEN 'N' ELSE 'Y' END) AS '%s'", $field_parent_id, 'variation_set_as_default');
    $table_fields[$field_sub_group_id] = sprintf("CONCAT(%s, '_', (CASE WHEN %s THEN %s ELSE %s END)) AS '%s'",
        $field_id,
        $field_parent_id,
        $field_parent_id,
        $field_product_id,
        'variation_sub_group_id'
    );

}

function fn_product_variations_exim_get_features()
{
    [$features] = fn_get_product_features([
        'exclude_group' => true,
        'purpose'       => FeaturePurposes::getAll(),
        'feature_types' => [
            ProductFeatures::TEXT_SELECTBOX,
            ProductFeatures::NUMBER_SELECTBOX
        ],
        'statuses'      => [
            ObjectStatuses::ACTIVE,
            ObjectStatuses::HIDDEN
        ]
    ], 0, DEFAULT_LANGUAGE);

    return $features;
}

function fn_product_variations_exim_set_variation_group_code($row, $key)
{
    if (isset($row[$key])) {
        return trim($row[$key]);
    } else {
        return null;
    }
}

function fn_product_variations_exim_post_processing($primary_object_ids, $import_data, $processed_data, &$final_import_notification)
{
    $has_variation_group_code = false;

    foreach ($import_data as $item) {
        $item = reset($item);

        if (isset($item['variation_group_code'])) {
            $has_variation_group_code = true;
        }

        if ($has_variation_group_code) {
            break;
        }
    }

    if ($has_variation_group_code) {
        fn_product_variations_exim_update_variation_groups($primary_object_ids, $import_data, $processed_data, $final_import_notification);
    }

    fn_product_variations_exim_update_default_variations($primary_object_ids, $import_data, $processed_data, $final_import_notification);
    fn_product_variations_exim_sync_variations($primary_object_ids);
}

function fn_product_variations_exim_update_variation_groups($primary_object_ids, $import_data, $processed_data, &$final_import_notification)
{
    $group_repository = ServiceProvider::getGroupRepository();
    $service = ServiceProvider::getService();

    $product_ids = array_filter(array_column($primary_object_ids, 'product_id'));

    $products_group_info = $group_repository->findGroupInfoByProductIds($product_ids);

    $default_product_ids = [];
    $product_group_ids = [];
    $on_remove_list = [];
    $on_update_list = [];
    $on_move_list = [];
    $on_create_list = [];
    $products_feature_values = [];
    $counter = [
        'created' => 0,
        'removed' => 0,
        'updated' => 0
    ];
    $warning_messages = [];

    foreach ($import_data as $key => $items) {
        if (empty($primary_object_ids[$key]['product_id'])) {
            continue;
        }

        $product = reset($items);
        $product_id = $primary_object_ids[$key]['product_id'];
        $variation_group = isset($products_group_info[$product_id]) ? $products_group_info[$product_id] : null;
        $variation_set_as_default = $product['variation_set_as_default'] == YesNo::YES;
        if ($variation_set_as_default) {
            $default_product_ids[$product_id] = $product_id;
        }

        $product['variation_group'] = $variation_group;
        $product['product_id'] = $product_id;

        if (empty($product['variation_group_code']) && !empty($variation_group)) {
            $on_remove_list[$variation_group['id']][$product_id] = $product_id;
        } elseif (!empty($product['variation_group_code']) && empty($variation_group)) {
            $on_create_list[$product['variation_group_code']][$product_id] = $product;
        } elseif (!empty($product['variation_group_code']) && !empty($variation_group)
            && $product['variation_group_code'] === $variation_group['code']
        ) {
            $on_update_list[$variation_group['id']][$product_id] = $product;
        } elseif (!empty($product['variation_group_code']) && !empty($variation_group)
            && $product['variation_group_code'] !== $variation_group['code']
        ) {
            $on_move_list[$product['variation_group_code']][$product_id] = $product;
        }

        $product_features = isset($product['product_features']) ? $product['product_features'] : [];
        $products_feature_values[$product_id] = $product_features;
    }

    foreach ($on_remove_list as $group_id => $product_ids) {
        $result = $service->detachProductsFromGroup($group_id, $product_ids);

        $warning_messages = fn_product_variations_exim_get_warnings($result, $warning_messages);

        $counter['removed'] += count($product_ids);
    }

    foreach ($on_move_list as $group_code => $products) {
        $group_id = $group_repository->findGroupIdByCode($group_code);

        if ($group_id) {
            $result = $service->moveProductsToGroup($group_id, array_keys($products), array_intersect_key($products_feature_values, $products));
        } else {
            $result = $service->moveProductsToNewGroup($group_code, array_keys($products), array_intersect_key($products_feature_values, $products));
        }

        $product_group = $result->getData('group', []);
        $product_group_id  = $product_group ? $product_group->getId() : 0;

        $warning_messages = fn_product_variations_exim_get_warnings($result, $warning_messages);

        foreach ($result->getData('products_status', []) as $product_id => $status) {
            if (!Group::isResultError($status)) {
                if ($product_group_id) {
                    $product_group_ids[$product_id] = $product_group_id;
                }
                $counter['updated']++;
            }
        }
    }

    foreach ($on_update_list as $group_id => $products) {
        $result = $service->changeProductsFeatureValues($group_id, array_intersect_key($products_feature_values, $products));

        $warning_messages = fn_product_variations_exim_get_warnings($result, $warning_messages);

        foreach ($result->getData('products_status', []) as $product_id => $status) {
            if (!Group::isResultError($status)) {
                $product_group_ids[$product_id] = $group_id;
                $counter['updated']++;
            }
        }
    }

    foreach ($on_create_list as $group_code => $products) {
        $group_id = $group_repository->findGroupIdByCode($group_code);

        if ($group_id) {
            $result = $service->attachProductsToGroup($group_id, array_keys($products));
        } else {
            $group_product_ids = array_keys($products);

            if ($default_product_ids) {
                usort($group_product_ids, static function ($a, $b) use ($default_product_ids) {
                    if (isset($default_product_ids[$a])) {
                        return -1;
                    }
                    if (isset($default_product_ids[$b])) {
                        return 1;
                    }
                    return 0;
                });
            }
            if (count($products) <= 1) {
                continue;
            }

            $result = $service->createGroup($group_product_ids, $group_code);
        }

        $product_group = $result->getData('group', []);
        $product_group_id  = $product_group ? $product_group->getId() : 0;

        $warning_messages = fn_product_variations_exim_get_warnings($result, $warning_messages);

        foreach ($result->getData('products_status', []) as $product_id => $status) {
            if (!Group::isResultError($status)) {
                if ($product_group_id) {
                    $product_group_ids[$product_id] = $product_group_id;
                }
                $counter['created']++;
            }
        }
    }

    $default_product_was_changed = false;

    foreach ($default_product_ids as $default_product_id) {
        if (empty($product_group_ids[$default_product_id])) {
            continue;
        }

        $result = $service->setDefaultProduct($product_group_ids[$default_product_id], $default_product_id);

        if ($default_product_was_changed) {
            continue;
        }

        $default_product_was_changed = $result->isSuccess();
    }

    if ($default_product_was_changed) {
        $product_data = fn_product_variations_exim_get_product_data_for_variations_mapping($product_ids);
        ServiceProvider::getProductIdMap()->setParentProductIdMapByProducts($product_data);
    }

    $final_import_notification = __('product_variations.exim.result_notice', [
        '[new]'               => $processed_data['N'],
        '[exist]'             => $processed_data['E'],
        '[skipped]'           => $processed_data['S'],
        '[total]'             => $processed_data['E'] + $processed_data['N'] + $processed_data['S'],
        '[variation_created]' => $counter['created'],
        '[variation_updated]' => $counter['updated'],
        '[variation_removed]' => $counter['removed'],
    ]);

    if (!count($warning_messages)) {
        return;
    }

    $smarty = Tygh::$app['view'];
    $smarty->assign('messages', $warning_messages);
    fn_set_notification(
        NotificationSeverity::INFO,
        __('warning'),
        $smarty->fetch('addons/product_variations/views/product_variations/components/export_warnings.tpl')
    );
}

function fn_product_variations_exim_update_default_variations($primary_object_ids, $import_data, $processed_data, &$final_import_notification)
{
    $service = ServiceProvider::getService();
    $product_id_map = ServiceProvider::getProductIdMap();

    $product_ids = array_filter(array_column($primary_object_ids, 'product_id'));
    $product_id_map->addProductIdsToPreload($product_ids);

    foreach ($import_data as $key => $item) {
        if (empty($primary_object_ids[$key]['product_id'])) {
            continue;
        }

        $product_id = $primary_object_ids[$key]['product_id'];
        $item = reset($item);

        if (!isset($item['amount']) && !isset($item['status'])) {
            continue;
        }

        if (isset($item['amount'])) {
            $service->onChangedProductQuantity($product_id);
        }

        /**
         * @phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
         */
        if (isset($item['status'])) {
            $service->onChangedVariationProductState($product_id);
        }
    }
}

function fn_product_variations_exim_sync_variations($primary_object_ids)
{
    $parent_product_ids = [];
    $product_ids = array_filter(array_column($primary_object_ids, 'product_id'));

    if (empty($product_ids)) {
        return;
    }

    $service = ServiceProvider::getSyncService();

    $product_id_map = ServiceProvider::getProductIdMap();
    $product_id_map->addProductIdsToPreload($product_ids);

    foreach ($product_ids as $product_id) {
        if ($product_id_map->isParentProduct($product_id)) {
            $parent_product_ids[$product_id] = $product_id;
        } elseif ($parent_product_id = $product_id_map->getParentProductId($product_id)) {
            $parent_product_ids[$parent_product_id] = $parent_product_id;
        }
    }

    foreach ($parent_product_ids as $product_id) {
        $child_product_ids = $product_id_map->getProductChildrenIds($product_id);

        if (empty($child_product_ids)) {
            continue;
        }

        $service->syncAll($product_id, $child_product_ids, [
            ProductRepository::TABLE_PRODUCTS,
            ProductRepository::TABLE_PRODUCT_DESCRIPTIONS,
            ProductRepository::TABLE_PRODUCTS_CATEGORIES,
            ProductRepository::TABLE_IMAGE_LINKS,
            ProductRepository::TABLE_PRODUCT_FEATURE_VALUES,
            ProductRepository::TABLE_PRODUCT_GLOBAL_OPTION_LINKS
        ]);
    }
}

/**
 * Gets value of feature variant
 *
 * @param int    $product_id Product ID
 * @param int    $feature_id Feature ID
 * @param string $lang_code  Lang code
 *
 * @return array<int, string>|null
 */
function fn_product_variations_exim_get_variation_feature_value($product_id, $feature_id, $lang_code = CART_LANGUAGE)
{
    static $feature_ids;
    static $products_feature_values = [];

    if ($feature_ids === null) {
        $feature_ids = Registry::get('runtime.exim.exported_variation_feature_ids');
    }

    if (!in_array($feature_id, $feature_ids)) {
        return null;
    }

    if (!isset($products_feature_values[$product_id])) {
        $query = ServiceProvider::getQueryFactory()->createQuery(
            ProductRepository::TABLE_PRODUCT_FEATURE_VALUES,
            ['product_id' => $product_id, 'feature_id' => $feature_ids, 'lang_code' => $lang_code],
            ['fvd.variant', 'pfv.feature_id'],
            'pfv'
        );

        $query->addInnerJoin('fvd', ProductRepository::TABLE_PRODUCT_FEATURE_VARIANT_DESCRIPTIONS, ['variant_id' => 'variant_id'], ['lang_code' => $lang_code]);

        $products_feature_values[$product_id] = $query->column(['feature_id', 'variant']);
    }

    return isset($products_feature_values[$product_id][$feature_id]) ? $products_feature_values[$product_id][$feature_id] : null;
}

function fn_product_variations_exim_get_variation_sub_group_id($data)
{
    return $data['variation_sub_group_id'];
}

function fn_product_variations_exim_get_variation_set_as_default($data)
{
    return $data['variation_group_id'] ? $data['variation_set_as_default'] : '';
}

function fn_product_variations_exim_set_variation_set_as_default($row, $key)
{
    if (isset($row[$key])) {
        return trim($row[$key]);
    } else {
        return null;
    }
}

/**
 * Gets warning and error operation messages
 *
 * @param OperationResult $result   Operation result
 * @param string[]        $messages Existing messages
 *
 * @return string[]
 */
function fn_product_variations_exim_get_warnings(OperationResult $result, array $messages = [])
{
    if ($result->hasErrors()) {
        $errors = $result->getErrors();
        foreach ($errors as $error) {
            $messages[] = $error;
        }
    }

    if ($result->hasWarnings()) {
        $warnings = $result->getWarnings();
        foreach ($warnings as $warning) {
            $messages[] = $warning;
        }
    }

    return $messages;
}

/**
 * Gets the product type and parent product ID
 *
 * @param array<int> $product_ids List of product identifiers
 *
 * @return array<int, array<string, int|string>>
 */
function fn_product_variations_exim_get_product_data_for_variations_mapping(array $product_ids)
{
    return db_get_array(
        'SELECT product_id, product_type, parent_product_id FROM ?:products WHERE product_id IN (?n)',
        $product_ids
    );
}
