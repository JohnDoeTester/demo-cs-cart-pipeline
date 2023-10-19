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

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Addons\MasterProducts\ServiceProvider;
use Tygh\Addons\ProductVariations\Product\Group\Events\ParentProductChangedEvent as VariationParentProductChangedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductAddedEvent as VariationProductAddedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductRemovedEvent as VariationProductRemovedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductUpdatedEvent as VariationProductUpdatedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Group as VariationGroup;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection;
use Tygh\Addons\ProductVariations\Service as VariationService;
use Tygh\Addons\ProductVariations\ServiceProvider as VariationsServiceProvider;
use Tygh\BlockManager\Block;
use Tygh\BlockManager\ProductTabs;
use Tygh\Common\OperationResult;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\OutOfStockActions;
use Tygh\Enum\ProductFilterProductFieldTypes;
use Tygh\Enum\ProductTracking;
use Tygh\Enum\SiteArea;
use Tygh\Enum\VendorStatuses;
use Tygh\Enum\YesNo;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Storefront\Storefront;
use Tygh\Enum\NotificationSeverity;

// phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed

/**
 * Installs the add-on products block and the product tab.
 */
function fn_master_products_install()
{
    $company_ids = [0];

    /** @var \Tygh\BlockManager\Block $block */
    $block = Block::instance();
    /** @var ProductTabs $product_tabs */
    $product_tabs = ProductTabs::instance();

    foreach ($company_ids as $company_id) {
        $block_data = [
            'type'         => 'products',
            'properties'   => [
                'template' => 'addons/master_products/blocks/products/vendor_products.tpl',
            ],
            'content_data' => [
                'content' => [
                    'items' => [
                        'filling' => 'master_products.vendor_products_filling',
                        'limit'   => '0',
                    ],
                ],
            ],
            'company_id'   => $company_id,
        ];

        $block_description = [
            'lang_code' => DEFAULT_LANGUAGE,
            'name'      => __('master_products.vendor_products_block_name', [], DEFAULT_LANGUAGE),
        ];

        $block_id = $block->update($block_data, $block_description);

        $tab_data = [
            'tab_type'      => 'B',
            'block_id'      => $block_id,
            'template'      => '',
            'addon'         => 'master_products',
            'status'        => 'A',
            'is_primary'    => 'N',
            'position'      => 0,
            'product_ids'   => null,
            'company_id'    => $company_id,
            'show_in_popup' => 'N',
            'lang_code'     => DEFAULT_LANGUAGE,
            'name'          => __('master_products.vendor_products_tab_name', [], DEFAULT_LANGUAGE),
        ];

        $product_tabs->update($tab_data);
    }
}

/**
 * Filters master products by vendor filter on storefront.
 *
 * @param array $params   Request params
 *
 * @return array
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_master_products_filter_master_products_by_vendor(array $params)
{
    $join = '';
    $condition = '';

    if (isset($params['vendor_location_region']) || isset($params['vendor_location_area'])) {
        $join = db_quote(' LEFT JOIN ?:products AS master_products ON products.product_id = master_products.master_product_id');

        return [$join, $condition];
    }

    if (!isset($params['filter_params']['company_id']) || !in_array(0, $params['filter_params']['company_id'])) {
        return [$join, $condition];
    }

    $vendor_ids = [];
    foreach ($params['filter_params']['company_id'] as $company_id) {
        if ($company_id !== 0) {
            $vendor_ids[] = $company_id;
        }
    }
    if (empty($vendor_ids)) {
        return [$join, $condition];
    }

    $join = db_quote(' LEFT JOIN ?:products AS master_products ON products.product_id = master_products.master_product_id');
    $condition = db_quote(' AND master_products.company_id IN (?n) AND master_products.status = ?s', $vendor_ids, ObjectStatuses::ACTIVE);

    return [$join, $condition];
}

/**
 * Hook handler: adds extra products search parameters.
 *
 * @param array  $params         Products search params
 * @param int    $items_per_page Amount of products shown per page
 * @param string $lang_code      Two-letter language code for product descriptions
 */
function fn_master_products_get_products_pre(&$params, $items_per_page, $lang_code)
{
    $params = array_merge([
        'show_master_products_only'  => false,
        'area'                       => AREA,
        'include_child_variations'   => true,
    ], $params);

    $params['runtime_company_id'] = (int) Registry::ifGet('runtime.vendor_id', Registry::get('runtime.company_id'));

    // Vendors must see only active master products
    if ($params['show_master_products_only'] && $params['runtime_company_id']) {
        $params['status'] = ObjectStatuses::ACTIVE;
    }

    // FIXME: Product offers must be displayed only in the blocks with the specific filling
    if (
        isset($params['block_data']['content']['items']['filling'], $params['block_data']['type'])
        && $params['block_data']['type'] === 'products'
        && $params['block_data']['content']['items']['filling'] !== 'master_products.vendor_products_filling'
    ) {
        $params['runtime_company_id'] = 0;
    }
}

/**
 * Hook handler: modifies products obtaining process to include vendor products into the list.
 */
function fn_master_products_get_products(
    &$params,
    &$fields,
    $sortings,
    &$condition,
    &$join,
    $sorting,
    &$group_by,
    $lang_code,
    $having
) {
    $condition_replacements = [];

    if (
        SiteArea::isStorefront($params['area'])
        || !empty($params['selecting_for_customer_area'])
    ) {
        $condition .= db_quote(' AND products.master_product_status IN (?a)', ['A']);

        if (isset($params['company_status']) && !empty($params['company_status'])) {
            $search = db_quote('AND companies.status IN (?a)', $params['company_status']);
            $replace = db_quote('AND (companies.status IN (?a) OR products.company_id = ?i)', $params['company_status'], 0);
        } else {
            $search = db_quote('AND companies.status = ?s', ObjectStatuses::ACTIVE);
            $replace = db_quote('AND (companies.status = ?s OR products.company_id = ?i)', ObjectStatuses::ACTIVE, 0);
        }

        $condition_replacements[$search] = $replace;

        if (!empty($params['vendor_products_by_product_id'])) {
            $repository = ServiceProvider::getProductRepository();

            $master_product_id = $repository->findMasterProductId($params['vendor_products_by_product_id']);

            if (!$master_product_id) {
                $master_product_id = (int) $params['vendor_products_by_product_id'];
            }

            $condition .= db_quote(' AND products.master_product_id = ?i', $master_product_id);
        } elseif (empty($params['runtime_company_id']) && empty($params['pid']) && empty($params['only_for_counting'])) {
            /** @var \Tygh\Storefront\Storefront $storefront */
            $storefront = $params['storefront'] instanceof Storefront
                ? $params['storefront']
                : Tygh::$app['storefront'];

            [$vendor_filter_join, $vendor_filter_condition] = fn_master_products_filter_master_products_by_vendor($params);

            $join .= db_quote(
                ' LEFT JOIN ?:master_products_storefront_offers_count AS master_products_storefront_offers_count '
                . ' ON master_products_storefront_offers_count.product_id = products.product_id'
                .   ' AND master_products_storefront_offers_count.storefront_id = ?i ?p',
                $storefront->storefront_id,
                $vendor_filter_join
            );

            if (!isset($_REQUEST['is_microstore'])) {
                $condition .= db_quote(' AND products.master_product_id = 0');
            }

            $condition .= db_quote(
                ' AND (products.company_id > 0 OR (master_products_storefront_offers_count.count > 0 ?p))',
                $vendor_filter_condition
            );

            if ($storefront->getCompanyIds()) {
                $search = db_quote('companies.company_id IN (?n)', $storefront->getCompanyIds());
                $replace = db_quote('(companies.company_id IN (?n) OR products.company_id = ?i)', $storefront->getCompanyIds(), 0);
                $condition_replacements[$search] = $replace;
            }

            if (!empty($fields['price'])) {
                $fields['price'] = 'COALESCE(?:master_products_storefront_min_price.price,'
                    . ' MIN(IF('
                    . 'prices.percentage_discount = 0,'
                    . 'prices.price,'
                    . 'prices.price - (prices.price * prices.percentage_discount)/100))'
                    . ') as price';

                $join .= db_quote(
                    ' LEFT JOIN ?:master_products_storefront_min_price'
                    . ' ON ?:master_products_storefront_min_price.product_id = prices.product_id'
                    . ' AND ?:master_products_storefront_min_price.storefront_id = ?i',
                    $storefront->storefront_id
                );
            }
        }
    }

    if ($params['show_master_products_only']) {
        if ($params['runtime_company_id']) {
            $search = db_quote(' AND products.company_id = ?i', $params['runtime_company_id']);
            $replace = db_quote(' AND products.company_id = ?i', 0);
            $condition_replacements[$search] = $replace;
        } else {
            $condition .= db_quote(' AND products.company_id = ?i', 0);
        }
    } elseif ($params['area'] === 'A'
        && empty($params['pid'])
        && empty($params['show_all_products'])
        && empty($params['has_not_variation_group'])
        && !isset($params['master_product_id'])
        && (!isset($params['parent_product_id']) || empty($params['parent_product_id']))
        && !isset($params['variation_group_id'])
        && empty($params['cid'])
        && empty($params['is_picker'])
        && !isset($params['for_current_storefront'])
    ) {
        $condition .= db_quote(' AND products.company_id <> ?i ', 0);
    }

    if (!empty($params['remove_company_condition'])) {
        $search = db_quote(' AND products.company_id = ?i', $params['runtime_company_id']);
        $condition_replacements[$search] = '';
    }

    if (!empty($params['master_product_id'])) {
        if (is_array($params['master_product_id'])) {
            $condition .= db_quote(' AND products.master_product_id IN (?n)', $params['master_product_id']);
        } else {
            $condition .= db_quote(' AND products.master_product_id = ?i', $params['master_product_id']);
        }
    }

    // FIXME: Dirty hack
    if ($condition_replacements) {
        $condition = strtr($condition, $condition_replacements);
    }

    $fields['master_product_offers_count'] = 'products.master_product_offers_count';
    $fields['master_product_id'] = 'products.master_product_id';
    $fields['company_id'] = 'products.company_id';

    return;
}

/**
 * The "get_products_post" hook handler.
 *
 * Actions performed:
 * - Change selected products data
 *
 * @param array $products Array of products
 * @param array $params   Request parameters
 *
 * @return void
 *
 * @see fn_get_products()
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_master_products_get_products_post(array &$products, array &$params)
{
    if (!SiteArea::isStorefront(AREA)) {
        return;
    }

    foreach ($products as &$product) {
        if (
            !empty($product['master_product_offers_count'])
            && empty($product['master_poduct_id'])
        ) {
            list($product['best_product_offer_id'], $best_product_offer_price) = fn_master_products_get_best_product_offer($product['product_id']);

            if ($best_product_offer_price === 0) {
                continue;
            }

            if (empty($product['company_id'])) {
                $product['price'] = $best_product_offer_price;
            }
        }
    }

    $products = fn_master_products_filter_master_products_quantities_for_current_storefront($products);
}

function fn_master_products_gather_additional_products_data_params($product_ids, &$params, &$products, $auth)
{
    if (!isset($params['get_vendor_products'])) {
        $params['get_vendor_products'] = false;
    }

    if (!isset($params['get_vendor_product_ids'])) {
        $params['get_vendor_product_ids'] = false;
    }

    if (!isset($params['runtime_company_id'])) {
        $params['runtime_company_id'] = (int) Registry::ifGet('runtime.vendor_id', Registry::get('runtime.company_id'));
    }

    if ($params['get_vendor_products'] && !empty($params['runtime_company_id'])) {
        list($vendor_products) = fn_get_products([
            'master_product_id' => $product_ids,
            'company_id'        => $params['runtime_company_id']
        ]);

        $product_id_map = array_column($vendor_products, 'product_id', 'master_product_id');

        foreach ($products as &$product) {
            $product_id = $product['product_id'];
            $vendor_product_id = isset($product_id_map[$product_id]) ? (int) $product_id_map[$product_id] : null;

            $product['vendor_product'] = isset($vendor_products[$vendor_product_id]) ? $vendor_products[$vendor_product_id] : null;
            $product['vendor_product_id'] = $vendor_product_id;
        }
        unset($product);
    }

    if ($params['get_vendor_product_ids'] && !empty($params['runtime_company_id'])) {
        $repository = ServiceProvider::getProductRepository();

        $product_id_map = $repository->findVendorProductIdsByMasterProductIds($product_ids, $params['runtime_company_id']);

        foreach ($products as &$product) {
            $product_id = $product['product_id'];
            $product['vendor_product_id'] = isset($product_id_map[$product_id]) ? (int) $product_id_map[$product_id] : null;
        }
        unset($product);
    }
}

function fn_master_products_gather_additional_products_data_post($product_ids, $params, $products, $auth, $lang_code)
{
    if (AREA !== 'C') {
        return;
    }

    $vendor_id = fn_master_products_get_runtime_company_id();

    if (empty($vendor_id)) {
        return;
    }

    $repository = VariationsServiceProvider::getProductRepository();
    $product_id_map = VariationsServiceProvider::getProductIdMap();

    $runtime_vendor_id = Registry::ifGet('runtime.vendor_id', null);
    foreach ($products as &$product) {
        if (empty($product['company_id'])
            || empty($product['master_product_id'])
            || empty($product['detailed_params']['info_type'])
            || $product['detailed_params']['info_type'] !== 'D'
        ) {
            continue;
        }

        $master_product_id = $product['master_product_id'];

        if (!$product_id_map->isChildProduct($master_product_id) && !$product_id_map->isParentProduct($master_product_id)) {
            continue;
        }

        $master_product = $repository->findProduct($master_product_id);

        if (!$master_product) {
            continue;
        }

        $master_product = $repository->loadProductGroupInfo($master_product);

        // swap runtime vendor ID to load master product data even when viewing a vendor product page
        Registry::set('runtime.vendor_id', (int) $master_product['company_id']);
        $master_product = $repository->loadProductFeaturesVariants($master_product);

        if (empty($master_product['variation_features_variants'])) {
            continue;
        }

        $product['variation_features_variants'] = $master_product['variation_features_variants'];
        $master_product_ids = [];

        foreach ($product['variation_features_variants'] as $features) {
            if (empty($features['variants'])) {
                continue;
            }

            foreach ($features['variants'] as $variant) {
                if (empty($variant['product'])) {
                    continue;
                }

                $master_product_ids[$variant['product']['product_id']] = $variant['product']['product_id'];
            }
        }

        if (!$master_product_ids) {
            continue;
        }

        $vendor_products_map = ServiceProvider::getProductRepository()->findVendorProductIdsByMasterProductIds($master_product_ids, $vendor_id, ['A']);

        if (empty($vendor_products_map)) {
            continue;
        }

        foreach ($product['variation_features_variants'] as &$features) {
            if (empty($features['variants'])) {
                continue;
            }

            foreach ($features['variants'] as &$variant) {
                if (empty($variant['product'])) {
                    continue;
                }

                $variant_product_id = $variant['product']['product_id'];

                if (empty($vendor_products_map[$variant_product_id])) {
                    continue;
                }

                $variant['product']['product_id'] = $vendor_products_map[$variant_product_id];
            }
            unset($variant);
        }
        unset($features);
    }
    unset($product);

    Registry::set('runtime.vendor_id', $runtime_vendor_id);
}

/**
 * Hook handler: adds master product ID into a list of fetched product fields.
 */
function fn_master_products_get_product_data($product_id, &$field_list, &$join, $auth, $lang_code, $condition)
{
    $field_replacements = [];

    $field_list .= ', ?:products.master_product_id, ?:products.master_product_status';

    $search = ', MIN(IF(?:product_prices.percentage_discount = 0, ?:product_prices.price,'
        . ' ?:product_prices.price - (?:product_prices.price * ?:product_prices.percentage_discount)/100)) as price';
    $replace = ', COALESCE(?:master_products_storefront_min_price.price, MIN(IF(?:product_prices.percentage_discount = 0, ?:product_prices.price,'
        . ' ?:product_prices.price - (?:product_prices.price * ?:product_prices.percentage_discount)/100))) as price';
    $field_replacements[$search] = $replace;

    // FIXME: Dirty hack
    if ($field_replacements) {
        $field_list = strtr($field_list, $field_replacements);
    }

    $storefront = StorefrontProvider::getStorefront();

    $join .= db_quote(
        ' LEFT JOIN ?:master_products_storefront_min_price'
        . ' ON ?:master_products_storefront_min_price.product_id = ?:products.product_id'
        .   ' AND ?:master_products_storefront_min_price.storefront_id = ?i',
        $storefront->storefront_id
    );
}

/**
 * Fetches company ID from any passed object or runtime.
 * FIXME: Obtaining company_id from the $_REQUEST is ugly. Must be redone.
 *
 * @param array|null $object Object to extract company_id from
 * @param string     $area   Site area
 *
 * @return int Company ID
 */
function fn_master_products_get_runtime_company_id($object = null, $area = AREA)
{
    if ($object === null && $area === 'C') {
        // FIXME
        $object = $_REQUEST;
    }

    static $runtime_company_id;

    if (isset($object['vendor_id'])) {
        return (int) $object['vendor_id'];
    }

    if ($runtime_company_id === null) {
        $runtime_company_id = (int) Registry::ifGet('runtime.vendor_id', Registry::get('runtime.company_id'));
    }

    return $runtime_company_id;
}

/**
 * Helper function that generates sidebar menu with master and vendor products on the products management pages.
 *
 * @param string $controller     Currently dispatched controller
 * @param string $mode           Currently dispatched controller mode
 * @param array  $request_params Additional params from request
 */
function fn_master_products_generate_navigation_sections($controller, $mode, $request_params = [])
{
    $active_section = '';

    if (empty($request_params['cid'])) {
        $active_section = $controller . '.' . $mode;
    }

    $dynamic_sections = Registry::ifGet('navigation.dynamic.sections', []);

    $dynamic_sections['products.manage'] = [
        'title' => __('master_products.products_being_sold'),
        'href'  => 'products.manage',
    ];
    $dynamic_sections['products.master_products'] = [
        'title' => __('master_products.products_that_vendors_can_sell'),
        'href'  => 'products.master_products',
    ];

    Registry::set('navigation.dynamic.sections', $dynamic_sections);
    if (!Registry::get('navigation.dynamic.active_section')) {
        Registry::set('navigation.dynamic.active_section', $active_section);
    }
}

/**
 * Hook handler: allows viewing master products.
 */
function fn_master_products_company_products_check(&$product_ids, $notify, &$company_condition)
{
    $controller = Registry::ifGet('runtime.controller', 'products');
    $mode = Registry::ifGet('runtime.mode', 'update');
    $request_method = isset($_SERVER['REQUEST_METHOD']) // FIXME
        ? $_SERVER['REQUEST_METHOD']
        : 'GET';

    if ($controller !== 'products' || $request_method !== 'GET' || !in_array($mode, ['update', 'update_file', 'update_folder'])) {
        return;
    }

    $company_condition = fn_get_company_condition(
        '?:products.company_id',
        true,
        Registry::get('runtime.company_id'),
        true
    );
}

/**
 * Hook handler: allows viewing master products.
 */
function fn_master_products_is_product_company_condition_required_post($product_id, &$is_required)
{
    $product_company_id = (int) db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);

    if ($product_company_id === 0) {
        $is_required = false;
    }
}

/**
 * Hook handler: updates vendor products descriptions when editing a master product.
 */
function fn_master_products_update_product_post($product_data, $product_id, $lang_code, $create)
{
    if ($create) {
        return;
    }

    $product_id_map = ServiceProvider::getProductIdMap();

    if (!$product_id_map->isMasterProduct($product_id) && !$product_id_map->isVendorProduct($product_id)) {
        return;
    }

    $service = ServiceProvider::getService();

    $service->onTableChanged('products', $product_id);
    $service->onTableChanged('product_descriptions', $product_id);
    $service->onTableChanged('product_status', $product_id);
    $service->onTableChanged('product_popularity', $product_id);

    $service->actualizeMasterProductPrice($product_id);
    $service->actualizeMasterProductOffersCount($product_id);
    $service->actualizeMasterProductQuantity($product_id);
}

/**
 * Hook handler: prevents vendor product categories update.
 */
function fn_master_products_update_product_categories_pre($product_id, &$product_data, $rebuild, $company_id)
{
    if (empty($product_data['category_ids'])) {
        return;
    }

    $repository = ServiceProvider::getProductRepository();

    if ($repository->findMasterProductId($product_id)) {
        $product_data['category_ids'] = [];
    }
}

/**
 * Hook handler: updates vendor products categories when editing a master product.
 */
function fn_master_products_update_product_categories_post(
    $product_id,
    $product_data,
    $existing_categories,
    $rebuild,
    $company_id
) {
    $service = ServiceProvider::getService();
    $service->onTableChanged('products_categories', $product_id);
}

/**
 * Hook handler: actualizers master product price on vendor product removal.
 */
function fn_master_products_delete_product_pre($product_id, $status)
{
    Registry::del('master_products.removed_product');

    if (!$status) {
        return;
    }

    $repository = ServiceProvider::getProductRepository();

    $master_product_id = $repository->findMasterProductId($product_id);

    if ($master_product_id) {
        Registry::set('master_products.removed_product.master_product_id', $master_product_id, true);
    }
}

/**
 * Hook handler: removes vendor products after master product removal.
 */
function fn_master_products_delete_product_post($product_id, $is_deleted)
{
    if (!$is_deleted) {
        return;
    }

    $repository = ServiceProvider::getProductRepository();
    $service = ServiceProvider::getService();
    $indexer = ServiceProvider::getIndexer();

    $vendor_product_ids = $repository->findVendorProductIds($product_id);

    foreach ($vendor_product_ids as $vendor_product_id) {
        fn_delete_product($vendor_product_id);
    }

    $master_product_id = Registry::get('master_products.removed_product.master_product_id');

    if ($master_product_id) {
        $service->actualizeMasterProductPrice($master_product_id, $is_deleted);
        $service->actualizeMasterProductOffersCount($master_product_id);
        $service->actualizeMasterProductQuantity($master_product_id);
    } else {
        $indexer->clearStorefrontOffersCountIndexByProductId($product_id);
        $indexer->clearStorefrontMinPriceIndexByProductId($product_id);
    }
    Registry::del('master_products.removed_product');
}


/**
 * Hook handler: actualizes master product price when the disabling/enabling vendor products.
 */
function fn_master_products_tools_change_status($params, $result)
{
    if (!$result || $params['table'] !== 'products') {
        return;
    }

    $product_id = $params['id'];

    $product_id_map = ServiceProvider::getProductIdMap();

    if (!$product_id_map->isMasterProduct($product_id) && !$product_id_map->isVendorProduct($product_id)) {
        return;
    }

    $service = ServiceProvider::getService();

    $service->onTableChanged('product_status', $product_id);
    $service->actualizeMasterProductPrice($product_id);
    $service->actualizeMasterProductOffersCount($product_id);
    $service->actualizeMasterProductQuantity($product_id);
}

function fn_master_products_product_type_create_by_product($product, $product_id, &$type)
{
    if (!empty($product['master_product_id'])) {
        $type = PRODUCT_TYPE_VENDOR_PRODUCT_OFFER;
    }
    if (!empty($product['master_product_id']) && !empty($product['parent_product_id'])) {
        $type = PRODUCT_TYPE_PRODUCT_OFFER_VARIATION;
    }
}

/**
 * Hook handler: normalize request for children products
 */
function fn_master_products_get_route(&$req, &$result, $area, &$is_allowed_url)
{
    if ($area !== 'C'
        || empty($req['dispatch'])
        || $req['dispatch'] !== 'products.view'
        || empty($req['product_id'])
        || empty($req['vendor_id'])
    ) {
        return;
    }

    $repository = ServiceProvider::getProductRepository();

    if (isset($req['variation_id'])) {
        $vendor_product_id = $repository->findVendorProductId($req['variation_id'], $req['vendor_id']);

        if (!$vendor_product_id) {
            return;
        }

        $product_id_map = VariationsServiceProvider::getProductIdMap();

        $parent_product_id = $product_id_map->getParentProductId($vendor_product_id);

        if ($parent_product_id) {
            $req['variation_id'] = $vendor_product_id;
            $req['product_id'] = $parent_product_id;
        } else {
            $req['product_id'] = $vendor_product_id;
            unset($req['variation_id']);
        }
    } else {
        $vendor_product_id = $repository->findVendorProductId($req['product_id'], $req['vendor_id']);

        if (!$vendor_product_id) {
            return;
        }

        $req['product_id'] = $vendor_product_id;
    }
}

/**
 * Hook handler: sync global options
 */
function fn_master_products_add_global_option_link_post($product_id, $option_id)
{
    $sync_service = ServiceProvider::getService();
    $sync_service->onTableChanged('product_global_option_links', $product_id, ['option_id' => $option_id]);
}

/**
 * Hook handler: sync global options
 */
function fn_master_products_delete_global_option_link_post($product_id, $option_id)
{
    $sync_service = ServiceProvider::getService();
    $sync_service->onTableChanged('product_global_option_links', $product_id, ['option_id' => $option_id]);
}

/**
 * Hook handler: sync feature values
 */
function fn_master_products_update_product_features_value_post($product_id)
{
    $sync_service = ServiceProvider::getService();
    $sync_service->onTableChanged('product_features_values', $product_id);
}

function fn_master_products_clone_product_data($product_id, &$data, $is_cloning_allowed)
{
    if (empty($data)) {
        return;
    }

    unset(
        $data['master_product_id'],
        $data['master_product_status'],
        $data['master_product_offers_count']
    );
}

function fn_master_products_variation_group_create_products_by_combinations_item($service, $parent_product_id, $combination_id, $combination, &$product_data)
{
    if (empty($product_data)) {
        return;
    }

    unset(
        $product_data['master_product_id'],
        $product_data['master_product_status'],
        $product_data['master_product_offers_count']
    );
}

/**
 * @param \Tygh\Addons\ProductVariations\SyncService $sync_service
 * @param array $events
 */
function fn_master_products_variation_sync_flush_sync_events($sync_service, $events)
{
    $product_ids = [];
    $table_product_ids = [];

    foreach ($events as $event) {
        if (empty($event['destination_product_ids'])
            || empty($event['table_id'])
        ) {
            continue;
        }

        foreach ($event['destination_product_ids'] as $product_id) {
            $product_ids[$product_id] = $product_id;
            $table_product_ids[$product_id][$event['table_id']] = $event['table_id'];
        }
    }

    if (empty($product_ids)) {
        return;
    }

    $product_repository = ServiceProvider::getProductRepository();
    $service = ServiceProvider::getService();

    $vendor_product_ids_map = $product_repository->findVendorProductIdsByMasterProductIds($product_ids);

    foreach ($vendor_product_ids_map as $master_product_id => $vendor_product_ids) {
        foreach ($table_product_ids[$master_product_id] as $table_id) {
            $service->syncData($table_id, $master_product_id, (array) $vendor_product_ids);
        }
    }
}

function fn_master_products_get_attachments_pre($object_type, &$object_id, $type, $lang_code)
{
    if ($object_type !== 'product' || !empty($params['skip_check_vendor_product'])) {
        return;
    }

    $product_repository = ServiceProvider::getProductRepository();

    $master_product_id = $product_repository->findMasterProductId($object_id);

    if ($master_product_id) {
        $object_id = $master_product_id;
    }
}

function fn_master_products_get_discussion_pre(&$object_id, $object_type, $get_posts, $params)
{
    if ($object_type !== DISCUSSION_OBJECT_TYPE_PRODUCT || !empty($params['skip_check_vendor_product'])) {
        return;
    }

    $product_repository = ServiceProvider::getProductRepository();

    $master_product_id = $product_repository->findMasterProductId($object_id);

    if ($master_product_id) {
        $object_id = $master_product_id;
    }
}

function fn_master_products_get_product_data_post(&$product_data, $auth, $preview, $lang_code)
{
    $product_id_map = ServiceProvider::getProductIdMap();
    $product_id_map->setMastertProductIdMapByProducts([$product_data]);

    if (!SiteArea::isStorefront(AREA)) {
        return;
    }

    if (empty($product_data['company_id']) && $product_id_map->isMasterProduct($product_data['product_id'])) {
        $master_product_qty = fn_master_products_get_master_product_quantity_for_storefront($product_data['product_id']);
        if ($master_product_qty !== false) {
            $product_data['amount'] = $master_product_qty;
        }
    }

    if (
        !YesNo::toBool(Registry::get('addons.master_products.allow_buy_default_common_product'))
        && !$product_id_map->isMasterProduct($product_data['product_id'])
        && (!isset($product_data['master_product_id']) || (int) $product_data['master_product_id'] === 0)
    ) {
        return;
    }

    list($product_data['best_product_offer_id'], $best_product_offer_price) = fn_master_products_get_best_product_offer($product_data['product_id']);

    if ($best_product_offer_price === 0) {
        return;
    }

    if (empty($product_data['company_id'])) {
        $product_data['price'] = $product_data['base_price'] = $best_product_offer_price;
    }
}

function fn_master_products_load_products_extra_data_pre(&$products, $params, $lang_code)
{
    if (!empty($params['vendor_products_by_product_id'])) {
        $master_product_id = $params['vendor_products_by_product_id'];
        $product_options = $combination = null;

        if (isset($params['master_product_combination'])) {
            $combination = (string) $params['master_product_combination'];
        } elseif (isset($params['master_product_data']['product_options'])) {
            $product_options = (array) $params['master_product_data']['product_options'];
        } elseif (isset($params['master_product_data'][$master_product_id]['product_options'])) {
            $product_options = (array) $params['master_product_data'][$master_product_id]['product_options'];
        }

        if ($combination || $product_options) {
            foreach ($products as $product_id => &$product) {
                if ($combination) {
                    $product['combination'] = $combination;
                } else {
                    $product['selected_options'] = $product_options;
                }
            }
            unset($product);
        }
    }

    ServiceProvider::getProductIdMap()->setMastertProductIdMapByProducts($products);
}

/**
 * Hook handler: gets price for master products depending on storefront
 *
 * @param array $extra_fields Array of requested product fields
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_master_products_load_products_extra_data(array &$extra_fields)
{
    if (!empty($extra_fields['?:product_prices']['fields']['price'])) {
        $extra_fields['?:product_prices']['fields']['price'] = 'COALESCE(?:master_products_storefront_min_price.price, ' .
            'MIN(IF(' .
            '?:product_prices.percentage_discount = 0,' .
            '?:product_prices.price,' .
            '?:product_prices.price - (?:product_prices.price * ?:product_prices.percentage_discount)/100))' .
            ')';

        $storefront = StorefrontProvider::getStorefront();

        $extra_fields['?:product_prices']['join'] = db_quote(
            ' LEFT JOIN ?:master_products_storefront_min_price'
            . ' ON ?:master_products_storefront_min_price.product_id = ?:product_prices.product_id'
            .   ' AND ?:master_products_storefront_min_price.storefront_id = ?i',
            $storefront->storefront_id
        );
    }
}

function fn_master_products_url_pre(&$url, $area, $protocol, $lang_code)
{
    if ($area !== 'C'
        || strpos($url, 'products.view') === false
        || Registry::get('addons.seo.status') !== 'A'
    ) {
        return;
    }

    $parsed_url = parse_url($url);
    $dispatch = null;

    if (empty($parsed_url['query'])) {
        return;
    }

    parse_str($parsed_url['query'], $parsed_query);

    if (isset($parsed_query['dispatch'])) {
        $dispatch = $parsed_query['dispatch'];
    } elseif (isset($parsed_url['path'])) {
        $dispatch = $parsed_url['path'];
    }

    if (empty($parsed_query['product_id']) || $dispatch !== 'products.view') {
        return;
    }

    $product_id = $parsed_query['product_id'];
    $master_product_id = ServiceProvider::getProductIdMap()->getMasterProductId($product_id);

    if (!$master_product_id) {
        return;
    }

    $company_id = ServiceProvider::getProductIdMap()->getVendorProductCompanyId($product_id);

    if (Registry::get('runtime.seo.is_creating_canonical_url')) {
        $url = strtr($url, ["product_id={$product_id}" => "product_id={$master_product_id}"]);
    } else {
        $url = strtr($url, ["product_id={$product_id}" => "product_id={$master_product_id}&vendor_id={$company_id}"]);
    }
}

function fn_master_products_update_image_pairs($pair_ids, $icons, $detailed, $pairs_data, $object_id, $object_type)
{
    if (empty($pair_ids) || empty($object_id) || $object_type !== 'product') {
        return;
    }

    $sync_service = ServiceProvider::getService();
    $sync_service->onTableChanged('images_links', $object_id);
}

function fn_master_products_delete_image_pair($pair_id, $object_type, $image)
{
    if (empty($image) || empty($image['object_id']) || $image['object_type'] !== 'product') {
        return;
    }

    $sync_service = ServiceProvider::getService();
    $sync_service->onTableChanged('images_links', $image['object_id']);
}

function fn_product_variations_master_products_create_vendor_product($master_product_id, $company_id, $product, $vendor_product_id)
{
    $group_repository = VariationsServiceProvider::getGroupRepository();
    $variation_service = VariationsServiceProvider::getService();
    $product_repository = ServiceProvider::getProductRepository();

    $master_product_group = $group_repository->findGroupInfoByProductId($master_product_id);

    if (empty($master_product_group)) {
        return;
    }

    $product_ids = $group_repository->findGroupProductIdsByGroupIds([$master_product_group['id']]);
    /** @var array<int, int> $vendor_product_ids */
    $vendor_product_ids = $product_repository->findVendorProductIdsByMasterProductIds($product_ids, $company_id);

    if (empty($vendor_product_ids)) {
        return;
    }

    $group_ids = $group_repository->findGroupIdsByProductIds($vendor_product_ids);

    if (empty($group_ids)) {
        $group_id = null;
    } else {
        $group_id = reset($group_ids);
    }

    if ($group_id !== null) {
        $variation_service->attachProductsToGroup($group_id, [$vendor_product_id]);
    } else {
        $variation_service->createGroup([$vendor_product_id], null, GroupFeatureCollection::createFromFeatureList($master_product_group['feature_collection']));
    }
}

/**
 * The "master_products_actualize_master_product_quantity" hook handler.
 *
 * Actions performed::
 *  - Change default variation if needed.
 *
 * @param int $product_id        Product identifier
 * @param int $master_product_id Master product identifier
 */
function fn_product_variations_master_products_actualize_master_product_quantity($product_id, $master_product_id)
{
    VariationsServiceProvider::getService()->onChangedProductQuantity($master_product_id);
}

/**
 * The "check_add_to_cart_post" hook handler.
 *
 * Actions performed:
 *  - Prevents the addition of a common product to cart
 *
 * @see fn_check_add_product_to_cart
 */
function fn_master_products_check_add_to_cart_post($cart, $product, $product_id, &$result)
{
    if (!$result
        || (Registry::get('addons.vendor_debt_payout.status') === 'A'
        && !empty($product_id)
        && (int) $product_id === fn_vendor_debt_payout_get_payout_product())
    ){
        return;
    }

    $product_company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);
    $result = !empty($product_company_id);
}

/**
 * The "seo_get_schema_org_markup_items_post" hook handler.
 *
 * Actions performed:
 *     - Adds aggregate offer into the Product markup item when viewing a common product.
 *
 * @param array<string, int>                                              $product_data Product data to get markup items from
 * @param bool                                                            $show_price   Whether product price must be shown
 * @param string                                                          $currency     Currency to get product price in
 * @param array<string, array<string, array<int, array<string, string>>>> $markup_items Schema.org markup items
 *
 * @param-out array<string, array<string, array<int, array<string, int|list<array{@type: string, availability: string, price?: float, priceCurrency?: string, url: string}>|mixed|string>>>> $markup_items Schema.org markup items
 *
 * @see fn_seo_get_schema_org_markup_items()
 */
function fn_master_products_seo_get_schema_org_markup_items_post($product_data, $show_price, $currency, &$markup_items)
{
    if (!$show_price) {
        return;
    }

    if (empty($product_data['master_product_offers_count'])) {
        return;
    }

    $product_repository = ServiceProvider::getProductRepository();

    $offer_count = $product_repository->getVendorProductsCount(
        $product_data['product_id'],
        [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN],
        [VendorStatuses::ACTIVE],
        Registry::get('settings.General.show_out_of_stock_products') === YesNo::YES
    );

    if ($offer_count === 0) {
        return;
    }

    $aggregate_offer = reset($markup_items['product']['offers']);
    if (!$aggregate_offer) {
        return;
    }
    $offer_id = key($markup_items['product']['offers']);

    $base_offer_url = $aggregate_offer['url'];
    $aggregate_offer = [
        '@type'         => 'http://schema.org/AggregateOffer',
        'lowPrice'      => $aggregate_offer['price'],
        'priceCurrency' => $aggregate_offer['priceCurrency'],
        'offerCount'    => $offer_count,
        'offers'        => [],
    ];

    if ($offer_count <= Registry::ifGet('config.master_products.seo_snippet_offers_threshold', 100)) {
        $vendor_product_ids = $product_repository->findVendorProductIds(
            $product_data['product_id'],
            [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN],
            [VendorStatuses::ACTIVE],
            true
        );
        $vendor_products = $product_repository->findProducts($vendor_product_ids);
        if (YesNo::isFalse(Registry::get('settings.General.show_out_of_stock_products'))) {
            $vendor_products = array_filter($vendor_products, static function ($product) {
                return $product['amount'] > 0
                    || $product['tracking'] === ProductTracking::DO_NOT_TRACK;
            });
        }
        if (!$vendor_products) {
            return;
        }

        foreach ($vendor_products as $vendor_product) {
            if (!isset($vendor_product['schema_org_features'])) {
                $vendor_product['schema_org_features'] = fn_seo_get_schema_org_product_features($vendor_product['product_id']);
            }

            $aggregate_offer['offers'][] = [
                '@type'         => 'http://schema.org/Offer',
                'name'          => fn_seo_get_schema_org_product_name($vendor_product),
                'sku'           => fn_seo_get_schema_org_product_sku($vendor_product),
                'gtin'          => fn_seo_get_schema_org_product_feature($vendor_product['schema_org_features'], 'gtin'),
                'mpn'           => fn_seo_get_schema_org_product_feature($vendor_product['schema_org_features'], 'mpn'),
                'availability'  => fn_seo_get_schema_org_product_availability($vendor_product),
                'url'           => fn_link_attach($base_offer_url, "vendor_id={$vendor_product['company_id']}"),
                'price'         => fn_format_price_by_currency(
                    $vendor_product['price'],
                    CART_PRIMARY_CURRENCY,
                    $currency
                ),
                'priceCurrency' => $aggregate_offer['priceCurrency'],
            ];
        }
    }

    $markup_items['product']['offers'][$offer_id] = $aggregate_offer;
}

/**
 * The "update_product_amount_post" hook handler.
 *
 * Actions performed:
 *  - Actualizes master product price, offers and quantity, after the amount was changed.
 *
 * @see fn_update_product_amount
 */
function fn_master_products_update_product_amount_post($product_id)
{
    $product_id_map = ServiceProvider::getProductIdMap();

    if (!$product_id_map->isMasterProduct($product_id) && !$product_id_map->isVendorProduct($product_id)) {
        return;
    }

    $service = ServiceProvider::getService();

    $service->actualizeMasterProductPrice($product_id);
    $service->actualizeMasterProductOffersCount($product_id);
    $service->actualizeMasterProductQuantity($product_id);
}

/**
 * Hook handler: after options reselected
 */
function fn_master_products_after_options_calculation($mode, $data)
{
    if (empty($data['reload_tabs'])) {
        return;
    }

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    /** @var array $product */
    $product = $view->getTemplateVars('product');

    fn_init_product_tabs($product);

    // check if product option change happened not in block
    if (empty($data['appearance']['obj_prefix'])) {
        $view->assign('no_capture', false);
    }
}

/**
 * The "discussion_is_user_eligible_to_write_review_for_product_post" hook handler.
 *
 * Actions performed:
 *  - Checks if common product is bought by chosen user
 *
 * @see fn_discussion_is_user_eligible_to_write_review_for_product
 */
function fn_master_products_discussion_is_user_eligible_to_write_review_for_product_post($user_id, $product_id, &$result, $need_to_buy_first)
{
    if (!$result && $need_to_buy_first) {
        $product_map = ServiceProvider::getProductIdMap();
        if ($product_map->isVendorProduct($product_id)) {
            $master_product_id = $product_map->getMasterProductId($product_id);
        } else {
            $master_product_id = $product_id;
        }
        $product_repository = ServiceProvider::getProductRepository();
        $product_ids = $product_repository->findVendorProductIds($master_product_id);
        if (!empty($product_ids)) {
            $query = VariationsServiceProvider::getQueryFactory()->createQuery(
                'orders',
                ['user_id' => $user_id],
                ['orders.order_id'],
                'orders'
            );
            $query->addInnerJoin('details', 'order_details', ['order_id' => 'order_id'], ['product_id' => $product_ids]);
            $query->setLimit(1);
            $result = (bool) $query->column();
        }
    }
}

/**
 * The "create_seo_name_pre" hook handler.
 *
 * Actions performed:
 * - Updates object name for vendor common product.
 *
 * @param int    $object_id   Object ID
 * @param string $object_type Object type
 * @param string $object_name Object name
 * @param int    $index       Index
 *
 * @see fn_create_seo_name
 */
function fn_master_products_create_seo_name_pre($object_id, $object_type, &$object_name, $index)
{
    if ($object_type !== 'p') {
        return;
    }

    $repository = ServiceProvider::getProductRepository();
    $product = current($repository->findVendorProductsInfo([$object_id]));

    if (empty($product['master_product_id']) || empty($product['company_id'])) {
        return;
    }

    $master_product_seo_name = fn_seo_get_name('p', $product['master_product_id']);
    $company_seo_name = fn_seo_get_name('m', $product['company_id']);

    /*
     * If $index <= 1 - means that seo name ($object_name) is not in use.
     * if $index > 1 - means that seo name ($object_name) already in use.
     * For example:
     *      One of the variations of the vendor product have seo name master-product-company
     *      Another variation will have the master-product-2-company seo name, 2 - is $index
     */

    if ($index <= 1) {
        $object_name = $master_product_seo_name . SEO_DELIMITER . $company_seo_name;
        return;
    }

    $object_name = $master_product_seo_name . SEO_DELIMITER . $index . SEO_DELIMITER . $company_seo_name;
}

/**
 * The "update_product_popularity" hook handler.
 *
 * Actions performed:
 * - Updates common products popularity
 *
 * @param int                       $product_id Product id
 * @param array<string, string|int> $popularity Popularity data
 *
 * @see fn_update_product_popularity
 */
function fn_master_products_update_product_popularity($product_id, array $popularity)
{
    $product_id_map = ServiceProvider::getProductIdMap();
    $master_product_id = $product_id_map->isMasterProduct($product_id) ? $product_id : $product_id_map->getMasterProductId($product_id);

    if (!$master_product_id) {
        return;
    }

    $product_repository = ServiceProvider::getProductRepository();
    $products = $product_repository->findVendorProductIdsByMasterProductIds([$master_product_id]);

    if (!$products) {
        return;
    }

    $product_ids = (array) $products[$master_product_id];
    $product_ids[$master_product_id] = $master_product_id;

    $sync_service = ServiceProvider::getService();
    $sync_service->syncData('product_popularity', $product_id, $product_ids);
}

/**
 * phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
 * --------------------------------------------------------------------
 *
 * The "attachments_check_permission_post" hook handler.
 *
 * Actions performed:
 * - Checks permission to work with attachment for common products
 *
 * @param mixed[] $request    Array of query parameters
 * @param bool    $permission Permission
 *
 * @return void
 */
function fn_master_products_attachments_check_permission_post(array $request, &$permission)
{
    if ($permission || empty($request['object_id'])) {
        return;
    }

    $product_id_map = ServiceProvider::getProductIdMap();
    $permission = $product_id_map->isMasterProduct($request['object_id']);
}

/**
 * The "change_company_status_before_mail" hook handler.
 *
 * Actions performed:
 *  - Marks storefront to reindex the offers count of master products if vendor status changed
 *
 * @param int    $company_id  Company ID
 * @param string $status_to   Status to letter
 * @param string $reason      Reason text
 * @param string $status_from Status from letter
 *
 * @see \fn_change_company_status()
 */
function fn_master_products_change_company_status_before_mail($company_id, $status_to, $reason, $status_from)
{
    if ($status_to !== VendorStatuses::ACTIVE && $status_from !== VendorStatuses::ACTIVE) {
        return;
    }

    $indexer = ServiceProvider::getIndexer();
    $indexer->markStorefrontToReindexStorefrontOffersCountByVendorId($company_id);
    $indexer->markStorefrontToReindexStorefrontMinPriceByVendorId($company_id);
}


/**
 * The "storefront_repository_save_post" hook handler.
 *
 * Actions performed:
 *  - Marks storefront to reindex the offers count of master products if storefront was added
 *  - Marks storefront to reindex the offers count of master products if storefront vendors were changed
 *
 * @param \Tygh\Storefront\Storefront  $storefront  Storefront
 * @param \Tygh\Common\OperationResult $save_result Result of the save process
 *
 * @see \Tygh\Storefront\Repository::save()
 */
function fn_master_products_storefront_repository_save_post(Storefront $storefront, OperationResult $save_result)
{
    if ($save_result->isFailure()) {
        return;
    }

    $storefront_id = (int) $save_result->getData();

    if (empty($storefront->storefront_id)) {
        ServiceProvider::getIndexer()->markStorefrontToReindexStorefrontOffersCount($storefront_id);
        ServiceProvider::getIndexer()->markStorefrontToReindexStorefrontMinPrice($storefront_id);
        return;
    }

    if (!$storefront->isReleationChanged('company_ids')) {
        return;
    }

    $company_ids = array_map('intval', $storefront->getCompanyIds());
    $stored_company_ids = array_map('intval', (array) $storefront->getStoredRelationValue('company_ids'));

    if ($company_ids === $stored_company_ids) {
        return;
    }

    ServiceProvider::getIndexer()->markStorefrontToReindexStorefrontOffersCount($storefront_id);
    ServiceProvider::getIndexer()->markStorefrontToReindexStorefrontMinPrice($storefront_id);
}

/**
 * The "settings_update_value_by_id_post" hook handler.
 *
 * Actions performed:
 *  - Marks storefront to reindex the offers count of master products if the "Show out of stock products" setting was changed for storefront
 *
 * @param \Tygh\Settings $settings          Settings instance
 * @param string         $object_id         Setting object ID
 * @param string|array   $value             New value that was passed to function
 * @param int            $company_id        Company ID
 * @param bool           $execute_functions Whether to execute action functions
 * @param array          $data              Data to be inserted/updated into settings objects table
 * @param array          $old_data          Previously existed data (if any) of settings object at settings objects table
 * @param string         $table             Table to save setting object value ("settings_objects" or "settings_vendor_values")
 * @param int            $storefront_id     Storefront identifier
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_master_products_settings_update_value_by_id_post(Settings $settings, $object_id, $value, $company_id, $execute_functions, array $data, array $old_data, $table, $storefront_id)
{
    if ($old_data['value'] === $value) {
        return;
    }

    if ($old_data['section_name'] === 'General' && $old_data['name'] === 'show_out_of_stock_products') {
        if ($storefront_id) {
            ServiceProvider::getIndexer()->markStorefrontToReindexStorefrontOffersCount($storefront_id);
        } else {
            ServiceProvider::getIndexer()->markAllStorefrontToReindexStorefrontOffersCount();
        }
        return;
    }

    if ($old_data['section_name'] === 'vendor_debt_payout' && $old_data['name'] === 'hide_products') {
        ServiceProvider::getIndexer()->markAllStorefrontToReindexStorefrontOffersCount();
        return;
    }
}

/**
 * The "update_product_tab_post" hook handler.
 *
 * Actions performed:
 *  - If master product were removed when the tab settings, this hook handler will launch tab syncing.
 *      This is necessary because the product page of a offer must not differ from the product page of its master product.
 *
 * @param int                       $tab_id   Product tab identifier
 * @param array<string, string|int> $tab_data Product tab data
 *
 * @see \Tygh\BlockManager\ProductTabs::update
 */
function fn_master_products_update_product_tab_post($tab_id, $tab_data)
{
    if (empty($tab_data['product_ids'])) {
        return;
    }

    $current_product_ids = [];
    $product_ids = fn_explode(',', (string) $tab_data['product_ids']);

    if (!empty($tab_data['current_product_ids'])) {
        $current_product_ids = fn_explode(',', (string) $tab_data['current_product_ids']);
    }

    $deleted_product_ids = array_diff($current_product_ids, $product_ids);
    $added_product_ids = array_diff($product_ids, $current_product_ids);
    $affected_product_ids = array_merge($deleted_product_ids, $added_product_ids);

    if (empty($affected_product_ids)) {
        return;
    }

    $service = ServiceProvider::getService();
    $product_id_map = ServiceProvider::getProductIdMap();
    foreach ($affected_product_ids as $product_id) {
        if (!$product_id_map->isMasterProduct($product_id) && !$product_id_map->isVendorProduct($product_id)) {
            continue;
        }
        $service->onTableChanged('product_tabs', $product_id, ['tab_id' => $tab_id]);
    }
}

/**
 * The "master_products_reindex_storefront_offers_count" hook handler.
 *
 * Actions performed:
 *   - Extends company statuses list with suspended status for update vendor offres count if "Hide products of suspended vendors" (Vendor-to-admin payments) is disabled
 *
 * @param array<string, int[]>  $params     Params
 * @param array<string, string> $conditions Conditions
 */
function fn_vendor_debt_payout_master_products_reindex_storefront_offers_count(array $params, array &$conditions)
{
    if (YesNo::toBool(Settings::instance(['company_id' => 0])->getValue('hide_products', 'vendor_debt_payout'))) {
        return;
    }

    // FIXME Indexer should be use fn_get_products conditions
    $conditions['companies_status'] = db_quote('companies.status IN (?a)', [VendorStatuses::ACTIVE, VendorStatuses::SUSPENDED]);
}

/**
 * Loads seller data for product offers.
 *
 * @param array<int, array<string, string|int|bool>> $products Product offers
 *
 * @return array<int, array<string, string|int|bool|array<string, string>>> Product offers with loaded seller data
 */
function fn_master_products_load_products_seller_data(array $products)
{
    list($companies,) = fn_get_companies([
        'company_id' => array_column($products, 'company_id'),
        'extend'     => [
            'product_count'  => YesNo::NO,
            'logos'          => true,
            'placement_info' => true,
        ],
    ], Tygh::$app['session']['auth']);

    $companies = fn_array_combine(array_column($companies, 'company_id'), $companies);

    foreach ($products as &$product) {
        $product['company'] = $companies[$product['company_id']];
        $product['is_vendor_products_list_item'] = true;
    }
    unset($product);

    return $products;
}

/**
 * Checks whether the product id is master product id
 *
 * @param int $product_id Product identifier
 *
 * @return bool Is master product
 */
function fn_master_products_is_master_product_id($product_id)
{
    $product_info = [
        $product_id => ['product_id' => $product_id]
    ];
    $product_info = fn_master_products_is_master_products($product_info);

    return $product_info[$product_id]['is_master_product'];
}

/**
 * Checks whether the product in array is master
 *
 * @param array<int, array<string, int|string>> $products_info Products identifiers
 *
 * @return array<int, array<string, bool>> Array with info about master product
 */
function fn_master_products_is_master_products(array $products_info)
{
    $product_id_map = ServiceProvider::getProductIdMap();
    $products_info_about_master_product = [];
    foreach ($products_info as $p_info) {
        $p_id = (int) $p_info['product_id'];
        $is_master_product = $product_id_map->isMasterProduct($p_id) === true;
        $products_info_about_master_product[$p_id]['is_master_product'] = $is_master_product;
    }
    return $products_info_about_master_product;
}

/**
 * The "storefront_rest_api_gather_additional_products_data_pre" hook handler.
 *
 * Actions performed:
 * - Loads sellers data for products when requested via API.
 *
 * @param array<int, array<string, string|int|bool>> $products           Products
 * @param array<string, string>                      $params             Request parameters
 * @param array<string, string>                      $data_gather_params Product data gather parameters
 *
 * @return void
 *
 * @param-out array<int, array<string, string|int|bool|array<string, string>>> $products Products
 *
 * @see \fn_storefront_rest_api_gather_additional_products_data()
 */
function fn_master_products_storefront_rest_api_gather_additional_products_data_pre(
    array &$products,
    array $params,
    array $data_gather_params
) {
    if (empty($params['vendor_products_by_product_id'])) {
        return;
    }

    $products = fn_master_products_load_products_seller_data($products);

    $public_company_properties = [
        'email'               => true,
        'company'             => true,
        'company_description' => true,
        'address'             => true,
        'city'                => true,
        'state'               => true,
        'country'             => true,
        'zipcode'             => true,
        'phone'               => true,
        'url'                 => true,
        'logos'               => true,
        'average_rating'      => true,
        'discussion'          => true,
    ];
    foreach ($products as &$product) {
        /**
         * @psalm-var array{
         *   company: array<string, string>
         * } $product
         */
        $product['company'] = array_filter(
            $product['company'],
            static function ($property) use ($public_company_properties) {
                return isset($public_company_properties[$property]);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
    unset($product);
}

/**
 * The "product_reviews_find_pre" hook handler.
 *
 * Actions performed:
 * - Replaces product_id with master_product_id.
 *
 * @param array{product_id?: int|int[], vendor_products_by_product_id?: int} $params Search and sort parameters
 *
 * @return void
 *
 * @see \Tygh\Addons\ProductReviews\ProductReview\Repository::find()
 */
function fn_master_products_product_reviews_find_pre(&$params)
{
    if (!empty($params['vendor_products_by_product_id'])) {
        $params['product_id'] = $params['vendor_products_by_product_id'];
    }

    if (!isset($params['product_id'])) {
        return;
    }

    $master_product_ids = [];
    $product_repository = ServiceProvider::getProductRepository();
    $service = ServiceProvider::getProductIdMap();

    foreach ((array) $params['product_id'] as $product_id) {
        $master_product_id = $product_repository->findMasterProductId($product_id);

        if ($master_product_id) {
            $master_product_ids[] = $master_product_id;
        } elseif ($service->isMasterProduct($product_id)) {
            $master_product_ids[] = $product_id;
        }
    }

    if (empty($master_product_ids)) {
        return;
    }

    $params['product_id'] = is_array($params['product_id']) ? $master_product_ids : reset($master_product_ids);
}

/**
 * The "product_reviews_is_user_eligible_to_write_product_review" hook handler.
 *
 * Actions performed:
 *  - Checks if common product is bought by chosen user
 *
 * @param int             $user_id           User identifier
 * @param int             $product_id        Product identifier
 * @param string|null     $ip                IP address by fn_ip_to_db
 * @param bool            $need_to_buy_first State of the review_after_purchase setting
 * @param bool            $review_ip_check   State of the review_ip_check setting
 * @param OperationResult $result            Operation result
 *
 * @return void
 *
 * @see \Tygh\Addons\ProductReviews\Service::isUserEligibleToWriteProductReview()
 */
function fn_master_products_product_reviews_is_user_eligible_to_write_product_review(
    $user_id,
    $product_id,
    $ip,
    $need_to_buy_first,
    $review_ip_check,
    OperationResult &$result
) {
    if (
        $result->isSuccess()
        || !$need_to_buy_first
        || isset($result->getErrors()['product_reviews.review_already_posted_from_ip'])
    ) {
        return;
    }

    $product_map = ServiceProvider::getProductIdMap();
    if ($product_map->isVendorProduct($product_id)) {
        $master_product_id = $product_map->getMasterProductId($product_id);
    } else {
        $master_product_id = $product_id;
    }

    if (!$master_product_id) {
        return;
    }

    $product_repository = ServiceProvider::getProductRepository();
    $product_ids = $product_repository->findVendorProductIds($master_product_id);
    if (empty($product_ids)) {
        return;
    }

    $query = VariationsServiceProvider::getQueryFactory()->createQuery(
        'orders',
        ['user_id' => $user_id],
        ['orders.order_id'],
        'orders'
    );
    $query->addInnerJoin('details', 'order_details', ['order_id' => 'order_id'], ['product_id' => $product_ids]);
    $query->setLimit(1);

    $result->setSuccess((bool) $query->column());
}

/**
 * The "product_reviews_find_pre" hook handler.
 *
 * Actions performed:
 *  - Replaces product_id with parent_product_id.
 *
 * @param array<string, string|int|null> $product_review_data Product review data
 *
 * @return void
 *
 * @see \Tygh\Addons\ProductReviews\ProductReview\Repository::create()
 */
function fn_master_products_product_reviews_create_pre(&$product_review_data)
{
    if (!isset($product_review_data['product_id'])) {
        return;
    }

    $product_repository = ServiceProvider::getProductRepository();

    $master_product_id = $product_repository->findMasterProductId((int) $product_review_data['product_id']);

    if (empty($master_product_id)) {
        return;
    }

    $product_review_data['product_id'] = $master_product_id;
}

/**
 * The "variation_group_save_group" hook handler.
 *
 * Actions performed:
 * - Updated group of vendor variation product offers if the master variation products was updated
 *
 * @param VariationService                                             $product_variation_service Product variations service
 * @param VariationGroup                                               $group                     Product variations group
 * @param \Tygh\Addons\ProductVariations\Product\Group\Events\AEvent[] $events                    Product variations group event
 *
 * @return void
 *
 * @see \Tygh\Addons\ProductVariations\Service::saveGroup()
 */
function fn_master_products_variation_group_save_group(
    VariationService $product_variation_service,
    VariationGroup $group,
    array $events
) {
    $group_repository = VariationsServiceProvider::getGroupRepository();
    $variation_service = VariationsServiceProvider::getService();
    $product_repository = ServiceProvider::getProductRepository();

    $parent_product_ids = $child_product_ids = $delete_product_ids = [];

    foreach ($events as $event) {
        if ($event instanceof VariationProductRemovedEvent && empty($event->getProduct()->getCompanyId())) {
            $delete_product_ids[] = $event->getProduct()->getProductId();
        } elseif ($event instanceof VariationProductAddedEvent && empty($event->getProduct()->getCompanyId())) {
            $child_product_ids[$event->getProduct()->getParentProductId()][$event->getProduct()->getProductId()] = $event->getProduct()->getProductId();
        } elseif ($event instanceof VariationProductUpdatedEvent && empty($event->getTo()->getCompanyId())) {
            if (!$event->getFrom()->hasSameParentProductId($event->getTo()->getParentProductId())) {
                $child_product_ids[$event->getTo()->getParentProductId()][$event->getTo()->getProductId()] = $event->getTo()->getProductId();
            }
        } elseif ($event instanceof VariationParentProductChangedEvent && empty($event->getTo()->getCompanyId())) {
            $to_group_product = $event->getTo();
            $product_id = $to_group_product->getProductId();

            $parent_product_ids[$product_id] = $product_id;
        }
    }

    if (
        empty($delete_product_ids)
        && empty($child_product_ids)
        && empty($parent_product_ids)
    ) {
        return;
    }

    $delete_product_ids = empty($delete_product_ids) ? [] : $product_repository->findVendorProductIdsByMasterProductIds($delete_product_ids);

    $delete_offers_product_ids = $child_offers_product_ids = $parent_offers_product_ids = [];

    if (!empty($delete_product_ids)) {
        /** @var array $offer_ids */
        foreach ($delete_product_ids as $offer_ids) {
            foreach ($offer_ids as $offer_id) {
                $group_id = $group_repository->findGroupIdByProductId($offer_id);
                if (empty($group_id)) {
                    continue;
                }
                $delete_offers_product_ids[$group_id][] = $offer_id;
            }
        }
        foreach ($delete_offers_product_ids as $group_id => $product_ids) {
            $variation_service->detachProductsFromGroup($group_id, $product_ids);
        }
    }

    if (!empty($child_product_ids)) {
        foreach ($child_product_ids as $parent_product_id => $product_ids) {
            $parent_product_id = empty($parent_product_id) ? reset($product_ids) : $parent_product_id;
            $master_product_group = $group_repository->findGroupInfoByProductId($parent_product_id);
            $offer_parent_product_ids = $product_repository->findVendorProductIdsByMasterProductIds([$parent_product_id]);
            if (empty($offer_parent_product_ids[$parent_product_id])) {
                continue;
            }
            /** @var array $offer_ids */
            $offer_ids = $offer_parent_product_ids[$parent_product_id];
            foreach ($offer_ids as $offer_parent_product_id) {
                $offer_parent_product = $product_repository->findProduct($offer_parent_product_id);
                $group_id = $group_repository->findGroupIdByProductId($offer_parent_product_id);
                /** @var array<int, int> $offer_product_ids */
                $offer_product_ids = $product_repository->findVendorProductIdsByMasterProductIds($product_ids, $offer_parent_product['company_id']);
                if (empty($offer_product_ids)) {
                    continue;
                }
                if ($group_id !== null) {
                    $variation_service->attachProductsToGroup($group_id, $offer_product_ids);
                } else {
                    $offer_product_ids = array_merge([$offer_parent_product_id], $offer_product_ids);
                    $variation_service->createGroup(
                        $offer_product_ids,
                        null,
                        GroupFeatureCollection::createFromFeatureList($master_product_group['feature_collection'])
                    );
                }
            }
        }
    }

    if (!empty($parent_product_ids)) {
        foreach ($parent_product_ids as $parent_product_id) {
            $offer_parent_product_ids = $product_repository->findVendorProductIdsByMasterProductIds([$parent_product_id]);
            if (empty($offer_parent_product_ids[$parent_product_id])) {
                continue;
            }
            /** @var array $offer_ids */
            $offer_ids = $offer_parent_product_ids[$parent_product_id];
            foreach ($offer_ids as $offer_parent_product_id) {
                $group_id = $group_repository->findGroupIdByProductId($offer_parent_product_id);
                if (!empty($group_id)) {
                    $variation_service->setDefaultProduct($group_id, $offer_parent_product_id);
                }
            }
        }
    }
}

/**
 * The "update_product_pre" hook handler.
 *
 * Actions performed:
 * - Forbid to change owner if the product has product offers.
 *
 * @param array<string, array<string, string|int|bool>> $product_data Product data
 * @param int                                           $product_id   Product identifier
 * @param string                                        $lang_code    Two-letter language code
 * @param bool                                          $can_update   Flag, allows addon to forbid to create/update product.
 *
 * @return void
 *
 * @see \fn_update_product()
 */
function fn_master_products_update_product_pre(&$product_data, &$product_id, &$lang_code, &$can_update)
{
    if (!$can_update || empty($product_id)) {
        return;
    }

    $company_id = Registry::get('runtime.company_id');
    if (!$company_id) {
        if (isset($product_data['company_id'])) {
            $company_id = $product_data['company_id'];
        } else {
            $company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);
        }
    }
    if (empty($company_id)) {
        return;
    }

    $product_repository = ServiceProvider::getProductRepository();
    $vendor_product_ids = $product_repository->findVendorProductIds($product_id);

    if (empty($vendor_product_ids)) {
        return;
    }

    $can_update = false;
    fn_set_notification(NotificationSeverity::ERROR, __('error'), __('master_products.changing_owner_is_not_available', [
        '[vendors_offers_link]' => fn_url('products.manage?master_product_id=' . $product_id)
    ]));
}

/**
 * Find best product offer
 *
 * @param int   $master_product_id  Master product identifier
 * @param array $vendor_product_ids Vendor offer product IDs
 *
 * @return array<int> Best product offer id and price
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_master_products_get_best_product_offer($master_product_id, array $vendor_product_ids = [])
{
    /**
     * Executed before searching for the best product offer, allows you to change the vendor products data
     *
     * @param int   $master_product_id  Master product identifier
     * @param array $vendor_product_ids Best product offer identifier
     */
    fn_set_hook('master_products_get_best_product_offer_pre', $master_product_id, $vendor_product_ids);

    $product_repository = ServiceProvider::getProductRepository();

    if (empty($vendor_product_ids)) {
        $is_show_out_of_stock_products_enabled = false;

        if (
            YesNo::toBool(Registry::get('addons.master_products.allow_buy_default_common_product'))
            && $product_repository->getProductOutOfStockActions($master_product_id) === OutOfStockActions::BUY_IN_ADVANCE
        ) {
            $count_products_in_stock = $product_repository->getVendorProductsCount($master_product_id, [ObjectStatuses::ACTIVE]);
            if (!$count_products_in_stock) {
                $is_show_out_of_stock_products_enabled = true;
            }
        }

        $vendor_product_ids = $product_repository->findVendorProductIds(
            $master_product_id,
            [ObjectStatuses::ACTIVE],
            null,
            $is_show_out_of_stock_products_enabled
        );

        if (empty($vendor_product_ids)) {
            return [$master_product_id, 0];
        }
    }

    $vendor_product_offers = $product_repository->findProducts($vendor_product_ids);
    fn_gather_additional_products_data($vendor_product_offers, []);

    $best_product_offer_id = null;
    foreach ($vendor_product_offers as $product) {
        if (
            !$best_product_offer_id
            || $vendor_product_offers[$best_product_offer_id]['price'] > $product['price']
        ) {
            $best_product_offer_id = $product['product_id'];
        }
    }

    $best_product_offer_price = !empty($vendor_product_offers[$best_product_offer_id])
        ? $vendor_product_offers[$best_product_offer_id]['price']
        : null;

    /**
     * Executed after searching for the best product offer, allows you to change the data
     *
     * @param int   $master_product_id        Master product identifier
     * @param int   $best_product_offer_id    Best product offer id
     * @param float $best_product_offer_price Best product offer price
     * @param array $vendor_product_offers    List of all offers
     */
    fn_set_hook('get_best_product_offer_post', $master_product_id, $best_product_offer_id, $best_product_offer_price, $vendor_product_offers);

    return [(int) $best_product_offer_id, $best_product_offer_price];
}

/**
 * The "pre_add_to_cart" hook handler.
 *
 * Actions performed:
 * - Change selected products data
 *
 * @param array $product_data List of products data
 * @param array $cart         Array of cart content and user information necessary for purchase
 *
 * @return void
 *
 * @see fn_add_product_to_cart()
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_master_products_pre_add_to_cart(array &$product_data, array $cart)
{
    if (
        !YesNo::toBool(Registry::get('addons.master_products.allow_buy_default_common_product'))
        || SiteArea::isAdmin(AREA)
    ) {
        return;
    }

    $use_other_product_offers = true;

    /**
     * Executed before checking the availability of the products and selecting the product offer of another vendor
     *
     * @param array $product_data             List of products data
     * @param array $cart                     Array of cart content and user information necessary for purchase
     * @param bool  $use_other_product_offers Add products from other vendors to the cart
     */
    fn_set_hook('master_products_pre_add_to_cart_pre', $product_data, $cart, $use_other_product_offers);

    $company_id = 0;
    if (!empty($cart['order_id'])) {
        $order_data = fn_get_order_info((int) $cart['order_id']);
        $company_id = (int) $order_data['company_id'];
    }

    $product_repository = ServiceProvider::getProductRepository();
    $product_id_is_over = 0;

    $is_master_product = false;
    if (!empty($_REQUEST['product_id'])) {
        $product_id_map = ServiceProvider::getProductIdMap();
        $is_master_product = $product_id_map->isMasterProduct($_REQUEST['product_id']);
    }

    $params = [ 'skip_offers_notification' => false ];
    foreach ($product_data as $key => &$product) {
        $master_product_id = $product_repository->findMasterProductId($key);

        $tracking = db_get_row('SELECT tracking FROM ?:products WHERE product_id = ?i', $key);
        $tracking = fn_normalize_product_overridable_fields($tracking);

        if (
            empty($key)
            || empty($product['amount'])
            || empty($master_product_id)
            || $tracking['tracking'] === ProductTracking::DO_NOT_TRACK
        ) {
            continue;
        }

        $vendor_product_ids = array_diff(
            $product_repository->findVendorProductIds($master_product_id, [ObjectStatuses::ACTIVE], [ObjectStatuses::ACTIVE], false, $company_id),
            [$key]
        );

        if (empty($vendor_product_ids)) {
            continue;
        }

        $product_amount = fn_get_product_amount($key);

        fn_master_products_get_product_amount_without_cart($product_amount, $cart, $key);

        if ($product_amount >= $product['amount']) {
            continue;
        }

        if (!$use_other_product_offers) {
            $product_id_is_over = $master_product_id;
            continue;
        }

        $total_amount = $product['amount'] - $product_amount;
        $product['amount'] = $product_amount;
        $added_products = [];
        while (!empty($vendor_product_ids)) {
            list($product_id,) = fn_master_products_get_best_product_offer($master_product_id, $vendor_product_ids);
            $product_amount = fn_get_product_amount($product_id, $params);
            fn_master_products_get_product_amount_without_cart($product_amount, $cart, $product_id);

            if ($product_amount >= $total_amount) {
                $added_products[$product_id] = [
                    'product_options' => isset($product['product_options']) ? $product['product_options'] : [],
                    'amount' => $total_amount,
                    'extra' => !empty($product['extra']) ? $product['extra'] : []
                ];
                break;
            }

            $added_products[$product_id] = [
                'product_options' => isset($product['product_options']) ? $product['product_options'] : [],
                'amount' => $product_amount,
                'extra' => !empty($product['extra']) ? $product['extra'] : []
            ];
            $total_amount -= $product_amount;
            $vendor_product_ids = array_diff($vendor_product_ids, [$product_id]);

            if (!next($vendor_product_ids)) {
                break;
            }
        }
    }

    if ($params['skip_offers_notification'] !== true) {
        if (!empty($added_products)) {
            $product_data += $added_products;
            fn_set_notification(
                NotificationSeverity::WARNING,
                __('warning'),
                __('master_products.include_offers_from_multiple_sellers')
            );
        } elseif (
            $use_other_product_offers === false
            && !empty($product_id_is_over)
            && $is_master_product
            && !SiteArea::isAdmin(AREA)
        ) {
            fn_set_notification(
                NotificationSeverity::WARNING,
                __('warning'),
                __(
                    'master_products.not_include_offers_from_multiple_sellers',
                    [
                        '[url]' => fn_url('products.view&product_id=' . $product_id_is_over) . '#tabs_content'
                    ]
                )
            );
        }
    }
}

/**
 * Determines the amount of the remaining product taking into account the one already added to the cart
 *
 * @param int   $product_amount Offer product amount
 * @param array $cart           Array of cart content and user information necessary for purchase
 * @param int   $product_id     Offer product id
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_master_products_get_product_amount_without_cart(&$product_amount, array $cart, $product_id)
{
    if (empty($cart['products'])) {
        return;
    }

    foreach ($cart['products'] as $cart_product) {
        if ($cart_product['product_id'] !== $product_id) {
            continue;
        }

        $product_amount -= $cart_product['amount'];
    }
}

/**
 * The `product_bundle_service_get_bundles` hook handler.
 *
 * Action performed:
 *      - Allows getting bundles for vendor products.
 *
 * @param array<string|int>     $params     Parameters for bundles search.
 * @param string                $fields     Requesting product bundles fields.
 * @param array<string, string> $joins      Joining tables for request.
 * @param array<string, string> $conditions Conditions of request.
 * @param array<string, string> $limit      Limit conditions of request.
 *
 * @param-out array<string|int|array<int>> $params
 *
 * @return void
 */
function fn_master_products_product_bundle_service_get_bundles(array &$params, $fields, array $joins, array &$conditions, array $limit)
{
    if (!isset($conditions['product_id'], $params['product_id']) || !SiteArea::isStorefront(AREA)) {
        return;
    }

    $vendor_product_ids = ServiceProvider::getProductRepository()->findVendorProductIds((int) $params['product_id']);
    if (empty($vendor_product_ids)) {
        return;
    }

    $params['product_id'] = $vendor_product_ids;
    $conditions['product_id'] = db_quote(' AND links.product_id IN (?n)', $vendor_product_ids);
}

/**
 * The `pre_add_to_wishlist` hook handler.
 *
 * Action performed:
 *      - Selects a variation for the best offer.
 *
 * @param array<string, array<string, string>>                                       $product_data Product to add data
 * @param array<string, array<string, array<string, string|int|array<int, string>>>> $wishlist     Wishlist data storage
 * @param array<string, string|int|array<string|int, int>>                           $auth         User session data
 *
 * @param-out array<array-key, array{product_id: string}>|array<string, array<string, string>> $product_data
 *
 * @return void
 */
function fn_master_products_pre_add_to_wishlist(array &$product_data, array $wishlist, array $auth)
{
    if (
        !YesNo::toBool(Registry::get('addons.master_products.allow_buy_default_common_product'))
        || !isset($_REQUEST['product_id'])
    ) {
        return;
    }

    $product_id = $_REQUEST['product_id'];
    $product_id_map = ServiceProvider::getProductIdMap();

    if (!$product_id_map->isMasterProduct($product_id)) {
        return;
    }

    $best_offer_id = $product_data[$product_id]['product_id'];
    $product_data = [
        $product_id => [
            'product_id' => $best_offer_id
        ]
    ];
}

/**
 * The "products_form_product_list_params_post" hook handler.
 *
 * Actions performed:
 * - Checks for additional parameters from request and process them
 *
 * @param array<string, int|bool|string> $request Request data
 * @param array<string, int|bool|string> $params  Parameter for getting products picker list
 *
 * @return void
 *
 * @see fn_products_form_product_list_params()
 */
function fn_master_products_products_form_product_list_params_post(array $request, &$params)
{
    if (!isset($request['selecting_for_customer_area'])) {
        return;
    }

    $params['selecting_for_customer_area'] = (bool) $request['selecting_for_customer_area'];
}

/**
 * The "generate_filter_field_params" hook handler.
 *
 * Actions performed:
 * - Adds master products into filter by vendor for further filtering.
 *
 * @param array<string, int|bool|string> $params Request parameters
 *
 * @return void
 *
 * @see fn_generate_filter_field_params()
 */
function fn_master_products_generate_filter_field_params(array &$params)
{
    if (!isset($params['filter_params']['company_id']) || in_array('0', $params['filter_params']['company_id'])) {
        return;
    }

    $params['filter_params']['company_id'][] = 0;
}

/**
 * The "get_current_filters_after_variants_select_query" hook handler.
 *
 * Actions performed:
 * - Modifies vendors list in 'Vendor' filter taking master products into account.
 *
 * @param array<string, int|bool|string> $params                         Request parameters
 * @param array<string, int|bool|string> $filters                        Filters data
 * @param array<string, int|bool|string> $selected_filters               Selected filters data
 * @param string                         $area                           Site area
 * @param string                         $lang_code                      Two-letters language code
 * @param array<string, int|bool|string> $variant_values                 Feature filters variants values
 * @param array<string, int|bool|string> $field_variant_values           Product field filters variants values
 * @param int                            $filter_id                      Filter id
 * @param array<string, int|bool|string> $filter                         Filter data
 * @param array<string, int|bool|string> $result                         Variants query result
 * @param string                         $fields_join                    Extra joins
 * @param string                         $products_table_base_joins      Products table base joins
 * @param string                         $fields_where                   Extra conditions
 * @param string                         $products_table_base_conditions Products table base conditions
 *
 * @return void
 *
 * @see fn_get_current_filters()
 */
function fn_master_products_get_current_filters_after_variants_select_query(
    array $params,
    array $filters,
    array $selected_filters,
    $area,
    $lang_code,
    array $variant_values,
    array &$field_variant_values,
    $filter_id,
    array $filter,
    &$result,
    $fields_join,
    $products_table_base_joins,
    $fields_where,
    $products_table_base_conditions
) {
    if ($filter['field_type'] !== ProductFilterProductFieldTypes::VENDOR || empty($result)) {
        return;
    }

    $vendor_ids = array_keys($result);

    if (!in_array(0, $vendor_ids)) {
        return;
    }

    // Remove 'All vendors' variant from 'Vendor' filter
    unset($result[0]);
    unset($field_variant_values[$filter_id]['variants'][0]);

    $fields_where .= ' AND products.company_id = 0';

    $master_product_ids = db_get_fields(
        'SELECT products.product_id'
        . ' FROM ?:products as products ?p ?p'
        . ' WHERE 1=1 ?p ?p',
        $fields_join,
        $products_table_base_joins,
        $fields_where,
        $products_table_base_conditions
    );

    $joins = db_quote('INNER JOIN ?:companies AS companies ON companies.company_id = products.company_id AND companies.status = ?s', VendorStatuses::ACTIVE);
    $conditions = db_quote('AND products.master_product_id IN (?n)', $master_product_ids);

    $master_products_result = db_get_hash_array(
        'SELECT products.company_id as variant_id, companies.company as variant'
        . ' FROM ?:products as products ?p'
        . ' WHERE 1=1 ?p'
        . ' GROUP BY products.company_id'
        . ' ORDER BY companies.company ASC',
        'variant_id',
        $joins,
        $conditions
    );

    $result = $field_variant_values[$filter_id]['variants'] = fn_array_merge($result, $master_products_result);
}

/**
 * The "vendor_locations_get_products_pre" hook handler.
 *
 * Actions performed:
 * - Includes master products to filter by location.
 *
 * @param array<string, int|bool|string> $params                    Request params
 * @param array<string, int|bool|string> $filter_types              Filter types
 * @param array<string, int|bool|string> $fields                    SELECT query fields
 * @param array<string, int|bool|string> $sortings                  SELECT query sorting
 * @param string                         $condition                 SELECT query condition
 * @param string                         $join                      SELECT query joins
 * @param string                         $master_products_condition SELECT query master products condition
 *
 * @return void
 *
 * @see fn_vendor_locations_get_products()
 */
function fn_master_products_vendor_locations_get_products_pre(array $params, array $filter_types, array $fields, array $sortings, string &$condition, string &$join, string &$master_products_condition)
{
    if (!$filter_types) {
        return;
    }

    /** @var \Tygh\Addons\VendorLocations\FilterTypes\BaseFilterType $filter_type */
    foreach ($filter_types as $filter_type) {
        $_condition = $filter_type->buildSqlWhereConditions();
        $vendor_ids = db_get_fields('SELECT company_id FROM ?:vendor_locations AS vendor_locations WHERE ?p', $_condition);
    }

    if (empty($vendor_ids)) {
        return;
    }

    $master_products_condition .= db_quote(' OR products.company_id = 0 AND master_products.company_id IN (?n)', $vendor_ids);

    if (!empty($params['category_id'])) {
        $master_products_condition .= db_quote(' AND ?:categories.category_id IN (?n)', $params['category_id']);
    }
}

/**
 * The "vendor_locations_get_products" hook handler.
 *
 * Actions performed:
 * - Includes master products to filter by location.
 *
 * @param array<string, int|bool|string> $params       Request params
 * @param array<string, int|bool|string> $filter_types Filter types
 * @param array<string, int|bool|string> $fields       SELECT query fields
 * @param array<string, int|bool|string> $sortings     SELECT query sorting
 * @param string                         $condition    SELECT query condition
 * @param string                         $join         SELECT query joins
 *
 * @return void
 *
 * @see fn_vendor_locations_get_products()
 */
function fn_master_products_vendor_locations_get_products(array $params, array $filter_types, array $fields, array $sortings, string &$condition, string &$join)
{
    if (!$filter_types) {
        return;
    }
    $join = str_replace('INNER JOIN ?:vendor_locations', 'LEFT JOIN ?:vendor_locations', $join);
}

/**
 * Gets quantity of master product considering current storefront.
 *
 * @param int $master_product_id Master product id
 *
 * @return int|bool
 */
function fn_master_products_get_master_product_quantity_for_storefront($master_product_id)
{
    $storefront = StorefrontProvider::getStorefront();
    $storefront_company_ids = $storefront->getCompanyIds();

    if (empty($storefront_company_ids)) {
        return false;
    }
    $product_repository = ServiceProvider::getProductRepository();

    return $product_repository->getVendorProductsSumQuantity(
        $master_product_id,
        [ObjectStatuses::ACTIVE],
        [ObjectStatuses::ACTIVE],
        $storefront_company_ids
    );
}

/**
 * Filters quantities of master products considering current storefront.
 *
 * @param array $products Array of products
 *
 * @return array
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_master_products_filter_master_products_quantities_for_current_storefront(array $products)
{
    if (StorefrontProvider::getRepository()->getCount() < 2) {
        return $products;
    }

    $storefront = StorefrontProvider::getStorefront();
    $storefront_company_ids = $storefront->getCompanyIds();
    if (empty($storefront_company_ids)) {
        return $products;
    }

    $product_repository = ServiceProvider::getProductRepository();

    foreach ($products as &$product) {
        if (!empty($product['company_id']) || $product['master_product_offers_count'] < 2 || empty($product['amount'])) {
            continue;
        }

        $master_product_qty = $product_repository->getVendorProductsSumQuantity(
            $product['product_id'],
            [ObjectStatuses::ACTIVE],
            [ObjectStatuses::ACTIVE],
            $storefront_company_ids
        );

        if ($master_product_qty !== null) {
            $product['amount'] = $master_product_qty;
        }
    }
    unset($product);

    return $products;
}

/**
 * The "update_location" hook handler.
 *
 * Actions performed:
 *  - When the settings of the products.view page are updated, this hook handler will save the list of products to which this page was applied.
 *      This will be necessary later for determining what products were added/removed from the page settings.
 *
 * @param array $location_data Location data
 *
 * @return void
 *
 * @see \Tygh\BlockManager\Location::update
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_master_products_update_location(array &$location_data)
{
    if (
        empty($location_data['location_id'])
        || empty($location_data['dispatch'])
        || $location_data['dispatch'] !== 'products.view'
    ) {
        return;
    }

    $query = VariationsServiceProvider::getQueryFactory()->createQuery(
        'bm_locations',
        ['location_id' => $location_data['location_id']],
        ['object_ids']
    );

    $location_data['current_object_ids'] = $query->scalar();
}

/**
 * The "update_location_post" hook handler.
 *
 * Actions performed:
 *  - If master products were added or removed when settings of the products.view page were changed, this hook handler will launch syncing for these products.
 *      This is necessary because the product page of product offer must not differ from the product page of its master product.
 *
 * @param array  $location_data Array of location data
 * @param string $lang_code     Two letters language code
 * @param int    $location_id   Location identificator
 *
 * @return void
 *
 * @see \Tygh\BlockManager\Location::update
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_master_products_update_location_post(array $location_data, $lang_code, $location_id)
{
    if (empty($location_data['dispatch']) || $location_data['dispatch'] !== 'products.view') {
        return;
    }

    $current_product_ids = [];
    $product_ids = empty($location_data['object_ids']) ? [] : fn_explode(',', $location_data['object_ids']);

    if (!empty($location_data['current_object_ids'])) {
        $current_product_ids = fn_explode(',', $location_data['current_object_ids']);
    }

    $deleted_product_ids = array_diff($current_product_ids, $product_ids);
    $added_product_ids = array_diff($product_ids, $current_product_ids);
    $affected_product_ids = array_merge($deleted_product_ids, $added_product_ids);

    if (empty($affected_product_ids)) {
        return;
    }

    $sync_service = ServiceProvider::getService();
    $sync_service->onTableChanged('bm_locations', $affected_product_ids, ['location_id' => $location_id]);
}

/**
 * The "update_block_post" hook handler.
 *
 * Actions performed:
 *  - Starts the syncing of the block settings if the content settings of this block change for master product.
 *      This is necessary because blocks on the product page of product offer must not differ from the blocks on the page of its master product.
 *
 * @param array $block_data  Array of block data
 * @param array $description Array of block description data @see Bm_Block::updateDescription
 * @param int   $block_id    Block identifier
 *
 * @return void
 *
 * @see \Tygh\BlockManager\Block::update
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_master_products_update_block_post(array $block_data, array $description, $block_id)
{
    if (
        empty($block_data['content_data']['object_type'])
        || empty($block_data['content_data']['object_id'])
        || $block_data['content_data']['object_type'] !== 'products'
    ) {
        return;
    }

    $master_product_id = $block_data['content_data']['object_id'];

    ServiceProvider::getService()->onTableChanged('bm_blocks_content', $master_product_id, [
        'block_id' => $block_id,
        'snapping_id' => isset($block_data['snapping_id']) ? $block_data['snapping_id'] : null
    ]);
}

/**
 * The "update_block_status_post" hook handler.
 *
 * Actions performed:
 *  - Starts the syncing of the block settings if the content settings of this block change for master product.
 *      This is necessary because blocks on the product page of product offer must not differ from the blocks on the page of its master product.
 *
 * @param array $status_data Array of status data
 *
 * @return void
 *
 * @see \Tygh\BlockManager\Block::updateStatus
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_master_products_update_block_status_post(array $status_data)
{
    if (
        empty($status_data['snapping_id'])
        || empty($status_data['status'])
        || empty($status_data['object_type'])
        || empty($status_data['object_id'])
        || $status_data['object_type'] !== 'products'
    ) {
        return;
    }

    $master_product_id = $status_data['object_id'];

    ServiceProvider::getService()->onTableChanged('bm_block_statuses', $master_product_id, [
        'snapping_id' => $status_data['snapping_id']
    ]);
}

/**
 * The "update_snapping_pre" hook handler.
 *
 * Actions performed:
 *  - Saves the IDs of products for which this block isn't available when the statuses of blocks for master product are updated.
 *      This will be necessary later for determining what products were added/removed from the block settings.
 *
 * @param array $snapping_data Array of snapping data
 *
 * @return void
 *
 * @see \Tygh\BlockManager\Block::updateSnapping
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_master_products_update_snapping_pre(array &$snapping_data)
{
    if (
        empty($snapping_data['snapping_id'])
        || empty($snapping_data['object_type'])
        || $snapping_data['object_type'] !== 'products'
    ) {
        return;
    }

    $query = VariationsServiceProvider::getQueryFactory()->createQuery(
        'bm_block_statuses',
        ['snapping_id' => $snapping_data['snapping_id'], 'object_type' => 'products'],
        ['object_ids']
    );

    $snapping_data['current_object_ids'] = $query->scalar();
}

/**
 * The "update_snapping_post" hook handler.
 *
 * Actions performed:
 *  - Starts the syncing of block statuses when the status of a block changes for master product.
 *      This is necessary because blocks on the page of product offer must not differ from the blocks on the page of its master product.
 *
 * @param array $snapping_data Array of snapping data
 *
 * @return void
 *
 * @see \Tygh\BlockManager\Block::updateSnapping
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_master_products_update_snapping_post(array $snapping_data)
{
    if (
        empty($snapping_data['snapping_id'])
        || empty($snapping_data['object_type'])
        || $snapping_data['object_type'] !== 'products'
    ) {
        return;
    }

    $current_product_ids = [];
    $product_ids = fn_explode(',', $snapping_data['object_ids']);

    if (!empty($snapping_data['current_object_ids'])) {
        $current_product_ids = fn_explode(',', $snapping_data['current_object_ids']);
    }

    $deleted_product_ids = array_diff($current_product_ids, $product_ids);
    $added_product_ids = array_diff($product_ids, $current_product_ids);
    $affected_product_ids = array_merge($deleted_product_ids, $added_product_ids);

    if (empty($affected_product_ids)) {
        return;
    }

    $sync_service = ServiceProvider::getService();
    $sync_service->onTableChanged('bm_block_statuses', $affected_product_ids, ['snapping_id' => $snapping_data['snapping_id']]);
}

/**
 * The `direct_payments_cart_service_get_group_products_get_company_id` hook handler.
 *
 * Action performed:
 *    - Changes detected product id for loaded file as product option from master product id to needed vendor product id.
 *    - Correctly detects owner of product.
 *
 * @param array<string, string> $products_data Products in the cart
 * @param int|string            $key           Cart ID
 * @param array<string, string> $item          Product data
 * @param int                   $vendor_id     Detected company ID
 *
 * @return void
 */
function fn_master_products_direct_payments_cart_service_get_group_products_get_company_id(array $products_data, $key, array &$item, &$vendor_id)
{
    if (!empty($vendor_id) || $key !== 'custom_files') {
        return;
    }
    $product_id_map = ServiceProvider::getProductIdMap();
    $option_data = explode('_', reset($item));
    $data_id = empty($option_data[0]) ? 0 : (int) $option_data[0];
    if (!$product_id_map->isMasterProduct($data_id)) {
        return;
    }
    foreach (array_keys($products_data) as $vendor_product_id) {
        if ($product_id_map->getMasterProductId((int) $vendor_product_id) !== $data_id) {
            continue;
        }
        $option_identifier_data = reset($item);
        $option_identifier = key($item);
        $item[$option_identifier] = str_replace((string) $data_id, $vendor_product_id, $option_identifier_data);
        $vendor_id = $product_id_map->getVendorProductCompanyId((int) $vendor_product_id);
        break;
    }
}

/**
 * The `google_sitemap_write_products_to_sitemap_before_getting_products` hook handler.
 *
 * Action performed:
 *    - Add condition for skip master products
 *
 * @param \Tygh\Storefront\Storefront                                $storefront         Storefront to generate sitemap for
 * @param string                                                     $last_modified_time Sitemap's last modified time in format YYYY-MM-DD
 * @param string                                                     $change_frequency   Sitemap item's update frequency
 * @param float                                                      $priority           Sitemap item's priority
 * @param resource                                                   $file               File the sitemap is written into
 * @param int                                                        $link_counter       Amount of links in the current sitemap file
 * @param int                                                        $file_counter       Amount of sitemap files
 * @param string                                                     $sitemap_header     Sitemap header
 * @param string                                                     $sitemap_footer     Sitemap footer
 * @param string[]                                                   $languages          List of languages to generate the sitemap for
 * @param array<int, array<string, string|int|null|array<int, int>>> $products           Products data
 * @param array<string, string|int|null|array<int, int>>             $product            Product data
 * @param int                                                        $page               Page number
 * @param array<string, string|bool|array<int, string>>              $params             Additional parameters
 * @param array<string, string>                                      $query_condition    Query condition
 *
 * @return void
 *
 * @param-out array<int|string, array<int, string>|string> $query_condition
 */
function fn_master_products_google_sitemap_write_products_to_sitemap_before_getting_products(
    Tygh\Storefront\Storefront $storefront,
    $last_modified_time,
    $change_frequency,
    $priority,
    $file,
    $link_counter,
    $file_counter,
    $sitemap_header,
    $sitemap_footer,
    array $languages,
    $products,
    $product,
    $page,
    $params,
    array &$query_condition
) {
    $query_condition[] = ['company_id', '!=', '0'];
}

/**
 * The `google_sitemap_write_products_to_sitemap_before_getting_link` hook handler.
 *
 * Action performed:
 *    - Add master product in sitemap if vendor offer is available
 *
 * @param \Tygh\Storefront\Storefront                                $storefront         Storefront to generate sitemap for
 * @param string                                                     $last_modified_time Sitemap's last modified time in format YYYY-MM-DD
 * @param string                                                     $change_frequency   Sitemap item's update frequency
 * @param float                                                      $priority           Sitemap item's priority
 * @param resource                                                   $file               File the sitemap is written into
 * @param int                                                        $link_counter       Amount of links in the current sitemap file
 * @param int                                                        $file_counter       Amount of sitemap files
 * @param string                                                     $sitemap_header     Sitemap header
 * @param string                                                     $sitemap_footer     Sitemap footer
 * @param string[]                                                   $languages          List of languages to generate the sitemap for
 * @param array<int, array<string, string|int|null|array<int, int>>> $products           Products data
 * @param array<string, string|int|null|array<int, int>>             $product            Product data
 * @param int                                                        $page               Page number
 * @param array<string, string|bool|array<int, string>>              $params             Additional parameters
 * @param array<int, array<string, string|int|null|array<int, int>>> $sitemap_items      Additional products in sitemap
 *
 * @return void
 *
 * @param-out array<int, array<string, string|int|null|array<int, int>>> $sitemap_items
 */
function fn_master_products_google_sitemap_write_products_to_sitemap_before_getting_link(
    Tygh\Storefront\Storefront $storefront,
    $last_modified_time,
    $change_frequency,
    $priority,
    $file,
    $link_counter,
    $file_counter,
    $sitemap_header,
    $sitemap_footer,
    array $languages,
    $products,
    $product,
    $page,
    $params,
    &$sitemap_items
) {
    static $master_products = [];
    static $storefront_id = 0;

    if ($storefront_id !== $storefront->storefront_id) {
        $storefront_id = $storefront->storefront_id;
        $master_products = [];
    }

    if (
        empty($product['master_product_id'])
        || array_key_exists((int) $product['master_product_id'], $sitemap_items)
        || array_key_exists((int) $product['master_product_id'], $master_products)
    ) {
        return;
    }

    $master_products[$product['master_product_id']] = $product['master_product_id'];
    $sitemap_items[(int) $product['master_product_id']] = [
        'product_id' => (int) $product['master_product_id']
    ];
}

/**
 * The "get_additional_information_post" hook handler.
 *
 * @param array $product      Product data
 * @param array $product_data The data of the changed product
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 *
 * @return void
 *
 * @see fn_get_additional_product_data
 */
function fn_master_products_get_additional_information_post(array &$product, array $product_data)
{
    if (
        !SiteArea::isAdmin(AREA)
        && $_REQUEST['dispatch'] === 'products.options'
        && (int) $product['master_product_id'] === 0
        && !empty($product['best_product_offer_id'])
    ) {
        $_auth = Tygh::$app['session']['auth'];
        $_data = reset($product_data['product_data']);
        $selected_options = empty($_data['product_options']) ? [] : $_data['product_options'];
        unset($selected_options['AOC']);

        /** @var array<array-key, string|int|bool> $best_product_offer */
        $best_product_offer = fn_get_product_data($product['best_product_offer_id'], $_auth, CART_LANGUAGE, '', true, true, true, true);
        $best_product_offer['changed_option'] = isset($product_data['changed_option']) ? reset($product_data['changed_option']) : '';
        $best_product_offer['selected_options'] = $selected_options;
        fn_gather_additional_product_data($best_product_offer);

        $product['price'] = $best_product_offer['price'];
        $product['list_price'] = $best_product_offer['list_price'];
        if (
            empty($product['discount'])
            && !empty($product['list_price'])
            && !empty($product['price'])
            && $product['list_price'] > $product['price']
        ) {
            $product['list_discount'] = fn_format_price($product['list_price'] - $product['price']);
            $product['list_discount_prc'] = sprintf('%d', round($product['list_discount'] * 100 / $product['list_price']));
        }
    }
}
