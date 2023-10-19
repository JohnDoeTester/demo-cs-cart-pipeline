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

use Tygh\BlockManager\Block;
use Tygh\BlockManager\ProductTabs;
use Tygh\Enum\ImagePairTypes;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\OutOfStockActions;
use Tygh\Enum\ProductFeaturesDisplayOn;
use Tygh\Enum\ProductTracking;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;
use Tygh\Languages\Languages;
use Tygh\Navigation\LastView;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Storefront\Storefront;
use Tygh\Themes\Themes;
use Tygh\Tools\SecurityHelper;
use Tygh\Enum\VendorStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Settings;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Gets full product data by its id
 *
 * @param int    $product_id                     Product ID
 * @param array  $auth                           Array with authorization data
 * @param string $lang_code                      The 2 letters language code
 * @param string $field_list                     List of fields for retrieving
 * @param bool   $get_add_pairs                  Get additional images
 * @param bool   $get_main_pair                  Get main images
 * @param bool   $get_taxes                      Get taxes
 * @param bool   $get_qty_discounts              Get quantity discounts
 * @param bool   $preview                        Is product previewed by admin
 * @param bool   $features                       Get product features
 * @param bool   $skip_company_condition         Skip company condition and retrieve product data for displayin on other store page. (Works only in ULT)
 * @param bool   $feature_variants_selected_only Gets only selected product feature variants
 * @param array  $params                         Additional parameters
 *
 * @return array|false Array with product data
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_get_product_data(//phpcs:ignore Squiz.Commenting.FunctionComment.TypeHintMissing
    $product_id,
    &$auth = [],
    $lang_code = CART_LANGUAGE,
    $field_list = '',
    $get_add_pairs = true,
    $get_main_pair = true,
    $get_taxes = true,
    $get_qty_discounts = false,
    $preview = false,
    $features = true,
    $skip_company_condition = false,
    $feature_variants_selected_only = false,
    array $params = []
) {
    $product_id = (int) $product_id;
    $auth = (array) $auth;

    $default_params = [
        'company_statuses' => [
            VendorStatuses::ACTIVE
        ]
    ];

    $params = array_merge($default_params, $params);

    /**
     * Change parameters for getting product data
     *
     * @param int                   $product_id             Product ID
     * @param array<string, string> $auth                   Array with authorization data
     * @param string                $lang_code              Two-letters language code
     * @param string                $field_list             List of fields for retrieving
     * @param bool                  $get_add_pairs          Get additional images
     * @param bool                  $get_main_pair          Get main images
     * @param bool                  $get_taxes              Get taxes
     * @param bool                  $get_qty_discounts      Get quantity discounts
     * @param bool                  $preview                Is product previewed by admin
     * @param bool                  $features               Get product features
     * @param bool                  $skip_company_condition Skip company condition and retrieve product data for displaying on other store page (ULT only).
     * @param array<string, string> $params                 Array of additional params
     */
    fn_set_hook(
        'get_product_data_pre',
        $product_id,
        $auth,
        $lang_code,
        $field_list,
        $get_add_pairs,
        $get_main_pair,
        $get_taxes,
        $get_qty_discounts,
        $preview,
        $features,
        $skip_company_condition,
        $params
    );

    $usergroup_ids = !empty($auth['usergroup_ids']) ? $auth['usergroup_ids'] : [];

    $runtime_company_id = Registry::get('runtime.company_id');
    $storefront = StorefrontProvider::getStorefront();

    if (empty($product_id)) {
        return false;
    }

    if (empty($field_list)) {
        $descriptions_list = '?:product_descriptions.*';
        $field_list = db_quote('?:products.*, ?p', $descriptions_list);
    }
    $field_list .= ', MIN(IF(?:product_prices.percentage_discount = 0, ?:product_prices.price,'
        . ' ?:product_prices.price - (?:product_prices.price * ?:product_prices.percentage_discount)/100)) as price';

    $company_ordering = db_quote('?:categories.storefront_id IN (?n) DESC,', [0, $storefront->storefront_id]);

    $field_list .= db_quote(
        ', GROUP_CONCAT('
        . ' CASE'
        . '   WHEN (?:products_categories.link_type = ?s) THEN CONCAT(?:products_categories.category_id, ?s)'
        . '   ELSE ?:products_categories.category_id'
        . ' END'
        . ' ORDER BY ?p (?:products_categories.link_type = ?s) DESC,'
        . ' ?:products_categories.category_position ASC,'
        . ' ?:products_categories.category_id ASC) as category_ids',
        'M',
        'M',
        $company_ordering,
        'M'
    );
    $field_list .= ', popularity.total as popularity';

    $price_usergroup = db_quote(
        ' AND ?:product_prices.usergroup_id IN (?n)',
        SiteArea::isAdmin(AREA) && !defined('ORDER_MANAGEMENT')
            ? USERGROUP_ALL
            : array_merge([USERGROUP_ALL], $usergroup_ids)
    );

    $condition = $avail_cond = '';
    $join = db_quote(
        ' LEFT JOIN ?:product_descriptions'
        . ' ON ?:product_descriptions.product_id = ?:products.product_id'
        . ' AND ?:product_descriptions.lang_code = ?s',
        $lang_code
    );

    if (!fn_allowed_for('ULTIMATE')) {
        if (!$skip_company_condition) {
            $avail_cond .= fn_get_company_condition('?:products.company_id');

            if (SiteArea::isStorefront(AREA)) {
                $avail_cond .= db_quote(' AND ?:categories.storefront_id IN (?n)', [0, $storefront->storefront_id]);
            }
        }
    } else {
        if (!$skip_company_condition && $runtime_company_id) {
            if (SiteArea::isStorefront(AREA)) {
                $avail_cond .= db_quote(' AND ?:categories.storefront_id IN (?n)', [0, $storefront->storefront_id]);
            } else {
                $avail_cond .= db_quote(' AND (?:categories.storefront_id IN (?n)', [0, $storefront->storefront_id])
                    . ' OR ' . fn_get_company_condition('?:products.company_id', false) . ')';
            }
        }

        if ($runtime_company_id) {
            $field_list .= ', IF('
                . 'shared_prices.product_id IS NOT NULL,'
                . 'MIN(IF(shared_prices.percentage_discount = 0, shared_prices.price,'
                    . ' shared_prices.price - (shared_prices.price * shared_prices.percentage_discount)/100)),'
                . 'MIN(IF(?:product_prices.percentage_discount = 0, ?:product_prices.price,'
                    . ' ?:product_prices.price - (?:product_prices.price * ?:product_prices.percentage_discount)/100))'
                . ') as price';

            $shared_prices_usergroup = db_quote(
                ' AND shared_prices.usergroup_id IN (?n)',
                SiteArea::isAdmin(AREA) && !defined('ORDER_MANAGEMENT') ? USERGROUP_ALL : array_merge([USERGROUP_ALL], $usergroup_ids)
            );

            $join .= db_quote(
                ' LEFT JOIN ?:ult_product_prices shared_prices ON shared_prices.product_id = ?:products.product_id'
                . ' AND shared_prices.company_id = ?i AND shared_prices.lower_limit = 1 ?p',
                $runtime_company_id,
                $shared_prices_usergroup
            );
        }
    }

    if (empty($preview) && SiteArea::isStorefront(AREA)) {
        $avail_cond .= ' AND (' . fn_find_array_in_set($usergroup_ids, '?:categories.usergroup_ids', true) . ')';
        $avail_cond .= ' AND (' . fn_find_array_in_set($usergroup_ids, '?:products.usergroup_ids', true) . ')';
        $avail_cond .= db_quote(
            ' AND ?:categories.status IN (?a) AND ?:products.status IN (?a)',
            [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN],
            [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN]
        );
    }

    if (fn_allowed_for('MULTIVENDOR') && !empty($params['use_i18n_fields'])) {
        $field_list .= ', company_descr.i18n_company as company_name';
        $join .= db_quote(
            ' LEFT JOIN ?:company_descriptions as company_descr ON company_descr.company_id = ?:products.company_id AND company_descr.lang_code = ?s',
            $lang_code
        );
    } else {
        $field_list .= ', companies.company as company_name';
    }

    $join .= ' LEFT JOIN ?:companies as companies ON companies.company_id = ?:products.company_id';
    if (!$preview && SiteArea::isStorefront(AREA)) {
        $condition .= db_quote(
            ' AND (companies.status IN (?a) OR ?:products.company_id = ?i)',
            $params['company_statuses'],
            0
        );
    }

    $avail_cond .= fn_get_localizations_condition('?:products.localization');
    $avail_cond .= fn_get_localizations_condition('?:categories.localization');

    $join .= db_quote(
        ' INNER JOIN ?:products_categories ON ?:products_categories.product_id = ?:products.product_id'
        . ' INNER JOIN ?:categories ON ?:categories.category_id = ?:products_categories.category_id ?p',
        $avail_cond
    );
    $join .= ' LEFT JOIN ?:product_popularity as popularity ON popularity.product_id = ?:products.product_id';

    /**
     * Change SQL parameters for product data select
     *
     * @param int    $product_id      Product ID
     * @param string $field_list      List of fields for retrieving
     * @param string $join            String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param array  $auth            Array with authorization data
     * @param string $lang_code       Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param string $condition       Condition for selecting product data
     * @param string $price_usergroup Condition for usergroup prices
     */
    fn_set_hook('get_product_data', $product_id, $field_list, $join, $auth, $lang_code, $condition, $price_usergroup);

    $product_data = db_get_row(
        'SELECT ?p FROM ?:products'
        . ' LEFT JOIN ?:product_prices'
        . ' ON ?:product_prices.product_id = ?:products.product_id'
        . ' AND ?:product_prices.lower_limit = 1 ?p'
        . ' ?p'
        . ' WHERE ?:products.product_id = ?i'
        . ' ?p'
        . ' GROUP BY ?:products.product_id',
        $field_list,
        $price_usergroup,
        $join,
        $product_id,
        $condition
    );

    if (empty($product_data)) {
        return false;
    }

    $product_data['base_price'] = $product_data['price']; // save base price (without discounts, etc...)

    list($product_data['category_ids'], $product_data['main_category']) = fn_convert_categories($product_data['category_ids']);

    // manually regroup categories
    if (!$runtime_company_id && fn_allowed_for('ULTIMATE')) {
        list($categories_data,) = fn_get_categories([
            'simple'         => false,
            'group_by_level' => false,
            'limit'          => 0,
            'items_per_page' => 0,
            'category_ids'   => $product_data['category_ids'],
            'item_ids'       => implode(',', $product_data['category_ids']),
        ]);
        $categories_groups = [];

        foreach ($categories_data as $category) {
            if ((int) $category['category_id'] === (int) $product_data['main_category']) {
                $main_category_owner = $category['company_id'];
            }
            if (!isset($categories_groups[$category['company_id']])) {
                $categories_groups[$category['company_id']] = [];
            }
            $categories_groups[$category['company_id']][] = $category['category_id'];
        }

        if (isset($main_category_owner)) {
            $categories_groups = [$main_category_owner => $categories_groups[$main_category_owner]] + $categories_groups;
        }

        $product_data['category_ids'] = [];

        foreach ($categories_groups as $category_ids) {
            $product_data['category_ids'] = array_merge($product_data['category_ids'], $category_ids);
        }
    }

    // Generate meta description automatically
    if (
        !empty($product_data['full_description'])
        && empty($product_data['meta_description'])
        && defined('AUTO_META_DESCRIPTION')
        && !SiteArea::isAdmin(AREA)
    ) {
        $product_data['meta_description'] = fn_generate_meta_description($product_data['full_description']);
    }

    $product_data['product_id'] = $product_id;

    // Get product shipping settings
    if (!empty($product_data['shipping_params'])) {
        $product_data = array_merge(unserialize($product_data['shipping_params']), $product_data);
    }

    // Get additional image pairs
    if ($get_add_pairs) {
        $product_data['image_pairs'] = fn_get_image_pairs($product_id, 'product', 'A', true, true, $lang_code);
    }

    // Get main image pair
    if ($get_main_pair) {
        $product_data['main_pair'] = fn_get_image_pairs($product_id, 'product', 'M', true, true, $lang_code);
    }

    // Get taxes
    $product_data['tax_ids'] = !empty($product_data['tax_ids']) ? explode(',', $product_data['tax_ids']) : [];

    // Get qty discounts
    if ($get_qty_discounts) {
        fn_get_product_prices($product_id, $product_data, $auth);
    }

    if (fn_allowed_for('ULTIMATE')) {
        $product_data['shared_product'] = fn_ult_is_shared_product($product_id);
    }

    if ($features) {
        // Get product features
        $path = !empty($product_data['category_ids']) ? fn_get_category_ids_with_parent($product_data['category_ids']) : '';

        $_params = [
            'category_ids'           => $path,
            'product_id'             => $product_id,
            'product_company_id'     => !empty($product_data['company_id']) ? $product_data['company_id'] : 0,
            'statuses'               => SiteArea::isStorefront(AREA) ? [ObjectStatuses::ACTIVE] : [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN],
            'variants'               => true,
            'plain'                  => false,
            'display_on'             => SiteArea::isAdmin(AREA) ? '' : 'product',
            'existent_only'          => !SiteArea::isAdmin(AREA),
            'variants_selected_only' => $feature_variants_selected_only
        ];
        list($product_data['product_features']) = fn_get_product_features($_params, 0, $lang_code);

        if (SiteArea::isStorefront(AREA)) {
            $product_data['header_features'] = fn_get_product_features_list($product_data, ProductFeaturesDisplayOn::HEADER);
        }
    } else {
        $product_data['product_features'] = fn_get_product_features_list($product_data, ProductFeaturesDisplayOn::ALL);
    }

    $product_data = fn_normalize_product_overridable_fields($product_data);
    $product_data['detailed_params']['info_type'] = 'D';

    /**
     * Particularize product data
     *
     * @param array  $product_data List with product fields
     * @param array  $auth         Array with authorization data
     * @param bool   $preview      Is product previewed by admin
     * @param string $lang_code    2-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_product_data_post', $product_data, $auth, $preview, $lang_code);

    return !empty($product_data) ? $product_data : false;
}

/**
 * Gets product name by id
 *
 * @param mixed $product_id Integer product id, or array of product ids
 * @param string $lang_code 2-letter language code
 * @param boolean $as_array Flag: if set, result will be returned as array <i>(product_id => product)</i>; otherwise only product name will be returned
 * @return mixed In case 1 <i>product_id</i> is passed and <i>as_array</i> is not set, a product name string is returned;
 * Array <i>(product_id => product)</i> for all given <i>product_ids</i>;
 * <i>False</i> if <i>$product_id</i> is not defined
 */
function fn_get_product_name($product_id, $lang_code = CART_LANGUAGE, $as_array = false)
{
    /**
     * Change parameters for getting product name
     *
     * @param int/array $product_id Product integer identifier
     * @param string    $lang_code  Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param boolean   $as_array   Flag determines if even one product name should be returned as array
     */
    fn_set_hook('get_product_name_pre', $product_id, $lang_code, $as_array);

    $result = false;
    if (!empty($product_id)) {
        if (!is_array($product_id) && strpos($product_id, ',') !== false) {
            $product_id = explode(',', $product_id);
        }

        $field_list = 'pd.product_id as product_id, pd.product as product';
        $join = '';
        if (is_array($product_id) || $as_array == true) {
            $condition = db_quote(' AND pd.product_id IN (?n) AND pd.lang_code = ?s', $product_id, $lang_code);
        } else {
            $condition = db_quote(' AND pd.product_id = ?i AND pd.lang_code = ?s', $product_id, $lang_code);
        }

        /**
         * Change SQL parameters for getting product name
         *
         * @param int/array $product_id Product integer identifier
         * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
         * @param boolean $as_array Flag determines if even one product name should be returned as array
         * @param string $field_list List of fields for retrieving
         * @param string $join String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
         * @param string $condition Condition for selecting product name
         */
        fn_set_hook('get_product_name', $product_id, $lang_code, $as_array, $field_list, $join, $condition);

        $result = db_get_hash_single_array("SELECT $field_list FROM ?:product_descriptions pd $join WHERE 1 $condition", array('product_id', 'product'));
        if (!(is_array($product_id) || $as_array == true)) {
            if (isset($result[$product_id])) {
                $result = $result[$product_id];
            } else {
                $result = null;
            }
        }
    }

    /**
     * Change product name selected by $product_id & $lang_code params
     *
     * @param int/array    $product_id Product integer identifier
     * @param string       $lang_code  Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param boolean      $as_array   Flag determines if even one product name should be returned as array
     * @param string/array $result     String containig product name or array with products names depending on $product_id param
     */
    fn_set_hook('get_product_name_post', $product_id, $lang_code, $as_array, $result);

    return $result;
}

/**
 * Gets product price by id
 *
 * @param int $product_id Product id
 * @param int $amount Optional parameter: necessary to calculate quantity discounts
 * @param array $auth Array of authorization data
 * @return float Price
 */
function fn_get_product_price($product_id, $amount, &$auth)
{
    /**
     * Change parameters for getting product price
     *
     * @param int   $product_id Product identifier
     * @param int   $amount     Amount of products, required to get wholesale price
     * @param array $auth       Array of user authentication data (e.g. uid, usergroup_ids, etc.)
     */
    fn_set_hook('get_product_price_pre', $product_id, $amount, $auth);

    $usergroup_condition = db_quote(
        'AND ?:product_prices.usergroup_id IN (?n)',
        (SiteArea::isStorefront(AREA) || defined('ORDER_MANAGEMENT') || defined('API'))
            ? array_merge([USERGROUP_ALL], $auth['usergroup_ids'])
            : USERGROUP_ALL
    );

    $price = db_get_field(
        "SELECT MIN(IF(?:product_prices.percentage_discount = 0, ?:product_prices.price, "
        . "?:product_prices.price - (?:product_prices.price * ?:product_prices.percentage_discount)/100)) as price "
        . "FROM ?:product_prices "
        . "WHERE lower_limit <=?i AND ?:product_prices.product_id = ?i ?p "
        . "ORDER BY lower_limit DESC LIMIT 1",
        $amount, $product_id, $usergroup_condition
    );

    /**
     * Change product price
     *
     * @param int   $product_id Product identifier
     * @param int   $amount     Amount of products, required to get wholesale price
     * @param array $auth       Array of user authentication data (e.g. uid, usergroup_ids, etc.)
     * @param float $price
     */
    fn_set_hook('get_product_price_post', $product_id, $amount, $auth, $price);

    return (empty($price))? 0 : floatval($price);
}

/**
 * Gets product descriptions to the given language
 *
 * @param array $products Array of products
 * @param string $fields List of fields to be translated
 * @param string $lang_code 2-letter language code.
 * @param boolean $translate_options Flag: if set, product options are also translated; otherwise not
 */
function fn_translate_products(&$products, $fields = '',$lang_code = '', $translate_options = false)
{
    /**
     * Change parameters for translating product text data
     *
     * @param array  $products          List of products
     * @param string $fields            Fields of products that should be translated
     * @param string $lang_code         Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param bool   $translate_options Flag that defines whether we want to translate product options. Set it to "true" in case you want.
     */
    fn_set_hook('translate_products_pre', $products, $fields, $lang_code, $translate_options);

    if (empty($fields)) {
        $fields = 'product, short_description, full_description';
    }

    foreach ($products as $k => $v) {
        if (!empty($v['deleted_product'])) {
            continue;
        }
        $descriptions = db_get_row("SELECT $fields FROM ?:product_descriptions WHERE product_id = ?i AND lang_code = ?s", $v['product_id'], $lang_code);
        foreach ($descriptions as $k1 => $v1) {
            $products[$k][$k1] = $v1;
        }
        if ($translate_options && !empty($v['product_options'])) {
            foreach ($v['product_options'] as $k1 => $v1) {
                $option_descriptions = db_get_row("SELECT option_name, option_text, description, comment FROM ?:product_options_descriptions WHERE option_id = ?i AND lang_code = ?s", $v1['option_id'], $lang_code);
                foreach ($option_descriptions as $k2 => $v2) {
                    $products[$k]['product_options'][$k1][$k2] = $v2;
                }

                if ($v1['option_type'] == 'C') {
                    $products[$k]['product_options'][$k1]['variant_name'] = (empty($v1['position'])) ? __('no', '', $lang_code) : __('yes', '', $lang_code);
                } elseif ($v1['option_type'] == 'S' || $v1['option_type'] == 'R') {
                    $variant_description = db_get_field("SELECT variant_name FROM ?:product_option_variants_descriptions WHERE variant_id = ?i AND lang_code = ?s", $v1['value'], $lang_code);
                    $products[$k]['product_options'][$k1]['variant_name'] = $variant_description;
                }
            }
        }
    }

    /**
     * Change translated products data
     *
     * @param array  $products          List of products
     * @param string $fields            Fields of products that should be translated
     * @param string $lang_code         Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param bool   $translate_options Flag that defines whether we want to translate product options. Set it to "true" in case you want.
     */
    fn_set_hook('translate_products_post', $products, $fields, $lang_code, $translate_options);
}

/**
 * Gets additional products data
 *
 * @param array  $products  List of products
 * @param array  $params    Array of flags which determines which data should be gathered
 * @param string $lang_code Two-letter language code
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_gather_additional_products_data(&$products, $params, $lang_code = CART_LANGUAGE) //phpcs:ignore Squiz.Commenting.FunctionComment.TypeHintMissing
{
    /**
     * Change parameters for gathering additional products data
     *
     * @param array  $products  List of products
     * @param array  $params    Array of flags which determines which data should be gathered
     * @param string $lang_code Two-letter language code
     */
    fn_set_hook('gather_additional_products_data_pre', $products, $params, $lang_code);

    if (empty($products)) {
        return;
    }

    // Set default values to input params
    $default_params = [
        'get_icon'                    => false,
        'get_detailed'                => false,
        'get_additional'              => false,
        'get_options'                 => true,
        'get_discounts'               => true,
        'get_features'                => false,
        'get_extra'                   => false,
        'get_taxed_prices'            => true,
        'get_for_one_product'         => !is_array(reset($products)),
        'detailed_params'             => true,
        'features_display_on'         => SiteArea::STOREFRONT,
        'get_active_options'          => false,
        'get_only_selectable_options' => false
    ];

    $params = array_merge($default_params, $params);

    $auth = & Tygh::$app['session']['auth'];
    $allow_negative_amount = Registry::get('settings.General.allow_negative_amount');
    $inventory_tracking = Registry::get('settings.General.inventory_tracking');

    if ($params['get_for_one_product']) {
        $products = [$products];
    }

    $product_ids = array_column($products, 'product_id');

    if ($params['get_icon'] || $params['get_detailed']) {
        $products_images = fn_get_image_pairs($product_ids, 'product', 'M', $params['get_icon'], $params['get_detailed'], $lang_code);
    }

    if ($params['get_additional']) {
        $additional_images = fn_get_image_pairs($product_ids, 'product', 'A', true, true, $lang_code);
    }

    if ($params['get_options'] || $params['get_active_options']) {
        $product_options = fn_get_product_options(
            $product_ids,
            $lang_code,
            $params['get_only_selectable_options'],
            false,
            $params['get_active_options']
        );
    } else {
        $has_product_options = db_get_hash_array("SELECT a.option_id, a.product_id FROM ?:product_options AS a WHERE a.product_id IN (?n) AND a.status = 'A'", 'product_id', $product_ids);
        $has_product_options_links = db_get_hash_array("SELECT c.option_id, c.product_id FROM ?:product_global_option_links AS c LEFT JOIN ?:product_options AS a ON a.option_id = c.option_id WHERE a.status = 'A' AND c.product_id IN (?n)", 'product_id', $product_ids);
    }

    /**
     * Changes before gathering additional products data
     *
     * @param array $product_ids               Array of product identifiers
     * @param array $params                    Parameters for gathering data
     * @param array $products                  Array of products
     * @param mixed $auth                      Array of user authentication data
     * @param array $products_images           Array with product main images
     * @param array $additional_images         Array with product additional images
     * @param array $product_options           Array with product options
     * @param array $has_product_options       Array of flags determines if product has options
     * @param array $has_product_options_links Array of flags determines if product has option links
     */
    fn_set_hook('gather_additional_products_data_params', $product_ids, $params, $products, $auth, $products_images, $additional_images, $product_options, $has_product_options, $has_product_options_links);

    static $is_unit_price_based_taxing = null;
    if ($is_unit_price_based_taxing === null) {
        $is_unit_price_based_taxing = Registry::get('settings.Checkout.tax_calculation') === 'unit_price';
    }

    // foreach $products
    foreach ($products as &$_product) {
        $product = $_product;
        $product_id = $product['product_id'];

        // Get images
        if ($params['get_icon'] == true || $params['get_detailed'] == true) {
            if (empty($product['main_pair']) && !empty($products_images[$product_id])) {
                $product['main_pair'] = reset($products_images[$product_id]);
            }
        }

        if ($params['get_additional'] == true) {
            if (empty($product['image_pairs']) && !empty($additional_images[$product_id])) {
                $product['image_pairs'] = $additional_images[$product_id];
            }
        }

        if (isset($product['price']) && !isset($product['base_price'])) {
            $product['base_price'] = $product['price']; // save base price (without discounts, etc...)
        }

        /**
         * Changes before gathering product options
         *
         * @param array $product Product data
         * @param mixed $auth Array of user authentication data
         * @param array $params Parameteres for gathering data
         */
        fn_set_hook('gather_additional_product_data_before_options', $product, $auth, $params);

        // Convert product categories
        if (!empty($product['category_ids']) && !is_array($product['category_ids'])) {
            list($product['category_ids'], $product['main_category']) = fn_convert_categories($product['category_ids']);

        } elseif (array_key_exists('category_id', $product) && empty($product['category_ids'])) {
            $product['category_ids'] = array();
            $product['main_category'] = 0;
        }

        $product['selected_options'] = empty($product['selected_options']) ? array() : $product['selected_options'];

        // Get product options
        if ($params['get_options'] && !empty($product_options[$product['product_id']])) {
            if (!isset($product['options_type']) || !isset($product['exceptions_type'])) {
                $types = db_get_row('SELECT options_type, exceptions_type FROM ?:products WHERE product_id = ?i', $product['product_id']);
                $types = fn_normalize_product_overridable_fields($types);
                $product['options_type'] = $types['options_type'];
                $product['exceptions_type'] = $types['exceptions_type'];
            }

            if (empty($product['product_options'])) {
                $product['product_options'] = $product_options[$product_id];
            }

            if (!empty($product['combination'])) {
                $selected_options = fn_get_product_options_by_combination($product['combination']);

                foreach ($selected_options as $option_id => $variant_id) {
                    if (isset($product['product_options'][$option_id])) {
                        $product['product_options'][$option_id]['value'] = $variant_id;
                    }
                }
            }

            $product = fn_apply_options_rules($product);

            if (!empty($params['get_icon']) || !empty($params['get_detailed'])) {
                // Get product options images
                if (!empty($product['combination_hash']) && !empty($product['product_options'])) {
                    $image = fn_get_image_pairs($product['combination_hash'], 'product_option', 'M', $params['get_icon'], $params['get_detailed'], $lang_code);
                    if (!empty($image)) {
                        $product['main_pair'] = $image;
                    }
                }
            }
            $product['has_options'] = !empty($product['product_options']);

            $exceptions = fn_get_product_exceptions($product['product_id'], true);
            $product = fn_apply_exceptions_rules($product, $exceptions);

            $selected_options = isset($product['selected_options']) ? $product['selected_options'] : array();
            foreach ($product['product_options'] as $option) {
                if (!empty($option['disabled'])) {
                    unset($selected_options[$option['option_id']]);
                }
            }
            $product['selected_options'] = $selected_options;

            // Change price
            if (isset($product['price']) && empty($product['modifiers_price'])) {
                $product['base_modifier'] = fn_apply_options_modifiers($selected_options, $product['base_price'], 'P', array(), array('product_data' => $product));
                $old_price = $product['price'];
                $product['price'] = fn_apply_options_modifiers($selected_options, $product['price'], 'P', array(), array('product_data' => $product));

                if (empty($product['original_price'])) {
                    $product['original_price'] = $old_price;
                }

                $product['original_price'] = fn_apply_options_modifiers($selected_options, $product['original_price'], 'P', array(), array('product_data' => $product));
                $product['modifiers_price'] = $product['price'] - $old_price;
            }

            if (isset($product['list_price']) && (float) $product['list_price']) {
                $product['list_price'] = fn_apply_options_modifiers($selected_options, $product['list_price'], 'P', array(), array('product_data' => $product));
            }

            if (!empty($product['prices']) && is_array($product['prices'])) {
                foreach ($product['prices'] as $pr_k => $pr_v) {
                    $product['prices'][$pr_k]['price'] = fn_apply_options_modifiers($selected_options, $pr_v['price'], 'P', array(), array('product_data' => $product));
                }
            }
        } else {
            $product['has_options'] = (!empty($has_product_options[$product_id]) || !empty($has_product_options_links[$product_id]))? true : false;
            $product['product_options'] = empty($product['product_options']) ? array() : $product['product_options'];
        }

        unset($selected_options);

        /**
         * Changes before gathering product discounts
         *
         * @param array $product Product data
         * @param mixed $auth Array of user authentication data
         * @param array $params Parameteres for gathering data
         */
        fn_set_hook('gather_additional_product_data_before_discounts', $product, $auth, $params);

        // Get product discounts
        if ($params['get_discounts'] && !isset($product['exclude_from_calculate'])) {
            fn_promotion_apply('catalog', $product, $auth);
            if (!empty($product['prices']) && is_array($product['prices'])) {
                $product_copy = $product;
                foreach ($product['prices'] as $pr_k => $pr_v) {
                    $product_copy['base_price'] = $product_copy['price'] = $pr_v['price'];
                    fn_promotion_apply('catalog', $product_copy, $auth);
                    $product['prices'][$pr_k]['price'] = $product_copy['price'];
                }
            }

            if (empty($product['discount']) && !empty($product['list_price']) && !empty($product['price']) && floatval($product['price']) && $product['list_price'] > $product['price']) {
                $product['list_discount'] = fn_format_price($product['list_price'] - $product['price']);
                $product['list_discount_prc'] = sprintf('%d', round($product['list_discount'] * 100 / $product['list_price']));
            }
        }

        // FIXME: old product options scheme
        $product['discounts'] = array('A' => 0, 'P' => 0);
        if (!empty($product['promotions'])) {
            foreach ($product['promotions'] as $v) {
                foreach ($v['bonuses'] as $a) {
                    if ($a['discount_bonus'] == 'to_fixed') {
                        $product['discounts']['A'] += $a['discount'];
                    } elseif ($a['discount_bonus'] == 'by_fixed') {
                        $product['discounts']['A'] += $a['discount_value'];
                    } elseif ($a['discount_bonus'] == 'to_percentage') {
                        $product['discounts']['P'] += 100 - $a['discount_value'];
                    } elseif ($a['discount_bonus'] == 'by_percentage') {
                        $product['discounts']['P'] += $a['discount_value'];
                    }
                }
            }
        }

        // Add product prices with taxes and without taxes
        if (
            $params['get_taxed_prices']
            && SiteArea::isStorefront(AREA)
            && $is_unit_price_based_taxing
        ) {
            fn_get_taxed_and_clean_prices($product, $auth);
            if (
                fn_get_customer_tax_exempt_status([], $auth)
                && isset($product['clean_price'])
            ) {
                $product['price'] = $product['clean_price'];
            }
        }

        if ($params['get_features'] && !isset($product['product_features'])) {
            $product['product_features'] = fn_get_product_features_list($product, $params['features_display_on']);
        }

        if ($params['get_extra'] && !empty($product['is_edp']) && $product['is_edp'] == 'Y') {
            $product['agreement'] = array(fn_get_edp_agreements($product['product_id']));
        }

        $product['qty_content'] = fn_get_product_qty_content($product, $allow_negative_amount, $inventory_tracking);

        if ($params['detailed_params']) {
            $product['detailed_params'] = empty($product['detailed_params']) ? $params : array_merge($product['detailed_params'], $params);
        }

        /**
         * Add additional data to product
         *
         * @param array $product Product data
         * @param mixed $auth Array of user authentication data
         * @param array $params Parameteres for gathering data
         */
        fn_set_hook('gather_additional_product_data_post', $product, $auth, $params);
        $_product = $product;
    }// \foreach $products

    /**
     * Add additional data to products after gathering additional products data
     *
     * @param array  $product_ids Array of product identifiers
     * @param array  $params      Parameteres for gathering data
     * @param array  $products    Array of products
     * @param array  $auth        Array of user authentication data
     * @param string $lang_code   Two-letter language code
     */
    fn_set_hook('gather_additional_products_data_post', $product_ids, $params, $products, $auth, $lang_code);

    if ($params['get_for_one_product'] == true) {
        $products = array_shift($products);
    }
}

/**
 * Forms a drop-down list of possible product quantity values with the given quantity step
 *
 * @param array  $product               Product data
 * @param string $allow_negative_amount Flag: allow or disallow negative product quantity(Y - allow, N - disallow)
 * @param string $inventory_tracking    Flag: track product qiantity or not (Y - track, N - do not track)
 *
 * @return array qty_content List of available quantity values with the given step
 */
function fn_get_product_qty_content($product, $allow_negative_amount, $inventory_tracking)
{
    if (empty($product['qty_step'])) {
        return array();
    }

    $qty_content = array();
    $default_list_qty_count = 100;

    $max_allowed_qty_steps = 50;

    if (empty($product['min_qty'])) {
        $min_qty = $product['qty_step'];
    } else {
        $min_qty = fn_ceil_to_step($product['min_qty'], $product['qty_step']);
    }

    if (!empty($product['list_qty_count'])) {
        $max_list_qty = $product['list_qty_count'] * $product['qty_step'] + $min_qty - $product['qty_step'];
    } else {
        $max_list_qty = $default_list_qty_count * $product['qty_step'] + $min_qty - $product['qty_step'];
    }

    if (
        $product['tracking'] !== ProductTracking::DO_NOT_TRACK
        && $allow_negative_amount !== YesNo::YES
        && $inventory_tracking !== YesNo::NO
        && !(isset($product['out_of_stock_actions']) && ($product['out_of_stock_actions'] === OutOfStockActions::BUY_IN_ADVANCE) && $product['amount'] <= 0)
    ) {
        if (isset($product['in_stock'])) {
            $max_qty = fn_floor_to_step($product['in_stock'], $product['qty_step']);

        } elseif (isset($product['inventory_amount'])) {
            $max_qty = fn_floor_to_step($product['inventory_amount'], $product['qty_step']);

        } elseif ($product['amount'] < $product['qty_step']) {
            $max_qty = $product['qty_step'];

        } else {
            $max_qty = fn_floor_to_step($product['amount'], $product['qty_step']);
        }

        if (!empty($product['list_qty_count'])) {
            $max_qty = min($max_qty, $max_list_qty);
        }
    } else {
        $max_qty = $max_list_qty;
    }

    if (!empty($product['max_qty'])) {
        $max_qty = min($max_qty, fn_floor_to_step($product['max_qty'], $product['qty_step']));
    }

    $total_steps_count = 1 + (($max_qty - $min_qty) / $product['qty_step']);

    if ($total_steps_count > $max_allowed_qty_steps) {
        return array();
    }

    for ($qty = $min_qty; $qty <= $max_qty; $qty += $product['qty_step']) {
        $qty_content[] = $qty;
    }

    return $qty_content;
}

/**
 * Gets additional data for a single product
 *
 * @param array|false                    $product       Product data
 * @param bool                           $get_icon      Flag that define if product icon should be gathered
 * @param bool                           $get_detailed  Flag determines if detailed image should be gathered
 * @param bool                           $get_options   Flag that define if product options should be gathered
 * @param bool                           $get_discounts Flag that define if product discounts should be gathered
 * @param bool                           $get_features  Flag that define if product features should be gathered
 * @param array<string, string|int|bool> $params        Array of flags which determines which data should be gathered
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_gather_additional_product_data(&$product, $get_icon = false, $get_detailed = false, $get_options = true, $get_discounts = true, $get_features = false, array $params = [])
{
    // Get specific settings
    $params = array_merge($params, [
        'get_icon'      => $get_icon,
        'get_detailed'  => $get_detailed,
        'get_options'   => $get_options,
        'get_discounts' => $get_discounts,
        'get_features'  => $get_features,
    ]);

    /**
     * Change parameters for gathering additional data for a product
     *
     * @param array $product Product data
     * @param array $params  parameters for gathering data
     */
    fn_set_hook('gather_additional_product_data_params', $product, $params);

    fn_gather_additional_products_data($product, $params);
}

/**
 * Removes product by identifier
 *
 * @param int $product_id Product identifier
 * @return boolean Flag that defines if product was deleted
 */
function fn_delete_product($product_id)
{
    $status = true;
    /**
     * Check product delete (run before product is deleted)
     *
     * @param int     $product_id Product identifier
     * @param boolean $status     Flag determines if product can be deleted, if false product is not deleted
     */
    fn_set_hook('delete_product_pre', $product_id, $status);

    $product_deleted = false;

    if (!empty($product_id)) {

        if (!fn_check_company_id('products', 'product_id', $product_id)) {
            fn_set_notification('W', __('warning'), __('access_denied'));

            return false;
        }

        if ($status == false) {
            return false;
        }

        Block::instance()->removeDynamicObjectData('products', $product_id);

        // Log product deletion
        fn_log_event('products', 'delete', array(
            'product_id' => $product_id,
        ));

        // Delete product files
        fn_delete_product_files(0, $product_id);

        // Delete product folders
        fn_delete_product_file_folders(0, $product_id);

        $category_ids = db_get_fields('SELECT category_id FROM ?:products_categories WHERE product_id = ?i', $product_id);
        db_query('DELETE FROM ?:products_categories WHERE product_id = ?i', $product_id);
        fn_update_product_count($category_ids);

        $res = db_query('DELETE FROM ?:products WHERE product_id = ?i', $product_id);
        db_query('DELETE FROM ?:product_descriptions WHERE product_id = ?i', $product_id);
        db_query('DELETE FROM ?:product_prices WHERE product_id = ?i', $product_id);
        db_query('DELETE FROM ?:product_features_values WHERE product_id = ?i', $product_id);
        db_query('DELETE FROM ?:product_options_exceptions WHERE product_id = ?i', $product_id);
        db_query('DELETE FROM ?:product_popularity WHERE product_id = ?i', $product_id);

        fn_delete_image_pairs($product_id, 'product');

        // Delete product options and inventory records for this product
        fn_poptions_delete_product($product_id);

        // Executing delete_product functions from active addons

        $product_deleted = $res;
    }

    /**
     * Process product delete (run after product is deleted)
     *
     * @param int  $product_id      Product identifier
     * @param bool $product_deleted True if product was deleted successfully, false otherwise
     */
    fn_set_hook('delete_product_post', $product_id, $product_deleted);

    return $product_deleted;
}

/**
 * Check if product exists in database.
 *
 * @param int $product_id
 * @return bool
 */
function fn_product_exists($product_id)
{
    $result = true;
    fn_set_hook('product_exists', $product_id, $result);

    $res = db_get_field('SELECT COUNT(*) FROM ?:products WHERE product_id = ?i', $product_id);

    return $result && $res;
}

/**
 * Global products update
 *
 * @param array $update_data List of updated fields and product_ids
 * @return boolean Always true
 */
function fn_global_update_products($update_data)
{
    $table = $field = $value = $type = array();
    $msg = '';
    $auth = & Tygh::$app['session']['auth'];

    /**
     * Global update products data (running before fn_global_update_products() function)
     *
     * @param array  $update_data List of updated fields and product_ids
     * @param array  $table       List of table names to be updated
     * @param array  $field       List of SQL field names to be updated
     * @param array  $value       List of new fields values
     * @param array  $type        List of field types absolute or persentage
     * @param string $msg         Message containing the information about the changes made
     */
    fn_set_hook('global_update_products_pre', $update_data, $table, $field, $value, $type, $msg);

    $all_product_notify = false;
    $currencies = Registry::get('currencies');

    if (!empty($update_data['product_ids'])) {
        $update_data['product_ids'] = explode(',', $update_data['product_ids']);
        if (fn_allowed_for('MULTIVENDOR') && !fn_company_products_check($update_data['product_ids'], true)) {
            return false;
        }
    } elseif (fn_allowed_for('MULTIVENDOR')) {
        $all_product_notify = true;
        $update_data['product_ids'] = db_get_fields("SELECT product_id FROM ?:products WHERE 1 ?p", fn_get_company_condition('?:products.company_id'));
    }

    // Update prices
    if (!empty($update_data['price'])) {
        $table[] = '?:product_prices';
        $field[] = 'price';
        $value[] = $update_data['price'];
        $type[] = $update_data['price_type'];

        $msg .= ($update_data['price'] > 0 ? __('price_increased') : __('price_decreased')) . ' ' . abs((float) $update_data['price']) . ($update_data['price_type'] === 'A' ? $currencies[CART_PRIMARY_CURRENCY]['symbol'] : '%') . '.<br />';
    }

    // Update list prices
    if (!empty($update_data['list_price'])) {
        $table[] = '?:products';
        $field[] = 'list_price';
        $value[] = $update_data['list_price'];
        $type[] = $update_data['list_price_type'];

        $msg .= ($update_data['list_price'] > 0 ? __('list_price_increased') : __('list_price_decreased')) . ' ' . abs((float) $update_data['list_price']) . ($update_data['list_price_type'] === 'A' ? $currencies[CART_PRIMARY_CURRENCY]['symbol'] : '%') . '.<br />';
    }

    // Update amount
    if (!empty($update_data['amount'])) {
        $table[] = '?:products';
        $field[] = 'amount';
        $value[] = $update_data['amount'];
        $type[] = 'A';

        $msg .= ($update_data['amount'] > 0 ? __('amount_increased') : __('amount_decreased')) . ' ' . abs((float) $update_data['amount']) . '.<br />';
    }

    /**
     * Global update products data (running inside fn_global_update_products() function before fields update)
     *
     * @param array  $table       List of table names to be updated
     * @param array  $field       List of SQL field names to be updated
     * @param array  $value       List of new fields values
     * @param array  $type        List of field types absolute or persentage
     * @param string $msg         Message containing the information about the changes made
     * @param array  $update_data List of updated fields and product_ids
     */
    fn_set_hook('global_update_products', $table, $field, $value, $type, $msg, $update_data);

    $where = !empty($update_data['product_ids']) ? db_quote(" AND product_id IN (?n)", $update_data['product_ids']) : '';

    foreach ($table as $k => $v) {
        $_value = db_quote("?d", $value[$k]);
        $sql_expression = $type[$k] == 'A' ? ($field[$k] . ' + ' . $_value) : ($field[$k] . ' * (1 + ' . $_value . '/ 100)');

        if (($type[$k] == 'A') && !empty($update_data['product_ids']) && ($_value > 0)) {
            foreach ($update_data['product_ids'] as $product_id) {
                $send_notification = false;
                $product = fn_get_product_data($product_id, $auth, DESCR_SL, '', true, true, true, true);

                $old_amount = (int) $product['amount'];
                $new_amount = (isset($update_data['amount']))
                    ? ((int) $update_data['amount']) + $old_amount
                    : $old_amount;
                if (
                    $product['tracking'] !== ProductTracking::DO_NOT_TRACK
                    && $old_amount <= 0
                    && $new_amount > 0
                    && $product['status'] === ObjectStatuses::ACTIVE
                ) {
                    $send_notification = true;
                }

                if ($send_notification) {
                    fn_send_product_notifications($product_id);
                }
            }
        }

        if (fn_allowed_for('ULTIMATE') && $field[$k] == 'price') {
            $company_condition = "";
            if (Registry::get('runtime.company_id')) {
                $company_condition .= db_quote(" AND company_id = ?i", Registry::get('runtime.company_id'));
            }

            db_query("UPDATE ?p SET ?p = IF(?p < 0, 0, ?p) WHERE product_id IN (SELECT product_id FROM ?:products WHERE 1 ?p ?p)", $v, $field[$k], $sql_expression, $sql_expression, $where, $company_condition);

            $sql_expression = $type[$k] == 'A' ? '`price` + ?d' : '`price` * (1 + ?d / 100)';
            $sql_expression = db_quote($sql_expression, $update_data['price']);

            db_query("UPDATE ?:ult_product_prices SET `price` = IF(?p < 0, 0, ?p) WHERE 1 ?p ?p", $sql_expression, $sql_expression, $where, $company_condition);
        } elseif ($field[$k] === 'amount') {
            db_query('UPDATE ?p SET ?p = ?p WHERE 1 ?p', $v, $field[$k], $sql_expression, $where);
        } else {
            db_query("UPDATE ?p SET ?p = IF(?p < 0, 0, ?p) WHERE 1 ?p", $v, $field[$k], $sql_expression, $sql_expression, $where);
        }
    }

    /**
     * Global update products data (running after fn_global_update_products() function)
     *
     * @param string $msg         Message containing the information about the changes made
     * @param array  $update_data List of updated fields and product_ids
     */
    fn_set_hook('global_update_products_post', $msg, $update_data);

    if (empty($update_data['product_ids']) || $all_product_notify) {
        fn_set_notification('N', __('notice'), __('all_products_have_been_updated') . '<br />' . $msg);
    } else {
        fn_set_notification('N', __('notice'), __('text_products_updated'));
    }

    return true;
}

/**
 * Adds or updates product
 *
 * @param array $product_data Product data
 * @param int $product_id Product identifier
 * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
 * @return mixed ID of created/updated product or false in case of error
 */
function fn_update_product($product_data, $product_id = 0, $lang_code = CART_LANGUAGE)
{
    $can_update = true;

    /**
     * Update product data (running before fn_update_product() function)
     *
     * @param array   $product_data Product data
     * @param int     $product_id   Product identifier
     * @param string  $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param boolean $can_update   Flag, allows addon to forbid to create/update product
     */
    fn_set_hook('update_product_pre', $product_data, $product_id, $lang_code, $can_update);

    if ($can_update === false) {
        return false;
    }

    SecurityHelper::sanitizeObjectData('product', $product_data);

    $product_info = db_get_row('SELECT company_id, shipping_params, qty_step, status, amount FROM ?:products WHERE product_id = ?i', $product_id);
    $product_info = fn_normalize_product_overridable_fields($product_info);

    if (fn_allowed_for('ULTIMATE')) {
        // check that product owner was not changed by store administrator
        if (Registry::get('runtime.company_id') || empty($product_data['company_id'])) {
            $product_company_id = isset($product_info['company_id']) ? $product_info['company_id'] : null;
            if (!empty($product_company_id)) {
                $product_data['company_id'] = $product_company_id;
            } else {
                if (Registry::get('runtime.company_id')) {
                    $product_company_id = $product_data['company_id'] = Registry::get('runtime.company_id');
                } else {
                    $product_company_id = $product_data['company_id'] = fn_get_default_company_id();
                }
            }
        } else {
            $product_company_id = $product_data['company_id'];
        }

        if (!empty($product_data['category_ids']) && !fn_check_owner_categories($product_company_id, $product_data['category_ids']) && empty($product_data['add_new_category'])) {
            fn_set_notification('E', __('error'), __('product_must_have_owner_category'));

            return false;
        }

        if (fn_ult_is_shared_product($product_id) === YesNo::YES) {
            $_company_id = Registry::get('runtime.company_id') ?: $product_company_id;
            $_product_id = fn_ult_update_shared_product($product_data, $product_id, $_company_id, $lang_code);
        }
    }

    if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id') && !empty($product_company_id) && Registry::get('runtime.company_id') != $product_company_id && !empty($_product_id)) {
        $product_id = $_product_id;
        $create = false;
    } else {
        $product_data['updated_timestamp'] = time();

        $product_data = fn_prepare_product_overridable_fields($product_data, isset($product_company_id) ? $product_company_id : null);
        $_data = $product_data;

        $_product_time = (isset($product_data['timestamp'])) ? fn_parse_date($product_data['timestamp']) : 0;
        if (empty($product_id) &&
            (empty($_product_time) || $_product_time == mktime(0, 0, 0, date("m"), date("d"), date("Y")))) { //For new products without timestamp or today date we use time()
            $_data['timestamp'] = time();
        } elseif (!empty($_product_time) && $_product_time != fn_get_product_timestamp($product_id, true)) { //If we change date of existing product than update it
            $_data['timestamp'] = $_product_time;
        } else {
            unset($_data['timestamp']);
        }

        if (empty($product_id) && Registry::get('runtime.company_id')) {
            $_data['company_id'] = Registry::get('runtime.company_id');
        }

        if (!empty($product_data['avail_since'])) {
            $_data['avail_since'] = fn_parse_date($product_data['avail_since']);
        }

        if (isset($product_data['tax_ids'])) {
            /** @var array $product_data['tax_ids'] */
            $_data['tax_ids'] = empty($product_data['tax_ids']) ? '' : fn_create_set($product_data['tax_ids']);
        }

        if (isset($product_data['localization'])) {
            $_data['localization'] = empty($product_data['localization']) ? '' : fn_implode_localizations($_data['localization']);
        }

        if (isset($product_data['usergroup_ids'])) {
            /** @var array $_data['usergroup_ids'] */
            $_data['usergroup_ids'] = empty($product_data['usergroup_ids']) ? '0' : implode(',', $_data['usergroup_ids']);
        }

        if (!empty($product_data['list_qty_count']) && $product_data['list_qty_count'] < 0) {
            $_data['list_qty_count'] = 0;
        }

        if (!empty($product_data['qty_step']) && $product_data['qty_step'] < 0) {
            $_data['qty_step'] = 0;
        }

        if (isset($_data['qty_step'])) {
            $qty_step = (int) abs((float) $_data['qty_step']);
        } elseif (isset($product_info['qty_step'])) {
            $qty_step = (int) abs((float) $product_info['qty_step']);
        } else {
            $qty_step = 0;
        }

        if (!empty($product_data['min_qty'])) {
            /** @var int|string $product_data['min_qty'] */
            $_data['min_qty'] = fn_ceil_to_step(abs((int) $product_data['min_qty']), $qty_step);
        }

        if (!empty($product_data['max_qty'])) {
            /** @var int|string $product_data['max_qty'] */
            $_data['max_qty'] = fn_ceil_to_step(abs((int) $product_data['max_qty']), $qty_step);
        }

        if (
            !empty($_data['min_qty'])
            && !empty($_data['max_qty'])
            && $_data['min_qty'] > $_data['max_qty']
        ) {
            $_data['min_qty'] = 0;
        }

        $shipping_params = array();
        if (!empty($product_info['shipping_params'])) {
            $shipping_params = unserialize($product_info['shipping_params']);
        }

        if (isset($product_data['free_shipping'])) {
            $_data['free_shipping'] = YesNo::toId($product_data['free_shipping']);
        }

        // Save the product shipping params
        $_shipping_params = array(
            'min_items_in_box' => isset($_data['min_items_in_box']) ? intval($_data['min_items_in_box']) : (!empty($shipping_params['min_items_in_box']) ? $shipping_params['min_items_in_box'] : 0),
            'max_items_in_box' => isset($_data['max_items_in_box']) ? intval($_data['max_items_in_box']) : (!empty($shipping_params['max_items_in_box']) ? $shipping_params['max_items_in_box'] : 0),
            'box_length' => isset($_data['box_length']) ? intval($_data['box_length']) : (!empty($shipping_params['box_length']) ? $shipping_params['box_length'] : 0),
            'box_width' => isset($_data['box_width']) ? intval($_data['box_width']) : (!empty($shipping_params['box_width']) ? $shipping_params['box_width'] : 0),
            'box_height' => isset($_data['box_height']) ? intval($_data['box_height']) : (!empty($shipping_params['box_height']) ? $shipping_params['box_height'] : 0),
        );

        $_data['shipping_params'] = serialize($_shipping_params);
        unset($_shipping_params);

        // whether full categories tree rebuild must be launched for a product
        $rebuild = false;

        // add new product
        if (empty($product_id)) {
            $create = true;
            $product_data['create'] = true;
            // product title can't be empty and not set product_id
            if (empty($product_data['product']) || !empty($product_data['product_id'])) {
                fn_set_notification('E', __('error'), __('need_product_name'));

                return false;
            }

            $product_id = db_query("INSERT INTO ?:products ?e", $_data);

            if (empty($product_id)) {
                $product_id = false;
            }

            //
            // Adding same product descriptions for all cart languages
            //
            $_data = $product_data;
            $_data['product_id'] =  $product_id;
            /** @var string $_data['product'] */
            $_data['product'] = trim($_data['product'], ' -');

            foreach (Languages::getAll() as $_data['lang_code'] => $_v) {
                db_query("INSERT INTO ?:product_descriptions ?e", $_data);
            }

            // update product
        } else {
            $create = false;
            if (isset($product_data['product']) && empty($product_data['product'])) {
                unset($product_data['product']);
            }

            $old_amount = (int) $product_info['amount'];
            $new_amount = (isset($_data['amount'])) ? (int) $_data['amount'] : $old_amount;

            $old_status = $product_info['status'];
            $new_status = (isset($_data['status'])) ? $_data['status'] : $old_status;
            $to_status_by_tools = $product_data['to_status_by_tools'] ?? null;

            $is_need_check_amount = $old_amount <= 0 && $new_amount > 0
                && $new_status === ObjectStatuses::ACTIVE;
            $is_need_check_status = (
                    (
                        $old_status !== $new_status
                        && $new_status === ObjectStatuses::ACTIVE
                    )
                    || $to_status_by_tools === ObjectStatuses::ACTIVE
                )
                && $new_amount > 0;

            $is_need_check_subscribes = $is_need_check_amount
                || $is_need_check_status;

            if ($is_need_check_subscribes) {
                fn_send_product_notifications($product_id);
            }

            if (fn_allowed_for('MULTIVENDOR') && isset($_data['company_id'])) {
                $old_company_id = isset($product_info['company_id']) ? (int) $product_info['company_id'] : null;
                $rebuild = $old_company_id !== (int) $_data['company_id'];
            }

            if ($product_info) {
                /**
                 * Executes before product updating, allows changing data
                 *
                 * @param array   $_data        Product data
                 * @param array   $product_info Old product data
                 * @param int     $product_id   Product identifier
                 * @param string  $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
                 */
                fn_set_hook('update_product_before_update', $_data, $product_info, $product_id, $lang_code);

                db_query("UPDATE ?:products SET ?u WHERE product_id = ?i", $_data, $product_id);

                $_data = $product_data;
                if (!empty($_data['product'])) {
                    /** @var string $_data['product'] */
                    $_data['product'] = trim($_data['product'], " -");
                }

                db_query(
                    'UPDATE ?:product_descriptions SET ?u WHERE product_id = ?i AND lang_code = ?s',
                    $_data, $product_id, $lang_code
                );
            } else {
                fn_set_notification(
                    'E', __('error'), __('object_not_found', array('[object]' => __('product'))),'','404'
                );
                $product_id = false;
            }
        }

        if ($product_id) {
            // Log product add/update
            fn_log_event('products', !empty($create) ? 'create' : 'update', array(
                'product_id' => $product_id,
            ));

            /** @var array $product_data['product_features'] */
            $product_data['product_features'] = !empty($product_data['product_features']) ? $product_data['product_features'] : [];
            /** @var array $product_data['add_new_variant'] */
            $product_data['add_new_variant'] = !empty($product_data['add_new_variant']) ? $product_data['add_new_variant'] : [];

            fn_update_product_categories($product_id, $product_data, $rebuild);

            // Update product features value
            fn_update_product_features_value(
                $product_id,
                $product_data['product_features'],
                $product_data['add_new_variant'],
                $lang_code,
                isset($product_data['product_features_params']) ? $product_data['product_features_params'] : []
            );

            // Update product prices
            $product_data = fn_update_product_prices($product_id, $product_data);

            if (isset($product_data['popularity'])) {
                if (fn_allowed_for('ULTIMATE') || (fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id') == 0)) {
                    $_data = array (
                        'product_id' => $product_id,
                        'total' => (int) $product_data['popularity']
                    );

                    db_query("INSERT INTO ?:product_popularity ?e ON DUPLICATE KEY UPDATE total = ?i", $_data, $product_data['popularity']);
                }
            }

            // Update main image
            fn_attach_image_pairs('product_main', 'product', $product_id, $lang_code);

            // Update additional images
            fn_attach_image_pairs('product_additional', 'product', $product_id, $lang_code);

            // Add new additional images
            fn_attach_image_pairs('product_add_additional', 'product', $product_id, $lang_code);

            // Remove images
            if (isset($product_data['removed_image_pair_ids'])) {
                $product_data['removed_image_pair_ids'] = array_filter($product_data['removed_image_pair_ids']);
            }
            if (!empty($product_data['removed_image_pair_ids'])) {
                fn_delete_image_pairs($product_id, 'product', '', $product_data['removed_image_pair_ids']);
            }

            /**
             * Re-attach one of the additional product images as the main one when product has no main image.
             * This case can occur when creating or updating a product programmatically via API.
             */
            $main_images = fn_get_image_pairs($product_id, 'product', ImagePairTypes::MAIN, true, true, $lang_code, true);

            if (!$main_images) {
                $additional_images = fn_get_image_pairs($product_id, 'product', ImagePairTypes::ADDITIONAL, true, true, $lang_code);
                $main_image_candidate = reset($additional_images);

                if ($main_image_candidate) {
                    $pairs_data = [
                        $main_image_candidate['pair_id'] => [
                            'detailed_alt' => '',
                            'type'         => ImagePairTypes::MAIN,
                            'object_id'    => 0,
                            'pair_id'      => $main_image_candidate['pair_id'],
                            'position'     => 0,
                            'is_new'       => YesNo::NO,
                        ],
                    ];

                    fn_update_image_pairs([], [], $pairs_data, $product_id, 'product', [], true, $lang_code);
                }
            } elseif (count($main_images) > 1) {
                array_pop($main_images);
                $pairs_data = [];
                foreach ($main_images as $main_image) {
                    $pairs_data[$main_image['pair_id']] = [
                        'detailed_alt' => '',
                        'type'         => ImagePairTypes::ADDITIONAL,
                        'object_id'    => 0,
                        'pair_id'      => $main_image['pair_id'],
                        'position'     => 0,
                        'is_new'       => YesNo::NO,
                    ];
                }

                fn_update_image_pairs([], [], $pairs_data, $product_id, 'product', [], true, $lang_code);
            }

            if (fn_allowed_for('ULTIMATE')) {
                fn_check_and_update_product_sharing($product_id);
            }
        }
    }

    /**
     * Update product data (running after fn_update_product() function)
     *
     * @param array  $product_data Product data
     * @param int    $product_id   Product integer identifier
     * @param string $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param bool   $create       Flag determines if product was created (true) or just updated (false).
     */
    fn_set_hook('update_product_post', $product_data, $product_id, $lang_code, $create);

    return (int) $product_id;
}

/**
 * Gets products list by search params
 *
 * @param array  $params         Product search params
 * @param int    $items_per_page Items per page
 * @param string $lang_code      Two-letter language code (e.g. 'en', 'ru', etc.)
 * @return array Products list and Search params
 */
function fn_get_products($params, $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    /**
     * Changes params for selecting products
     *
     * @param array  $params         Product search params
     * @param int    $items_per_page Items per page
     * @param string $lang_code      Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_products_pre', $params, $items_per_page, $lang_code);

    // Init filter
    $params = LastView::instance()->update('products', $params);

    // Set default values to input params
    $default_params = [
        'area'                     => AREA,
        'use_caching'              => true,
        'extend'                   => ['product_name', 'prices', 'categories'],
        'custom_extend'            => [],
        'pname'                    => '',
        'pshort'                   => '',
        'pfull'                    => '',
        'pkeywords'                => '',
        'feature'                  => [],
        'type'                     => 'simple',
        'page'                     => 1,
        'action'                   => '',
        'filter_variants'          => [],
        'features_hash'            => '',
        'limit'                    => 0,
        'bid'                      => 0,
        'match'                    => '',
        'tracking'                 => [],
        'get_frontend_urls'        => false,
        'items_per_page'           => $items_per_page,
        'apply_disabled_filters'   => '',
        'load_products_extra_data' => true,
        'storefront'               => null,
        'company_ids'              => '',
    ];

    if (empty($params['custom_extend'])) {
        $params['extend'] = !empty($params['extend']) ? array_merge($default_params['extend'], $params['extend']) : $default_params['extend'];
    } else {
        $params['extend'] = $params['custom_extend'];
    }

    $params = array_merge($default_params, $params);

    $params['hide_out_of_stock_products'] = YesNo::toBool(Registry::get('settings.General.inventory_tracking'))
        && !YesNo::toBool(Registry::get('settings.General.show_out_of_stock_products'))
        && SiteArea::isStorefront($params['area']);

    if ((empty($params['pname']) || $params['pname'] !== 'Y')
        && (empty($params['pshort']) || $params['pshort'] !== 'Y')
        && (empty($params['pfull']) || $params['pfull'] !== 'Y')
        && (empty($params['pkeywords']) || $params['pkeywords'] !== 'Y')
        && (isset($params['q']) && fn_string_not_empty($params['q']))
    ) {
        $params['pname'] = 'Y';
    }

    $total = !empty($params['total']) ? intval($params['total']) : 0;
    $auth = & Tygh::$app['session']['auth'];

    $fields = array(
        'product_id' => 'products.product_id',
    );

    // Define sort fields
    // @TODO move to separate function with hook or merge with fn_get_products_sorting()
    $sortings = array (
        'code' => 'products.product_code',
        'status' => 'products.status',
        'product' => 'product',
        'position' => 'products_categories.position',
        'price' => 'price',
        'list_price' => 'products.list_price',
        'weight' => 'products.weight',
        'amount' => 'products.amount',
        'timestamp' => 'products.timestamp',
        'updated_timestamp' => 'products.updated_timestamp',
        'popularity' => 'popularity.total',
        'company' => 'company_name',
        'null' => 'NULL'
    );

    if (!empty($params['get_amount'])) {
        $fields['get_amount'] = 'products.amount';
    }

    if (!empty($params['get_subscribers'])) {
        $sortings['num_subscr'] = 'num_subscr';
        $fields['num_subscr'] = 'COUNT(DISTINCT product_subscriptions.subscription_id) as num_subscr';
    }

    if (!empty($params['order_ids'])) {
        $sortings['p_qty'] = 'purchased_qty';
        $sortings['p_subtotal'] = 'purchased_subtotal';
        $fields['purchased_qty'] = 'order_details.purchased_qty';
        $fields['purchased_subtotal'] = 'order_details.purchased_subtotal';
    }

    // Fallback to default sorting field
    if (empty($params['sort_by'])) {
        $params = array_merge($params, fn_get_default_products_sorting());
    }

    // Fallback to default sorting order
    $sortings_list = fn_get_products_sorting();
    if (empty($params['sort_order'])) {
        if (!empty($sortings_list[$params['sort_by']]['default_order'])) {
            $params['sort_order'] = $sortings_list[$params['sort_by']]['default_order'];
        } else {
            $params['sort_order'] = 'asc';
        }
    }

    if (isset($params['compact']) && $params['compact'] == 'Y') {
        $union_condition = ' OR ';
    } else {
        $union_condition = ' AND ';
    }

    $join = $condition = $u_condition = $inventory_join_cond = '';
    $having = array();

    // Search string condition for SQL query
    if (isset($params['q']) && fn_string_not_empty($params['q'])) {
        $params['q'] = trim($params['q']);
        if ($params['match'] == 'any') {
            $query_pieces = fn_explode(' ', $params['q']);
            $search_type = ' OR ';
        } elseif ($params['match'] == 'all') {
            $query_pieces = fn_explode(' ', $params['q']);
            $search_type = ' AND ';
        } else {
            $query_pieces = array($params['q']);
            $search_type = '';
        }

        $search_conditions = array();
        foreach ($query_pieces as $piece) {
            if (strlen($piece) == 0) {
                continue;
            }

            $tmp = db_quote("(descr1.search_words LIKE ?l)", '%' . $piece . '%'); // check search words

            if ($params['pname'] == 'Y') {
                $tmp .= db_quote(" OR descr1.product LIKE ?l", '%' . $piece . '%');
            }
            if ($params['pshort'] == 'Y') {
                $tmp .= db_quote(" OR descr1.short_description LIKE ?l", '%' . $piece . '%');
                $tmp .= db_quote(" OR descr1.short_description LIKE ?l", '%' . htmlentities($piece, ENT_QUOTES, 'UTF-8') . '%');
            }
            if ($params['pfull'] == 'Y') {
                $tmp .= db_quote(" OR descr1.full_description LIKE ?l", '%' . $piece . '%');
                $tmp .= db_quote(" OR descr1.full_description LIKE ?l", '%' . htmlentities($piece, ENT_QUOTES, 'UTF-8') . '%');
            }
            if ($params['pkeywords'] == 'Y') {
                $tmp .= db_quote(" OR (descr1.meta_keywords LIKE ?l OR descr1.meta_description LIKE ?l)", '%' . $piece . '%', '%' . $piece . '%');
            }
            if (!empty($params['feature_variants'])) {
                $tmp .= db_quote(" OR ?:product_features_values.value LIKE ?l", '%' . $piece . '%');
                $params['extend'][] = 'feature_values';
            }

            if (isset($params['pcode_from_q']) && $params['pcode_from_q'] == 'Y') {
                $tmp .= db_quote(' OR products.product_code LIKE ?l', '%' . $piece . '%');
            }

            /**
             * Executed for each part of a search query; it allows to modify the SQL conditions of the search.
             *
             * @param array  $params        List of parameters passed to fn_get_products functions
             * @param array  $fields        List of fields for retrieving
             * @param array  $sortings      Sorting fields
             * @param string $condition     String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
             * @param string $join          String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
             * @param string $sorting       String containing the SQL-query ORDER BY clause. This variable isn't used; it remains only for backward compatibility.
             * @param string $group_by      String containing the SQL-query GROUP BY field. This variable isn't used; it remains only for backward compatibility.
             * @param string $tmp           String containing SQL-query search condition by piece
             * @param string $piece         Part of the search query
             * @param array  $having        HAVING condition
             * @param string $lang_code     Two-letter language code (e.g. 'en', 'ru', etc.)
             */
            fn_set_hook('additional_fields_in_search', $params, $fields, $sortings, $condition, $join, $sorting, $group_by, $tmp, $piece, $having, $lang_code);

            $search_conditions[] = '(' . $tmp . ')';
        }

        $_cond = implode($search_type, $search_conditions);

        if (!empty($search_conditions)) {
            $condition .= ' AND (' . $_cond . ') ';
        }

        //if perform search we also get additional fields
        if ($params['pname'] == 'Y') {
            $params['extend'][] = 'product_name';
        }

        if ($params['pshort'] == 'Y' || $params['pfull'] == 'Y' || $params['pkeywords'] == 'Y') {
            $params['extend'][] = 'description';
        }

        unset($search_conditions);
    }

    //
    // [Advanced and feature filters]
    //
    if (!empty($params['apply_limit']) && $params['apply_limit'] && !empty($params['pid'])) {
        $pids = array();

        foreach ($params['pid'] as $pid) {
            if ($pid != $params['exclude_pid']) {
                if (count($pids) == $params['limit']) {
                    break;
                } else {
                    $pids[] = $pid;
                }
            }
        }
        $params['pid'] = $pids;
    }

    if (!empty($params['pcode'])) {
        $pcode = trim($params['pcode']);
        $condition .= db_quote(' AND products.product_code LIKE ?l', '%' . $pcode . '%');
    }

    // Feature code
    if (!empty($params['feature_code'])) {
        $condition .= db_quote(" AND ?:product_features.feature_code = ?s", $params['feature_code']);
        $params['extend'][] = 'features';
        $params['extend'][] = 'feature_values';
    }

    // find with certain variant
    if (!empty($params['variant_id'])) {
        $join .= db_quote(" INNER JOIN ?:product_features_values as c_var ON c_var.product_id = products.product_id AND c_var.lang_code = ?s AND c_var.variant_id = ?i", $lang_code, $params['variant_id']);
    }

    if (!empty($params['features_hash']) || !empty($params['filter_variants'])) {

        $selected_filters = !empty($params['filter_variants']) ? $params['filter_variants'] : fn_parse_filters_hash($params['features_hash']);
        $filter_request = db_quote("SELECT ?:product_filters.*, ?:product_features.feature_type FROM ?:product_filters LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_filters.feature_id WHERE ?:product_filters.filter_id IN (?n)", array_keys($selected_filters));

        if (empty($params['apply_disabled_filters'])) {
            $filter_request .= " AND ?:product_filters.status = 'A'";
        }

        $filters = db_get_hash_array($filter_request, 'filter_id');
        list($join, $condition) = fn_generate_feature_conditions($filters, $selected_filters, $join, $condition, $lang_code);

        $params = fn_generate_filter_field_params($params, $filters, $selected_filters);
    }

    if (!empty($params['updated_in_hours'])) {
        $hours_ago = TIME - $params['updated_in_hours'] * SECONDS_IN_HOUR;
        $condition .= db_quote(' AND products.updated_timestamp >= ?i', $hours_ago);
    }

    fn_set_hook(
        'get_products_before_select',
        $params,
        $join,
        $condition,
        $u_condition,
        $inventory_join_cond,
        $sortings,
        $total,
        $items_per_page,
        $lang_code,
        $having
    );

    //
    // [/Advanced filters]
    //

    $feature_search_condition = '';
    if (!empty($params['feature_variants'])) {

        $feature_params = array(
            'plain' => true,
            'variants' => false,
            'exclude_group' => true,
            'feature_id' => array_keys($params['feature_variants'])

        );
        list($features, ) = fn_get_product_features($feature_params, PRODUCT_FEATURES_THRESHOLD);
        list($join, $condition) = fn_generate_feature_conditions($features, $params['feature_variants'], $join, $condition, $lang_code);
    }

    // Filter by category ID
    if (!empty($params['cid'])) {
        $cids = is_array($params['cid']) ? $params['cid'] : explode(',', $params['cid']);

        if (isset($params['subcats']) && $params['subcats'] == 'Y') {
            $_ids = db_get_fields(
                "SELECT a.category_id"."
                 FROM ?:categories as a"."
                 LEFT JOIN ?:categories as b"."
                 ON b.category_id IN (?n)"."
                 WHERE a.id_path LIKE CONCAT(b.id_path, '/%')",
                $cids
            );

            $cids = fn_array_merge($cids, $_ids, false);
        }

        $condition .= db_quote(" AND ?:categories.category_id IN (?n)", $cids);
    }

    // If we need to get the products by IDs and no IDs passed, don't search anything
    if (!empty($params['force_get_by_ids'])
        && empty($params['pid'])
        && empty($params['product_id'])
        && empty($params['get_conditions'])
    ) {
        return array(array(), $params, 0);
    }

    // Product ID search condition for SQL query
    if (!empty($params['pid'])) {
        $pid = $params['pid'];
        if (!is_array($pid) && strpos($pid, ',') !== false) {
            $pid = explode(',', $pid);
        }
        $u_condition .= db_quote($union_condition . ' products.product_id IN (?n)', $pid);
    }

    // Exclude products from search results
    if (!empty($params['exclude_pid'])) {
        $condition .= db_quote(' AND products.product_id NOT IN (?n)', $params['exclude_pid']);
    }

    // Search products by localization
    $condition .= fn_get_localizations_condition('products.localization', true);

    $company_condition = '';

    $storefront = $params['storefront'] instanceof Storefront
        ? $params['storefront']
        : StorefrontProvider::getStorefront();

    if (SiteArea::isStorefront($params['area'])) {
        $params['extend'][] = 'companies';
        $params['for_current_storefront'] = true;

        if (empty($params['company_status'])) {
            $params['company_status'] = VendorStatuses::ACTIVE;
        }
    } else {
        if (fn_allowed_for('MULTIVENDOR')) {
            $company_condition .= fn_get_company_condition('products.company_id');
        } else {
            $cat_company_condition = '';
            if (Registry::get('runtime.company_id')) {
                $params['extend'][] = 'categories';
                $cat_company_condition .= fn_get_company_condition('?:categories.company_id');
            } elseif (!empty($params['company_ids'])) {
                $params['extend'][] = 'categories';
                if (is_array($params['company_ids'])) {
                    $cat_company_condition .= db_quote(' AND ?:categories.company_id IN (?n)', $params['company_ids']);
                } else {
                    $cat_company_condition .= db_quote(' AND ?:categories.company_id IN (?n)', explode(',', $params['company_ids']));
                }
            }
            $company_condition .= $cat_company_condition;
        }
    }

    if (!empty($params['company_status'])) {
        $company_condition .= db_quote(' AND companies.status IN (?a)', $params['company_status']);
    }

    if (fn_allowed_for('MULTIVENDOR') && isset($params['for_current_storefront']) && $storefront->getCompanyIds()) {
        $storefront_company_ids = $storefront->getCompanyIds();
        $storefront_company_ids[] = 0;
        $company_condition .= db_quote(' AND products.company_id IN (?n)', $storefront_company_ids);
    }

    $condition .= $company_condition;

    if (!fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id') && isset($params['company_id'])) {
        $params['company_id'] = Registry::get('runtime.company_id');
    }
    if (isset($params['company_id']) && $params['company_id'] != '') {
        $condition .= db_quote(' AND products.company_id = ?i ', $params['company_id']);
    }

    if (!empty($params['company_ids']) && is_array($params['company_ids'])) {
        $condition .= db_quote(' AND products.company_id IN (?n)', $params['company_ids']);
    }

    if (!empty($params['filter_params'])) {
        $params['filter_params'] = fn_check_table_fields($params['filter_params'], 'products');

        foreach ($params['filter_params'] as $field => $f_vals) {
            $condition .= db_quote(' AND products.' . $field . ' IN (?a) ', $f_vals);
        }
    }

    if (isset($params['price_from']) && fn_is_numeric($params['price_from'])) {
        $condition .= db_quote(' AND prices.price >= ?d', fn_convert_price(trim($params['price_from'])));
        $params['extend'][] = 'prices2';
        $params['extend'][] = 'prices';
    }

    if (isset($params['price_to']) && fn_is_numeric($params['price_to'])) {
        $condition .= db_quote(' AND prices.price <= ?d', fn_convert_price(trim($params['price_to'])));
        $params['extend'][] = 'prices2';
        $params['extend'][] = 'prices';
    }

    if (isset($params['weight_from']) && fn_is_numeric($params['weight_from'])) {
        $condition .= db_quote(' AND products.weight >= ?p', fn_convert_weight(trim($params['weight_from'])));
    }

    if (isset($params['weight_to']) && fn_is_numeric($params['weight_to'])) {
        $condition .= db_quote(' AND products.weight <= ?p', fn_convert_weight(trim($params['weight_to'])));
    }

    // search specific inventory status
    if (!empty($params['tracking'])) {
        $params['tracking'] = (array) $params['tracking'];
        $tracking_conditions = [];
        $global_tracking = Registry::get('settings.General.global_tracking');
        $tracking_values = [ProductTracking::TRACK, ProductTracking::DO_NOT_TRACK];

        foreach ($params['tracking'] as $tracking_value) {
            if (!empty($global_tracking)) {
                if ($global_tracking === $tracking_value) {
                    $tracking_conditions[] = db_quote('(products.tracking IN (?a) OR products.tracking IS NULL)', $tracking_values);
                } else {
                    $tracking_conditions[] = db_quote('(products.tracking NOT IN (?a) AND products.tracking IS NOT NULL)', $tracking_values);
                }
                continue;
            }

            if (Registry::get('settings.General.default_tracking') === $tracking_value) {
                $tracking_conditions[] = db_quote('(products.tracking = ?s OR products.tracking IS NULL)', $tracking_value);
            } else {
                $tracking_conditions[] = db_quote('products.tracking = ?s', $tracking_value);
            }
        }

        if (!empty($tracking_conditions)) {
            $condition .= db_quote(' AND (?p)', implode(' OR ', $tracking_conditions));
        }
    }

    if (isset($params['amount_from']) && fn_is_numeric($params['amount_from'])) {
        $condition .= db_quote(' AND products.amount >= ?i', $params['amount_from']);
    }

    if (isset($params['amount_to']) && fn_is_numeric($params['amount_to'])) {
        $condition .= db_quote(' AND products.amount <= ?i', $params['amount_to']);
    }

    // Cut off out of stock products
    if ($params['hide_out_of_stock_products']) {
        if (Registry::get('settings.General.global_tracking') === ProductTracking::TRACK) {
            $condition .= db_quote(' AND products.amount > 0');
        } elseif (Registry::get('settings.General.default_tracking') === ProductTracking::TRACK) {
            $condition .= db_quote(' AND (products.amount > 0 OR products.tracking = ?s)', ProductTracking::DO_NOT_TRACK);
        } else {
            $condition .= db_quote(' AND (products.amount > 0 OR products.tracking = ?s OR products.tracking IS NULL)', ProductTracking::DO_NOT_TRACK);
        }
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(' AND products.status IN (?a)', $params['status']);
    }

    if (!empty($params['shipping_freight_from'])) {
        $condition .= db_quote(' AND products.shipping_freight >= ?d', $params['shipping_freight_from']);
    }

    if (!empty($params['shipping_freight_to'])) {
        $condition .= db_quote(' AND products.shipping_freight <= ?d', $params['shipping_freight_to']);
    }

    if (!empty($params['free_shipping'])) {
        $condition .= db_quote(' AND products.free_shipping = ?s', $params['free_shipping']);
    }

    if (!empty($params['downloadable'])) {
        $condition .= db_quote(' AND products.is_edp = ?s', $params['downloadable']);
    }

    if (!empty($params['period']) && $params['period'] != 'A') {
        list($params['time_from'], $params['time_to']) = fn_create_periods($params);
        $condition .= db_quote(" AND (products.timestamp >= ?i AND products.timestamp <= ?i)", $params['time_from'], $params['time_to']);
    }

    if (!empty($params['item_ids'])) {
        $condition .= db_quote(" AND products.product_id IN (?n)", explode(',', $params['item_ids']));
    }

    if (isset($params['popularity_from']) && fn_is_numeric($params['popularity_from'])) {
        $params['extend'][] = 'popularity';
        $condition .= db_quote(' AND popularity.total >= ?i', $params['popularity_from']);
    }

    if (isset($params['popularity_to']) && fn_is_numeric($params['popularity_to'])) {
        $params['extend'][] = 'popularity';
        $condition .= db_quote(' AND popularity.total <= ?i', $params['popularity_to']);
    }

    if (!empty($params['order_ids'])) {
        $order_ids = $params['order_ids'];

        if (!is_array($order_ids)) {
            $order_ids = explode(',', $order_ids);
        }

        if ($order_ids) {
            $join .= db_quote(
                ' INNER JOIN ('
                . 'SELECT'
                . ' product_id,'
                . ' SUM(?:order_details.amount) as purchased_qty,'
                . ' SUM(?:order_details.price * ?:order_details.amount) as purchased_subtotal'
                . ' FROM ?:order_details'
                . ' WHERE order_id IN (?n)'
                . ' GROUP BY product_id'
                . ') AS order_details ON order_details.product_id = products.product_id',
                $order_ids
            );
        }
    }

    $limit = '';
    $group_by = 'products.product_id';
    // Show enabled products
    $_p_statuses = array('A');

    if (empty($params['usergroup_ids']) || $auth['user_type'] === UserTypes::CUSTOMER) {
        $params['usergroup_ids'] = $auth['usergroup_ids'];
    }
    $condition .= (SiteArea::isStorefront($params['area']))
        ? ' AND (' . fn_find_array_in_set($params['usergroup_ids'], 'products.usergroup_ids', true) . ')'
        . db_quote(' AND products.status IN (?a)', $_p_statuses)
        : '';

    // -- JOINS --

    // Feature values and features
    if (in_array('feature_values', $params['extend'])) {
        $join .= db_quote(" LEFT JOIN ?:product_features_values ON ?:product_features_values.product_id = products.product_id AND ?:product_features_values.lang_code = ?s", $lang_code);
        if (in_array('features', $params['extend'])) {
            $join .= db_quote(" LEFT JOIN ?:product_features ON ?:product_features_values.feature_id = ?:product_features.feature_id");
        }
    }

    if (in_array('product_name', $params['extend'])) {
        $fields['product'] = 'descr1.product as product';
    }

    if (in_array('product_name', $params['extend']) || in_array('description', $params['extend'])) {
        $join .= db_quote(" LEFT JOIN ?:product_descriptions as descr1 ON descr1.product_id = products.product_id AND descr1.lang_code = ?s ", $lang_code);
    }

    // get prices
    $price_condition = '';
    if (in_array('prices', $params['extend'])) {
        $join .= " LEFT JOIN ?:product_prices as prices ON prices.product_id = products.product_id AND prices.lower_limit = 1";
        $price_condition = db_quote(' AND prices.usergroup_id IN (?n)', (($params['area'] == 'A') ? USERGROUP_ALL : array_merge(array(USERGROUP_ALL), $auth['usergroup_ids'])));
        $condition .= $price_condition;
    }

    // get prices for search by price
    if (in_array('prices2', $params['extend'])) {
        $price_usergroup_cond_2 = db_quote(' AND prices_2.usergroup_id IN (?n)', (($params['area'] == 'A') ? USERGROUP_ALL : array_merge(array(USERGROUP_ALL), $auth['usergroup_ids'])));
        $join .= " LEFT JOIN ?:product_prices as prices_2 ON prices.product_id = prices_2.product_id AND prices_2.lower_limit = 1 AND prices_2.price < prices.price " . $price_usergroup_cond_2;
        $condition .= ' AND prices_2.price IS NULL';
        $price_condition .= ' AND prices_2.price IS NULL';
    }

    // get companies
    $companies_join = db_quote(" LEFT JOIN ?:companies AS companies ON companies.company_id = products.company_id ");
    if (in_array('companies', $params['extend'])) {
        $fields['company_name'] = 'companies.company as company_name';
        $join .= $companies_join;
    }

    // for compatibility
    if (in_array('category_ids', $params['extend'])) {
        $params['extend'][] = 'categories';
    }

    // get categories
    $_c_statuses = array('A' , 'H');// Show enabled categories
    $skip_checking_usergroup_permissions = fn_is_preview_action($auth, $params);

    if ($skip_checking_usergroup_permissions) {
        $category_avail_cond = '';
    } else {
        $category_avail_cond = (SiteArea::isStorefront($params['area']))
            ? ' AND (' . fn_find_array_in_set($params['usergroup_ids'], '?:categories.usergroup_ids', true) . ')'
            : '';
    }

    $category_avail_cond .= SiteArea::isStorefront($params['area'])
        ? db_quote(' AND ?:categories.status IN (?a) AND ?:categories.storefront_id IN (?n)', $_c_statuses, [0, $storefront->storefront_id])
        : '';
    $categories_join = db_quote(
        ' INNER JOIN ?:products_categories as products_categories ON products_categories.product_id = products.product_id' .
        ' INNER JOIN ?:categories ON ?:categories.category_id = products_categories.category_id' .
        ' ?p' .
        ' ?p',
        $category_avail_cond,
        $feature_search_condition
    );

    if (in_array('categories', $params['extend'])) {
        $join .= $categories_join;
        $condition .= fn_get_localizations_condition('?:categories.localization', true);
    }

    // get popularity
    $popularity_join = db_quote(" LEFT JOIN ?:product_popularity as popularity ON popularity.product_id = products.product_id");
    if (in_array('popularity', $params['extend'])) {
        $fields['popularity'] = 'popularity.total as popularity';
        $join .= $popularity_join;
    }

    if (!empty($params['get_subscribers'])) {
        $join .= " LEFT JOIN ?:product_subscriptions as product_subscriptions ON product_subscriptions.product_id = products.product_id";
    }

    //  -- \JOINs --

    if (!empty($u_condition)) {
        $condition .= " $union_condition ((" . ($union_condition == ' OR ' ? '0 ' : '1 ') . $u_condition . ')' . $company_condition . $price_condition . ')';
    }

    // Load prices in main SQL-query when they are needed and sorting or filtering by price is applied
    if (
        in_array('prices', $params['extend'])
        && (
            (isset($params['sort_by']) && $params['sort_by'] == 'price')
            || in_array('prices2', $params['extend'])
        )
    ) {
        $fields['price'] = 'MIN(IF(prices.percentage_discount = 0, prices.price, prices.price - (prices.price * prices.percentage_discount)/100)) as price';
    }

    /**
     * Changes additional params for selecting products
     *
     * @param array  $params    Product search params
     * @param array  $fields    List of fields for retrieving
     * @param array  $sortings  Sorting fields
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $sorting   String containing the SQL-query ORDER BY clause
     * @param string $group_by  String containing the SQL-query GROUP BY field
     * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param array  $having    HAVING condition
     */
    fn_set_hook('get_products', $params, $fields, $sortings, $condition, $join, $sorting, $group_by, $lang_code, $having);

    // -- SORTINGS --
    if ($params['sort_by'] == 'popularity' && !in_array('popularity', $params['extend'])) {
        $join .= $popularity_join;
    }

    if ($params['sort_by'] == 'company' && !in_array('companies', $params['extend'])) {
        $join .= $companies_join;
    }

    // Fallback to any other sorting field in case of $sortings doesn't contain desired sorting field
    if (empty($sortings[$params['sort_by']])) {

        foreach (array_keys($sortings_list) as $sortings_list_sort_by) {

            if (isset($sortings[$sortings_list_sort_by])) {
                $params['sort_by'] = $sortings_list_sort_by;
                break;
            }

        }
    }

    $sorting = db_sort($params, $sortings);

    if (!empty($sorting) && $params['sort_by'] !== 'null') {
        $sorting .= ', products.product_id ASC'; // workaround for bug https://bugs.mysql.com/bug.php?id=69732
    }

    if (fn_allowed_for('ULTIMATE')) {
        if (in_array('sharing', $params['extend'])) {
            $fields['is_shared_product'] = "IF(COUNT(IF(?:categories.company_id = products.company_id, NULL, ?:categories.company_id)), 'Y', 'N') as is_shared_product";

            if (!in_array('categories', $params['extend'], true)) {
                $join .= $categories_join;
            }
        }
    }
    // -- \SORTINGS --

    // Used for View cascading
    if (!empty($params['get_query'])) {
        return "SELECT products.product_id FROM ?:products as products $join WHERE 1 $condition GROUP BY products.product_id";
    }

    // Used for Extended search
    if (!empty($params['get_conditions'])) {
        return array($fields, $join, $condition);
    }

    if (!empty($params['limit'])) {
        $limit = db_quote(" LIMIT 0, ?i", $params['limit']);
    } elseif (!empty($params['items_per_page'])) {
        $limit = db_paginate($params['page'], $params['items_per_page']);
    }

    $calc_found_rows = '';
    if (empty($total)) {
        $calc_found_rows = 'SQL_CALC_FOUND_ROWS';
    }

    if (!empty($having)) {
        $having = ' HAVING ' . implode(' AND ', $having);
    } else {
        $having = '';
    }

    $sql_query_body = "SELECT $calc_found_rows " . implode(', ', $fields)
        . " FROM ?:products as products $join WHERE 1 $condition GROUP BY $group_by $having $sorting $limit";

    $fn_load_products = function ($query, $params) use ($total) {
        $products = db_get_array($query);
        $total_found_rows = empty($params['items_per_page'])
            ? count($products)
            : (empty($total) ? db_get_found_rows() : $total);

        return array($products, $total_found_rows);
    };

    // Caching conditions
    if (
        $params['use_caching']

        // We're on category products page
        && isset($params['dispatch'])
        && $params['dispatch'] == 'categories.view'
        && $params['area'] == 'C'

        // Context user is guest
        && $auth['usergroup_ids'] == array(0, 1)

        // We filter by category
        && !empty($params['cid'])

        // No search query
        && empty($params['q'])

        // No filters
        && empty($params['pid'])
        && empty($params['exclude_pid'])
        && empty($params['features_hash'])
        && empty($params['feature_code'])
        && empty($params['multiple_variants'])
        && empty($params['custom_range'])
        && empty($params['field_range'])
        && empty($params['fields_ids'])
        && empty($params['slider_vals'])
        && empty($params['ch_filters'])
        && empty($params['tx_features'])
        && empty($params['feature_variants'])
        && empty($params['filter_params'])
        && !isset($params['price_from'])
        && !isset($params['price_to'])
        && !isset($params['weight_from'])
        && !isset($params['weight_to'])
        && empty($params['tracking'])
        && !isset($params['amount_from'])
        && !isset($params['amount_to'])
        && empty($params['status'])
        && empty($params['shipping_freight_from'])
        && empty($params['shipping_freight_to'])
        && empty($params['free_shipping'])
        && empty($params['downloadable'])
        && !isset($params['pcode'])
        && empty($params['period'])
        && empty($params['item_ids'])
        && !isset($params['popularity_from'])
        && !isset($params['popularity_to'])
        && empty($params['order_ids'])
    ) {
        $cache_prefix = __FUNCTION__;
        $cache_key = md5($sql_query_body);
        $cache_tables = array('products', 'categories', 'products_categories');
        if (fn_allowed_for('MULTIVENDOR')) {
            $cache_tables[] = 'companies';
        }

        Registry::registerCache(
            array($cache_prefix, $cache_key),
            $cache_tables,
            Registry::cacheLevel('static'),
            true
        );

        if ($cache = Registry::get($cache_key)) {
            list($products, $params['total_items']) = $cache;
        } else {
            list ($products, $params['total_items']) = $fn_load_products($sql_query_body, $params);

            if ($params['total_items'] > Registry::get('config.tweaks.products_found_rows_no_cache_limit')) {
                Registry::set($cache_key, array($products, $params['total_items']));
            }
        }
    } else {
        list ($products, $params['total_items']) = $fn_load_products($sql_query_body, $params);
    }

    if (!empty($params['get_frontend_urls'])) {
        foreach ($products as &$product) {
            $product['url'] = fn_url('products.view?product_id=' . $product['product_id'], 'C');
        }
    }

    if (!empty($params['item_ids'])) {
        $products = fn_sort_by_ids($products, explode(',', $params['item_ids']));
    }
    if (!empty($params['pid']) && !empty($params['apply_limit']) && $params['apply_limit']) {
        $products = fn_sort_by_ids($products, $params['pid']);
    }

    if ($params['load_products_extra_data']) {
        $products = fn_load_products_extra_data($products, $params, $lang_code);
    } else {
        $products = fn_array_elements_to_keys($products, 'product_id');
        $products = fn_normalize_products_overridable_fields($products);
    }

    /**
     * Changes selected products
     *
     * @param array  $products  Array of products
     * @param array  $params    Product search params
     * @param string $lang_code Language code
     */
    fn_set_hook('get_products_post', $products, $params, $lang_code);

    LastView::instance()->processResults('products', $products, $params);

    return array($products, $params);
}

/**
 * Lazily loads additional data related to products after they have been fetched from DB.
 * Used to ease main product loading SQL-query.
 *
 * @param array $products List of products
 * @param array $params Parameters passed to fn_get_products()
 * @param string $lang_code Language code passed to fn_get_products()
 *
 * @return array List of products with additional data merged into.
 */
function fn_load_products_extra_data($products, $params, $lang_code)
{
    if (empty($products)) {
        return $products;
    }

    $extra_fields = array();

    /**
     * Loads products extra data
     *
     * @param array  $products     Array of products
     * @param array  $params       Product search params
     * @param string $lang_code    Language code
     * @param array  $extra_fields Extra fields list
     */
    fn_set_hook('load_products_extra_data_pre', $products, $params, $lang_code, $extra_fields);

    $products = fn_array_elements_to_keys($products, 'product_id');
    $product_ids = array_keys($products);

    // Fields from "products" table
    $extra_fields['?:products'] = array(
        'primary_key' => 'product_id',
        'fields' => empty($params['only_short_fields'])
            ? array('*')
            : array(
                'product_id',
                'product_code',
                'product_type',
                'status',
                'company_id',
                'list_price',
                'amount',
                'weight',
                'tracking',
                'is_edp',
            )
    );

    // Load prices lazily when they are needed and no sorting or filtering by price is applied
    if (
        in_array('prices', $params['extend'])
        && $params['sort_by'] != 'price'
        && !in_array('prices2', $params['extend'])
    ) {
        $extra_fields['?:product_prices'] = array(
            'primary_key' => 'product_id',
            'fields' => array(
                'price' =>
                    'MIN(IF(' .
                    '?:product_prices.percentage_discount = 0,' .
                    '?:product_prices.price,' .
                    '?:product_prices.price - (?:product_prices.price * ?:product_prices.percentage_discount)/100' .
                    '))'
            ),
            'condition'   => db_quote(
                ' AND ?:product_prices.lower_limit = 1 AND ?:product_prices.usergroup_id IN (?n)',
                ($params['area'] == 'A')
                    ? USERGROUP_ALL
                    : array_unique(array_merge(array(USERGROUP_ALL), Tygh::$app['session']['auth']['usergroup_ids']))
            ),
            'group_by' => ' GROUP BY ?:product_prices.product_id'
        );
    }

    // Descriptions
    $extra_fields['?:product_descriptions']['primary_key'] = 'product_id';
    $extra_fields['?:product_descriptions']['condition'] = db_quote(
        " AND ?:product_descriptions.lang_code = ?s", $lang_code
    );

    if (in_array('search_words', $params['extend'])) {
        $extra_fields['?:product_descriptions']['fields'][] = 'search_words';
    }
    if (in_array('description', $params['extend'])) {
        $extra_fields['?:product_descriptions']['fields'][] = 'short_description';

        if (in_array('full_description', $params['extend'])) {
            $extra_fields['?:product_descriptions']['fields'][] = 'full_description';
        } else {
            $extra_fields['?:product_descriptions']['fields']['full_description'] =
                "IF(?:product_descriptions.short_description = '' OR ?:product_descriptions.short_description IS NULL,"
                . " ?:product_descriptions.full_description, '')";
        }
    }

    // Categories
    if (in_array('categories', $params['extend'])) {
        $categories_join = ' INNER JOIN ?:categories ON ?:categories.category_id = ?:products_categories.category_id';
        if (SiteArea::isStorefront(AREA)) {
            $storefront = StorefrontProvider::getStorefront();
            $categories_join .= db_quote(' AND ?:categories.storefront_id IN (?n)', [0, $storefront->storefront_id]);

            if (fn_allowed_for('ULTIMATE') && !empty($params['company_ids'])) {
                $categories_join .= db_quote(' AND ?:categories.company_id IN (?n)', explode(',', $params['company_ids']));
            }

            if (!fn_is_preview_action(Tygh::$app['session']['auth'], $params)) {
                $categories_join .= ' AND ('
                    . fn_find_array_in_set(Tygh::$app['session']['auth']['usergroup_ids'], '?:categories.usergroup_ids', true)
                    . ')';
            }
            $categories_join .= db_quote(' AND ?:categories.status IN (?a) ', array('A', 'H'));
        }

        $extra_fields['?:products_categories'] = array(
            'primary_key' => 'product_id',
            'fields'    => array(
                'category_ids'        => 'GROUP_CONCAT('
                    . 'IF(?:products_categories.link_type = "M",'
                    . ' CONCAT(?:products_categories.category_id, "M"),'
                    . ' ?:products_categories.category_id)'
                    . ')',
            ),
            'condition' => fn_get_localizations_condition('?:categories.localization', true),
            'join'      => $categories_join,
            'group_by' => ' GROUP BY ?:products_categories.product_id'
        );

        if (!empty($params['cid'])) {
            $category_ids = is_array($params['cid']) ? $params['cid'] : explode(',', $params['cid']);

            // Fetch position of product at given category.
            // This is only possible when only one category is given at "cid" parameter, because it's impossible to
            // determine which category to choose as "position" field source when selecting products from several categories.
            if (sizeof($category_ids) === 1) {
                $extra_fields['?:products_categories']['fields']['position'] = 'product_position_source.position';
                $extra_fields['?:products_categories']['join'] .= db_quote(
                    ' LEFT JOIN ?:products_categories AS product_position_source'
                    . ' ON ?:products_categories.product_id = product_position_source.product_id'
                    . ' AND product_position_source.category_id = ?i',
                    reset($category_ids)
                );
            }
        }
    }

    if (in_array('meta_data', $params['extend'])) {
        $extra_fields['?:product_descriptions']['fields'][] = 'meta_description';
        $extra_fields['?:product_descriptions']['fields'][] = 'meta_keywords';
    }

    if (in_array('page_title', $params['extend'])) {
        $extra_fields['?:product_descriptions']['fields'][] = 'page_title';
    }

    if (in_array('promo_text', $params['extend'])) {
        $extra_fields['?:product_descriptions']['fields'][] = 'promo_text';
    }

    /**
     * Allows you to extend configuration of extra fields that should be lazily loaded for products.
     *
     * @see fn_load_extra_data_by_item_ids()
     * @param array  $extra_fields
     * @param array  $products     List of products
     * @param array  $product_ids  List of product identifiers
     * @param array  $params       Parameters passed to fn_get_products()
     * @param string $lang_code    Language code passed to fn_get_products()
     */
    fn_set_hook('load_products_extra_data', $extra_fields, $products, $product_ids, $params, $lang_code);

    // Execute extra data loading SQL-queries and merge results into $products array
    fn_merge_extra_data_to_entity_list(
        fn_load_extra_data_by_entity_ids($extra_fields, $product_ids),
        $products
    );

    // Categories post-processing
    if (in_array('categories', $params['extend'])) {
        foreach ($products as $k => $v) {
            if (isset($v['category_ids'])) {
                list($products[$k]['category_ids'], $products[$k]['main_category']) = fn_convert_categories($v['category_ids']);
            }
        }
    }

    $products = fn_normalize_products_overridable_fields($products);

    /**
     * Allows you lazily load extra data for products after they were fetched from DB or post-process lazy-loaded
     * additional data related to products.
     *
     * @param array  $products    List of products
     * @param array  $product_ids List of product identifiers
     * @param array  $params      Parameters passed to fn_get_products()
     * @param string $lang_code   Language code passed to fn_get_products()
     */
    fn_set_hook('load_products_extra_data_post', $products, $product_ids, $params, $lang_code);

    return $products;
}

function fn_get_products_sorting()
{
    $sorting = array(
        'null' => array('description' => __('none'), 'default_order' => 'asc', 'desc' => false),
        'timestamp' => array('description' => __('date'), 'default_order' => 'desc'),
        'position' => array('description' => __('default'), 'default_order' => 'asc'),
        'product' => array('description' => __('name'), 'default_order' => 'asc'),
        'price' => array('description' => __('price'), 'default_order' => 'asc'),
        'popularity' => array('description' => __('popularity'), 'default_order' => 'desc')
    );

    /**
     * Change products sortings
     *
     * @param array   $sorting     Sortings
     * @param boolean $simple_mode Flag that defines if products sortings should be returned as simple titles list
     */
    fn_set_hook('products_sorting', $sorting, $simple_mode);

    return $sorting;
}

function fn_get_products_sorting_orders()
{
    $result = array('asc', 'desc');

    /**
     * Change products sorting orders
     *
     * @param array $result Sorting orders
     */
    fn_set_hook('get_products_sorting_orders', $result);

    return $result;
}

function fn_get_products_views($simple_mode = true, $active = false)
{
    /**
     * Change params for getting product views
     *
     * @param boolean $simple_mode Flag that defines is product views should be returned in simple mode
     * @param boolean $active      Flag that defines if only active views should be returned
     */
    fn_set_hook('get_products_views_pre', $simple_mode, $active);

    $active_views = Registry::get('settings.Appearance.default_products_view_templates');
    if (!is_array($active_views)) {
        parse_str($active_views, $active_views);
    }

    if (!array_key_exists(Registry::get('settings.Appearance.default_products_view'), $active_views)) {
        $active_views[Registry::get('settings.Appearance.default_products_view')] = 'Y';
    }

    /*if (Registry::isExist('products_views') == true && AREA != 'A') {
        $products_views = Registry::get('products_views');

        foreach ($products_views as &$view) {
            $view['title'] = __($view['title']);
        }

        if ($simple_mode) {
            $products_views = Registry::get('products_views');

            foreach ($products_views as $key => $value) {
                $products_views[$key] = $value['title'];
            }
        }

        if ($active) {
            $products_views = array_intersect_key($products_views, $active_layouts);
        }

        return $products_views;
    }*/

    $products_views = array();

    $theme = Themes::areaFactory('C');

    // Get all available product_list_templates dirs
    $dir_params = array(
        'dir' => 'templates/blocks/product_list_templates',
        'get_dirs' => false,
        'get_files' => true,
        'extension' => '.tpl'
    );
    $view_templates[$dir_params['dir']] = $theme->getDirContents($dir_params, Themes::STR_MERGE);

    foreach ((array) Registry::get('addons') as $addon_name => $data) {
        if ($data['status'] == 'A') {
            $dir_params['dir'] = "templates/addons/{$addon_name}/blocks/product_list_templates";
            $view_templates[$dir_params['dir']] = $theme->getDirContents($dir_params, Themes::STR_MERGE, Themes::PATH_ABSOLUTE, Themes::USE_BASE);
        }
    }

    // Scan received directories and fill the "views" array
    foreach ($view_templates as $dir => $templates) {
        foreach ($templates as $file_name => $file_info) {
            $template_description = fn_get_file_description($file_info[Themes::PATH_ABSOLUTE], 'template-description', true);
            $_title = fn_basename($file_name, '.tpl');
            $template_path = str_replace(
                Themes::factory($file_info['theme'])->getThemePath() . '/templates/',
                '',
                $file_info[Themes::PATH_ABSOLUTE]
            );
            $products_views[$_title] = array(
                'template' => $template_path,
                'title' => empty($template_description) ? $_title : $template_description,
                'active' => array_key_exists($_title, $active_views)
            );
        }
    }

    //Registry::set('products_views',  $products_views);

    foreach ($products_views as &$view) {
        $view['title'] = __($view['title']);
    }

    if ($simple_mode) {
        foreach ($products_views as $key => $value) {
            $products_views[$key] = $value['title'];
        }
    }

    if ($active) {
        $products_views = array_intersect_key($products_views, $active_views);
    }

    /**
     * Change product views
     *
     * @param array   $products_views Array of products views
     * @param boolean $simple_mode    Flag that defines is product views should be returned in simple mode
     * @param boolean $active         Flag that defines if only active views should be returned
     */
    fn_set_hook('get_products_views_post', $products_views, $simple_mode, $active);

    return $products_views;
}

function fn_get_products_layout($params)
{
    static $result = null;

    // Function returns incorrect value when called more than once, this is a simple workaround.
    if ($result !== null) {
        return $result;
    }

    /**
     * Change params for getting products layout
     *
     * @param array $params Params for getting products layout
     */
    fn_set_hook('get_products_layout_pre', $params);

    if (!isset(Tygh::$app['session']['products_layout'])) {
        Tygh::$app['session']['products_layout'] = Registry::get('settings.Appearance.save_selected_view') == 'Y' ? array() : '';
    }

    $active_views = fn_get_products_views(false, true);
    $default_view = Registry::get('settings.Appearance.default_products_view');

    if (!empty($params['category_id'])) {
        $_layout = db_get_row(
            "SELECT default_view, selected_views FROM ?:categories WHERE category_id = ?i",
            $params['category_id']
        );
        $category_default_view = $_layout['default_view'];
        $category_views = unserialize($_layout['selected_views']);
        if (!empty($category_views)) {
            if (!empty($category_default_view)) {
                $default_view = $category_default_view;
            }
            $active_views = $category_views;
        }
        $ext_id = $params['category_id'];
    } else {
        $ext_id = 'search';
    }

    if (!empty($params['layout'])) {
        $layout = $params['layout'];
    } elseif (Registry::get('settings.Appearance.save_selected_view') == 'Y' && !empty(Tygh::$app['session']['products_layout'][$ext_id])) {
        $layout = Tygh::$app['session']['products_layout'][$ext_id];
    } elseif (Registry::get('settings.Appearance.save_selected_view') == 'N' && !empty(Tygh::$app['session']['products_layout'])) {
        $layout = Tygh::$app['session']['products_layout'];
    }

    $selected_view = (!empty($layout) && !empty($active_views[$layout])) ? $layout : $default_view;

    /**
     * Change selected layout
     *
     * @param array $selected_view Selected layout
     * @param array $params        Params for getting products layout
     */
    fn_set_hook('get_products_layout_post', $selected_view, $params);

    if (!empty($params['layout']) && $params['layout'] == $selected_view) {
        if (Registry::get('settings.Appearance.save_selected_view') == 'Y') {
            if (!is_array(Tygh::$app['session']['products_layout'])) {
                Tygh::$app['session']['products_layout'] = array();
            }
            Tygh::$app['session']['products_layout'][$ext_id] = $selected_view;
        } else {
            Tygh::$app['session']['products_layout'] = $selected_view;
        }
    }

    $result = $selected_view;

    return $selected_view;
}

/**
 * Gets available product details views list
 *
 * @param int|string $get_default Information about object type or product identifier
 * @param int        $company_id  Company identifier
 *
 * @return array<string, string> List of available details views list
 */
function fn_get_product_details_views($get_default = 'default', $company_id = 0)
{
    $product_details_views = array();
    if (fn_allowed_for('ULTIMATE')) {
        $company_id = empty(fn_get_runtime_company_id()) ? $company_id : fn_get_runtime_company_id();
    }

    /**
     * Changes params for getting product details views or adds additional views
     *
     * @param array  $product_details_views Array for product details views templates
     * @param string $get_default           Type of default layout
     */
    fn_set_hook('get_product_details_views_pre', $product_details_views, $get_default);

    if ($get_default == 'category') {
        $parent_layout = Settings::getSettingValue('Appearance.default_product_details_view', $company_id);
        $product_details_views['default'] = __('default_product_details_view', array(
            '[default]' => __($parent_layout)
        ));

    } elseif ($get_default != 'default') {
        $category_id = fn_get_product_main_category_id((int) $get_default);
        $parent_layout = fn_get_product_details_view_by_category($category_id);

        if (empty($parent_layout) || $parent_layout == 'default') {
            $parent_layout = Settings::getSettingValue('Appearance.default_product_details_view', $company_id);
        }

        $product_details_views['default'] = __('default_product_details_view', array(
            '[default]' => __($parent_layout)
        ));
    }

    $theme = Themes::areaFactory('C');

    // Get all available product_templates dirs
    $dir_params = array(
        'dir' => 'templates/blocks/product_templates',
        'get_dirs' => false,
        'get_files' => true,
        'extension' => '.tpl'
    );
    $view_templates[$dir_params['dir']] = $theme->getDirContents($dir_params, Themes::STR_MERGE);

    foreach ((array) Registry::get('addons') as $addon_name => $data) {
        if ($data['status'] == 'A') {
            $dir_params['dir'] = "templates/addons/{$addon_name}/blocks/product_templates";
            $view_templates[$dir_params['dir']] = $theme->getDirContents($dir_params, Themes::STR_MERGE, Themes::PATH_ABSOLUTE, Themes::USE_BASE);
        }
    }

    // Scan received directories and fill the "views" array
    foreach ($view_templates as $dir => $templates) {
        foreach ($templates as $file_name => $file_info) {
            $template_description = fn_get_file_description($file_info[Themes::PATH_ABSOLUTE], 'template-description', true);
            $_title = fn_basename($file_name, '.tpl');
            $product_details_views[$_title] = empty($template_description) ? __($_title) : $template_description;
        }
    }

    /**
     * Changes product details views
     *
     * @param array  $product_details_views Product details views
     * @param string $get_default           Type of default layout
     */
    fn_set_hook('get_product_details_views_post', $product_details_views, $get_default);

    return $product_details_views;
}

/**
 * Gets product detail page layout by product category
 *
 * @param int $category_id Category ID
 *
 * @return string
 */
function fn_get_product_details_view_by_category($category_id)
{
    return db_get_field(
        'SELECT product_details_view FROM ?:categories'
        . ' WHERE category_id = ?i',
        $category_id
    );
}

/**
 * Gets product main category ID
 *
 * @param int $product_id Product ID
 *
 * @return int
 */
function fn_get_product_main_category_id($product_id)
{
    return (int) db_get_field(
        'SELECT category_id FROM ?:products_categories'
        . ' WHERE product_id = ?i AND link_type = ?s',
        $product_id,
        'M'
    );
}


function fn_get_product_details_view($product_id)
{
    /**
     * Changes params for getting product details layout
     *
     * @param int $product_id Product identifier
     */
    fn_set_hook('get_product_details_view_pre', $product_id);
    fn_set_hook('get_product_details_layout_pre', $product_id);

    $selected_view = Registry::get('settings.Appearance.default_product_details_view');
    if (!empty($product_id)) {
        $selected_view = db_get_field('SELECT details_layout FROM ?:products WHERE product_id = ?i', $product_id);
        $selected_view = fn_normalize_product_overridable_field_value('details_layout', $selected_view);

        if (empty($selected_view) || $selected_view === 'default') {
            $category_id = fn_get_product_main_category_id($product_id);
            $selected_view = fn_get_product_details_view_by_category($category_id);
        }
        if (empty($selected_view) || $selected_view === 'default') {
            $selected_view = Registry::get('settings.Appearance.default_product_details_view');
        }
    }

    $theme = Themes::areaFactory('C');

    // Search all available product_templates dirs
    if ($theme->getContentPath("templates/blocks/product_templates/{$selected_view}.tpl")) {
        $result = "blocks/product_templates/{$selected_view}.tpl";
    } else {
        foreach ((array) Registry::get('addons') as $addon_name => $data) {
            if ($data['status'] == 'A') {
                if ($theme->getContentPath(
                    "templates/addons/{$addon_name}/blocks/product_templates/{$selected_view}.tpl",
                    Themes::CONTENT_FILE, Themes::PATH_ABSOLUTE, Themes::USE_BASE
                )) {
                    $result = "addons/{$addon_name}/blocks/product_templates/{$selected_view}.tpl";
                    break;
                }
            }
        }
    }

    if (empty($result)) {
        $result = 'blocks/product_templates/default_template.tpl';
    }

    /**
     * Changes product details layout template
     *
     * @param string $result     Product layout template
     * @param int    $product_id Product identifier
     */
    fn_set_hook('get_product_details_view_post', $result, $product_id);
    fn_set_hook('get_product_details_layout_post', $result, $product_id);

    return $result;
}

/**
 * Clones product.
 *
 * @param int $product_id Product identifier
 *
 * @return array|false Return false if product was not cloned
 */
function fn_clone_product($product_id)
{
    /**
     * Adds additional actions before product cloning
     *
     * @param int $product_id Original product identifier
     */
    fn_set_hook('clone_product_pre', $product_id);

    // Clone main data
    $data = db_get_row("SELECT * FROM ?:products WHERE product_id = ?i", $product_id);
    $is_cloning_allowed = true;

    /**
     * Executed after the data of the cloned product is received.
     * Allows to modify the data before cloning or to forbid cloning.
     *
     * @param int   $product_id             Product identifier
     * @param array $data                   Product data
     * @param bool  $is_cloning_allowed     If 'false', the product can't be cloned
     */
    fn_set_hook('clone_product_data', $product_id, $data, $is_cloning_allowed);

    if (!$is_cloning_allowed || !$data) {
        return false;
    }

    unset($data['product_id']);
    $data['status'] = 'D';
    $data['timestamp'] = $data['updated_timestamp'] = time();
    $pid = db_query("INSERT INTO ?:products ?e", $data);

    // Clone descriptions
    $data = db_get_array("SELECT * FROM ?:product_descriptions WHERE product_id = ?i", $product_id);
    foreach ($data as $v) {
        $v['product_id'] = $pid;
        if ($v['lang_code'] == CART_LANGUAGE) {
            $orig_name = $v['product'];
            $new_name = $v['product'].' [CLONE]';
        }

        $v['product'] .= ' [CLONE]';
        db_query("INSERT INTO ?:product_descriptions ?e", $v);
    }

    // Clone prices
    $data = db_get_array("SELECT * FROM ?:product_prices WHERE product_id = ?i", $product_id);
    foreach ($data as $v) {
        $v['product_id'] = $pid;
        unset($v['price_id']);
        db_query("INSERT INTO ?:product_prices ?e", $v);
    }

    // Clone categories links
    $data = db_get_array("SELECT * FROM ?:products_categories WHERE product_id = ?i", $product_id);
    $_cids = array();
    foreach ($data as $v) {
        $v['product_id'] = $pid;
        db_query("INSERT INTO ?:products_categories ?e", $v);
        $_cids[] = $v['category_id'];
    }
    fn_update_product_count($_cids);

    // Clone product options
    fn_clone_product_options($product_id, $pid);

    // Clone global linked options
    $gl_options = db_get_fields("SELECT option_id FROM ?:product_global_option_links WHERE product_id = ?i", $product_id);
    if (!empty($gl_options)) {
        foreach ($gl_options as $v) {
            db_query("INSERT INTO ?:product_global_option_links (option_id, product_id) VALUES (?i, ?i)", $v, $pid);
        }
    }

    // Clone product features
    $data = db_get_array("SELECT * FROM ?:product_features_values WHERE product_id = ?i", $product_id);
    foreach ($data as $v) {
        $v['product_id'] = $pid;
        db_query("INSERT INTO ?:product_features_values ?e", $v);
    }

    // Clone blocks
    Block::instance()->cloneDynamicObjectData('products', $product_id, $pid);

    // Clone tabs info
    ProductTabs::instance()->cloneStatuses($pid, $product_id);

    // Clone addons
    fn_set_hook('clone_product', $product_id, $pid);

    // Clone images
    fn_clone_image_pairs($pid, $product_id, 'product');

    // Clone product files
    fn_clone_product_files($product_id, $pid);

    /**
     * Adds additional actions after product cloning
     *
     * @param int    $product_id Original product identifier
     * @param int    $pid        Cloned product identifier
     * @param string $orig_name  Original product name
     * @param string $new_name   Cloned product name
     */
    fn_set_hook('clone_product_post', $product_id, $pid, $orig_name, $new_name);

    return array('product_id' => $pid, 'orig_name' => $orig_name, 'product' => $new_name);
}

/**
 * Updates product prices.
 *
 * @param int   $product_id   Product identifier.
 * @param array $product_data Array of product data.
 * @param int   $company_id   Company identifier.
 *
 * @return array Modified $product_data array.
 */
function fn_update_product_prices($product_id, $product_data, $company_id = 0)
{
    $_product_data = $product_data;
    $skip_price_delete = false;
    // Update product prices
    if (isset($_product_data['price'])) {
        $_price = array (
            'price' => abs((float) $_product_data['price']),
            'lower_limit' => 1,
        );

        if (!isset($_product_data['prices'])) {
            $_product_data['prices'][0] = $_price;
            $skip_price_delete = true;

        } else {
            unset($_product_data['prices'][0]);
            array_unshift($_product_data['prices'], $_price);
        }
    }

    if (!empty($_product_data['prices'])) {
        if (fn_allowed_for('ULTIMATE') && $company_id) {
            $table_name = '?:ult_product_prices';
            $condition = db_quote(' AND company_id = ?i', $company_id);
        } else {
            $table_name = '?:product_prices';
            $condition = '';
        }

        /**
         * Allows to influence the process of updating the prices of a product.
         *
         * @param int    $product_id        Product identifier.
         * @param array  $_product_data     Array of product data.
         * @param int    $company_id        Company identifier.
         * @param bool   $skip_price_delete Whether to delete the old prices of a product.
         * @param bool   $table_name        Database table name where the price data is stored.
         * @param string $condition         SQL conditions for deleting the old prices of a product.
         */
        fn_set_hook('update_product_prices', $product_id, $_product_data, $company_id, $skip_price_delete, $table_name, $condition);

        if (!$skip_price_delete) {
            db_query("DELETE FROM $table_name WHERE product_id = ?i $condition", $product_id);
        }

        foreach ($_product_data['prices'] as $v) {
            $v['type'] = !empty($v['type']) ? $v['type'] : 'A';
            $v['usergroup_id'] = !empty($v['usergroup_id']) ? $v['usergroup_id'] : 0;
            if ($v['lower_limit'] == 1 && $v['type'] == 'P' && $v['usergroup_id'] == 0) {
                fn_set_notification('W', __('warning'), __('cant_save_percentage_price'));
                continue;
            }
            if (!empty($v['lower_limit'])) {
                $v['product_id'] = $product_id;
                if (!empty($company_id)) {
                    $v['company_id'] = $company_id;
                }
                if ($v['type'] == 'P') {
                    $v['percentage_discount'] = ($v['price'] > 100) ? 100 : $v['price'];
                    $v['price'] = $_product_data['price'];
                }
                unset($v['type']);

                if (count($_product_data['prices']) == 1 && $skip_price_delete && empty($_product_data['create'])) {
                    $data = array(
                        'price' => $v['price']
                    );

                    db_query("UPDATE $table_name SET ?u WHERE product_id = ?i AND ((lower_limit = ?i AND usergroup_id = ?i) OR percentage_discount > ?i) ?p", $data, $v['product_id'], 1, 0, 0, $condition);
                } else {
                    db_query("REPLACE INTO $table_name ?e", $v);
                }
            }
        }
    }

    return $_product_data;
}

/**
 * Gets product prices.
 *
 * @param int   $product_id   Product identifier
 * @param array $product_data Array of product data. Result data will be saved in this variable.
 * @param array $auth         Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @param int   $company_id   Company identifier.
 * @param bool  $get_all      Gets all prices if true
 */
function fn_get_product_prices($product_id, &$product_data, $auth, $company_id = 0, $get_all = false)
{
    if (fn_allowed_for('ULTIMATE') && $company_id) {
        $table_name = '?:ult_product_prices';
        $condition = db_quote(' AND prices.company_id = ?i', $company_id);
    } else {
        $table_name = '?:product_prices';
        $condition = '';
    }

    if ($get_all || AREA !== 'C') {
        $product_data['prices'] = db_get_array("SELECT prices.product_id, prices.lower_limit, usergroup_id, prices.percentage_discount, IF(prices.percentage_discount = 0, prices.price, prices.price - (prices.price * prices.percentage_discount)/100) as price FROM $table_name prices WHERE product_id = ?i $condition ORDER BY usergroup_id, lower_limit", $product_id);
    } else {
        $_prices = db_get_hash_multi_array("SELECT prices.product_id, prices.lower_limit, usergroup_id, prices.percentage_discount, IF(prices.percentage_discount = 0, prices.price, prices.price - (prices.price * prices.percentage_discount)/100) as price FROM $table_name prices WHERE prices.product_id = ?i $condition AND lower_limit > 1 AND prices.usergroup_id IN (?n) ORDER BY lower_limit", array('usergroup_id'), $product_id, array_merge(array(USERGROUP_ALL), $auth['usergroup_ids']));

        if (empty($auth['usergroup_ids'])) {
            return;
        }

        foreach ($auth['usergroup_ids'] as $usergroup_id) {
            if (empty($_prices[$usergroup_id])) {
                continue;
            }

            if (empty($product_data['prices'])) {
                $product_data['prices'] = $_prices[$usergroup_id];
            } else {
                foreach ($_prices[$usergroup_id] as $comp_data) {
                    $add_elm = true;
                    foreach ($product_data['prices'] as $price_id => $price_data) {
                        if ($price_data['lower_limit'] != $comp_data['lower_limit']) {
                            continue;
                        }

                        $add_elm = false;
                        if ($price_data['price'] > $comp_data['price']) {
                            $product_data['prices'][$price_id] = $comp_data;
                        }
                        break;
                    }
                    if ($add_elm) {
                        $product_data['prices'][] = $comp_data;
                    }
                }
            }
        }

        if (!empty($product_data['prices'])) {
            $tmp = [];
            foreach ($product_data['prices'] as $price_id => $price_data) {
                $tmp[$price_id] = $price_data['lower_limit'];
            }
            array_multisort($tmp, SORT_ASC, $product_data['prices']);
        }

        // else, get prices for not members
        if (empty($product_data['prices']) && !empty($_prices[0]) && sizeof($_prices[0]) > 0) {
            $product_data['prices'] = $_prices[0];
        }
    }
}

/**
 * Gets default products sorting params
 *
 * @return array Sorting params
 */
function fn_get_default_products_sorting()
{
    $params  = explode('-', Registry::get('settings.Appearance.default_products_sorting'));
    if (is_array($params) && count($params) == 2) {
        $sorting = array (
            'sort_by' => array_shift($params),
            'sort_order' => array_shift($params),
        );
    } else {
        $default_sorting = fn_get_products_sorting();
        $sort_by = current(array_keys($default_sorting));
        $sorting = array (
            'sort_by' => $sort_by,
            'sort_order' => $default_sorting[$sort_by]['default_order'],
        );
    }

    return $sorting;
}

/**
 * Gets products from feature comparison list
 *
 * @return array List of compared products
 */
function fn_get_comparison_products()
{
    $compared_products = [];

    if (!empty(Tygh::$app['session']['comparison_list'])) {
        $params = array(
            'pid' => Tygh::$app['session']['comparison_list'],
        );
        list($compared_products, ) = fn_get_products($params);

        fn_gather_additional_products_data($compared_products, ['get_icon' => true, 'get_detailed' => true, 'get_additional' => false, 'get_options' => false]);
    }

    /**
     * Changes compared products
     *
     * @param array $compared_products List of compared products
     */
    fn_set_hook('get_comparison_products_post', $compared_products);

    return $compared_products;
}

/**
 * Product fields for multi update
 *
 * @return array Product fields
 */
function fn_get_product_fields()
{
    $fields = [
        [
            'name' => '[data][status]',
            'text' => __('status'),
            'disabled' => 'Y',
            'field' => 'status'
        ],
        [
            'name' => '[data][product]',
            'text' => __('product_name'),
            'disabled' => 'Y',
            'field' => 'product'
        ],
        [
            'name' => '[data][price]',
            'text' => __('price'),
            'field' => 'price'
        ],
        [
            'name' => '[data][list_price]',
            'text' => __('list_price'),
            'field' => 'list_price'
        ],
        [
            'name' => '[data][short_description]',
            'text' => __('short_description'),
            'field' => 'short_description'
        ],
        [
            'name' => '[data][promo_text]',
            'text' => __('promo_text'),
            'field' => 'promo_text'
        ],
        [
            'name' => '[categories]',
            'text' => __('categories'),
            'field' => 'categories'
        ],
        [
            'name' => '[data][full_description]',
            'text' => __('full_description'),
            'field' => 'full_description'
        ],
        [
            'name' => '[data][search_words]',
            'text' => __('search_words'),
            'field' => 'search_words'
        ],
        [
            'name' => '[data][meta_keywords]',
            'text' => __('meta_keywords'),
            'field' => 'meta_keywords'
        ],
        [
            'name' => '[data][meta_description]',
            'text' => __('meta_description'),
            'field' => 'meta_description'
        ],
        [
            'name' => '[main_pair]',
            'text' => __('image_pair'),
            'field' => 'main_pair'
        ],
        [
            'name' => '[data][min_qty]',
            'text' => __('min_order_qty'),
            'field' => 'min_qty'
        ],
        [
            'name' => '[data][max_qty]',
            'text' => __('max_order_qty'),
            'field' => 'max_qty'
        ],
        [
            'name' => '[data][qty_step]',
            'text' => __('quantity_step'),
            'field' => 'qty_step'
        ],
        [
            'name' => '[data][list_qty_count]',
            'text' => __('list_quantity_count'),
            'field' => 'list_qty_count'
        ],
        [
            'name' => '[data][product_code]',
            'text' => __('sku'),
            'field' => 'product_code'
        ],
        [
            'name' => '[data][weight]',
            'text' => __('weight'),
            'field' => 'weight'
        ],
        [
            'name' => '[data][shipping_freight]',
            'text' => __('shipping_freight'),
            'field' => 'shipping_freight'
        ],
        [
            'name' => '[data][free_shipping]',
            'text' => __('free_shipping'),
            'field' => 'free_shipping'
        ],
        [
            'name' => '[data][zero_price_action]',
            'text' => __('zero_price_action'),
            'field' => 'zero_price_action'
        ],
        [
            'name' => '[data][taxes]',
            'text' => __('taxes'),
            'field' => 'taxes'
        ],
        [
            'name' => '[data][features]',
            'text' => __('features'),
            'field' => 'features'
        ],
        [
            'name' => '[data][page_title]',
            'text' => __('page_title'),
            'field' => 'page_title'
        ],
        [
            'name' => '[data][timestamp]',
            'text' => __('creation_date'),
            'field' => 'timestamp'
        ],
        [
            'name' => '[data][amount]',
            'text' => __('quantity'),
            'field' => 'amount'
        ],
        [
            'name' => '[data][avail_since]',
            'text' => __('available_since'),
            'field' => 'avail_since'
        ],
        [
            'name' => '[data][out_of_stock_actions]',
            'text' => __('out_of_stock_actions'),
            'field' => 'out_of_stock_actions'
        ],
        [
            'name' => '[data][details_layout]',
            'text' => __('product_details_view'),
            'field' => 'details_layout'
        ],
        [
            'name' => '[data][min_items_in_box]',
            'text' => __('minimum_items_in_box'),
            'field' => 'min_items_in_box'
        ],
        [
            'name' => '[data][max_items_in_box]',
            'text' => __('maximum_items_in_box'),
            'field' => 'max_items_in_box'
        ],
        [
            'name' => '[data][box_length]',
            'text' => __('box_length'),
            'field' => 'box_length'
        ],
        [
            'name' => '[data][box_width]',
            'text' => __('box_width'),
            'field' => 'box_width'
        ],
        [
            'name' => '[data][box_height]',
            'text' => __('box_height'),
            'field' => 'box_height'
        ],
    ];

    if (YesNo::isTrue(Registry::get('settings.General.enable_edp'))) {
        $fields[] = [
            'name' => '[data][is_edp]',
            'text' => __('downloadable'),
            'field' => 'is_edp'
        ];
        $fields[] = [
            'name' => '[data][edp_shipping]',
            'text' => __('edp_enable_shipping'),
            'field' => 'edp_shipping'
        ];
    }

    if (Registry::get('config.tweaks.disable_localizations') === false) {
        $fields[] =  [
            'name'  => '[data][localization]',
            'text'  => __('localization'),
            'field' => 'localization',
        ];
    }

    $fields[] =  [
        'name'  => '[data][usergroup_ids]',
        'text'  => __('usergroups'),
        'field' => 'usergroup_ids',
    ];

    if (Registry::get('settings.General.inventory_tracking') !== YesNo::NO) {
        $fields[] = [
            'name' => '[data][tracking]',
            'text' => __('inventory'),
            'field' => 'tracking'
        ];
    }

    if (fn_allowed_for('ULTIMATE,MULTIVENDOR') && !Registry::get('runtime.company_id')) {
        $fields[] = [
            'name' => '[data][company_id]',
            'text' => fn_allowed_for('MULTIVENDOR') ? __('vendor') : __('store'),
            'field' => 'company_id'
        ];
    }

    if (fn_allowed_for('ULTIMATE') || (fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id') == 0)) {
        $fields[] = [
            'name' => '[data][popularity]',
            'text' => __('popularity'),
            'field' => 'popularity'
        ];
    }

    $fields = fn_filter_product_overridable_fields($fields);

    /**
     * Hook for change fields array
     *
     * @param array $fields Product fields
     */
    fn_set_hook('get_product_fields', $fields);

    return $fields;
}

/**
 * Get product code by product identifier.
 *
 * @param int   $product_id         Product identifier.
 * @param array $product_options    Selected options.
 *
 * @return string
 */
function fn_get_product_code($product_id, $product_options = array())
{
    $product_code = null;

    /**
     * Executed when a product code is requested by the product ID.
     * Allows you to substitute the product code.
     *
     * @param int           $product_id        Product identifier
     * @param array         $product_options   Selected options
     * @param string|null   $product_code      Product code
     */
    fn_set_hook('get_product_code', $product_id, $product_options, $product_code);

    if ($product_code === null) {
        $product_code = db_get_field('SELECT product_code FROM ?:products WHERE product_id = ?i', $product_id);
    }

    return (string) $product_code;
}

/**
 * Returns product creation timestamp
 *
 * @param int $product_id Product ID
 * @param bool $day_begin Set timestamp to beginning of the day
 * @return int product creation timestamp
 */
function fn_get_product_timestamp($product_id, $day_begin = false)
{
    if (empty($product_id)) {
        return false;
    }

    $timestamp = db_get_field("SELECT timestamp FROM ?:products WHERE product_id = ?i", $product_id);

    if ($day_begin) {
        $timestamp = mktime(0,0,0, date("m", $timestamp), date("d", $timestamp), date("Y", $timestamp));
    }

    return $timestamp;
}

/**
 * Filtering product data before save
 *
 * @deprecated since 4.11.1.
 *
 * @param  array &$request      $_REQUEST
 * @param  array &$product_data Product data
 */
function fn_filter_product_data(&$request, &$product_data)
{
    /**
     * Filtering product data
     *
     * @param array $request      $_REQUEST
     * @param array $product_data $product_data
     */
    fn_set_hook('filter_product_data', $request, $product_data);
}

/**
 * Gets amount of a product in stock.
 *
 * @param int                 $product_id Product identifier
 * @param array<string, bool> $params     Extra parameters
 *
 * @return int Amount
 */
function fn_get_product_amount($product_id, array &$params = [])
{
    $amount = db_get_field(
        'SELECT prod.amount'
        . ' FROM ?:products AS prod'
        . ' WHERE prod.product_id = ?i',
        $product_id
    );

    /**
     * Executed after getting product amount, allows you to change amount
     *
     * @param int                 $product_id Product identifier
     * @param int                 $amount     Product amount
     * @param array<string, bool> $params     Extra parameters
     */
    fn_set_hook('get_product_amount_post', $product_id, $amount, $params);

    return (int) $amount;
}

/**
 * Gets product statuses to display in the status picker on the product management pages.
 *
 * @param string $status     Current product status
 * @param bool   $add_hidden Whether to add the Hidden status
 * @param string $lang_code  Two-letter language code
 *
 * @return string[]
 */
function fn_get_product_statuses($status, $add_hidden = false, $lang_code = CART_LANGUAGE)
{
    $statuses = fn_get_default_statuses($status, $add_hidden, $lang_code);

    /**
     * Executes after product statuses list is obtained, allows you to add or remove product statuses from it
     *
     * @param string   $status     Current product status
     * @param bool     $add_hidden Whether to add the Hidden status
     * @param string   $lang_code  Two-letter language code
     * @param string[] $statuses   Product statuses
     */
    fn_set_hook('get_product_statuses_post', $status, $add_hidden, $lang_code, $statuses);

    return $statuses;
}

/**
 * Gets product statuses to display in the status picker on the product search form.
 *
 * @param string $lang_code Two-letter language code
 *
 * @return string[]
 */
function fn_get_all_product_statuses($lang_code = CART_LANGUAGE)
{
    $statuses = fn_get_default_statuses('', true, $lang_code);

    /**
     * Executes after product statuses list is obtained, allows you to add or remove product statuses from it
     *
     * @param string   $lang_code Two-letter language code
     * @param string[] $statuses  Product statuses
     */
    fn_set_hook('get_all_product_statuses_post', $lang_code, $statuses);

    return $statuses;
}

/**
 * Prepares product quick view data
 *
 * @param array $params Parameteres for gathering additional quick view data
 * @return boolean Always true
 */
function fn_prepare_product_quick_view($params)
{
    if (!empty($params['prev_url'])) {
        Tygh::$app['view']->assign('redirect_url', $params['prev_url']);
    }

    /**
     * Additional actions for product quick view
     *
     * @param array $_REQUEST Request parameters
     */
    fn_set_hook('prepare_product_quick_view', $_REQUEST);

    return true;
}

function fn_get_product_pagination_steps($cols, $products_per_page)
{
    $min_range = $cols * 4;
    $max_ranges = 4;
    $steps = array();

    for ($i = 0; $i < $max_ranges; $i++) {
        $steps[] = $min_range;
        $min_range = $min_range * 2;
    }

    $steps[] = (int) $products_per_page;

    $steps = array_unique($steps);

    sort($steps, SORT_NUMERIC);

    return $steps;
}

/**
 * Initializes product tab
 *
 * @param array $product Product data
 *
 * @return bool
 */
function fn_init_product_tabs(array $product)
{
    /**
     * Change product data before tabs initializing
     *
     * @param array $product Product data
     */
    fn_set_hook('init_product_tabs_pre', $product);

    $product_id = !empty($product['product_id']) ? $product['product_id'] : 0;

    $tabs = ProductTabs::instance()->getList(
        '',
        $product_id,
        DESCR_SL
    );

    foreach ($tabs as $tab_id => $tab) {
        if ($tab['status'] == 'D') {
            continue;
        }
        if (!empty($tab['template'])) {
            $tabs[$tab_id]['html_id'] = fn_basename($tab['template'], ".tpl");
        } else {
            $tabs[$tab_id]['html_id'] = 'product_tab_' . $tab_id;
        }

        if ($tab['show_in_popup'] != "Y") {
            Registry::set('navigation.tabs.' . $tabs[$tab_id]['html_id'], array (
                'title' => $tab['name'],
                'js' => true
            ));
        }
    }

    /**
     * Change product tabs and data before passing tabs variable to view
     *
     * @param array $product Product data
     * @param array $tabs    Product tabs
     */
    fn_set_hook('init_product_tabs_post', $product, $tabs);

    Tygh::$app['view']->assign('tabs', $tabs);

    return true;
}

/**
 * Gets products subscribers
 *
 * @param array<string, int|string> $params         Search subscribers params
 * @param int                       $items_per_page Elements per page
 * @param string                    $lang_code      Two letters language code
 *
 * @return array<array<int, int|string>> Subscribers list
 */
function fn_get_product_subscribers($params, $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    // Set default values to input params
    $default_params = array (
        'page' => 1,
        'email' => '',
        'product_id' => 0,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    /**
     * Changes params for getting product subscribers
     *
     * @param array  $params         Search subscribers params
     * @param int    $items_per_page Elements per page
     * @param string $lang_code      2-letters language code
     */
    fn_set_hook('get_product_subscribers_pre', $params, $items_per_page, $lang_code);

    // Init filter
    $params = LastView::instance()->update('subscribers', $params);

    $fields = [
        'subscriber_id' => 'subscription_id as subscriber_id',
        'email'         => 'email'
    ];
    $conditions = [
        'product_id' => db_quote('product_id = ?i', $params['product_id'])
    ];

    $joins = [];

    $limit = '';

    if (isset($params['email']) && fn_string_not_empty($params['email'])) {
        $conditions['email'] = db_quote('email LIKE ?l', '%' . trim($params['email']) . '%');
    }


    /**
     * Changes SQL params for the subscribers search
     *
     * @param array  $params     Subscribers search params
     * @param string $joins      Join parameters
     * @param array  $conditions Request condition
     * @param array  $fields     Selectable fields
     * @param string $lang_code  Language code
     */
    fn_set_hook('get_product_subscribers', $params, $joins, $conditions, $fields, $lang_code);

    $sorting = db_sort($params, array('email' => 'email'), 'email', 'asc');

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field(
            'SELECT COUNT(*) FROM ?:product_subscriptions ?p WHERE 1=1 AND ?p',
            implode(' ', $joins),
            implode(' AND ', $conditions)
        );
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $subscribers = db_get_hash_array(
        'SELECT ?p'
        . ' FROM ?:product_subscriptions ?p'
        . ' WHERE 1=1 AND ?p ?p ?p',
        'subscriber_id',
        implode(',', $fields),
        implode(' ', $joins),
        implode(' AND ', $conditions),
        $sorting,
        $limit
    );

    /**
     * Changes product subscribers
     *
     * @param int   $product_id  Product identifier
     * @param array $params      Search subscribers params
     * @param array $subscribers Array of subscribers
     */
    fn_set_hook('get_product_subscribers_post', $params, $subscribers);

    return array($subscribers, $params);
}

/**
 * Updates product subscriber (updates, if product subscriber exist and inserts, if it is not).
 *
 * @param int                       $product_id      Product identifier
 * @param array<string, int|string> $subscriber_data Subscriber data which was updated
 *
 * @return int|false
 */
function fn_update_product_subscriber($product_id, $subscriber_data)
{
    if (empty($product_id) || empty($subscriber_data) || empty($subscriber_data['email'])) {
        return false;
    }

    /**
     * Executes before updating product subscriber.
     *
     * @param int                       $product_id      Product identifier
     * @param array<string, int|string> $subscriber_data Subscriber data which was updated
     */
    fn_set_hook('update_product_subscriber_pre', $product_id, $subscriber_data);

    $subscription_id = db_get_field(
        'SELECT subscription_id FROM ?:product_subscriptions WHERE product_id = ?i AND email = ?s',
        $product_id,
        $subscriber_data['email']
    );

    if ($subscription_id && empty($subscriber_data['user_id'])) {
        return false;
    }

    $subscriber_data = array_merge($subscriber_data, [
        'subscription_id' => (int) $subscription_id,
        'product_id'      => (int) $product_id
    ]);

    return db_replace_into('product_subscriptions', $subscriber_data);
}

/**
 * Deletes subscribers of the product
 *
 * @param int|array<int> $subscriber_ids Subscriber identifiers
 *
 * @return bool
 */
function fn_delete_product_subscribers($subscriber_ids)
{
    if (empty($subscriber_ids)) {
        return false;
    }

    $subscriber_ids = (is_array($subscriber_ids)) ? $subscriber_ids : [$subscriber_ids];
    db_query('DELETE FROM ?:product_subscriptions WHERE subscription_id IN (?n)', $subscriber_ids);

    return true;
}

/**
 * Updates product popularity (updates, if product popularity exist and inserts, if it is not).
 *
 * @param int   $product_id Product id
 * @param array $popularity Popularity data
 *
 * @return void
 *
 * @psalm-suppress RedundantCastGivenDocblockType
 */
function fn_update_product_popularity($product_id, array $popularity)
{
    $default_popularity = [
        'viewed' => 0,
        'added' => 0,
        'deleted' => 0,
        'bought' => 0,
        'total' => 0,
    ];

    $popularity = array_merge($default_popularity, $popularity);

    $popularity['product_id'] = (int) $product_id;

    $update_data = [
        'viewed'  => db_quote('viewed = viewed + ?i', $popularity['viewed']),
        'added'   => db_quote('added = added + ?i', $popularity['added']),
        'deleted' => db_quote('deleted = deleted + ?i', $popularity['deleted']),
        'bought'  => db_quote('bought = bought + ?i', $popularity['bought']),
        'total'   => db_quote('total = total + ?i', $popularity['total'])
    ];

    $insert_data = $popularity;
    $insert_data['total'] = ($popularity['deleted'] > 0) ? 0 : $popularity['total'];

    db_query(
        'INSERT INTO ?:product_popularity ?e ON DUPLICATE KEY UPDATE ?p',
        $insert_data,
        implode(', ', $update_data)
    );

    /**
     * Executes after updating product popularity.
     *
     * @param int   $product_id  Product if
     * @param array $popularity  Popularity data which was updated
     */
    fn_set_hook('update_product_popularity', $product_id, $popularity);
}

/**
 * Gets number of products by the params
 *
 * @param array $params Params to count products
 *
 * @return int
 */
function fn_get_products_count(array $params)
{
    $params['get_conditions'] = true;
    $params['custom_extend'] = ['sharing'];

    list(, $join, $condition) = fn_get_products($params);

    $count = db_get_field('SELECT COUNT(DISTINCT products.product_id) FROM ?:products AS products' . $join . ' WHERE 1=1 ' . $condition);

    return (int) $count;
}

/**
 * Filters product fields, unsets field if overridable field is set global
 *
 * @param array<array<string, string>> $fields        List of fields
 * @param int|null                     $company_id    Company ID
 * @param int|null                     $storefront_id Storefront ID
 *
 * @return array<array<string, string>>
 */
function fn_filter_product_overridable_fields(array $fields, $company_id = null, $storefront_id = null)
{
    $schema = fn_get_product_overridable_fields_schema();

    foreach ($fields as $field_key => $field) {
        if (!isset($schema[$field['field']])) {
            continue;
        }

        $field_schema = $schema[$field['field']];
        $global_value = Settings::getSettingValue($field_schema['global_setting'], $company_id, $storefront_id);

        if ($global_value === null) {
            continue;
        }

        unset($fields[$field_key]);
    }

    return $fields;
}

/**
 * Normalizes overridable product field value
 *
 * @param string   $field         Fieild name (tracking, min_qty, etc)
 * @param mixed    $value         Field value
 * @param int|null $company_id    Company ID
 * @param int|null $storefront_id Storefront ID
 *
 * @return mixed
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
function fn_normalize_product_overridable_field_value($field, $value, $company_id = null, $storefront_id = null)
{
    static $schema;

    if ($schema === null) {
        $schema = fn_get_product_overridable_fields_schema();
    }

    if (!isset($schema[$field])) {
        return $value;
    }

    $field_schema = $schema[$field];

    $global_value = Settings::getSettingValue($field_schema['global_setting'], $company_id, $storefront_id);

    if ($global_value !== null) {
        return $global_value;
    }

    if ($value !== null) {
        return $value;
    }

    return Settings::getSettingValue($field_schema['default_setting'], $company_id, $storefront_id);
}

/**
 * Normalizes overridable fields of product
 *
 * @param array<string, mixed> $product       Product data
 * @param int|null             $company_id    Company ID
 * @param int|null             $storefront_id Storefront ID
 *
 * @return array<string, mixed> Normalized product data
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
function fn_normalize_product_overridable_fields(array $product, $company_id = null, $storefront_id = null)
{
    $schema = fn_get_product_overridable_fields_schema();
    $fields = array_keys($schema);

    foreach ($fields as $field) {
        if (!array_key_exists($field, $product)) {
            continue;
        }

        $product[$field . '_raw'] = $product[$field];
        $product[$field] = fn_normalize_product_overridable_field_value($field, $product[$field], $company_id, $storefront_id);
    }

    return $product;
}

/**
 * Normalizes overridable fields of products
 *
 * @param array<int, array<string, mixed>> $products      Products
 * @param int|null                         $company_id    Company ID
 * @param int|null                         $storefront_id Storefront ID
 *
 * @return array<int, array<string, mixed>> Normalized products
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
function fn_normalize_products_overridable_fields(array $products, $company_id = null, $storefront_id = null)
{
    foreach ($products as &$product) {
        $product = fn_normalize_product_overridable_fields($product, $company_id, $storefront_id);
    }
    unset($product);

    return $products;
}

/**
 * Prepares to save overridable fields of product
 *
 * @param array<string, mixed> $product       Product data
 * @param int|null             $company_id    Company ID
 * @param int|null             $storefront_id Storefront ID
 *
 * @return array<string, mixed> Prepared product data
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
function fn_prepare_product_overridable_fields(array $product, $company_id = null, $storefront_id = null)
{
    $schema = fn_get_product_overridable_fields_schema();

    foreach ($schema as $field => $field_schema) {
        if (!array_key_exists($field, $product)) {
            continue;
        }

        $global_value = Settings::getSettingValue($field_schema['global_setting'], $company_id, $storefront_id);

        if ($global_value !== null) {
            unset($product[$field]);
            continue;
        }

        if ($product[$field] === '' || $product[$field] === '__default__') {
            $product[$field] = null;
            continue;
        }
    }

    return $product;
}

/**
 * Gets product overridable fields schema
 *
 * @return array<string, array{global_setting: string, default_setting: string}>
 */
function fn_get_product_overridable_fields_schema()
{
    static $schema;

    if ($schema !== null) {
        return $schema;
    }

    return $schema = fn_get_schema('products', 'overridable_fields');
}

/**
 * Checks whether product notification is enabled
 *
 * @param int    $product_id Product identificator
 * @param int    $user_id    User identificator
 * @param string $email      Email for product notifications
 *
 * @return string Return product_notification parameter
 */
function fn_check_product_notification_setting($product_id, $user_id, $email)
{
    $product_notification_enabled = YesNo::NO;

    if (isset(Tygh::$app['session']['product_notifications'])) {
        if (
            isset(Tygh::$app['session']['product_notifications']['product_ids'])
            && in_array($product_id, Tygh::$app['session']['product_notifications']['product_ids'])
        ) {
            $product_notification_enabled = YesNo::YES;
        }
    }

    if (($user_id === 0) && !empty($email)) {
        if (
            !db_get_field(
                'SELECT subscription_id FROM ?:product_subscriptions WHERE product_id = ?i AND email = ?s',
                $product_id,
                $email
            )
        ) {
            $product_notification_enabled = YesNo::NO;
        }
    } elseif (
        !db_get_field(
            'SELECT subscription_id FROM ?:product_subscriptions WHERE product_id = ?i AND user_id = ?i',
            $product_id,
            $user_id
        )
    ) {
        $product_notification_enabled = YesNo::NO;
    }

    return $product_notification_enabled;
}

/**
 * Forms params from request for getting products picker list
 *
 * @param array<string, int|bool|string> $request Request data
 *
 * @return array<string, int|bool|string> Formed params
 */
function fn_products_form_product_list_params($request)
{
    $page_number = isset($request['page']) ? (int) $request['page'] : 1;
    $page_size = isset($request['page_size']) ? (int) $request['page_size'] : 10;
    $search_query = isset($request['q']) ? $request['q'] : null;
    $product_type = isset($request['product_type']) ? $request['product_type'] : '';

    $params = [
        'area'                     => 'A',
        'page'                     => $page_number,
        'q'                        => $search_query,
        'items_per_page'           => $page_size,
        'pcode_from_q'             => 'Y',
        'product_type'             => $product_type
    ];

    if (isset($request['preselected'])) {
        $params['pid'] = $request['preselected'];
    }

    if (isset($request['company_id'])) {
        $params['company_id'] = $request['company_id'];
    }

    if (isset($request['company_ids'])) {
        $params['company_ids'] = $request['company_ids'];
    }

    if (isset($request['ids'])) {
        $params['pid'] = $request['ids'];
        $params['items_per_page'] = 0;
    }

    if (isset($request['for_current_storefront'])) {
        $params['for_current_storefront'] = true;
    }

    if (isset($request['exclude_pid'])) {
        $params['exclude_pid'] = is_array($request['exclude_pid'])
            ? $request['exclude_pid']
            : fn_explode(',', $request['exclude_pid']);
    }

    /**
     * Changes parameters for getting products picker list
     *
     * @param array<string, int|bool|string> $request Request data
     * @param array<string, int|bool|string> $params Parameter for getting products picker list
     */
    fn_set_hook('products_form_product_list_params_post', $request, $params);

    return $params;
}
