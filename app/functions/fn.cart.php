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

use Faker\Factory as Faker;
use Illuminate\Support\Collection;
use Tygh\BlockManager\Block;
use Tygh\BlockManager\Location;
use Tygh\Embedded;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\OrderDataTypes;
use Tygh\Enum\OrderStatuses;
use Tygh\Enum\OutOfStockActions;
use Tygh\Enum\ProductOptionTypes;
use Tygh\Enum\ProductTracking;
use Tygh\Enum\ProductZeroPriceActions;
use Tygh\Enum\ProfileDataTypes;
use Tygh\Enum\ProfileFieldLocations;
use Tygh\Enum\ProfileFieldSections;
use Tygh\Enum\ShippingCalculationTypes;
use Tygh\Enum\ShippingRateTypes;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Enum\VendorStatuses;
use Tygh\Enum\YesNo;
use Tygh\Languages\Languages;
use Tygh\Navigation\LastView;
use Tygh\Notifications\EventIdProviders\OrderProvider;
use Tygh\Providers\EventDispatcherProvider;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Shippings\Shippings;
use Tygh\Storage;
use Tygh\Themes\Themes;
use Tygh\Tools\SecurityHelper;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Gets displayable product data to show it in the cart
 *
 * @param string $hash             Unique product HASH
 * @param array  $product          Product data
 * @param bool   $skip_promotion   Skip promotion calculation
 * @param array  $cart             Array of cart content and user information necessary for purchase
 * @param array  $auth             Array with authorization data
 * @param int    $promotion_amount Amount of product in promotion (like Free products, etc)
 * @param string $lang_code        Two-letter language code
 *
 * @return array|bool Product data
 */
function fn_get_cart_product_data($hash, &$product, $skip_promotion, &$cart, &$auth, $promotion_amount = 0, $lang_code = CART_LANGUAGE)
{
    /**
     * Prepare params before getting product data from cart
     *
     * @param string $hash             Unique product HASH
     * @param array  $product          Product data
     * @param bool   $skip_promotion   Skip promotion calculation
     * @param array  $cart             Array of cart content and user information necessary for purchase
     * @param array  $auth             Array with authorization data
     * @param int    $promotion_amount Amount of product in promotion (like Free products, etc)
     * @param string $lang_code        Two-letter language code
     */
    fn_set_hook('get_cart_product_data_pre', $hash, $product, $skip_promotion, $cart, $auth, $promotion_amount, $lang_code);

    $params = [
        'company_statuses' => [
            VendorStatuses::ACTIVE
        ]
    ];

    if (!empty($product['product_id'])) {

        $fields = [
            '?:products.product_id',
            '?:products.company_id',
            "GROUP_CONCAT(IF(?:products_categories.link_type = 'M', CONCAT(?:products_categories.category_id, 'M'), ?:products_categories.category_id)) as category_ids",
            '?:products.product_code',
            '?:products.weight',
            '?:products.tracking',
            '?:product_descriptions.product',
            '?:product_descriptions.short_description',
            '?:products.is_edp',
            '?:products.edp_shipping',
            '?:products.shipping_freight',
            '?:products.free_shipping',
            '?:products.zero_price_action',
            '?:products.tax_ids',
            '?:products.qty_step',
            '?:products.list_qty_count',
            '?:products.max_qty',
            '?:products.min_qty',
            '?:products.amount as in_stock',
            '?:products.shipping_params',
            '?:companies.status as company_status',
            '?:products.out_of_stock_actions',
            '?:products.updated_timestamp'
        ];

        $join  = db_quote("LEFT JOIN ?:product_descriptions ON ?:product_descriptions.product_id = ?:products.product_id AND ?:product_descriptions.lang_code = ?s", $lang_code);

        $_p_statuses = [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN];
        $_c_statuses = [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN];

        $storefront = StorefrontProvider::getStorefront();
        $avail_cond = SiteArea::isStorefront(AREA)
            ? db_quote(' AND ?:categories.storefront_id IN (?n)', [0, $storefront->storefront_id])
            : '';

        if (
            fn_allowed_for('ULTIMATE')
            && Registry::get('runtime.company_id')
            && !SiteArea::isStorefront(AREA)
        ) {
            $avail_cond .= db_quote(' AND (?:categories.storefront_id IN (?n)', [0, $storefront->storefront_id])
                . ' OR ' . fn_get_company_condition('?:products.company_id', false) . ')';
        }

        $avail_cond .= (AREA == 'C') ? " AND (" . fn_find_array_in_set($auth['usergroup_ids'], '?:categories.usergroup_ids', true) . ")" : '';
        $avail_cond .= (AREA == 'C') ? " AND (" . fn_find_array_in_set($auth['usergroup_ids'], '?:products.usergroup_ids', true) . ")" : '';
        $avail_cond .= (AREA == 'C' && !(isset($auth['area']) && $auth['area'] == 'A')) ? db_quote(' AND ?:categories.status IN (?a) AND ?:products.status IN (?a)', $_c_statuses, $_p_statuses) : '';
        $avail_cond .= (AREA == 'C') ? fn_get_localizations_condition('?:products.localization') : '';

        $join .= " INNER JOIN ?:products_categories ON ?:products_categories.product_id = ?:products.product_id INNER JOIN ?:categories ON ?:categories.category_id = ?:products_categories.category_id $avail_cond";
        $join .= " LEFT JOIN ?:companies ON ?:companies.company_id = ?:products.company_id";

        if (fn_allowed_for('MULTIVENDOR')) {
            $fields[] = '?:company_descriptions.i18n_company as company_name';
            $join .= db_quote(
                ' LEFT JOIN ?:company_descriptions ON ?:company_descriptions.company_id = ?:products.company_id AND ?:company_descriptions.lang_code = ?s',
                $lang_code
            );
        } else {
            $fields[] = '?:companies.company as company_name';
        }

        /**
         * Prepare params before getting product data from cart
         *
         * @param string                           $hash             Unique product HASH
         * @param array<string, int|string|array>  $product          Product data
         * @param bool                             $skip_promotion   Skip promotion calculation
         * @param array<string, int|string|array>  $cart             Array of cart content and user information necessary for purchase
         * @param array<string, int|string|array>  $auth             Array with authorization data
         * @param int                              $promotion_amount Amount of product in promotion (like Free products, etc)
         * @param array<string, string>            $fields           SQL query fields
         * @param string                           $join             JOIN statement
         * @param array<string, array>             $params           Array of additional params
         */
        fn_set_hook('pre_get_cart_product_data', $hash, $product, $skip_promotion, $cart, $auth, $promotion_amount, $fields, $join, $params);

        $_pdata = db_get_row("SELECT " . implode(', ', $fields) . " FROM ?:products ?p WHERE ?:products.product_id = ?i GROUP BY ?:products.product_id", $join, $product['product_id']);
        $_pdata = fn_normalize_product_overridable_fields($_pdata);

        // delete product from cart if vendor was disabled.
        if (
            empty($_pdata)
            || (
                !empty($_pdata['company_id'])
                && !defined('ORDER_MANAGEMENT')
                && !in_array($_pdata['company_status'], $params['company_statuses'])
            )
        ) {
            fn_delete_cart_product($cart, $hash);

            return false;
        }

        if (!empty($_pdata['category_ids'])) {
            [$_pdata['category_ids'], $_pdata['main_category']] = fn_convert_categories($_pdata['category_ids']);
        } else {
            $_pdata['category_ids'] = array();
        }

        $_pdata['options_count'] = db_get_field("SELECT COUNT(*) FROM ?:product_options WHERE product_id = ?i AND status = 'A'", $product['product_id']);

        $amount = !empty($product['amount_total']) ? $product['amount_total'] : $product['amount'];
        $_pdata['price'] = fn_get_product_price($product['product_id'], $amount, $auth);

        $_pdata['base_price'] = (isset($product['stored_price']) && $product['stored_price'] == 'Y') ? $product['price'] : $_pdata['price'];

        /**
         * Executes after getting product data from database.
         *
         * @param int   $product_id Product ID
         * @param array $_pdata     Product data
         * @param array $product    Product data
         * @param array $auth       Array with authorization data
         * @param array $cart       Array of cart content and user information necessary for purchase
         * @param int   $hash       Unique product HASH
         */
        fn_set_hook('get_cart_product_data', $product['product_id'], $_pdata, $product, $auth, $cart, $hash);

        $product['stored_price'] = empty($product['stored_price']) ? 'N' : $product['stored_price'];
        $product['stored_discount'] = empty($product['stored_discount']) ? 'N' : $product['stored_discount'];
        $product['product_options'] = empty($product['product_options']) ? array() : $product['product_options'];

        if (empty($_pdata['product_id'])) { // FIXME - for deleted products for OM
            fn_delete_cart_product($cart, $hash);

            return array();
        }

        if (!empty($_pdata['options_count']) && empty($product['product_options'])) {
            $cart['products'][$hash]['product_options'] = fn_get_default_product_options($product['product_id']);
        }

        $product['amount'] = fn_check_amount_in_stock($product['product_id'], $product['amount'], $product['product_options'], $hash, $_pdata['is_edp'], !empty($product['original_amount']) ? $product['original_amount'] : 0, $cart);

        if ($product['amount'] == 0) {
            fn_delete_cart_product($cart, $hash);
            fn_save_cart_content($cart, $auth['user_id']);
            return false;
        }

        $exceptions = fn_get_product_exceptions($product['product_id'], true);
        if (!isset($product['options_type']) || !isset($product['exceptions_type'])) {
            $product = array_merge($product, db_get_row('SELECT options_type, exceptions_type FROM ?:products WHERE product_id = ?i', $product['product_id']));
            $product = fn_normalize_product_overridable_fields($product);
        }

        if (!fn_is_allowed_options_exceptions($exceptions, $product['product_options'], $product['options_type'], $product['exceptions_type']) && !defined('GET_OPTIONS')) {
            fn_set_notification(
                NotificationSeverity::ERROR,
                __('notice'),
                __('product_options_forbidden_combination', ['[product]' => $_pdata['product']])
            );
            fn_delete_cart_product($cart, $hash);

            return false;
        }

        if (!fn_is_allowed_options($product)) {
            fn_set_notification(
                NotificationSeverity::ERROR,
                __('notice'),
                __('product_disabled_options', ['[product]' => $_pdata['product']])
            );
            fn_delete_cart_product($cart, $hash);

            return false;
        }

        if (isset($product['extra']['custom_files'])) {
            $_pdata['extra']['custom_files'] = $product['extra']['custom_files'];
        }

        $_pdata['calculation'] = array();

        if (isset($product['extra']['exclude_from_calculate'])) {
            $_pdata['exclude_from_calculate'] = $product['extra']['exclude_from_calculate'];
            $_pdata['aoc'] = !empty($product['extra']['aoc']);
            $_pdata['price'] = 0;
        } else {
            if ($product['stored_price'] == 'Y') {
                $_pdata['price'] = $product['price'];
            }
        }

        if (isset($product['extra']['updated_timestamp']) && !empty($_pdata['updated_timestamp'])) {
            if ($product['extra']['updated_timestamp'] !== $_pdata['updated_timestamp']) {
                $cart['products'][$hash]['main_pair'] = fn_get_cart_product_icon($product['product_id'], $product);
                $cart['products'][$hash]['extra']['updated_timestamp'] = $_pdata['updated_timestamp'];
            }
        }

        $product['price'] = ($_pdata['zero_price_action'] == 'A' && isset($product['custom_user_price']))
            ? $product['custom_user_price']
            : floatval($_pdata['price']);

        $cart['products'][$hash]['price'] = $product['price'];

        $_pdata['original_price'] = $product['price'];

        if ($product['stored_price'] != 'Y' && !isset($product['extra']['exclude_from_calculate'])) {
            $_tmp = $product['price'];
            $product['price'] = fn_apply_options_modifiers($product['product_options'], $product['price'], 'P', array(), array('product_data' => $product));
            $product['modifiers_price'] = $_pdata['modifiers_price'] = $product['price'] - $_tmp; // modifiers
        } else {
            $product['modifiers_price'] = $_pdata['modifiers_price'] = 0;
        }

        if (isset($product['modifiers_price']) && $_pdata['zero_price_action'] == 'A') {
            $_pdata['base_price'] = $product['price'] - $product['modifiers_price'];
        }

        $_pdata['weight'] = fn_apply_options_modifiers($product['product_options'], $_pdata['weight'], 'W', array(), array('product_data' => $product));
        $_pdata['amount'] = $product['amount'];
        $_pdata['price'] = $_pdata['original_price'] = fn_format_price($product['price']);

        $_pdata['stored_price'] = $product['stored_price'];

        if ($cart['options_style'] == 'F') {
            $_pdata['product_options'] = fn_get_selected_product_options($product['product_id'], $product['product_options'], $lang_code);
        } elseif ($cart['options_style'] == 'I') {
            $_pdata['product_options'] = fn_get_selected_product_options_info($product['product_options'], $lang_code);
        } else {
            $_pdata['product_options'] = $product['product_options'];
        }

        fn_set_hook('get_cart_product_data_post_options', $product['product_id'], $_pdata, $product);

        if (($_pdata['free_shipping'] != 'Y' || AREA == 'A') && ($_pdata['is_edp'] != 'Y' || ($_pdata['is_edp'] == 'Y' && $_pdata['edp_shipping'] == 'Y'))) {
            $cart['shipping_required'] = true;
        }

        $cart['products'][$hash]['is_edp'] = (!empty($_pdata['is_edp']) && $_pdata['is_edp'] == 'Y') ? 'Y' : 'N';
        $cart['products'][$hash]['edp_shipping'] = (!empty($_pdata['edp_shipping']) && $_pdata['edp_shipping'] == 'Y') ? 'Y' : 'N';

        if (empty($cart['products'][$hash]['extra']['parent'])) { // count only products without parent
            if ($skip_promotion == true && !empty($promotion_amount)) {
                $cart['amount'] += $promotion_amount;
            } else {
                $cart['amount'] += $product['amount'];
            }
        }

        if ($skip_promotion == false) {
            if (
                (empty($cart['order_id']) || !empty($cart['recalculate_catalog_promotions']))
                && fn_promotion_is_recalculation_enabled($cart)
            ) {
                fn_promotion_apply('catalog', $_pdata, $auth);
            } else {
                if (isset($product['discount'])) {
                    if (isset($product['promotions'])) {
                        $_pdata['promotions'] = $product['promotions'];
                    }

                    $_pdata['discount'] = $product['discount'];
                    $_pdata['price'] -= (float) $product['discount'];

                    if ($_pdata['price'] < 0) {
                        $_pdata['discount'] += $_pdata['price'];
                        $_pdata['price'] = 0;
                    }
                }
            }

            // apply discount to the product
            if (!empty($_pdata['discount'])) {
                $cart['use_discount'] = true;
            }
        }

        if (!empty($product['object_id'])) {
            $_pdata['object_id'] = $product['object_id'];
        }

        $_pdata['shipping_params'] = empty($_pdata['shipping_params']) ? array() : unserialize($_pdata['shipping_params']);

        $_pdata['stored_discount'] = $product['stored_discount'];
        $cart['products'][$hash]['modifiers_price'] = $product['modifiers_price'];

        $_pdata['subtotal'] = $_pdata['price'] * $product['amount'];
        $cart['original_subtotal'] += $_pdata['original_price'] * $product['amount'];
        $cart['subtotal'] += $_pdata['subtotal'];

        /**
         * Prepare params before getting product data from cart
         *
         * @param string $hash             Unique product HASH
         * @param array  $product          Product data
         * @param bool   $skip_promotion   Skip promotion calculation
         * @param array  $cart             Array of cart content and user information necessary for purchase
         * @param array  $auth             Array with authorization data
         * @param int    $promotion_amount Amount of product in promotion (like Free products, etc)
         * @param array  $_pdata           Product data
         * @param string $lang_code        Two-letter language code
         */
        fn_set_hook('get_cart_product_data_post', $hash, $product, $skip_promotion, $cart, $auth, $promotion_amount, $_pdata, $lang_code);

        return $_pdata;
    }

    return array();
}

/**
 * Update cart products data
 *
 * @param array $cart          Array of cart content and user information necessary for purchase
 * @param array $cart_products Array of new data for products information update
 *
 * @return boolean Always true
 */
function fn_update_cart_data(&$cart, &$cart_products)
{
    foreach ($cart_products as $k => $v) {
        if (isset($cart['products'][$k])) {
            if (!isset($v['base_price'])) {
                $cart['products'][$k]['base_price'] = $v['base_price'] = $cart['products'][$k]['stored_price'] != 'Y' ? $v['price'] : $cart['products'][$k]['price'];
            } else {
                if ($cart['products'][$k]['stored_price'] == 'Y') {
                    $cart_products[$k]['base_price'] = $cart['products'][$k]['price'];
                }
            }

            $cart['products'][$k]['base_price'] = $cart['products'][$k]['stored_price'] != 'Y' ? $v['base_price'] : $cart['products'][$k]['price'];
            $cart['products'][$k]['price'] = $cart['products'][$k]['stored_price'] != 'Y' ? $v['price'] : $cart['products'][$k]['price'];
            if (isset($v['discount'])) {
                $cart['products'][$k]['discount'] = $v['discount'];
            }
            if (isset($v['promotions'])) {
                $cart['products'][$k]['promotions'] = $v['promotions'];
            }
            if (isset($v['category_ids'])) {
                $cart['products'][$k]['category_ids'] = $v['category_ids'];
            }
            if (isset($v['product'])) {
                $cart['products'][$k]['product'] = $v['product'];
            }
        }
    }

    return true;
}

/**
 * Get payment method data
 *
 * @param int    $payment_id payment ID
 * @param string $lang_code  2-letter language code
 *
 * @return array payment information
 */
function fn_get_payment_method_data($payment_id, $lang_code = CART_LANGUAGE)
{
    static $payments = array();

    if (empty($payments[$payment_id])) {
        $fields = array(
            '?:payments.*',
            '?:payment_descriptions.*',
            '?:payment_processors.processor',
            '?:payment_processors.type AS processor_type',
            '?:addons.status AS processor_status',
        );

        $join = db_quote(" LEFT JOIN ?:payment_descriptions ON ?:payments.payment_id = ?:payment_descriptions.payment_id AND ?:payment_descriptions.lang_code = ?s", $lang_code);
        $join .= db_quote(" LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id");
        $join .= db_quote(" LEFT JOIN ?:addons ON ?:payment_processors.addon = ?:addons.addon");

        /**
         * Change select condition (fields, joins) before selecting payment method data
         *
         * @param int    $payment_id payment ID
         * @param string $lang_code  2-letter language code
         * @param array  $fields     List of fields in SELECT query
         * @param array  $join       List of JOINed tables
         */
        fn_set_hook('summary_get_payment_method_data', $payment_id, $lang_code, $fields, $join);

        $payment = db_get_row("SELECT " . implode(', ', $fields) . " FROM ?:payments ?p WHERE ?:payments.payment_id = ?i", $join, $payment_id);

        if (!empty($payment)) {
            $payment['processor_params'] = (!empty($payment['processor_params'])) ? unserialize($payment['processor_params']) : '';
            $payment['tax_ids'] = !empty($payment['tax_ids']) ? fn_explode(',', $payment['tax_ids']) : array();
            $payment['image'] = fn_get_image_pairs($payment_id, 'payment', 'M', true, true, $lang_code);
        }

        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        [$storefronts,] = $repository->find(['payment_ids' => $payment_id]);
        $payment['storefront_ids'] = implode(',', array_keys($storefronts));

        fn_set_hook('summary_get_payment_method', $payment_id, $payment);
        $payments[$payment_id] = $payment;
    }

    return $payments[$payment_id];
}

/**
 * Gets payments method data
 *
 * @param array $params Array of flags/data which determines which data should be gathered
 *
 * @return array payments information
 */
function fn_get_payments($params = array())
{
    $default_params = [
        'area'             => AREA,
        'lang_code'        => DESCR_SL,
        'extend'           => [],
        'storefront_id'    => null,
        'processor_script' => null,
        'template'         => '',
    ];

    // Backward compatibility
    if (!is_array($params)) {
        $params = array(
            'lang_code' => DESCR_SL,
        );
    }

    $params = array_merge($default_params, $params);
    if ($params['area'] === 'C' && $params['storefront_id'] === null) {
        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = Tygh::$app['storefront'];
        $params['storefront_id'] = $storefront->storefront_id;
    }

    /**
     * Executes before fetching payments, allows you to modify the parameters passed to the function.
     *
     * @param array $params Array of flags/data which determines which data should be gathered
     */
    fn_set_hook('get_payments_pre', $params);

    $fields = array(
        '?:payments.*',
        '?:payment_descriptions.*',
        'IF (ISNULL(?:addons.status), "A", ?:addons.status) AS processor_status',
        '?:payment_processors.type AS processor_type',
        '?:payment_processors.processor_script AS processor_script',
    );

    $join = array(
        db_quote('LEFT JOIN ?:payment_descriptions ON ?:payment_descriptions.payment_id = ?:payments.payment_id AND ?:payment_descriptions.lang_code = ?s', $params['lang_code']),
        'LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id',
        'LEFT JOIN ?:addons ON ?:payment_processors.addon = ?:addons.addon',
    );

    $having = array();

    $order = array('?:payments.position');
    $condition = array();

    if (!empty($params['payment_id'])) {
        if (is_array($params['payment_id'])) {
            $condition[] = db_quote('?:payments.payment_id IN (?n)', $params['payment_id']);
        } else {
            $condition[] = db_quote('?:payments.payment_id = ?i', $params['payment_id']);
        }
    }

    if (!empty($params['status'])) {
        $condition[] = db_quote('?:payments.status = ?s', $params['status']);
    } elseif ($params['area'] == 'C') {
        $condition[] = db_quote('?:payments.status = ?s', 'A');
    }

    if (!empty($params['processor_status'])) {
        $having[] = db_quote('processor_status = ?s', $params['processor_status']);
    } elseif ($params['area'] == 'C') {
        $having[] = db_quote('processor_status = ?s', 'A');
    }

    if (!empty($params['usergroup_ids'])) {
        $condition[] = "(" . fn_find_array_in_set($params['usergroup_ids'], '?:payments.usergroup_ids', true) . ")";
    }

    if ($params['storefront_id']) {
        $join[] = db_quote(
            ' LEFT JOIN ?:storefronts_payments AS storefronts_payments'
            . ' ON storefronts_payments.payment_id = ?:payments.payment_id'
        );
        $condition[] = db_quote(
            '(storefronts_payments.storefront_id = ?i OR storefronts_payments.storefront_id IS NULL)',
            $params['storefront_id']
        );
    }

    if ($params['processor_script'] !== null) {
        $condition[] = db_quote('?:payment_processors.processor_script IN (?a)', (array) $params['processor_script']);
    }

    if (!empty($params['company_ids']) && is_array($params['company_ids'])) {
        $condition[] = db_quote('?:payments.company_id IN (?a)', $params['company_ids']);
    }

    if (!empty($params['template'])) {
        $condition[] = db_quote('?:payments.template LIKE ?s', '%' . $params['template'] . '%');
    }

    /**
     * Changes params to get payment processors
     *
     * @param array $params    Array of flags/data which determines which data should be gathered
     * @param array $fields    List of fields for retrieving
     * @param array $join      Array with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param array $order     Array containing SQL-query with sorting fields
     * @param array $condition Array containing SQL-query condition possibly prepended with a logical operator AND
     * @param array $having    Array containing SQL-query condition to HAVING group
     *
     */
    fn_set_hook('get_payments', $params, $fields, $join, $order, $condition, $having);

    $fields = implode(', ', $fields);
    $join = implode(' ', $join);
    $order = !empty($order) ? 'ORDER BY ' . implode(', ', $order) : '';
    $condition = !empty($condition) ? 'WHERE ' . implode(' AND ', $condition) : '';
    $having = !empty($having) ? 'HAVING ' . implode(' AND ', $having) : '';

    if (!empty($params['simple'])) {
        $payments = db_get_hash_single_array("SELECT $fields FROM ?:payments $join $condition $having $order", array('payment_id', 'payment'));
    } else {
        $payments = db_get_hash_array("SELECT $fields FROM ?:payments $join $condition $having $order", 'payment_id');
    }

    if (in_array('images', $params['extend'])) {
        foreach ($payments as &$payment) {
            $payment['image'] = fn_get_image_pairs($payment['payment_id'], 'payment', 'M', true, true);
        }
        unset($payment);
    }

    /**
     * Changes selected payments
     *
     * @param array $params   Array of flags/data which determines which data should be gathered
     * @param array $payments List of payments
     */
    fn_set_hook('get_payments_post', $params, $payments);

    return $payments;
}

/**
 * Create/Update payments data
 *
 * @param array  $payment_data
 * @param int    $payment_id
 * @param string $lang_code 2-letter language code
 *
 * @return int Payment id
 * @throws \Tygh\Exceptions\DeveloperException
 */
function fn_update_payment($payment_data, $payment_id, $lang_code = DESCR_SL)
{
    $default_params = [
        'payment_id' => $payment_id
    ];
    if (!$payment_id) {
        $default_params['storefront_ids'] = '';
    }
    $payment_data = array_merge($default_params, $payment_data);

    SecurityHelper::sanitizeObjectData('payment', $payment_data);

    $certificate_file = fn_filter_uploaded_data('payment_certificate');
    $certificates_dir = Registry::get('config.dir.certificates');
    $previous_storefronts = [];

    $can_remove_offline_payment_params = true;

    /**
     * Adds additional actions before payment updating
     *
     * @param array  $payment_data               Payment data
     * @param int    $payment_id                 Payment identifier
     * @param string $lang_code                  Language code
     * @param array  $certificate_file
     * @param string $certificates_dir
     * @param string $can_remove_offline_payment_params Whether offline payment parameters should be removed
     *
     */
    fn_set_hook('update_payment_pre', $payment_data, $payment_id, $lang_code, $certificate_file, $certificates_dir, $can_remove_offline_payment_params);

    /**
     * Create/update the certificate file
     * only for an existing payment method.
     *
     * Non-existing payment method will be created first,
     * then will be updated. (fn_update_payment calling at line 450).
     */
    if ($payment_id) {

        if ($certificate_file) {
            $file = reset($certificate_file);
            $filename = $payment_id . '/' . $file['name'];

            fn_mkdir($certificates_dir . $payment_id);
            fn_copy($file['path'], $certificates_dir . $filename);
            $payment_data['processor_params']['certificate_filename'] = $filename;
        }

        $old_params = fn_get_processor_data($payment_id);

        if (empty($payment_data['processor_params']['certificate_filename']) && isset($old_params['processor_params']['certificate_filename'])) {
            $payment_data['processor_params']['certificate_filename'] = $old_params['processor_params']['certificate_filename'];
        }

        if (!empty($payment_data['processor_params']['certificate_filename'])) {
            if (!empty($old_params['processor_params']['certificate_filename']) && $payment_data['processor_params']['certificate_filename'] != $old_params['processor_params']['certificate_filename']) {
                fn_rm($certificates_dir . $old_params['processor_params']['certificate_filename']);
            }

            if (!file_exists($certificates_dir . $payment_data['processor_params']['certificate_filename'])) {
                $payment_data['processor_params']['certificate_filename'] = '';
            }
        }

        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        [$previous_storefronts,] = $repository->find(['payment_ids' => $payment_id]);
    }

    if (!empty($payment_data['processor_id'])) {
        $payment_data['template'] = db_get_field("SELECT processor_template FROM ?:payment_processors WHERE processor_id = ?i", $payment_data['processor_id']);
    } elseif ($can_remove_offline_payment_params) {
        $payment_data['processor_params'] = '';
    }

    $payment_data['localization'] = !empty($payment_data['localization']) ? fn_implode_localizations($payment_data['localization']) : '';
    $payment_data['usergroup_ids'] = empty($payment_data['usergroup_ids'])
        ? USERGROUP_ALL
        : (is_array($payment_data['usergroup_ids'])
            ? implode(',', $payment_data['usergroup_ids'])
            : $payment_data['usergroup_ids']);
    $payment_data['tax_ids'] = !empty($payment_data['tax_ids']) ? fn_create_set($payment_data['tax_ids']) : '';

    // Update payment processor settings
    $processor_params = array();
    if (!empty($payment_data['processor_params'])) {
        $processor_params = $payment_data['processor_params'];
        $payment_data['processor_params'] = serialize($payment_data['processor_params']);
    }

    if (!empty($payment_id)) {
        $action = 'update';
        $arow = db_query("UPDATE ?:payments SET ?u WHERE payment_id = ?i", $payment_data, $payment_id);
        db_query("UPDATE ?:payment_descriptions SET ?u WHERE payment_id = ?i AND lang_code = ?s", $payment_data, $payment_id, $lang_code);

        if ($arow === false) {
            fn_set_notification('E', __('error'), __('object_not_found', array('[object]' => __('payment'))),'','404');
            $payment_id = false;
        }
    } else {
        $action = 'add';
        $payment_data['payment_id'] = $payment_id = db_query("INSERT INTO ?:payments ?e", $payment_data);
        foreach (Languages::getAll() as $payment_data['lang_code'] => $_v) {
            db_query("INSERT INTO ?:payment_descriptions ?e", $payment_data);
        }

        /**
         * Update the certificate
         */
        if ($certificate_file && $payment_id) {
            unset($payment_data['lang_code']);
            $payment_data['processor_params'] = $processor_params;
            fn_update_payment($payment_data, $payment_id, $lang_code);
        }
    }

    if (isset($payment_data['storefront_ids'])) {
        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        [$new_storefronts,] = $repository->find(['storefront_id' => $payment_data['storefront_ids']]);
        $added_storefronts = array_diff_key($new_storefronts, $previous_storefronts);
        /** @var \Tygh\Storefront\Storefront $storefront */
        foreach ($added_storefronts as $storefront) {
            $repository->save($storefront->addPaymentIds($payment_id));
        }
        $removed_storefronts = array_diff_key($previous_storefronts, $new_storefronts);
        foreach ($removed_storefronts as $storefront) {
            $repository->save($storefront->removePaymentIds($payment_id));
        }
    }

    fn_attach_image_pairs('payment_image', 'payment', $payment_id, $lang_code);

    fn_set_hook('update_payment_post', $payment_data, $payment_id, $lang_code, $certificate_file, $certificates_dir, $processor_params, $action);

    return $payment_id;
}

/**
 * Creates/Updates currency
 *
 * @param array  $currency_data Currency information
 * @param int    $currency_id   Currency id
 * @param string $lang_code     2-letter language code
 *
 * @return int Currency id
 */
function fn_update_currency($currency_data, $currency_id, $lang_code = DESCR_SL)
{
    /**
     * Updates currency data before updating
     *
     * @param array  $currency_data Currency information
     * @param int    $currency_id   Currency id
     * @param string $lang_code     2-letter language code
    */
    fn_set_hook('update_currency_pre', $currency_data, $currency_id, $lang_code);

    $currency_data['currency_code'] = strtoupper($currency_data['currency_code']);

    $is_primary_currency = !empty($currency_data['is_primary']) && YesNo::toBool($currency_data['is_primary']);
    $currency_data['coefficient'] = $is_primary_currency || !isset($currency_data['coefficient']) ? 1 : $currency_data['coefficient'];
    $currency_data['symbol'] = empty($currency_data['symbol']) ? '' : SecurityHelper::sanitizeHtml($currency_data['symbol']);

    if (empty($currency_data['coefficient']) || floatval($currency_data['coefficient']) <= 0) {
        fn_set_notification('W', __('warning'), __('currency_rate_greater_than_null'));

        return false;
    }

    $is_exists = db_get_field("SELECT COUNT(*) FROM ?:currencies WHERE currency_code = ?s AND currency_id <> ?i", $currency_data['currency_code'], $currency_id);

    if (!empty($is_exists)) {
        fn_set_notification('E', __('error'), __('error_currency_exists', array(
            '[code]' => $currency_data['currency_code'],
        )));

        return false;
    }

    if (isset($currency_data['decimals']) && $currency_data['decimals'] > 2) {
        fn_set_notification('W', __('warning'), __('notice_too_many_decimals', array(
            '[DECIMALS]' => $currency_data['decimals'],
            '[CURRENCY]' => $currency_data['currency_code'],
        )));
    }

    if ($is_primary_currency) {
        db_query("UPDATE ?:currencies SET is_primary = 'N' WHERE is_primary = 'Y'");
    }

    $previous_storefronts = [];

    if (empty($currency_id)) {
        $currency_data['currency_id'] = $currency_id = db_query('INSERT INTO ?:currencies ?e', $currency_data);
        fn_create_description('currency_descriptions', 'currency_id', $currency_data['currency_id'], $currency_data);
    } else {
        db_query('UPDATE ?:currencies SET ?u WHERE currency_id = ?i', $currency_data, $currency_id);
        db_query('UPDATE ?:currency_descriptions SET ?u WHERE currency_id = ?i AND lang_code = ?s', $currency_data, $currency_id, $lang_code);

        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        [$previous_storefronts,] = $repository->find(['currency_ids' => $currency_id]);
    }

    if (isset($currency_data['storefront_ids'])) {
        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        [$new_storefronts,] = $repository->find(['storefront_id' => $currency_data['storefront_ids']]);
        $added_storefronts = array_diff_key($new_storefronts, $previous_storefronts);
        /** @var \Tygh\Storefront\Storefront $storefront */
        foreach ($added_storefronts as $storefront) {
            $repository->save($storefront->addCurrencyIds($currency_id));
        }
        $removed_storefronts = array_diff_key($previous_storefronts, $new_storefronts);
        foreach ($removed_storefronts as $storefront) {
            $repository->save($storefront->removeCurrencyIds($currency_id));

            if ($storefront->getAvailableCurrencyIds()) {
                continue;
            }

            $primary_currency_id = db_get_field('SELECT currency_id FROM ?:currencies  WHERE is_primary = ?s', YesNo::YES);

            if (!$primary_currency_id) {
                fn_set_notification(
                    NotificationSeverity::WARNING,
                    __('warning'),
                    __(
                        'warning_default_currency_absent',
                        [
                            '[storefront]' => $storefront->name,
                            '[link]'    => fn_url('currencies.manage'),
                        ]
                    )
                );

                continue;
            }

            $repository->save($storefront->addCurrencyIds($primary_currency_id));

            fn_set_notification(
                NotificationSeverity::WARNING,
                __('warning'),
                __(
                    'storefront_currencies_assigned',
                    [
                        '[url]'  => fn_url('storefronts.update?storefront_id=' . $storefront->storefront_id),
                        '[name]' => $storefront->name,
                    ]
                )
            );
        }
    }

    /**
     * Changes result of currency saving
     *
     * @param array  $currency_data Currency information
     * @param int    $currency_id   Currency id
     * @param string $lang_code     2-letter language code
     * @param int    $currency_id   Currency id
    */
    fn_set_hook('update_currency_post', $currency_data, $currency_id, $lang_code, $currency_id);

    fn_save_currencies_integrity();

    return $currency_id;
}

/**
 * Get product amount
 *
 * @param int $product_id
 * @param int $user_id
 *
 * @return int purchased product amount
 */
function fn_get_ordered_products_amount($product_id, $user_id)
{

    if (empty($user_id) || empty($product_id)) {
        return 0;
    }

    $where = db_quote(' AND ?:order_details.product_id = ?i', $product_id);

    $orders_company_condition = '';

    if (fn_allowed_for('ULTIMATE')) {
        $orders_company_condition = fn_get_company_condition('?:orders.company_id');
    }

    $product_amount = db_get_field(
        "SELECT sum(?:order_details.amount)"
        . " FROM ?:order_details"
        . " INNER JOIN ?:orders"
            . " ON ?:orders.order_id = ?:order_details.order_id"
            . " AND ?:orders.is_parent_order != 'Y'"
                . " ?p"
        . " INNER JOIN ?:statuses"
            . " ON ?:statuses.status = ?:orders.status"
            . " AND ?:statuses.type = ?s"
        . " INNER JOIN ?:status_data"
            . " ON ?:statuses.status_id = ?:status_data.status_id"
            . " AND param = ?s"
            . " AND value = ?s"
        . " WHERE ?:orders.user_id = ?i"
            . " ?p"
        . " GROUP BY ?:order_details.product_id",
        $orders_company_condition,
        STATUSES_ORDER,
        'inventory',
        'D',
        $user_id,
        $where
    );

    if (empty($product_amount)) {
        return 0;
    }

    return $product_amount;
}

/**
 * Updates product quantity, sends stock notifications.
 *
 * @param int    $product_id      Product identifier
 * @param int    $amount_delta   Product quantity
 * @param array  $product_options List of selected product options
 * @param string $sign            Changes sign (+/-)
 * @param bool   $notify          Whether to send product stock notifications
 * @param array  $order_info      Order information
 *
 * @return bool
 */
function fn_update_product_amount($product_id, $amount_delta, $product_options, $sign, $notify = true, $order_info = [])
{

    /**
     * Executes when the quantity of the product in stock is updated, but before checking of tracking settings state
     * Allows to perform actions, when the quantity should be updated.
     *
     * @param int    $product_id      Product identifier
     * @param int    $amount_delta    Product new quantity value
     * @param array  $product_options List of selected product options
     * @param string $sign            Changes sign (+/-)
     * @param bool   $notify          Whether to send product stock notifications
     * @param array  $order_info      Order information
     */
    fn_set_hook('update_product_amount_before_tracking_checking', $product_id, $amount_delta, $product_options, $sign, $notify, $order_info);

    if (Registry::get('settings.General.inventory_tracking') === YesNo::NO) {
        return true;
    }

    $tracking_info = db_get_row('SELECT tracking, out_of_stock_actions FROM ?:products WHERE product_id = ?i', $product_id);
    $tracking_info = fn_normalize_product_overridable_fields($tracking_info);

    // Return if product does not exist
    if (empty($tracking_info)) {
        return true;
    }

    $tracking = $tracking_info['tracking'];
    $allow_pre_orders = $tracking_info['out_of_stock_actions'] == OutOfStockActions::BUY_IN_ADVANCE;

    if ($tracking === ProductTracking::DO_NOT_TRACK) {
        return true;
    }

    $product = db_get_row('SELECT amount, product_code FROM ?:products WHERE product_id = ?i', $product_id);
    $current_amount = $original_amount = $product['amount'];
    $product_code = $product['product_code'];

    /**
     * Executed before the quantity of the product in stock is updated.
     * Allows you to modify the remaining inventory.
     *
     * @param int    $product_id      Product identifier
     * @param int    $amount_delta    Product new quantity value
     * @param array  $product_options List of selected product options
     * @param string $sign            Changes sign (+/-)
     * @param string $tracking        Product inventory tracking mode
     * @param int    $current_amount  Current product quantity
     * @param string $product_code    Product code
     * @param bool   $notify          Whether to send product stock notifications
     * @param array  $order_info      Order information
     * @param int    $cart_id         Product cart ID
     */
    fn_set_hook('update_product_amount_pre', $product_id, $amount_delta, $product_options, $sign, $tracking, $current_amount, $product_code, $notify, $order_info, $cart_id);

    if ($sign == '-') {
        $new_amount = $current_amount - $amount_delta;

        // Tune new product amount for pre-ordered products
        if ($new_amount < 0 && Registry::get('settings.General.allow_negative_amount') != 'Y') {
            if (!$allow_pre_orders) {
                return false;
            }
        }

        // Notify administrator about inventory low stock
        if ($new_amount <= Registry::get('settings.General.low_stock_threshold') && !defined('ORDER_MANAGEMENT')) {
            // Log product low-stock
            $company_id = fn_get_company_id('products', 'product_id', $product_id);

            fn_log_event('products', 'low_stock', array (
                'product_id' => $product_id,
            ));
            $lang_code = fn_get_company_language($company_id);
            $lang_code = !empty($lang_code) ? $lang_code : Registry::get('settings.Appearance.backend_default_language');

            /** @var \Tygh\Mailer\Mailer $mailer */
            $mailer = Tygh::$app['mailer'];

            $mailer->send(array(
                'to' => 'company_orders_department',
                'from' => 'default_company_orders_department',
                'data' => array(
                    'product_options' => '',
                    'new_qty' => $new_amount,
                    'product_id' => $product_id,
                    'product_code' => $product_code,
                    'product' => fn_get_product_name($product_id, $lang_code),
                ),
                'template_code' => 'low_stock',
                'tpl' => 'orders/low_stock.tpl', // this parameter is obsolete and is used for back compatibility
                'company_id' => $company_id,
            ), 'A', $lang_code);
        }
    } else {
        $new_amount = $current_amount + $amount_delta;
    }

    /**
     * Executes when updating product quantity before setting new amount in the database,
     * allows to modify data passed to the SQL query.
     *
     * @param int    $new_amount      New product quantity
     * @param int    $product_id      Product ID
     * @param int    $cart_id         Product cart ID
     * @param string $tracking        Product inventory tracking mode
     * @param bool   $notify          Whether to send product stock notifications
     * @param array  $order_info      Order information
     * @param int    $amount_delta    Product new quantity value
     * @param int    $current_amount  Product current quantity value
     * @param int    $original_amount Product original quantity value
     * @param string $sign            Product quantity increment or decrement sign (-/+)
     *
     */
    fn_set_hook('update_product_amount', $new_amount, $product_id, $cart_id, $tracking, $notify, $order_info, $amount_delta, $current_amount, $original_amount, $sign);

    db_query('UPDATE ?:products SET amount = ?i WHERE product_id = ?i', $new_amount, $product_id);

    if (($current_amount <= 0) && ($new_amount > 0) && $notify === true) {
        fn_send_product_notifications($product_id);
    }

    /**
     * Executed after the quantity of the product in stock is updated.
     *
     * @param int    $product_id      Product identifier
     * @param int    $amount_delta    Product quantity
     * @param array  $product_options List of selected product options
     * @param string $sign            Changes sign (+/-)
     * @param string $tracking        Product inventory tracking mode
     * @param int    $current_amount  Current product quantity
     * @param int    $new_amount      New product quantity
     * @param string $product_code    Product code
     * @param bool   $notify          Whether to send product stock notifications
     */
    fn_set_hook(
        'update_product_amount_post',
        $product_id,
        $amount_delta,
        $product_options,
        $sign, $tracking,
        $current_amount,
        $new_amount,
        $product_code,
        $notify
    );

    return true;
}

/**
 * @param array $cart
 * @param int   $order_id
 *
 * @return array|bool
 */
function fn_update_order(&$cart, $order_id = 0)
{
    fn_set_hook('pre_update_order', $cart, $order_id);

    $cart['user_data'] = (isset($cart['user_data'])) ? $cart['user_data'] : array();

    $order = fn_array_merge($cart, fn_filter_order_user_data($cart['user_data']));
    unset($order['user_data']);

    $order = fn_fill_contact_info_from_address($order);

    if (empty($order['profile_id'])) {
        $order['profile_id'] = 0;
    }

    if (!empty($cart['shipping'])) {
        $order['shipping_ids'] = fn_create_set(array_keys($cart['shipping']));
    }

    if (!empty($cart['payment_surcharge'])) {
        $cart['total'] += $cart['payment_surcharge'];
        $order['total'] = $cart['total'];

        if (fn_allowed_for('MULTIVENDOR')) {
            $cart['companies'] = fn_get_products_companies($cart['products']);
            if (fn_take_payment_surcharge_from_vendor($cart['products'])) {
                $cart['companies_count'] = count($cart['companies']);
                $cart['total'] -= $cart['payment_surcharge'];
                $order['total'] = $cart['total'];
            }
        }
    }

    if (empty($order_id)) {
        if (!SiteArea::isStorefront(AREA) && isset($cart['user_data']['lang_code'])) {
            $user_lang = $cart['user_data']['lang_code'];
        }
        $ip = fn_get_ip();
        $order['ip_address'] = fn_ip_to_db($ip['host']);
        $order['timestamp']  = isset($order['timestamp']) ? fn_parse_date($order['timestamp']) : TIME;
        $order['updated_at'] = $order['timestamp'];
        $order['lang_code']  = isset($user_lang) && !empty($user_lang) ? $user_lang : CART_LANGUAGE;
        $order['company_id'] = 0;
        $order['status']     = OrderStatuses::INCOMPLETED; // incomplete by default to increase inventory
        $order_status        = $order['status'];

        if (count($order['product_groups']) > 1 && !$order['parent_order_id']) {
            $order['is_parent_order'] = 'Y';
            $order['status'] = OrderStatuses::PARENT;

        } elseif (!empty($order['product_groups'][0]['company_id'])) {
            $order['is_parent_order'] = 'N';
            $order['company_id'] = $order['product_groups'][0]['company_id'];
        }

        if (fn_allowed_for('ULTIMATE')) {
            if (Registry::get('runtime.company_id') == 0) {
                fn_set_notification('E', __('error'), __('text_select_vendor'));

                return false;

            } else {
                $order['company_id'] = Registry::get('runtime.company_id');
            }
        }

        if (defined('CART_LOCALIZATION')) {
            $order['localization_id'] = CART_LOCALIZATION;
        }

        $order['promotions'] = serialize(!empty($cart['promotions']) ? $cart['promotions'] : array());
        if (!empty($cart['promotions'])) {
            $order['promotion_ids'] = fn_create_set(array_keys($cart['promotions']));
        }

        fn_set_hook('create_order', $order);

        $order_id = db_query("INSERT INTO ?:orders ?e", $order);
    } else {
        $order['updated_at']  = isset($order['updated_at']) ? fn_parse_date($order['updated_at']) : TIME;
        unset($order['order_id'], $order['timestamp']);

        // We're editing existing order
        if (isset($cart['promotions'])) {
            $order['promotions'] = serialize($cart['promotions']);
            $order['promotion_ids'] = fn_create_set(array_keys($cart['promotions']));
        }

        $old_order = db_get_row(
            "SELECT company_id, payment_id, status, parent_order_id, is_parent_order FROM ?:orders WHERE order_id = ?i",
            $order_id
        );
        $order['status']     = $old_order['status'];
        $order['company_id'] = $old_order['company_id'];
        $order_status        = $order['status'];

        if (!isset($order['parent_order_id'])) {
            $order['parent_order_id'] = $old_order['parent_order_id'];
        }

        if (!isset($order['is_parent_order'])) {
            $order['is_parent_order'] = $old_order['is_parent_order'];
        }

        if (!empty($cart['payment_id']) && $cart['payment_id'] == $old_order['payment_id']) {
            $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);
            if (!empty($payment_info)) {
                $payment_info = unserialize(fn_decrypt_text($payment_info));
                $cart['payment_info'] = array_merge($payment_info, (!empty($cart['payment_info'])) ? $cart['payment_info'] : array());
            }
        }

        // incomplete the order to increase inventory amount.
        fn_change_order_status($order_id, OrderStatuses::INCOMPLETED, $old_order['status'], fn_get_notification_rules([], true));
        if (fn_allowed_for('MULTIVENDOR') && $order['is_parent_order'] === 'Y') {
            $order['status'] = OrderStatuses::PARENT;
        } else {
            $order['status'] = OrderStatuses::INCOMPLETED;
        }

        fn_set_hook('edit_place_order', $order_id);

        $profile_fields = db_get_hash_array("SELECT field_id, value FROM ?:profile_fields_data WHERE object_id = ?i AND object_type = 'O'", 'field_id', $order_id);
        foreach ($profile_fields as $k => $v) {
            if (!isset($cart['user_data']['fields'][$k])) {
                $cart['user_data']['fields'][$k] = $v['value'];
            }
        }

        fn_set_hook('update_order', $order, $order_id);

        db_query("UPDATE ?:orders SET ?u WHERE order_id = ?i", $order, $order_id);

        if (!empty($order['products'])) {
            db_query("DELETE FROM ?:order_details WHERE order_id = ?i", $order_id);
        }
    }

    fn_store_user_profile_fields($cart['user_data'], $order_id, ProfileDataTypes::ORDER);
    fn_create_order_details($order_id, $cart);
    fn_update_order_data($order_id, $cart);

    // Log order creation/update
    $log_action = isset($old_order) ? 'update' : 'create';
    fn_log_event('orders', $log_action, array(
        'order_id' => $order_id,
    ));

    return array($order_id, $order_status);
}

/**
 * Creates order details
 *
 * @param int   $order_id Order identifier to create details for
 * @param array $cart     Cart contents
 *
 * @throws \Tygh\Exceptions\DeveloperException
 */
function fn_create_order_details($order_id, $cart)
{
    if (!empty($cart['products'])) {
        foreach ((array) $cart['products'] as $k => $v) {
            if (empty($v['product_id'])) {
                continue;
            }
            $product_code = '';
            $extra = empty($v['extra']) ? array() : $v['extra'];
            $v['discount'] = empty($v['discount']) ? 0 : $v['discount'];

            $extra['product'] = empty($v['product']) ? fn_get_product_name($v['product_id']) : $v['product'];

            $extra['company_id'] = !empty($v['company_id']) ? $v['company_id'] : 0;

            if (isset($v['is_edp'])) {
                $extra['is_edp'] = $v['is_edp'];
            }
            if (isset($v['edp_shipping'])) {
                $extra['edp_shipping'] = $v['edp_shipping'];
            }
            if (isset($v['discount'])) {
                $extra['discount'] = $v['discount'];
            }
            if (isset($v['base_price'])) {
                $extra['base_price'] = floatval($v['base_price']);
            }
            if (!empty($v['promotions'])) {
                $extra['promotions'] = $v['promotions'];
            }
            if (!empty($v['stored_price'])) {
                $extra['stored_price'] = $v['stored_price'];
            }

            if (!empty($v['product_options'])) {
                $_options = fn_get_product_options($v['product_id']);
                if (!empty($_options)) {
                    foreach ($_options as $option_id => $option) {
                        if (!isset($v['product_options'][$option_id])) {
                            $v['product_options'][$option_id] = '';
                        }
                    }
                }

                $extra['product_options'] = $v['product_options'];
                $cart_id = fn_generate_cart_id($v['product_id'], array('product_options' => $v['product_options']), true);

                $extra['product_options_value'] = fn_get_selected_product_options_info($v['product_options']);
            } else {
                $v['product_options'] = array();
            }

            if (empty($product_code)) {
                $product_code = db_get_field("SELECT product_code FROM ?:products WHERE product_id = ?i", $v['product_id']);
            }

            // Check the cart custom files
            if (isset($extra['custom_files'])) {
                $dir_path = 'order_data/' . $order_id;

                foreach ($extra['custom_files'] as $option_id => $files) {
                    if (is_array($files)) {
                        foreach ($files as $file_id => $file) {
                            $file['path'] = 'sess_data/' . fn_basename($file['path']);

                            Storage::instance('custom_files')->copy($file['path'], $dir_path . '/' . $file['file']);

                            Storage::instance('custom_files')->delete($file['path']);
                            Storage::instance('custom_files')->delete($file['path'] . '_thumb');

                            $extra['custom_files'][$option_id][$file_id]['path'] = $dir_path . '/' . $file['file'];
                        }
                    }
                }
            }
            $order_details = array (
                'item_id' => $k,
                'order_id' => $order_id,
                'product_id' => $v['product_id'],
                'product_code' => $product_code,
                'price' => (!empty($v['stored_price']) && $v['stored_price'] == 'Y') ? $v['price'] - $v['discount'] : $v['price'],
                'amount' => $v['amount'],
                'extra' => serialize($extra),
            );

            /**
             * Modifies product order details
             *
             * @param int   $order_id      Order identifier to create details for
             * @param array $cart          Cart contents
             * @param array $order_details Ordered product details
             * @param array $extra         Product extra parameters
             */
            fn_set_hook('create_order_details', $order_id, $cart, $order_details, $extra);
            db_query("INSERT INTO ?:order_details ?e", $order_details);

            // Increase product popularity
            $popularity = array (
                'product_id' => $v['product_id'],
                'bought' => 1,
                'total' => POPULARITY_BUY,
            );

            /**
             * Modifies product popularity
             *
             * @param int   $order_id      Order identifier to create details for
             * @param array $cart          Cart contents
             * @param array $order_details Ordered product details
             * @param array $extra         Product extra parameters
             * @param array $popularity    Product popularity
             */
            fn_set_hook('create_order_details_post', $order_id, $cart, $order_details, $extra, $popularity);

            fn_update_product_popularity($popularity['product_id'], $popularity);
        }
    }

}

/**
 * @param int   $order_id
 * @param array $cart
 *
 * @return bool
 */
function fn_update_order_data($order_id, $cart)
{
    $_data = array();
    $clear_types = array();

    if (!empty($cart['product_groups'])) {

        // Save products groups
        $_data[] = array (
            'order_id' => $order_id,
            'type' => OrderDataTypes::GROUPS, //groups information
            'data' => serialize($cart['product_groups']),
        );

        // Save shipping information
        $chosen_shippings = array();
        foreach ($cart['product_groups'] as $group) {
            $group_shipping = !empty($group['chosen_shippings']) ? $group['chosen_shippings'] : array();
            $chosen_shippings = array_merge($chosen_shippings, $group_shipping);
        }

        fn_apply_stored_shipping_rates($cart, $order_id);
        $_data[] = array (
            'order_id' => $order_id,
            'type' => OrderDataTypes::SHIPPING, //shipping information
            'data' => serialize($chosen_shippings),
        );
    }

    // Save taxes
    if (!empty($cart['taxes'])) {
        $_data[] = array (
            'order_id' => $order_id,
            'type' => OrderDataTypes::TAXES, //taxes information
            'data' => serialize($cart['taxes']),
        );
    } elseif (isset($cart['taxes'])) {
        $clear_types[] = 'T';
    }

    // Save payment information
    if (isset($cart['payment_info'])) {
        $_data[] = array (
            'order_id' => $order_id,
            'type' => OrderDataTypes::PAYMENT, //payment information
            'data' => fn_encrypt_text(serialize($cart['payment_info'])),
        );
    }

    // Save coupons information
    if (!empty($cart['coupons'])) {
        $_data[] = array (
            'order_id' => $order_id,
            'type' => OrderDataTypes::COUPONS, //coupons
            'data' => serialize($cart['coupons']),
        );
    } elseif (isset($cart['coupons'])) {
        $clear_types[] = 'C';
    }

    // Save secondary currency (for order notifications from payments with feedback requests)
    $_data[] = array (
        'order_id' => $order_id,
        'type' => OrderDataTypes::CURRENCY, //secondary currency
        'data' => isset($cart['secondary_currency']) ? serialize($cart['secondary_currency']) : serialize(CART_SECONDARY_CURRENCY),
    );

    if (!empty($clear_types)) {
        db_query("DELETE FROM ?:order_data WHERE order_id = ?i AND type IN (?a)", $order_id, $clear_types);
    }

    db_query("REPLACE INTO ?:order_data ?m", $_data);

    return true;
}

/**
 * Places an order
 *
 * @param array  $cart              Array of the cart contents and user information necessary for purchase
 * @param array  $auth              Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @param string $action            Current action. Can be empty or "save"
 * @param int    $issuer_id
 * @param int    $parent_order_id
 *
 * @return array
 */
function fn_place_order(&$cart, &$auth, $action = '', $issuer_id = null, $parent_order_id = 0)
{
    if (isset($cart['parent_order_id']) && empty($parent_order_id)) {
        $parent_order_id = (int)$cart['parent_order_id'];
    }

    $allow = fn_allow_place_order($cart, $auth, $parent_order_id);

    fn_set_hook('pre_place_order', $cart, $allow, $cart['product_groups']);

    if ($allow === false) {
        fn_set_notification('E', __('error'), __('order_was_not_placed'), 'K', 'failed_order_message');
    }

    if (!isset($cart['storefront_id'])) {
        $storefront = StorefrontProvider::getStorefront();
        if ($storefront) {
            $cart['storefront_id'] = $storefront->storefront_id;
        }
    }

    if ($allow == true && !fn_cart_is_empty($cart, false)) {

        $cart['parent_order_id'] = $parent_order_id;

        // Remove unallowed chars from cc number
        if (!empty($cart['payment_info']['card_number'])) {
            $cart['payment_info']['card_number'] = str_replace(array(' ', '-'), '', $cart['payment_info']['card_number']);
        }

        if (empty($cart['order_id'])) {
            $cart['user_id']    = $auth['user_id'];
            $cart['tax_exempt'] = $auth['tax_exempt'];
            $cart['issuer_id']  = $issuer_id;
            // Create order
            [$order_id, $order_status] = fn_update_order($cart);

        } else {
            // Update order
            [$order_id, $order_status] = fn_update_order($cart, $cart['order_id']);
        }

        if (!empty($order_id)) {

            if (empty($parent_order_id)) {
                // Update stored cart
                $condition = fn_user_session_products_condition();
                db_query('UPDATE ?:user_session_products SET order_id = ?i WHERE ' . $condition, $order_id);
            }

            // If customer is not logged in, store order ids in the session
            if (empty($auth['user_id'])) {
                $auth['order_ids'][] = $order_id;
            }

            // If order total is zero, just save the order without any processing procedures
            if (
                empty((float) $cart['total'])
                && (
                    fn_allowed_for('ULTIMATE')
                    || fn_allowed_for('MULTIVENDOR')
                    && !empty($parent_order_id)
                    && empty($cart['payment_id'])
                )
            ) {
                $action = 'save';
                $order_status = OrderStatuses::PAID;
            }

            fn_set_hook('place_order', $order_id, $action, $order_status, $cart, $auth);

            $is_processor_script = false;
            if ($action != 'save') {
                [$is_processor_script, ] = fn_check_processor_script($cart['payment_id'], true);
            }

            if (!$is_processor_script && $order_status === OrderStatuses::INCOMPLETED) {
                $order_status = OrderStatuses::OPEN;
            }

            $short_order_data = fn_get_order_short_info($order_id);

            if ($is_processor_script || $order_status === OrderStatuses::PARENT) {
                $notification_rules = fn_get_notification_rules(array(), true);
            } else {
                $notification_rules = fn_get_notification_rules(array());
            }

            // Set new order status
            fn_change_order_status($order_id, $order_status, $short_order_data['status'], $notification_rules, true);

            $cart['processed_order_id'] = array();
            $cart['processed_order_id'][] = $order_id;

            if (!$parent_order_id && count($cart['product_groups']) > 1) {
                $child_orders = fn_place_suborders($order_id, $cart, $auth, $action, $issuer_id);

                array_unshift($child_orders, $order_id);
                $cart['processed_order_id'] = $child_orders;
            }

            /**
             * Executes after placing an order and setting the order status,
             * allows you to process information about the placed order.
             *
             * @param array  $cart               Cart data
             * @param array  $auth               Authentication data
             * @param string $action             Current action. Can be empty or "save"
             * @param int    $issuer_id          Issuer identifier
             * @param int    $parent_order_id    Parent order identifier
             * @param int    $order_id           Order identifier
             * @param string $order_status       Order status (one char)
             * @param array  $short_order_data   Short order data
             * @param array  $notification_rules Array with notification rules
             */
            fn_set_hook(
                'place_order_post',
                $cart,
                $auth,
                $action,
                $issuer_id,
                $parent_order_id,
                $order_id,
                $order_status,
                $short_order_data,
                $notification_rules
            );

            return array($order_id, $action != 'save');
        }
    }

    return array(false, false);
}

/**
 * @param int    $order_id  Order identifier
 * @param array  $cart      Array of the cart contents and user information necessary for purchase
 * @param array  $auth      Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @param string $action    Current action. Can be empty or "save"
 * @param int    $issuer_id Issuer identifier
 *
 * @return array
 * @internal
 */
function fn_place_suborders($order_id, $cart, &$auth, $action, $issuer_id)
{
    $order_ids = array();
    foreach ($cart['product_groups'] as $key_group => $group) {
        $suborder_cart = $cart;
        $total_products_price = 0;
        $total_shipping_cost = 0;
        $suborder_part = 0;

        /**
         * Executes before building child cart contents,
         * allows you to modify the child cart contents.
         *
         * @param int    $order_id      Order identifier
         * @param array  $cart          Cart contents
         * @param array  $auth          Authentication data
         * @param string $action        Current action. Can be empty or "save"
         * @param int    $issuer_id     Issuer identifier
         * @param array  $suborder_cart Child cart contents
         * @param array  $key_group     Child cart products group key
         * @param array  $group         Child cart products
         */
        fn_set_hook('place_suborders_pre', $order_id, $cart, $auth, $action, $issuer_id, $suborder_cart, $key_group, $group);

        foreach ($group['products'] as $product) {
            $total_products_price += ($product['price'] * $product['amount']);
        }
        foreach ($suborder_cart['products'] as $cart_id => $product) {
            if (!in_array($cart_id, array_keys($group['products']))) {
                unset($suborder_cart['products'][$cart_id]);
            }
        }

        if (!empty($suborder_cart['chosen_shipping'][$key_group])) {

            $chosen_shipping_id = $suborder_cart['chosen_shipping'][$key_group];

            if (empty($group['chosen_shippings'])) {
                $total_shipping_cost += $group['shippings'][$chosen_shipping_id]['rate'];
            } else {
                foreach ($group['chosen_shippings'] as $shipping) {
                    $total_shipping_cost += $shipping['rate'];
                }
            }

            $suborder_cart['chosen_shipping'] = array($chosen_shipping_id);

        } else {
            $suborder_cart['chosen_shipping'] = array();
        }

        $parent_order_cost = $cart['subtotal'] + $cart['shipping_cost'];
        if (!$parent_order_cost) {
            $parent_order_cost = 1;
        }

        $suborder_cost = $total_products_price + $total_shipping_cost;

        $suborder_part = $suborder_cost / $parent_order_cost;

        $suborder_cart['payment_surcharge'] = $suborder_part * $cart['payment_surcharge'];

        $suborder_cart['recalculate'] = true;
        if (empty($suborder_cart['stored_shipping'])) {
            $suborder_cart['calculate_shipping'] = true;
        }

        $suborder_cart['company_id'] = $group['company_id'];
        $suborder_cart['parent_order_id'] = $order_id;

        $suborder_cart['product_groups'] = [$group];
        fn_calculate_cart_content($suborder_cart, $auth);
        fn_calculate_payment_taxes($suborder_cart, $auth);

        fn_set_hook('place_suborders', $cart, $suborder_cart, $key_group);

        [$order_ids[],] = fn_place_order($suborder_cart, $auth, $action, $issuer_id, $order_id);
    }

    return $order_ids;
}

/**
 * @param string $processor_script
 *
 * @return string
 */
function fn_get_processor_script_path($processor_script)
{
    if (file_exists(Registry::get('config.dir.payments') . $processor_script)) {
        return Registry::get('config.dir.payments') . $processor_script;

    } else {
        // Check if add-ons have processor script
        $addons_path = Registry::get('config.dir.addons');
        $addons = Registry::get('addons');

        foreach ($addons as $addon_id => $addon) {
            if ($addon['status'] == 'A' && file_exists($addons_path . $addon_id . '/payments/' . $processor_script)) {
                return $addons_path . $addon_id . '/payments/' . $processor_script;
            }
        }
    }

    return '';
}

/**
 * Order payment processing
 *
 * @param int        $order_id           order ID
 * @param bool|array $force_notification force user notification (true - notify, false - do not notify, order status
 *                                       properties will be skipped)
 * @param array      $payment_info       payment data
 *
 * @return bool True if payment uses processor script, false otherwise
 */
function fn_start_payment($order_id, $force_notification = array(), $payment_info = array())
{
    $order_info = fn_get_order_info($order_id);

    if (!empty($order_info['payment_info']) && !empty($payment_info)) {
        $order_info['payment_info'] = $payment_info;
    }

    [$is_processor_script, $processor_data] = fn_check_processor_script($order_info['payment_id']);

    if ($is_processor_script) {
        set_time_limit(300);
        fn_mark_payment_started($order_id);

        $mode = Registry::get('runtime.mode');

        Embedded::leave();

        $pp_response = array();

        /** @var \Tygh\Location\Manager $location_manager */
        $location_manager = Tygh::$app['location'];
        // FIXME: Backward compatibility: Prefill deprecated billing address profile fields
        $order_info = $location_manager->fillEmptyLocationFields($order_info, BILLING_ADDRESS_PREFIX);
        $order_info = $location_manager->fillEmptyLocationFields($order_info, SHIPPING_ADDRESS_PREFIX);

        include(fn_get_processor_script_path($processor_data['processor_script']));

        if (empty($pp_response['is_deferred_payment'])) {
            return fn_finish_payment($order_id, $pp_response, $force_notification);
        } else {
            return true;
        }
    }

    return false;
}

/**
 * Stores information indicating that a payment was started in the database.
 *
 * @param int $order_id Order ID
 *
 * @return array Stored payment data
 */
function fn_mark_payment_started($order_id)
{
    $payment_data = array(
        'order_id' => $order_id,
        'type'     => OrderDataTypes::PAYMENT_STARTED,
        'data'     => TIME,
    );

    db_query("REPLACE INTO ?:order_data ?e", $payment_data);

    return $payment_data;
}

/**
 * Finish order paymnent
 *
 * @param int        $order_id           order ID
 * @param array      $pp_response        payment response
 * @param bool|array $force_notification force user notification (true - notify, false - do not notify, order status
 *                                       properties will be skipped)
 *
 * @return bool Always true
 */
function fn_finish_payment($order_id, $pp_response, $force_notification = array())
{
    // Change order status
    $valid_id = db_get_field("SELECT order_id FROM ?:order_data WHERE order_id = ?i AND type = 'S'", $order_id);

    if (!empty($valid_id)) {
        fn_update_order_payment_info($order_id, $pp_response);

        if (isset($pp_response['order_status']) && $pp_response['order_status'] == 'N' && !empty(Tygh::$app['session']['cart']['placement_action']) && Tygh::$app['session']['cart']['placement_action'] == 'repay') {
            $pp_response['order_status'] = 'I';
        }

        fn_set_hook('finish_payment', $order_id, $pp_response, $force_notification);

        if (!empty($pp_response['order_status'])) {
            fn_change_order_status($order_id, $pp_response['order_status'], '', $force_notification);
        }

        db_query("DELETE FROM ?:order_data WHERE order_id = ?i AND type = 'S'", $order_id);
    }

    return true;
}


/**
 * Stores cart content in the customer's profile
 *
 * @param array  $cart      Cart contents
 * @param int    $user_id   User identifier
 * @param string $type      Cart type
 * @param string $user_type User type
 *
 * @return bool True
 *
 * @phpcsSuppress Squiz.Commenting.FunctionComment.TypeHintMissing
 */
function fn_save_cart_content(&$cart, $user_id, $type = 'C', $user_type = 'R')
{
    /**
     * Actions before storing cart content in the customer's profile
     *
     * @param array  $cart      Cart contents
     * @param int    $user_id   User identifier
     * @param string $type      Cart type
     * @param string $user_type User type
     */
    fn_set_hook('save_cart_content_pre', $cart, $user_id, $type, $user_type);

    if (empty($user_id)) {
        if (fn_get_session_data('cu_id')) {
            $user_id = fn_get_session_data('cu_id');
        } else {
            $user_id = fn_crc32(uniqid(TIME));
            fn_set_session_data('cu_id', $user_id, COOKIE_ALIVE_TIME);
        }
        $user_type = 'U';
    }

    if (!empty($user_id)) {
        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = Tygh::$app['storefront'];

        $condition = fn_user_session_products_condition([
            'user_id'             => $user_id,
            'type'                => $type,
            'user_type'           => $user_type,
            'get_session_user_id' => false,
            'get_session_id'      => false,
            'storefront_id'       => $storefront->storefront_id,
        ]);

        db_query('DELETE FROM ?:user_session_products WHERE 1=1 AND ?p', $condition);

        $product_groups = empty($cart['product_groups']) ? [] : (array) $cart['product_groups'];
        $products = empty($cart['products']) ?  [] : (array) $cart['products'];

        if ($products) {
            $product_groups_map = [];

            foreach ($product_groups as $group_id => $product_group) {
                foreach ($product_group['products'] as $key => $product) {
                    $product_groups_map[$key] = $group_id;
                }
            }

            if (!empty($cart['user_data'])) {
                $cart['user_data']['firstname'] = !empty($cart['user_data']['firstname']) ? $cart['user_data']['firstname'] : null;
                $cart['user_data']['lastname'] = !empty($cart['user_data']['lastname']) ? $cart['user_data']['lastname'] : null;
                $cart['user_data']['phone'] = !empty($cart['user_data']['phone']) ? $cart['user_data']['phone'] : null;
                $user_data = fn_fill_contact_info_from_address($cart['user_data']);
                $user_data['profile_id'] = !empty($cart['profile_id']) ? $cart['profile_id'] : null;
            }

            $ip = fn_get_ip();
            $ip_address = fn_ip_to_db($ip['host']);
            $company_id = fn_allowed_for('ULTIMATE') ? Registry::get('runtime.company_id') : null;
            $session_id = Tygh::$app['session']->getID();
            $position = 0;

            foreach ($products as $cart_id => $product) {
                if (!empty($cart['user_data'])) {
                    $product['firstname'] = isset($user_data['firstname']) ? $user_data['firstname'] : null;
                    $product['lastname'] = isset($user_data['lastname']) ? $user_data['lastname'] : null;
                    $product['phone'] = isset($user_data['phone']) ? $user_data['phone'] : null;

                    $product['email'] = (!empty($cart['user_data']['email'])) ? $cart['user_data']['email'] : '';
                    $product['user_data'] = !empty($cart['user_data']) ? $cart['user_data'] : [];
                    unset($product['user_data']['password']);

                    $product['group_id'] = isset($product_groups_map[$cart_id]) ? $product_groups_map[$cart_id] : null;
                    $product['chosen_shipping'] = $product['group_id'] && !empty($cart['chosen_shipping'][$product['group_id']])
                        ? $cart['chosen_shipping'][$product['group_id']]
                        : null;
                }

                $product_data = [
                    'user_id'    => $user_id,
                    'timestamp'  => !empty($product['timestamp']) ? $product['timestamp'] : TIME,
                    'type'       => $type,
                    'user_type'  => $user_type,
                    'item_id'    => $cart_id,
                    'item_type'  => 'P',
                    'product_id' => $product['product_id'],
                    'amount'     => !empty($product['amount']) ? $product['amount'] : 1,
                    'price'      => !empty($product['price']) ? $product['price'] : 0,
                    'extra'      => serialize($product),
                    'session_id' => $session_id,
                    'ip_address' => $ip_address,
                    'order_id'   => !empty($product['order_id']) ? $product['order_id'] : '',
                    'position'   => $position,
                ];

                //FIXME This is a workaround. See @1-17222 for details.
                if (empty($product_data['order_id'])) {
                    unset($product_data['order_id']);
                }

                if ($company_id !== null) {
                    $product_data['company_id'] = $company_id;
                }

                $product_data['storefront_id'] = $storefront->storefront_id;

                /**
                 * Executes when saving cart content, right before saving product data,
                 * allows you to modify the stored data.
                 *
                 * @param array  $cart         Cart contents
                 * @param int    $user_id      User identifier
                 * @param string $type         Cart type
                 * @param string $user_type    User type
                 * @param array  $product_data Product data
                 */
                fn_set_hook('save_cart_content_before_save', $cart, $user_id, $type, $user_type, $product_data);

                /** @var \Tygh\Database\Connection $db */
                $db = Tygh::$app['db'];
                $db->replaceInto('user_session_products', $product_data);
                $position += 10;
            }
        }

        /**
         * Deprecated: This hook will be removed in version 5.x.x.. Use save_cart_content_post instead.
         */
        fn_set_hook('save_cart', $cart, $user_id, $type);

        /**
         * Actions after storing cart content in the customer's profile
         *
         * @param array  $cart      Cart contents
         * @param int    $user_id   User identifier
         * @param string $type      Cart type
         * @param string $user_type User type
         */
        fn_set_hook('save_cart_content_post', $cart, $user_id, $type, $user_type);
    }

    return true;
}

/**
 * Extract cart content from the customer's profile.
 *
 * @param array  $cart      Cart to extract contents to
 * @param int    $user_id   User ID
 * @param string $type      Cart type: C for cart, W for wishlist
 * @param string $user_type User type: R for registered, U for guest
 * @param string $lang_code Two-letter language code
 *
 * @return void
 *
 * @phpcsSuppress Squiz.Commenting.FunctionComment.TypeHintMissing
 */
function fn_extract_cart_content(&$cart, $user_id, $type = 'C', $user_type = 'R', $lang_code = CART_LANGUAGE)
{
    $auth = & Tygh::$app['session']['auth'];

    // Restore cart content
    if (!empty($user_id)) {
        $item_types = fn_get_cart_content_item_types('X');

        $condition = db_quote('user_id = ?i AND type = ?s AND user_type = ?s AND item_type IN (?a)', $user_id, $type, $user_type, $item_types);

        if (fn_allowed_for('MULTIVENDOR')) {

            /** @var \Tygh\Storefront\Storefront $storefront */
            $storefront = Tygh::$app['storefront'];
            $storefront_id = $storefront->storefront_id;

            $condition .= db_quote(' AND storefront_id = ?i', $storefront_id);
        }

        fn_set_hook('pre_extract_cart', $cart, $condition, $item_types, $user_id, $type, $user_type);

        $_prods = db_get_hash_array('SELECT * FROM ?:user_session_products WHERE ?p ORDER BY position', 'item_id', $condition);
        if (!empty($_prods) && is_array($_prods)) {
            $cart['products'] = empty($cart['products']) ? [] : $cart['products'];

            // For authorized users, the data in the database takes precedence over the data in the session,
            // except for the situation when the user has just logged in,
            // in which case the data should be merged.
            // For just authorized users user_data.user_id is temporarily empty.
            if (!empty($cart['user_data']['user_id'])) {
                $cart['products'] = [];
            }

            foreach ($_prods as $_item_id => $_prod) {
                if (!empty($cart['products'][$_item_id])) {
                    $product = $cart['products'][$_item_id];
                } else {
                    $_prod_extra = unserialize($_prod['extra']);
                    unset($_prod['extra']);
                    $product = fn_array_merge($_prod, $_prod_extra, true);
                }

                $cart['products'][$_item_id] = $product;
            }
        } elseif (empty($_prods) && !empty($cart['user_data']['user_id'])) {
            $cart['products'] = [];
        }
    }

    /**
     * Executes when extracting cart content from the customer's profile, allows you to modify the extract data.
     *
     * @param array  $cart                  Cart to extract contents to
     * @param int    $user_id               User ID
     * @param string $type                  Cart type: C for cart, W for wishlist
     * @param string $user_type             User type: R for registered, U for guest
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    fn_set_hook('extract_cart', $cart, $user_id, $type, $user_type);

    if ($type !== 'C') {
        return;
    }

    $cart['change_cart_products'] = true;
    fn_calculate_cart_content($cart, $auth, 'S', false, 'I', true, $lang_code);
}

/**
 * Gets cart content item types
 *
 * @param string $action
 * V - for View mode
 * X - for eXtract mode
 *
 * @return array
 */
function fn_get_cart_content_item_types($action = 'V')
{
    $item_types = array('P');

    fn_set_hook('get_cart_item_types', $item_types, $action);

    return $item_types;
}

/**
 * Generate title string for order details page
 *
 * @param int $order_id order identifier
 *
 * @return string
 */
function fn_get_order_name($order_id)
{
    $total = db_get_field("SELECT total FROM ?:orders WHERE order_id = ?i", $order_id);
    if ($total == '') {
        return false;
    }

    /** @var \Tygh\Tools\Formatter $formatter */
    $formatter = Tygh::$app['formatter'];
    $result = $formatter->asPrice($total, CART_PRIMARY_CURRENCY, true, true);

    return $order_id . ' - ' . $result;
}

/**
 * Gets order statuses which decreasing the inventory
 *
 * @return array Available decreasing the inventory statuses
 */
function fn_get_order_paid_statuses()
{
    $paid_statuses = fn_get_status_by_type_and_param(STATUSES_ORDER, ['inventory' => 'D']);

    /**
     *  Gets order statuses which decreasing the inventory (at the end of fn_get_order_paid_statuses())
     *
     * @param array $paid_statuses List of order decreasing the inventory statuses
     */
    fn_set_hook('get_order_paid_statuses_post', $paid_statuses);

    return $paid_statuses;
}

/**
 * Gets orders status
 *
 * @param array $params
 *        order_id - Order identifier
 *        status - Orders status
 *
 * @return array Orders status
 */
function fn_get_orders_status($params = [])
{
    $condition = '';

    if (!empty($params['order_id'])) {
        $condition .= db_quote(' AND order_id = ?i', $params['order_id']);
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(' AND status = ?s', $params['status']);
    }

    $statuses = db_get_array('SELECT order_id, status FROM ?:orders WHERE 1 ?p', $condition);

    return $statuses;
}

/**
 * Converts price from once currency to other
 *
 * @param float  $price         value to be converted
 * @param string $currency_from in what currency did we get the value
 * @param string $currency_to   in what currency should we send the result
 *
 * @return float converted value
 */
function fn_format_price_by_currency($price, $currency_from = CART_PRIMARY_CURRENCY, $currency_to = CART_SECONDARY_CURRENCY)
{
    $currencies = Registry::get('currencies');
    $currency_from = !empty($currencies[$currency_from]) ? $currencies[$currency_from] : $currencies[CART_PRIMARY_CURRENCY];
    $currency_to = !empty($currencies[$currency_to]) ? $currencies[$currency_to] : $currencies[CART_SECONDARY_CURRENCY];

    $result = fn_format_price($price / ($currency_to['coefficient'] / $currency_from['coefficient']), $currency_to['currency_code']);

    /**
     * Update converted value
     *
     * @param float  $price         value to be converted
     * @param string $currency_from in what currency did we get the value
     * @param string $currency_to   in what currency should we send the result
     * @param float  $result        converted value
     */
    fn_set_hook('format_price_by_currency_post', $price, $currency_from, $currency_to, $result);

    return $result;
}

/**
 * Gets order data
 *
 * @param int                    $order_id           Order ID
 * @param bool                   $native_language    If set to true, order information will be retrieved on the language the order was
 *                                    placed on
 * @param bool                   $format_info        If set to true, product options will be formatted
 * @param bool                   $get_edp_files      If set to true, retrieves info about downloadable files
 * @param bool                   $skip_static_values If set to true, product option modifiers won't be retrieved from order
 * @param string                 $lang_code          Language code
 * @param array<string|int|bool> $extra_params       Additional parameters
 *
 * @return array|false order data
 */
function fn_get_order_info(
    $order_id,
    $native_language = false,
    $format_info = true,
    $get_edp_files = false,
    $skip_static_values = false,
    $lang_code = CART_LANGUAGE,
    array $extra_params = []
)
{
    if (!empty($order_id)) {

        $condition = fn_get_company_condition('?:orders.company_id');
        $order = db_get_row("SELECT * FROM ?:orders WHERE ?:orders.order_id = ?i $condition", $order_id);

        if (!empty($order)) {
            $lang_code = ($native_language == true) ? $order['lang_code'] : $lang_code;

            if (isset($order['ip_address'])) {
                $order['ip_address'] = fn_ip_from_db($order['ip_address']);
            }

            $order['discount'] = floatval($order['discount']);
            $order['subtotal_discount'] = floatval($order['subtotal_discount']);
            $order['payment_surcharge'] = floatval($order['payment_surcharge']);
            $order['payment_method'] = fn_get_payment_method_data($order['payment_id'], $lang_code);

            // Get additional profile fields
            $additional_fields = db_get_hash_single_array(
                "SELECT field_id, value FROM ?:profile_fields_data "
                . "WHERE object_id = ?i AND object_type = 'O'",
                array('field_id', 'value'), $order_id
            );
            $order['fields'] = $additional_fields;

            $order['products'] = db_get_hash_array(
                "SELECT ?:order_details.*, ?:product_descriptions.product, ?:products.status as product_status FROM ?:order_details "
                . "LEFT JOIN ?:product_descriptions ON ?:order_details.product_id = ?:product_descriptions.product_id AND ?:product_descriptions.lang_code = ?s "
                . "LEFT JOIN ?:products ON ?:order_details.product_id = ?:products.product_id "
                . "WHERE ?:order_details.order_id = ?i ORDER BY ?:product_descriptions.product",
                'item_id', $lang_code, $order_id
            );

            $order['promotions'] = unserialize($order['promotions']);
            if (!empty($order['promotions'])) { // collect additional data
                $params = array (
                    'promotion_id' => array_keys($order['promotions']),
                );
                [$promotions] = fn_get_promotions($params);
                foreach ($promotions as $pr_id => $p) {
                    $order['promotions'][$pr_id]['name'] = $p['name'];
                    $order['promotions'][$pr_id]['short_description'] = $p['short_description'];
                }
            }

            // Get additional data
            $additional_data = db_get_hash_single_array("SELECT type, data FROM ?:order_data WHERE order_id = ?i", array('type', 'data'), $order_id);

            $order['taxes'] = array();
            $order['tax_subtotal'] = 0;
            $order['display_shipping_cost'] = $order['shipping_cost'];

            // Replace country, state and title values with their descriptions
            $order_company_id = isset($order['company_id']) ? $order['company_id'] : ''; // company_id will be rewritten by user field, so need to save it.
            fn_add_user_data_descriptions($order, $lang_code);
            $order['company_id'] = $order_company_id;

            $order['need_shipping'] = false;
            $deps = array();

            // Get shipping information
            if (!empty($additional_data[OrderDataTypes::SHIPPING])) {
                $order['shipping'] = unserialize($additional_data[OrderDataTypes::SHIPPING]);

                foreach ($order['shipping'] as $key => $v) {
                    $shipping_id = isset($v['shipping_id']) ? $v['shipping_id'] : 0;
                    $shipping_name = fn_get_shipping_name($shipping_id, $lang_code);
                    if ($shipping_name) {
                        $order['shipping'][$key]['shipping'] = $shipping_name;
                    }
                }
            }

            // Get shipments common information
            $order['shipment_ids'] = db_get_fields(
                'SELECT sh.shipment_id'
                . ' FROM ?:shipments AS sh'
                . ' LEFT JOIN ?:shipment_items AS s_items ON (sh.shipment_id = s_items.shipment_id)'
                . ' WHERE s_items.order_id = ?i GROUP BY s_items.shipment_id',
                $order_id
            );

            $_products = db_get_array('SELECT item_id, SUM(amount) AS amount FROM ?:shipment_items WHERE order_id = ?i GROUP BY item_id', $order_id);
            $shipped_products = [];

            if (!empty($_products)) {
                foreach ($_products as $_product) {
                    $shipped_products[$_product['item_id']] = $_product['amount'];
                }
            }
            unset($_products);

            foreach ($order['products'] as $k => $v) {
                //Check for product existance
                if (empty($v['product'])) {
                    $order['products'][$k]['deleted_product'] = true;
                } else {
                    $order['products'][$k]['deleted_product'] = false;
                }

                $order['products'][$k]['discount'] = 0;

                $v['extra'] = @unserialize($v['extra']);
                if ($order['products'][$k]['deleted_product'] == true && !empty($v['extra']['product'])) {
                    $order['products'][$k]['product'] = $v['extra']['product'];
                } else {
                    $order['products'][$k]['product'] = fn_get_product_name($v['product_id'], $lang_code);
                }

                $order['products'][$k]['company_id'] = empty($v['extra']['company_id']) ? 0 : $v['extra']['company_id'];

                if (!empty($v['extra']['discount']) && floatval($v['extra']['discount'])) {
                    $order['products'][$k]['discount'] = $v['extra']['discount'];
                    $order['use_discount'] = true;
                }

                if (!empty($v['extra']['promotions'])) {
                    $order['products'][$k]['promotions'] = $v['extra']['promotions'];
                }

                if (isset($v['extra']['base_price'])) {
                    $order['products'][$k]['base_price'] = floatval($v['extra']['base_price']);
                } else {
                    $order['products'][$k]['base_price'] = $v['price'];
                }
                $order['products'][$k]['original_price'] = $order['products'][$k]['base_price'];

                // Form hash key for this product
                $order['products'][$k]['cart_id'] = $v['item_id'];
                $deps['P_'.$order['products'][$k]['cart_id']] = $k;

                // Unserialize and collect product options information
                if (!empty($v['extra']['product_options'])) {
                    if ($format_info == true) {

                        $stored_options = $v['extra']['product_options_value'];
                        $source_options = fn_get_selected_product_options_info($v['extra']['product_options'], $lang_code);
                        $option_id_key  = 'option_id';

                        $order['products'][$k]['product_options'] = fn_array_merge(
                            fn_array_combine(array_column($stored_options, $option_id_key), $stored_options),
                            fn_array_combine(array_column($source_options, $option_id_key), $source_options),
                            true
                        );

                    }

                    $product_options_value = ($skip_static_values == false && !empty($v['extra']['product_options_value'])) ? $v['extra']['product_options_value'] : array();

                    if (empty($v['extra']['stored_price']) || (!empty($v['extra']['stored_price']) && $v['extra']['stored_price'] != 'Y')) { // apply modifiers if this is not the custom price
                        $order['products'][$k]['original_price'] = fn_apply_options_modifiers($v['extra']['product_options'], $order['products'][$k]['base_price'], 'P', $product_options_value, array('product_data' => $v));
                    }
                }

                $order['products'][$k]['extra'] = $v['extra'];
                $order['products'][$k]['tax_value'] = 0;
                $order['products'][$k]['display_subtotal'] = $order['products'][$k]['subtotal'] = ($v['price'] * $v['amount']);

                // Get information about edp
                if ($get_edp_files == true && $order['products'][$k]['extra']['is_edp'] == 'Y') {
                    $order['products'][$k]['files'] = db_get_array(
                        "SELECT ?:product_files.file_id, ?:product_files.activation_type, ?:product_files.max_downloads, "
                        . "?:product_file_descriptions.file_name, ?:product_file_ekeys.active, ?:product_file_ekeys.downloads, "
                        . "?:product_file_ekeys.ekey, ?:product_file_ekeys.ttl FROM ?:product_files "
                        . "LEFT JOIN ?:product_file_descriptions ON ?:product_file_descriptions.file_id = ?:product_files.file_id "
                        . "AND ?:product_file_descriptions.lang_code = ?s "
                        . "LEFT JOIN ?:product_file_ekeys ON ?:product_file_ekeys.file_id = ?:product_files.file_id "
                        . "AND ?:product_file_ekeys.order_id = ?i WHERE ?:product_files.product_id = ?i AND ?:product_files.status = ?s",
                        $lang_code, $order_id, $v['product_id'], 'A'
                    );
                }

                // Get shipments information
                // If current edition is FREE, we still need to check shipments accessibility (need to display promotion link)
                if (isset($shipped_products[$k])) {
                    $order['products'][$k]['shipped_amount'] = $shipped_products[$k];
                    $order['products'][$k]['shipment_amount'] = $v['amount'] - $shipped_products[$k];

                } else {
                    $order['products'][$k]['shipped_amount'] = 0;
                    $order['products'][$k]['shipment_amount'] = $v['amount'];
                }

                if ($order['products'][$k]['shipped_amount'] < $order['products'][$k]['amount']) {
                    if (!empty($order['shipping'])) {
                        $group_key = empty($v['extra']['group_key']) ? 0 : $v['extra']['group_key'];
                        $order['shipping'][$group_key]['need_shipment'] = true;
                    } else {
                        $order['need_shipment'] = true;
                    }
                }

                // Check if the order needs the shipping method
                if (!($v['extra']['is_edp'] == 'Y' && (!isset($v['extra']['edp_shipping']) || $v['extra']['edp_shipping'] != 'Y'))) {
                    $order['need_shipping'] = true;
                }

                // Adds flag that defines if product page is available
                $order['products'][$k]['is_accessible'] = fn_is_accessible_product($v);

                $order['products'][$k]['product_url'] = fn_url('products.view?product_id=' . $v['product_id'] . '&storefront_id=' . $order['storefront_id'], SiteArea::STOREFRONT, 'current', $lang_code);

                fn_set_hook('get_order_items_info_post', $order, $v, $k);
            }

            // Unserialize and collect taxes information
            if (!empty($additional_data[OrderDataTypes::TAXES])) {
                $order['taxes'] = unserialize($additional_data[OrderDataTypes::TAXES]);
                $order['shipping_tax_value'] = 0.0;
                if (is_array($order['taxes'])) {
                    $show_prices_with_taxes = YesNo::toBool(Registry::get('settings.Appearance.cart_prices_w_taxes'));
                    $default_tax_calculation_method = Registry::get('settings.Checkout.tax_calculation');
                    foreach ($order['taxes'] as $tax_id => $tax_data) {
                        $actual_tax_name = fn_get_tax_name($tax_id, $lang_code);
                        $order['taxes'][$tax_id]['description'] = empty($actual_tax_name) ? $tax_data['description'] : $actual_tax_name;
                        $price_not_includes_tax = !YesNo::toBool($tax_data['price_includes_tax']);

                        if ($price_not_includes_tax) {
                            $order['tax_subtotal'] += $tax_data['tax_subtotal'];
                        }

                        // FIXME: Backward compatibility for orders that have no calculation type stored
                        $tax_calculation_method = isset($tax_data['calculation_type'])
                            ? $tax_data['calculation_type']
                            : $default_tax_calculation_method;
                        if ($tax_calculation_method !== 'unit_price') {
                            continue;
                        }

                        foreach ($tax_data['applies'] as $_id => $value) {
                            if (preg_match('/^P_/', $_id) && isset($deps[$_id])) {
                                $order['products'][$deps[$_id]]['tax_value'] += $value;
                                if ($price_not_includes_tax) {
                                    $order['products'][$deps[$_id]]['display_subtotal'] += $show_prices_with_taxes ? $value : 0;
                                }
                            }
                            //phpcs:ignore
                            if (preg_match('/^S_/', $_id)) {
                                $order['shipping_tax_value'] += $value;
                                if ($price_not_includes_tax) {
                                    $order['display_shipping_cost'] += $show_prices_with_taxes ? $value : 0;
                                }
                            }
                        }
                    }
                } else {
                    $order['taxes'] = [];
                }
            }

            if (!empty($additional_data[OrderDataTypes::COUPONS])) {
                $order['coupons'] = unserialize($additional_data[OrderDataTypes::COUPONS]);
            }

            if (!empty($additional_data[OrderDataTypes::CURRENCY])) {
                $order['secondary_currency'] = unserialize($additional_data[OrderDataTypes::CURRENCY]);
            }

            if (!empty($order['issuer_id'])) {
                $order['issuer_data'] = fn_get_user_short_info($order['issuer_id']);
            }

            if (!empty($order['user_id'])) {
                $user_short_info = fn_get_user_short_info($order['user_id']);
                $order['user_type'] = $user_short_info['user_type'];
            } else {
                $order['user_type'] = UserTypes::CUSTOMER;
            }

            // Recalculate subtotal
            $order['subtotal'] = $order['display_subtotal'] = 0;
            foreach ($order['products'] as $v) {
                $order['subtotal'] += $v['subtotal'];
                $order['display_subtotal'] += $v['display_subtotal'];
            }

            // Unserialize and collect payment information
            if (!empty($additional_data[OrderDataTypes::PAYMENT])) {
                $order['payment_info'] = unserialize(fn_decrypt_text($additional_data[OrderDataTypes::PAYMENT]));
            }

            if (empty($order['payment_info']) || !is_array($order['payment_info'])) {
                $order['payment_info'] = array();
            }

            // Get product groups
            if (!empty($additional_data[OrderDataTypes::GROUPS])) {
                $order['product_groups'] = unserialize($additional_data[OrderDataTypes::GROUPS]);
                foreach ($order['product_groups'] as &$product_group) {
                    foreach ($product_group['products'] as &$product) {
                        unset($product['user_data']['password']);
                    }

                    if (!fn_allowed_for('MULTIVENDOR') || empty($extra_params['use_i18n_company_fields'])) {
                        continue;
                    }
                    $_company_id = isset($product_group['company_id']) ? $product_group['company_id'] : 0;
                    $company_name = fn_get_company_name($_company_id, '', $lang_code, ['use_i18n_fields' => true]);
                    if ($company_name) {
                        $product_group['name'] = $company_name;
                    }
                }
                unset($product_group, $product);
            }

            $order['doc_ids'] = db_get_hash_single_array("SELECT type, doc_id FROM ?:order_docs WHERE order_id = ?i", array('type', 'doc_id'), $order_id);
        }

        fn_set_hook('get_order_info', $order, $additional_data);

        return $order;
    }

    return false;
}

/**
 * Check that the first paid order
 *
 * @param array $order Order data
 */
function fn_check_first_order(&$order)
{
    // phpcs:ignore
    if (SiteArea::isAdmin(AREA) && !fn_get_storage_data('first_order')) {
        $status = !empty($order['status']) ? $order['status'] : '';

        if (in_array($status, fn_get_settled_order_statuses())) {
            $order['first_order'] = true;
            fn_set_storage_data('first_order', true);
            Tygh::$app['view']->assign('mode','notification');
            fn_set_notification(NotificationSeverity::SUCCESS, __('well_done'), Tygh::$app['view']->fetch('common/share.tpl'));
        }
    }
}

/**
 * Checks if product is currently accessible for viewing
 *
 * @param array $product Product data
 *
 * @return boolean Flag that defines if product is accessible
 */
function fn_is_accessible_product($product)
{
    $result = false;

    $status = db_get_field('SELECT status FROM ?:products WHERE product_id = ?i', $product['product_id']);
    if (!empty($status) && $status != "D") {
        $result = true;
    }

    /**
     * Changes result of product accessibility checking
     *
     * @param array   $product Product data
     * @param boolean $result  Flag that defines if product is accessible
     */
    fn_set_hook('is_accessible_product_post', $product, $result);

    return $result;
}

/**
 * Gets short order info
 *
 * @param int $order_id
 *
 * @return array|bool
 */
function fn_get_order_short_info($order_id)
{
    if (!empty($order_id)) {
        $order = db_get_row("SELECT total, status, issuer_id, firstname, lastname, timestamp, is_parent_order, storefront_id FROM ?:orders WHERE order_id = ?i", $order_id);

        return $order;
    }

    return false;
}

/**
 * Get suborders short info
 *
 * @param int $parent_order_id
 *
 * @return array
 */
function fn_get_suborders_info($parent_order_id)
{
    $orders = array();

    if ($parent_order_id) {
        $orders = db_get_array('SELECT order_id, total, status, issuer_id, firstname, lastname, timestamp, company_id FROM ?:orders WHERE parent_order_id = ?i', $parent_order_id);
    }

    return $orders;
}

/**
 * Changes order status.
 *
 * @param int     $order_id           Order identifier
 * @param string  $status_to          New order status (one char)
 * @param string  $status_from        Old order status (one char)
 * @param array   $force_notification Array with notification rules
 * @param boolean $place_order        True, if this function have been called inside of fn_place_order function
 *
 * @return boolean
 */
function fn_change_order_status($order_id, $status_to, $status_from = '', $force_notification = array(), $place_order = false)
{
    $order_info = fn_get_order_info($order_id, true);

    if (!$order_info) {
        return false;
    }

    if (defined('CART_LOCALIZATION') && $order_info['localization_id'] && CART_LOCALIZATION != $order_info['localization_id']) {
        Tygh::$app['view']->assign('localization', fn_get_localization_data(CART_LOCALIZATION));
    }

    $order_statuses = fn_get_statuses(STATUSES_ORDER, array(), true, false, ($order_info['lang_code'] ? $order_info['lang_code'] : CART_LANGUAGE), $order_info['company_id']);

    if (empty($status_from)) {
        $status_from = $order_info['status'];
    }
    if (empty($order_info) || empty($status_to) || $status_from == $status_to) {
        return false;
    }

    if (fn_allowed_for('MULTIVENDOR') && YesNo::toBool($order_info['is_parent_order'])) {
        $child_orders = db_get_hash_single_array(
            'SELECT order_id, status' .
            ' FROM ?:orders' .
            ' WHERE parent_order_id = ?i',
            ['order_id', 'status'],
            $order_id
        );
        $is_order_status_change_successful = $is_child_order_status_change_successful = true;
        foreach ($child_orders as $child_order_id => $child_status_from) {
            $change_child_status = true;
            $child_status_to = $status_to;

            /**
             * Executes before changing the order's child order status,
             * allowing to modify the child order status or prevent the status from being changed.
             *
             * @param int    $order_id            Parent order identifier
             * @param string $status_to           New parent order status (one char)
             * @param string $status_from         Old parent order status (one char)
             * @param array  $force_notification  Array with notification rules
             * @param bool   $place_order         True, if this function have been called inside of fn_place_order function.
             * @param int    $child_order_id      Child order identifier
             * @param string $child_status_to     New child order status (one char)
             * @param bool   $change_child_status If true, the child order status will be changed
             * @param string $child_status_from   Old child order status (one char)
             */
            fn_set_hook(
                'change_order_status_child_order',
                $order_id,
                $status_to,
                $status_from,
                $force_notification,
                $place_order,
                $child_order_id,
                $child_status_to,
                $change_child_status,
                $child_status_from
            );

            if (!$change_child_status) {
                continue;
            }

            if ($child_status_from !== $child_status_to) {
                $is_child_order_status_change_successful = fn_change_order_status(
                    $child_order_id,
                    $child_status_to,
                    $child_status_from,
                    $force_notification,
                    $place_order
                );
            }
            $is_order_status_change_successful = $is_order_status_change_successful && $is_child_order_status_change_successful;
        }

        return $is_order_status_change_successful;
    }

    $_updated_ids = array();
    $_error = false;

    foreach ($order_info['products'] as $k => $v) {

        // Generate ekey if EDP is ordered
        if (!empty($v['extra']['is_edp']) && $v['extra']['is_edp'] == 'Y') {
            continue; // don't track inventory
        }

        // Update product amount if inventory tracking is enabled
        if (Registry::get('settings.General.gloabl_inventory_tracking') === YesNo::NO) {
            continue;
        }

        /**
         * Executes when changing order status before changing a product stock balance in the database.
         *
         * @param int     $order_id            Parent order identifier
         * @param string  $status_to           New parent order status (one char)
         * @param string  $status_from         Old parent order status (one char)
         * @param array   $force_notification  Array with notification rules
         * @param bool    $place_order         True, if this function have been called inside of fn_place_order function.
         * @param array   $order_info          Order information
         * @param string  $k                   Product cart ID
         * @param array   $v                   Cart product data
         */
        fn_set_hook(
            'change_order_status_before_update_product_amount',
            $order_id,
            $status_to,
            $status_from,
            $force_notification,
            $place_order,
            $order_info,
            $k,
            $v
        );

        if ($order_statuses[$status_to]['params']['inventory'] === 'D' && $order_statuses[$status_from]['params']['inventory'] === 'I') {
            // decrease amount
            if (!fn_update_product_amount($v['product_id'], $v['amount'], @$v['extra']['product_options'], '-', $force_notification !== false, $order_info)) {
                $status_to = OrderStatuses::BACKORDERED;

                $_error = true;
                fn_set_notification('W', __('warning'), __('low_stock_subj', [
                    '[product]' => fn_get_product_name($v['product_id']) . ' #' . $v['product_id'],
                ]));

                break;
            }

            $_updated_ids[] = $k;
        } elseif ($order_statuses[$status_to]['params']['inventory'] === 'I' && $order_statuses[$status_from]['params']['inventory'] === 'D') {
            // increase amount
            fn_update_product_amount(
                $v['product_id'],
                $v['amount'],
                @$v['extra']['product_options'],
                '+',
                $force_notification !== false,
                $order_info
            );
        }
    }

    if ($_error) {
        if (!empty($_updated_ids)) {
            foreach ($_updated_ids as $id) {
                // increase amount
                fn_update_product_amount(
                    $order_info['products'][$id]['product_id'],
                    $order_info['products'][$id]['amount'],
                    @$order_info['products'][$id]['extra']['product_options'],
                    '+',
                    $force_notification !== false,
                    $order_info
                );
            }
            unset($_updated_ids);
        }

        if ($status_from == $status_to) {
            return false;
        }
    }

    fn_set_hook('change_order_status', $status_to, $status_from, $order_info, $force_notification, $order_statuses, $place_order);

    if ($status_from == $status_to) {
        if (!empty($_updated_ids)) {
            foreach ($_updated_ids as $id) {
                // increase amount
                fn_update_product_amount(
                    $order_info['products'][$id]['product_id'],
                    $order_info['products'][$id]['amount'],
                    @$order_info['products'][$id]['extra']['product_options'],
                    '+',
                    $force_notification !== false,
                    $order_info
                );
            }
            unset($_updated_ids);
        }

        return false;
    }

    fn_promotion_post_processing($status_to, $status_from, $order_info, $force_notification);

    // Log order status change
    fn_log_event('orders', 'status', array (
        'order_id' => $order_id,
        'status_from' => $status_from,
        'status_to' => $status_to,
    ));

    if (!empty($order_statuses[$status_to]['params']['appearance_type']) && ($order_statuses[$status_to]['params']['appearance_type'] == 'I' || $order_statuses[$status_to]['params']['appearance_type'] == 'C') && !db_get_field("SELECT doc_id FROM ?:order_docs WHERE type = ?s AND order_id = ?i", $order_statuses[$status_to]['params']['appearance_type'], $order_id)) {
        $_data = array (
            'order_id' => $order_id,
            'type' => $order_statuses[$status_to]['params']['appearance_type'],
        );
        $order_info['doc_ids'][$order_statuses[$status_to]['params']['appearance_type']] = db_query("INSERT INTO ?:order_docs ?e", $_data);
    }

    // Check if we need to remove CC info
    if (!empty($order_statuses[$status_to]['params']['remove_cc_info']) && $order_statuses[$status_to]['params']['remove_cc_info'] == 'Y' && !empty($order_info['payment_info'])) {
        fn_cleanup_payment_info($order_id, $order_info['payment_info'], true);
    }

    $edp_data = fn_generate_ekeys_for_edp(['status_from' => $status_from, 'status_to' => $status_to], $order_info);
    $order_info['status'] = $status_to;

    db_query('UPDATE ?:orders SET status = ?s, updated_at = ?i WHERE order_id = ?i', $status_to, TIME, $order_id);

    if ($status_to !== OrderStatuses::PARENT && $status_to !== OrderStatuses::INCOMPLETED) {
        $status_id = strtolower($status_to);
        $event_dispatcher = EventDispatcherProvider::getEventDispatcher();
        $notification_settings_factory = EventDispatcherProvider::getNotificationSettingsFactory();
        $notification_rules = $notification_settings_factory->create($force_notification);

        $event_dispatcher->dispatch(
            "order.status_changed.{$status_id}",
            ['order_info' => $order_info],
            $notification_rules,
            new OrderProvider($order_info)
        );

        if ($edp_data) {
            $notification_rules = fn_get_edp_notification_rules($force_notification ?: [], $edp_data);
            $event_dispatcher->dispatch(
                'order.edp',
                ['order_info' => $order_info, 'edp_data' => $edp_data],
                $notification_rules,
                new OrderProvider($order_info, $edp_data)
            );
        }
    }

    fn_order_notification($order_info, $edp_data, $force_notification);

    /**
     * Executes after order status is changed, allows you to perform additional operations.
     *
     * @param int    $order_id           Order identifier
     * @param string $status_to          New order status (one char)
     * @param string $status_from        Old order status (one char)
     * @param array  $force_notification Array with notification rules
     * @param bool   $place_order        True, if this function have been called inside of fn_place_order function
     * @param array  $order_info         Order information
     * @param array  $edp_data           Downloadable products data
     */
    fn_set_hook('change_order_status_post', $order_id, $status_to, $status_from, $force_notification, $place_order, $order_info, $edp_data);

    return true;
}

/**
 * Deletes order
 *
 * @param int $order_id
 *
 * @return int
 */
function fn_delete_order($order_id)
{
    if (Registry::get('runtime.company_id') && !fn_allowed_for('ULTIMATE')) {
        fn_company_access_denied_notification();

        return false;
    }

    // Log order deletion
    fn_log_event('orders', 'delete', array (
        'order_id' => $order_id,
    ));

    fn_change_order_status($order_id, OrderStatuses::INCOMPLETED, '', fn_get_notification_rules([], false)); // incomplete to increase inventory

    /**
     * Executes before order was deleted,
     * allows you to remove additional data.
     *
     * @param int $order_id Order ID
     */
    fn_set_hook('delete_order', $order_id);

    db_query("DELETE FROM ?:order_data WHERE order_id = ?i", $order_id);
    db_query("DELETE FROM ?:order_details WHERE order_id = ?i", $order_id);
    $result = db_query("DELETE FROM ?:orders WHERE order_id = ?i", $order_id);
    db_query("DELETE FROM ?:product_file_ekeys WHERE order_id = ?i", $order_id);
    db_query("DELETE FROM ?:order_docs WHERE order_id = ?i", $order_id);

    // Delete shipments
    $shipment_ids = db_get_fields('SELECT shipment_id FROM ?:shipment_items WHERE order_id = ?i GROUP BY shipment_id', $order_id);
    fn_delete_shipments($shipment_ids);

    fn_delete_profile_fields_data(ProfileDataTypes::ORDER, $order_id);

    return $result;
}

/**
 * Generates EDP ekeys for email notification
 *
 * @param array $statuses     order statuses
 * @param array $order_info   order information
 * @param array $active_files array with file download statuses
 *
 * @return array $edp_data
 */
function fn_generate_ekeys_for_edp(array $statuses, array $order_info, array $active_files = [])
{
    /**
     * Actions before generating ekeys for downloadable products (EDP)
     *
     * @param array $statuses       Order statuses
     * @param array $order_info     Order information
     * @param array $active_files   Array with file download statuses
     */
    fn_set_hook('generate_ekeys_for_edp_pre', $statuses, $order_info, $active_files);

    $edp_data = array();
    $order_statuses = fn_get_statuses(STATUSES_ORDER, [], true);

    foreach ($order_info['products'] as $v) {

        // Generate ekey if EDP is ordered
        if (empty($v['extra']['is_edp']) || !YesNo::toBool($v['extra']['is_edp'])) {
            continue;
        }

        $activations = db_get_hash_single_array('SELECT activation_type, file_id FROM ?:product_files WHERE product_id = ?i AND status = ?s', ['file_id', 'activation_type'], $v['product_id'], ObjectStatuses::ACTIVE);

        foreach ($activations as $file_id => $activation_type) {
            // Check if ekey already was generated for this file
            $_ekey = db_get_row('SELECT ekey, active, file_id, product_id, order_id, ekey FROM ?:product_file_ekeys WHERE file_id = ?i AND order_id = ?i', $file_id, $order_info['order_id']);

            if (!empty($_ekey)) {
                $_ekey['activation'] = $activation_type;
                $paid_statuses = fn_get_settled_order_statuses();

                // If order status changed to "Processed"
                if (($activation_type === 'P') && !empty($statuses) && !isset($active_files[$v['product_id']][$file_id])) {
                    if (in_array($statuses['status_to'], $paid_statuses)) {
                        $active_files[$v['product_id']][$file_id] = YesNo::YES;
                    } else {
                        $active_files[$v['product_id']][$file_id] = YesNo::NO;
                    }
                }

                if (!empty($active_files[$v['product_id']][$file_id])) {
                    db_query('UPDATE ?:product_file_ekeys SET ?u WHERE file_id = ?i AND product_id = ?i AND order_id = ?i', ['active' => $active_files[$v['product_id']][$file_id]], $_ekey['file_id'], $_ekey['product_id'], $_ekey['order_id']);

                    if ($active_files[$v['product_id']][$file_id] === YesNo::YES && $_ekey['active'] !== YesNo::YES) {
                        $edp_data[$v['product_id']]['files'][$file_id] = $_ekey;
                    }
                }

            } else {
                $_data = [
                    'file_id' => $file_id,
                    'product_id' => $v['product_id'],
                    'ekey' => md5(uniqid(rand())),
                    'ttl' => (TIME + (Registry::get('settings.General.edp_key_ttl') * 60 * 60)),
                    'order_id' => $order_info['order_id'],
                    'activation' => $activation_type,
                ];

                // Activate the file if type is "Immediately" or "After full payment" and order statuses is from "paid" group
                if (
                    $activation_type === 'I'
                    || !empty($active_files[$v['product_id']][$file_id]) && $active_files[$v['product_id']][$file_id] === YesNo::YES
                    || ($activation_type === 'P' && !empty($statuses)
                        && $order_statuses[$statuses['status_to']]['params']['inventory'] === 'D'
                        && substr_count('O', $statuses['status_to']) === 0 && (
                            $order_statuses[$statuses['status_from']]['params']['inventory'] !== 'D'
                            || substr_count('O', $statuses['status_from']) > 0
                        ))
                ) {
                    $_data['active'] = YesNo::YES;
                    $edp_data[$v['product_id']]['files'][$file_id] = $_data;
                }

                db_query('REPLACE INTO ?:product_file_ekeys ?e', $_data);
            }

            if (empty($edp_data[$v['product_id']]['files'][$file_id])) {
                continue;
            }

            $edp_data[$v['product_id']]['files'][$file_id]['file_size'] = db_get_field('SELECT file_size FROM ?:product_files WHERE file_id = ?i', $file_id);
            $edp_data[$v['product_id']]['files'][$file_id]['file_name'] = db_get_field('SELECT file_name FROM ?:product_file_descriptions WHERE file_id = ?i AND lang_code = ?s', $file_id, CART_LANGUAGE);
            $edp_data[$v['product_id']]['files'][$file_id]['url'] = fn_url(
                'orders.get_file?file_id=' . $file_id . '&product_id=' . $v['product_id'] . '&ekey=' . $edp_data[$v['product_id']]['files'][$file_id]['ekey'],
                SiteArea::STOREFRONT,
                fn_get_storefront_protocol()
            );

            if (!isset($edp_data[$v['product_id']]['url'])) {
                $edp_data[$v['product_id']]['url'] = fn_url('orders.downloads?product_id=' . $v['product_id'] . '&ekey=' . $edp_data[$v['product_id']]['files'][$file_id]['ekey'], SiteArea::STOREFRONT, 'http');
            }

            if (isset($edp_data[$v['product_id']]['product'])) {
                continue;
            }

            $edp_data[$v['product_id']]['product'] = $v['product'];
        }
    }

    /**
     * Actions after generating ekeys for downloadable products (EDP)
     *
     * @param array $statuses       Order statuses
     * @param array $order_info     Order information
     * @param array $active_files   Array with file download statuses
     * @param array $edp_data       EDP ekeys for email notification
     */
    fn_set_hook('generate_ekeys_for_edp_post', $statuses, $order_info, $active_files, $edp_data);

    return $edp_data;
}

/**
 * Updates order payment information
 *
 * @param int   $order_id
 * @param array $pp_response Response from payment processor
 *
 * @return boolean true
 */
function fn_update_order_payment_info($order_id, $pp_response)
{
    if (empty($order_id) || empty($pp_response) || !is_array($pp_response)) {
        return false;
    }

    $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);
    if (!empty($payment_info)) {
        $payment_info = unserialize(fn_decrypt_text($payment_info));
    } else {
        $payment_info = array();
    }

	/**
	 * Executes before merging payment processor response with the stored order's payment info, allowing to modify processor response
     *
     * @param int   $order_id     Order identifier
     * @param array $pp_response  Response from payment processor
     * @param array $payment_info Stored order's payment info
	 */
	fn_set_hook('update_order_payment_info_before_processing_response', $order_id, $pp_response, $payment_info);

    foreach ($pp_response as $k => $v) {
        $payment_info[$k] = $v;
    }

    $data = array (
        'data' => fn_encrypt_text(serialize($payment_info)),
        'order_id' => $order_id,
        'type' => 'P',
    );

    /**
     * Executes right before putting the order payment info in the database, allowing you to modify the SQL query.
     *
     * @param int   $order_id     Order identifier
     * @param array $pp_response  Response from payment processor
     * @param array $payment_info Payment processor response merged with the stored payment information
     * @param array $data         Order data to be put in the database
     */
    fn_set_hook('update_order_payment_info', $order_id, $pp_response, $payment_info, $data);

    db_query("REPLACE INTO ?:order_data ?e", $data);

    $child_orders_ids = db_get_fields("SELECT order_id FROM ?:orders WHERE parent_order_id = ?i", $order_id);
    if (!empty($child_orders_ids)) {
        foreach ($child_orders_ids as $child_id) {
            fn_update_order_payment_info($child_id, $pp_response);
        }
    }

    return true;
}

/**
 * Gets shipping info
 *
 * @param int    $shipping_id Shipping ID
 * @param string $lang_code   Language code
 *
 * @return array Shipping info
 */
function fn_get_shipping_info($shipping_id, $lang_code = CART_LANGUAGE)
{
    $fields = array(
        '?:shippings.*',
        '?:shipping_descriptions.shipping',
        '?:shipping_descriptions.description',
        '?:shipping_descriptions.delivery_time',
    );
    $join = db_quote(" LEFT JOIN ?:shipping_descriptions ON ?:shipping_descriptions.shipping_id = ?:shippings.shipping_id AND ?:shipping_descriptions.lang_code = ?s", $lang_code);
    $conditions = "";

    /**
     * Change SQL parameters for shipping info select
     *
     * @param int    $shipping_id Shipping ID
     * @param string $fields      List of fields for retrieving
     * @param string $join        String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $conditions  Condition for selecting product data
     * @param string $lang_code   Lang code
     */
    fn_set_hook('get_shipping_info', $shipping_id, $fields, $join, $conditions, $lang_code);

    $shipping = [];
    if (!empty($shipping_id)) {
        $shipping = db_get_row('SELECT ?p FROM ?:shippings ?p WHERE ?:shippings.shipping_id = ?i ?p',
            implode(', ', $fields), $join, $shipping_id, $conditions
        );
    }

    if (empty($shipping)) {
        return [];
    }

    //FIXME Saved for backward compatibility
    $shipping['allow_multiple_locations'] = true;

    /**
     * Modify shipping data after selection
     *
     * @param int    $shipping_id Shipping ID
     * @param string $lang_code   Lang code
     * @param array  $shipping    SHipping data
     */
    fn_set_hook('get_shipping_info_after_select', $shipping_id, $lang_code, $shipping);

    if (!empty($shipping)) {
        $shipping['tax_ids'] = !empty($shipping['tax_ids']) ? fn_explode(',', $shipping['tax_ids']) : [];
        $shipping['icon'] = fn_get_image_pairs($shipping['shipping_id'], 'shipping', 'M', true, true, $lang_code);

        if (!empty($shipping['service_params'])) {
            $shipping['service_params'] = unserialize($shipping['service_params']);
        }
        $shipping['rates'] = fn_get_shipping_destinations($shipping_id, $shipping, $lang_code);
    }

    /** @var \Tygh\Storefront\Repository $repository */
    $repository = Tygh::$app['storefront.repository'];
    [$storefronts,] = $repository->find(['shipping_ids' => $shipping_id]);
    $shipping['storefront_ids'] = implode(',', array_keys($storefronts));

    /**
     * Particularize shipping information
     *
     * @param int    $shipping_id Shipping ID
     * @param string $lang_code   Lang code
     * @param array  $shipping    Shipping data
     */
    fn_set_hook('get_shipping_info_post', $shipping_id, $lang_code, $shipping);

    return $shipping;
}

/**
 * Fetches shipping destination data
 *
 * @param int    $shipping_id Shipping identifier
 * @param array  $shipping    Shipping data
 * @param string $lang_code   Two-letters language code
 *
 * @return array
 */
function fn_get_shipping_destinations($shipping_id, array $shipping, $lang_code = CART_LANGUAGE)
{
    $destinations = fn_get_destinations();
    $destination_ids = array_column($destinations, 'destination_id');
    $destination_deliveries = fn_get_shipping_destination_delivery_time($shipping_id, $destination_ids, $lang_code);
    $shipping_localization = !empty($shipping['localization']) ? fn_explode(',', $shipping['localization']) : [];

    foreach ($destinations as $k => $destination) {
        $destination_id = $destination['destination_id'];
        if (!empty($destination_deliveries[$destination_id]['delivery_time']) ) {
            $destinations[$k]['delivery_time'] = $destination_deliveries[$destination_id]['delivery_time'];
        }

        if ($shipping_localization) { // check available destinations, but skip default destination
            $destination_localization = fn_explode(',', $destination['localization']);
            if (!array_intersect($shipping_localization, $destination_localization)) {
                continue;
            }
        }

        $destinations[$k] = array_merge($destinations[$k], fn_get_shipping_rate($shipping_id, $destination_id));
    }

    return array_combine(
        array_column($destinations, 'destination_id'),
        $destinations
    );
}

/**
 * Get shipping rate
 *
 * @param int $shipping_id    Shipping ID
 * @param int $destination_id location
 *
 * @return array rate info
 */
function fn_get_shipping_rate($shipping_id, $destination_id)
{
    $rate = db_get_row("SELECT rate_id, rate_value, destination_id, IF(rate_value = '', 0, 1) as rates_defined, base_rate FROM ?:shipping_rates WHERE shipping_id = ?i AND destination_id = ?i", $shipping_id, $destination_id);

    if (!empty($rate)) {
        $rate['rate_value'] = unserialize((string) $rate['rate_value']);
    }
    return $rate;
}

/**
 * Gets all shippings
 *
 * @param bool   $simple
 * @param string $lang_code
 *
 * @return array
 */
function fn_get_shippings($simple, $lang_code = CART_LANGUAGE)
{
    $conditions = '1';

    if (AREA == 'C') {
        $conditions .= " AND (" . fn_find_array_in_set(Tygh::$app['session']['auth']['usergroup_ids'], 'a.usergroup_ids', true) . ")";
        $conditions .= " AND a.status = 'A'";
        $conditions .= fn_get_localizations_condition('a.localization');
    }

    if ($simple == true) {
        return db_get_hash_single_array("SELECT a.shipping_id, b.shipping FROM ?:shippings as a LEFT JOIN ?:shipping_descriptions as b ON a.shipping_id = b.shipping_id AND b.lang_code = ?s WHERE ?p ORDER BY a.position", array('shipping_id', 'shipping'), $lang_code, $conditions);
    } else {
        $fields = [
            'a.shipping_id',
            'a.min_weight',
            'a.max_weight',
            'a.position',
            'a.status',
            'a.company_id',
            'b.shipping',
            'b.delivery_time',
            'a.usergroup_ids',
        ];

        /**
         * Executes before sql query.
         *
         * @param array<string> $fields Shippings fields.
         * @param array<string> $conditions Conditions for query.
         * @param array<string> $lang_code Language code.
         */
        fn_set_hook('get_shippings', $fields, $conditions, $lang_code);

        return db_get_array(
            'SELECT ' . implode(', ', $fields)
            . ' FROM ?:shippings as a LEFT JOIN ?:shipping_descriptions as b ON a.shipping_id = b.shipping_id AND b.lang_code = ?s'
            . ' WHERE ?p ORDER BY a.position',
            $lang_code,
            $conditions
        );
    }
}

/**
 * Gets names all available shippings for specified company.
 *
 * @param int $company_id Company identifier.
 *
 * @return array<string>
 */
function fn_get_shippings_names($company_id)
{
    if ($company_id === 0) {
        $shippings_info = fn_get_shippings(false);
    } else {
        $shippings_info = fn_get_available_shippings($company_id);
    }
    return array_column($shippings_info, 'shipping', 'shipping_id');
}

/**
 * Gets shipping name
 *
 * @param int    $shipping_id shipping identifier
 * @param string $lang_code   2-letter language code (e.g. 'en', 'ru', etc.)
 *
 * @return string Shipping name if shipping identifier is not null; false otherwise
 */
function fn_get_shipping_name($shipping_id, $lang_code = CART_LANGUAGE)
{
    if (!empty($shipping_id)) {
        return db_get_field("SELECT shipping FROM ?:shipping_descriptions WHERE shipping_id = ?i AND lang_code = ?s", $shipping_id, $lang_code);
    }

    return false;
}

/**
 * Create/Update shipping name
 *
 * @param array  $shipping_data shipping info
 * @param int    $shipping_id   shipping identifier
 * @param string $lang_code     2-letter language code (e.g. 'en', 'ru', etc.)
 *
 * @return string Shipping name if shipping identifier is not null; false otherwise
 */
function fn_update_shipping($shipping_data, $shipping_id, $lang_code = DESCR_SL)
{
    $default_params = [
        'shipping_id' => $shipping_id
    ];
    if (!$shipping_id) {
        $default_params['storefront_ids'] = '';
    }
    $shipping_data = array_merge($default_params, $shipping_data);

    SecurityHelper::sanitizeObjectData('shipping', $shipping_data);

    $previous_storefronts = [];

    if (!empty($shipping_data['shipping']) || !empty($shipping_id)) {
        $shipping_data['localization'] = empty($shipping_data['localization'])
            ? ''
            : fn_implode_localizations($shipping_data['localization']);
        $shipping_data['tax_ids'] = !empty($shipping_data['tax_ids'])
            ? fn_create_set($shipping_data['tax_ids'])
            : '';

        $shipping_data['usergroup_ids'] = empty($shipping_data['usergroup_ids'])
            ? USERGROUP_ALL
            : (is_array($shipping_data['usergroup_ids'])
                ? implode(',', $shipping_data['usergroup_ids'])
                : $shipping_data['usergroup_ids']);

        unset($shipping_data['shipping_id']);

        if (isset($shipping_data['rate_calculation']) && $shipping_data['rate_calculation'] == 'M') {
            $shipping_data['service_id'] = 0;
            $shipping_data['service_params'] = array();
        }

        if (isset($shipping_data['service_params'])) {
            $shipping_data['service_params'] = serialize($shipping_data['service_params']);
        }

        /**
         * Prepare shipping data right before the shipping method data will be updated
         *
         * @param array  $shipping_data shipping info
         * @param int    $shipping_id   shipping identifier
         * @param string $lang_code     2-letter language code (e.g. 'en', 'ru', etc.)
         */
        fn_set_hook('update_shipping', $shipping_data, $shipping_id, $lang_code);

        if (!empty($shipping_id)) {
            $action = 'update';

            /** @var \Tygh\Storefront\Repository $repository */
            $repository = Tygh::$app['storefront.repository'];
            [$previous_storefronts,] = $repository->find(['shipping_ids' => $shipping_id]);

            $update_result = db_query(
                'UPDATE ?:shippings SET ?u WHERE shipping_id = ?i',
                $shipping_data,
                $shipping_id
            );
            db_query(
                'UPDATE ?:shipping_descriptions SET ?u WHERE shipping_id = ?i AND lang_code = ?s',
                $shipping_data,
                $shipping_id,
                $lang_code
            );

            if ($update_result === false) {
                fn_set_notification(
                    NotificationSeverity::ERROR,
                    __('error'),
                    __('object_not_found', ['[object]' => __('shipping')]),
                    '',
                    '404'
                );
                $shipping_id = false;
            }
        } else {
            $action = 'add';

            $shipping_id = $shipping_data['shipping_id'] = db_query("INSERT INTO ?:shippings ?e", $shipping_data);

            foreach (array_keys(Languages::getAll()) as $shipping_data['lang_code']) {
                db_query('INSERT INTO ?:shipping_descriptions ?e', $shipping_data);
            }

            if ($shipping_data['rate_calculation'] === 'R') {
                $all_destinations = db_get_fields('SELECT destination_id FROM ?:destinations WHERE status = ?s', ObjectStatuses::ACTIVE);
                $rates = [];
                foreach ($all_destinations as $destination_id) {
                    $rates['delivery_time'][$destination_id] = '';
                    $rates[$destination_id]['base_rate'] = 0;
                }
                $shipping_data['rates'] = $rates;
            }
        }

        if ($shipping_id) {
            fn_attach_image_pairs('shipping', 'shipping', $shipping_id, $lang_code);
            $destination_deliveries = isset($shipping_data['rates']['delivery_time'])
                ? $shipping_data['rates']['delivery_time']
                : [];
            unset($shipping_data['rates']['delivery_time']);

            if (isset($shipping_data['rates'])) {
                $shipping_data['rates'] = array_filter($shipping_data['rates']);
                fn_update_shipping_rates($shipping_data, $shipping_id);
            }

            fn_update_shipping_destination_delivery_time($shipping_id, $destination_deliveries, $lang_code);

            if (isset($shipping_data['storefront_ids'])) {
                /** @var \Tygh\Storefront\Repository $repository */
                $repository = Tygh::$app['storefront.repository'];
                [$new_storefronts,] = $repository->find(['storefront_id' => $shipping_data['storefront_ids']]);
                $added_storefronts = array_diff_key($new_storefronts, $previous_storefronts);
                /** @var \Tygh\Storefront\Storefront $storefront */
                foreach ($added_storefronts as $storefront) {
                    $repository->save($storefront->addShippingIds($shipping_id));
                }
                $removed_storefronts = array_diff_key($previous_storefronts, $new_storefronts);
                foreach ($removed_storefronts as $storefront) {
                    $repository->save($storefront->removeShippingIds($shipping_id));
                }
            }
        }

        /**
         * Executes after the shipping method data was updated
         *
         * @param array  $shipping_data shipping info
         * @param int    $shipping_id   shipping identifier
         * @param string $lang_code     2-letter language code (e.g. 'en', 'ru', etc.)
         * @param string $action        Action that is performed with the shipping method
         */
        fn_set_hook('update_shipping_post', $shipping_data, $shipping_id, $lang_code, $action);
    }

    return $shipping_id;
}

/**
 * Updates shipping delivery time by destination and selected language
 *
 * @param int    $shipping_id   Shipping identifier
 * @param array  $deliveries    Destinations delivery time data
 * @param string $lang_code     Two-letters language code
 *
 * @return void
 */
function fn_update_shipping_destination_delivery_time($shipping_id, $deliveries, $lang_code)
{
    db_query('DELETE FROM ?:shipping_time_descriptions WHERE shipping_id = ?i AND lang_code = ?s', $shipping_id, $lang_code);

    if (empty($deliveries)) {
        return;
    }

    $destination_deliveries = (new Collection($deliveries))
        ->filter(function ($delivery_time) {
            return trim($delivery_time) !== '';
        })
        ->map(function ($delivery_time, $destination_id) {
            return [
                'delivery_time'  => trim($delivery_time),
                'destination_id' => $destination_id,
            ];
        })
        ->reduce(function ($acc, $item) use ($shipping_id, $lang_code) {
            $item = array_merge($item, ['shipping_id' => $shipping_id, 'lang_code' => $lang_code]);
            $acc[] = $item;
            return $acc;
        }, []);

    if (empty($destination_deliveries)) {
        return;
    }

    db_query('INSERT INTO ?:shipping_time_descriptions ?m', $destination_deliveries);
}

/**
 * Fetches delivery time for specified destinations and language
 *
 * @param int    $shipping_id     Shipping identifier
 * @param array  $destination_ids Destinations identifiers
 * @param string $lang_code       Two-letter language code
 *
 * @return array
 */
function fn_get_shipping_destination_delivery_time($shipping_id, $destination_ids, $lang_code = CART_LANGUAGE)
{
    return db_get_hash_array(
        'SELECT destination_id, delivery_time FROM ?:shipping_time_descriptions'
        . ' WHERE destination_id IN (?n) AND lang_code = ?s AND shipping_id = ?i',
        'destination_id',
        $destination_ids,
        $lang_code,
        $shipping_id
    );
}

/**
 * Update shipping rates
 *
 * @param array $shipping_data shipping info
 * @param int   $shipping_id   shipping identifier
 */
function fn_update_shipping_rates($shipping_data, $shipping_id)
{
    if (!empty($shipping_id)) {
        db_query('DELETE FROM ?:shipping_rates WHERE shipping_id = ?i', $shipping_id);

        if (empty($shipping_data['rates'])) {
            return;
        }

        foreach ($shipping_data['rates'] as $destination_id => $rate) {

            if (!empty($rate['destination_id'])) {
                $destination_id = $rate['destination_id'];
            }

            $rate_types = ShippingRateTypes::getAll();
            $normalized_data = [];

            foreach ($rate_types as $type) {
                // Update rate values
                if (!empty($rate['rate_value'][$type]) && is_array($rate['rate_value'][$type])) {
                    fn_normalized_shipping_rate($normalized_data, $rate['rate_value'][$type], $type);
                }

                // Add new rate values
                if (!empty($shipping_data['add_rates']) && is_array($shipping_data['add_rates'][$destination_id]['rate_value'][$type])) {
                    fn_normalized_shipping_rate($normalized_data, $shipping_data['add_rates'][$destination_id]['rate_value'][$type], $type);
                }

                if (!empty($normalized_data[$type]) && is_array($normalized_data[$type])) {
                    ksort($normalized_data[$type], SORT_NUMERIC);
                }
            }
            $base_rate = isset($rate['base_rate']) ? $rate['base_rate'] : 0;
            if (!fn_is_empty($normalized_data) || isset($rate['base_rate'])) {
                $normalized_data = fn_is_empty($normalized_data) ? null : serialize($normalized_data);
                db_replace_into('shipping_rates', [
                    'rate_value'     => $normalized_data,
                    'shipping_id'    => $shipping_id,
                    'destination_id' => $destination_id,
                    'base_rate'      => $base_rate,
                ]);
            }
        }
    }

}

/**
 * Normalized shipping rates
 *
 * @param array<string, array<string, array<string, float|int|string>>> $normalized_data Normalized information about rates.
 * @param array<string, array<string, float|int|string>>                $rates           Raw rates data.
 * @param string                                                        $rate_type       Rate types: Cost, Weight, Items
 */
function fn_normalized_shipping_rate(&$normalized_data, $rates, $rate_type)
{
    foreach ($rates as $rate) {
        if ($rate['value'] === '') {
            continue;
        }
        if (
            isset($normalized_data[$rate_type][(string) $rate['range_from_value']])
            && $normalized_data[$rate_type][(string) $rate['range_from_value']]['value'] !== 0
        ) {
            continue;
        }
        if ($rate['range_to_value'] !== '') {
            $rate['range_to_value'] = $rate_type === 'I'
                ? (int) $rate['range_to_value']
                : (float) $rate['range_to_value'];
        }
        $rate['range_from_value'] = $rate_type === 'I'
            ? (int) $rate['range_from_value']
            : (float) $rate['range_from_value'];
        /** @var float|int|string $formatted_value */
        $formatted_value = fn_format_price($rate['value']);

        /**
         * Executes before rate value is overwritten by formatted one, allows changing the value.
         *
         * @param array<string, string> $normalized_data Normalized information about rates
         * @param array<string, string> $rates           Raw rates data
         * @param string                $rate_type       Rate types: Cost, Weight, Items
         * @param float                 $formatted_value Formatted value of rate
         */
        fn_set_hook('normalized_shipping_rate_reformat_value', $normalized_data, $rate, $rate_type, $formatted_value);

        $rate['value'] = $formatted_value;
        $rate['per_unit'] = empty($rate['per_unit'])
            ? YesNo::NO
            : (string) $rate['per_unit'];

        $normalized_data[$rate_type][(string) $rate['range_from_value']] = [
            'range_from_value' => $rate['range_from_value'],
            'range_to_value'   => $rate['range_to_value'],
            'value'            => $rate['value'],
            'per_unit'         => $rate['per_unit'],
            'type'             => isset($rate['type']) ? $rate['type'] : 'F',
        ];
    }
}

/**
 * Gets all taxes
 *
 * @param string $lang_code
 *
 * @return array
 */
function fn_get_taxes($lang_code = CART_LANGUAGE)
{
    return db_get_hash_array("SELECT a.*, b.tax FROM ?:taxes as a LEFT JOIN ?:tax_descriptions as b ON b.tax_id = a.tax_id AND b.lang_code = ?s ORDER BY a.priority", 'tax_id', $lang_code);
}

/**
 * Gets tax data
 *
 * @param int    $tax_id    tax identifier
 * @param string $lang_code 2-letter language code (e.g. 'en', 'ru', etc.)
 *
 * @return array Tax data if tax identifier is not null; false otherwise
 */
function fn_get_tax($tax_id, $lang_code = CART_LANGUAGE)
{
    $tax = array();
    if (!empty($tax_id)) {
        $tax = db_get_row("SELECT a.*, tax FROM ?:taxes as a LEFT JOIN ?:tax_descriptions as b ON b.tax_id = a.tax_id AND b.lang_code = ?s WHERE a.tax_id = ?i", $lang_code, $tax_id);
    }

    return $tax;
}

/**
 * Gets tax name
 *
 * @param int    $tax_id
 * @param string $lang_code
 * @param bool   $as_array
 *
 * @return array|bool|string
 */
function fn_get_tax_name($tax_id = 0, $lang_code = CART_LANGUAGE, $as_array = false)
{
    if (!empty($tax_id)) {
        if (!is_array($tax_id) && strpos($tax_id, ',') !== false) {
            $tax_id = explode(',', $tax_id);
        }
        if (is_array($tax_id) || $as_array == true) {
            return db_get_hash_single_array("SELECT tax_id, tax FROM ?:tax_descriptions WHERE tax_id IN (?n) AND lang_code = ?s", array('tax_id', 'tax'), $tax_id, $lang_code);
        } else {
            return db_get_field("SELECT tax FROM ?:tax_descriptions WHERE tax_id = ?i AND lang_code = ?s", $tax_id, $lang_code);
        }
    }

    return false;
}

/**
 * Get all rates for specific tax
 *
 * @param int $tax_id
 * @param int $destination_id
 *
 * @return array|bool
 */
function fn_get_tax_rates($tax_id, $destination_id = 0)
{
    if (empty($tax_id)) {
        return false;
    }

    return db_get_array("SELECT * FROM ?:tax_rates WHERE tax_id = ?i AND destination_id = ?i", $tax_id, $destination_id);
}

/**
 * Get selected taxes
 *
 * @param string $taxes_set
 *
 * @return array|bool
 */
function fn_get_set_taxes($taxes_set)
{
    if (empty($taxes_set)) {
        return false;
    }

    if (!is_array($taxes_set)) {
        $taxes_set = explode(',', $taxes_set);
    }

    return db_get_hash_array("SELECT tax_id, address_type, priority, price_includes_tax, regnumber FROM ?:taxes WHERE tax_id IN (?n) AND status = 'A' ORDER BY priority", 'tax_id', $taxes_set);
}

/**
 * Creates or updates tax
 *
 * @param array  $tax_data  Tax data
 * @param int    $tax_id    Tax identifier
 * @param string $lang_code 2-letter language code (e.g. 'en', 'ru', etc.)
 *
 * @return int tax identifier update or create tax
 */
function fn_update_tax($tax_data, $tax_id, $lang_code = CART_LANGUAGE)
{
    /**
     * Update tax data (running before fn_update_tax() function)
     *
     * @param array  $tax_data  Tax data
     * @param int    $tax_id    Tax identifier
     * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('update_tax_pre', $tax_data, $tax_id, $lang_code);

    if (!empty($tax_id)) {
        $arow = db_query('UPDATE ?:taxes SET ?u WHERE tax_id = ?i', $tax_data, $tax_id);
        db_query('UPDATE ?:tax_descriptions SET ?u WHERE tax_id = ?i AND lang_code = ?s', $tax_data, $tax_id, $lang_code);

        if ($arow === false) {
            fn_set_notification('E', __('error'), __('object_not_found', array('[object]' => __('tax'))),'','404');
            $tax_id = false;
        }
    } else {
        unset($tax_data['tax_id']);
        $tax_id = $tax_data['tax_id'] = db_query("INSERT INTO ?:taxes ?e", $tax_data);

        foreach (Languages::getAll() as $tax_data['lang_code'] => $_v) {
            db_query("INSERT INTO ?:tax_descriptions ?e", $tax_data);
        }
    }

    // Update rates data
    if (!empty($tax_id) && !empty($tax_data['rates'])) {
        $destination_ids = db_get_fields("SELECT destination_id FROM ?:destinations");

        foreach ($tax_data['rates'] as $destination_id => $v) {
            if (in_array($destination_id, $destination_ids)) {

                $rate_id = db_get_field("SELECT rate_id FROM ?:tax_rates WHERE destination_id = ?i AND tax_id = ?i", $destination_id, $tax_id);

                if (!empty($rate_id)) {
                    if (
                        isset($v['rate_value'])
                        && fn_string_not_empty((string) $v['rate_value'])
                    ) {
                        $v['rate_value'] = floatval($v['rate_value']);
                        db_query("UPDATE ?:tax_rates SET ?u WHERE rate_id = ?i", $v, $rate_id);
                    } else {
                        db_query("DELETE FROM ?:tax_rates WHERE rate_id = ?i", $rate_id);
                    }
                } elseif (
                    isset($v['rate_value'])
                    && fn_string_not_empty((string) $v['rate_value'])
                ) {
                    $v['destination_id'] = $destination_id;
                    $v['tax_id'] = $tax_id;
                    db_query("INSERT INTO ?:tax_rates ?e", $v);
                }
            }
        }
    }


    /**
     * Executes after tax was updated/inserted.
     *
     * @param array  $tax_data  Tax data
     * @param int    $tax_id    Tax identifier
     * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('update_tax_post', $tax_data, $tax_id, $lang_code);

    return $tax_id;
}

/**
 * Delete tax
 *
 * @param int $tax_id ID of the tax to be removed.
 *
 * @return boolean
 */
function fn_delete_tax($tax_id)
{
    /**
     * Delete taxes (running before fn_delete_taxes() function)
     *
     * @param array $tax_ids Taxes identifier
     */
    fn_set_hook('delete_tax_pre', $tax_id);

    $result = db_query("DELETE FROM ?:taxes WHERE tax_id = ?i", $tax_id);
    db_query("DELETE FROM ?:tax_descriptions WHERE tax_id = ?i", $tax_id);
    db_query("DELETE FROM ?:tax_rates WHERE tax_id = ?i", $tax_id);
    db_query("UPDATE ?:products SET tax_ids = ?p", fn_remove_from_set('tax_ids', $tax_id));
    db_query("UPDATE ?:shippings SET tax_ids = ?p", fn_remove_from_set('tax_ids', $tax_id));

    return $result;
}

/**
 * Delete taxes
 *
 * @param array $tax_ids IDs of the taxes to be removed.
 *
 * @return boolean true
 */
function fn_delete_taxes($tax_ids)
{
    /**
     * Delete taxes (running before fn_delete_taxes() function)
     *
     * @param array $tax_ids Taxes identifier
     */
    fn_set_hook('delete_taxes_pre', $tax_ids);

    foreach ((array) $tax_ids as $v) {
        fn_delete_tax($v);
    }

    return true;
}

function fn_add_exclude_products(&$cart, &$auth)
{
    $subtotal = 0;
    $original_subtotal = 0;

    if (isset($cart['products']) && is_array($cart['products'])) {
        foreach ($cart['products'] as $cart_id => $product) {
            if (empty($product['product_id'])) {
                continue;
            }

            if (isset($product['extra']['exclude_from_calculate'])) {
                if (fn_promotion_is_recalculation_enabled($cart)) {
                    fn_delete_cart_product($cart, $cart_id);
                }
            } else {
                if (!isset($product['product_options'])) {
                    $product['product_options'] = array();
                }

                $product_subtotal = fn_apply_options_modifiers($product['product_options'], $product['price'], 'P', array(), array('product_data' => $product)) * $product['amount'];
                $original_subtotal += $product_subtotal;
                $subtotal += $product_subtotal - ((isset($product['discount'])) ? (float) $product['discount'] : 0);
            }
        }
    }

    fn_set_hook('exclude_products_from_calculation', $cart, $auth, $original_subtotal, $subtotal);

}

/**
 * Calculates cart content.
 *
 * @param array  $cart                        Cart to calculate
 * @param array  $auth                        Current user authentication data
 * @param string $calculate_shipping          Shipping calculation policy:
 *                                            A - calculate all available methods
 *                                            E - calculate selected methods only (from cart[shipping])
 *                                            S - skip calculation
 * @param bool   $calculate_taxes             Whether to calculate taxes
 * @param string $options_style               Options calculation policy:
 *                                            F - full
 *                                            S - skip selection
 *                                            I - info
 * @param bool   $apply_cart_promotions       Whether to apply promotions
 * @param string $lang_code                   Two-letter language code
 * @param string $area                        Single-letter area code
 *
 * @return array Calculated cart products and product groups.
 *               Products prices definition:
 *                   base_price - price without options modifiers
 *                   original_price - price without discounts (with options modifiers)
 *                   price - price includes discount and taxes
 *                   original_subtotal - original_price * product qty
 *                   subtotal - price * product qty
 *                   discount - discount for this product
 *                   display_price - the displayed price (price does not use in the calculaton)
 *                   display_subtotal - the displayed subtotal (price does not use in the calculaton)
 *               Cart prices definition:
 *                   shipping_cost - total shipping cost
 *                   subtotal - sum (price * amount) of all products
 *                   original_subtotal - sum (original_price * amount) of all products
 *                   tax_subtotal - sum of all the tax values
 *                   display_subtotal - the displayed subtotal (does not use in the calculaton)
 *                   subtotal_discount - the order discount
 *                   discount - sum of all products discounts (except subtotal_discount)
 *                   total - order total
 */
function fn_calculate_cart_content(
    &$cart,
    $auth,
    $calculate_shipping = 'A',
    $calculate_taxes = true,
    $options_style = 'F',
    $apply_cart_promotions = true,
    $lang_code = CART_LANGUAGE,
    $area = AREA
) {
    $disable_change_calculate_shipping = !$apply_cart_promotions && $calculate_shipping === ShippingCalculationTypes::SKIP_CALCULATION;

    $cart_products = $product_groups = [];

    $cart['subtotal']
        = $cart['display_subtotal']
        = $cart['original_subtotal']
        = $cart['amount']
        = $cart['total']
        = $cart['discount']
        = $cart['tax_subtotal']
        = $cart['display_shipping_cost']
        = $cart['shipping_cost']
        = 0;

    $cart['use_discount'] = false;
    $cart['shipping_failed'] = $cart['company_shipping_failed'] = false;

    $cart['stored_taxes'] = empty($cart['stored_taxes'])
        ? 'N'
        : $cart['stored_taxes'];

    $cart['coupons'] = empty($cart['coupons'])
        ? []
        : $cart['coupons'];

    // FIXME: Backward compatibility
    $cart['recalculate'] = isset($cart['recalculate'])
        ? $cart['recalculate']
        : false;

    // FIXME: Backward compatibility
    $cart['calculate_shipping'] = isset($cart['calculate_shipping'])
        ? $cart['calculate_shipping']
        : false;

    $cart['free_shipping'] = [];

    $cart['options_style'] = $options_style;

    $cart['products'] = !empty($cart['products'])
        ? $cart['products']
        : [];

    $cart['applied_promotions'] = empty($cart['applied_promotions'])
        ? []
        : $cart['applied_promotions'];

    fn_add_exclude_products($cart, $auth);

    if (is_array($cart['products'])) {

        $amount_totals = [];
        if (Registry::get('settings.General.disregard_options_for_discounts') == 'Y') {
            foreach ($cart['products'] as $k => $v) {
                if (!empty($amount_totals[$v['product_id']])) {
                    $amount_totals[$v['product_id']] += $v['amount'];
                } else {
                    $amount_totals[$v['product_id']] = $v['amount'];
                }
            }
        }

        // Collect product data
        foreach ($cart['products'] as $cart_id => $cart_product) {
            $cart['products'][$cart_id]['amount_total'] = isset($amount_totals[$cart_product['product_id']])
                ? $amount_totals[$cart_product['product_id']]
                : $cart_product['amount'];

            $product_data = fn_get_cart_product_data(
                $cart_id,
                $cart['products'][$cart_id],
                false,
                $cart,
                $auth,
                0,
                $lang_code
            );

            if (!$product_data) { // FIXME - for deleted products for OM
                fn_delete_cart_product($cart, $cart_id);
                continue;
            }

            $cart_products[$cart_id] = $product_data;
        }

        /**
         * Executes when calculating cart content after products data is collected.
         * Allows to modify cart content and affect further processes like promotions or shipping calculation.
         *
         * @param array $cart                  Array of the cart contents and user information necessary for purchase
         * @param array $cart_products         Array of products in cart
         * @param array $auth                  Array of user authentication data (e.g. uid, usergroup_ids, etc.)
         * @param bool  $apply_cart_promotions Whether promotions have to be applied to cart content
         */
        fn_set_hook('calculate_cart_items', $cart, $cart_products, $auth, $apply_cart_promotions);

        $current_promotions = $cart['applied_promotions'] ?: [];
        // Apply cart promotions
        if ($apply_cart_promotions && $cart['subtotal'] >= 0 && fn_promotion_is_recalculation_enabled($cart)) {
            if (!empty($cart['stored_subtotal_discount'])) {
                $prev_discount = $cart['subtotal_discount'];
            }
            $cart['applied_promotions'] = fn_promotion_apply('cart', $cart, $auth, $cart_products);
            $cart['subtotal_discount'] = $cart['subtotal_discount'] > $cart['subtotal'] ? $cart['subtotal'] : $cart['subtotal_discount'];
            if (!empty($cart['stored_subtotal_discount'])) {
                $cart['subtotal_discount'] = $prev_discount;
            }
        }
        fn_check_promotion_notices();

        $cart['applied_promotions'] = $cart['applied_promotions'] ?: [];

        // If Free shipping promotion was added or removed, shipping rates must be recalculated
        $promotion_pairs = [
            [$current_promotions, $cart['applied_promotions']],
            [$cart['applied_promotions'], $current_promotions],
        ];
        foreach ($promotion_pairs as [$promo_before, $promo_after]) {
            foreach ($promo_before as $promotion_id => $promotion) {
                if (isset($promo_after[$promotion_id])) {
                    continue;
                }
                foreach ($promotion['bonuses'] as $bonus) {
                    if ($bonus['bonus'] === 'free_shipping') {
                        $calculate_shipping = 'A';
                        break;
                    }
                }
            }
        }

        // FIXME: Backward compatibility: If the 'calculate_shipping' property is true, shipping calculation will be forced
        if (!$disable_change_calculate_shipping && $cart['calculate_shipping'] === true) {
            $calculate_shipping = 'A';
        }

        // Check if companies whose products are present in the order have enabled shipping methods
        $shipping_required = false;
        $company_shippings = [];
        foreach ($cart_products as $product) {
            if (!isset($company_shippings[$product['company_id']])) {
                $company_shippings[$product['company_id']] = Shippings::hasEnabledShippings($product['company_id']);
            }
            if ($company_shippings[$product['company_id']]) {
                $shipping_required = true;
                break;
            }
        }
        unset($company_shippings);

        $location = fn_get_customer_location($auth, $cart);
        $product_groups = Shippings::groupProductsList($cart_products, $location);

        // Disable shipping when having only products without shipping
        if ($shipping_required) {
            $shipping_required = !array_reduce($product_groups, function($skip_shipping, $group) {
                return $skip_shipping && ($group['shipping_no_required'] || $group['shipping_by_marketplace']);
            }, true);
        }

        $cart['shipping_required'] = $shipping_required;

        if (!empty($cart['change_cart_products'])) {
            if (!empty($cart['product_groups']) && count($product_groups) === count($cart['product_groups'])) {
                foreach ($product_groups as $group) {
                    foreach ($cart['product_groups'] as $product_group_key => $product_group) {
                        if (
                            isset($product_group['group_key'])
                            && $group['group_key'] === $product_group['group_key']
                        ) {
                            $cart['product_groups'][$product_group_key]['products'] = $group['products'];
                            break;
                        }
                    }
                }
            } elseif (!empty($cart['product_groups']) && count($cart['product_groups']) !== count($product_groups)) {
                unset($cart['product_groups']);
            }

            unset($cart['change_cart_products']);
            $cart['stored_taxes'] = 'N';
        }

        $shipping_cache_tables = [
            'shippings',
            'shipping_descriptions',
            'shipping_rates',
            'shipping_services',
            'shipping_service_descriptions',
            'shipping_time_descriptions',
            'countries',
            'states',
        ];

        $shipping_cache_key = 'calculated_shipping_rates';

        /**
         * Executes when the cart content is calculated before the shipping rates are calculated,
         * allows you to modify the cart state.
         *
         * @param array         $cart                  Cart data
         * @param array         $auth                  Authentication data
         * @param string        $calculate_shipping    One-letter flag indicating how to calculate the shipping cost:
         *                                             A - calculate all available methods
         *                                             E - calculate selected methods only (from cart[shipping])
         *                                             S - skip calculation
         * @param bool          $calculate_taxes       Whether taxes should be calculated
         * @param string        $options_style         One-letter flag indicating how to obtain options information:
         *                                             F - full
         *                                             S - skip selection
         *                                             I - info
         * @param bool          $apply_cart_promotions Whether promotions should be applied to the cart
         * @param array<string> $shipping_cache_tables Database tables that cause shipping recalculation
         * @param string        $shipping_cache_key    Shipping rates cache key
         */
        fn_set_hook(
            'calculate_cart_content_before_shipping_calculation',
            $cart,
            $auth,
            $calculate_shipping,
            $calculate_taxes,
            $options_style,
            $apply_cart_promotions,
            $shipping_cache_tables,
            $shipping_cache_key
        );

        // If shipping methods were changed, shipping recalculation is forced
        Registry::registerCache(['checkout', $shipping_cache_key], $shipping_cache_tables, Registry::cacheLevel(['user', 'storefront']));
        if (!$disable_change_calculate_shipping && $calculate_shipping === 'S' && !Registry::isExist($shipping_cache_key)) {
            $calculate_shipping = 'A';
        }

        // $cart and $auth could be changed by addons, thus refresh is required
        $location = fn_get_customer_location($auth, $cart);
        $product_groups = Shippings::groupProductsList($cart_products, $location);

        // Check whether shipping must be calculated
        $is_shipping_calculation_forced = $calculate_shipping === 'A';
        $is_shipping_calculation_required_for_selected = $calculate_shipping === 'E';
        $is_shipping_selected = !empty($cart['chosen_shipping'])
            && count($cart['chosen_shipping']) === count($product_groups);

        $cart['calculate_shipping'] = $cart['shipping_required']
            && (
                $is_shipping_calculation_forced
                || (
                    $is_shipping_calculation_required_for_selected
                    && $is_shipping_selected
                )
        );

        if ($cart['calculate_shipping'] || empty($cart['product_groups'])) {
            $shippings = [];
            $shippings_groups = [];

            foreach ($product_groups as $key_group => $group) {
                if ($cart['shipping_required'] === false) {
                    $product_groups[$key_group]['free_shipping'] = true;
                    $product_groups[$key_group]['shipping_no_required'] = true;
                }

                $product_groups[$key_group]['shippings'] = [];
                $shippings_group = Shippings::getShippingsList($group, $lang_code, $area, ['get_images' => true]);

                foreach ($shippings_group as $shipping_id => $shipping) {
                    $shippings_groups[$shipping_id] = $shipping;

                    $product_group = $group;
                    if (!empty($shipping['service_params']['max_weight_of_box'])) {
                        $product_group = Shippings::repackProductsByWeight($group, $shipping['service_params']['max_weight_of_box']);
                    }

                    $shippings[] = array_merge($shipping, [
                        'package_info'      => $product_group['package_info'],
                        'package_info_full' => $product_group['package_info_full'],
                        'keys'              => [
                            'group_key'   => $key_group,
                            'shipping_id' => $shipping_id,
                            'company_id'  => $product_group['company_id']
                        ],
                    ]);

                    $shipping['group_key'] = $key_group;
                    $shipping['rate'] = 0;

                    // shipping is free when obtained via promotions, or group has free shipping and shipping method is suitable for free shipping
                    $shipping['free_shipping'] = in_array($shipping_id, $cart['free_shipping']) ||
                        $group['free_shipping'] && Shippings::isFreeShipping($shipping);

                    $product_groups[$key_group]['shippings'][$shipping_id] = $shipping;

                    // Adding a shipping method from the created order, if the shipping is not yet in the list.
                    if (!empty($cart['chosen_shipping']) && !empty($cart['shipping']) && !empty($cart['order_id'])) {
                        foreach ($cart['shipping'] as $cart_shipping) {
                            if (!isset($shippings_group[$cart_shipping['shipping_id']])) {
                                $shippings_group[$cart_shipping['shipping_id']] = $cart_shipping;
                            }
                        }
                    }
                }
            }

            if ($cart['calculate_shipping']) {
                $cart['product_groups'] = $product_groups;
                $rates = Shippings::calculateRates($shippings);
                Registry::set($shipping_cache_key, $rates);

                foreach ($rates as $rate) {
                    $group_key = $rate['keys']['group_key'];
                    $shipping_id = $rate['keys']['shipping_id'];
                    $company_id = $rate['keys']['company_id'];
                    if (!Shippings::isFreeShipping($shippings_groups[$shipping_id])) {
                        $package_info = $product_groups[$group_key]['package_info_full'];
                    } else {
                        $package_info = $product_groups[$group_key]['package_info'];
                    }
                    if (
                        $company_id
                        && Shippings::isSentByMarketplace($shippings_groups[$shipping_id])
                    ) {
                        $rate['price'] = 0;
                    }
                    if (!empty($product_groups[$group_key]['shippings'][$shipping_id]['rate_info'])) {
                        if ($rate['price'] !== false) {
                            $rate['price'] += !empty($package_info['shipping_freight'])
                                ? $package_info['shipping_freight']
                                : 0;
                            $product_groups[$group_key]['shippings'][$shipping_id]['rate'] = empty($product_groups[$group_key]['shippings'][$shipping_id]['free_shipping'])
                                ? $rate['price']
                                : 0;
                        } else {
                            unset($product_groups[$group_key]['shippings'][$shipping_id]);
                        }

                        if (!empty($rate['service_delivery_time'])) {
                            $product_groups[$group_key]['shippings'][$shipping_id]['service_delivery_time'] = $rate['service_delivery_time'];
                        }
                    } else {
                        unset($product_groups[$group_key]['shippings'][$shipping_id]);
                    }
                }
            }
            $cart['product_groups'] = $product_groups;
        }

        $product_groups = &$cart['product_groups'];

        /**
         * Executes when the cart content is calculated after the shipping rates are calculated,
         * allows you to modify the cart and the shipping list.
         *
         * @param array    $cart                  Cart data
         * @param array    $auth                  Authentication data
         * @param string   $calculate_shipping    1-letter flag indicating how to calculate the shipping cost:
         *                                        A - calculate all available methods
         *                                        E - calculate selected methods only (from cart[shipping])
         *                                        S - skip calculation
         * @param bool     $calculate_taxes       Whether taxes should be calculated
         * @param string   $options_style         1-letter flag indicating how to obtain options information:
         *                                        F - full
         *                                        S - skip selection
         *                                        I - info
         * @param bool     $apply_cart_promotions Whether promotions should be applied to the cart
         * @param string   $lang_code             Two-letter language code
         * @param string   $area                  Single-letter area code
         * @param array    $cart_products         Cart products
         * @param array    $product_groups        Products grouped by packages, suppliers, vendors
         */
        fn_set_hook(
            'calculate_cart_content_after_shipping_calculation',
            $cart,
            $auth,
            $calculate_shipping,
            $calculate_taxes,
            $options_style,
            $apply_cart_promotions,
            $lang_code,
            $area,
            $cart_products,
            $product_groups
        );

        // FIXME
        $cart['shipping_cost'] = 0;
        $cart['shipping'] = array();
        if (empty($cart['chosen_shipping'])) {
            $cart['chosen_shipping'] = array();
            if (
                fn_allowed_for('ULTIMATE')
                && Registry::get('settings.Checkout.display_shipping_step') != 'Y'
                && $cart['calculate_shipping']
            ) {
                foreach ($product_groups as $key_group => $group) {
                    if (!empty($group['shippings'])) {
                        $first_shipping = reset($group['shippings']);
                        $cart['chosen_shipping'][$key_group] = $first_shipping['shipping_id'];
                    }
                }
            }
        }

        $count_shipping_failed = 0;
        foreach ($product_groups as $key_group => $group) {
            if ($cart['calculate_shipping'] && (
                    !isset($cart['chosen_shipping'][$key_group]) ||
                    (empty($cart['keep_chosen_shipping']) && empty($group['shippings'][$cart['chosen_shipping'][$key_group]]))
                ) && (
                    !$group['free_shipping'] ||
                    $group['all_free_shipping']
                )
            ) {
                $cart['chosen_shipping'][$key_group] = key($group['shippings']);
            }

            if (
                $group['shipping_no_required']
                || $group['shipping_by_marketplace']
            ) {
                unset($cart['chosen_shipping'][$key_group]);
            }

            if (!isset($cart['chosen_shipping'][$key_group]) && (!$group['shipping_no_required'] && !$group['shipping_by_marketplace'])) {
                $count_shipping_failed++;
                $cart['company_shipping_failed'] = true;
            }

            foreach ($group['shippings'] as $shipping_id => $shipping) {
                if (isset($cart['chosen_shipping'][$key_group]) && $cart['chosen_shipping'][$key_group] == $shipping_id) {
                    $cart['shipping_cost'] += $shipping['rate'];
                }
            }

            if (!empty($group['shippings']) && isset($cart['chosen_shipping'][$key_group]) && isset($group['shippings'][$cart['chosen_shipping'][$key_group]])) {
                $shipping = $group['shippings'][$cart['chosen_shipping'][$key_group]];
                $shipping_id = $shipping['shipping_id'];
                if (empty($cart['shipping'][$shipping_id])) {
                    $cart['shipping'][$shipping_id] = $shipping;
                    $cart['shipping'][$shipping_id]['rates'] = array();
                }
                $cart['shipping'][$shipping_id]['rates'][$key_group] = $shipping['rate'];
            }
        }
        $cart['display_shipping_cost'] = $cart['shipping_cost'];

        if (!empty($product_groups) && count($product_groups) == $count_shipping_failed) {
            $cart['shipping_failed'] = true;
        }

        $cart['chosen_shipping_disabled'] = false;
        foreach ($cart['chosen_shipping'] as $key_group => $shipping_id) {
            if (!empty($product_groups[$key_group]) && !empty($product_groups[$key_group]['shippings'][$shipping_id])) {
                $shipping = $product_groups[$key_group]['shippings'][$shipping_id];
                $shipping['group_name'] = $product_groups[$key_group]['name'];
                $product_groups[$key_group]['chosen_shippings'] = array($shipping);
            } else {
                if (empty($cart['keep_chosen_shipping'])) {
                    unset($cart['chosen_shipping'][$key_group]);
                } else {
                    $cart['chosen_shipping_disabled'] = true;
                }
            }
        }

        fn_apply_stored_shipping_rates($cart);

        fn_set_hook('calculate_cart_taxes_pre', $cart, $cart_products, $product_groups, $calculate_taxes, $auth);

        $calculated_taxes_summary = [];

        foreach ($product_groups as $key_group => &$group) {
            foreach ($group['products'] as $cart_id => $product) {
                if (!empty($cart_products[$cart_id])) {
                    $group['products'][$cart_id] = $cart_products[$cart_id];
                }
            }

            // Calculate taxes
            if ($calculate_taxes) {
                $calculated_taxes = fn_calculate_taxes($cart, $key_group, $group['products'], $group['shippings'], $auth);
                if (!empty($group['chosen_shippings'])) {
                    foreach ($group['chosen_shippings'] as &$chosen_shipping) {
                        // phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit
                        if (isset($group['shippings'][$chosen_shipping['shipping_id']]['taxed_price'])) {
                            $chosen_shipping['taxed_price'] = $group['shippings'][$chosen_shipping['shipping_id']]['taxed_price'];
                        }
                    }
                    unset($chosen_shipping);
                }
                if (fn_get_customer_tax_exempt_status($cart, $auth)) {
                    continue;
                }
                foreach ($calculated_taxes as $tax_id => $tax) {
                    if (empty($calculated_taxes_summary[$tax_id])) {
                        $calculated_taxes_summary[$tax_id] = $tax;
                    } else {
                        if (isset($calculated_taxes_summary[$tax_id]['tax_subtotal'], $tax['applies']['S'])) {
                            $calculated_taxes_summary[$tax_id]['tax_subtotal'] += $tax['applies']['S'];
                        }
                        if (isset($calculated_taxes_summary[$tax_id]['applies']['S'], $tax['applies']['S'])) {
                            $calculated_taxes_summary[$tax_id]['applies']['S'] += $tax['applies']['S'];
                        }
                        if (empty($group['marketplace_shipping'])) {
                            $calculated_taxes_summary[$tax_id]['tax_subtotal'] += $tax['applies']['P'];
                            $calculated_taxes_summary[$tax_id]['applies']['P'] += $tax['applies']['P'];
                        } elseif (isset($tax['applies']['items']['S'], $calculated_taxes_summary[$tax_id]['applies']['items']['S'])) {
                            $calculated_taxes_summary[$tax_id]['applies']['items']['S'] =
                                array_merge(
                                    $calculated_taxes_summary[$tax_id]['applies']['items']['S'],
                                    $tax['applies']['items']['S']
                                );
                        }
                    }
                }
            } elseif ($cart['stored_taxes'] !== 'Y') {
                $cart['taxes'] = $cart['tax_summary'] = [];
            }
        }
        unset($group);

        fn_apply_calculated_taxes($calculated_taxes_summary, $cart);

        $shipping_rates = [];

        /**
         * Executes after taxes are calculated when calculating cart content.
         *
         * @param array $cart
         * @param array $cart_products
         * @param array $shipping_rates Deprecated: is always an empty array
         * @param bool  $calculate_taxes
         * @param array $auth
         */
        fn_set_hook('calculate_cart_taxes_post', $cart, $cart_products, $shipping_rates, $calculate_taxes, $auth);

        $cart['subtotal'] = $cart['display_subtotal'] = 0;

        fn_update_cart_data($cart, $cart_products);

        foreach ($cart['products'] as $product_code => $product) {
            foreach ($product_groups as $key_group => $group) {
                if (in_array($product_code, array_keys($group['products']))) {
                    $product_groups[$key_group]['products'][$product_code] = $product;
                }
            }
        }

        // Calculate totals
        $display_cart_prices_with_taxes =
            YesNo::toBool(Registry::get('settings.Appearance.cart_prices_w_taxes'))
            && !fn_get_customer_tax_exempt_status($cart, $auth);

        foreach ($product_groups as $key_group => $group) {
            if (isset($group['marketplace_shipping'])) {
                continue;
            }
            foreach ($group['products'] as $product_code => $product) {
                if (!isset($cart_products[$product_code])) {
                    continue;
                }
                $_tax = (!empty($product['tax_summary']) ? ($product['tax_summary']['added'] / $product['amount']) : 0);
                $is_stored_price = !empty($product['stored_price']) && YesNo::isTrue($product['stored_price']);
                if (
                    !$is_stored_price
                    && fn_get_customer_tax_exempt_status($cart, $auth)
                ) {
                    $included_tax = (!empty($product['tax_summary'])) ? $product['tax_summary']['included'] / $product['amount'] : 0;
                    $cart_products[$product_code]['price'] -= $included_tax;
                    $cart_products[$product_code]['base_price'] -= $included_tax;
                }

                $cart_products[$product_code]['display_price'] = $cart_products[$product_code]['price'] + ($display_cart_prices_with_taxes ? $_tax : 0);
                $cart_products[$product_code]['subtotal'] = $cart_products[$product_code]['price'] * $product['amount'];
                $cart_products[$product_code]['display_subtotal'] = $cart_products[$product_code]['display_price'] * $product['amount'];

                if (!empty($product['tax_summary'])) {
                    $cart_products[$product_code]['tax_summary'] = $product['tax_summary'];
                }

                $cart['subtotal'] += $cart_products[$product_code]['subtotal'];
                $cart['display_subtotal'] += $cart_products[$product_code]['display_subtotal'];

                $cart['products'][$product_code]['base_price'] = $cart_products[$product_code]['base_price'];

                $product_groups[$key_group]['products'][$product_code]['base_price'] = $cart_products[$product_code]['base_price'];

                if (!$is_stored_price) {
                    $cart['products'][$product_code]['price'] = $cart_products[$product_code]['price'];
                    $cart['products'][$product_code]['display_price'] = $cart_products[$product_code]['display_price'];

                    $product_groups[$key_group]['products'][$product_code]['price'] = $cart_products[$product_code]['price'];
                    $product_groups[$key_group]['products'][$product_code]['display_price'] = $cart_products[$product_code]['display_price'];
                }

                $product_groups[$key_group]['products'][$product_code]['main_category'] = $cart_products[$product_code]['main_category'];

                if (!fn_get_customer_tax_exempt_status($cart, $auth)) {
                    $cart['tax_subtotal'] += (!empty($product['tax_summary']) ? ($product['tax_summary']['added']) : 0);
                }
                $cart['total'] += ($cart_products[$product_code]['price'] - 0) * $product['amount'];

                if (!empty($product['discount'])) {
                    $cart['discount'] += $product['discount'] * $product['amount'];
                }
            }
        }

        if (Registry::get('settings.Checkout.tax_calculation') == 'subtotal') {
            $cart['tax_subtotal'] += (!empty($cart['tax_summary']['added']) ? ($cart['tax_summary']['added']) : 0);
        }

        $cart['subtotal'] = fn_format_price($cart['subtotal']);
        $cart['display_subtotal'] = fn_format_price($cart['display_subtotal']);

        $cart['total'] += $cart['tax_subtotal'];

        $cart['total'] = fn_format_price($cart['total'] + $cart['shipping_cost']);

        $cart['discounted_subtotal'] = $cart['subtotal'];

        if (!empty($cart['subtotal_discount'])) {
            $cart['discounted_subtotal'] = $cart['subtotal'] - ($cart['subtotal_discount'] < $cart['subtotal'] ? $cart['subtotal_discount'] : $cart['subtotal']);
            $cart['total'] -= ($cart['subtotal_discount'] < $cart['total']) ? $cart['subtotal_discount'] : $cart['total'];
        }
    }

    /**
     * Processes cart data after calculating all prices and other data (taxes, shippings etc)
     *
     * @param array  $cart               Cart data
     * @param array  $cart_products      Cart products
     * @param array  $auth               Auth data
     * @param string $calculate_shipping // 1-letter flag
     *      A - calculate all available methods
     *      E - calculate selected methods only (from cart[shipping])
     *      S - skip calculation
     * @param bool $calculate_taxes       Flag determines if taxes should be calculated
     * @param bool $apply_cart_promotions Flag determines if promotions should be applied to the cart
     */
    fn_set_hook('calculate_cart', $cart, $cart_products, $auth, $calculate_shipping, $calculate_taxes, $apply_cart_promotions);

    if ($cart['calculate_shipping'] || empty($cart['product_groups'])) {
        $cart['product_groups'] = $product_groups;
    }

    // FIXME: Backward compatibility
    $cart['recalculate'] = false;

    // FIXME: Backward compatibility
    $cart['calculate_shipping'] = false;

    // If a customer changes his/her address outside the checkout page,
    // this hash will be used to trigger shipping recalculation
    if (isset($cart['user_data'])) {
        $cart['location_hash'] = fn_checkout_get_location_hash($cart['user_data']);
    }

    /**
     * Processes cart data after calculating all prices and other data (taxes, shippings etc) including products group
     *
     * @param array  $cart               Cart data
     * @param array  $auth               Auth data
     * @param string $calculate_shipping // 1-letter flag
     *      A - calculate all available methods
     *      E - calculate selected methods only (from cart[shipping])
     *      S - skip calculation
     * @param bool   $calculate_taxes Flag determines if taxes should be calculated
     * @param string $options_style   1-letter flag
     *      "F" - Full option information (with exceptions)
     *      "I" - Short info
     *      "" - "Source" info. Only ids array (option_id => variant_id)
     * @param bool  $apply_cart_promotions Flag determines if promotions should be applied to the cart
     * @param array $cart_products         Cart products
     * @param array $product_groups        Products grouped by packages, suppliers, vendors
     */
    fn_set_hook('calculate_cart_post', $cart, $auth, $calculate_shipping, $calculate_taxes, $options_style, $apply_cart_promotions, $cart_products, $product_groups);

    return array(
        $cart_products,
        $product_groups,
    );
}

/**
 * Return true if cart empty
 *
 * @param array $cart
 * @param bool|true $check_excluded
 * If true then products will on checked extra fields 'exclude_from_calculate' and 'parent'
 * @return bool
 */
function fn_cart_is_empty($cart, $check_excluded = true)
{
    $result = empty($cart['products']);

    if ($check_excluded && !$result) {
        $result = true;

        foreach ($cart['products'] as $v) {
            if (!isset($v['extra']['exclude_from_calculate']) && empty($v['extra']['parent'])) {
                $result = false;
                break;
            }
        }
    }

    /**
     * Change result of check cart is empty
     *
     * @param array $cart           Data of cart
     * @param bool  $result         Result of check cart is empty
     * @param bool  $check_excluded If true then products will on checked extra fields 'exclude_from_calculate' and 'parent'
     */
    fn_set_hook('is_cart_empty', $cart, $result, $check_excluded);

    return $result;
}

/**
 * Calculate total weight of products in cart
 *
 * @param array  $cart          cart information
 * @param array  $cart_products cart products
 * @param string $type          S - weight for shipping, A - all, C - all, except excluded from calculation
 *
 * @return int products weight
 */
function fn_get_products_weight($cart, $cart_products, $type = 'S')
{
    $weight = 0;

    if (is_array($cart_products)) {
        foreach ($cart_products as $k => $v) {
            if ($type == 'S') {
                if (fn_exclude_from_shipping_calculate($cart['products'][$k])) {
                    continue;
                }
            } elseif ($type == 'C') {
                if (isset($v['exclude_from_calculate'])) {
                    continue;
                }
            }

            if (isset($v['weight'])) {
                $weight += ($v['weight'] * $v['amount']);
            }
        }
    }

    return !empty($weight) ? sprintf("%.3f", $weight) : '0.001';
}

/**
 * Calculate total quantity of products in cart
 *
 * @param array  $cart          cart information
 * @param array  $cart_products cart products
 * @param string $type          S - quantity for shipping, A - all, C - all, except excluded from calculation
 *
 * @return int products quantity
 */
function fn_get_products_amount($cart, $cart_products, $type = 'S')
{
    $amount = 0;

    foreach ($cart_products as $k => $v) {
        if ($type == 'S') {
            if (fn_exclude_from_shipping_calculate($cart['products'][$k])) {
                continue;
            }
        } elseif ($type == 'C') {
            if (isset($v['exclude_from_calculate'])) {
                continue;
            }
        }

        $amount += $v['amount'];
    }

    /**
     * Executes after total quantity of products in cart is calculated,
     * allows you to modify the calculated amount.
     *
     * @param array  $cart          Cart contents
     * @param array  $cart_products Products in cart
     * @param string $type          Type of products to take into account: S - quantity for shipping, A - all, C - all, except excluded from calculation
     * @param int    $amout         Amount of products in cart
     */
    fn_set_hook('get_products_amount_post', $cart, $cart_products, $type, $amount);

    return $amount;
}

/**
 * Checks whether product should be excluded from shipping calculation
 *
 * @param array $product Product data
 *
 * @return bool
 */
function fn_exclude_from_shipping_calculate($product)
{
    $exclude = ($product['is_edp'] == 'Y' && $product['edp_shipping'] != 'Y')
        || $product['free_shipping'] == 'Y';

    fn_set_hook('exclude_from_shipping_calculation', $product, $exclude);

    return $exclude;
}

// Get Payment processor data
function fn_get_processor_data($payment_id)
{
    $pdata = db_get_row('SELECT processor_id, processor_params FROM ?:payments WHERE payment_id = ?i', $payment_id);
    if (empty($pdata)) {
        return false;
    }

    $processor_data = db_get_row('SELECT * FROM ?:payment_processors WHERE processor_id = ?i', $pdata['processor_id']);
    if (!empty($pdata['processor_params'])) {
        $processor_data['processor_params'] = unserialize($pdata['processor_params']);
    } else {
        $processor_data['processor_params'] = [];
    }

    $processor_data['currencies'] = (!empty($processor_data['currencies'])) ? explode(',', $processor_data['currencies']) : [];

    return $processor_data;
}

function fn_get_payment_templates($payment = array())
{
    $company_id = null;

    if (fn_allowed_for('ULTIMATE')) {
        if (!empty($payment['company_id'])) {
            $company_id = $payment['company_id'];
        } else {
            $company_id = Registry::ifGet('runtime.company_id', fn_get_default_company_id());
        }
    }

    $theme = Themes::areaFactory('C', $company_id);

    $dir_params = array(
        'dir' => 'templates/views/orders/components/payments',
        'get_dirs' => false,
        'get_files' => true,
        'extension' => '.tpl',
    );
    $view_templates[$dir_params['dir']] = $theme->getDirContents($dir_params, Themes::STR_MERGE);

    // Get addons templates as well
    foreach ((array) Registry::get('addons') as $addon_name => $data) {
        if ($data['status'] == 'A') {
            $dir_params['dir'] = "templates/addons/{$addon_name}/views/orders/components/payments";
            $view_templates[$dir_params['dir']] = $theme->getDirContents($dir_params, Themes::STR_MERGE, Themes::PATH_ABSOLUTE, Themes::USE_BASE);
        }
    }

    $payment_templates = array();
    foreach ($view_templates as $dir => $templates) {
        foreach ($templates as $file_name => $file_info) {
            $payment_templates[$file_name] = str_replace(
                Themes::factory($file_info['theme'])->getThemePath() . '/templates/',
                '',
                $file_info[Themes::PATH_ABSOLUTE]
            );
        }
    }

    return $payment_templates;
}

/**
 * Gets list of all available payment processors
 *
 * @param string $lang_code 2-letter language code
 *
 * @return array List of payment processors
 */
function fn_get_payment_processors($lang_code = CART_LANGUAGE)
{
    $fields = array(
        '?:payment_processors.processor_id',
        '?:payment_processors.processor',
        '?:payment_processors.type',
        '?:payment_processors.addon',
        '?:language_values.value AS description',
        'IF (ISNULL(?:addons.status), "A", ?:addons.status) AS processor_status',
    );

    $join = array(
        db_quote("LEFT JOIN ?:language_values ON ?:language_values.name = CONCAT('processor_description_', REPLACE(?:payment_processors.processor_script, '.php', '')) AND lang_code = ?s", $lang_code),
        db_quote('LEFT JOIN ?:addons ON ?:addons.addon = ?:payment_processors.addon'),
    );

    $condition = array(
        '1',
    );

    /**
     * Changes params to get payment processors
     *
     * @param string $lang_code 2-letter language code
     * @param array  $fields    List of fields for retrieving
     * @param array  $join      Array with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param array  $condition Array containing SQL-query condition possibly prepended with a logical operator AND
     *
     */
    fn_set_hook('get_payment_processors', $lang_code, $fields, $join, $condition);

    $processors = db_get_hash_array('SELECT ' . implode(', ', $fields) . ' FROM ?:payment_processors ' . implode(' ', $join) . ' WHERE ' . implode(' AND ', $condition) . ' ORDER BY processor', 'processor_id');

    /**
     * Changes selected payment processors
     *
     * @param string $lang_code  Language code
     * @param array  $processors Array of processors
     */
    fn_set_hook('get_payment_processors_post', $lang_code, $processors);

    return $processors;
}

/**
 * Get processor data by processor script
 *
 * @param string $processor_script name of processor script
 * @return (array) processor data
 */
function fn_get_processor_data_by_name($processor_script)
{
    $processor_data = db_get_row("SELECT * FROM ?:payment_processors WHERE processor_script = ?s", $processor_script);

    /**
     * Change processor data
     *
     * @param string $processor_script Processor script name
     * @param array  $processor_data   Processor data
     */
    fn_set_hook('get_processor_data_by_name', $processor_script, $processor_data);

    return $processor_data;
}

/**
 * Get payment method by processor_id
 *
 * @param string $processor_id
 * @param string $lang_code
 * @return (array) payment methods which use this processor
 */
function fn_get_payment_by_processor($processor_id, $lang_code = CART_LANGUAGE)
{
    $payment_methods = db_get_hash_array("SELECT ?:payments.payment_id, ?:payments.a_surcharge, ?:payments.p_surcharge, ?:payments.payment_category, ?:payment_descriptions.*, ?:payment_processors.type AS processor_type, ?:payments.status FROM ?:payments LEFT JOIN ?:payment_descriptions ON ?:payments.payment_id = ?:payment_descriptions.payment_id AND ?:payment_descriptions.lang_code = ?s LEFT JOIN ?:payment_processors ON ?:payment_processors.processor_id = ?:payments.processor_id WHERE ?:payments.processor_id = ?i ORDER BY ?:payments.position", 'payment_id', $lang_code, $processor_id);

    return $payment_methods;
}

/**
 * Get customer location or default location
 *
 * @param array $auth Auth data
 * @param array $cart Cart data
 * @param bool $billing Use billing fields
 * @return array
 */
function fn_get_customer_location($auth, $cart, $billing = false)
{
    $u_info = $s_info = array();
    $prefix = SHIPPING_ADDRESS_PREFIX . '_';
    if ($billing == true) {
        $prefix = BILLING_ADDRESS_PREFIX . '_';
    }

    $user_data = isset($cart['user_data'])
        ? $cart['user_data']
        : [];

    if (!empty($user_data)) {
        $u_info = $user_data;
    } elseif (!empty($auth['user_id'])) {
        $u_info = fn_get_user_info($auth['user_id'], true, $cart['profile_id']);
    }

    if (empty($u_info)) {
        foreach (Registry::get('settings.General') as $f_name => $f_value) {
            if (strpos($f_name, 'default_') === 0) {
                $f_name = substr($f_name, 8);
                $u_info[$prefix . $f_name] = $f_value;
            }
        }
    } else {
        $u_info = array_filter($u_info);

        $u_info += [
            $prefix . 'country' => Registry::get('settings.Checkout.default_country'),
            $prefix . 'state'   => Registry::get('settings.Checkout.default_state'),
            $prefix . 'city'    => Registry::get('settings.Checkout.default_city'),
        ];

        if (!isset($u_info[$prefix . 'zipcode'])
            && $u_info[$prefix . 'city'] === Registry::get('settings.Checkout.default_city')
        ) {
            $u_info[$prefix . 'zipcode'] = Registry::get('settings.Checkout.default_zipcode');
        }
    }

    foreach ($u_info as $field_name => $field_value) {
        if (strpos($field_name, $prefix) === 0) {
            $f_name = substr($field_name, 2);
            $s_info[$f_name] = !empty($field_value) ? $field_value : Registry::get('settings.General.default_' . $f_name);
        }
    }

    // Add residential address flag
    $s_info['address_type'] = (!empty($u_info['s_address_type'])) ? $u_info['s_address_type'] : 'residential';

    // Get First and Last names
    $u_info['firstname'] = !empty($u_info['firstname']) ? $u_info['firstname'] : 'John';
    $u_info['lastname'] = !empty($u_info['lastname']) ? $u_info['lastname'] : 'Doe';

    if ($prefix == 'b') {
        $s_info['firstname'] = (!empty($u_info['b_firstname'])) ? $u_info['b_firstname'] : $u_info['firstname'];
        $s_info['lastname'] = (!empty($u_info['b_lastname'])) ? $u_info['b_lastname'] : $u_info['lastname'];
    } else {
        $s_info['firstname'] = (!empty($u_info['s_firstname'])) ? $u_info['s_firstname'] : (!empty($u_info['b_firstname']) ? $u_info['b_firstname'] : $u_info['firstname']);
        $s_info['lastname'] = (!empty($u_info['s_lastname'])) ? $u_info['s_lastname'] : (!empty($u_info['b_lastname']) ? $u_info['b_lastname'] : $u_info['lastname']);
    }

    // Get country/state descriptions
    if (!empty($s_info['country'])) {
        $avail_country = db_get_field("SELECT COUNT(*) FROM ?:countries WHERE code = ?s AND status = 'A'", $s_info['country']);
        if (empty($avail_country)) {
            return array();
        }

        if (!empty($s_info['state'])) {
            $avail_state = db_get_field("SELECT COUNT(*) FROM ?:states WHERE country_code = ?s AND code = ?s AND status = 'A'", $s_info['country'], $s_info['state']);
            if (empty($avail_state)) {
                $s_info['state'] = '';
            }
        }
    }

    return $s_info;
}

/**
 * Calculate products and shipping taxes
 *
 * @param array $cart           Cart data
 * @param int|string $group_key      Group number
 * @param array $group_products Products data
 * @param array $shipping_rates
 * @param array $auth           Auth data
 *
 * @return array
 */
function fn_calculate_taxes(&$cart, $group_key, &$group_products, &$shipping_rates, $auth)
{
    /**
     * Prepare params before applying products and shipping taxes to cart
     *
     * @param array $cart           Cart data
     * @param array $group_products Products data
     * @param array $shipping_rates
     * @param array $auth           Auth data
     */
    fn_set_hook('calculate_taxes_pre', $cart, $group_products, $shipping_rates, $auth);

    $calculated_data = [];

    $has_tax_exempt = fn_get_customer_tax_exempt_status($cart, $auth);

    if (Registry::get('settings.Checkout.tax_calculation') === 'unit_price') {
        // Tax calculation method based on UNIT PRICE

        // Calculate product taxes
        foreach ($group_products as $k => $product) {
            $taxes = fn_get_product_taxes($k, $cart, $group_products);

            if (
                !isset($product['subtotal'])
                || empty($taxes)
            ) {
                continue;
            }

            if (
                (float) $product['price'] === (float) $product['subtotal']
                && (int) $product['amount'] !== 1
            ) {
                $price = fn_format_price($product['price']);
            } else {
                $price = fn_format_price($product['subtotal'] / $product['amount']);
            }

            $calculated_data['P_' . $k] = fn_calculate_tax_rates($taxes, $price, $product['amount'], $auth, $cart);

            $group_products[$k]['tax_summary'] = ['included' => 0, 'added' => 0, 'total' => 0]; // tax summary for 1 unit of product
            $cart['products'][$k]['tax_summary'] = ['included' => 0, 'added' => 0, 'total' => 0]; // tax summary for 1 unit of product

            // Apply taxes to product subtotal
            if (empty($calculated_data['P_' . $k])) {
                continue;
            }

            foreach ($calculated_data['P_' . $k] as $_k => $v) {
                $group_products[$k]['taxes'][$_k] = $v;
                $cart['products'][$k]['taxes'][$_k] = $v;
                if (YesNo::isFalse($taxes[$_k]['price_includes_tax'])) {
                    $group_products[$k]['tax_summary']['added'] += $v['tax_subtotal'];
                    $cart['products'][$k]['tax_summary']['added'] += $v['tax_subtotal'];
                } else {
                    $group_products[$k]['tax_summary']['included'] += $v['tax_subtotal'];
                    $cart['products'][$k]['tax_summary']['included'] += $v['tax_subtotal'];
                }
            }
            $group_products[$k]['tax_summary']['total'] = $group_products[$k]['tax_summary']['added'] + $group_products[$k]['tax_summary']['included'];
            $cart['products'][$k]['tax_summary']['total'] = $cart['products'][$k]['tax_summary']['added'] + $cart['products'][$k]['tax_summary']['included'];
        }

        // Calculate shipping taxes
        if (
            !empty($shipping_rates)
            && (
                empty($cart['stored_shipping'])
                || (
                    defined('ORDER_MANAGEMENT')
                    && !$has_tax_exempt
                )
            )
        ) {
            $display_cart_prices_with_taxes =
                YesNo::isTrue(Registry::get('settings.Appearance.cart_prices_w_taxes'))
                && !fn_get_customer_tax_exempt_status($cart, $auth);

            foreach ($shipping_rates as $shipping_id => $shipping) {
                $taxes = fn_get_shipping_taxes($shipping_id, $shipping_rates, $cart);

                if (!empty($taxes)) {

                    $shipping_rates[$shipping_id]['taxes'] = array();

                    $calculate_rate = true;

                    if (!empty($cart['chosen_shipping'][$group_key]) && $cart['chosen_shipping'][$group_key] == $shipping_id) {

                        $calculated_data['S_' . $group_key . '_' . $shipping_id] = fn_calculate_tax_rates($taxes, $shipping['rate'], 1, $auth, $cart);

                        if (!empty($calculated_data['S_' . $group_key . '_' . $shipping_id])) {
                            foreach ($calculated_data['S_' . $group_key . '_' . $shipping_id] as $__k => $__v) {
                                if (
                                    $display_cart_prices_with_taxes
                                    && !YesNo::toBool($taxes[$__k]['price_includes_tax'])
                                ) {
                                    $cart['display_shipping_cost'] += $__v['tax_subtotal'];
                                }

                                if (
                                    !$has_tax_exempt
                                    && !YesNo::toBool($taxes[$__k]['price_includes_tax'])
                                ) {
                                    $cart['tax_subtotal'] += $__v['tax_subtotal'];
                                }
                                //phpcs:ignore
                                if (YesNo::toBool($cart['stored_taxes'])) {
                                    $cart['taxes'][$__k]['applies']['S_' . $group_key . '_' . $shipping_id] = $__v['tax_subtotal'];
                                }
                            }

                            $shipping_rates[$shipping_id]['taxes']['S_' . $group_key . '_' . $shipping_id] = $calculated_data['S_' . $group_key . '_' . $shipping_id];
                            $calculate_rate = false;
                        }
                    }

                    if ($calculate_rate) {
                        $cur_shipping_rates = fn_calculate_tax_rates($taxes, $shipping['rate'], 1, $auth, $cart);
                        if (!empty($cur_shipping_rates)) {
                            $shipping_rates[$shipping_id]['taxes'] = $cur_shipping_rates;
                        }
                    }
                }
            }

            foreach ($shipping_rates as $shipping_id => $shipping) {
                // Calculate taxes for each shipping rate
                $taxes = fn_get_shipping_taxes($shipping_id, $shipping_rates, $cart);

                $shipping_rates[$shipping_id]['taxed_price'] = 0;
                unset($shipping_rates[$shipping_id]['inc_tax']);

                if (!empty($taxes)) {
                    $shipping_rates[$shipping_id]['taxes'] = array();

                    $tax = fn_calculate_tax_rates($taxes, fn_format_price($shipping['rate']), 1, $auth, $cart);

                    $shipping_rates[$shipping_id]['taxes'] = $tax;

                    if (!empty($tax)) {
                        foreach ($tax as $_tax) {
                            if (
                                $display_cart_prices_with_taxes
                                && YesNo::isFalse($_tax['price_includes_tax'])
                            ) {
                                $shipping_rates[$shipping_id]['taxed_price'] += $_tax['tax_subtotal'];
                            }

                            if (
                                !$has_tax_exempt
                                || isset($shipping_rates[$shipping_id]['rate_tax_exempt'])
                                || YesNo::isFalse($_tax['price_includes_tax'])
                            ) {
                                continue;
                            }

                            $shipping_rates[$shipping_id]['rate_tax_exempt'] = true;
                            $shipping_rates[$shipping_id]['rate'] -= $_tax['tax_subtotal'];
                            if (in_array($shipping_id, $cart['chosen_shipping'])) {
                                $cart['shipping_cost'] -= $_tax['tax_subtotal'];
                                $cart['display_shipping_cost'] -= $_tax['tax_subtotal'];
                            }
                            // phpcs:ignore
                            if (isset($cart['product_groups'][$group_key]['chosen_shippings'][$group_key]['rate'])) {
                                $cart['product_groups'][$group_key]['chosen_shippings'][$group_key]['rate'] = $shipping_rates[$shipping_id]['rate'];
                            }
                        }

                        $shipping_rates[$shipping_id]['inc_tax'] = $display_cart_prices_with_taxes;
                    }

                    if (!empty($shipping_rates[$shipping_id]['rate']) && $shipping_rates[$shipping_id]['taxed_price'] > 0) {
                        $shipping_rates[$shipping_id]['taxed_price'] += $shipping_rates[$shipping_id]['rate'];
                    }
                }
            }
        }
    } else {
        // Tax calculation method based on SUBTOTAL

        // Calculate discounted subtotal
        if (!isset($cart['subtotal_discount'])) {
            $cart['subtotal_discount'] = 0;
        }
        $discounted_subtotal = $cart['subtotal'] - $cart['subtotal_discount'];

        if ($discounted_subtotal < 0) {
            $discounted_subtotal = 0;
        }

        // Get discount distribution coefficient (DDC) between taxes
        if ($cart['subtotal'] > 0) {
            $ddc = $discounted_subtotal / $cart['subtotal'];
        } else {
            $ddc = 1;
        }

        //
        // Group subtotal by taxes
        //
        $subtotal = array();

        // Get products taxes
        foreach ($group_products as $cart_id => $product) {
            $taxes = fn_get_product_taxes($cart_id, $cart, $group_products);

            // This cleanup is required in order to avoid adding the same tax (product item tax) twice, in a rare case:
            // when taxes first calculated based on "Unit price" and then the setting switched to "Subtotal" and calculated again
            unset($group_products[$cart_id]['tax_summary'], $cart['products'][$cart_id]['tax_summary']);

            if (!empty($taxes)) {
                foreach ($taxes as $tax_id => $tax) {
                    if (empty($subtotal[$tax_id])) {
                        $subtotal[$tax_id] = fn_init_tax_subtotals($tax);
                    }

                    $_subtotal = ($product['price'] == $product['subtotal'] && $product['amount'] != 1) ? fn_format_price($product['price'] * $product['amount']) : $product['subtotal'];

                    $subtotal[$tax_id]['subtotal'] += $_subtotal;
                    $subtotal[$tax_id]['applies']['P'] += $_subtotal;
                    $subtotal[$tax_id]['applies']['items']['P'][$cart_id] = true;

                    if (isset($product['company_id'])) {
                        if (!isset($subtotal[$tax_id]['group'][$group_key])) {
                            $subtotal[$tax_id]['group'][$group_key]['products'] = 0;
                        }
                        $subtotal[$tax_id]['group'][$group_key]['products'] += $_subtotal;
                        $priority_stack['products'][$group_key] = -1;
                        $applied_taxes['products'][$group_key] = 0;
                    }
                }
            }
        }

        // Get shipping taxes
        if (!empty($shipping_rates)) {
            foreach ($shipping_rates as $shipping_id => $shipping) {
                // Calculate taxes for each shipping rate
                $taxes = fn_get_shipping_taxes($shipping_id, $shipping_rates, $cart);

                $shipping_rates[$shipping_id]['taxed_price'] = 0;
                unset($shipping_rates[$shipping_id]['inc_tax']);

                // Display shipping with taxes at cart/checkout page
                if (!empty($taxes)) {
                    $shipping_rates[$shipping_id]['taxes'] = array();

                    $tax = fn_calculate_tax_rates($taxes, fn_format_price($shipping['rate']), 1, $auth, $cart);
                    $shipping_rates[$shipping_id]['taxes'] = $tax;

                    if (!empty($tax) && Registry::get('settings.Appearance.cart_prices_w_taxes') == 'Y') {
                        foreach ($tax as $_id => $_tax) {
                            if ($_tax['price_includes_tax'] != 'Y') {
                                $shipping_rates[$shipping_id]['taxed_price'] += $_tax['tax_subtotal'];
                            }
                        }
                        $shipping_rates[$shipping_id]['inc_tax'] = !fn_get_customer_tax_exempt_status($cart, $auth);
                    }

                    if (!empty($shipping_rates[$shipping_id]['rate']) && $shipping_rates[$shipping_id]['taxed_price'] > 0) {
                        $shipping_rates[$shipping_id]['taxed_price'] += $shipping_rates[$shipping_id]['rate'];
                    }
                }

                if (!isset($cart['chosen_shipping'][$group_key]) || $cart['chosen_shipping'][$group_key] != $shipping_id) {
                    continue;
                }

                // Add shipping taxes to "tax" array
                if (!empty($taxes)) {
                    foreach ($taxes as $tax_id => $tax) {
                        if (empty($subtotal[$tax_id])) {
                            $subtotal[$tax_id] = fn_init_tax_subtotals($tax);
                        }

                        $subtotal[$tax_id]['subtotal'] += $shipping['rate'];
                        $subtotal[$tax_id]['applies']['S'] += $shipping['rate'];
                        $subtotal[$tax_id]['applies']['items']['S'][$group_key][$shipping_id] = true;

                        $priority_stack['shippings'][$group_key] = -1;
                        $applied_taxes['shippings'][$group_key] = 0;
                    }
                }
            }
        }

        if (!empty($subtotal)) {
            $subtotal = fn_sort_array_by_key($subtotal, 'priority');
        }

        // Apply DDC and calculate tax rates
        $calculated_taxes = array();

        if (empty($priority_stack)) {
            $priority_stack['products'][0] = -1;
            $priority_stack['shippings'][0] = -1;
            $applied_taxes['products'][0] = 0;
            $applied_taxes['shippings'][0] = 0;
        }

        foreach ($subtotal as $tax_id => $_st) {
            if (empty($_st['tax_id'])) {
                $_st['tax_id'] = $tax_id;
            }

            $product_tax = fn_calculate_tax_rates(array($_st), fn_format_price($_st['applies']['P'] * $ddc), 1, $auth, $cart);
            $shipping_tax = fn_calculate_tax_rates(array($_st), fn_format_price($_st['applies']['S']), 1, $auth, $cart);

            if (empty($product_tax) && empty($shipping_tax)) {
                continue;
            }

            if (empty($_st['groups'])) {
                $_st['groups'][0]['products'] = $_st['applies']['P'];
                $_st['groups'][0]['shippings'] = $_st['applies']['S'];
            }

            foreach ($_st['groups'] as $group_key => $applies) {
                $apply_tax_stack = array(
                    'products' => 0,
                    'shippings' => 0,
                );

                if (!isset($priority_stack['products'][$group_key])) {
                    $priority_stack['products'][$group_key] = -1;
                }
                if (!isset($priority_stack['shippings'][$group_key])) {
                    $priority_stack['shippings'][$group_key] = -1;
                }

                if ($priority_stack['products'][$group_key] < 0 && !empty($applies['products'])) {
                    $priority_stack['products'][$group_key] = $_st['priority'];

                } elseif (!empty($applies['products']) && $priority_stack['products'][$group_key] != $_st['priority']) {
                    $apply_tax_stack['products'] = $applied_taxes['products'][$group_key];
                    $priority_stack['products'][$group_key] = $_st['priority'];
                }

                if ($priority_stack['shippings'][$group_key] < 0 && !empty($applies['shippings'])) {
                    $priority_stack['shippings'][$group_key] = $_st['priority'];

                } elseif (!empty($applies['shippings']) && $priority_stack['shippings'][$group_key] != $_st['priority']) {
                    $apply_tax_stack['shippings'] = $applied_taxes['shippings'][$group_key];
                    $priority_stack['shippings'][$group_key] = $_st['priority'];
                }

                if (empty($calculated_data[$tax_id])) {
                    $calculated_data[$tax_id] = empty($product_tax) ? reset($shipping_tax) : reset($product_tax);
                }

                if (!empty($applies['products'])) {
                    $products_tax = fn_calculate_tax_rates(array($_st), fn_format_price($applies['products'] * $ddc + $apply_tax_stack['products']), 1, $auth, $cart);
                    if (empty($product_tax)) {
                        $products_tax[$tax_id]['tax_subtotal'] = 0;
                    }
                } else {
                    $products_tax[$tax_id]['tax_subtotal'] = 0;
                }

                if (!empty($applies['shippings'])) {
                    $shippings_tax = fn_calculate_tax_rates(array($_st), fn_format_price($applies['shippings'] + $apply_tax_stack['shippings']), 1, $auth, $cart);
                } else {
                    $shippings_tax[$tax_id]['tax_subtotal'] = 0;
                }

                if (!isset($applied_taxes['products'][$group_key])) {
                    $applied_taxes['products'][$group_key] = 0;
                }
                if (!isset($applied_taxes['shippings'][$group_key])) {
                    $applied_taxes['shippings'][$group_key] = 0;
                }

                if ($_st['price_includes_tax'] != 'Y') {
                    $applied_taxes['products'][$group_key] += $products_tax[$tax_id]['tax_subtotal'];
                    $applied_taxes['shippings'][$group_key] += $shippings_tax[$tax_id]['tax_subtotal'];
                }

                if (!isset($calculated_data[$tax_id]['applies']['P'])) {
                    $calculated_data[$tax_id]['applies']['P'] = 0;
                }
                if (!isset($calculated_data[$tax_id]['applies']['S'])) {
                    $calculated_data[$tax_id]['applies']['S'] = 0;
                }
                $calculated_data[$tax_id]['applies']['P'] += isset($products_tax[$tax_id]['tax_subtotal']) ? $products_tax[$tax_id]['tax_subtotal'] : 0;
                $calculated_data[$tax_id]['applies']['S'] += $shippings_tax[$tax_id]['tax_subtotal'];
                $calculated_data[$tax_id]['applies']['items'] = $_st['applies']['items'];
                $calculated_data[$tax_id]['tax_subtotal'] = $calculated_data[$tax_id]['applies']['P'] + $calculated_data[$tax_id]['applies']['S'];
            }
        }
    }

    /**
     * Processes products data after applying products and shipping taxes to cart)
     *
     * @param array $cart            Cart data
     * @param array $group_products  Products data
     * @param array $shipping_rates  Shipping rates data
     * @param array $auth            Auth data
     * @param array $calculated_data Payment taxes data
     */
    fn_set_hook('calculate_taxes_post', $cart, $group_products, $shipping_rates, $auth, $calculated_data);

    return $calculated_data;
}

/**
 * Calculate payment surcharge taxes, calculated separately from products and shipping taxes
 * becuase payment surcharge is calculated based on cart totals.
 *
 * @param array $cart Cart data
 * @param array $auth Auth data
 * @return boolean always false
 */
function fn_calculate_payment_taxes(&$cart, $auth)
{
    /**
     * Prepare params before applying payment taxes to cart
     *
     * @param array $cart Cart data
     * @param array $auth Auth data
     */
    fn_set_hook('calculate_payment_taxes_pre', $cart, $auth);

    if (fn_allowed_for('MULTIVENDOR') && fn_take_payment_surcharge_from_vendor($cart['products'])) {
        return false;
    }
    $calculated_data = array();

    if (Registry::get('settings.Checkout.tax_calculation') == 'unit_price') {
        // Tax calculation method based on UNIT PRICE

        $display_cart_prices_with_taxes = YesNo::isTrue(Registry::get('settings.Appearance.cart_prices_w_taxes'))
            && !fn_get_customer_tax_exempt_status($cart, $auth);

        if (!empty($cart['payment_id']) && !empty($cart['payment_surcharge'])) {
            $payment_id = $cart['payment_id'];
            $taxes = fn_get_payment_taxes($payment_id, $cart);

            if (!empty($taxes)) {
                $calculated_data['PS_' . $payment_id] = fn_calculate_tax_rates($taxes, fn_format_price($cart['payment_surcharge']), 1, $auth, $cart);

                if (!empty($calculated_data['PS_' . $payment_id])) {
                    foreach ($calculated_data['PS_' . $payment_id] as $__k => $__v) {
                        if (!YesNo::toBool($taxes[$__k]['price_includes_tax'])) {
                            if ($display_cart_prices_with_taxes) {
                                $cart['payment_surcharge'] += $__v['tax_subtotal'];
                            }
                        } elseif (fn_get_customer_tax_exempt_status($cart, $auth)) {
                            $cart['payment_surcharge'] -= $__v['tax_subtotal'];
                        }
                    }
                }
            }
        }

    } else {
        if (!empty($cart['payment_id']) && !empty($cart['payment_surcharge'])) {
            $taxes = fn_get_payment_taxes($cart['payment_id'], $cart);
            $priority = 0;
            $calc_surcharge = $cart['payment_surcharge'];
            $taxed_surcharge =  $cart['payment_surcharge'];
            if (!empty($taxes)) {
                foreach ($taxes as $tax_id => $tax) {
                    if ($tax['priority'] > $priority) {
                        $calc_surcharge = $taxed_surcharge;
                    }

                    $calculated_tax = fn_calculate_tax_rates(array($tax), fn_format_price($calc_surcharge), 1, $auth, $cart);
                    if (empty($calculated_tax[$tax_id])) {
                        continue;
                    }
                    $calculated_data[$tax_id] = fn_init_tax_subtotals($calculated_tax[$tax_id]);

                    $calculated_data[$tax_id]['tax_subtotal'] = $calculated_tax[$tax_id]['tax_subtotal'];
                    $calculated_data[$tax_id]['applies']['PS'] = $calculated_tax[$tax_id]['tax_subtotal'];
                    $calculated_data[$tax_id]['applies']['items']['PS'][$cart['payment_id']] = true;
                    $taxed_surcharge += $calculated_tax[$tax_id]['tax_subtotal'];
                }
            }
        }

    }

    /**
     * Processes payment data taxes after applying payment taxes to cart
     *
     * @param array $cart            Cart data
     * @param array $auth            Auth data
     * @param array $calculated_data Payment data taxes
     */
    fn_set_hook('calculate_payment_taxes_post', $cart, $auth, $calculated_data);

    if (!fn_get_customer_tax_exempt_status($cart, $auth)) {
        fn_apply_payment_taxes($calculated_data, $cart);
    }

    return false;
}

/**
 * Apply payment surcharge taxes to cart, payment surcharge taxes calculated and applied
 * separately from products and shipping taxes
 * cart taxes are supposed to keep shippings and products taxes
 *
 * @param array $calculated_data payment data taxes
 * @param array $cart cart data
 * @return boolean always true
 */
function fn_apply_payment_taxes($calculated_data, &$cart)
{
    $tax_added = 0;

    if (empty($cart['taxes'])) {
        $cart['taxes'] = array();
        $cart['tax_subtotal'] = 0;
    }
    if (!empty($calculated_data)) {
        if (Registry::get('settings.Checkout.tax_calculation') == 'unit_price') {
            // Based on the unit price
            foreach ($calculated_data as $id => $_taxes) {
                if (empty($_taxes)) {
                    continue;
                }
                foreach ($_taxes as $k => $v) {
                    if (empty($cart['taxes'][$k])) {
                        $cart['taxes'][$k] = $v;
                        $cart['taxes'][$k]['tax_subtotal'] = 0;
                    }
                    $cart['taxes'][$k]['applies'][$id] = $v['tax_subtotal'];
                    $cart['taxes'][$k]['tax_subtotal'] += $v['tax_subtotal'];

                    if ($v['price_includes_tax'] == 'N') {
                        if (Registry::get('settings.Appearance.cart_prices_w_taxes') != 'Y') {
                            $tax_added += $v['tax_subtotal'];
                        }
                        $cart['tax_subtotal'] += $v['tax_subtotal'];
                    }
                }
            }
        } else {
            if (empty($cart['tax_summary'])) {
                // Based on the order subtotal
                $cart['tax_summary'] = array(
                    'included' => 0,
                    'added' => 0,
                    'total' => 0,
                );
            }

            foreach ($calculated_data as $tax_id => $v) {
                if (!empty($cart['taxes'][$tax_id])) {
                    $cart['taxes'][$tax_id]['applies']['PS'] =  $v['applies']['PS'];
                    $cart['taxes'][$tax_id]['applies']['items']['PS'] =  $v['applies']['items']['PS'];
                    $cart['taxes'][$tax_id]['tax_subtotal'] += $v['tax_subtotal'];
                } else {
                    $cart['taxes'][$tax_id] = $v;
                }

                if ($v['price_includes_tax'] == 'Y') {
                    $cart['tax_summary']['included'] += $v['tax_subtotal'];
                } else {
                    $cart['tax_summary']['added'] += $v['tax_subtotal'];
                    $tax_added += $v['tax_subtotal'];
                }

                $cart['tax_summary']['total'] += $v['tax_subtotal'];
            }
        }
    }
    if (!empty($tax_added)) {
        $cart['total'] = fn_format_price($cart['total'] + $tax_added);
    }

    /**
     * Apply payment taxes (running after fn_apply_payment_taxes function)
     *
     * @param array $calculated_data payment data taxes
     * @param array $cart            cart data
     */
    fn_set_hook('apply_payment_taxes_post', $calculated_data, $cart);

    return true;
}

/**
 * Gets correct tax exempt status for cart calculations.
 *
 * @param array<string, array<string, string>> $cart Cart information.
 * @param array<string, string>|null           $auth Authentication info.
 * @param string                               $area Current site area.
 *
 * @return bool
 */
function fn_get_customer_tax_exempt_status(array $cart, ?array $auth, string $area = AREA): bool
{
    if (empty($auth) && empty($cart)) {
        return false;
    }

    if (!isset($auth['tax_exempt']) && !isset($cart['user_data']['tax_exempt'])) {
        return false;
    }

    if (SiteArea::isStorefront($area)) {
        return isset($auth['tax_exempt'])
            ? YesNo::toBool($auth['tax_exempt'])
            : YesNo::toBool($cart['user_data']['tax_exempt']);
    }

    return isset($cart['user_data']['tax_exempt'])
        ? YesNo::toBool($cart['user_data']['tax_exempt'])
        : YesNo::toBool($auth['tax_exempt']);
}

/**
 * Init taxes array: add additional params to tax array for calculation
 *
 * @param array $tax base tax array
 * @return array array with inited params
 */
function fn_init_tax_subtotals($tax)
{
    $tax['subtotal'] = $tax['applies']['P'] = $tax['applies']['S'] = 0;
    $tax['applies']['items']['P'] = $tax['applies']['items']['S'] = array();

    /**
     * Init tax subtotals (running after fn_init_tax_subtotals function)
     *
     * @param array $tax tax array
     */
    fn_set_hook('init_tax_subtotals_post', $tax);

    return $tax;
}

function fn_get_product_taxes($idx, $cart, $cart_products)
{
    $taxes = [];
    if (YesNo::toBool($cart['stored_taxes'])) {
        $_idx = '';
        if (isset($cart['products'][$idx]['original_product_data']['cart_id'])) {
            $_idx = $cart['products'][$idx]['original_product_data']['cart_id'];
        }

        foreach ((array) $cart['taxes'] as $_k => $_v) {
            if (isset($_v['applies']['P_'.$idx]) || isset($_v['applies']['items']['P'][$idx]) || isset($_v['applies']['P_'.$_idx]) || isset($_v['applies']['items']['P'][$_idx])) {
                $taxes[$_k] = $_v;
            }
        }
    }
    if (
        isset($cart_products[$idx]['tax_ids'])
        && (
            !YesNo::toBool($cart['stored_taxes'])
            || empty($taxes)
        )
    ) {
        $taxes = fn_get_set_taxes($cart_products[$idx]['tax_ids']);
    }

    return $taxes;
}

/**
 * Get payment taxes
 *
 * @param integer $payment_id payment method id
 * @param array $cart cart data
 * @return array array with taxes
 */
function fn_get_payment_taxes($payment_id, $cart)
{
    // get current tax ids
    $tax_ids = db_get_field("SELECT tax_ids FROM ?:payments WHERE payment_id = ?i", $payment_id);
    if (!empty($tax_ids)) {
        $taxes = fn_get_set_taxes($tax_ids);

        // apply new rates if exists
        if ($cart['stored_taxes'] == 'Y' && !empty($cart['stored_taxes_data'])) {

            foreach ((array) $cart['stored_taxes_data'] as $_k => $_v) {

                if (!empty($taxes[$_k]) && (!empty($_v['applies']['PS_'.$payment_id]) || !empty($_v['applies']['items']['PS'][$payment_id]))) {
                    if (!empty($_v['rate_value']) && !empty($_v['rate_type'])) {
                        $taxes[$_k]['rate_value'] = $_v['rate_value'];
                        $taxes[$_k]['rate_type'] = $_v['rate_type'];
                    }
                }
            }
        }

    }

    /**
     * Init payment taxes (running after fn_get_payment_taxes function)
     *
     * @param integer $payment_id payment method id
     * @param array   $cart       cart data
     * @param array   $taxes      array with taxes
     */
    fn_set_hook('get_payment_taxes_post', $payment_id, $cart, $taxes);

    return $taxes;
}

function fn_get_shipping_taxes($shipping_id, $shipping_rates, $cart)
{
    $tax_ids = array();
    if (defined('ORDER_MANAGEMENT')) {
        $shipping_ids = array();
        foreach ($shipping_rates as $shipping) {
            $shipping_ids[] = $shipping['shipping_id'];
        }
        $_taxes = db_get_hash_single_array("SELECT tax_ids, shipping_id FROM ?:shippings WHERE shipping_id IN (?n)", array('shipping_id', 'tax_ids'), $shipping_ids);

        if (!empty($_taxes)) {
            foreach ($_taxes as $_ship => $_tax) {
                if (!empty($_tax)) {
                    $_tids = explode(',', $_tax);
                    foreach ($_tids as $_tid) {
                        $tax_ids[$_ship][$_tid] = $_tax;
                    }
                }
            }
        }
    }

    if ($cart['stored_taxes'] == 'Y') {
        $taxes = array();

        foreach ((array) $cart['taxes'] as $_k => $_v) {
            isset($_v['applies']['items']['S'][$shipping_id]) ? $exists = true : $exists = false;
            foreach ($_v['applies'] as $aid => $av) {
                if (strpos($aid, 'S_' . $shipping_id . '_') !== false) {
                    $exists = true;

                }
            }
            if ($exists == true || (!empty($tax_ids[$shipping_id]) && !empty($tax_ids[$shipping_id][$_k]))) {
                $taxes[$_k] = $_v;
                $taxes[$_k]['applies'] = array();
            }
        }
    } else {
        $taxes = array();
        $tax_ids = db_get_field("SELECT tax_ids FROM ?:shippings WHERE shipping_id = ?i", $shipping_id);
        if (!empty($tax_ids)) {
            $taxes = db_get_hash_array("SELECT tax_id, address_type, priority, price_includes_tax, regnumber FROM ?:taxes WHERE tax_id IN (?n) AND status = 'A' ORDER BY priority", 'tax_id', explode(',', $tax_ids));
        }
    }

    /**
     * Executes after shipping taxes are retrieved, allows you to modify the shipping taxes
     *
     * @param int       $shipping_id        Shipping method identifier
     * @param array     $shipping_rates     Array of shipping methods
     * @param array     $cart               Array of the cart contents and user information necessary for purchase
     * @param array     $taxes              Array of shipping taxes
     */
    fn_set_hook('get_shipping_taxes_post', $shipping_id, $shipping_rates, $cart, $taxes);

    return $taxes;
}

/**
 * Apply calculated products and shipping taxes to cart
 * cart taxes are supposed to be empty
 *
 * @param array $calculated_data payment data taxes
 * @param array $cart cart data
 * @return boolean always true
 */
function fn_apply_calculated_taxes($calculated_data, &$cart)
{
    if ($cart['stored_taxes'] == 'Y') {
        // save taxes to prevent payment taxes loss
        $cart['stored_taxes_data'] = $cart['taxes'];
    }

    $cart['taxes'] = array();
    $cart['tax_subtotal'] = !empty($cart['tax_subtotal']) ? $cart['tax_subtotal'] : 0;
    $cart['tax_summary'] = array(
        'included' => 0,
        'added' => 0,
        'total' => 0,
    );

    if (!empty($calculated_data)) {
        if (Registry::get('settings.Checkout.tax_calculation') == 'unit_price') {
            // Based on the unit price
            $taxes_data = array();
            foreach ($calculated_data as $id => $_taxes) {
                if (empty($_taxes)) {
                    continue;
                }
                foreach ($_taxes as $k => $v) {
                    if (empty($taxes_data[$k])) {
                        $taxes_data[$k] = $v;
                        $taxes_data[$k]['tax_subtotal'] = 0;
                    }
                    $taxes_data[$k]['applies'][$id] = $v['tax_subtotal'];
                    $taxes_data[$k]['tax_subtotal'] += $v['tax_subtotal'];
                }
            }

            $calculated_data = $taxes_data;
        }

        foreach ($calculated_data as $tax_id => $v) {
            $cart['taxes'][$tax_id] = $v;

            if ($v['price_includes_tax'] == 'Y') {
                $cart['tax_summary']['included'] += $v['tax_subtotal'];
            } else {
                $cart['tax_summary']['added'] += $v['tax_subtotal'];
            }

            $cart['tax_summary']['total'] += $v['tax_subtotal'];
        }

    } else { // FIXME!!! Test on order management
        $cart['taxes'] = array();
        $cart['tax_summary'] = array();
    }

    /**
     * Apply products and shipping taxes (running after fn_apply_calculated_taxes function)
     *
     * @param array $calculated_data payment data taxes
     * @param array $cart            cart data
     */
    fn_set_hook('apply_calculated_taxes_post', $calculated_data, $cart);

    return true;
}

function fn_format_rate_value($rate_value, $rate_type, $decimals='2', $dec_point='.', $thousands_sep=',', $coefficient = '')
{
    if (!empty($coefficient) && @$rate_type != 'P') {
        $rate_value = (float) $rate_value / (float) $coefficient;
    }

    if (empty($rate_type)) {
        $rate_type = 'F';
    }

    fn_set_hook('format_rate_value', $rate_value, $rate_type, $decimals, $dec_point, $thousands_sep, $coefficient);

    if (
        (strlen($thousands_sep) > 1 || strlen($dec_point) > 1)
        && (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50400)
    ) {
        $value = number_format(fn_format_price($rate_value, '', $decimals), $decimals, '.', ',');
        $value = str_replace(array('.', ','), array($dec_point, $thousands_sep), $value);
    } else {
        $value = number_format(fn_format_price($rate_value, '', $decimals), $decimals, $dec_point, $thousands_sep);
    }

    if ($rate_type == 'F') { // Flat rate

        return $value;
    } elseif ($rate_type == 'P') { // Percent rate

        return $value.'%';
    }

    return $rate_value;

}

/**
 * Checks needed product quantity in stock.
 *
 * @param int    $product_id              Product identifier
 * @param int    $amount                  Product quantity
 * @param array  $product_options         List of selected product options
 * @param int    $cart_id                 Item cart identifier
 * @param string $is_edp                  Flag, is downloadable product (Y|N)
 * @param int    $original_amount         Original product quantity
 * @param array  $cart                    Array of cart content and user information necessary for purchase
 * @param int    $update_id               Updated item cart identifier
 * @param bool   $skip_error_notification All error notifications will be change to warnings if true
 *
 * @return bool|int Returned new product quantity or false if checks failed.
 */
function fn_check_amount_in_stock($product_id, $amount, $product_options, $cart_id, $is_edp, $original_amount, &$cart, $update_id = 0, $skip_error_notification = false)
{
    /**
     * Executed before getting product data from database in the beginning of the function.
     * Allows you to change the inventory checking logic.
     *
     * @param int    $product_id              Product identifier
     * @param int    $amount                  Product quantity
     * @param array  $product_options         List of selected product options
     * @param int    $cart_id                 Item cart identifier
     * @param string $is_edp                  Flag, is downloadable product (Y|N)
     * @param int    $original_amount         Original product quantity
     * @param array  $cart                    Array of cart content and user information necessary for purchase
     * @param bool   $skip_error_notification All error notifications will be change to warnings if true
     */
    fn_set_hook('check_amount_in_stock', $product_id, $amount, $product_options, $cart_id, $is_edp, $original_amount, $cart, $skip_error_notification);

    // If the product is EDP don't track the inventory
    if ($is_edp == 'Y') {
        return 1;
    }

    $product = db_get_row(
        'SELECT p.tracking, p.amount, p.min_qty, p.max_qty, p.qty_step, p.list_qty_count, p.out_of_stock_actions, p.product_type, pd.product'
        . ' FROM ?:products AS p'
        . ' LEFT JOIN ?:product_descriptions AS pd ON pd.product_id = p.product_id AND lang_code = ?s'
        . ' WHERE p.product_id = ?i',
        CART_LANGUAGE,
        $product_id
    );

    $product = fn_normalize_product_overridable_fields($product);

    /**
     * Executes before the product amount in cart check.
     * Allows you to change the inventory checking logic.
     *
     * @param int    $product_id              Product identifier
     * @param int    $amount                  Product quantity
     * @param array  $product_options         List of selected product options
     * @param int    $cart_id                 Item cart identifier
     * @param string $is_edp                  Flag, is downloadable product (Y|N)
     * @param int    $original_amount         Original product quantity
     * @param array  $cart                    Array of cart content and user information necessary for purchase
     * @param int    $update_id               Updated item cart identifier
     * @param array  $product                 Product data
     * @param bool   $skip_error_notification All error notifications will be change to warnings if true
     */
    fn_set_hook(
        'check_amount_in_stock_before_cart_amount_check',
        $product_id,
        $amount,
        $product_options,
        $cart_id,
        $is_edp,
        $original_amount,
        $cart,
        $update_id,
        $product,
        $skip_error_notification
    );

    if (
        isset($product['tracking'])
        && $product['tracking'] !== ProductTracking::DO_NOT_TRACK
        && Registry::get('settings.General.inventory_tracking') !== YesNo::NO
    ) {
        $current_amount = $product['amount'];

        if (!empty($cart['products']) && is_array($cart['products'])) {
            $product_not_in_cart = true;
            foreach ($cart['products'] as $k => $v) {
                // Check if the product with the same selectable options already exists ( for tracking = O)
                if ($k != $cart_id) {
                    if (
                        isset($product['tracking'])
                        && ($product['tracking'] !== ProductTracking::DO_NOT_TRACK && (int) $v['product_id'] === (int) $product_id)
                    ) {
                        $current_amount -= $v['amount'];
                    }
                } else {
                    $product_not_in_cart = false;
                }
            }

            if (
                $product['tracking'] !== ProductTracking::DO_NOT_TRACK
                && !empty($update_id)
                && $product_not_in_cart
                && !empty($cart['products'][$update_id])
            ) {
                $current_amount += $cart['products'][$update_id]['amount'];
            }
        }
    }

    /**
     * Executed before the checks for the remaining quantity of products.
     * Allows you to change the inventory checking logic.
     *
     * @param int    $product_id              Product identifier
     * @param int    $amount                  Product quantity
     * @param array  $product_options         List of selected product options
     * @param int    $cart_id                 Item cart identifier
     * @param string $is_edp                  Flag, is downloadable product (Y|N)
     * @param int    $original_amount         Original product quantity
     * @param array  $cart                    Array of cart content and user information necessary for purchase
     * @param int    $update_id               Updated item cart identifier
     * @param array  $product                 Product data
     * @param int    $current_amount          Current product quantity in cart
     * @param bool   $skip_error_notification All error notifications will be change to warnings if true
     */
    fn_set_hook('check_amount_in_stock_before_check', $product_id, $amount, $product_options, $cart_id, $is_edp, $original_amount, $cart, $update_id, $product, $current_amount, $skip_error_notification);

    $notification_type = ($skip_error_notification) ? 'W' : 'E';
    $min_qty = 1;
    $product['qty_step'] = floor((float) $product['qty_step']);

    if (!empty($product['min_qty']) && $product['min_qty'] > $min_qty) {
        $min_qty = fn_ceil_to_step($product['min_qty'], $product['qty_step']);
    }

    if (!empty($product['qty_step']) && $product['qty_step'] > $min_qty) {
        $min_qty = $product['qty_step'];
    }

    $cart_amount_changed = false;
    // Step parity check
    if (!empty($product['qty_step']) && $amount % $product['qty_step']) {
        $amount = fn_ceil_to_step($amount, $product['qty_step']);
        $cart_amount_changed = true;
    }

    $allow_negative_ammount = Registry::get('settings.General.allow_negative_amount') === YesNo::YES;
    $inventory_tracking = Registry::get('settings.General.inventory_tracking') !== YesNo::NO;
    $allow_product_preorder = ($product['out_of_stock_actions'] === OutOfStockActions::BUY_IN_ADVANCE)
        && ($product['amount'] <= 0 || $product['amount'] < $min_qty);

    if (isset($current_amount)
        && $current_amount >= 0
        && $current_amount < $amount
        && !$allow_negative_ammount
        && !$allow_product_preorder
    ) {
        // For order edit: add original amount to existent amount
        $current_amount += $original_amount;

        if ($current_amount > 0 && $current_amount < $amount) {
            if (!defined('ORDER_MANAGEMENT')) {
                $amount = fn_ceil_to_step($current_amount, $product['qty_step']);
                fn_set_notification(NotificationSeverity::WARNING, __('important'), __('text_cart_amount_corrected', [
                    '[product]' => $product['product'],
                    '[count]' => $amount,
                ]));
            } else {
                fn_set_notification('W', __('warning'), __('text_cart_not_enough_inventory'));
            }
        } elseif ($current_amount < $amount) {
            if (
                defined('ORDER_MANAGEMENT')
                && isset($cart['products'][$cart_id]['amount'])
            ) {
                fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('text_cart_not_enough_inventory'));

                return $cart['products'][$cart_id]['amount'];
            }

            fn_set_notification(
                $notification_type,
                __('notice'),
                __('text_cart_zero_inventory_and_removed', ['[product]' => $product['product']])
            );

            return false;
        } elseif ($current_amount <= 0 && $amount <= 0) {
            fn_set_notification(
                $notification_type,
                __('notice'),
                __('text_cart_zero_inventory', ['[product]' => $product['product']]),
                '',
                'zero_inventory'
            );

            return false;
        }
    }

    if ($amount < $min_qty
        || (
            isset($current_amount)
            && $amount > $current_amount
            && !$allow_negative_ammount
            && $inventory_tracking
            && !$allow_product_preorder
        )
        && isset($product_not_in_cart)
        && !$product_not_in_cart
    ) {
        if (($current_amount < $min_qty || $current_amount == 0)
            && !$allow_negative_ammount
            && $inventory_tracking
            && !$allow_product_preorder
        ) {
            fn_set_notification('W', __('warning'), __('text_cart_not_enough_inventory'));
            if (!defined('ORDER_MANAGEMENT')) {
                $amount = false;
            }
        } elseif ($amount > $current_amount
            && !$allow_negative_ammount
            && $inventory_tracking
            && !$allow_product_preorder
        ) {
            fn_set_notification('W', __('warning'), __('text_cart_not_enough_inventory'));

            if (!defined('ORDER_MANAGEMENT')) {
                $amount = fn_floor_to_step($current_amount, $product['qty_step']);
            }
        } elseif ($amount < $min_qty) {
            fn_set_notification('W', __('notice'), __('text_cart_min_qty', array(
                '[product]' => $product['product'],
                '[quantity]' => $min_qty,
            )));

            $cart_amount_changed = false;

            if (!defined('ORDER_MANAGEMENT')) {
                $amount = $min_qty;
            } elseif (
                !empty($product['qty_step']) && isset($product_not_in_cart) && $product_not_in_cart
                || !empty($product['qty_step']) && !isset($product_not_in_cart)
            ) {
                $amount = fn_ceil_to_step((int) $min_qty, $product['qty_step']);
                $cart_amount_changed = true;
            }
        }
    }

    $max_qty = fn_floor_to_step($product['max_qty'], $product['qty_step']);
    if (!empty( $max_qty) && $amount >  $max_qty) {
        fn_set_notification('W', __('notice'), __('text_cart_max_qty', array(
            '[product]' => $product['product'],
            '[quantity]' =>  $max_qty,
        )));
        $cart_amount_changed = false;

        if (!defined('ORDER_MANAGEMENT')) {
            $amount = $max_qty;
        }
    }

    if ($cart_amount_changed) {
        fn_set_notification('W', __('important'), __('text_cart_amount_changed', array('[product]' => $product['product'])));
    }

    fn_set_hook('post_check_amount_in_stock', $product_id, $amount, $product_options, $cart_id, $is_edp, $original_amount, $cart);

    return empty($amount) ? false : $amount;
}

//
// Calculate unique product id in the cart
//
function fn_generate_cart_id($product_id, $extra, $only_selectable = false)
{
    $_cid = array();

    if (!empty($extra['product_options']) && is_array($extra['product_options'])) {

        // Try to select all options (including Globals)
        Registry::set('runtime.skip_sharing_selection', true);

        foreach ($extra['product_options'] as $k => $v) {
            if ($only_selectable == true) {
                continue;
            }

            $_cid[] = $v;
        }

        Registry::set('runtime.skip_sharing_selection', false);
    }

    if (isset($extra['exclude_from_calculate'])) {
        $_cid[] = $extra['exclude_from_calculate'];
    }

    fn_set_hook('generate_cart_id', $_cid, $extra, $only_selectable);

    natsort($_cid);
    array_unshift($_cid, $product_id);
    $cart_id = fn_crc32(implode('_', $_cid));

    return $cart_id;
}


//
// Normalize product amount
//
function fn_normalize_amount($amount = '1')
{
    $amount = abs(intval($amount));

    return empty($amount) ? 0 : $amount;
}


function fn_order_placement_routines($action = '', $order_id = 0, $force_notification = array(), $clear_cart = true, $area = AREA)
{
    if (Embedded::isLeft() && !Embedded::isEnabled()) {
        Embedded::enable();
    }

    if ($action == 'checkout_redirect') {
        if ($area == 'A') {
            fn_redirect("order_management.edit?order_id=" . reset(Tygh::$app['session']['cart']['processed_order_id']));
        } else {
            fn_redirect('checkout.checkout');
        }
    } elseif (in_array($action, array('save', 'repay', 'route')) && !empty($order_id)) {
        $cart = &Tygh::$app['session']['cart'];
        $order_info = fn_get_order_info($order_id, true);

        $display_notification = true;

        fn_set_hook('placement_routines', $order_id, $order_info, $force_notification, $clear_cart, $action, $display_notification);

        if (!empty($cart['placement_action'])) {
            if (empty($action)) {
                $action = $cart['placement_action'];
            }
            unset($cart['placement_action']);
        }

        $_error = false;

        if ($action == 'save') {
            if ($display_notification) {
                fn_set_notification('N', __('congratulations'), __('text_order_saved_successfully'));
            }
        } else {
            if ($order_info['status'] === OrderStatuses::PARENT) {
                $child_orders = db_get_hash_single_array("SELECT order_id, status FROM ?:orders WHERE parent_order_id = ?i", array('order_id', 'status'), $order_id);
                $status = reset($child_orders);
                $child_orders = array_keys($child_orders);
            } else {
                $status = $order_info['status'];
            }
            if (in_array($status, fn_get_order_paid_statuses())) {
                if ($action == 'repay') {
                    fn_set_notification('N', __('congratulations'), __('text_order_repayed_successfully'));
                } else {
                    fn_set_notification('N', __('order_placed'), __('text_order_placed_successfully'));
                }
            } elseif ($status === OrderStatuses::BACKORDERED) {
                fn_set_notification('W', __('important'), __('text_order_backordered'));
            } else {
                if ($area == 'A' || $action == 'repay') {
                    if ($status !== OrderStatuses::CANCELED) {
                        $_payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);
                        if (!empty($_payment_info)) {
                            $_payment_info = unserialize(fn_decrypt_text($_payment_info));
                            $_msg = !empty($_payment_info['reason_text']) ? $_payment_info['reason_text'] : '';
                            $_msg .= empty($_msg) ? __('text_order_placed_error') : '';
                            fn_set_notification('E', '', $_msg);
                        }
                    }
                } else {
                    $_error = true;
                    if (!empty($child_orders)) {
                        array_unshift($child_orders, $order_id);
                    } else {
                        $child_orders = array();
                        $child_orders[] = $order_id;
                    }
                    $cart[($status === OrderStatuses::INCOMPLETED ? 'processed_order_id' : 'failed_order_id')] = $child_orders;
                }
                if ($status === OrderStatuses::INCOMPLETED || ($action === 'repay' && $status === OrderStatuses::CANCELED)) {
                    fn_set_notification(NotificationSeverity::WARNING, __('important'), __('text_transaction_cancelled'), '', 'transaction_cancelled');
                }
            }
        }

        // Empty cart
        if ($clear_cart == true && $_error == false) {

            $params = [];

            if (!empty($cart['storefront_id'])) {
                $params['storefront_id'] = $cart['storefront_id'];
            }

            if (defined('ORDER_MANAGEMENT') && !empty($cart['abandoned_cart_user_id'])) {

                $params['user_id'] = $cart['abandoned_cart_user_id'];
                $params['session_id'] = false;

                if (isset($cart['abandoned_cart_storefront_id'])) {
                    $params['storefront_id'] = $cart['abandoned_cart_storefront_id'];
                }

                $abandoned_cart_conversion_cleanup_condition = fn_user_session_products_condition($params);
                db_query('DELETE FROM ?:user_session_products WHERE 1=1 AND ?p', $abandoned_cart_conversion_cleanup_condition);
            }

            $cart = [
                'user_data'  => !empty($cart['user_data']) ? $cart['user_data'] : [],
                'profile_id' => !empty($cart['profile_id']) ? $cart['profile_id'] : 0,
                'user_id'    => !empty($cart['user_id']) ? $cart['user_id'] : 0,
            ];
            Tygh::$app['session']['shipping_rates'] = [];
            unset(Tygh::$app['session']['shipping_hash']);

            $current_user_cart_cleanup_condition = fn_user_session_products_condition($params);
            db_query('DELETE FROM ?:user_session_products WHERE 1=1 AND ?p', $current_user_cart_cleanup_condition);
        }

        $allow_external_redirect = false;

        if ($area === 'A') {
            $redirect_url = 'orders.details?order_id=' . $order_id;
        } else {
            $redirect_url = 'checkout.' . ($_error ? 'checkout' : 'complete?order_id=' . $order_id);
        }

        fn_set_hook('order_placement_routines', $order_id, $force_notification, $order_info, $_error, $redirect_url, $allow_external_redirect);

        fn_redirect($redirect_url, $allow_external_redirect);

    } elseif ($action == 'index_redirect') {
        fn_redirect(fn_url('', 'C', 'http'));
    } else {
        fn_redirect(fn_url($action, 'C', 'http'));
    }
}

//
// Calculate difference
//
function fn_less_zero($first_arg, $second_arg = 0, $zero = false)
{
    if (!empty($second_arg)) {
        if ($first_arg - $second_arg > 0) {
            return $first_arg - $second_arg;
        } else {
            return 0;
        }
    } else {
        if (empty($zero)) {
            return $first_arg;
        } else {
            return 0;
        }
    }
}

/**
 * Checks if product can be added to cart
 *
 * @param array $cart       Array of the cart contents and user information necessary for purchase
 * @param array $product    Params with that product is adding to cart
 * @param int   $product_id Identifier of adding product
 *
 * @return bool $result
 */
function fn_check_add_product_to_cart($cart, $product, $product_id)
{
    $result = true;

    /**
     * Change parmetres of checking if product can be added to cart (run before fn_check_add_product_to_cart func)
     *
     * @param array   $cart       Array of the cart contents and user information necessary for purchase
     * @param array   $product    Params with that product is adding to cart
     * @param int     $product_id Identifier of adding product
     * @param boolean $result     Flag determines if product can be added to cart
     */
    fn_set_hook('check_add_to_cart_pre', $cart, $product, $product_id, $result);

    $product_company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);

    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];
    if ((fn_allowed_for('MULTIVENDOR') && !empty($product_company_id)) && $storefront->getCompanyIds() && !in_array($product_company_id, $storefront->getCompanyIds())) {
        $result = (AREA === 'A');
    } else {
        if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
            if ($product_company_id != Registry::get('runtime.company_id') && fn_ult_is_shared_product($product_id, Registry::get('runtime.company_id')) != 'Y') {
                $result = false;
            }
        }

        if (fn_allowed_for('MULTIVENDOR') && isset($cart['company_id'])) {
            if ($product_company_id != $cart['company_id']) {
                $result = false;
            }
        }
    }

    /**
     * Change parmetres of checking if product can be added to cart (run before fn_check_add_product_to_cart func)
     *
     * @param array   $cart       Array of the cart contents and user information necessary for purchase
     * @param array   $product    Params with that product is adding to cart
     * @param int     $product_id Identifier of adding product
     * @param boolean $result     Flag determines if product can be added to cart
     */
    fn_set_hook('check_add_to_cart_post', $cart, $product, $product_id, $result);

    return $result;
}

/**
 * Adds product to cart.
 *
 * @param array     $product_data   List of products data
 * @param array     $cart           Array of cart content and user information necessary for purchase
 * @param array     $auth           Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @param bool      $update         Flag, if true that is update mode. Usable for order management
 *
 * @return array|bool   Return list of added product IDs or false otherwise.
 */
function fn_add_product_to_cart($product_data, &$cart, &$auth, $update = false)
{
    $ids = array();
    if (!empty($product_data) && is_array($product_data)) {
        if (!defined('GET_OPTIONS')) {
            [$product_data, $cart] = fn_add_product_options_files($product_data, $cart, $auth, $update);
        }

        fn_set_hook('pre_add_to_cart', $product_data, $cart, $auth, $update);

        foreach ($product_data as $key => $data) {
            if (empty($key)) {
                continue;
            }
            if (empty($data['amount'])) {
                continue;
            }

            $product_id = (!empty($data['product_id'])) ? (int) $data['product_id'] : (int) $key;

            // Check if the product price with options modifiers equals to zero
            $zero_price_action = db_get_field('SELECT zero_price_action FROM ?:products WHERE product_id = ?i', $product_id);
            $zero_price_action = fn_normalize_product_overridable_field_value('zero_price_action', $zero_price_action);

            if ($zero_price_action === ProductZeroPriceActions::ASK_TO_ENTER_PRICE && defined('ORDER_MANAGEMENT')) {
                $data['stored_price'] = YesNo::YES;
            }

            $data['stored_price'] = (!empty($data['stored_price']) && defined('ORDER_MANAGEMENT')) ? $data['stored_price'] : YesNo::NO;

            if (empty($data['extra'])) {
                $data['extra'] = array();
            }

            if (!fn_check_add_product_to_cart($cart, $data, $product_id)) {
                continue;
            }

            // Check if product options exist
            if (!isset($data['product_options'])) {
                $data['product_options'] = fn_get_default_product_options($product_id, false, [], true);
            }

            // Generate cart id
            $data['extra']['product_options'] = $data['product_options'];

            $_id = fn_generate_cart_id($product_id, $data['extra'], false);

            if (isset($ids[$_id]) && $key == $_id) {
                continue;
            }

            if (isset($data['extra']['exclude_from_calculate'])) {
                if ($update && !empty($cart['products'][$key]) && !empty($cart['products'][$key]['extra']['aoc'])) {
                    $cart['saved_product_options'][$cart['products'][$key]['extra']['saved_options_key']] = $data['product_options'];
                } elseif (!$update && !empty($data['extra']['saved_options_key']) && !empty($data['extra']['aoc'])) {
                    $cart['saved_product_options'][$data['extra']['saved_options_key']] = $data['product_options'];
                }

                if (isset($cart['deleted_exclude_products'][$data['extra']['exclude_from_calculate']][$_id])) {
                    continue;
                }
            }
            $amount = fn_normalize_amount(@$data['amount']);

            if (!isset($data['extra']['exclude_from_calculate'])) {
                if ($data['stored_price'] != 'Y') {
                    $allow_add = true;
                    // Check if the product price with options modifiers equals to zero
                    $price = fn_get_product_price($product_id, $amount, $auth);

                    /**
                     * Executed when a product is added to cart, once the price of the product is determined.
                     * Allows to change the price of the product in the cart.
                     *
                     * @param array     $product_data       List of products data
                     * @param array     $cart               Array of cart content and user information necessary for purchase
                     * @param array     $auth               Array of user authentication data (e.g. uid, usergroup_ids, etc.)
                     * @param bool      $update             Flag, if true that is update mode. Usable for order management
                     * @param int       $_id                Cart item identifier
                     * @param array     $data               Current product data
                     * @param int       $product_id         Product identifier
                     * @param int       $amount             Product quantity
                     * @param float     $price              Product price
                     * @param string    $zero_price_action  Flag, determines the action when the price of the product is 0
                     * @param bool      $allow_add          Flag, determines if product can be added to cart
                     */
                    fn_set_hook('add_product_to_cart_get_price', $product_data, $cart, $auth, $update, $_id, $data, $product_id, $amount, $price, $zero_price_action, $allow_add);

                    if (!floatval($price) && $zero_price_action == 'A') {
                        if (isset($cart['products'][$key]['custom_user_price'])) {
                            $price = $cart['products'][$key]['custom_user_price'];
                        } else {
                            $custom_user_price = empty($data['price']) ? 0 : $data['price'];
                        }
                    }
                    $price = fn_apply_options_modifiers($data['product_options'], $price, 'P', array(), array('product_data' => $data));
                    if (!floatval($price)) {
                        $data['price'] = isset($data['price']) ? fn_parse_price($data['price']) : 0;

                        if (AREA == 'C'
                            && ($zero_price_action == 'R'
                                ||
                                ($zero_price_action == 'A' && floatval($data['price']) < 0)
                            )
                        ) {
                            if ($zero_price_action == 'A') {
                                fn_set_notification('E', __('error'), __('incorrect_price_warning'));
                            } elseif ($zero_price_action === ProductZeroPriceActions::NOT_ALLOW_ADD_TO_CART) {
                                fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('warning_zero_price_restricted_product', [
                                    '[product]' => fn_get_product_name($product_id)
                                ]));
                            }
                            $allow_add = false;
                        }

                        $price = empty($data['price']) ? 0 : $data['price'];
                    }

                    /**
                     * Recalculates price and checks if product can be added with the current price
                     *
                     * @param array $data Adding product data
                     * @param float $price Calculated product price
                     * @param boolean $allow_add Flag that determines if product can be added to cart
                     */
                    fn_set_hook('add_product_to_cart_check_price', $data, $price, $allow_add);

                    if (!$allow_add) {
                        continue;
                    }

                } else {
                    $price = empty($data['price']) ? 0 : $data['price'];
                }
            } else {
                $price = 0;
            }

            $_data = db_get_row(
                'SELECT is_edp, options_type, tracking, unlimited_download, updated_timestamp FROM ?:products WHERE product_id = ?i',
                $product_id
            );
            $_data = fn_normalize_product_overridable_fields($_data);
            if (isset($_data['is_edp'])) {
                $data['is_edp'] = $_data['is_edp'];
            } elseif (!isset($data['is_edp'])) {
                $data['is_edp'] = 0;
            }
            if (isset($_data['options_type'])) {
                $data['options_type'] = $_data['options_type'];
            }
            if (isset($_data['tracking'])) {
                $data['tracking'] = $_data['tracking'];
            }
            if (isset($_data['unlimited_download'])) {
                $data['extra']['unlimited_download'] = $_data['unlimited_download'];
            }
            if (isset($_data['updated_timestamp'])) {
                $data['extra']['updated_timestamp'] = $_data['updated_timestamp'];
            }

            if (!isset($cart['products'][$_id])) { // If product doesn't exists in the cart
                $skip_error_notification = isset($data['extra']['exclude_from_calculate']) ? $data['extra']['exclude_from_calculate'] : false;
                $amount = empty($data['original_amount'])
                    ? fn_check_amount_in_stock($product_id, $amount, $data['product_options'], $_id, $data['is_edp'], 0, $cart, $update == true ? $key : 0, $skip_error_notification)
                    : $data['original_amount'];

                if ($amount === false) {
                    continue;
                }

                if ($update && $key !== $_id) {
                    // If option changed, save items order
                    $item_index = array_search($key, array_keys($cart['products']));
                    if ($item_index !== false) {
                        $offset = $item_index + 1;
                        $to_insert = [$_id => []];
                        $cart['products'] = array_slice($cart['products'], 0, $offset, true) + $to_insert + array_slice($cart['products'], $offset, null, true);
                    }
                }

                $cart['products'][$_id]['product_id'] = $product_id;
                $cart['products'][$_id]['product_code'] = fn_get_product_code($product_id, $data['product_options']);
                $cart['products'][$_id]['product'] = fn_get_product_name($product_id);
                $cart['products'][$_id]['amount'] = $amount;
                $cart['products'][$_id]['product_options'] = $data['product_options'];
                $cart['products'][$_id]['price'] = $price;
                if (!empty($zero_price_action) && $zero_price_action == 'A') {
                    if (isset($custom_user_price)) {
                        $cart['products'][$_id]['custom_user_price'] = $custom_user_price;
                    } elseif (isset($cart['products'][$key]['custom_user_price'])) {
                        $cart['products'][$_id]['custom_user_price'] = $cart['products'][$key]['custom_user_price'];
                    }
                }
                $cart['products'][$_id]['stored_price'] = $data['stored_price'];

                // add image for minicart
                $cart['products'][$_id]['main_pair'] = fn_get_cart_product_icon($product_id, $data);

                fn_define_original_amount($product_id, $_id, $cart['products'][$_id], $data);

                if ($update == true && $key != $_id) {
                    fn_delete_cart_product($cart, $key, false);
                }

            } else { // If product is already exist in the cart

                $_initial_amount = empty($cart['products'][$_id]['original_amount']) ? $cart['products'][$_id]['amount'] : $cart['products'][$_id]['original_amount'];

                // If ID changed (options were changed), summ the total amount of old and new products
                if ($update == true && $key != $_id) {
                    $amount += $_initial_amount;
                    fn_delete_cart_product($cart, $key, false);
                }

                $cart['products'][$_id]['amount'] = fn_check_amount_in_stock($product_id, (($update == true) ? 0 : $_initial_amount) + $amount, $data['product_options'], $_id, (!empty($data['is_edp']) && $data['is_edp'] == 'Y' ? 'Y' : 'N'), 0, $cart, $update == true ? $key : 0);
            }

            $cart['products'][$_id]['extra'] = (empty($data['extra'])) ? array() : $data['extra'];
            $cart['products'][$_id]['stored_discount'] = @$data['stored_discount'];
            if (defined('ORDER_MANAGEMENT')) {
                $cart['products'][$_id]['discount'] = @$data['discount'];
            }

            // Increase product popularity
            if (empty(Tygh::$app['session']['products_popularity']['added'][$product_id])) {
                $popularity = [
                    'added' => 1,
                    'total' => POPULARITY_ADD_TO_CART,
                ];

                fn_update_product_popularity($product_id, $popularity);

                Tygh::$app['session']['products_popularity']['added'][$product_id] = true;
            }

            $company_id = db_get_field("SELECT company_id FROM ?:products WHERE product_id = ?i", $product_id);
            $cart['products'][$_id]['company_id'] = $company_id;

            if (!empty($data['saved_object_id'])) {
                $cart['products'][$_id]['object_id'] = $data['saved_object_id'];
            }

            if (isset($cart['applied_promotions'])) {
                foreach ($cart['applied_promotions'] as $promotion) {
                    // phpcs:ignore
                    if (isset($cart['saved_product_options'][$promotion['promotion_id'] . '_' . $product_id])) {
                        $cart['saved_product_options'][$promotion['promotion_id'] . '_' . $product_id] = $cart['products'][$_id]['product_options'];
                    }
                }
            }

            fn_set_hook('add_to_cart', $cart, $product_id, $_id);

            $ids[$_id] = $product_id;
        }

        /**
         * Change product data after adding product to cart
         *
         * @param array $product_data Product data
         * @param array $cart Cart data
         * @param array $auth Auth data
         * @param bool $update Flag the determains if cart data are updated
         */
        fn_set_hook('post_add_to_cart', $product_data, $cart, $auth, $update, $ids);

        $cart['recalculate'] = true;
        $cart['change_cart_products'] = true;

        if (!empty($cart['chosen_shipping']) && empty($cart['deny_unsetting_product_group'])) {
            $cart['calculate_shipping'] = true;
            unset($cart['product_groups']);
        }

        return $ids;

    } else {
        return false;
    }
}

/**
 * Forms cart based on order
 *
 * @param integer $order_id Order ID
 * @param array   $cart     Cart
 * @param array   $auth     Auth info
 * @param bool    $copy     True if creating new order, otherwise edit
 *
 * @return bool True if success, false otherwise
 */
function fn_form_cart($order_id, &$cart, &$auth, $copy = false)
{
    $order_info = fn_get_order_info($order_id, false, false);

    if (empty($order_info)) {
        fn_set_notification('E', __('error'), __('object_not_found', array('[object]' => __('order'))),'','404');

        return false;
    }

    /**
     * Change cart data before forming cart.
     *
     * @param int   $order_id   Order id
     * @param array $cart       Cart data
     * @param array $auth       Auth information
     * @param array $order_info Order info
     * @param bool  $copy       If true, create new order, otherwise edit
     */
    fn_set_hook('form_cart_pre_fill', $order_id, $cart, $auth, $order_info, $copy);

    // Fill the cart
    foreach ($order_info['products'] as $_id => $item) {
        $_item = array (
            $item['product_id'] => array (
                'amount' => $item['amount'],
                'product_options' => (!empty($item['extra']['product_options']) ? $item['extra']['product_options'] : array()),
                'price' => $item['original_price'],
                'stored_discount' => 'Y',
                'stored_price' => 'Y',
                'discount' => (!empty($item['extra']['discount']) ? $item['extra']['discount'] : 0),
                'original_amount' => $item['amount'], // the original amount, that stored in order
                'original_product_data' => array ( // the original cart ID and amount, that stored in order
                    'cart_id' => $_id,
                    'amount' => $item['amount'],
                ),
            ),
        );
        if (isset($item['extra'])) {
            $_item[$item['product_id']]['extra'] = $item['extra'];
        }

        fn_add_product_to_cart($_item, $cart, $auth);
    }

    // Workaround for the add-ons that do not add a product to cart unless the parent product is already added.
    if (count($order_info['products']) > count($cart['products'])) {
        foreach ($order_info['products'] as $_id => $item) {
            if (empty($cart['products'][$_id])) {
                $_item = array (
                    $item['product_id'] => array (
                        'amount' => $item['amount'],
                        'product_options' => (!empty($item['extra']['product_options']) ? $item['extra']['product_options'] : array()),
                        'price' => $item['original_price'],
                        'stored_discount' => 'Y',
                        'stored_price' => 'Y',
                        'discount' => (!empty($item['extra']['discount']) ? $item['extra']['discount'] : 0),
                        'original_amount' => $item['amount'], // the original amount, that stored in order
                        'original_product_data' => array ( // the original cart ID and amount, that stored in order
                            'cart_id' => $_id,
                            'amount' => $item['amount'],
                        ),
                    ),
                );
                if (isset($item['extra'])) {
                    $_item[$item['product_id']]['extra'] = $item['extra'];
                }
                fn_add_product_to_cart($_item, $cart, $auth);
            }
        }
    }

    // Restore custom files
    $dir_path = 'order_data/' . $order_id;

    if (Storage::instance('custom_files')->isExist($dir_path)) {
        Storage::instance('custom_files')->copy($dir_path, 'sess_data');
    }

    if ($copy == false) {
        $cart['parent_order_id'] = $order_info['parent_order_id'];
    }
    $cart['payment_id'] = $order_info['payment_id'];
    $cart['stored_taxes'] = 'Y';
    $cart['stored_discount'] = 'Y';
    $cart['taxes'] = $order_info['taxes'];
    $cart['promotions'] = !empty($order_info['promotions']) ? $order_info['promotions'] : array();

    $cart['shipping'] = (!empty($order_info['shipping'])) ? $order_info['shipping'] : array();
    $cart['stored_shipping'] = array();

    foreach ($cart['shipping'] as $sh_id => $v) {
        if (!empty($v['rates'])) {
            $cart['stored_shipping'][$sh_id] = array_sum($v['rates']);
        }
    }

    if (!empty($order_info['product_groups'])) {
        $cart['product_groups'] = $order_info['product_groups'];
        foreach ($order_info['product_groups'] as $group_key => $group) {
            if (!empty($group['chosen_shippings'])) {
                foreach ($group['chosen_shippings'] as $key => $chosen_shipping) {
                    foreach ($group['shippings'] as $shipping_id => $shipping) {
                        $cart['product_groups'][$group_key]['chosen_shippings'][$key]['stored_shipping'] = true;

                        if ($shipping_id == $chosen_shipping['shipping_id']) {
                            $cart['chosen_shipping'][$chosen_shipping['group_key']] = $shipping_id;
                        }
                    }
                }
            }
        }
    } else {
        $cart['product_groups'] = array();
    }

    $cart['order_timestamp'] = $order_info['timestamp'];
    $cart['notes'] = $order_info['notes'];
    $cart['details'] = $order_info['details'];
    $cart['payment_info'] = @$order_info['payment_info'];
    $cart['profile_id'] = $order_info['profile_id'];

    // Add order discount
    if (floatval($order_info['subtotal_discount'])) {
        $cart['stored_subtotal_discount'] = 'Y';
        $cart['subtotal_discount'] = $cart['original_subtotal_discount'] = fn_format_price($order_info['subtotal_discount']);
    }

    // Fill the cart with the coupons
    if (!empty($order_info['coupons'])) {
        $cart['coupons'] = $order_info['coupons'];
    }

    // Set the customer if exists
    $_data = array();
    if (!empty($order_info['user_id'])) {
        $_data = db_get_row('SELECT user_id, user_login as login FROM ?:users WHERE user_id = ?i', $order_info['user_id']);
    }
    $auth = fn_fill_auth($_data, array(), false, 'C');
    $auth['tax_exempt'] = $order_info['tax_exempt'];

    // Fill customer info
    $cart['user_data'] = fn_array_merge(
        fn_check_table_fields($order_info, 'users'),
        fn_check_table_fields($order_info, 'user_profiles')
    );
    if (!empty($order_info['fields'])) {
        $cart['user_data']['fields'] = $order_info['fields'];
    }
    fn_add_user_data_descriptions($cart['user_data']);

    if (!empty($order_info['secondary_currency']) && Registry::get("currencies.{$order_info['secondary_currency']}")) {
        $cart['secondary_currency'] = $order_info['secondary_currency'];
    }

    fn_set_hook('form_cart', $order_info, $cart, $auth);

    return true;
}

//
// Calculate taxes for products or shippings
//
function fn_calculate_tax_rates($taxes, $price, $amount, $auth, &$cart)
{
    static $destination_id;
    static $tax_description;
    static $user_data;

    $taxed_price = $price;

    if (!empty($cart['user_data']) && !fn_is_empty($cart['user_data'])) {
        $profile_fields = fn_get_profile_fields('O', $auth);
        $billing_population = fn_check_profile_fields_population($cart['user_data'], 'B', $profile_fields);
        $shipping_population = fn_check_profile_fields_population($cart['user_data'], 'S', $profile_fields);

        if (empty($auth['user_id']) && (!$shipping_population || !$billing_population)) {
            fn_define('ESTIMATION', true);
        }
    }

    if (empty($auth['user_id']) && (empty($cart['user_data']) || fn_is_empty($cart['user_data']) || $billing_population != true || $shipping_population != true) && Registry::get('runtime.checkout') && Registry::get('settings.Appearance.taxes_using_default_address') !== 'Y' && !defined('ESTIMATION')) {
        return false;
    }
    $tax_calculation_type = Registry::get('settings.Checkout.tax_calculation');

    if ((empty($destination_id) || $user_data != @$cart['user_data'])) {
        // Get billing location
        $location = fn_fill_location_fields(fn_get_customer_location($auth, $cart, true));
        $destination_id['B'] = fn_get_available_destination($location);

        // Get shipping location
        $location = fn_fill_location_fields(fn_get_customer_location($auth, $cart));
        $destination_id['S'] = fn_get_available_destination($location);
    }

    if (!empty($cart['user_data'])) {
        $user_data = $cart['user_data'];
    }
    $_tax = 0;
    $previous_priority = -1;
    $previous_price = '';

    foreach ($taxes as $key => $tax) {
        if (empty($tax['tax_id'])) {
            $tax['tax_id'] = $key;
        }

        if (empty($tax['priority'])) {
            $tax['priority'] = 0;
        }

        $_is_zero = floatval($taxed_price);
        if (empty($_is_zero)) {
            continue;
        }

        if (!empty($cart['stored_taxes']) && $cart['stored_taxes'] == 'Y' && (!empty($tax['rate_type']) || isset($cart['taxes'][$tax['tax_id']]['rate_value']))) {
            $rate = array (
                'rate_value' => isset($cart['taxes'][$tax['tax_id']]['rate_value']) ? $cart['taxes'][$tax['tax_id']]['rate_value'] : $tax['rate_value'],
                'rate_type' => isset($cart['taxes'][$tax['tax_id']]['rate_type']) ? $cart['taxes'][$tax['tax_id']]['rate_type'] : $tax['rate_type'],
            );

        } else {
            if (!isset($destination_id[$tax['address_type']])) {
                continue;
            }

            $rate = db_get_row("SELECT destination_id, rate_value, rate_type FROM ?:tax_rates WHERE tax_id = ?i AND destination_id = ?i", $tax['tax_id'], $destination_id[$tax['address_type']]);
            if (!isset($rate['rate_value'])) {
                continue;
            }
        }

        $base_price = ($tax['priority'] == $previous_priority) ? $previous_price : $taxed_price;

        if ($rate['rate_type'] == 'P') { // Percent dependence
            // If tax is included into the price
            if ($tax['price_includes_tax'] == 'Y') {
                $_tax = fn_format_price($base_price - $base_price / ( 1 + ($rate['rate_value'] / 100)));
                // If tax is NOT included into the price
            } else {
                $_tax = fn_format_price($base_price * ($rate['rate_value'] / 100));
                $taxed_price += $_tax;
            }

        } else {
            $_tax = fn_format_price($rate['rate_value']);
            // If tax is NOT included into the price
            if ($tax['price_includes_tax'] != 'Y') {
                $taxed_price += $_tax;
            }
        }

        $previous_priority = $tax['priority'];
        $previous_price = $base_price;

        if (empty($tax_description[$tax['tax_id']])) {
            $tax_description[$tax['tax_id']] = db_get_field("SELECT tax FROM ?:tax_descriptions WHERE tax_id = ?i AND lang_code = ?s", $tax['tax_id'], CART_LANGUAGE);
        }

        $taxes_data[$tax['tax_id']] = array (
            'rate_type' => $rate['rate_type'],
            'rate_value' => $rate['rate_value'],
            'price_includes_tax' => $tax['price_includes_tax'],
            'regnumber' => @$tax['regnumber'],
            'priority' => @$tax['priority'],
            'tax_subtotal' => fn_format_price($_tax * $amount),
            'description' => $tax_description[$tax['tax_id']],
            'calculation_type' => $tax_calculation_type,
        );
    }

    return empty($taxes_data) ? false : $taxes_data;
}

/**
 * Fills cart with product and customer data from abandoned cart
 *
 * @param array $params Query parameters
 *
 * @return array
 */
function fn_form_cart_from_abandoned($params)
{
    if (!is_array($params)) {
        $params = [
            'user_id' => (int) $params,
        ];
    }

    $cart = [];
    fn_clear_cart($cart);

    $customer_id = $params['user_id'];

    $cart['abandoned_cart_user_id'] = $customer_id;
    $user_profiles = fn_get_user_profiles($customer_id);

    [$stored_cart] = fn_get_carts($params);
    if (!empty($stored_cart[$customer_id]['company_id'])) {
        $cart['abandoned_cart_company_id'] = $stored_cart[$customer_id]['company_id'];
    }

    if (!empty($stored_cart[$customer_id]['storefront_id'])) {
        $cart['abandoned_cart_storefront_id'] = $stored_cart[$customer_id]['storefront_id'];
    }

    $extra = unserialize($stored_cart[$customer_id]['extra']);
    if ($user_profiles) {
        $profile_id = (int) reset($user_profiles)['profile_id'];
        $cart['user_data'] = fn_get_user_info($customer_id, true, $profile_id);
        $cart['profile_id'] = $profile_id;
        $customer_auth = fn_fill_auth(['user_id' => $customer_id], [], false, 'C');
    } else {
        $faker = Faker::create();

        if (!empty($extra['email'])) {
            $email = $extra['email'];
        } elseif (!empty($extra['phone'])) {
            $email = sprintf('%s@example.com', preg_replace('@\D@', '', $extra['phone']));
        } elseif (!empty($extra['address'])) {
            $convert_letters = fn_get_schema('literal_converter', 'schema');
            $address = strtr($extra['address'], $convert_letters) . '_' . rand(0, 9999);
            $email = filter_var(
                sprintf('%s@example.com', str_replace([' ', '+'], ['_', ' '], $address)),
                FILTER_SANITIZE_EMAIL
            );
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = $faker->safeEmail;
        }

        $user_data = [
            'email'     => $email,
            'firstname' => isset($extra['firstname']) ? $extra['firstname'] : $faker->firstNameMale,
            'lastname'  => isset($extra['lastname']) ? $extra['lastname'] : $faker->firstNameMale,
            'phone'     => isset($extra['phone']) ? $extra['phone'] : '',
        ];

        $general_settings = Registry::get('settings.General');

        $extra_user_data = isset($extra['user_data']) ? $extra['user_data'] : [];
        $user_data = array_merge($user_data, [
            'b_firstname'     => isset($extra_user_data['b_firstname'])     ? $extra_user_data['b_firstname']     : $user_data['firstname'],
            's_firstname'     => isset($extra_user_data['s_firstname'])     ? $extra_user_data['s_firstname']     : $user_data['firstname'],
            'b_lastname'      => isset($extra_user_data['b_lastname'])      ? $extra_user_data['b_lastname']      : $user_data['lastname'],
            's_lastname'      => isset($extra_user_data['s_lastname'])      ? $extra_user_data['s_lastname']      : $user_data['lastname'],
            'b_phone'         => isset($extra_user_data['b_phone'])         ? $extra_user_data['b_phone']         : $user_data['phone'],
            's_phone'         => isset($extra_user_data['s_phone'])         ? $extra_user_data['s_phone']         : $user_data['phone'],

            'b_country'       => isset($extra_user_data['b_country'])       ? $extra_user_data['b_country']       : $general_settings['default_country'],
            's_country'       => isset($extra_user_data['s_country'])       ? $extra_user_data['s_country']       : $general_settings['default_country'],
            'b_state'         => isset($extra_user_data['b_state'])         ? $extra_user_data['b_state']         : $general_settings['default_state'],
            's_state'         => isset($extra_user_data['s_state'])         ? $extra_user_data['s_state']         : $general_settings['default_state'],
            'b_country_descr' => isset($extra_user_data['b_country_descr']) ? $extra_user_data['b_country_descr'] : '',
            's_country_descr' => isset($extra_user_data['s_country_descr']) ? $extra_user_data['s_country_descr'] : '',
            'b_state_descr'   => isset($extra_user_data['b_state_descr'])   ? $extra_user_data['b_state_descr']   : '',
            's_state_descr'   => isset($extra_user_data['s_state_descr'])   ? $extra_user_data['s_state_descr']   : '',

            'b_city'          => isset($extra_user_data['b_city'])          ? $extra_user_data['b_city']          : $general_settings['default_city'],
            's_city'          => isset($extra_user_data['s_city'])          ? $extra_user_data['s_city']          : $general_settings['default_city'],
            'b_address'       => isset($extra_user_data['b_address'])       ? $extra_user_data['b_address']       : $general_settings['default_address'],
            's_address'       => isset($extra_user_data['s_address'])       ? $extra_user_data['b_address']       : $general_settings['default_address'],
            'b_address_2'     => isset($extra_user_data['b_address_2'])     ? $extra_user_data['b_address_2']     : $general_settings['default_address'],
            'b_zipcode'       => isset($extra_user_data['b_zipcode'])       ? $extra_user_data['b_zipcode']       : $general_settings['default_address'],
            's_zipcode'       => isset($extra_user_data['s_zipcode'])       ? $extra_user_data['s_zipcode']       : $general_settings['default_address'],
        ]);

        $cart['user_data'] = $user_data;
        $customer_auth = fn_fill_auth([], [], false, 'C');
    }

    $cart_products_params = [];
    if (!empty($params['storefront_id'])) {
        $cart_products_params = [
            'storefront_id' => $params['storefront_id']
        ];
    }

    $cart_products = fn_get_cart_products($customer_id, $cart_products_params);
    $products = [];
    foreach ($cart_products as $product) {
        $product_id = $product['product_id'];
        $products[$product_id] = [
            'product_id'      => $product_id,
            'product_options' => isset($product['extra']['product_options']) ? $product['extra']['product_options'] : [],
            'amount'          => $product['amount'],
        ];

        if (isset($product['extra']['group_id']) && !empty($product['extra']['chosen_shipping'])) {
            $cart['chosen_shipping'][$product['extra']['group_id']] = $product['extra']['chosen_shipping'];
        }
    }

    fn_add_product_to_cart($products, $cart, $customer_auth);

    return [$cart, $customer_auth];
}

//
//Get order payment data
//
function fn_get_payment_data($payment_id, $object_id = 0, $lang_code = CART_LANGUAGE)
{
    $data = db_get_row("SELECT * FROM ?:payment_descriptions WHERE payment_id = ?i AND lang_code = ?s", $payment_id, $lang_code);

    fn_set_hook('get_payment_data', $data, $payment_id, $object_id, $lang_code);

    return $data;
}

function fn_get_status_params($status, $type = STATUSES_ORDER)
{
    $status_id = fn_get_status_id($status, $type);
    return db_get_hash_single_array("SELECT param, value FROM ?:status_data WHERE status_id = ?i", array('param', 'value'), $status_id);
}

/**
 * Gets parameter value of the status
 *
 * @param string $status Status code
 * @param string $param Parameter name
 * @param string $type Status type (order type defualt)
 * @return string Parameter value
 */
function fn_get_status_param_value($status, $param, $type = STATUSES_ORDER)
{
    $status_id = fn_get_status_id($status, $type);
    return db_get_field("SELECT value FROM ?:status_data WHERE status_id = ?i AND param = ?s", $status_id, $param);
}

//
// Delete product from the cart
//
function fn_delete_cart_product(&$cart, $cart_id, $full_erase = true)
{
    fn_set_hook('delete_cart_product', $cart, $cart_id, $full_erase);

    if (!empty($cart_id) && !empty($cart['products'][$cart_id])) {
        // Decrease product popularity
        if (!empty($cart['products'][$cart_id]['product_id'])) {
            $product_id = $cart['products'][$cart_id]['product_id'];

            $popularity = [
                'deleted' => 1,
                'total' => POPULARITY_DELETE_FROM_CART,
            ];

            fn_update_product_popularity($product_id, $popularity);

            unset(Tygh::$app['session']['products_popularity']['added'][$product_id]);
        }

        // Delete saved product files
        if (isset($cart['products'][$cart_id]['extra']['custom_files']) && $full_erase) {
            foreach ($cart['products'][$cart_id]['extra']['custom_files'] as $option_id => $images) {
                if (!empty($images)) {
                    foreach ($images as $image) {
                        Storage::instance('custom_files')->delete($image['path']);
                        Storage::instance('custom_files')->delete($image['path'] . '_thumb');
                    }
                }
            }
        }

        unset($cart['products'][$cart_id]);

        if (!empty($cart['product_groups'])) {
            foreach ($cart['product_groups'] as $group_key => $group) {
                if (isset($group['products'][$cart_id])) {
                    unset($cart['product_groups'][$group_key]['products'][$cart_id]);
                }
            }
        }

        if (!empty($cart['chosen_shipping']) && empty($cart['deny_unsetting_product_group'])) {
            $cart['calculate_shipping'] = true;
            unset($cart['product_groups']);
        }

        $cart['recalculate'] = true;
        $cart['change_cart_products'] = true;
    }

    return true;
}

//
// Checks whether this order used the current payment and calls the payment_cc_complete.php file
//
function fn_check_payment_script($script_name, $order_id, &$processor_data = null)
{
    $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
    $processor_data = fn_get_processor_data($payment_id);
    if ($processor_data['processor_script'] == $script_name) {
        return true;
    }

    return false;
}

//
// This function calculates product prices without taxes and with taxes
//
function fn_get_taxed_and_clean_prices(&$product, &$auth)
{
    $tax_value = 0;
    $included_tax = false;
    $is_tax_calculated_by_unit_price = Registry::get('settings.Checkout.tax_calculation') === 'unit_price';

    if (empty($product) || empty($product['product_id']) || empty($product['tax_ids'])) {
        return false;
    }
    if (isset($product['subtotal'])) {
        $tx_price =  $product['subtotal'];
    } elseif (empty($product['price'])) {
        $tx_price = 0;
    } elseif (isset($product['discounted_price'])) {
        if ($is_tax_calculated_by_unit_price) {
            $discount = $product['price'] - $product['discounted_price'];
            $tx_price = $product['price'];
        } else {
            $tx_price = $product['discounted_price'];
        }
    } else {
        $tx_price = $product['price'];
    }

    $product_taxes = fn_get_set_taxes($product['tax_ids']);

    $calculated_data = fn_calculate_tax_rates($product_taxes, $tx_price, 1, $auth, Tygh::$app['session']['cart']);
    // Apply taxes to product subtotal
    if (!empty($calculated_data)) {
        foreach ($calculated_data as $_k => $v) {
            $tax_value += $v['tax_subtotal'];
            if ($v['price_includes_tax'] != 'Y') {
                $included_tax = true;
                $tx_price += $v['tax_subtotal'];
            }
        }
    }
    if (isset($discount) && $is_tax_calculated_by_unit_price) {
        $tx_price -= $discount;
    }
    $product['clean_price'] = $tx_price - $tax_value;
    $product['taxed_price'] = $tx_price;
    $product['taxes'] = $calculated_data;
    $product['included_tax'] = $included_tax;
    if (YesNo::toBool($auth['tax_exempt'])) {
        $product['tax_value'] = $tax_value;
        if (isset($product['list_discount_prc'])) {
            $product['list_discount_prc'] = sprintf('%d', round($product['list_discount'] * 100 / ($product['list_price'] - $tax_value)));
        }
    }

    return true;
}

function fn_clear_cart(&$cart, $complete = false, $clear_all = false)
{
    fn_set_hook('clear_cart', $cart, $complete, $clear_all);

    // Decrease products popularity
    if (!empty($cart['products'])) {
        $pids = array();

        foreach ($cart['products'] as $product) {
            $pids[] = $product['product_id'];
            unset(Tygh::$app['session']['products_popularity']['added'][$product['product_id']]);
        }

        foreach ($pids as $pid) {
            fn_update_product_popularity(
                $pid,
                [
                    'deleted' => 1,
                    'total' => POPULARITY_DELETE_FROM_CART
                ]
            );
        }
    }

    if ($clear_all) {
        $cart = [];
    } else {
        $user_data = [];
        if (!SiteArea::isAdmin(AREA) && Tygh::$app['session']['auth']['user_id']) {
            $user_data = fn_get_user_info(Tygh::$app['session']['auth']['user_id']);
        } elseif (!empty($cart['user_data']) && !$complete) {
            $user_data = $cart['user_data'];
        }
        $cart = [
            'products'    => [],
            'recalculate' => false,
            'user_data'   => $user_data,
        ];
    }

    unset(Tygh::$app['session']['shipping_hash']);

    return true;
}

function fn_external_discounts($product)
{
    $discounts = 0;

    fn_set_hook('get_external_discounts', $product, $discounts);

    return $discounts;
}

//
// This function is used to find out the total shipping cost. Used in payments, quickbooks
//
function fn_order_shipping_cost($order_info)
{
    $cost = (floatval($order_info['shipping_cost'])) ? $order_info['shipping_cost'] : 0;

    if ($cost) {
        $cost += fn_order_shipping_taxes_cost($order_info);
    }

    return $cost ? fn_format_price($cost) : 0;
}

/**
 * Calculate the shipping taxes total.
 *
 * @param array $order_info
 * @return int
 */
function fn_order_shipping_taxes_cost($order_info)
{
    $cost = 0;

    if (!empty($order_info['taxes'])) {
        foreach ($order_info['taxes'] as $tax) {
            if ($tax['price_includes_tax'] == 'N') {
                foreach ($tax['applies'] as $_id => $value) {
                    if (strpos($_id, 'S_') !== false) {
                        $cost += $value;
                    }
                }
            }
        }
    }

    return $cost;
}

/**
 * Obfuscates sensitive data (card number and cvc/cvv2) in payment information.
 * Accordingly to PCI DSS, only the PAN, expiration date, service code, or cardholder name may be stored.
 *
 * @param string       $order_id     Order ID to clean up payment information
 * @param string|array $payment_info Payment information from the database or from the payment service
 * @param bool         $silent       If set to false, encryption progress bar will be shown
 * @param bool         $return_info  Specifies what to return: array with obfuscated information or encrypted string
 *
 * @return array|string|null Array with obufscated data when $order_id is not set and $return_info is true,
 *                           encrypted string to store in datbase when $order_id isn't set and $return_info is false,
 *                           nothing otherwise.
 */
function fn_cleanup_payment_info($order_id, $payment_info, $silent = false, $return_info = false)
{
    if ($silent == false) {
        fn_set_progress('echo', __('processing_order') . '&nbsp;<b>#'.$order_id.'</b>...');
    }

    if (!is_array($payment_info)) {
        $info = @unserialize(fn_decrypt_text($payment_info));
    } else {
        $info = $payment_info;
    }

    if (!empty($info['cvv2'])) {
        $info['cvv2'] = 'XXX';
    }
    if (!empty($info['card_number'])) {
        $info['card_number'] = str_replace(array('-', ' '), '', $info['card_number']);
        $info['card_number'] = substr_replace($info['card_number'], str_repeat('X', strlen($info['card_number']) - 4), 0, strlen($info['card_number']) - 4);
    }

    $_data = fn_encrypt_text(serialize($info));
    if (!empty($order_id)) {
        db_query("UPDATE ?:order_data SET data = ?s WHERE order_id = ?i AND type = 'P'", $_data, $order_id);
    } else {
        return $return_info ? $info : $_data;
    }
}

/**
 * Checks whether order can be placed.
 *
 * @param array    $cart            Array of the cart contents and user information necessary for purchase
 * @param array    $auth            Array with authorization data
 * @param int|null $parent_order_id Parent order id
 *
 * @return bool Whether order can be placed.
 */
function fn_allow_place_order(array &$cart, array $auth = [], $parent_order_id = null)
{
    $result = true;

    $total = Registry::get('settings.General.min_order_amount_type') === 'products_with_shippings'
        ? $cart['total']
        : $cart['subtotal'];

    fn_set_hook('allow_place_order', $total, $cart, $parent_order_id);

    // Check minimal amount only for parent order
    if (empty($parent_order_id)) {
        $checkout_settings = fn_get_checkout_settings($cart);
        $cart['min_order_amount'] = $checkout_settings['min_order_amount'];
        $cart['amount_failed'] = $cart['min_order_amount'] > $total && (float) $total;

        if (YesNo::toBool($checkout_settings['show_unavailable_shipping_methods'])) {
            $cart['shipping_failed'] = $cart['shipping_required'] && empty($cart['shipping']);
        }
    }

    if (!empty($cart['amount_failed']) || !empty($cart['shipping_failed']) || !empty($cart['company_shipping_failed'])) {
        $result = false;
    }

    /**
     * Action after checking if order can be placed.
     *
     * @param array    $cart            Array of the cart contents and user information necessary for purchase
     * @param array    $auth            Array with authorization data
     * @param int|null $parent_order_id Parent order id
     * @param int      $total           Order total
     * @param bool     $result          Flag determines if order can be placed
     */
    fn_set_hook('allow_place_order_post', $cart, $auth, $parent_order_id, $total, $result);

    return $result;
}

/**
 * Returns orders
 *
 * @param array  $params         Orders search params
 * @param int    $items_per_page Items per page
 * @param bool   $get_totals     Whether to get totals
 * @param string $lang_code      Two-letter language code (e.g. 'en', 'ru', etc.)
 *
 * @return array
 */
function fn_get_orders(array $params, $items_per_page = 0, $get_totals = false, $lang_code = CART_LANGUAGE)
{
    // Init filter
    $params = LastView::instance()->update('orders', $params);

    if (isset($params['extra'])) {
        $params['extra'] = (array) $params['extra'];
    }

    // Set default values to input params
    $default_params = [
        'page'           => 1,
        'items_per_page' => $items_per_page,
        'extra'          => ['issuers', 'invoice_docs', 'memo_docs']
    ];

    $params = array_merge($default_params, $params);

    foreach ($default_params['extra'] as $value_extra) {
        if (in_array($value_extra, $params['extra'])) {
            continue;
        }
        $params['extra'][] = $value_extra;
    }

    if (!empty($params['issuer']) && !in_array('issuers', $params['extra'], true)) {
        $params['extra'][] = 'issuers';
    }

    if ((!empty($params['invoice_id']) || !empty($params['has_invoice']))
        && !in_array('invoice_docs', $params['extra'], true)
    ) {
        $params['extra'][] = 'invoice_docs';
    }

    if ((!empty($params['credit_memo_id']) || !empty($params['has_credit_memo']))
        && !in_array('memo_docs', $params['extra'], true)
    ) {
        $params['extra'][] = 'memo_docs';
    }

    $params['include_incompleted'] = empty($params['include_incompleted']) ? false : $params['include_incompleted']; // default incomplited orders should not be displayed
    if (
        !empty($params['status']) && (is_array($params['status']) && in_array(OrderStatuses::INCOMPLETED, $params['status'])
            || !is_array($params['status']) && $params['status'] === OrderStatuses::INCOMPLETED)
    ) {
        $params['include_incompleted'] = true;
    }

    // Define fields that should be retrieved
    $fields = [
        '?:orders.order_id',
        '?:orders.issuer_id',
        '?:orders.user_id',
        '?:orders.is_parent_order',
        '?:orders.parent_order_id',
        '?:orders.company_id',
        '?:orders.company',
        '?:orders.timestamp',
        '?:orders.firstname',
        '?:orders.lastname',
        '?:orders.email',
        '?:orders.company',
        '?:orders.phone',
        '?:orders.status',
        '?:orders.total'
    ];

    // Define sort fields
    $sortings = [
        'order_id' => '?:orders.order_id',
        'status'   => '?:orders.status',
        'customer' => ['?:orders.lastname', '?:orders.firstname'],
        'email'    => '?:orders.email',
        'date'     => ['?:orders.timestamp', '?:orders.order_id'],
        'total'    => '?:orders.total',
    ];

    if (in_array('issuers', $params['extra'], true)) {
        $fields[] = "CONCAT(issuers.firstname, ' ', issuers.lastname) as issuer_name";
        $fields[] = 'issuers.email as issuer_email';
    }

    if (in_array('invoice_docs', $params['extra'], true)) {
        $fields[] = 'invoice_docs.doc_id as invoice_id';
    }

    if (in_array('memo_docs', $params['extra'], true)) {
        $fields[] = 'memo_docs.doc_id as credit_memo_id';
    }

    fn_set_hook('pre_get_orders', $params, $fields, $sortings, $get_totals, $lang_code);

    if (isset($params['compact']) && $params['compact'] === YesNo::YES) {
        $union_condition = ' OR ';
    } else {
        $union_condition = ' AND ';
    }

    $condition = $_condition = $join = $group = '';

    $condition .= db_quote(' AND ?:orders.is_parent_order != ?s', YesNo::YES);
    $condition .= fn_get_company_condition('?:orders.company_id');

    if (in_array('issuers', $params['extra'], true)) {
        $join = db_quote(' LEFT JOIN ?:users as issuers ON issuers.user_id = ?:orders.issuer_id');
    }

    if (in_array('invoice_docs', $params['extra'], true)) {
        $join .= " LEFT JOIN ?:order_docs as invoice_docs ON invoice_docs.order_id = ?:orders.order_id AND invoice_docs.type = 'I'";
    }

    if (in_array('memo_docs', $params['extra'], true)) {
        $join .= " LEFT JOIN ?:order_docs as memo_docs ON memo_docs.order_id = ?:orders.order_id AND memo_docs.type = 'C'";
    }

    if (in_array('user_type', $params['extra'], true)) {
        $fields[] = '?:users.user_type';
        $join .= ' LEFT JOIN ?:users ON ?:users.user_id = ?:orders.user_id';
    }

    if (isset($params['phone']) && !empty($params['phone'])) {
        $phone = '%' . $params['phone'] . '%';
        $condition .= db_quote(
            " AND ((REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(?:orders.phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') LIKE ?l)"
            . " OR (REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(b_phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') LIKE ?l)"
            . " OR (REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(s_phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') LIKE ?l))",
            $phone,
            $phone,
            $phone
        );
    }

    if (isset($params['cname']) && fn_string_not_empty($params['cname'])) {
        $customer_name = fn_explode(' ', trim($params['cname']));
        $customer_name = array_values(array_filter($customer_name, 'fn_string_not_empty'));
        if (sizeof($customer_name) == 2) {
            $_condition .= db_quote(
                ' ?p ((?:orders.firstname LIKE ?l AND ?:orders.lastname LIKE ?l)'
                . ' OR (?:orders.firstname LIKE ?l AND ?:orders.lastname LIKE ?l))',
                $union_condition,
                '%' . $customer_name[0] . '%',
                '%' . $customer_name[1] . '%',
                '%' . $customer_name[1] . '%',
                '%' . $customer_name[0] . '%'
            );
        } else {
            $_condition .= db_quote(
                ' ?p (?:orders.firstname LIKE ?l OR ?:orders.lastname LIKE ?l)',
                $union_condition,
                '%' . trim($params['cname']) . '%',
                '%' . trim($params['cname']) . '%'
            );
        }
    }

    if (isset($params['issuer']) && fn_string_not_empty($params['issuer'])) {
        $issuer_name = fn_explode(' ', $params['issuer']);
        $issuer_name = array_filter($issuer_name, "fn_string_not_empty");
        if (sizeof($issuer_name) == 2) {
            $_condition .= db_quote(" $union_condition (issuers.firstname LIKE ?l AND issuers.lastname LIKE ?l)", array_shift($issuer_name) . "%", "%" . array_shift($issuer_name) . "%");
        } else {
            $issuer_name = array_shift($issuer_name);
            $_condition .= db_quote(" $union_condition (issuers.firstname LIKE ?l OR issuers.lastname LIKE ?l)", $issuer_name . "%", "%" . $issuer_name . "%");
        }
    }

    if (!empty($params['no_issuer'])) {
        $condition .= db_quote(' AND ?:orders.issuer_id IS NULL');
    }

    if (isset($params['company_id']) && $params['company_id'] != '') {
        $condition .= db_quote(' AND ?:orders.company_id = ?i ', $params['company_id']);
    }

    if (!empty($params['company_ids']) && is_array($params['company_ids'])) {
        $condition .= db_quote(' AND ?:orders.company_id IN (?n)', $params['company_ids']);
    }

    if (!empty($params['tax_exempt'])) {
        $condition .= db_quote(" AND ?:orders.tax_exempt = ?s", $params['tax_exempt']);
    }

    if (!empty($params['company'])) {
        $condition .= db_quote(" AND ?:orders.company LIKE ?l", '%' . $params['company'] . '%');
    }

    if (isset($params['email']) && fn_string_not_empty($params['email'])) {
        $_condition .= db_quote(" $union_condition ?:orders.email LIKE ?l", "%" . trim($params['email']) . "%");
    }

    if (!empty($params['user_id'])) {
        $condition .= db_quote(' AND ?:orders.user_id IN (?n)', $params['user_id']);
    }

    if (isset($params['total_from']) && fn_is_numeric($params['total_from'])) {
        $condition .= db_quote(" AND ?:orders.total >= ?d", fn_convert_price($params['total_from']));
    }

    if (!empty($params['total_to']) && fn_is_numeric($params['total_to'])) {
        $condition .= db_quote(" AND ?:orders.total <= ?d", fn_convert_price($params['total_to']));
    }

    if (isset($params['total_sec_from']) && fn_is_numeric($params['total_sec_from'])) {
        $condition .= db_quote(" AND ?:orders.total >= ?d", fn_convert_price($params['total_sec_from'], CART_SECONDARY_CURRENCY));
    }

    if (!empty($params['total_sec_to']) && fn_is_numeric($params['total_sec_to'])) {
        $condition .= db_quote(" AND ?:orders.total <= ?d", fn_convert_price($params['total_sec_to'], CART_SECONDARY_CURRENCY));
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(' AND ?:orders.status IN (?a)', $params['status']);
    }

    if (empty($params['include_incompleted'])) {
        $condition .= db_quote(' AND ?:orders.status != ?s', OrderStatuses::INCOMPLETED);
    }

    if (!empty($params['storefront_id'])) {
        $condition .= db_quote(' AND ?:orders.storefront_id IN (?n)', (array) $params['storefront_id']);
    }

    if (!empty($params['order_id'])) {
        $_condition .= db_quote($union_condition . ' ?:orders.order_id IN (?n)', (!is_array($params['order_id']) && (strpos($params['order_id'], ',') !== false) ? explode(',', $params['order_id']) : $params['order_id']));
    }

    if (!empty($params['p_ids']) || !empty($params['product_view_id'])) {
        $arr = (strpos($params['p_ids'], ',') !== false || !is_array($params['p_ids'])) ? explode(',', $params['p_ids']) : $params['p_ids'];

        if (empty($params['product_view_id'])) {
            $condition .= db_quote(" AND ?:order_details.product_id IN (?n)", $arr);
        } else {
            $condition .= db_quote(" AND ?:order_details.product_id IN (?n)", db_get_fields(fn_get_products(array('view_id' => $params['product_view_id'], 'get_query' => true))));
        }

        $join .= " LEFT JOIN ?:order_details ON ?:order_details.order_id = ?:orders.order_id";
        $group = " GROUP BY ?:orders.order_id ";
    }

    $docs_conditions = array();
    if (!empty($params['invoice_id']) || !empty($params['has_invoice'])) {
        if (!empty($params['has_invoice'])) {
            $docs_conditions[] = "invoice_docs.doc_id IS NOT NULL";
        } elseif (!empty($params['invoice_id'])) {
            $docs_conditions[] = db_quote("invoice_docs.doc_id = ?i", $params['invoice_id']);
        }
    }

    if (!empty($params['credit_memo_id']) || !empty($params['has_credit_memo'])) {
        if (!empty($params['has_credit_memo'])) {
            $docs_conditions[] = "memo_docs.doc_id IS NOT NULL";
        } elseif (!empty($params['credit_memo_id'])) {
            $docs_conditions[] = db_quote("memo_docs.doc_id = ?i", $params['credit_memo_id']);
        }
    }

    if (!empty($docs_conditions)) {
        $condition .= ' AND (' . implode(' OR ', $docs_conditions) . ')';
    }

    if (!empty($params['shippings'])) {
        $set_conditions = array();
        foreach ($params['shippings'] as $v) {
            $set_conditions[] = db_quote("FIND_IN_SET(?s, ?:orders.shipping_ids)", $v);
        }
        $condition .= ' AND (' . implode(' OR ', $set_conditions) . ')';
    }

    if (!empty($params['payments'])) {
        $condition .= db_quote(" AND ?:orders.payment_id IN (?n)", $params['payments']);
    }

    if (!empty($params['period']) && $params['period'] != 'A') {
        [$params['time_from'], $params['time_to']] = fn_create_periods($params);

        $condition .= db_quote(" AND (?:orders.timestamp >= ?i AND ?:orders.timestamp <= ?i)", $params['time_from'], $params['time_to']);
    }

    if (isset($params['updated_at_from'])) {
        $condition .= db_quote(' AND ?:orders.updated_at >= ?i', $params['updated_at_from']);
    }

    if (isset($params['updated_at_to'])) {
        $condition .= db_quote(' AND ?:orders.updated_at <= ?i', $params['updated_at_to']);
    }

    if (!empty($params['custom_files']) && $params['custom_files'] == 'Y') {
        $condition .= db_quote(" AND ?:order_details.extra LIKE ?l", '%custom_files%');

        if (empty($params['p_ids']) && empty($params['product_view_id'])) {
            $join .= " LEFT JOIN ?:order_details ON ?:order_details.order_id = ?:orders.order_id";
            $group = " GROUP BY ?:orders.order_id ";
        }
    }

    if (!empty($params['parent_order_id'])) {
        $condition .= db_quote(' AND ?:orders.parent_order_id IN (?n)', (array) $params['parent_order_id']);
    }

    if (!empty($params['company_name'])) {
        $fields[] = '?:companies.company as company_name';
        $join .= " LEFT JOIN ?:companies ON ?:companies.company_id = ?:orders.company_id";
    }

    if (!empty($_condition)) {
        $condition .= ' AND (' . ($union_condition == ' OR ' ? '0 ' : '1 ') . $_condition . ')';
    }

    fn_set_hook('get_orders', $params, $fields, $sortings, $condition, $join, $group);

    $sorting = db_sort($params, $sortings, 'date', 'desc');

    // Used for Extended search
    if (!empty($params['get_conditions'])) {
        return array($fields, $join, $condition);
    }

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $limit = db_paginate($params['page'], $params['items_per_page']);
        $params['total_items'] = db_get_field(
            'SELECT COUNT(DISTINCT (?:orders.order_id))'
            . ' FROM ?:orders'
            . ' ?p'
            . ' WHERE 1 ?p',
            $join,
            $condition
        );
    }

    $orders = db_get_array('SELECT ' . implode(', ', $fields) . " FROM ?:orders $join WHERE 1 $condition $group $sorting $limit");

    fn_set_hook('get_orders_post', $params, $orders);

    foreach ($orders as $k => $order) {
        if (isset($order['ip_address'])) {
            $order['ip_address'] = fn_ip_from_db($order['ip_address']);
        }
    }

    if ($get_totals) {
        $paid_statuses = fn_get_settled_order_statuses();
        $totals = [];

        /**
         * Executes before get orders totals. Allows to modify and extend totals.
         *
         * @param array<string> $paid_statuses List of settled order statuses
         * @param string        $join          String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
         * @param string        $condition     String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
         * @param string        $group         String containing the SQL-query GROUP BY field
         * @param array         $totals        Orders totals
         */
        fn_set_hook('get_orders_totals', $paid_statuses, $join, $condition, $group, $totals);

        $totals['gross_total'] = db_get_field(
            'SELECT sum(t.total) FROM (SELECT total FROM ?:orders ?p WHERE 1 ?p ?p) as t',
            $join,
            $condition,
            $group
        );
        $totals['totally_paid'] = db_get_field(
            'SELECT sum(t.total) FROM (SELECT total FROM ?:orders ?p WHERE ?:orders.status IN (?a) ?p ?p) as t',
            $join,
            $paid_statuses,
            $condition,
            $group
        );

        $params['paid_statuses'] = $paid_statuses;
    } else {
        $totals = [];
    }

    LastView::instance()->processResults('orders', $orders, $params);

    return [$orders, $params, $totals];
}

/**
 * Gets shipping method parameters by identifier
 *
 * @param int $shipping_id Shipping identifier
 * @return array Shipping parameters
 */
function fn_get_shipping_params($shipping_id)
{
    $params = array();
    if ($shipping_id) {
        $params = db_get_field("SELECT service_params FROM ?:shippings WHERE shipping_id = ?i", $shipping_id);

        if (!empty($params)) {
            $params = unserialize($params);
        }
    }

    return $params;
}

/**
 * Gets shipping service data by identifier
 *
 * @param int $service_id Shipping service identifier
 * @return array Shipping service data
 */
function fn_get_shipping_service_data($service_id)
{
    static $services = array();

    if (!isset($services[$service_id])) {

        $service = db_get_row("SELECT code, module FROM ?:shipping_services WHERE service_id = ?i AND status = 'A'", $service_id);

        if (empty($service)) {
            $services[$service_id] = false;

            return false;
        }

        $services[$service_id] = $service;
    }

    return $services[$service_id];
}

/**
 * Convert weight to pounds/ounces
 *
 * @param float $weight Weight
 *
 * @deprecated since 4.11.5. Use the fn_convert_weight_to_imperial_units function to convert weight.
 *
 * @see fn_convert_weight_to_imperial_units
 *
 * @return array converted data
 */
function fn_expand_weight($weight)
{
    return fn_convert_weight_to_imperial_units($weight);
}

/**
 * Convert weight to kilograms/grams
 *
 * @param float|string $weight Weight in the unit of weight specified in the store settings
 *
 * @return array{full_grams: float, full_kilograms: float, kilograms: float, grams: float, plain:float} Converted data
 */
function fn_convert_weight_to_metric_units($weight)
{
    $full_grams = ceil($weight * Registry::get('settings.General.weight_symbol_grams'));
    $full_kilograms = (float) sprintf('%.1f', $full_grams / 1000);
    $kilograms = floor($full_kilograms);
    $grams = $full_grams - $kilograms * 1000;

    return [
        'full_grams'     => $full_grams,
        'full_kilograms' => $full_kilograms,
        'grams'          => $grams,
        'kilograms'      => $kilograms,
        'plain'          => (float) $weight,
    ];
}

/**
 * Convert weight to pounds/ounces
 *
 * @param float|string $weight Weight in the unit of weight specified in the store settings
 *
 * @return array{full_ounces: float, full_pounds: float, pounds: float, ounces: float, plain:float} Converted data
 */
function fn_convert_weight_to_imperial_units($weight)
{
    $full_ounces = ceil(round($weight * Registry::get('settings.General.weight_symbol_grams') / 28.35, 3));
    $full_pounds = (float) sprintf('%.1f', $full_ounces / 16);
    $pounds = floor($full_ounces / 16);
    $ounces = $full_ounces - $pounds * 16;

    return [
        'full_ounces' => $full_ounces,
        'full_pounds' => $full_pounds,
        'pounds'      => $pounds,
        'ounces'      => $ounces,
        'plain'       => (float) $weight,
    ];
}

/**
 * Generate unique ID to cache rates calculation results
 *
 * @param mixed parameters to generate unique ID from
 * @return mixed array with rates if calculated, false otherwise
 */
function fn_generate_cached_rate_id()
{
    return md5(serialize(func_get_args()));
}

/**
 * Sends order notification.
 *
 * @param array         $order_info         Order information
 * @param array         $edp_data           Information about downloadable products
 * @param bool|string[] $force_notification Notification rules to override default status notification settings
 * @param string|null   $event_id           Id of event for event based notification system
 *
 * @deprecated since 4.11.1. Use the Tygh::$app['event.dispatcher'] service to send messages.
 * @see \Tygh\Notifications\EventDispatcher
 */
function fn_order_notification(&$order_info, $edp_data = array(), $force_notification = array(), $event_id = null)
{
    static $notified = array();

    $send_order_notification = true;

    if (
        (!empty($notified[$order_info['order_id']][$order_info['status']]) && $notified[$order_info['order_id']][$order_info['status']])
        || $order_info['status'] === OrderStatuses::INCOMPLETED || $order_info['status'] === OrderStatuses::PARENT
    ) {
        $send_order_notification = false;
    }

    fn_set_hook('send_order_notification', $order_info, $edp_data, $force_notification, $notified, $send_order_notification);

    if (!$send_order_notification) {
        return;
    }

    $order_statuses = fn_get_statuses(STATUSES_ORDER, array(), true, false, ($order_info['lang_code'] ? $order_info['lang_code'] : CART_LANGUAGE), $order_info['company_id']);

    $notify_user = $notify_department = $notify_vendor = true;

    if (!is_array($force_notification)) {
        $force_notification = fn_get_notification_rules($force_notification, !$force_notification);
    }
    if (isset($force_notification[UserTypes::CUSTOMER])) {
        $notify_user = $force_notification[UserTypes::CUSTOMER];
    }
    if (isset($force_notification[UserTypes::ADMIN])) {
        $notify_department = $force_notification[UserTypes::ADMIN];
    }
    if (isset($force_notification[UserTypes::VENDOR])) {
        $notify_vendor = $force_notification[UserTypes::VENDOR];
    }
    $notified[$order_info['order_id']][$order_info['status']] = $notify_user || $notify_department || $notify_vendor;

    $status_id = strtolower($order_info['status']);

    /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
    $event_dispatcher = Tygh::$app['event.dispatcher'];

    /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
    $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];
    $notification_rules = $notification_settings_factory->create([
        UserTypes::CUSTOMER => $notify_user,
        UserTypes::VENDOR   => $notify_vendor,
        UserTypes::ADMIN    => $notify_department,
    ]);
    $dispatching_event_id = isset($event_id) ? $event_id : "order.status_changed.{$status_id}";

    $event_dispatcher->dispatch(
        $dispatching_event_id,
        ['order_info' => $order_info],
        $notification_rules,
        new OrderProvider($order_info)
    );
    if ($edp_data) {
        $event_dispatcher->dispatch(
            'order.edp',
            ['order_info' => $order_info, 'edp_data' => $edp_data],
            $notification_rules,
            new OrderProvider($order_info, $edp_data)
        );
    }

    /**
     * Executes after an order notification was sent.
     *
     * @param array  $order_info         Order information
     * @param array  $order_statuses     Information about order statuses
     * @param bool[] $force_notification Notification rules to override default status notification settings
     *
     * @deprecated since 4.11.1. Use `place_order_manually_post`, `update_order_details_post` and
     * `change_order_status_post` hooks instead.
     */
    fn_set_hook('order_notification', $order_info, $order_statuses, $force_notification);
}

/**
 *
 * @param int $payment_id payment ID
 * @param string $action action
 * @return array (boolean, string)
 */
function fn_check_processor_script($payment_id, $additional_params = false)
{

    if ($additional_params) {
        if (!empty($_REQUEST['skip_payment']) && AREA == 'C') {
            return array(false, '');
        }
    }

    $payment = fn_get_payment_method_data((int) $payment_id);

    if (!empty($payment['processor_id'])) {
        $processor_data = fn_get_processor_data($payment['payment_id']);
        if (!empty($processor_data['processor_script'])) {
            $script_path = fn_get_processor_script_path($processor_data['processor_script']);

            if (!empty($script_path)) {
                return array(true, $processor_data);
            }
        }
    }

    return array(false, '');
}

/**
 * Check if store can use processor script
 *
 * @param string $processor name of processor script
 * @param string $area current working area
 * @return bool
 */
function fn_check_prosessor_status($processor, $area = AREA)
{
    $is_active = false;

    $processor = fn_get_processor_data_by_name($processor . '.php');
    if (!empty($processor)) {
        $payments = fn_get_payment_by_processor($processor['processor_id']);

        if (!empty($payments)) {
            foreach ($payments as $payment) {
                if ($payment['status'] == 'A' || $area == 'A') { // admin can use disable payments
                    $is_active = true;
                }
            }
        }
    }

    return $is_active;
}

function fn_add_product_options_files($product_data, &$cart, &$auth, $update = false, $location = 'cart')
{
    // Check if products have custom images
    if (!$update) {
        $uploaded_data = fn_filter_uploaded_data('product_data');
    } else {
        $uploaded_data = fn_filter_uploaded_data('cart_products');
    }

    // Check for the already uploaded files
    if (!empty($product_data['custom_files']['uploaded'])) {
        foreach ($product_data['custom_files']['uploaded'] as $file_id => $file_data) {
            if (Storage::instance('images')->isExist('sess_data/' . fn_basename($file_data['path']))) {
                $id = $file_data['product_id'] . $file_data['option_id'] . $file_id;
                $uploaded_data[$id] = array(
                    'name' => $file_data['name'],
                    'path' => 'sess_data/' . fn_basename($file_data['path']),
                );

                $product_data['custom_files'][$id] = $file_data['product_id'] . '_' . $file_data['option_id'];
            }
        }
    }

    if (!empty($uploaded_data) && !empty($product_data['custom_files'])) {
        $files_data = array();

        foreach ($uploaded_data as $key => $file) {
            $file_info = fn_pathinfo($file['name']);
            $file['extension'] = empty($file_info['extension']) ? '' : $file_info['extension'];
            $file['is_image'] = isset($file['type']) ? fn_get_image_extension($file['type']) : false;

            $_data = explode('_', $product_data['custom_files'][$key]);
            $product_id = empty($_data[0]) ? 0 : $_data[0];
            $option_id = empty($_data[1]) ? 0 : $_data[1];
            $file_id = str_replace($option_id . $product_id, '', $key);

            if (empty($file_id)) {
                $files_data[$product_id][$option_id][] = $file;
            } else {
                $files_data[$product_id][$option_id][$file_id] = $file;
            }
        }
    }

    unset($product_data['custom_files']);

    foreach ($product_data as $key => $data) {
        $product_id = (!empty($data['product_id'])) ? $data['product_id'] : $key;

        // Check if product has custom images
        if ($update || isset($files_data[$key])) {
            $hash = $key;
        } else {
            $hash = $product_id;
        }

        $_options = fn_get_product_options($product_id);
        if (!empty($files_data[$hash]) && is_array($files_data[$hash])) {

            foreach ($files_data[$hash] as $option_id => $files) {
                foreach ($files as $file_id => $file) {
                    // Check for the allowed extensions
                    if (!empty($_options[$option_id]['allowed_extensions'])) {
                        if ((empty($file['extension']) && !empty($_options[$option_id]['allowed_extensions'])) || !preg_match("/\b" . $file['extension'] . "\b/i", $_options[$option_id]['allowed_extensions'])) {
                            fn_set_notification('E', __('error'), $file['name'] . ': ' . __('text_forbidden_uploaded_file_extension', array(
                                '[ext]' => $file['extension'],
                                '[exts]' => $_options[$option_id]['allowed_extensions'],
                            )));
                            unset($files_data[$hash][$option_id][$file_id]);
                            continue;
                        }
                    }

                    // Check for the max file size

                    if (!empty($_options[$option_id]['max_file_size'])) {
                        if (empty($file['size'])) {
                            $file['size'] = filesize($file['path']);
                        }

                        if ($file['size'] > $_options[$option_id]['max_file_size'] * 1024) {
                            fn_set_notification('E', __('error'), $file['name'] . ': ' . __('text_forbidden_uploaded_file_size', array(
                                '[size]' => $_options[$option_id]['max_file_size'] . ' kb',
                            )));
                            unset($files_data[$hash][$option_id][$file_id]);
                            continue;
                        }
                    }

                    $_file_path = 'sess_data/file_' . uniqid(TIME);

                    [, $_file_path] = Storage::instance('custom_files')->put($_file_path, [
                        'file' => $file['path'],
                    ]);

                    if (!$_file_path) {
                        fn_set_notification('E', __('error'), __('text_cannot_create_file', [
                            '[file]' => $file['name'],
                        ]));

                        unset($files_data[$hash][$option_id][$file_id]);
                        continue;
                    }

                    $file['path'] = $_file_path;
                    $file['file'] = fn_basename($file['path']);

                    if ($file['is_image']) {
                        $file['thumbnail'] = 'image.custom_image?image=' . $file['file'] . '&type=T';
                        $file['detailed'] = 'image.custom_image?image=' . $file['file'] . '&type=D';
                    }

                    $file['location'] = $location;

                    if ($update) {
                        $cart['products'][$key]['extra']['custom_files'][$option_id][] = $file;
                    } else {
                        $data['extra']['custom_files'][$option_id][] = $file;

                    }
                }

                if ($update) {
                    if (!empty($cart['products'][$key]['product_options'][$option_id])) {
                        $cart['products'][$key]['product_options'][$option_id] = md5(serialize($cart['products'][$key]['extra']['custom_files'][$option_id]));
                    }
                } else {
                    if (!empty($data['extra']['custom_files'][$option_id])) {
                        $data['product_options'][$option_id] = md5(serialize($data['extra']['custom_files'][$option_id]));
                    }
                }
            }

            // Check the required options
            if (empty($data['extra']['parent'])) {
                foreach ($_options as $option) {
                    if ($option['option_type'] == 'F' && $option['required'] == 'Y' && !$update) {
                        if (empty($data['product_options'][$option['option_id']])) {
                            fn_set_notification('E', __('error'), __('product_cannot_be_added'));

                            unset($product_data[$key]);

                            return array($product_data, $cart);
                        }
                    }
                }
            }

        } else {
            if (empty($data['extra']['parent'])) {
                foreach ($_options as $option) {
                    if (
                        $option['option_type'] === ProductOptionTypes::FILE
                        && YesNo::isTrue($option['required'])
                        && empty($cart['skip_product_option_file_validation'])
                        && empty($cart['products'][$hash]['extra']['custom_files'][$option['option_id']])
                        && empty($data['extra']['custom_files'][$option['option_id']])
                    ) {
                        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('product_cannot_be_added'));

                        unset($product_data[$key]);

                        return array($product_data, $cart);
                    }
                }
            }
        }

        if ($update) {
            foreach ($_options as $option) {
                if ($option['option_type'] == 'F' && empty($cart['products'][$key]['extra']['custom_files'][$option['option_id']])) {
                    unset($cart['products'][$key]['extra']['custom_files'][$option['option_id']]);
                    unset($cart['products'][$key]['product_options'][$option['option_id']]);
                    unset($data['product_options'][$option['option_id']]);
                }
            }
        }

        if (isset($cart['products'][$key]['extra']['custom_files'])) {
            foreach ($cart['products'][$key]['extra']['custom_files'] as $option_id => $files) {
                foreach ($files as $file) {
                    $data['extra']['custom_files'][$option_id][] = $file;
                }

                $data['product_options'][$option_id] = md5(serialize($files));
            }
        }

        $product_data[$key] = $data;
    }

    return array($product_data, $cart);
}

/**
 *   save stored taxes for products
 * @param array $cart cart
 * @param int $update_id   key of $cart['products'] to be updated
 * @param int $new_id  new key
 * @param bool $consider_existing  whether consider or not existing key
 */
function fn_update_stored_cart_taxes(&$cart, $update_id, $new_id, $consider_existing = false)
{
    if (!empty($cart['taxes']) && is_array($cart['taxes'])) {
        foreach ($cart['taxes'] as $t_id => $s_tax) {
            if (!empty($s_tax['applies']) && is_array($s_tax['applies'])) {
                $compare_key = 'P_' . $update_id;
                $new_key = 'P_' . $new_id;
                if (array_key_exists($compare_key, $s_tax['applies'])) {
                    $cart['taxes'][$t_id]['applies'][$new_key] = (isset($s_tax['applies'][$new_key]) && $consider_existing ? $s_tax['applies'][$new_key] : 0) + $s_tax['applies'][$compare_key];
                    unset($cart['taxes'][$t_id]['applies'][$compare_key]);
                }
            }
        }
    }
}

function fn_define_original_amount($product_id, $cart_id, &$product, $prev_product)
{
    if (!empty($prev_product['original_product_data']) && !empty($prev_product['original_product_data']['amount'])) {
        $product['original_amount'] = $prev_product['original_product_data']['amount'];
        $product['original_product_data'] = $prev_product['original_product_data'];
    } elseif (!empty($prev_product['original_amount'])) {
        $product['original_amount'] = $prev_product['original_amount'];
    }
}

function fn_get_shipments_info($params, $items_per_page = 0)
{
    // Init view params
    $params = LastView::instance()->update('shipments', $params);

    // Set default values to input params
    $default_params = array (
        'page' => 1,
        'items_per_page' => $items_per_page,
    );

    $params = array_merge($default_params, $params);

    $fields_list = array(
        '?:shipments.shipment_id',
        '?:shipments.timestamp AS shipment_timestamp',
        '?:shipments.comments',
        '?:shipments.status',
        '?:shipment_items.order_id',
        '?:orders.timestamp AS order_timestamp',
        '?:orders.s_firstname',
        '?:orders.s_lastname',
        '?:orders.firstname',
        '?:orders.lastname',
        '?:orders.company',
        '?:orders.user_id',
    );

    $joins = array(
        'LEFT JOIN ?:shipment_items ON (?:shipments.shipment_id = ?:shipment_items.shipment_id)',
        'LEFT JOIN ?:orders ON (?:shipment_items.order_id = ?:orders.order_id)',
    );

    $condition = '';
    if (Registry::get('runtime.company_id')) {
        $joins[] = 'LEFT JOIN ?:companies ON (?:companies.company_id = ?:orders.company_id)';
        $condition = db_quote(' AND ?:companies.company_id = ?i', Registry::get('runtime.company_id'));
    }

    $group = array(
        '?:shipments.shipment_id',
    );

    // Define sort fields
    $sortings = array (
        'id' => "?:shipments.shipment_id",
        'status' => "?:shipments.status",
        'order_id' => "?:orders.order_id",
        'shipment_date' => "?:shipments.timestamp",
        'order_date' => "?:orders.timestamp",
        'customer' => array("?:orders.s_lastname", "?:orders.s_firstname"),
    );

    $sorting = db_sort($params, $sortings, 'id', 'desc');

    if (isset($params['advanced_info']) && $params['advanced_info']) {
        $fields_list[] = '?:shipments.shipping_id';
        $fields_list[] = '?:shipping_descriptions.shipping AS shipping';
        $fields_list[] = '?:shipments.tracking_number';
        $fields_list[] = '?:shipments.carrier';

        $joins[] = ' LEFT JOIN ?:shippings ON (?:shipments.shipping_id = ?:shippings.shipping_id)';
        $joins[] = db_quote(' LEFT JOIN ?:shipping_descriptions ON (?:shippings.shipping_id = ?:shipping_descriptions.shipping_id AND ?:shipping_descriptions.lang_code = ?s)', DESCR_SL);
    }

    if (!empty($params['order_id'])) {
        $condition .= db_quote(' AND ?:shipment_items.order_id = ?i', $params['order_id']);
    }

    if (!empty($params['shipment_id'])) {
        $condition .= db_quote(' AND ?:shipments.shipment_id = ?i', $params['shipment_id']);
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(' AND ?:shipments.status = ?s', $params['status']);
    }

    if (isset($params['cname']) && fn_string_not_empty($params['cname'])) {
        $arr = fn_explode(' ', $params['cname']);
        foreach ($arr as $k => $v) {
            if (!fn_string_not_empty($v)) {
                unset($arr[$k]);
            }
        }
        if (sizeof($arr) == 2) {
            $condition .= db_quote(" AND ?:orders.firstname LIKE ?l AND ?:orders.lastname LIKE ?l", "%".array_shift($arr)."%", "%".array_shift($arr)."%");
        } else {
            $condition .= db_quote(" AND (?:orders.firstname LIKE ?l OR ?:orders.lastname LIKE ?l)", "%".trim($params['cname'])."%", "%".trim($params['cname'])."%");
        }
    }

    if (!empty($params['company'])) {
        $condition .= db_quote(" AND ?:orders.company LIKE ?l", "%" . $params['company'] . "%");
    }

    if (!empty($params['p_ids']) || !empty($params['product_view_id'])) {
        $arr = (strpos($params['p_ids'], ',') !== false || !is_array($params['p_ids'])) ? explode(',', $params['p_ids']) : $params['p_ids'];

        if (empty($params['product_view_id'])) {
            $condition .= db_quote(" AND ?:shipment_items.product_id IN (?n)", $arr);
        } else {
            $condition .= db_quote(" AND ?:shipment_items.product_id IN (?n)", db_get_fields(fn_get_products(array('view_id' => $params['product_view_id'], 'get_query' => true)), ','));
        }

        $joins[] = "LEFT JOIN ?:order_details ON ?:order_details.order_id = ?:orders.order_id";
    }

    if (!empty($params['shipment_period']) && $params['shipment_period'] != 'A') {
        [$params['shipment_time_from'], $params['shipment_time_to']] = fn_create_periods($params, 'shipment_');

        $condition .= db_quote(" AND (?:shipments.timestamp >= ?i AND ?:shipments.timestamp <= ?i)", $params['shipment_time_from'], $params['shipment_time_to']);
    }

    if (!empty($params['order_period']) && $params['order_period'] != 'A') {
        [$params['order_time_from'], $params['order_time_to']] = fn_create_periods($params, 'order_');

        $condition .= db_quote(" AND (?:orders.timestamp >= ?i AND ?:orders.timestamp <= ?i)", $params['order_time_from'], $params['order_time_to']);
    }

    fn_set_hook('get_shipments', $params, $fields_list, $joins, $condition, $group);

    $fields_list = implode(', ', $fields_list);
    $joins = implode(' ', $joins);
    $group = implode(', ', $group);

    if (!empty($group)) {
        $group = ' GROUP BY ' . $group;
    }

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(DISTINCT(?:shipments.shipment_id)) FROM ?:shipments $joins WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $shipments = db_get_array("SELECT $fields_list FROM ?:shipments $joins WHERE 1 $condition $group $sorting $limit");

    if (isset($params['advanced_info']) && $params['advanced_info'] && !empty($shipments)) {
        $shipment = reset($shipments);
        $order_info = fn_get_order_info($shipment['order_id']);

        foreach ($shipments as $id => $shipment) {
            $items = db_get_array('SELECT item_id, amount FROM ?:shipment_items WHERE shipment_id = ?i', $shipment['shipment_id']);
            if (!empty($items)) {
                foreach ($items as $item) {
                    $shipments[$id]['products'][$item['item_id']] = $item['amount'];
                    if (!empty($order_info['products'][$item['item_id']]['extra']['group_key'])) {
                        $shipments[$id]['group_key'] = $order_info['products'][$item['item_id']]['extra']['group_key'];
                    } else {
                        $shipments[$id]['group_key'] = !empty($order_info['shipping'][0]['group_key'])? $order_info['shipping'][0]['group_key'] : 0;
                    }
                }
            }

            if (!empty($shipment['carrier'])) {
                $shipments[$id]['carrier_info'] = Shippings::getCarrierInfo($shipment['carrier'], $shipment['tracking_number']);
            }
        }

        foreach ($shipments as $id => $shipment) {
            $shipments[$id]['one_full'] = true;

            foreach ($order_info['products'] as $product_key => $product) {
                $group_key = !empty($product['extra']['group_key']) ? $product['extra']['group_key'] : 0;
                if ($shipment['group_key'] == $group_key) {
                    if (empty($shipment['products'][$product_key]) || $shipment['products'][$product_key] < $product['amount']) {
                        $shipments[$id]['one_full'] = false;
                        break;
                    }
                }
            }
        }
    }

    foreach ($shipments as &$shipment) {
        if (empty($shipment['s_firstname'])) {
            $shipment['s_firstname'] = $shipment['firstname'];
        }
        if (empty($shipment['s_lastname'])) {
            $shipment['s_lastname'] = $shipment['lastname'];
        }
    }
    unset($shipment);

    /**
     * Changes selected shipments
     *
     * @param array $shipments Array of shipments
     * @param array $params    Shipments search params
     */
    fn_set_hook('get_shipments_info_post', $shipments, $params);

    LastView::instance()->processResults('shipments_info', $shipments, $params);

    return array($shipments, $params);
}

/**
 * Verification that at least one product was chosen.
 *
 * @param array $products Array products data
 * @return bool true - if at least one product was chosen, else "false".
 */
function fn_check_shipped_products($products)
{
    $allow = true;
    $total_amount = 0;

    if (!empty($products) && is_array($products)) {
        foreach ($products as $key => $amount) {
            $total_amount += empty($amount) ? 0 : $amount;
        }

        if ($total_amount == 0) {
            $allow = false;
        }

    } else {
        $allow = false;
    }

    return $allow;
}

/**
 * Verification, that all products were delivered by the same shipment.
 *
 * @param array $shipments - shipments data.
 * @return bool true - if all products in the order were delivered by the same shipment
 */
function fn_one_full_shipped(&$shipments)
{
    $full_shipment = true;
    $sort_shipments = array();

    if (!empty($shipments) && is_array($shipments)) {
        foreach ($shipments as $shipment) {
            if (empty($shipment['one_full'])) {
                $full_shipment = false;
                break;
            }
            $sort_shipments[$shipment['group_key']] = $shipment;
        }
        if ($full_shipment) {
            $shipments = $sort_shipments;
        }
    }

    return $full_shipment;
}

/**
 * Create/update shipment
 *
 * @param array $shipment_data Array of shipment data.
 * @param int $shipment_id Shipment identifier
 * @param int $group_key Group number
 * @param bool $all_products
 * @param mixed $force_notification user notification flag (true/false), if not set, will be retrieved from status
 *                                  parameters
 * @return int $shipment_id
 */
function fn_update_shipment($shipment_data, $shipment_id = 0, $group_key = 0, $all_products = false, $force_notification = array())
{
    if (!empty($shipment_id)) {

        $shipment_data = array_intersect_key($shipment_data, array(
            'tracking_number' => 1,
            'carrier' => 1,
            'comments' => 1,
            'timestamp' => 1,
            'status' => 1,
        ));

        $arow = db_query("UPDATE ?:shipments SET ?u WHERE shipment_id = ?i", $shipment_data, $shipment_id);
        if ($arow === false) {
            fn_set_notification('E', __('error'), __('object_not_found', array('[object]' => __('shipment'))),'','404');
            $shipment_id = false;
        }
    } else {

        if (empty($shipment_data['order_id']) || empty($shipment_data['shipping_id'])) {
            return false;
        }

        $order_info = fn_get_order_info($shipment_data['order_id'], false, true, true);

        if (empty($shipment_data['tracking_number']) && empty($shipment_data['carrier'])) {
            return false;
        }

        if ($all_products) {
            foreach ($order_info['product_groups'] as $group) {
                foreach ($group['products'] as $item_key => $product) {

                    if (!empty($product['extra']['group_key'])) {
                        if ($group_key == $product['extra']['group_key']) {
                            $shipment_data['products'][$item_key] = $product['amount'];
                        }
                    } elseif ($group_key == $order_info['shipping'][0]['group_key']) {
                        $shipment_data['products'][$item_key] = $product['amount'];
                    }
                }
            }
        }

        if (!empty($shipment_data['products']) && fn_check_shipped_products($shipment_data['products'])) {

            fn_set_hook('create_shipment', $shipment_data, $order_info, $group_key, $all_products);

            foreach ($shipment_data['products'] as $key => $amount) {
                if (isset($order_info['products'][$key])) {
                    $amount = intval($amount);

                    if ($amount > ($order_info['products'][$key]['amount'] - $order_info['products'][$key]['shipped_amount'])) {
                        $shipment_data['products'][$key] = $order_info['products'][$key]['amount'] - $order_info['products'][$key]['shipped_amount'];
                    }
                }
            }

            if (fn_check_shipped_products($shipment_data['products'])) {
                $shipment_data['timestamp']  = isset($shipment_data['timestamp']) ? fn_parse_date($shipment_data['timestamp']) : TIME;

                $shipment_id = db_query("INSERT INTO ?:shipments ?e", $shipment_data);

                foreach ($shipment_data['products'] as $key => $amount) {

                    if ($amount == 0) {
                        continue;
                    }

                    $_data = array(
                        'item_id' => $key,
                        'shipment_id' => $shipment_id,
                        'order_id' => $shipment_data['order_id'],
                        'product_id' => $order_info['products'][$key]['product_id'],
                        'amount' => $amount,
                    );

                    db_query("INSERT INTO ?:shipment_items ?e", $_data);
                }

                if (fn_check_permissions('orders', 'update_status', 'admin') && !empty($shipment_data['order_status'])) {
                    fn_change_order_status($shipment_data['order_id'], $shipment_data['order_status']);
                }

                $shipment_data['comments'] = isset($shipment_data['comments'])
                    ? $shipment_data['comments']
                    : '';

                /**
                 * Called after new shipment creation.
                 *
                 * @param array $shipment_data Array of shipment data.
                 * @param array $order_info Shipment order info
                 * @param int $group_key Group number
                 * @param bool $all_products
                 * @param int $shipment_id Created shipment identifier
                 */
                fn_set_hook('create_shipment_post', $shipment_data, $order_info, $group_key, $all_products, $shipment_id);

                $shipment = [
                    'shipment_id'     => $shipment_id,
                    'timestamp'       => $shipment_data['timestamp'],
                    'shipping'        => db_get_field('SELECT shipping FROM ?:shipping_descriptions WHERE shipping_id = ?i AND lang_code = ?s', $shipment_data['shipping_id'], $order_info['lang_code']),
                    'tracking_number' => isset($shipment_data['tracking_number']) ? $shipment_data['tracking_number'] : '',
                    'carrier_info'    => Shippings::getCarrierInfo(
                        $shipment_data['carrier'],
                        isset($shipment_data['tracking_number']) ? $shipment_data['tracking_number'] : ''
                    ),
                    'comments'        => $shipment_data['comments'],
                    'products'        => $shipment_data['products'],
                ];

                /**
                 * Executes before sending a notification about the new shipment creation,
                 * allows you to modify the notification shipment data.
                 *
                 * @param array $shipment_data      Shipment data
                 * @param int   $shipment_id        Shipment identifier
                 * @param int   $group_key          Cart products group key
                 * @param bool  $all_products       Whether to use all products to create the new shipment
                 * @param array $force_notification Array with notification rules
                 * @param array $order_info         Shipment order information
                 * @param array $shipment           Notification shipment data
                 */
                fn_set_hook('update_shipment_before_send_notification', $shipment_data, $shipment_id, $group_key, $all_products, $force_notification, $order_info, $shipment);

                /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
                $event_dispatcher = Tygh::$app['event.dispatcher'];

                /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
                $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];
                $notification_rules = $notification_settings_factory->create($force_notification);

                $event_dispatcher->dispatch('order.shipment_updated',
                    ['shipment' => $shipment, 'order_info' => $order_info],
                    $notification_rules
                );

                fn_set_notification('N', __('notice'), __('shipment_has_been_created'));
            }

        } else {
            fn_set_notification('E', __('error'), __('products_for_shipment_not_selected'));
        }

    }

    return $shipment_id;
}

function fn_delete_shipments($shipment_ids)
{
    $result = false;

    /**
     * Called before shipments deletion
     *
     * @param array $shipment_ids Identifiers of deleted shipments
     */
    fn_set_hook('delete_shipments_pre', $shipment_ids);

    if (!empty($shipment_ids)) {
        $result = db_query('DELETE FROM ?:shipments WHERE shipment_id IN (?n)', $shipment_ids);
        db_query('DELETE FROM ?:shipment_items WHERE shipment_id IN (?n)', $shipment_ids);
    }

    /**
     * Called after shipments deletion
     *
     * @param array $shipment_ids Identifiers of deleted shipments
     * @param int   $result       Number of affected by deletion database rows
     */
    fn_set_hook('delete_shipments', $shipment_ids, $result);

    return $result;
}

/**
 * Deletes shipping method by identifier
 *
 * @param int $shipping_id Shipping identifier
 *
 * @return bool Always true
 */
function fn_delete_shipping($shipping_id)
{
    db_query('DELETE FROM ?:shipping_rates WHERE shipping_id = ?i', $shipping_id);
    db_query('DELETE FROM ?:shipping_descriptions WHERE shipping_id = ?i', $shipping_id);
    db_query('DELETE FROM ?:shipping_time_descriptions WHERE shipping_id = ?i', $shipping_id);
    $result = db_query('DELETE FROM ?:shippings WHERE shipping_id = ?i', $shipping_id);

    /** @var \Tygh\Storefront\Repository $repository */
    $repository = \Tygh::$app['storefront.repository'];
    [$storefronts,] = $repository->find(['shipping_ids' => $shipping_id]);
    /** @var \Tygh\Storefront\Storefront $storefront */
    foreach ($storefronts as $storefront) {
        $repository->save($storefront->removeShippingIds($shipping_id));
    }

    fn_set_hook('delete_shipping', $shipping_id, $result);

    return $result;
}

function fn_purge_undeliverable_products(&$cart)
{
    foreach ((array) $cart['products'] as $k => $v) {
        if (isset($v['shipping_failed']) && $v['shipping_failed']) {
            unset($cart['products'][$k]);
        }
    }
}

function fn_apply_stored_shipping_rates(&$cart, $order_id = 0)
{
    if (!empty($cart['stored_shipping'])) {
        $total_cost = 0;
        foreach ($cart['product_groups'] as $group_key => $group) {
            if (isset($group['chosen_shippings'])) {
                foreach ($group['chosen_shippings'] as $shipping_key => $shipping) {
                    if (isset($cart['stored_shipping'][$group_key][$shipping_key])) {
                        if (!empty($cart['free_shipping']) && in_array($shipping['shipping_id'], $cart['free_shipping'])) {
                            if (!empty($cart['stored_shipping'][$group_key][$shipping_key])) {
                                // save original value
                                $cart['original_stored_shipping'][$group_key][$shipping_key] = $cart['stored_shipping'][$group_key][$shipping_key];
                                // apply free shipping
                                $cart['stored_shipping'][$group_key][$shipping_key] = 0;
                            } else {
                                // save calulated rates as orignal: shipping is zero due to free shipping
                                $cart['original_stored_shipping'][$group_key][$shipping_key] = $shipping['rate'];
                            }
                        } elseif (empty($cart['stored_shipping'][$group_key][$shipping_key]) && isset($cart['original_stored_shipping'][$group_key][$shipping_key])) {
                            // free shiping was disabled - restore previous price
                            $cart['stored_shipping'][$group_key][$shipping_key] = !empty($cart['original_stored_shipping'][$group_key][$shipping_key]) ? $cart['original_stored_shipping'][$group_key][$shipping_key] : $shipping['rate'];
                            unset($cart['original_stored_shipping'][$group_key][$shipping_key]);
                        }

                        $piece = fn_format_price($cart['stored_shipping'][$group_key][$shipping_key]);
                        $cart['product_groups'][$group_key]['chosen_shippings'][$shipping_key]['rate'] = $piece;
                        $cart['product_groups'][$group_key]['shippings'][$shipping['shipping_id']]['rate'] = $piece;
                        $cart['product_groups'][$group_key]['chosen_shippings'][$shipping_key]['stored_shipping'] = true;
                        $cart['product_groups'][$group_key]['shippings'][$shipping['shipping_id']]['stored_shipping'] = true;
                        $shipping['rate'] = $piece;
                        $total_cost += $piece;
                    } else {
                        if (!empty($shipping['rate'])) {
                            $total_cost += $shipping['rate'];
                        }
                    }
                }
            }
        }
        if (!empty($order_id)) {
            db_query("UPDATE ?:orders SET shipping_cost = ?d WHERE order_id = ?i", $total_cost, $order_id);
        }
        $cart['shipping_cost'] = $total_cost;
    }
}

function fn_checkout_update_shipping(&$cart, $shipping_ids)
{
    $cart['chosen_shipping'] = $shipping_ids;

    return true;
}

/**
 * Applies surcharge of selected payment to cart total
 *
 * @param array $cart Array of the cart contents and user information necessary for purchase
 * @param array $auth Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @param string $lang_code 2-letter language code (e.g. 'en', 'ru', etc.)
 * @return bool Always true
 */
function fn_update_payment_surcharge(&$cart, $auth, $lang_code = CART_LANGUAGE)
{
    $cart['payment_surcharge'] = 0;

    // Calculate cart payment surcharge based on cart total
    if (!empty($cart['payment_id'])) {
        $surcharges = db_get_row(
            "SELECT a_surcharge AS `absolute`, p_surcharge AS `percentage` FROM ?:payments WHERE payment_id = ?i",
            $cart['payment_id']
        );

        if (!empty($surcharges)) {
            if (floatval($surcharges['absolute'])) {
                $cart['payment_surcharge'] += $surcharges['absolute'];
            }
            if (floatval($surcharges['percentage'])) {
                $cart['payment_surcharge'] += fn_format_price($cart['total'] * $surcharges['percentage'] / 100);
            }
        }
    }

    if (!empty($cart['payment_surcharge'])) {
        // Apply surcharge title
        $cart['payment_surcharge_title'] = db_get_field(
            "SELECT surcharge_title FROM ?:payment_descriptions WHERE payment_id = ?i AND lang_code = ?s",
            $cart['payment_id'],
            $lang_code
        );

        // Apply tax
        fn_calculate_payment_taxes($cart, $auth);
    }

    return true;
}

/**
 * Gets product image pairs (icon, detailed)
 *
 * @param int   $product_id     Product identifier
 * @param array $product_data   Product data
 *
 * @return array
 */
function fn_get_cart_product_icon($product_id, $product_data = array())
{
    $image = null;
    $selected_options = array();

    if (!empty($product_data['product_options'])) {
        foreach ($product_data['product_options'] as $key => $item) {
            if (is_array($item) && isset($item['option_id'], $item['value'])) {
                $selected_options[$item['option_id']] = $item['value'];
            } else {
                $selected_options[$key] = $item;
            }
        }
    }

    /**
     * Executed before gets product image pairs.
     * Allows to substitute the detailed image.
     *
     * @param int   $product_id         Product identifier
     * @param array $product_data       Product data
     * @param array $selected_options   List of selected product options
     * @param null  $image              Product image pairs (icon, detailed)
     */
    fn_set_hook('get_cart_product_icon', $product_id, $product_data, $selected_options, $image);

    if ($image === null) {
        if (!empty($selected_options)) {
            $combination_hash = fn_generate_cart_id($product_id, array('product_options' => $selected_options), true);
            $image = fn_get_image_pairs($combination_hash, 'product_option', 'M', true, true);

            if (!empty($image)) {
                return $image;
            }
        }

        $image = fn_get_image_pairs($product_id, 'product', 'M', true, true);
    }


    return $image;
}

/**
 * Gets payment methods optionally grouped by category.
 *
 * @param array  $cart                Array of the cart contents and user information necessary for purchase
 * @param array  $auth                Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @param string $lang_code           2-letter language code (e.g. 'en', 'ru', etc.)
 * @param bool   $get_payment_groups  If set to true, payment methods groupped by category will be returned.
 *                                    Otherwise, payment methods will be returned ungroupped
 *
 * @return array
 */
function fn_prepare_checkout_payment_methods(&$cart, &$auth, $lang_code = CART_LANGUAGE, $get_payment_groups = true)
{
    static $payment_methods = [];
    $payment_groups = [];

    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];

    $get_payments_params = [
        'usergroup_ids' => $auth['usergroup_ids'],
        'extend'        => ['images'],
        'lang_code'     => $lang_code,
        'storefront_id' => $storefront->storefront_id,
    ];

    /**
     * Executes before getting payment methods on checkout,
     * allows you to modify the parameters passed to the function that obtains payments.
     *
     * @param array  $cart                Array of the cart contents and user information necessary for purchase
     * @param array  $auth                Array of user authentication data (e.g. uid, usergroup_ids, etc.)
     * @param string $lang_code           2-letter language code (e.g. 'en', 'ru', etc.)
     * @param bool   $get_payment_groups  If set to true, payment methods groupped by category will be returned.
     *                                    Otherwise, payment methods will be returned ungroupped
     * @param array  $get_payments_params Parameters that are used to fetch payments
     */
    fn_set_hook(
        'prepare_checkout_payment_methods_before_get_payments',
        $cart,
        $auth,
        $lang_code,
        $get_payment_groups,
        $payment_methods,
        $get_payments_params
    );

    $cache_key = md5(json_encode($get_payments_params));

    // Get payment methods
    if (empty($payment_methods[$cache_key])) {
        $payment_methods[$cache_key] = fn_get_payments($get_payments_params);
    }

    /**
     * Executes after payment methods for checkout are obtained, allows you to modify the fetched payment methods.
     *
     * @param array  $cart                Array of the cart contents and user information necessary for purchase
     * @param array  $auth                Array of user authentication data (e.g. uid, usergroup_ids, etc.)
     * @param string $lang_code           2-letter language code (e.g. 'en', 'ru', etc.)
     * @param bool   $get_payment_groups  If set to true, payment methods groupped by category will be returned.
     *                                    Otherwise, payment methods will be returned ungroupped
     * @param array  $get_payments_params Parameters that are used to fetch payments
     * @param string $cache_key           The unique key that is used to cache fetched payments
     */
    fn_set_hook(
        'prepare_checkout_payment_methods_after_get_payments',
        $cart,
        $auth,
        $lang_code,
        $get_payment_groups,
        $payment_methods,
        $get_payments_params,
        $cache_key
    );

    // Check if payment method has surcharge rates
    foreach ($payment_methods[$cache_key] as $payment_id => &$payment) {

        if ($payment['processor_type'] == 'C') {
            continue;
        }

        $payment['surcharge_value'] = 0;
        if ((float) $payment['a_surcharge']) {
            $payment['surcharge_value'] += $payment['a_surcharge'];
        }
        if ((float) $payment['p_surcharge'] && !empty($cart['total'])) {
            $payment['surcharge_value'] += fn_format_price($cart['total'] * $payment['p_surcharge'] / 100);
        }

        $payment_groups[$payment['payment_category']][$payment_id] = $payment;
    }
    unset($payment);

    if (!empty($payment_groups)) {
        ksort($payment_groups);
    }

    /**
     * Allows to modify payment methods grouped by category used for the checkout page.
     *
     * @param array $cart                     Array of the cart contents and user information necessary for purchase
     * @param array $auth                     Array of user authentication data (e.g. uid, usergroup_ids, etc.)
     * @param array $payment_groups List of payment methods grouped by category
     */
    fn_set_hook('prepare_checkout_payment_methods', $cart, $auth, $payment_groups);

    return $get_payment_groups
        ? $payment_groups
        : $payment_methods[$cache_key];
}

function fn_update_order_customer_info($data, $order_id)
{
    $order_info = fn_get_order_info($order_id);
    $new_order_info = array();
    $need_update = false;

    if (empty($order_info)) {
        return false;
    }

    foreach ($data as $k => $v) {
        if ($data[$k] != $order_info[$k]) {
            $need_update = true;
            $new_order_info[$k] = $v;
        }
    }

    if ($need_update) {
        db_query("UPDATE ?:orders SET ?u WHERE order_id = ?i", $new_order_info, $order_id);
    }

    return true;
}

/**
 * Returns all available shippings for root/vendor company.
 *
 * @param int  $company_id         Company identifier
 * @param bool $get_service_params Whether to get shipping methods configuration
 * @param int  $storefront_id      Storefront identifier
 *
 * @return array List of shippings
 */
function fn_get_available_shippings($company_id = null, $get_service_params = false, $storefront_id = null)
{
    $condition = '1=1';
    if ($company_id !== null && !fn_allowed_for('ULTIMATE')) {
        /** @var string $company_shippings */
        $company_shippings = db_get_field('SELECT shippings FROM ?:companies WHERE company_id = ?i', $company_id);
        $condition .= db_quote(' AND (a.company_id = ?i ', $company_id);

        if (!empty($company_shippings)) {
            $condition .= db_quote(' OR a.shipping_id IN (?n)', explode(',', $company_shippings));
        }

        $condition .= ')';
    }

    $fields = [
        'a.shipping_id',
        'a.company_id',
        'a.min_weight',
        'a.max_weight',
        'a.position',
        'a.status',
        'a.tax_ids',
        'a.usergroup_ids',
        'b.shipping',
        'b.delivery_time',
        'c.company AS company_name',
    ];

    $join = [
        db_quote('LEFT JOIN ?:shipping_descriptions AS b ON a.shipping_id = b.shipping_id AND b.lang_code = ?s', DESCR_SL),
        db_quote('LEFT JOIN ?:companies AS c ON c.company_id = a.company_id'),
    ];

    $repository = StorefrontProvider::getRepository();
    $storefront_ids = empty($storefront_id)
        ? []
        : (array) $storefront_id;
    if (!$storefront_ids && $company_id) {
        $storefronts = $repository->findAvailableForCompanyId($company_id, false);
        if ($storefronts) {
            $storefront_ids = [];
            foreach ($storefronts as $storefront) {
                $storefront_ids[] = $storefront->storefront_id;
            }
        }
    }

    $fields[] = 'storefront_id';
    $join[] = db_quote(
        ' LEFT JOIN ?:storefronts_shippings AS storefronts_shippings'
        . ' ON storefronts_shippings.shipping_id = a.shipping_id'
    );
    if (!empty($storefront_ids)) {
        $condition .= db_quote(
            ' AND (storefronts_shippings.storefront_id IN (?n) OR storefronts_shippings.storefront_id IS NULL)',
            $storefront_ids
        );
    }

    if ($get_service_params) {
        $fields[] = 'a.service_params';
        $fields[] = 's.module';
        $join[] = db_quote('LEFT JOIN ?:shipping_services AS s ON s.service_id = a.service_id');
    }

    /**
     * Executes before obtaining the list of shipping methods in the administration panel. Allows you to modify SQL query parameters
     *
     * @param int|null $company_id         ID of the admin company
     * @param string[] $fields             Fields to fetch from the database
     * @param string[] $join               SQL query JOINs
     * @param string   $condition          SQL query condition string
     * @param bool     $get_service_params Whether to get shipping methods configuration
     */
    fn_set_hook('get_available_shippings', $company_id, $fields, $join, $condition, $get_service_params);

    $shippings_list = db_get_hash_array(
        'SELECT ?p'
        . ' FROM ?:shippings AS a'
        . ' ?p'
        . ' WHERE ?p'
        . ' ORDER BY a.position',
        'shipping_id',
        implode(', ', $fields),
        implode(' ', $join),
        $condition
    );

    if ($get_service_params) {
        array_walk($shippings_list, function(&$shipping) {
            if ($shipping['service_params']) {
                $shipping['service_params'] = unserialize($shipping['service_params']);
            } else {
                $shipping['service_params'] = [];
            }
        });
    }

    return $shippings_list;
}

/**
 * Provides a direct link to the payment script.
 *
 * @param string $protocol         HTTP protocol: 'current', 'http' or 'https'
 * @param string $processor_script Processor script basename
 *
 * @return string
 * @deprecated since 4.10.4. Use the payment_notification controller in your payments.
 *
 */
function fn_payment_url($protocol, $processor_script)
{
    if (empty($protocol)) {
        return '';
    }
    if ($protocol === 'current') {
        $protocol = defined('HTTPS')? 'https' : 'http';
    }

    $payment_dir = '/app/payments/';

    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];
    $url = "{$protocol}://{$storefront->url}";

    /**
     * Executes after the direct link to the payment script is generated, allows you to modify URL parts.
     *
     * @param string $protocol         HTTP protocol: 'current', 'http' or 'https'
     * @param string $processor_script Processor script basename
     * @param string $url              Store URL
     * @param string $payment_dir      Payment methods directory
     *
     * @deprecated since 4.10.4. Use the payment_notification controller in your payments.
     */
    fn_set_hook('payment_url', $protocol, $processor_script, $url, $payment_dir);

    return rtrim($url, '/') . $payment_dir . $processor_script;
}

/**
 * Gets URI for checkout
 *
 * @param string $url URN (Uniform Resource Name or Query String)
 * @param string $area Area
 * @return string URI
 */
function fn_checkout_url($url = '', $area = AREA)
{
    $protocol = Registry::get('settings.Security.secure_storefront') === YesNo::YES ? 'https' : 'http';

    return fn_url($url, $area, $protocol);
}

/**
 * Update cart products from passed products data
 *
 * @param array $cart Array of cart content and user information necessary for purchase
 * @param array $product_data Array of new products data
 * @param array $auth Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @return boolean Always true
 */
function fn_update_cart_products(&$cart, $product_data, $auth)
{
    /**
     * Actions before updating cart products
     *
     * @param array $cart         Array of cart content and user information necessary for purchase
     * @param array $product_data Array of new products data
     * @param array $auth         Array of user authentication data (e.g. uid, usergroup_ids, etc.)
     */
    fn_set_hook('update_cart_products_pre', $cart, $product_data, $auth);

    if (is_array($cart['products']) && !empty($product_data)) {

        [$product_data, $cart] = fn_add_product_options_files($product_data, $cart, $auth, true);
        unset($product_data['custom_files']);

        foreach ($product_data as $k => $v) {
            if (empty($v['extra'])) {
                $v['extra'] = array();
            }

            if (empty($v['price']) || $v['price'] < 0) {
                $v['price'] = 0;
            }

            unset($v['object_id']);

            $amount = fn_normalize_amount($v['amount']);

            $v['extra'] = empty($cart['products'][$k]['extra']) ? array() : $cart['products'][$k]['extra'];
            $v['extra']['product_options'] = empty($v['product_options']) ? array() : $v['product_options'];
            $_id = fn_generate_cart_id($v['product_id'], $v['extra']);

            if (!isset($cart['products'][$_id])) { //if combination doesn't exist in the cart
                $cart['products'][$_id] = $v;
                $cart['products'][$_id]['company_id'] = !empty($cart['products'][$k]['company_id']) ? $cart['products'][$k]['company_id'] : 0;
                $_product = $cart['products'][$k];
                unset($cart['products'][$k]['extra']['custom_files']);

                fn_define_original_amount($v['product_id'], $_id, $cart['products'][$_id], $_product);
                fn_delete_cart_product($cart, $k);
            } elseif ($k != $_id) { // if the combination is exist but differs from the current
                $amount += $cart['products'][$_id]['amount'];
                unset($cart['products'][$k]['extra']['custom_files']);

                fn_delete_cart_product($cart, $k);
            }

            if (empty($amount)) {
                fn_delete_cart_product($cart, $_id);
                continue;
            } else {
                $_product_options = !empty($v['product_options']) ? $v['product_options'] : array();
                $cart['products'][$_id]['amount'] = fn_check_amount_in_stock($v['product_id'], $amount, $_product_options, $_id, (!empty($cart['products'][$_id]['is_edp']) && $cart['products'][$_id]['is_edp'] == 'Y' ? 'Y' : 'N'), !empty($cart['products'][$_id]['original_amount']) ? $cart['products'][$_id]['original_amount'] : 0, $cart);

                if ($cart['products'][$_id]['amount'] == false && !empty($_product)) {
                    $cart['products'][$_id] = $_product;
                    unset($_product);
                }
            }

            if ($k != $_id) {
                $cart['products'][$_id]['prev_cart_id'] = $k;

                // save stored taxes for products
                fn_update_stored_cart_taxes($cart, $k, $_id, true);

            } elseif (isset($cart['products'][$_id]['prev_cart_id'])) {
                unset($cart['products'][$_id]['prev_cart_id']);
            }

            $cart['products'][$_id]['stored_price'] = !empty($v['stored_price']) ? $v['stored_price'] : 'N';
            if ($cart['products'][$_id]['stored_price'] == 'Y') {
                $cart['products'][$_id]['price'] = $v['price'];
            }

            $cart['products'][$_id]['stored_discount'] = !empty($v['stored_discount']) ? $v['stored_discount'] : 'N';
            if ($cart['products'][$_id]['stored_discount'] == 'Y') {
                $cart['products'][$_id]['discount'] = $v['discount'];
            }

            $product = $cart['products'][$_id];

            if (!empty($product['extra']['saved_options_key']) && !empty($product['extra']['aoc'])) {
                $cart['saved_product_options'][$product['extra']['saved_options_key']] = $product['product_options'];
            }
        }
    }

    /**
     * Additional cart products updates from passed params
     *
     * @param array $cart         Array of cart content and user information necessary for purchase
     * @param array $product_data Array of new products data
     * @param array $auth         Array of user authentication data (e.g. uid, usergroup_ids, etc.)
     */
    fn_set_hook('update_cart_products_post', $cart, $product_data, $auth);

    return true;
}

/**
 * Update cart products and etc. from passed params
 *
 * @param array $cart          Array of cart content and user information necessary for purchase
 * @param array $new_cart_data Array of new data for products, totals, discounts and etc. update
 * @param array $auth          Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 *
 * @return boolean Always true
 */
function fn_update_cart_by_data(&$cart, $new_cart_data, $auth)
{
    // Clean up saved shipping rates
    unset(Tygh::$app['session']['shipping_rates']);

    // update products
    $product_data = !empty($new_cart_data['cart_products']) ? $new_cart_data['cart_products'] : array();
    fn_update_cart_products($cart, $product_data, $auth);

    // Update shipping cost
    $cart['stored_shipping'] = array();
    if (!empty($cart['product_groups'])) {
        foreach ($cart['product_groups'] as $group_key => $group) {
            if (!empty($group['chosen_shippings'])) {
                foreach ($group['chosen_shippings'] as $shipping_key => $shipping) {
                    if (!empty($new_cart_data['stored_shipping'][$group_key][$shipping_key]) && $new_cart_data['stored_shipping'][$group_key][$shipping_key] != 'N') {
                        $cart['stored_shipping'][$group_key][$shipping_key] = (float) $new_cart_data['stored_shipping_cost'][$group_key][$shipping_key];
                        $cart['product_groups'][$group_key]['chosen_shippings'][$shipping_key]['rate'] = $cart['stored_shipping'][$group_key][$shipping_key];
                    } else {
                        unset($cart['product_groups'][$group_key]['chosen_shippings'][$shipping_key]['stored_shippings']);
                        unset($cart['product_groups'][$group_key]['shippings'][$shipping['shipping_id']]['stored_shippings']);
                    }
                }
            }
        }
    }

    // Update taxes
    if (!empty($new_cart_data['taxes']) && @$new_cart_data['stored_taxes'] == 'Y') {
        foreach ($new_cart_data['taxes'] as $id => $rate) {
            $cart['taxes'][$id]['rate_value'] = $rate;
        }
    }

    $cart['stored_taxes'] = !empty($new_cart_data['stored_taxes']) ? $new_cart_data['stored_taxes'] : array();

    if (!empty($new_cart_data['stored_subtotal_discount']) && $new_cart_data['stored_subtotal_discount'] == 'Y') {
        $cart['stored_subtotal_discount'] = 'Y';
        $cart['subtotal_discount'] = $new_cart_data['subtotal_discount'];
    } else {
        unset($cart['stored_subtotal_discount']);
        $cart['subtotal_discount'] = !empty($cart['original_subtotal_discount']) ? $cart['original_subtotal_discount'] : 0;
    }

    // Apply coupon
    if (!empty($new_cart_data['coupon_code'])) {
        fn_trusted_vars('coupon_code');
        // That's why $cart->setPendingCoupon() is better
        $cart['pending_coupon'] = fn_strtolower($new_cart_data['coupon_code']);
    }

    /**
     * Additional cart updates from passed params
     *
     * @param array $cart          Array of cart content and user information necessary for purchase
     * @param array $new_cart_data Array of new data for products, totals, discounts and etc. update
     * @param array $auth          Array of user authentication data (e.g. uid, usergroup_ids, etc.)
     */
    fn_set_hook('update_cart_by_data_post', $cart, $new_cart_data, $auth);

    return true;
}

/**
 * Enables checkout mode
 */
function fn_enable_checkout_mode()
{
    Registry::set('runtime.checkout', true);
}

/**
 * Gets credit card type by its number
 *
 * @param integer $number credir card number
 *
 * @return mixed card type or false on failure
 */
function fn_get_credit_card_type($number)
{
    $card_types = array(
        'amex' => array(
            'pattern' => "/^3[47]/",
            'valid_length' => array(15),
        ),
        'diners_club_carte_blanche' => array(
            'pattern' => "/^30[0-5]/",
            'valid_length' => array(14),
        ),
        'diners_club_international' => array(
            'pattern' => "/^36/",
            'valid_length' => array(14),
        ),
        'jcb' => array(
            'pattern' => "/^35(2[89]|[3-8][0-9])/",
            'valid_length' => array(16),
        ),
        'laser' => array(
            'pattern' => "/^(6304|670[69]|6771)/",
            'valid_length' => array(16, 17, 18, 19),
        ),
        'visa_debit' => array(
            'pattern' => "/^(456735|400626|40854749|40940002|41228586|41373337|41378788|418760|41917679|419772|420672|42159294|422793|423769|431072|444001|44400508|44620011|44621354|44625772|44627483|446286|446294|446200|450875|45397879|454313|45443235|454742|45672545|46583079|46590150|47511059|47571059|47622069|47634089|48440910|484427|49096079|49218182|400115|40083739|41292123|417935|419740|419741|41977376|424519|4249623|444000|48440608|48441126|48442855|491880)/",
            'valid_length' => array(13, 16),
        ),
        'visa_electron' => array(
            'pattern' => "/^(4026|417500|4508|4844|491(3|7))/",
            'valid_length' => array(16),
        ),
        'visa' => array(
            'pattern' => "/^4/",
            'valid_length' => array(13, 16),
        ),
        'mastercard_debit' => array(
            'pattern' => "/^(516730|516979|517000|517049|535110|535309|535420|535819|537210|537609|557347|557496|557498|557547)/",
            'valid_length' => array(16),
        ),
        'mastercard' => array(
            'pattern' => "/^(?:5[1-5][0-9]{2}|222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720)[0-9]{12}$/",
            'valid_length' => array(16),
        ),
        'discover' => array(
            'pattern' => "/^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]|64[4-9])|65)/",
            'valid_length' => array(16, 19),
        ),
        'maestro' => array(
            'pattern' => "/^(50|5[6-9]|6[0-9])/",
            'valid_length' => array(12, 13, 14, 15, 16, 17, 18, 19),
        ),
    );

    $number_length = strlen($number);
    foreach ($card_types as $card => $tests) {
        if (preg_match($tests['pattern'], $number, $m) && in_array($number_length, $tests['valid_length'])) {
            return $card;
        }
    }

    return false;
}

/**
 * Checks whether given month number is 1 >= and <= 12
 *
 * @param string|integer $month
 *
 * @return bool
 */
function fn_validate_cc_expiry_month($month)
{
    return $month >= 1 && $month <= 12;
}

/**
 * Gets credit card type by its number and maps it to payment-specific name
 *
 * @param string $card_number card number
 * @param array  $cards_map   key->value array of card types map
 *
 * @return string payment-specific card type or empty string for unknown card
 */
function fn_get_payment_card($card_number, $cards_map)
{
    $card_type = fn_get_credit_card_type($card_number);

    return isset($cards_map[$card_type]) ? $cards_map[$card_type] : '';
}

/**
 * Filters credit card data, removes cleaned up fields
 *
 * @param array  $payment_info Credit card data
 * @param string $area         Current working area
 *
 * @return array Filtered card data
 */
function fn_filter_card_data($payment_info, $area = AREA)
{
    $checked = array();
    if ($area == 'A' && !empty($payment_info)) {
        if (!empty($payment_info['card_number']) && strpos($payment_info['card_number'], 'X') === false) {
            $checked = $payment_info;
        } else {
            $checked = array (
                'cardholder_name' => !empty($payment_info['cardholder_name']) ? $payment_info['cardholder_name'] : '',
            );
        }
    }

    return $checked;
}

/**
 * Create HTML form with payment data and submit it.
 *
 * @param string  $submit_url           URL to send payment data
 * @param array   $data                 Payment data to be submitted
 * @param string  $payment_name         Payment name to be displayed duron form submitting
 * @param boolean $exclude_empty_values Define that payment data elements with empty values should be excluded from
 *                                      payment form
 * @param string  $method               form submit method (get/post)
 * @param bool    $parse_url            Populate form parameters from request parametes passed in $submit_url
 * @param string  $target               Form target: 'form' creates payment form and submits it, 'parent' replaces
 *                                      window location
 * @param string  $connection_message   Custom message to display instead of 'Connecting to $payment_name'
 */
function fn_create_payment_form($submit_url, $data, $payment_name = '', $exclude_empty_values = true, $method = 'post', $parse_url = true, $target = 'form', $connection_message = '')
{
    /**
     * Executes before create payment form; allows modifying form data.
     *
     * @param string  $submit_url           URL to send payment data
     * @param array   $data                 Payment data to be submitted
     * @param string  $payment_name         Payment name to be displayed duron form submitting
     * @param bool    $exclude_empty_values Define that payment data elements with empty values should be excluded from payment form
     * @param string  $method               Form submit method (get/post)
     * @param bool    $parse_url            Populate form parameters from request parametes passed in $submit_url
     * @param string  $target               Form target: 'form' creates payment form and submits it, 'parent' replaces window location
     * @param string  $connection_message   Custom message to display instead of 'Connecting to $payment_name'
     */
    fn_set_hook('create_payment_form_pre', $submit_url, $data, $payment_name, $exclude_empty_values, $method, $parse_url, $target, $connection_message);

    Embedded::leave();

    if (Embedded::isEnabled()) {
        [$submit_url, $data, $method, $payment_name] = Embedded::processPaymentForm($submit_url, $data, $payment_name, $exclude_empty_values, $method);
    }

    if ($parse_url) {
        $parsed_url = parse_url($submit_url);
        if (!empty($parsed_url['query'])) {
            $_data = array();
            parse_str($parsed_url['query'], $_data);
            $data = fn_array_merge($data, $_data);
            $submit_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
        }
    }

    echo <<<EOT
        <form method="$method" action="$submit_url" name="process">
EOT;

    foreach ($data as $name => $value) {
        if (
            is_string($value)
            && fn_string_not_empty($value)
            || $exclude_empty_values === false
        ) {
            echo('<input type="hidden" name="' . htmlentities($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlentities($value, ENT_QUOTES, 'UTF-8') . '" />' . "\n");
        }
    }

    if (!empty($connection_message)) {
        echo $connection_message;
    } elseif (!empty($payment_name)) {
        echo(__('text_cc_processor_connection', array(
            '[processor]' => $payment_name,
        )));
    }

    echo <<<EOT
        <noscript><p>
EOT;
    echo(__('text_cc_javascript_disabled'));

    echo <<<EOT
        </p><p><input type="submit" name="btn" value="
EOT;
    echo(__('cc_button_submit'));
    echo <<<EOT
"></p>
        </noscript>
        </form>
        <script>
            window.onload = function(){
EOT;
    if ($target == 'parent') {
echo <<<EOT
                window.parent.location='{$submit_url}';
EOT;
    } elseif ($target == 'form') {
echo <<<EOT
                document.process.submit();
EOT;
    }
echo <<<EOT
            };
        </script>
        </body>
    </html>
EOT;
    exit;
}

function fn_checkout_summary(&$cart)
{
    if (fn_cart_is_empty($cart) == true) {
        return;
    }

    fn_set_hook('checkout_summary', $cart);

    //Get payment methods
    $payment_data = isset($cart['payment_id']) ? fn_get_payment_method_data($cart['payment_id']) : null;

    Tygh::$app['view']->assign('payment_method', $payment_data);

    // Downlodable files agreements
    $agreements = array();
    if (!empty($cart['products'])) {
        foreach ($cart['products'] as $item) {
            if ($item['is_edp'] == 'Y') {
                if ($_agreement = fn_get_edp_agreements($item['product_id'], true)) {
                    $agreements[$item['product_id']] = $_agreement;
                }
            }
        }
    }

    /**
     * Executed after the license agreements of the files of downloadable products are retrieved.
     * The hook allows to modify the list of license agreements.
     *
     * @param array $cart       Array of cart content and user information necessary for purchase
     * @param array $agreements License agreements list of the files of downloadable products
     */
    fn_set_hook('cart_agreements', $cart, $agreements);

    if (!empty($agreements)) {
        Tygh::$app['view']->assign('cart_agreements', $agreements);
    }
}

/**
 * @deprecated since 4.10.1
 *
 * @param array $cart
 *
 * @return bool
 */
function fn_need_shipping_recalculation(&$cart)
{
    if ($cart['recalculate'] == true) {
        return true;
    }

    $recalculate_shipping = false;
    if (!empty(Tygh::$app['session']['customer_loc'])) {
        foreach (Tygh::$app['session']['customer_loc'] as $k => $v) {
            if (!empty($v) && empty($cart['user_data'][$k])) {
                $recalculate_shipping = true;
                break;
            }
        }
    }

    if ($recalculate_shipping == false && !empty(Tygh::$app['session']['checkout_mode']) && (Tygh::$app['session']['checkout_mode'] == 'cart' && Registry::get('runtime.mode') == 'checkout')) {
        $recalculate_shipping = true;
    }

    unset(Tygh::$app['session']['customer_loc']);

    return $recalculate_shipping;

}

/**
 * Gets payment buttons to display on the cart page.
 *
 * @param array      $cart              Cart contents
 * @param array      $cart_products     Cart products
 * @param array      $auth              Current user's authentication data
 * @param array|null $checkout_payments All available payment methods data
 *
 * @return string[]
 */
function fn_get_checkout_payment_buttons(&$cart, &$cart_products, &$auth, array $checkout_payments = null)
{
    $checkout_buttons = [];

    fn_set_hook('get_checkout_payment_buttons_pre', $cart, $cart_products, $auth, $checkout_buttons);

    if ($checkout_payments === null) {
        $checkout_payments = fn_prepare_checkout_payment_methods($cart, $auth, CART_LANGUAGE, false);
    }
    if ($checkout_payments && !is_numeric(key($checkout_payments))) {
        $checkout_payments = fn_checkout_flatten_payments_list($checkout_payments);
    }

    array_walk($checkout_payments, function(&$processor_data) {
        if (!empty($processor_data['processor_params']) && is_string($processor_data['processor_params'])) {
            $processor_data['processor_params'] = unserialize($processor_data['processor_params']);
        }
    });

    $checkout_payment_ids = (new Collection($checkout_payments))
        ->filter(
            function ($payment) {
                return $payment['processor_type'] !== 'P';
            }
        )
        ->pluck('payment_id')
        ->all();

    foreach ($checkout_payment_ids as $payment_id) {
        $payment = $checkout_payments[$payment_id];

        /**
         * Executes when getting payment method buttons when processing a single payment method,
         * allows you to add your own payment button or modify the existing ones.
         *
         * @param array    $cart                 Cart contents
         * @param array    $cart_products        Cart products
         * @param array    $auth                 Current user's authentication data
         * @param string[] $checkout_buttons     Payment method buttons
         * @param int[]    $checkout_payment_ids IDs of all available payment methods
         * @param int      $payment_id           ID of the currently processed payment method
         * @param array    $payment              Currently processed payment method data
         * @param array    $checkout_payments    All available payment methods data
         */
        fn_set_hook('get_checkout_payment_buttons', $cart, $cart_products, $auth, $checkout_buttons, $checkout_payment_ids, $payment_id, $payment, $checkout_payments);

        if (!empty($payment['processor_script'])) {
            $script_path = fn_get_processor_script_path($payment['processor_script']);
            if ($script_path) {
                include($script_path);
            }
        }
    }

    fn_set_hook('get_checkout_payment_buttons_post', $cart, $cart_products, $auth, $checkout_buttons);

    if (Registry::get('settings.Checkout.disable_anonymous_checkout') == 'Y'
        && empty($auth['user_id'])
        && !empty($checkout_buttons)
    ) {
        $url = fn_url('auth.login_form?return_url=' . urlencode(fn_url('checkout.cart')));

        return array(__('payments.signin_to_checkout', array('[url]' => $url)));
    }

    return $checkout_buttons;
}

/**
 * @deprecated since 4.10.1
 *             Nobody stores clients' credit cards data in their stores.
 * @param $cart
 * @param $user_data
 */
function fn_get_default_credit_card(&$cart, $user_data)
{
    if (!empty($user_data['credit_cards'])) {
        $cards = unserialize(fn_decrypt_text($user_data['credit_cards']));
        foreach ((array) $cards as $cc) {
            if ($cc['default']) {
                $cart['payment_info'] = $cc;
                break;
            }
        }
    } elseif (isset($cart['payment_info'])) {
        unset($cart['payment_info']);
    }
}

function fn_get_shipping_hash($product_groups)
{
    // If shipping methods changed and shipping step is completed, display notification
    $shipping_hash = '';

    if (!empty($product_groups)) {
        $rates = array();
        foreach ($product_groups as $key_group => $group) {
            $rates[$key_group] = array();
            foreach ($group['shippings'] as $key_shipping => $shipping) {
                $rates[$key_group][$key_shipping] = $shipping['rate'];
            }
            ksort($rates[$key_group]);
        }
        ksort($rates);
        $shipping_hash = md5(fn_recursive_makehash($rates));
    }

    return $shipping_hash;
}

/**
 * Update steps data handler
 *
 * @param  array $cart   Cart
 * @param  array $auth   Auth
 * @param  array $params Params
 * @return array array(status, redirect_params)
 */
function fn_checkout_update_steps(&$cart, &$auth, $params)
{
    $params = array_merge([
        'user_data'       => [],
        'shipping_ids'    => [],
        'customer_notes'  => '',
        'guest_checkout'  => false,
        'ship_to_another' => true,
        'payment_id'      => null,
        'payment_info'    => [],
    ], $params);

    $redirect_params = [];

    /**
     * Executes before updating checkout steps, allows you to modify the function parameters.
     *
     * @param array $cart            Cart content
     * @param array $auth            Customer's authentication data
     * @param array $params          Step update parameters
     * @param array $redirect_params Redirection parameters
     */
    fn_set_hook('checkout_update_steps_pre', $cart, $auth, $params, $redirect_params);

    $fn_checkout_prepare_user_data = function ($user_data) {
        array_walk($user_data, 'fn_trim_helper');
        unset($user_data['user_type']);

        return $user_data;
    };

    $user_data = $fn_checkout_prepare_user_data($params['user_data'] ?: []);

    $is_anonymous_checkout_allowed = Registry::get('settings.Checkout.disable_anonymous_checkout') !== YesNo::YES;

    $errors = false;

    $user_id = empty($auth['user_id'])
        ? 0
        : $auth['user_id'];

    $profile_id = null;

    // Update contact information
    if (!empty($user_data['email']) && $found_user_id = fn_is_user_exists($user_id, $user_data)) {
        fn_set_notification('E', __('error'), __('error_user_exists'), 'K', 'error_checkout_user_exists');
        fn_save_post_data('user_data');

        /**
         * Executes when updating checkout step and a user with the same email as the one supplied by a customer is found,
         * allows you to modify the redirection parameters.
         *
         * @param array $cart            Cart content
         * @param array $auth            Customer's authentication data
         * @param array $params          Step update parameters
         * @param array $redirect_params Redirection parameters
         * @param int   $found_user_id   Found user ID
         */
        fn_set_hook('checkout_update_steps_user_exists', $cart, $auth, $params, $redirect_params, $found_user_id);

        $cart['user_data']['user_exists'] = true;
        $cart['user_data']['found_user_id'] = $found_user_id;
        $cart['user_data']['found_user_email'] = $user_data['email'];
        [$cart, $auth] = fn_checkout_update_user_data(
            $cart,
            $auth,
            $user_data,
            $params['ship_to_another'],
            $found_user_id
        );

        return array(false, $redirect_params);
    } else {
        unset($cart['user_data']['user_exists'], $cart['user_data']['found_user_id'], $cart['user_data']['found_user_email']);
    }

    $cart['user_data'] = empty($cart['user_data'])
        ? []
        : $cart['user_data'];

    $location_hash = isset($cart['location_hash']) ? $cart['location_hash'] : fn_checkout_get_location_hash($cart['user_data']);

    /**
     * Executes before updating checkout user data, allows you to modify the user data.
     *
     * @param array $cart            Cart content
     * @param array $auth            Customer's authentication data
     * @param array $params          Step update parameters
     * @param array $user_id         User ID
     * @param array $user_data       User data
     */
    fn_set_hook('checkout_update_steps_before_update_user_data', $cart, $auth, $params, $user_id, $user_data);

    if ($user_id) {
        $user_data['user_id'] = $user_id;

        if (isset($user_data['profile_id'])) {
            if (empty($user_data['profile_id'])) {
                $user_data['profile_type'] = 'S';
            }
            $profile_id = $user_data['profile_id'];
        } elseif (!empty($cart['user_data']['profile_id'])) {
            $profile_id = $cart['user_data']['profile_id'];
        } elseif (!empty($cart['profile_id'])) {
            $profile_id = $cart['profile_id'];
        }

        $current_user_data = fn_get_user_info($user_id, true, $profile_id);

        $update_user_data = empty($cart['user_data']['profile_update_timestamp'])
            || $cart['user_data']['profile_update_timestamp'] < $current_user_data['profile_update_timestamp']
            || !empty($cart['profile_changed']);

        if ($update_user_data) {
            $cart['user_data'] = fn_array_merge(
                $cart['user_data'],
                $current_user_data
            );

            unset($cart['profile_changed']);
        }

        if ($profile_id) {
            $cart['profile_id'] = $profile_id;
        }
    }

    // Update billing/shipping information
    if (!empty($params['user_data'])) {
        [$cart, $auth] = fn_checkout_update_user_data(
            $cart,
            $auth,
            $user_data,
            $params['ship_to_another'],
            $user_id
        );
    }

    $is_location_changed = fn_checkout_get_location_hash($cart['user_data']) !== $location_hash;

    if ($params['shipping_ids']) {
        fn_checkout_update_shipping($cart, $params['shipping_ids']);
    }

    if ($params['customer_notes']) {
        $cart['notes'] = $params['customer_notes'];
    }

    $cart['guest_checkout'] = $is_anonymous_checkout_allowed && (!$user_id || $params['guest_checkout']);

    $is_shipping_method_changed = false;
    if (!empty($cart['chosen_shipping'])) {
        $shipping_ids = array_column($cart['shipping'], 'shipping_id');
        $is_shipping_method_changed = !empty(array_diff($cart['chosen_shipping'], $shipping_ids));
    }

    $shipping_calculation_type = fn_checkout_get_shippping_calculation_type($cart, $is_location_changed, $is_shipping_method_changed);

    fn_calculate_cart_content($cart, $auth, $shipping_calculation_type, true, 'F');

    if ($params['payment_id']) {
        $cart = fn_checkout_update_payment(
            $cart,
            $auth,
            $params['payment_id'],
            $params['payment_info']
        );
    }

    $shipping_hash = fn_get_shipping_hash($cart['product_groups']);
    fn_save_cart_content($cart, $auth['user_id']);

    if (Registry::get('settings.Checkout.display_shipping_step') !== YesNo::NO
        && !empty(Tygh::$app['session']['shipping_hash'])
        && Tygh::$app['session']['shipping_hash'] !== $shipping_hash
        && $cart['shipping_required']
        && !isset($params['force_rendering'])
    ) {

        /**
         * Executes when updating checkout step and the shipping cost was changed,
         * allows you to modify the redirection parameters.
         *
         * @param array $cart            Cart content
         * @param array $auth            Customer's authentication data
         * @param array $params          Step update parameters
         * @param array $redirect_params Redirection parameters
         */
        fn_set_hook('checkout_update_steps_shipping_changed', $cart, $auth, $params, $redirect_params);

        return array(false, $redirect_params);
    }

    return array(!$errors, $redirect_params);
}

/**
 * Handles order placement on checkout.
 *
 * @param array $cart   Cart contents
 * @param array $auth   Authentication data
 * @param array $params Request parameters
 *
 * @return int Order placement result
 */
function fn_checkout_place_order(&$cart, &$auth, $params)
{
    // Prevent unauthorized access
    if (empty($cart['user_data']['email'])) {
        return PLACE_ORDER_STATUS_DENIED;
    }

    // Prevent using disabled payment method by challenging HTTP data
    if (!empty($params['payment_id'])) {
        $cart['payment_id'] = $params['payment_id'];
    }

    if (!empty($cart['payment_id'])) {
        $payment_method_data = fn_get_payment_method_data($cart['payment_id']);

        if (empty($payment_method_data) || $payment_method_data['status'] != 'A') {
            fn_set_notification('E', __('notice'), __('payment_method_not_found'));

            return PLACE_ORDER_STATUS_TO_CART;
        }
    }

    unset($cart['failed_order_id'], $cart['processed_order_id']);

    if (!empty($params['payment_info'])) {
        $cart['payment_info'] = $params['payment_info'];
    } else {
        $cart['payment_info'] = array();
    }

    if (empty($params['payment_info']) && !empty($cart['extra_payment_info'])) {
        $cart['payment_info'] = empty($cart['payment_info'])
            ? []
            : $cart['payment_info'];
        $cart['payment_info'] = array_merge($cart['extra_payment_info'], $cart['payment_info']);
    }

    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];
    $cart['storefront_id'] = $storefront->storefront_id;

    if (!empty($cart['products'])) {
        foreach ($cart['products'] as $cart_id => $product) {

            /** @var string $_is_edp */
            $_is_edp = db_get_field("SELECT is_edp FROM ?:products WHERE product_id = ?i", $product['product_id']);

            /**
             * Executes before checking a product stock balance when placing an order on checkout,
             * allows to modify product data.
             *
             * @param array  $cart    Cart contents
             * @param array  $auth    Authentication data
             * @param array  $params  Request parameters
             * @param string $cart_id Product cart ID
             * @param string $product Product data
             * @param string $_is_edp Whether product is downloadable
             */
            fn_set_hook('checkout_place_order_before_check_amount_in_stock', $cart, $auth, $params, $cart_id, $product, $_is_edp);

            if (fn_check_amount_in_stock($product['product_id'], $product['amount'], empty($product['product_options']) ? array() : $product['product_options'], $cart_id, $_is_edp, 0, $cart) == false) {
                fn_delete_cart_product($cart, $cart_id);

                return PLACE_ORDER_STATUS_TO_CART;
            }

            $exceptions = fn_get_product_exceptions($product['product_id'], true);
            if (!isset($product['options_type']) || !isset($product['exceptions_type'])) {
                $product = array_merge($product, db_get_row('SELECT options_type, exceptions_type FROM ?:products WHERE product_id = ?i', $product['product_id']));
                $product = fn_normalize_product_overridable_fields($product);
            }

            if (!fn_is_allowed_options_exceptions($exceptions, $product['product_options'], $product['options_type'], $product['exceptions_type'])) {
                fn_set_notification(
                    NotificationSeverity::ERROR,
                    __('notice'),
                    __('product_options_forbidden_combination', ['[product]' => $product['product']])
                );
                fn_delete_cart_product($cart, $cart_id);

                return PLACE_ORDER_STATUS_TO_CART;
            }

            if (!fn_is_allowed_options($product)) {
                fn_set_notification(
                    NotificationSeverity::ERROR,
                    __('notice'),
                    __('product_disabled_options', ['[product]' => $product['product']])
                );
                fn_delete_cart_product($cart, $cart_id);

                return PLACE_ORDER_STATUS_TO_CART;
            }
        }
    }

    [$order_id, $process_payment] = fn_place_order($cart, $auth);

    // Clean up saved shipping rates
    unset(Tygh::$app['session']['product_groups']);

    if (!empty($order_id)) {
        // Update user and profile fields
        if (!empty($auth['user_id'])) {
            fn_checkout_update_user_profile($auth, $cart);
        } elseif (fn_checkout_is_email_address_fake($cart['user_data']['email'])) {
            $cart['user_data']['email'] = '';
        }

        if (empty($params['skip_payment']) && $process_payment == true || (!empty($params['skip_payment']) && empty($auth['act_as_user']))) { // administrator, logged in as customer can skip payment
            $payment_info = !empty($cart['payment_info']) ? $cart['payment_info'] : array();
            fn_start_payment($order_id, array(), $payment_info);
        }

        /**
         * Perform actions before order placement redirect on checkout
         *
         * @param array $cart   Cart data
         * @param array $auth   Authentication data
         * @param array $params Request parameters
         */
        fn_set_hook('checkout_place_orders_pre_route', $cart, $auth, $params);

        fn_order_placement_routines('route', $order_id);

        return PLACE_ORDER_STATUS_OK;
    } else {
        return PLACE_ORDER_STATUS_TO_CART;
    }
}

/**
 * Returns available checkout steps lists
 *
 * @param  array $profile_fields Profile fields
 *
 * @return array
 */
function fn_checkout_get_display_steps($profile_fields = array())
{
    if (!$profile_fields) {
        $profile_fields = fn_get_profile_fields('O');
    }

    $display_steps = array(
        'step_one'   => true,
        'step_two'   => true,
        'step_three' => true,
        'step_four'  => true,
    );
    if (Registry::get('settings.Checkout.configure_sign_in_step') == 'hide') {
        // Need to check profile fields
        $required_fields = fn_get_profile_fields('O', array(), CART_LANGUAGE, array(
            'get_checkout_required' => true,
        ));
        if (empty($required_fields['C']) && Registry::get('settings.Checkout.disable_anonymous_checkout') != 'Y') {
            $display_steps['step_one'] = false;
        }
    }
    if (empty($profile_fields['B']) && empty($profile_fields['S'])) {
        $display_steps['step_two'] = false;
    }
    if (Registry::get('settings.Checkout.display_shipping_step') != 'Y' && fn_allowed_for('ULTIMATE')) {
        $display_steps['step_three'] = false;
    }
    if (Registry::get('settings.Checkout.display_payment_step') != 'Y') {
        $display_steps['step_four'] = false;
    }

    return $display_steps;
}

/**
 * Gets SQL condition to manipulate with ?:user_session_products table
 *
 * @param  array $params Params
 *
 * @return string
 */
function fn_user_session_products_condition($params = array())
{
    $params = array_merge(array(
        'user_id' => null,
        'session_id' => Tygh::$app['session']->getID(),
        'type' => 'C',
        'user_type' => '',
        'get_session_user_id' => true,
        'get_session_id' => true,
    ), $params);

    if (is_null($params['user_id']) && $params['get_session_user_id']) {
        if (!empty(Tygh::$app['session']['auth']['user_id'])) {
            $params['user_id'] = Tygh::$app['session']['auth']['user_id']; // Registereg
        } else {
            $params['user_id'] = fn_get_session_data('cu_id'); // Guest
        }
    }

    $conditions = array();

    if (!empty($params['user_id'])) {
        $conditions['user_id'] = db_quote("user_id = ?i", $params['user_id']);
    }

    if (!empty($params['session_id']) && $params['get_session_id'] === true) {
        $conditions['session_id'] = db_quote("session_id = ?s", $params['session_id']);
    }

    if (!empty($params['type'])) {
        $conditions['type'] = db_quote("type = ?s", $params['type']);
    }

    if (!empty($params['user_type'])) {
        $conditions['user_type'] = db_quote("user_type = ?s", $params['user_type']);
    }

    if (!empty($params['storefront_id'])) {
        $conditions['storefront_id'] = db_quote('storefront_id = ?i', $params['storefront_id']);
    }

    /**
     * Process user session products condition
     *
     * @param array $params     Params
     * @param array $conditions SQL conditions to manipulate with ?:user_session_products table
     */
    fn_set_hook('user_session_products_condition', $params, $conditions);

    return implode(' AND ', $conditions);
}

/**
 * Deletes products from abandoned/live carts
 *
 * @param mixed  $user_ids      IDs of users to manage (array or int)
 * @param string $data          Company ID
 * @param int    $storefront_id Storefront ID
 *
 * @return bool Always true
 */
function fn_delete_user_cart($user_ids, $data = '', $storefront_id = 0)
{
    $condition = db_quote(' AND user_id IN (?n)', (array) $user_ids);

    if (!empty($storefront_id)) {
        $condition .= db_quote(' AND storefront_id = ?i', $storefront_id);
    }

    /**
     * Deletes products from abandoned/live carts
     *
     * @param array  $user_ids   IDs of users to manage
     * @param string $condition  String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $data       Company ID
     */
    fn_set_hook('delete_user_cart', $user_ids, $condition, $data);

    db_query("DELETE FROM ?:user_session_products WHERE 1 $condition");

    return true;
}

/**
 * Gets abandoned/live carts
 *
 * @param array $params         Query parameters
 * @param int   $items_per_page Number of carts per page
 *
 * @return array Abandoned/live carts
 *      array(
 *          0: array List of carts
 *          1: array Query parameters
 *          2: array User IDs (grouped by companies for ultimate)
 *      )
 */
function fn_get_carts($params, $items_per_page = 0)
{
    // Init filter
    $params = LastView::instance()->update('carts', $params);

    // Set default values to input params
    $default_params = array (
        'page' => 1,
        'items_per_page' => $items_per_page,
    );

    $params = array_merge($default_params, $params);

    // Define fields that should be retrieved
    $fields = [
        '?:user_session_products.user_id',
        '?:users.firstname',
        '?:users.lastname',
        '?:user_session_products.timestamp AS date',
        '?:user_session_products.ip_address',
        '?:user_session_products.extra',
        '?:user_session_products.storefront_id',
    ];

    // Define sort fields
    $sortings = array(
        'customer' => "CONCAT(?:users.lastname, ?:users.firstname)",
        'date' => "?:user_session_products.timestamp",
    );

    if (fn_allowed_for('ULTIMATE')) {
        $sortings['company_id'] = "?:user_session_products.company_id";
    }

    $sorting = db_sort($params, $sortings, 'date', 'desc');

    $condition = $join = '';

    /**
     * Allows you to edit the parameters
     *
     * @param array  $params         Query params
     * @param int    $items_per_page Number of carts per page
     * @param array  $fields         SQL fields to be selected in an SQL-query
     * @param string $condition      String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $join           String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     */
    fn_set_hook('get_carts_pre', $params, $items_per_page, $fields, $condition, $join);

    $group = " GROUP BY ?:user_session_products.user_id";

    if (isset($params['cname']) && fn_string_not_empty($params['cname'])) {
        $arr = fn_explode(' ', $params['cname']);
        foreach ($arr as $k => $v) {
            if (!fn_string_not_empty($v)) {
                unset($arr[$k]);
            }
        }
        if (sizeof($arr) == 2) {
            $condition .= db_quote(
                ' AND ((?:users.firstname LIKE ?l AND ?:users.lastname LIKE ?l)'
                . ' OR (?:users.firstname LIKE ?l AND ?:users.lastname LIKE ?l))',
                '%' . $arr[0] . '%',
                '%' . $arr[1] . '%',
                '%' . $arr[1] . '%',
                '%' . $arr[0] . '%'
            );
        } else {
            $condition .= db_quote(
                ' AND (?:users.firstname LIKE ?l OR ?:users.lastname LIKE ?l)',
                '%' . trim($params['cname']) . '%',
                '%' . trim($params['cname']) . '%'
            );
        }
    }

    if (isset($params['email']) && fn_string_not_empty($params['email'])) {
        $condition .= db_quote(" AND ?:users.email LIKE ?l", "%" . trim($params['email']) . "%");
    }

    if (!empty($params['user_id'])) {
        $condition .= db_quote(" AND ?:user_session_products.user_id = ?i", $params['user_id']);
    }

    if (!empty($params['storefront_id'])) {
        $condition .= db_quote(' AND ?:user_session_products.storefront_id IN (?n)', (array) $params['storefront_id']);
    }

    if (!empty($params['online_only'])) {
        $sessions = Tygh::$app['session']->getStorageDriver()->getOnline('C');
        if (!empty($sessions)) {
            $condition .= db_quote(" AND ?:user_session_products.session_id IN (?a)", $sessions);
        } else {
            $condition .= db_quote(" AND 0");
        }
    }

    if (!empty($params['with_info_only'])) {
        $condition .= db_quote(" AND ?:users.email != ''");
    }

    if (!empty($params['users_type'])) {
        if ($params['users_type'] == 'R') {
            $condition .= db_quote(" AND !ISNULL(?:users.user_id)");
        } elseif ($params['users_type'] == 'G') {
            $condition .= db_quote(" AND ISNULL(?:users.user_id)");
        }
    }

    if (!empty($params['total_from']) || !empty($params['total_to'])) {
        $having = '';
        if (!empty($params['total_from']) && fn_is_numeric($params['total_from'])) {
            $having .= db_quote(" AND SUM(price * amount) >= ?d", $params['total_from']);
        }

        if (!empty($params['total_to']) && fn_is_numeric($params['total_to'])) {
            $having .= db_quote(" AND SUM(price * amount) <= ?d", $params['total_to']);
        }

        if (!empty($having)) {
            $users4total = db_get_fields("SELECT user_id FROM ?:user_session_products GROUP BY user_id HAVING 1 $having");
            if (!empty($users4total)) {
                $condition .= db_quote(" AND (?:user_session_products.user_id IN (?n))", $users4total);
            } else {
                $condition .= " AND (?:user_session_products.user_id = 'no')";
            }
        }
    }

    if (!empty($params['period']) && $params['period'] != 'A') {
        /** @psalm-suppress PossiblyInvalidArgument */
        [$params['time_from'], $params['time_to']] = fn_create_periods($params);
        $condition .= db_quote(" AND (?:user_session_products.timestamp >= ?i AND ?:user_session_products.timestamp <= ?i)", $params['time_from'], $params['time_to']);
    }

    $_condition = array();
    if (!empty($params['product_type_c'])) {
        $_condition[] = "?:user_session_products.type = 'C'";
    }
    if (!empty($params['product_type_w'])) {
        $_condition[] = "?:user_session_products.type = 'W'";
    }

    if (!empty($_condition)) {
        $condition .= " AND (" . implode(" OR ", $_condition).")";
    }

    if (!empty($params['p_ids']) || !empty($params['product_view_id'])) {
        $arr = (strpos($params['p_ids'], ',') !== false || !is_array($params['p_ids'])) ? explode(',', $params['p_ids']) : $params['p_ids'];
        if (empty($params['product_view_id'])) {
            $condition .= db_quote(" AND ?:user_session_products.product_id IN (?n)", $arr);
        } else {
            $condition .= db_quote(" AND ?:user_session_products.product_id IN (?n)", db_get_fields(fn_get_products(array('view_id' => $params['product_view_id'], 'get_query' => true))));
        }
    }

    $join .= " LEFT JOIN ?:users ON ?:user_session_products.user_id = ?:users.user_id";

    // checking types for retrieving from the database
    $type_restrictions = array('C');

    /**
     * Sets getting abandoned/live carts parameters
     *
     * @param array  $type_restrictions Product types
     * @param array  $params            Query params
     * @param string $condition         String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $join              String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param array  $fields            SQL fields to be selected in an SQL-query
     * @param string $group             String containing the SQL-query GROUP BY field
     * @param array  $array_index_field Deprecated unused parameter
     */
    fn_set_hook('get_carts', $type_restrictions, $params, $condition, $join, $fields, $group, $array_index_field);

    if (!empty($type_restrictions) && is_array($type_restrictions)) {
        $condition .= " AND ?:user_session_products.type IN ('" . implode("', '", $type_restrictions) . "')";
    }

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $limit = db_paginate($params['page'], $params['items_per_page']);
    }

    if (fn_allowed_for('ULTIMATE')) {
        $group = " GROUP BY ?:user_session_products.user_id, ?:user_session_products.company_id";
    }

    /**
     * Gets abandoned/live carts
     *
     * @param array  $params         Query params
     * @param int    $items_per_page Amount of carts per page
     * @param array  $fields         SQL fields to be selected in an SQL-query
     * @param string $join           String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition      String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $group          String containing the SQL-query GROUP BY field
     * @param string $sorting        String containing the SQL-query ORDER BY field
     * @param string $limit          String containing the SQL-query LIMIT field
     */
    fn_set_hook('get_carts_before_select', $params, $items_per_page, $fields, $join, $condition, $group, $sorting, $limit);

    $carts_list = db_get_hash_array(
        "SELECT SQL_CALC_FOUND_ROWS " . implode(', ', $fields)
        . " FROM ?:user_session_products $join"
        . " WHERE 1 $condition $group $sorting $limit",
        'user_id'
    );

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_found_rows();
    }

    $extra_data = [];
    if (!empty($carts_list)) {

        $extra_data_condition = db_quote('?:user_session_products.user_id IN (?a) AND ?:user_session_products.type = ?s', array_keys($carts_list), 'C');

        if (!empty($params['storefront_id'])) {
            $extra_data_condition .= db_quote(' AND ?:user_session_products.storefront_id IN (?n)', (array) $params['storefront_id']);
        }

        $extra_data = db_get_hash_array(
            'SELECT user_id, SUM(amount) as cart_products, SUM(amount * price) as total, order_id'
            . ' FROM ?:user_session_products'
            . ' WHERE ?p'
            . ' GROUP BY user_id',
            'user_id', $extra_data_condition
        );
    }

    if ($extra_data) {
        $carts_list = fn_array_merge($carts_list, $extra_data);
    }

    if (!empty($params['check_shipping_billing'])) {
        $profile_fields = fn_get_profile_fields('O');
    }

    $user_ids = array();
    foreach ($carts_list as &$item) {
        $data_extra = [];
        if (!empty($item['extra'])) {
            $data_extra = unserialize($item['extra']);
        }

        $item['ip_address'] = fn_ip_from_db($item['ip_address']);
        $profile_id = isset($data_extra['user_data']['profile_id']) ? $data_extra['user_data']['profile_id'] : null;
        $item['user_data'] = fn_get_user_info($item['user_id'], true, $profile_id);
        unset(
            $item['user_data']['password'],
            $item['user_data']['salt'],
            $item['user_data']['last_passwords'],
            $item['user_data']['password_change_timestamp'],
            $item['user_data']['api_key']
        );


        if (empty($item['user_data']) && !empty($data_extra)) {
            $data_user = fn_fill_contact_info_from_address($data_extra);

            $item['firstname'] = (!empty($data_user['firstname'])) ? $data_user['firstname'] : '';
            $item['lastname'] = (!empty($data_user['lastname'])) ? $data_user['lastname'] : '';
            $item['phone'] = (!empty($data_user['phone'])) ? $data_user['phone'] : '';
            $item['email'] = (!empty($data_extra['email'])) ? $data_extra['email'] : '';

        } else {
            $item['user_data']['firstname'] = (!empty($item['user_data']['firstname'])) ? $item['user_data']['firstname'] : null;
            $item['user_data']['lastname'] = (!empty($item['user_data']['lastname'])) ? $item['user_data']['lastname'] : null;
            $item['user_data']['phone'] = (!empty($item['user_data']['phone'])) ? $item['user_data']['phone'] : null;

            $item['user_data'] = fn_fill_contact_info_from_address($item['user_data']);

            $item['firstname'] = (!empty($item['user_data']['firstname'])) ? $item['user_data']['firstname'] : '';
            $item['lastname'] = (!empty($item['user_data']['lastname'])) ? $item['user_data']['lastname'] : '';
            $item['phone'] = (!empty($item['user_data']['phone'])) ? $item['user_data']['phone'] : '';
        }

        if (!empty($params['check_shipping_billing']) && $item['user_data']) {
            $item['user_data']['ship_to_another'] = fn_check_shipping_billing($item['user_data'], $profile_fields);
        }

        if (fn_allowed_for('ULTIMATE')) {
            $user_ids[$item['company_id']][] = $item['user_id'];
        } else {
            $user_ids[] = $item['user_id'];
        }
    }

    /**
     * Actions after getting abandoned/live carts
     *
     * @param array  $carts_list     List of abandoned/live carts
     * @param array  $params         Query params
     * @param array  $user_ids       Cart User IDs. Grouped by companies for ultimate
     * @param int    $items_per_page Amount of carts per page
     * @param array  $fields         SQL fields to be selected in an SQL-query
     * @param string $join           String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition      String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $group          String containing the SQL-query GROUP BY field
     * @param string $sorting        String containing the SQL-query ORDER BY field
     * @param string $limit          String containing the SQL-query LIMIT field
     */
    fn_set_hook('get_carts_post', $carts_list, $params, $user_ids, $items_per_page, $fields, $join, $condition, $group, $sorting, $limit);

    LastView::instance()->processResults('carts', $carts_list, $params);

    return array($carts_list, $params, $user_ids);
}

/**
 * Gets products in a particular abandoned or live cart
 *
 * @param  int   $user_id User ID
 * @param  array $params  Params
 *
 * @return array
 */
function fn_get_cart_products($user_id, $params = array())
{
    $fields = array(
        'p.item_id',
        'p.item_type',
        'p.product_id',
        'p.amount',
        'p.price',
        'p.extra',
        'd.product',
    );
    $conditions = [
        db_quote("p.user_id = ?i", $user_id),
        db_quote("p.type = 'C'"),
        db_quote("p.item_type IN (?a)", fn_get_cart_content_item_types()),
    ];

    if (!empty($params['storefront_id'])) {
        $conditions[] = db_quote("p.storefront_id = ?i", $params['storefront_id']);
    }

    if (fn_allowed_for('ULTIMATE') && !empty($params['c_company_id'])) {
        $conditions[] = db_quote("p.company_id = ?i", $params['c_company_id']);
    }

    /**
     * Gets products in a particular abandoned or live cart
     *
     * @param int    $user_id    User ID
     * @param array  $params     Params
     * @param array  $fields     SQL fields to be selected in an SQL-query
     * @param string $conditions String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     */
    fn_set_hook('get_cart_products', $user_id, $params, $fields, $conditions);

    $cart_products = db_get_array(
        "SELECT " . implode(', ', $fields)
        . " FROM ?:user_session_products p"
        . " LEFT JOIN ?:product_descriptions d ON p.product_id = d.product_id AND d.lang_code = ?s"
        . " WHERE " . implode(' AND ', $conditions),
        DESCR_SL
    );

    foreach ($cart_products as $key => $product) {
        $cart_products[$key]['extra'] = unserialize($product['extra']);
    }

    /**
     * Actions after getting products in a particular abandoned or live cart
     *
     * @param int    $user_id       User ID
     * @param array  $params        Params
     * @param array  $cart_products Products list in a abandoned or live cart
     * @param array  $fields        SQL fields to be selected in an SQL-query
     * @param string $conditions    String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     */
    fn_set_hook('get_cart_products_post', $user_id, $params, $cart_products, $fields, $conditions);

    return $cart_products;
}

/**
 * Stores shipping rates when managing order
 *
 * @param int   $order_id      Order number
 * @param array $cart          Cart contents
 * @param array $customer_auth Authentication data
 */
function fn_store_shipping_rates($order_id, &$cart, $customer_auth)
{
    fn_set_hook('store_shipping_rates_pre', $order_id, $cart, $customer_auth);
    if (!empty($cart['product_groups'])) {
        foreach ($cart['product_groups'] as $group_key => $group) {
            if (!empty($group['chosen_shippings'])) {
                foreach ($group['chosen_shippings'] as $shipping_key => $shipping) {
                    if (!empty($shipping['stored_shipping']) && empty($cart['stored_shipping'][$group_key][$shipping_key])) {
                        $cart['stored_shipping'][$group_key][$shipping_key] = $shipping['rate'];
                    }
                }
            }
        }
    }
    fn_set_hook('store_shipping_rates_post', $order_id, $cart, $customer_auth);
}

/**
 * Checks if an order is available to view for a customer
 *
 * @param  int   $order_id Order ID
 * @param  array $auth     Auth array
 *
 * @return bool
 */
function fn_is_order_allowed($order_id, $auth)
{
    $orders_company_condition = '';
    if (fn_allowed_for('ULTIMATE')) {
        $orders_company_condition = fn_get_company_condition('?:orders.company_id');
    }

    if (!empty($auth['user_id'])) {
        $allowed = db_get_field(
            "SELECT user_id FROM ?:orders WHERE user_id = ?i AND order_id = ?i $orders_company_condition",
            $auth['user_id'], $order_id
        );

    } elseif (!empty($auth['order_ids'])) {
        $allowed = in_array($order_id, $auth['order_ids']);
    }

    /**
     * Deprecated
     * @since 4.3.7
     */
    fn_set_hook('is_order_allowed', $order_id, $allowed);

    /**
     * Checks if an order is available for a customer
     *
     * @param int   $order_id   Order ID
     * @param array $auth       Auth array
     * @param int   $allowed    Allowed flag
     */
    fn_set_hook('is_order_allowed_post', $order_id, $auth, $allowed);

    return !empty($allowed);
}

/**
 * Fills empty/missing fields of location data.
 *
 * @param array $location Locations data with empty/missing fields
 * @param string $prefix Prefix to populate shipping (s_) or billing (b_) fields
 *
 * @return array Location data with filled/populated fields
 */
function fn_fill_location_fields($location = array(), $prefix = '')
{
    $address_fields = array('country', 'state', 'city', 'zipcode', 'address', 'phone');

    foreach ($address_fields as $field_name) {
        if (empty($location[$field_name])) {
            $location[$field_name] = Registry::get('settings.General.default_' . $field_name);
        }
        if (
            is_string($prefix)
            && fn_string_not_empty($prefix)
            && empty($location[$prefix . $field_name])
        ) {
            $location[$prefix . $field_name] = $location[$field_name];
        }
    }

    return $location;
}

/**
 * Filtering of user fields to use in ordering.
 * Will be included the special fields for order and fields available in order checkout.
 *
 * @param array $user_data List of user data
 *
 * @return array
 */
function fn_filter_order_user_data($user_data)
{
    $user_data = array_map(function ($param) {
        return is_null($param) ? '' : $param;
    }, $user_data);
    $result = array();
    $fields = db_get_fields(
        "SELECT field_name FROM ?:profile_fields WHERE checkout_show = 'Y' AND is_default = 'Y' AND field_name != ''"
    );

    // Special order fields
    $fields = array_merge($fields, ['email', 'tax_exempt']);

    foreach ($fields as $field) {
        if (array_key_exists($field, $user_data)) {
            $result[$field] = $user_data[$field];
        }
    }

    return $result;
}

/**
 * Obfuscates sensitive data (card number and cvc/cvv2) in the payment information of orders.
 *
 * @param array $params Orders search parameters
 */
function fn_batch_cleanup_payment_info(array $params = array())
{
    $params = array_merge(array(
        'time_from' => null,
        'time_to'   => null,
        'status'    => array(),
        'order_id'  => array(),
    ), $params);

    $condition = db_quote('od.type = ?s', 'P');
    if ($params['order_id']) {
        $condition .= db_quote(' AND o.order_id IN (?n)', (array)  $params['order_id']);
    }
    if ($params['time_from'] !== null) {
        $condition .= db_quote(' AND o.timestamp >= ?i', $params['time_from']);
    }
    if ($params['time_to'] !== null) {
        $condition .= db_quote(' AND o.timestamp <= ?i', $params['time_to']);
    }
    if ($params['status']) {
        $condition .= db_quote(' AND o.status IN (?a)', (array) $params['status']);
    }

    $orders_to_cleanup = db_get_hash_single_array(
        'SELECT o.order_id AS order_id, od.data AS payment_info'
        . ' FROM ?:order_data AS od'
        . ' LEFT JOIN ?:orders AS o ON o.order_id = od.order_id'
        . ' WHERE ?p',
        array('order_id', 'payment_info'),
        $condition
    );

    if ($orders_to_cleanup) {
        foreach ($orders_to_cleanup as $order_id => $payment_info) {
            if (!$payment_info) {
                continue;
            }
            fn_cleanup_payment_info($order_id, $payment_info, true);
        }
    }
}

/**
 * Determines whether user data was changed in the cart.
 *
 * @param array $cart
 * @param array $auth
 *
 * @return bool
 */
function fn_is_cart_user_data_changed(array &$cart, array $auth)
{
    fn_filter_hidden_profile_fields($cart['user_data'], 'O');
    ksort($cart['user_data']);

    // Here check the previous and the current checksum of user_data - if they are different, recalculate the cart.
    $current_state = fn_crc32(serialize($cart['user_data']));

    $cart['user_data'] = fn_get_user_info($auth['user_id'], empty($_REQUEST['profile']), $cart['profile_id']);
    fn_filter_hidden_profile_fields($cart['user_data'], 'O');
    ksort($cart['user_data']);

    return $current_state != fn_crc32(serialize($cart['user_data']));
}

/**
 * Updates payment method in the cart.
 *
 * @param array $cart         Cart content
 * @param array $auth         Customer's authentication data
 * @param int   $payment_id   Selected payment method identifier
 * @param array $payment_info Selected payment method payment information
 *
 * @return array
 */
function fn_checkout_update_payment($cart, $auth, $payment_id, $payment_info = [])
{
    $user_id = empty($auth['user_id'])
        ? 0
        : $auth['user_id'];

    $cart['payment_id'] = (int) $payment_id;
    unset($cart['extra_payment_info']);

    if ($payment_info) {
        $cart['extra_payment_info'] = $payment_info;
    }

    fn_update_payment_surcharge($cart, $auth);
    fn_save_cart_content($cart, $user_id);

    return $cart;
}

/**
 * Updates customer's user data in the cart.
 *
 * @param array $cart            Cart content
 * @param array $auth            Customer's authentication data
 * @param array $user_data       New customer's user data
 * @param bool  $ship_to_another Whether the billing address differes from the shipping one
 * @param int   $user_id         Customer's user identifier
 *
 * @return array
 */
function fn_checkout_update_user_data(&$cart, $auth, $user_data, $ship_to_another, $user_id)
{
    $cart['user_data'] = empty($cart['user_data'])
        ? []
        : $cart['user_data'];

    $user_data = fn_array_merge($cart['user_data'], $user_data);
    $user_data['user_type'] = empty($cart['user_data']['user_type'])
        ? AREA
        : $cart['user_data']['user_type'];

    $user_data = fn_fill_contact_info_from_address($user_data);

    $profile_fields = fn_get_profile_fields('O');
    fn_convert_profile_dates_to_timestamps($user_data, $profile_fields);

    if (!$ship_to_another) {
        fn_fill_address($user_data, $profile_fields);
    }

    $cart['user_data'] = fn_array_merge($cart['user_data'], $user_data);
    unset(
        $cart['user_data']['s_country_descr'],
        $cart['user_data']['b_country_descr'],
        $cart['user_data']['s_state_descr'],
        $cart['user_data']['b_state_descr']
    );

    /** @var \Tygh\Location\Manager $manager */
    $manager = Tygh::$app['location'];
    // prefill some address fields from default settings when it's necessary
    [$cart['user_data'],] = $manager->setLocationFromUserData($cart['user_data']);

    /**
     * Executes after customer's user data is updated on checkout,
     * allows you to modify the returned values of the function.
     *
     * @param array $cart            Cart content
     * @param array $auth            Customer's authentication data
     * @param array $user_data       New customer's user data
     * @param bool  $ship_to_another Whether the billing address differs from the shipping one
     * @param int   $user_id         Customer's user identifier
     */
    fn_set_hook('checkout_update_user_data_post', $cart, $auth, $user_data, $ship_to_another, $user_id);

    return [$cart, $auth];
}

/**
 * Gets hash of location fields that are important for shipping.
 *
 * @param array $user_data User data from the cart.
 *                         Must be pre-populated with all the fields
 *
 * @return string Hash
 */
function fn_checkout_get_location_hash(array $user_data)
{
    /** @var \Tygh\Location\Manager $manager */
    $manager = Tygh::$app['location'];

    $location = $manager->getLocationFromUserData($user_data);

    return md5(json_encode($location));
}

/**
 * Flattens payment methods list for the checkout page.
 *
 * @param array $payment_methods
 *
 * @deprecated since 4.10.1. This function is used to keep the backward compatibility.
 * For properly populated list use \fn_prepare_checkout_payment_methods() with $get_payment_groups = false instead.
 *
 * @return array
 */
function fn_checkout_flatten_payments_list(array $payment_methods)
{
    $payment_methods_flat = array_reduce($payment_methods, function($list, $tab) {
        $list = array_merge($list, array_values($tab));
        return $list;
    }, []);

    usort($payment_methods_flat, function ($payment1, $payment2) {
        if ($payment1['position'] > $payment2['position']) {
            return 1;
        }
        if ($payment1['position'] < $payment2['position']) {
            return -1;
        }

        return 0;
    });

    return array_combine(array_column($payment_methods_flat, 'payment_id'), $payment_methods_flat);
}

/**
 * Generates a fake email address when placing an order with the "Show the "E-mail" field on checkout" setting disabled.
 *
 * @param array  $user_data  Customer data
 * @param string $unique_id  Unique value to create email address with
 *
 * @return string
 */
function fn_checkout_generate_fake_email_address(array $user_data, $unique_id)
{
    $name_parts = [];

    array_walk($user_data, 'fn_trim_helper');
    if (isset($user_data['firstname']) && $user_data['firstname'] !== '') {
        $name_parts[] = $user_data['firstname'];
    }
    if (isset($user_data['lastname']) && $user_data['lastname'] !== '') {
        $name_parts[] = $user_data['lastname'];
    }

    $name_parts[] = $unique_id;

    $domain = 'example.com';

    $local_part = fn_generate_name(implode(' ', $name_parts));

    /**
     * Executes when generating a fake email address for a customer when placing an order,
     * allows you to change a local-part and a domain of the generated email address.
     *
     * @param array  $user_data  Customer data
     * @param string $unique_id  Unique value to create email address with
     * @param string $local_part Locat-part of the email address
     * @param string $domain     Email of the email address
     */
    fn_set_hook('checkout_generate_fake_email', $user_data, $unique_id, $local_part, $domain);

    $fake_email = $local_part . '@' . $domain;

    return $fake_email;
}

/**
 * Checks if the customer email is the auto-generated fake one.
 *
 * @param string $email_address Customer email address
 *
 * @return bool Whether the address is the fake one
 */
function fn_checkout_is_email_address_fake($email_address)
{
    $email_address_parts = explode('@', $email_address);
    $domain = end($email_address_parts);

    $fake_domain = 'example.com';

    $is_fake = $domain === $fake_domain;

    /**
     * Executes when checking if the customer email is the auto-generated fake one,
     * allows you to modify the check result.
     *
     * @param string $email_address Customer email address
     * @param bool   $is_fake       Check result
     */
    fn_set_hook('checkout_is_email_address_fake_post', $email_address, $is_fake);

    return $is_fake;
}

/**
 * Gets the $calculate_shipping parameter value for the fn_calculate_cart_content function.
 *
 * @param array $cart                       Cart content
 * @param bool  $is_location_changed        Whether a customer's location was changed
 * @param bool  $is_shipping_method_changed When was the shipping method changed
 *
 * @return string
 */
function fn_checkout_get_shippping_calculation_type(array $cart, $is_location_changed, $is_shipping_method_changed = false)
{
    $is_shipping_method_selected = !empty($cart['chosen_shipping']);
    $estimate_with_no_shipping = Registry::ifGet('checkout.estimate_shipping_when_none_selected', YesNo::YES) === YesNo::YES;

    return $estimate_with_no_shipping && !$is_shipping_method_selected || $is_location_changed || $is_shipping_method_changed
        ? ShippingCalculationTypes::CALCULATE_ALL_SHIPPING_METHODS
        : ShippingCalculationTypes::SKIP_CALCULATION;
}

/**
 * Removes currency.
 *
 * @param int $currency_id
 */
function fn_delete_currency($currency_id)
{
    $currency_code = db_get_field('SELECT currency_code FROM ?:currencies WHERE currency_id = ?i', $currency_id);

    db_query('DELETE FROM ?:currencies WHERE currency_code = ?s', $currency_code);
    db_query('DELETE FROM ?:currency_descriptions WHERE currency_id = ?i', $currency_id);

    /** @var \Tygh\Storefront\Repository $storefronts_repository */
    $storefronts_repository = Tygh::$app['storefront.repository'];
    [$storefronts,] = $storefronts_repository->find(['currency_id' => $currency_id]);
    /** @var \Tygh\Storefront\Storefront $storefront */
    foreach ($storefronts as $storefront) {
        $storefronts_repository->save($storefront->removeCurrencyIds($currency_id));
    }

    fn_save_currencies_integrity();
}

/**
 * Prevents usage of deleted and disabled currencies.
 *
 * @param string $primary_currency Primary currency code
 *
 * @return void
 */
function fn_save_currencies_integrity($primary_currency = CART_PRIMARY_CURRENCY)
{
    // Prevent disabling all currencies
    $avail = db_get_field('SELECT COUNT(*) FROM ?:currencies WHERE status = ?s', ObjectStatuses::ACTIVE);
    if (!$avail) {
        $primary_currency_exists = db_get_field('SELECT COUNT(1) FROM ?:currencies WHERE currency_code = ?s', $primary_currency);
        if (!$primary_currency_exists) {
            $primary_currency = db_get_field('SELECT lang_code FROM ?:currencies WHERE status = ?s LIMIT 1', ObjectStatuses::HIDDEN);
            if (!$primary_currency) {
                $primary_currency = db_get_field('SELECT lang_code FROM ?:currencies LIMIT 1');
            }
        }
        db_query('UPDATE ?:currencies SET status = ?s WHERE currency_code = ?s', ObjectStatuses::ACTIVE, $primary_currency);

        fn_set_notification(
            NotificationSeverity::WARNING,
            __('warning'),
            __('warning_cannot_disable_all_currencies')
        );
    }

    $frontend_default_currency = Registry::get('settings.Appearance.frontend_default_currency');

    $available = db_get_field(
        'SELECT COUNT(1) FROM ?:currencies WHERE currency_code IN (?a) AND status = ?s',
        $frontend_default_currency,
        ObjectStatuses::ACTIVE
    );
    if (!$available) {
        $first_avail_code = db_get_field('SELECT currency_code FROM ?:currencies WHERE status = ?s LIMIT 1', ObjectStatuses::ACTIVE);
        Settings::instance()->updateValue('frontend_default_currency', $first_avail_code, 'Appearance');

        fn_set_notification(
            NotificationSeverity::WARNING,
            __('warning'),
            __(
                'warning_default_currency_disabled',
                [
                    '[link]' => fn_url('settings.manage?section_id=Appearance'),
                ]
            )
        );
    }

    fn_save_storefront_currency_integrity();
}

/**
 * Assigns currencies for storefronts that have no currencies configured.
 *
 * @return void
 */
function fn_save_storefront_currency_integrity()
{
    $storefront_repository = StorefrontProvider::getRepository();
    [$all_storefronts,] = $storefront_repository->find(['get_total' => false]);
    $currencies = fn_get_currencies_list(['status' => ObjectStatuses::ACTIVE]);
    foreach ($all_storefronts as $storefront) {
        $storefront_has_currencies = false;
        foreach ($currencies as $currency) {
            if (!$currency['storefront_ids']) {
                $storefront_has_currencies = true;
                break;
            }
            if (in_array($storefront->storefront_id, explode(',', $currency['storefront_ids']))) {
                $storefront_has_currencies = true;
                break;
            }
        }

        if ($storefront_has_currencies) {
            continue;
        }

        $currency_ids = array_column($currencies, 'currency_id');
        $storefront_repository->save($storefront->addCurrencyIds($currency_ids));

        fn_set_notification(
            NotificationSeverity::WARNING,
            __('warning'),
            __(
                'storefront_all_currencies_assigned',
                [
                    '[url]'  => fn_url('storefronts.update?storefront_id=' . $storefront->storefront_id),
                    '[name]' => $storefront->name,
                ]
            )
        );
    }
}

/**
 * Determines if current user is allowed to work with multiple profiles
 *
 * @param array $auth User authentication data
 *
 * @return bool
 */
function fn_checkout_is_multiple_profiles_allowed($auth)
{
    return !empty($auth['user_id']) && Registry::get('settings.General.user_multiple_profiles') == YesNo::YES;
}

/**
 * Fetches user profiles with specific to new checkout data
 *
 * @param array $auth User authentication data
 *
 * @return array
 */
function fn_checkout_get_user_profiles($auth)
{
    $user_profiles = fn_get_user_profiles($auth['user_id'], [
        'fetch_fields_values' => true,
        'fetch_descriptions'  => true,
    ]);

    $profile_fields = fn_get_profile_fields('O', $auth, CART_LANGUAGE, [
        'section' => ProfileFieldSections::SHIPPING_ADDRESS,
    ]);

    foreach ($user_profiles as $profile_id => &$profile) {
        $user_profile = fn_get_user_info($auth['user_id'], true, $profile_id);
        $is_valid_profile = true;

        if (empty($profile['company']) && !empty($user_profile['company'])) {
            $profile['company'] = $user_profile['company'];
        }

        foreach ($profile_fields[ProfileFieldSections::SHIPPING_ADDRESS] as $field_id => $field) {
            if (in_array($field['field_name'], ['s_city', 'b_city'])) {
                $field['checkout_required'] = YesNo::YES;
            }

            if (!isset($field['checkout_required']) || $field['checkout_required'] === YesNo::NO) {
                continue;
            }

            $is_default_field_empty = !isset($user_profile[$field['field_name']]) || trim($user_profile[$field['field_name']]) === '';
            $is_custom_field_empty = !isset($user_profile['fields'][$field_id]) || trim($user_profile['fields'][$field_id]) === '';

            if ($is_default_field_empty && $is_custom_field_empty) {
                $is_valid_profile = false;
                break;
            }
        }

        $profile['is_selectable'] = $profile['all_required_fields_filled'] = $is_valid_profile;
    }
    unset($profile);

    /**
     * Executes after gets user profiles, allows to modify user profiles
     *
     * @param array $auth           User authentication data
     * @param array $user_profiles  User profiles
     * @param array $profile_fields Profile fields
     */
    fn_set_hook('checkout_get_user_profiles', $auth, $user_profiles, $profile_fields);

    $selectable_profiles = array_filter($user_profiles, function ($profile) {
        return !empty($profile['is_selectable']);
    });

    $show_profiles_on_checkout = count($user_profiles) > 1 || count($user_profiles) === count($selectable_profiles);

    return [$user_profiles, $selectable_profiles, $show_profiles_on_checkout];
}

/**
 * Sets profile identifier to cart
 *
 * @param array $cart       Cart
 * @param array $auth       Auth data
 * @param int   $profile_id Profile identifier
 *
 * @return bool
 */
function fn_checkout_set_cart_profile_id(&$cart, $auth, $profile_id)
{
    if (empty($auth['user_id'])) {
        return false;
    }

    $cart['profile_id'] = $cart['user_data']['profile_id'] = $profile_id;
    $cart['profile_changed'] = true;

    fn_set_session_data('last_order_profile_id', $profile_id);

    /** @var \Tygh\Location\Manager $manager */
    $manager = Tygh::$app['location'];
    $manager->setLocationFromUserProfile($profile_id);

    return true;
}

/**
 * Forces notifications being sent to customer when EDP in order have Activation mode set to Immeditaly.
 *
 * @param array $force_notification
 * @param array $edp_data
 *
 * @return \Tygh\Notifications\Settings\Ruleset
 *
 * @internal
 */
function fn_get_edp_notification_rules(array $force_notification, array $edp_data)
{
    /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
    $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];

    foreach ($edp_data as $group) {
        foreach ($group['files'] as $file) {
            if ($file['activation'] === 'I') {
                $force_notification[UserTypes::CUSTOMER] = true;
                break;
            }
        }
    }

    return $notification_settings_factory->create($force_notification);
}

/**
 * Updates order staff only notes
 *
 * @param int    $order_id Order identifier to update notes for
 * @param string $text     Order details text
 *
 */
function fn_update_order_staff_only_notes($order_id, $text = '')
{
    if ($order_id) {
        db_query('UPDATE ?:orders SET details = ?s WHERE order_id = ?i', $text, $order_id);
    }
}

/**
 * Gets order statuses which marked as payment received
 *
 * @return array List of one-letter status codes
 */
function fn_get_settled_order_statuses()
{
    $settled_order_statuses = fn_get_status_by_type_and_param(STATUSES_ORDER, ['payment_received' => YesNo::YES]);

    /**
     * Gets order statuses which marked as payment received (at the end of fn_get_order_paid_statuses())
     *
     * @param array $settled_order_statuses List of statuses which marked as payment received
     */
    fn_set_hook('get_settled_order_statuses_post', $settled_order_statuses);

    return empty($settled_order_statuses) ? ['P', 'C'] : $settled_order_statuses;
}

/**
 * Gets currency information.
 *
 * @param int $currency_id Currency ID
 * @param string $lang_code 2 letters language code
 *
 * @return array
 */
function fn_get_currency($currency_id, $lang_code = DESCR_SL)
{
    return  db_get_row('SELECT a.*, b.description FROM ?:currencies as a LEFT JOIN ?:currency_descriptions as b ON a.currency_id = b.currency_id AND lang_code = ?s WHERE a.currency_id = ?i', $lang_code, $currency_id);
}

/**
 * Gets all payment processors sorted by categories.
 *
 * @param bool $allow_multiple_categories
 * @return array
 */
function fn_get_payment_processors_by_category($allow_multiple_categories = false)
{
    $processors = fn_get_payment_processors();
    $categories = fn_get_schema('payments', 'categories');
    usort($categories, static function ($a, $b) {
        return $a['position'] > $b['position'] ? 1 : -1;
    });
    $category_criteria = array_column($categories, 'criteria', 'name');

    $processors_by_category = [];
    foreach ($category_criteria as $category_name => $criterion) {

        $group_validator_callback = function ($processor) use ($criterion) {
            foreach ($criterion as $processor_property_name => $required_processor_property_value) {
                if (!isset($processor[$processor_property_name])) {
                    return false;
                }
                $processor_property = $processor[$processor_property_name];
                $meets_criterion = is_array($required_processor_property_value)
                    ? in_array($processor_property, $required_processor_property_value)
                    : $processor_property == $required_processor_property_value;
                if (!$meets_criterion) {
                    return false;
                }
            }
            return true;
        };

        $processors_by_category[$category_name] = (new Collection($processors))->filter($group_validator_callback)->toArray();
        if (!$allow_multiple_categories) {
            $processors = array_diff_key($processors, $processors_by_category[$category_name]);
        }
    }

    return $processors_by_category;
}

/**
 * Checks whether Terms and conditions must be accepted by a customer during checkout.
 *
 * @return bool
 */
function fn_checkout_is_terms_and_conditions_agreement_required()
{
    $is_agreement_required = null;

    /**
     * Executes when checking whether Terms and conditions must be accepted by a customer during checkout,
     * allows you to set requirement directly.
     *
     * @param bool|null $is_agreement_required Whether Terms and conditions must be accepted
     */
    fn_set_hook('checkout_is_terms_and_conditions_agreement_required_pre', $is_agreement_required);

    if ($is_agreement_required !== null) {
        return $is_agreement_required;
    }

    $layout_page = Location::instance()->get('checkout.checkout');
    $terms_and_conditions_blocks = Block::instance()->getBlocksByTypeForLocation(
        'lite_checkout_terms_and_conditions',
        $layout_page['location_id']
    );
    $is_agreement_required = array_reduce(
        $terms_and_conditions_blocks,
        function ($is_agreement_required, $block_status) {
            return $is_agreement_required || $block_status === ObjectStatuses::ACTIVE;
        },
        false
    );

    /**
     * Executes when checking whether Terms and conditions must be accepted by a customer during checkout,
     * after requirement is determined, allows you to modify the check result.
     *
     * @param bool $is_agreement_required Whether Terms and conditions must be accepted
     */
    fn_set_hook('checkout_is_terms_and_conditions_agreement_required_post', $is_agreement_required);

    return $is_agreement_required;
}

/**
 * Updates user profile after place order
 *
 * @param array $auth Auth data
 * @param array $cart Cart data
 *
 * @return void
 */
function fn_checkout_update_user_profile(array $auth, array $cart)
{
    if (empty($auth['user_id']) || empty($cart['user_data'])) {
        return;
    }

    $user_id = $auth['user_id'];
    $user_data = $cart['user_data'];
    $profile_id = isset($cart['profile_id']) ? $cart['profile_id'] : null;
    $ship_to_another = isset($user_data['ship_to_another']) ? (bool) $user_data['ship_to_another'] : true;

    if (!$ship_to_another) {
        //Do not update billing address
        $profile_fields = fn_get_profile_fields(ProfileFieldLocations::CHECKOUT_FIELDS, $auth, CART_LANGUAGE, [
            'section' => ProfileFieldSections::BILLING_ADDRESS,
        ]);

        if (!empty($profile_fields[ProfileFieldSections::BILLING_ADDRESS])) {
            foreach ($profile_fields[ProfileFieldSections::BILLING_ADDRESS] as $field) {
                unset($user_data[$field['field_name']], $user_data['fields'][$field['field_id']]);
            }
        }
    }

    $current_user_data = fn_get_user_info($user_id, true, $profile_id);

    // Check if we need to send notification with new email to customer
    $send_notification = isset($user_data['email']) && $user_data['email'] !== $current_user_data['email'];

    $user_data = fn_array_merge(
        $current_user_data,
        $user_data
    );

    fn_update_user($user_id, $user_data, $auth, true, $send_notification);
}

/**
 * Determines if user should get access to checkout page.
 *
 * @param array<string> $cart            Cart information.
 * @param array<string> $payment_methods Payment methods.
 *
 * @return bool
 */
function fn_get_access_to_checkout(array $cart, array $payment_methods)
{
    $access = true;
    if (fn_cart_is_empty($cart)) {
        $access = false;
    } elseif (empty($payment_methods)) {
        $access = false;
    }

    /**
     * Executes after access status to checkout was determined, allows to change it.
     *
     * @param array<string> $cart            Cart information.
     * @param array<string> $payment_methods Payment methods.
     * @param bool          $access          True if user can access checkout page, false otherwise.
     */
    fn_set_hook('get_access_to_checkout', $cart, $payment_methods, $access);

    return $access;
}

/**
 * Get checkout settings
 *
 * @param array<string, int|string|array> $cart Cart information.
 *
 * @return array
 */
function fn_get_checkout_settings(array $cart)
{
    $checkout_settings = Registry::get('settings.Checkout');

    /**
     * Executes after getting checkout settings used in the store,
     * allows you to modify settings that will be used on checkout.
     *
     * @param array<string> $cart              Cart information.
     * @param array<string> $checkout_settings Checkout settings information.
     */
    fn_set_hook('get_checkout_settings_post', $cart, $checkout_settings);

    return $checkout_settings;
}
