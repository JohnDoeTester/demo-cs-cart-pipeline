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

use Tygh\BlockManager\Layout;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ProductFilterProductFieldTypes;
use Tygh\Enum\ProfileDataTypes;
use Tygh\Enum\ProfileTypes;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Enum\VendorPayoutApprovalStatuses;
use Tygh\Enum\VendorPayoutTypes;
use Tygh\Enum\VendorStatuses;
use Tygh\Enum\YesNo;
use Tygh\NotificationsCenter\NotificationsCenter;
use Tygh\Providers\VendorServicesProvider;
use Tygh\Registry;
use Tygh\SmartyEngine\Security as SmartySecurity;
use Tygh\Themes\Patterns;
use Tygh\Themes\Styles;
use Tygh\Themes\Themes;
use Tygh\Tygh;
use Tygh\VendorPayoutDetailsBuilder;
use Tygh\VendorPayouts;

/* HOOKS */

function fn_mve_get_product_filter_fields(&$filters)
{
    $filters[ProductFilterProductFieldTypes::VENDOR] = [
        'db_field' => 'company_id',
        'table' => 'products',
        'description' => 'vendor',
        'condition_type' => 'F',
        'conditions' => static function ($db_field, $join, $condition) {
            if (SiteArea::isAdmin(AREA)) {
                $join .= db_quote('INNER JOIN ?:companies as companies ON companies.company_id = products.company_id');
            } elseif (SiteArea::isStorefront(AREA)) {
                $join .= db_quote(
                    'LEFT JOIN ?:company_descriptions as company_descr ON company_descr.company_id = products.company_id AND company_descr.lang_code = ?s',
                    CART_LANGUAGE
                );
            }

            return [$db_field, $join, $condition];
        },
    ];
    if (SiteArea::isStorefront(AREA)) {
        $filters[ProductFilterProductFieldTypes::VENDOR]['variant_name_field'] = 'company_descr.i18n_company';
    } else {
        $filters[ProductFilterProductFieldTypes::VENDOR]['variant_name_field'] = 'companies.company';
    }
}

function fn_mve_delete_user(&$user_id, &$user_data)
{
    if ($user_data['is_root'] == 'Y') {
        $successor_id = db_get_field(
            'SELECT user_id FROM ?:users'
            . ' WHERE company_id = ?i'
                . ' AND user_id <> ?i'
                . ' AND user_type = ?s'
            . ' LIMIT 1',
            $user_data['company_id'],
            $user_id,
            'V'
        );
        if ($successor_id) {
            db_query('UPDATE ?:users SET is_root = ?s WHERE user_id = ?i', 'Y', $successor_id);
        }
    }
}

function fn_mve_get_user_type_description(&$type_descr)
{
    $type_descr['S']['V'] = 'vendor_administrator';
    $type_descr['P']['V'] = 'vendor_administrators';
}

function fn_mve_get_user_types(&$types)
{
    $company_id = Registry::get('runtime.company_id');
    if ($company_id) {
        unset($types['A']);
    }

    $types['V'] = 'add_vendor_administrator';
}

function fn_mve_user_need_login(&$types)
{
    $types[] = 'V';
}

function fn_mve_place_order(&$order_id, &$action, &$order_status, &$cart, &$auth)
{
    $order_info = fn_get_order_info($order_id);
    if ($order_info['is_parent_order'] != 'Y' && !empty($order_info['company_id'])) {
        // Check if the order already placed
        $payout = VendorPayouts::instance()->getSimple(array(
            'order_id' => $order_id,
            'payout_type' => VendorPayoutTypes::ORDER_PLACED
        ));

        if ($payout) {
            $payout = reset($payout);
            $payout_id = $payout['payout_id'];
        } else {
            $payout_id = 0;
        }

        $company_data = fn_get_company_data($order_info['company_id']);
        $payout_data = [
            'company_id'      => $order_info['company_id'],
            'order_id'        => $order_id,
            'order_amount'    => $order_info['total'],
            'payout_type'     => VendorPayoutTypes::ORDER_PLACED,
            'approval_status' => VendorPayoutApprovalStatuses::COMPLETED,
        ];
        $payout_builder = new VendorPayoutDetailsBuilder();
        $payout_data['details'] = $payout_builder->createDetails($order_info, $cart);

        /**
         * Actions before save vendor payout
         *
         * @param array  $order_info   Order info
         * @param array  $company_data Company data info
         * @param string $action       Action
         * @param string $order_status Order status
         * @param array  $cart         Cart array
         * @param array  $payout_data  Data that will be save
         * @param int    $payout_id    Payout ID
         * @param array  $auth         Auth
         */
        fn_set_hook('mve_place_order', $order_info, $company_data, $action, $order_status, $cart, $payout_data, $payout_id, $auth);

        if (!$payout_id) {
            $payout_id = VendorPayouts::instance()->update($payout_data, $payout_id);
        }
    }

    /**
     * Actions after save vendor payout
     *
     * @param int         $order_id     Order ID
     * @param string      $action       Action
     * @param string      $order_status Order status
     * @param array       $cart         Cart array
     * @param array       $auth         Auth
     * @param array       $order_info   Order info
     * @param array|null  $company_data Company data info
     * @param array|null  $payout_data  Data that will be save
     * @param int|null    $payout_id    Payout ID
     */
    fn_set_hook('mve_place_order_post', $order_id, $action, $order_status, $cart, $auth, $order_info, $company_data, $data, $payout_id);

    return $payout_id;
}

function fn_mve_update_order(&$new_order_info, &$order_id)
{
    $old_order_info = fn_get_order_info($order_id);

    if ($old_order_info['is_parent_order'] != 'Y' && !empty($old_order_info['company_id'])) {

        $payout = VendorPayouts::instance()->getSimple(array(
            'order_id' => $order_id,
            'payout_type' => VendorPayoutTypes::ORDER_PLACED
        ));

        if ($payout) {
            $payout = reset($payout);
            $payout_id = $payout['payout_id'];
        } else {
            $payout_id = 0;
        }

        $company_data = fn_get_company_data($old_order_info['company_id']);
        $payout_data = [];
        $payout_builder = new VendorPayoutDetailsBuilder();

        if ($payout_id) {
            if ($new_order_info['total'] != $old_order_info['total']) {
                $payout_data = [
                    'company_id'      => $old_order_info['company_id'],
                    'order_id'        => $order_id,
                    'order_amount'    => $new_order_info['total'] - $old_order_info['total'],
                    'payout_type'     => VendorPayoutTypes::ORDER_CHANGED,
                    'approval_status' => VendorPayoutApprovalStatuses::COMPLETED,
                ];
                $payout_data['old_details'] = isset($payout['details']) ? unserialize($payout['details']) : $payout_builder->createDetails($old_order_info);
                $payout_data['details'] = $payout_builder->createUpdatedDetails($new_order_info, $payout_data['old_details']);
            }
        } else {
            $payout_data = [
                'company_id'      => $old_order_info['company_id'],
                'order_id'        => $order_id,
                'order_amount'    => $new_order_info['total'],
                'payout_type'     => VendorPayoutTypes::ORDER_PLACED,
                'approval_status' => VendorPayoutApprovalStatuses::COMPLETED,
            ];
            $payout_data['details'] = $payout_builder->createDetails($new_order_info);
            $payout_data['old_details'] = $payout_builder->createDetails($old_order_info);
        }

        fn_set_hook('mve_update_order', $new_order_info, $order_id, $old_order_info, $company_data, $payout_id, $payout_data);

        if ($payout_data) {
            VendorPayouts::instance()->update($payout_data);
        }
    }
}

function fn_mve_get_categories(&$params, &$join, &$condition, &$fields, &$group_by, &$sortings, &$lang_code)
{
    // Restrict categories list for microstore
    if (AREA == 'C' && !empty($params['company_ids'])) {
        $company_id = (int) $params['company_ids'];
        $id_paths = db_get_fields(
            "SELECT id_path FROM ?:categories c"
            . " JOIN ?:category_vendor_product_count p USING(category_id)"
            . " WHERE p.company_id = ?i",
            $company_id
        );
        // Getting not empty categories and their parents
        $vendor_category_ids = array();
        foreach ($id_paths as $id_path) {
            foreach (explode('/', $id_path) as $category_id) {
                $vendor_category_ids[] = $category_id;
            }
        }
        if ($vendor_category_ids) {
            $condition .= db_quote(" AND ?:categories.category_id IN(?n)", array_unique($vendor_category_ids));
        } else {
            $condition .= db_quote(" AND 0");
        }
    }
}

function fn_mve_get_categories_after_sql(&$categories, &$params)
{
    // Rewrite product_count for vendor
    if (!$params['simple'] && $company_id = Registry::get('runtime.company_id')) {
        $products_count = db_get_hash_single_array(
            "SELECT category_id, product_count FROM ?:category_vendor_product_count WHERE company_id = ?i",
            array('category_id', 'product_count'), $company_id
        );
        foreach ($categories as &$category) {
            $category['product_count'] = 0;
            if (!empty($products_count[$category['category_id']])) {
                $category['product_count'] = $products_count[$category['category_id']];
            }
        }
    }
}

function fn_mve_update_product_count_post(&$category_ids)
{
    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    // Recalculate vendor product count for particular categories
    $db->query('DELETE FROM ?:category_vendor_product_count WHERE category_id IN(?n)', $category_ids);
    $select_query = $db->quote(
        ' SELECT company_id, category_id, COUNT(product_id)'
        . ' FROM ?:products_categories c'
        . ' INNER JOIN ?:products p USING(product_id)'
        . ' WHERE category_id IN(?n)'
        . ' GROUP BY p.company_id, c.category_id',
        $category_ids
    );

    $db->replaceSelectionInto(
        'category_vendor_product_count',
        ['company_id', 'category_id', 'product_count'],
        $select_query,
        ['product_count']
    );
}

function fn_mve_export_process(&$pattern, &$export_fields, &$options, &$conditions, &$joins, &$table_fields, &$processes)
{
    if (Registry::get('runtime.company_id')) {
        if ($pattern['section'] == 'products') {
            // Limit scope to the current vendor's products only (if in vendor mode)
            $company_condition = fn_get_company_condition('products.company_id', false);
            if (!empty($company_condition)) {
                $conditions[] = $company_condition;
            }
        }

        if ($pattern['section'] == 'orders') {
            $company_condition = fn_get_company_condition('orders.company_id', false);

            if (!empty($company_condition)) {
                $conditions[] = $company_condition;
            }
        }

        if ($pattern['section'] == 'users') {
            $company_condition = fn_get_company_condition('orders.company_id', false);

            if (!empty($company_condition)) {
                $u_ids = db_get_fields('SELECT users.user_id FROM ?:users AS users LEFT JOIN ?:orders AS orders ON (users.user_id = orders.user_id) WHERE ' . $company_condition . ' GROUP BY users.user_id');

                if (!empty($u_ids)) {
                    $conditions[] = db_quote('users.user_id IN (?n)', $u_ids);
                }
            }
        }
    }
}

function fn_mve_get_users(&$params, &$fields, &$sortings, &$condition, &$join)
{
    if (isset($params['company_id']) && $params['company_id'] != '') {
        $condition['company_id'] = db_quote(' AND ?:users.company_id = ?i ', $params['company_id']);
    }

    if (Registry::get('runtime.company_id')) {
        if (empty($params['user_type'])) {
            $condition['users_company_id'] = db_quote(" AND (?:users.user_id IN (?n) OR (?:users.user_type != ?s AND" . fn_get_company_condition('?:users.company_id', false) . ")) ", fn_get_company_customers_ids(Registry::get('runtime.company_id')), 'C');
        } elseif (fn_check_user_type_admin_area ($params['user_type'])) {
            $condition['users_company_id'] = fn_get_company_condition('?:users.company_id');
        } elseif ($params['user_type'] == 'C') {
            $condition['users_company_id'] = db_quote(" AND ?:users.user_id IN (?n) ", fn_get_company_customers_ids(Registry::get('runtime.company_id')));
        }
    }
}

/**
 * Hook is used for changing query that selects primary object ID.
 *
 * @param array $pattern Array with import pattern data
 * @param array $_alt_keys Array with key=>value data of possible primary object (used for 'where' condition)
 * @param array $v Array with importing data (one row)
 * @param boolean $skip_get_primary_object_id Skip or not getting Primary object ID
 */
function fn_mve_import_get_primary_object_id(&$pattern, &$_alt_keys, &$v, &$skip_get_primary_object_id)
{
    if ($pattern['section'] !== 'products' || $pattern['pattern_id'] !== 'products') {
        return;
    }
    $company_name = empty($v['company']) ? '' : $v['company'];
    $company_id = fn_mve_get_vendor_id_for_product($company_name);
    if ($company_id === null) {
        $skip_get_primary_object_id = true;

        return;
    }

    $_alt_keys['company_id'] = $company_id;
}

/**
 * @param array $v                 Object data
 * @param array $primary_object_id Primary object id data
 * @param array $options           Import options
 * @param array $processed_data    Processed data
 * @param bool  $skip_record       Whether to skip record import
 *
 * @return bool
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_mve_import_check_product_data(&$v, $primary_object_id, &$options, &$processed_data, &$skip_record)
{
    if (empty($primary_object_id)) {
        if (!isset($v['product'])) {
            $v['product'] = '';
        }
    }

    if (Registry::get('runtime.company_id')) {
        $v['company_id'] = Registry::get('runtime.company_id');

        if (!empty($primary_object_id['product_id'])) {
            $v['product_id'] = $primary_object_id['product_id'];
            // Check the category name
            if (!array_key_exists('Category', $v)) {
                $v['Category'] = '';
            } elseif (
                !fn_mve_import_check_exist_category($v['Category'], $options['category_delimiter'], $v['lang_code'])
            ) {
                $v['Category'] = '';
            }
        } else {
            unset($v['product_id']);
            if (
                empty($v['Category'])
                || !fn_mve_import_check_exist_category($v['Category'], $options['category_delimiter'], $v['lang_code'])
            ) {
                $v['Category'] = '';
            }
        }

        if (!empty($v['Secondary categories']) && !$skip_record) {
            $delimiter = ';';
            $categories = explode($delimiter, $v['Secondary categories']);
            array_walk($categories, 'fn_trim_helper');

            foreach ($categories as $key => $category) {
                if (fn_mve_import_check_exist_category($category, $options['category_delimiter'], $v['lang_code'])) {
                    continue;
                }
                unset($categories[$key]);
            }

            $v['Secondary categories'] = implode($delimiter . ' ', $categories);
        }
    }

    return true;
}


/**
 * Check on exists import category in database
 *
 * @param array $category
 * @param string $delimiter
 * @param string $lang
 * @return bool
 */
function fn_mve_import_check_exist_category($category, $delimiter, $lang)
{
    if (empty($category)) {
        return false;
    }

    static $company_categories_ids = null;

    if ($company_categories_ids === null) {
        $company_categories_ids = Registry::get('runtime.company_data.category_ids');
    }

    if (strpos($category, $delimiter) !== false) {
        $paths = explode($delimiter, $category);
        array_walk($paths, 'fn_trim_helper');
    } else {
        $paths = array($category);
    }

    if (!empty($paths)) {
        $parent_id = 0;

        foreach ($paths as $name) {
            $sql = "SELECT ?:categories.category_id FROM ?:category_descriptions"
                . " INNER JOIN ?:categories ON ?:categories.category_id = ?:category_descriptions.category_id"
                . " WHERE ?:category_descriptions.category = ?s AND lang_code = ?s AND parent_id = ?i";

            $category_id = db_get_field($sql, $name, $lang, $parent_id);

            if (empty($category_id)) {
                return false;
            }

            $parent_id = $category_id;
        }

        if (!empty($company_category_ids) && !in_array($parent_id, $company_category_ids)) {
            return false;
        }

        return true;
    }

    return false;
}

function fn_mve_import_check_object_id(&$primary_object_id, &$processed_data, &$skip_record, $object = 'products')
{
    if (!empty($primary_object_id)) {
        $value = reset($primary_object_id);
        $field = key($primary_object_id);
        $company_id = db_get_field("SELECT company_id FROM ?:$object WHERE $field = ?s", $value);
        if ($company_id != Registry::get('runtime.company_id')) {
            $processed_data['S']++;
            $skip_record = true;
        }
    }

    /**
     * Additional actions for import
     *
     * @param array<string, string|int> $primary_object_id Primary object identifier
     * @param array<string, int>        $processed_data    Processed data
     * @param bool                      $skip_record       Skip record flag
     * @param string                    $object            Object type
     */
    fn_set_hook('mve_import_check_object_id', $primary_object_id, $processed_data, $skip_record, $object);

    return true;
}

function fn_import_reset_company_id($import_data)
{
    foreach ($import_data as $key => $data) {
        $import_data[$key]['company_id'] = Registry::get('runtime.company_id');
        unset($import_data[$key]['company']);
    }
}

function fn_mve_import_check_company_id(&$primary_object_id, &$v,  &$processed_data, &$skip_record)
{
    if (!empty($primary_object_id)) {
        $value = reset($primary_object_id);
        $field = key($primary_object_id);

        $company_id = db_get_field('SELECT company_id FROM ?:products WHERE ' . $field . ' = ?s', $value);
    } else {
        $company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $v['product_id']);
    }

    if ($company_id != Registry::get('runtime.company_id')) {
        $processed_data['S']++;
        $skip_record = true;

        return false;
    }

    return true;
}


function fn_mve_set_admin_notification(&$auth)
{
    if ($auth['company_id']) {
        return;
    }

    $count = db_get_field(
        'SELECT COUNT(*) FROM ?:companies WHERE status IN (?a)',
        [VendorStatuses::NEW_ACCOUNT, VendorStatuses::PENDING]
    );
    if (!$count) {
        return;
    }

    $event_dispatcher = Tygh::$app['event.dispatcher'];

    // FIXME: Clean up pending vendor notifications from all receivers
    db_query(
        'DELETE FROM ?:notifications WHERE ?w',
        [
            'tag'        => 'vendor_status',
            'area'       => 'A',
            'section'    => NotificationsCenter::SECTION_ADMINISTRATION,
            'severity'   => NotificationSeverity::WARNING,
            'is_read'    => 0,
            'action_url' => 'companies.manage?status[]=' . VendorStatuses::NEW_ACCOUNT . '&status[]=' . VendorStatuses::PENDING,
        ]
    );

    $event_dispatcher->dispatch('vendors_require_approval', []);
}

function fn_mve_get_companies(&$params, &$fields, &$sortings, &$condition, &$join, &$auth, &$lang_code)
{
    $fields[] = '?:companies.tax_number';
    if (!empty($params['get_description'])) {
        $fields[] = '?:company_descriptions.company_description';
        $join .= db_quote(' LEFT JOIN ?:company_descriptions ON ?:company_descriptions.company_id = ?:companies.company_id AND ?:company_descriptions.lang_code = ?s ', $lang_code);
    }
}

function fn_mve_delete_order(&$order_id)
{
    db_query('DELETE FROM ?:vendor_payouts WHERE order_id = ?i', $order_id);
    $parent_id = db_get_field("SELECT parent_order_id FROM ?:orders WHERE order_id = ?i", $order_id);
    if ($parent_id) {
        $count = db_get_field("SELECT COUNT(*) FROM ?:orders WHERE parent_order_id = ?i", $parent_id);
        if ($count == 1) { //this is the last child order, so we can delete the parent order.
            fn_delete_order($parent_id);
        }
    }
}

/**
 * @param string   $condition   Query condition; it is treated as a WHERE clause
 * @param int      $user_id     User identifier
 * @param string[] $user_fields Array of table column names to be returned
 *
 * @see \fn_get_user_info()
 */
function fn_mve_get_user_info_before(&$condition, &$user_id, array &$user_fields)
{
    if (!trim($condition)) {
        return;
    }

    if (Registry::get('runtime.company_id')) {
        $condition = "(user_type = 'V' {$condition})";
    }

    $customer_order_exists = db_get_fields(
        'SELECT 1 FROM ?:orders WHERE company_id = ?i AND user_id = ?i LIMIT 1',
        Registry::get('runtime.company_id'),
        $user_id
    );

    if ($customer_order_exists) {
        $condition = db_quote("((user_id = ?i AND user_type = ?s) OR {$condition})", $user_id, UserTypes::CUSTOMER);
    }

    $condition = " AND {$condition} ";
}

function fn_mve_get_product_options(&$fields, &$condition, &$join, &$extra_variant_fields, &$product_ids, &$lang_code)
{
    // FIXME 2tl show admin
    if (Registry::get('runtime.is_restoring_cart_from_backend') !== true) {
        $condition .= fn_get_company_condition('a.company_id', true, '', true);
    }
}

function fn_mve_get_product_global_options_before_select(&$params, &$fields, &$condition, &$join)
{
    // FIXME 2tl show admin
    $condition .= fn_get_company_condition('company_id', true, '', true);
}

function fn_mve_get_product_option_data_pre(&$option_id, &$product_id, &$fields, &$condition, &$join, &$extra_variant_fields, &$lang_code)
{
    // FIXME 2tl show admin
    $condition .= fn_get_company_condition('company_id', true, '', true);
}

function fn_mve_clone_page_pre(&$page_id, &$data)
{
    if (!fn_check_company_id('pages', 'page_id', $page_id)) {
        fn_company_access_denied_notification();
        unset($data);
    }
}

function fn_mve_update_page_post(&$page_data, &$page_id, &$lang_code, &$create, &$old_page_data)
{
    if (empty($page_data['page'])) {
        return false;
    }

    if (!$create) {
        //update page
        $page_childrens = db_get_fields("SELECT page_id FROM ?:pages WHERE id_path LIKE ?l AND parent_id != 0", '%' . $page_id . '%');

        if (!empty($page_childrens)) {
            //update childrens company if we update company for root page.
            if ($page_data['parent_id'] == 0 || $old_page_data['parent_id'] == 0) {
                fn_change_page_company($page_id, $page_data['company_id']);
            }
        }
    }
}

/**
 * The `get_shippings` hook handler.
 *
 * Action performed:
 *     - Adds fields in SELECT-query
 *
 * @param array<string> $fields Array of shipping fields to get
 *
 * @see \fn_get_shippings()
 *
 * @return void
 */
function fn_mve_get_shippings(array &$fields)
{
    $fields[] = 'a.available_for_new_vendors';
}

/* FUNCTIONS */

/**
 * Gets order statuses that will be used for vendor payouts.
 *
 * @return array Statuses
 *
 * @deprecated 4.5.1
 */
function fn_get_order_payout_statuses()
{
    return VendorPayouts::instance()->getPayoutOrderStatuses();
}

function fn_companies_add_payout($payment)
{
    $_data = array(
        'company_id' => $payment['vendor'],
        'payout_amount' => $payment['amount'],
        'comments' => $payment['comments'],
        'payout_type' => Registry::get('runtime.company_id') ? VendorPayoutTypes::WITHDRAWAL : VendorPayoutTypes::PAYOUT,
    );

    VendorPayouts::instance()->update($_data);

    if ($_data['payout_type'] == VendorPayoutTypes::WITHDRAWAL) {
        $mail_data = array(
            'to' => 'default_company_support_department',
            'from' => 'company_support_department',
            'template_code' => 'accounting_new_withdrawal',
            'tpl' => 'companies/accounting_new_withdrawal.tpl',  // this parameter is obsolete and is used for back compatibility
        );
        $accounting_url = str_replace(Registry::get('config.vendor_index'), Registry::get('config.admin_index'), fn_url('companies.balance'));
    } elseif (isset($payment['notify_user']) && $payment['notify_user'] == 'Y') {
        $mail_data = array(
            'to' => 'company_support_department',
            'from' => 'default_company_support_department',
            'template_code' => 'accounting_new_payout',
            'tpl' => 'companies/accounting_new_payout.tpl',  // this parameter is obsolete and is used for back compatibility
        );
        $accounting_url = fn_url('companies.balance', 'V');
    }

    if (isset($mail_data)) {
        /** @var \Tygh\Mailer\Mailer $mailer */
        $mailer = Tygh::$app['mailer'];
        /** @var \Tygh\Tools\Formatter $formatter */
        $formatter = Tygh::$app['formatter'];

        $payment_data = array(
            'vendor' => fn_get_company_name($payment['vendor']),
            'amount' => $formatter->asPrice($payment['amount']),
            'comments' => $payment['comments'],
            'initiator' => fn_get_user_name(Tygh::$app['session']['auth']['user_id'])
        );

        $mailer->send(array_merge($mail_data, array(
            'data' => array(
                'payment' => $payment_data,
                'accounting_url' => $accounting_url,
            ),
            'company_id' => $payment['vendor'],
        )), 'A', fn_get_company_language($payment['vendor']));
    }
}

/**
 * Gets user IDs from orders
 *
 * @param int $company_id Company ID
 *
 * @return string[] User IDs without casting to int
 */
function fn_get_company_customers_ids($company_id)
{
    return db_get_fields('SELECT DISTINCT(user_id) FROM ?:orders WHERE company_id = ?i AND user_id > 0', $company_id);
}

function fn_take_payment_surcharge_from_vendor($products = array())
{
    $take_surcharge_from_vendor = false;

    /**
     * Getting option 'take payment surcharge from vendor'
     *
     * @param array $products                   Products array
     * @param bool  $take_surcharge_from_vendor Take payment surcharge from vendor flag
     */
    fn_set_hook('take_payment_surcharge', $products, $take_surcharge_from_vendor);

    return $take_surcharge_from_vendor;
}

function fn_mve_update_page_before(&$page_data, &$page_id, &$lang_code)
{
    if (!empty($page_data['page'])) {
        fn_set_company_id($_data, 'company_id', true);
    }
}

function fn_mve_update_product($product_data, $product_id, $lang_code, $create)
{
    if (isset($product_data['company_id'])) {
        // Assign company_id to all product options
        $options_ids = db_get_fields('SELECT option_id FROM ?:product_options WHERE product_id = ?i', $product_id);
        if ($options_ids) {
            db_query("UPDATE ?:product_options SET company_id = ?s WHERE option_id IN (?n)", $product_data['company_id'], $options_ids);
        }
    }
}

/**
 * Changes the result of administrator access to profiles checking
 *
 * @param boolean $result    Result of check : true if administrator has access, false otherwise
 * @param string  $user_type Types of profiles
 *
 * @return void
 */
function fn_mve_check_permission_manage_profiles(&$result, $user_type)
{
    if (!$result) {
        return;
    }

    $params = array (
        'user_type' => $user_type
    );

    $can_manage_profiles = !fn_is_restricted_admin($params) || $user_type !== UserTypes::ADMIN;

    if ($can_manage_profiles && Registry::get('runtime.company_id')) {
        $can_manage_profiles = $user_type == 'V' && Registry::get('runtime.company_id');
    }

    $result = $can_manage_profiles;
}

/**
 * Changes defined user type
 *
 * @param string $user_type User type
 * @param array  $params    Request parameters
 * @param string $area      current application area
 *
 * @return bool Always true
 */
function fn_mve_get_request_user_type(&$user_type, &$params, &$area)
{
    if ($area == 'A' && empty($params['user_type']) && empty($params['user_id']) && Registry::get('runtime.company_id')) {
        $user_type = 'V';
    }

    return true;
}

function fn_mve_delete_shipping($shipping_id)
{
    db_query("UPDATE ?:companies SET shippings = ?p", fn_remove_from_set('shippings', $shipping_id));
}

/**
 * Applies shipping method to all vendors. Returns count of vendors.
 *
 * @param  int $shipping_id Shipping ID
 * @return int Count of vendors
 */
function fn_apply_shipping_to_vendors($shipping_id)
{
    $companies_count = db_get_field("SELECT COUNT(*) FROM ?:companies WHERE NOT FIND_IN_SET(?i, shippings)",
        $shipping_id
    );

    db_query("UPDATE ?:companies SET shippings = ?p", fn_add_to_set('shippings', $shipping_id));

    return $companies_count;
}

function fn_mve_get_products(&$params, &$fields, &$sortings, &$condition, &$join, &$sorting, &$group_by, $lang_code)
{
    // code for products filter by company (vendor)
    if (isset($params['company_id']) && $params['company_id'] != '') {
        $params['company_id'] = intval($params['company_id']);
        $condition .= db_quote(' AND products.company_id = ?i ', $params['company_id']);
    }
}

function fn_mve_logo_types(&$types, &$for_company)
{
    if ($for_company) {
        unset($types['favicon']);
        unset($types['theme']['for_layout']);
    } else {
        $types['vendor'] = [
            'for_layout' => true,
            'text'       => '',
            'image'      => 'vendor.png',
        ];
    }
}

function fn_get_products_companies($products)
{
    $companies = array();

    foreach ($products as $v) {
        $_company_id = !empty($v['company_id']) ? $v['company_id'] : 0;
        $companies[$_company_id] = $_company_id;
    }

    return $companies;
}

function fn_get_vendor_categories($params)
{
    $items = array();

    if (!empty($params['company_ids'])) {
        $items = fn_get_categories($params);
    }

    return $items;
}

function fn_mve_dropdown_object_link_post(&$object_data, &$object_type, &$result)
{
    static $vendor_id;

    if (empty($vendor_id)) {
        $vendor_id = Registry::get('runtime.vendor_id');
    }

    if ($object_type == 'vendor_categories') {
        $result = fn_url('companies.products?category_id=' . $object_data['category_id'] . '&company_id=' . $vendor_id);
    }
}

function fn_mve_settings_variants_image_verification_use_for(&$objects)
{
    $objects['apply_for_vendor_account'] = __('use_for_apply_for_vendor_account');
}

function fn_mve_get_predefined_statuses(&$type, &$statuses, &$status)
{
    if ($type == 'companies') {
        $statuses['companies'] = [
            VendorStatuses::ACTIVE => __('active'),
        ];
        if ($status === VendorStatuses::NEW_ACCOUNT) {
            $statuses['companies'][VendorStatuses::NEW_ACCOUNT] = __('new');
        }
        $statuses['companies'][VendorStatuses::PENDING] = __('pending');
        $statuses['companies'][VendorStatuses::SUSPENDED] = __('suspended');
        $statuses['companies'][VendorStatuses::DISABLED] = __('disabled');
    }
}

function fn_mve_get_company_data(&$company_id, &$lang_code, &$extra, &$fields, &$join, &$condition)
{
    // Vendor shouldn't see another vendor unless it's necessary
    if (!isset($extra['skip_company_condition']) || $extra['skip_company_condition'] != true) {
        $condition .= fn_get_company_condition('companies.company_id');
    }
}

/**
 * Modifies locations used for the vendor area and sets them the same values that are specified for the admin area.
 *
 * @param string $url       URN (Uniform Resource Name or Query String)
 * @param string $area      Area
 * @param string $protocol  Output URL protocol (protocol://). If equals 'rel', no protocol will be included
 * @param string $lang_code 2 letters language code
 * @param array  $locations Locations used for different protocols in store's area.
 */
function fn_mve_url_set_locations(&$url, &$area, &$protocol, &$lang_code, &$locations)
{
    if (AREA !== 'C') {
        /** @var \Tygh\Storefront\Repository $storefront_repository */
        $storefront_repository = Tygh::$app['storefront.repository'];
        $current_company_id = Registry::get('runtime.company_id');
        if ($current_company_id) {
            $storefront = $storefront_repository->findByCompanyId($current_company_id);
            if ($storefront) {
                $locations['C']['http'] = 'http://' . $storefront->url;
                $locations['C']['https'] = 'https://' . $storefront->url;
                $locations['C']['current'] = defined('HTTPS')
                    ? $locations['C']['https']
                    : $locations['C']['http'];
                $locations['C']['rel'] = $locations['C']['current'];
            }
        }
    }

    $locations['V'] = $locations['A'];
}

/**
 * Updates payout or withdrawal status and sends notification to vendor if required.
 *
 * @param int    $id            Payout ID
 * @param string $status        Status identifier (see Tygh\Enum\VendorPayoutApprovalStatuses)
 * @param bool   $notify_vendor If true, e-mail notification will be sent to the vendor
 */
function fn_companies_update_payout_status($id, $status, $notify_vendor = true)
{
    $payout_data = VendorPayouts::instance()->getSimple(array(
        'payout_id' => $id
    ));
    $payout_data = $payout_data ? reset($payout_data): array();

    if ($payout_data && $payout_data['approval_status'] != $status) {

        VendorPayouts::instance()->update(array(
            'approval_status' => $status
        ), $id);

        fn_set_notification('N', __('notice'), __('status_changed'));

        if (!$notify_vendor) {
            return;
        }

        $suffix = $status == VendorPayoutApprovalStatuses::COMPLETED ? 'approved' : 'declined';

        if ($payout_data['payout_type'] == VendorPayoutTypes::WITHDRAWAL) {
            $mail_data = array(
                'template_code' => "accounting_withdrawal_{$suffix}",
                'tpl' => "companies/accounting_withdrawal_{$suffix}.tpl",  // this parameter is obsolete and is used for back compatibility
            );
        } else {
            $mail_data = array(
                'template_code' => "accounting_payout_{$suffix}",
                'tpl' => "companies/accounting_payout_{$suffix}.tpl",  // this parameter is obsolete and is used for back compatibility
            );
        }

        /** @var \Tygh\Mailer\Mailer $mailer */
        $mailer = Tygh::$app['mailer'];
        /** @var \Tygh\Tools\Formatter $formatter */
        $formatter = Tygh::$app['formatter'];

        $payment_data = array(
            'vendor' => fn_get_company_name($payout_data['company_id']),
            'amount' => $formatter->asPrice($payout_data['payout_amount']),
            'date' => $formatter->asDatetime($payout_data['payout_date']),
        );

        $mailer->send(array_merge($mail_data, array(
            'to' => 'company_support_department',
            'from' => 'default_company_support_department',
            'data' => array(
                'payment' => $payment_data
            ),
            'company_id' => $payout_data['company_id'],
        )), 'A', fn_get_company_language($payout_data['company_id']));
    }
}

/**
 * Sets vendor ID in runtime based on requested object owner.
 *
 * @param array  $req                Request parameters
 * @param string $area               Site's area
 * @param bool   $is_allowed_url     Flag that determines if url is supported
 * @param string $controller         Controller to handle request
 * @param string $mode               Requested controller mode
 * @param string $action             Requested mode action
 * @param string $dispatch_extra     Additional dispatch data
 * @param array  $current_url_params Parameters to generate current url
 * @param string $current_url        Current url
 */
function fn_mve_get_route_runtime(&$req, &$area, &$result, &$is_allowed_url, &$controller, &$mode, &$action, &$dispatch_extra, &$current_url_params, &$current_url)
{
    if ($area == 'C' && $controller != '_no_page') {
        $vendor_id = fn_mve_get_vendor_id_by_request($req, $controller, $mode, $action, $dispatch_extra);

        Registry::set('runtime.vendor_id', $vendor_id);
    }
}

/**
 * Provides the list of dispatches that are owned by the vendor.
 *
 * @return array Owned dispatches
 */
function fn_get_vendor_dispatches()
{
    $owning_schema = fn_get_schema('vendors', 'dispatches');
    $owned_dispatches = array_filter($owning_schema, function($item) {
        return !empty($item['can_edit_blocks']);
    });

    return array_keys($owned_dispatches);

}

/**
 * Hook handler: expands styles list with the vendor ones.
 *
 * @param Styles $styles_instance Styles object
 * @param array  $style_files     style files list
 * @param array  $params          search params
 */
function fn_mve_styles_get_list(&$styles_instance, &$style_files, &$params)
{
    if ($vendor_id = fn_get_styles_owner()) {
        $vendor_styles_dir = $styles_instance->getStylesDir() . '/vendor/' . $vendor_id;
        $vendor_styles = array_keys($styles_instance->theme->getDirContents(array(
            'dir' => $vendor_styles_dir,
            'get_dirs' => false,
            'get_files' => true,
            'extension' => '.less',
        ), Themes::STR_EXCLUSIVE));

        if ($vendor_styles) {
            $style_files = array_unique(array_merge($style_files, $vendor_styles));
            sort($style_files);
        }
    }
}

/**
 * Hook handler: modifies the path to the vendor style file.
 *
 * @param Styles $styles_instance Styles object
 * @param string $path            current path
 * @param string $style_id        style ID
 * @param string $type            file type
 */
function fn_mve_styles_get_style_file(&$styles_instance, &$path, &$style_id, &$type)
{
    if ($vendor_id = fn_get_styles_owner()) {
        $vendor_styles_dir = fn_mve_get_vendor_style_path($path, $vendor_id, $style_id, $type);
        if (is_file($vendor_styles_dir)) {
            $path = dirname($vendor_styles_dir);
        }
    }
}

/**
 * Hook handler: replaces layout style with the vendor's one.
 *
 * @param array $params Request parameters
 * @param array $layout Layout data
 */
function fn_mve_init_layout(&$params, &$layout)
{
    if ($vendor_id = fn_get_styles_owner()) {
        $vendor_style_id = db_get_field('SELECT style_id FROM ?:vendor_styles WHERE company_id = ?i AND layout_id = ?i', $vendor_id, $layout['layout_id']);
        if (fn_string_not_empty($vendor_style_id)) {
            $layout['style_id'] = $vendor_style_id;
        }
    }
}

/**
 * Hook handler: modifies hash of the compiled styles file to store separate styles for vendors.
 *
 * @param array  $files          Array with style files
 * @param string $styles         Style code
 * @param string $prepend_prefix Prepend prefix
 * @param array  $params         Additional params
 * @param string $area           Site's area ('A' for admin, 'C' for customer)
 * @param array  $css_dirs       Directories to load style files from
 * @param string $hash           Hash part of the compiled styles file
 */
function fn_mve_merge_styles_file_hash(&$files, &$styles, &$prepend_prefix, &$params, &$area, &$css_dirs, &$hash)
{
    if ($vendor_id = fn_get_styles_owner()) {
        $hash .= '_' . $vendor_id;
    }
}


/**
 * Checks if theme customization enabled for the vendor and provides his/her ID.
 *
 * @return int|bool
 */
function fn_mve_get_vendor_id_from_customization_mode()
{
    if (!empty(Tygh::$app['session']['customization']['modes']['theme_editor']) && !empty(Tygh::$app['session']['auth']['company_id'])) {
        return Tygh::$app['session']['auth']['company_id'];
    }

    return false;
}

/**
 * Hook handler: replaces store logos with the default ones when loading logos for vendor styles.
 *
 * @param int                                           $company_id    Company ID
 * @param int                                           $layout_id     Layout ID
 * @param string                                        $style_id      Style ID
 * @param array<string, array<string, int|string|bool>> $logos         Selected logos
 * @param int|null                                      $storefront_id Storefront ID
 *
 * @param-out array<string, array> $logos
 */
function fn_mve_get_logos_post(&$company_id, &$layout_id, &$style_id, array &$logos, $storefront_id)
{
    if (
        !fn_get_styles_owner()
        || !$layout_id
        || $company_id
        || Registry::isExist('runtime.obtaining_vendor_logos')
    ) {
        return;
    }

    Registry::set('runtime.obtaining_vendor_logos', true);
    $layout_data = Layout::instance($company_id, [], $storefront_id)->get($layout_id);
    if ($layout_data) {
        $logos = fn_get_logos(0, $layout_id, $layout_data['style_id'], $storefront_id);
    }
    Registry::del('runtime.obtaining_vendor_logos');
}

/**
 * Provides overriden styles path for vendors.
 *
 * @param string $styles_path Path where styles are stored (see Tygh\Themes\Styles::getStylesPath())
 * @param int    $vendor_id   Vendor ID
 * @param string $style_id    File name of the style schema (like: "satori")
 * @param string $type        File type
 *
 * @return string Path
 */
function fn_mve_get_vendor_style_path($styles_path, $vendor_id, $style_id, $type = 'less')
{
    return sprintf('%s/vendor/%s/%s.%s', $styles_path, $vendor_id, $style_id, $type);
}

/**
 * Hook handler: modifies path where vendor's styles file is saved.
 *
 * @param \Tygh\Themes\Styles $styles_instance Styles instance
 * @param string              $style_id        File name of the style schema (like: "satori")
 * @param array               $style           Style data
 * @param string              $style_path      Path to save style to
 * @param string              $less            LESS content of the style
 */
function fn_mve_styles_update(&$styles_instance, &$style_id, &$style, &$style_path, &$less)
{
    if ($vendor_id = fn_get_styles_owner()) {
        $style_path = fn_mve_get_vendor_style_path($styles_instance->getStylesPath(), $vendor_id, $style_id, 'less');
    }
}

/**
 * Hook handler: modifies path where custom CSS of the vendor's styles file is saved.
 *
 * @param \Tygh\Themes\Styles $styles_instance Styles instance
 * @param string              $style_id        File name of the style schema (like: "satori")
 * @param string              $style_path      Path to save style to
 * @param string              $custom_css      Custom CSS content of the style
 */
function fn_mve_styles_add_custom_css(&$styles_instance, &$style_id, &$style_path, &$custom_css)
{
    if ($vendor_id = fn_get_styles_owner()) {
        $style_path = fn_mve_get_vendor_style_path($styles_instance->getStylesPath(), $vendor_id, $style_id, 'css');
    }
}

/**
 * Hook handler: modifies paths of style files when copying.
 *
 * @param \Tygh\Themes\Styles $styles_instance Styles instance
 * @param array               $from            Source style info: name, less path, css path
 * @param array               $to              Destination file info: name, less path, css path
 * @param bool                $clone_logos     Indicates if logos have to be cloned for the new style
 */
function fn_mve_styles_copy(&$styles_instance, &$from, &$to, &$clone_logos)
{
    if ($vendor_id = fn_get_styles_owner()) {

        $vendor_less = fn_mve_get_vendor_style_path($styles_instance->getStylesPath(), $vendor_id, $from['style'], 'less');
        if (file_exists($vendor_less)) {
            $from['less'] = $vendor_less;
        }
        $to['less'] = fn_mve_get_vendor_style_path($styles_instance->getStylesPath(), $vendor_id, $to['style'], 'less');

        $vendor_css = fn_mve_get_vendor_style_path($styles_instance->getStylesPath(), $vendor_id, $from['style'], 'css');
        if (file_exists($vendor_css)) {
            $from['css'] = $vendor_css;
        }
        $to['css'] = fn_mve_get_vendor_style_path($styles_instance->getStylesPath(), $vendor_id, $to['style'], 'css');

        $clone_logos = false;
    }
}

/**
 * Hook handler: replaces styles in layouts with the vendor ones.
 *
 * @param \Tygh\BlockManager\Layout $layout_instance Layout object
 * @param array                     $params          Search params
 * @param string                    $condition       Conditions part of SQL query
 * @param array                     $fields          Fields to select with SQL query
 * @param string                    $join            Join part of SQL condition
 */
function fn_mve_layout_get_list(&$layout_instance, &$params, &$condition, &$fields, &$join)
{
    $vendor_id = fn_get_styles_owner();

    if (empty($vendor_id)) {
        return;
    }

    $fields[] = db_quote(' IF(?:vendor_styles.company_id = ?i, ?:vendor_styles.style_id, ?:bm_layouts.style_id) AS style_id', $vendor_id);
    $join .= db_quote(' LEFT JOIN ?:vendor_styles ON ?:vendor_styles.layout_id = ?:bm_layouts.layout_id AND ?:vendor_styles.company_id = ?i', $vendor_id);
    $condition .= db_quote(' AND (?:vendor_styles.company_id = ?i OR ?:vendor_styles.company_id IS NULL)', $vendor_id);
}

/**
 * Hook handler: replaces style in default layout with the vendor one.
 *
 * @param \Tygh\BlockManager\Layout $layout_instance Layout object
 * @param string                    $theme_name      Theme name
 * @param string                    $condition       Conditions part of SQL query
 * @param array                     $fields          Fields to select with SQL query
 * @param string                    $join            Join part of SQL condition
 */
function fn_mve_layout_get_default(&$layout_instance, &$theme_name, &$condition, &$fields, &$join)
{
    $params = array();
    fn_mve_layout_get_list($layout_instance, $params, $condition, $fields, $join);
}

/**
 * Hook handler: stores selected vendor style in the database instead of replacing layout data.
 *
 * @param \Tygh\Themes\Styles $styles_instance   Styles instance
 * @param int                 $layout_id         Layout ID
 * @param string              $style_id          Style name
 * @param bool                $update_for_layout Whether update layout information
 * @param bool                $result            Return value
 */
function fn_mve_styles_set_style_pre(&$styles_instance, &$layout_id, &$style_id, &$update_for_layout, &$result)
{
    if ($vendor_id = fn_get_styles_owner()) {
        $result = db_query('REPLACE INTO ?:vendor_styles ?e', array(
            'company_id' => $vendor_id,
            'layout_id' => $layout_id,
            'style_id' => $style_id
        ));
        $update_for_layout = false;
    }
}

/**
 * Hook handler: prevents logos removal when deleting a style.
 *
 * @param \Tygh\Themes\Styles $styles_instance Styles instance
 * @param string              $style_id        Style name
 * @param bool                $delete_logos    Indicates if logos for the style have to be deleted
 */
function fn_mve_styles_delete_before_logos(&$styles_instance, &$style_id, &$delete_logos)
{
    if (fn_get_styles_owner()) {
        $delete_logos = false;
    }
}

/**
 * Hook handler: modifies path to patterns.
 *
 * @param \Tygh\Themes\Patterns $patterns_instance Patterns instance
 * @param string                $path              current path
 * @param string                $style_id          style to get path for
 */
function fn_mve_patterns_get_path(&$patterns_instance, &$path, &$style_id)
{
    if ($style_id && ($vendor_id = fn_get_styles_owner())) {
        /**
         * @var Styles $styles
         */
        $styles = Styles::factory(Themes::areaFactory('C')->getThemeName());

        if (is_file(fn_mve_get_vendor_style_path($styles->getStylesPath(), $vendor_id, $style_id))) {
            $path = rtrim($path, '/') . "/vendor/{$vendor_id}/";
        }
    }
}

/**
 * Hook handler: modifies path to patterns when saving patterns.
 *
 * @param \Tygh\Themes\Patterns $patterns_instance Patterns instance
 * @param string                $style_id          Style name
 * @param array                 $style             Style data
 * @param array                 $uploaded_data     Uploaded files
 * @param string                $path              Path where patterns will be saved
 * @param string                $rel_path          Relative patterns path
 */
function fn_mve_patterns_save(&$patterns_instance, &$style_id, &$style, &$uploaded_data, &$path, &$rel_path)
{
    if ($vendor_id = fn_get_styles_owner()) {
        $suffix = "/vendor/{$vendor_id}/{$style_id}";
        // do not rewrite already rewritten path
        if (substr($path, -strlen($suffix)) != $suffix) {
            $path = rtrim($patterns_instance->getPath(''), '/') . $suffix;
            $rel_path = rtrim($patterns_instance->getRelPath(''), '/') . $suffix . '/';
        }
    }
}

/**
 * Hook handler: modifies relative path to patterns dir.
 *
 * @param \Tygh\Themes\Patterns $patterns_instance Patterns instance
 * @param string                $path              Relative path to patterns dir
 * @param string                $style_id          Style name
 */
function fn_mve_patterns_get_rel_path(&$patterns_instance, &$path, &$style_id)
{
    fn_mve_patterns_get_path($patterns_instance, $path, $style_id);
}

/**
 * Hook handler: removes vendor styles and patterns.
 *
 * @param int  $company_id Vendor ID
 * @param bool $result     Removal result
 */
function fn_mve_delete_company(&$company_id, &$result)
{
    if ($result) {
        // list of themes where vendor has custom styles
        $themes_list = db_get_fields(
            'SELECT layouts.theme_name'
            . ' FROM ?:vendor_styles AS styles'
            . ' LEFT JOIN ?:bm_layouts AS layouts ON layouts.layout_id = styles.layout_id'
            . ' WHERE company_id = ?i',
            $company_id
        );

        foreach ($themes_list as $theme) {
            /**
             * @var \Tygh\Themes\Styles $styles_instance
             */
            $styles_instance = Styles::factory($theme);
            $patterns_instance = Patterns::instance(array('theme_name' => $theme));

            fn_rm(rtrim($styles_instance->getStylesPath(), '/') . '/vendor/' . $company_id);
            fn_rm(rtrim($patterns_instance->getPath(''), '/')   . '/vendor/' . $company_id);
        }

        db_query('DELETE FROM ?:vendor_styles WHERE company_id = ?i', $company_id);

        fn_delete_profile_fields_data(ProfileDataTypes::SELLER, $company_id);

        // Remove dismissed onboarding blocks data for deleted vendor
        db_query('DELETE FROM ?:custom_blocks_vendor_dismiss WHERE company_id = ?i', $company_id);
    }
}

/**
 * Hook handler: checks if vendor can remove a style.
 *
 * @param \Tygh\Themes\Styles $styles_instance Styles instance
 * @param array               $style           Style data
 * @param bool                $is_removable    Whether style can be removed
 */
function fn_mve_styles_is_removable_post(&$styles_instance, &$style, &$is_removable)
{
    if ($is_removable && ($vendor_id = fn_get_styles_owner())) {
        $is_removable = file_exists(
            fn_mve_get_vendor_style_path($styles_instance->getStylesPath(), $vendor_id, $style['style_id'], 'less')
        );
    }
}

/**
 * Hook handler: adds flag indicating if vendor can manage a location with the Theme editor.
 *
 * @param string $status     Controller response status
 * @param string $area       Currentry running application area
 * @param string $controller Executed controller
 * @param string $mode       Executed mode
 * @param string $action     Executed action
 */
function fn_mve_dispatch_before_send_response(&$status, &$area, &$controller, &$mode, &$action)
{
    if ($area == 'C' && defined('AJAX_REQUEST') && Registry::get('runtime.root_template') == 'index.tpl') {
        $is_customizable = empty(Tygh::$app['session']['auth']['company_id']) ||
            Registry::get('runtime.vendor_id') == Tygh::$app['session']['auth']['company_id'];
        Tygh::$app['ajax']->assign('is_theme_editor_allowed', $is_customizable);
    }
}

/**
 * Gets vendor ID by requested object.
 *
 * @param array  $request        Request parameters
 * @param string $controller     Dispatched controller
 * @param string $mode           Dispatched mode
 * @param string $action         Dispatched action
 * @param string $dispatch_extra Extra dispatch data
 *
 * @return int Vendor ID
 */
function fn_mve_get_vendor_id_by_request($request = array(), $controller = 'index', $mode = 'index', $action = '', $dispatch_extra = '')
{
    $vendor_id = 0;
    $owning_schema = array();

    $dispatches_to_checks = array(
        "{$controller}.{$mode}.{$action}.{$dispatch_extra}",
        "{$controller}.{$mode}.{$action}",
        "{$controller}.{$mode}"
    );

    $owned_dispatches = fn_get_schema('vendors', 'dispatches');

    foreach ($dispatches_to_checks as $dispatch) {
        if (isset($owned_dispatches[$dispatch])) {
            $owning_schema = $owned_dispatches[$dispatch];
            break;
        }
    }

    if ($owning_schema) {
        if (isset($owning_schema['callable'])) {
            [$func_name, $request_parameters] = $owning_schema['callable'];
            $args = array();
            foreach ($request_parameters as $param_name) {
                $args[] = isset($request[$param_name]) ? $request[$param_name] : null;
            }
            $vendor_id = call_user_func_array($func_name, $args);
        } elseif (isset($request[$owning_schema['request_param']])) {
            $vendor_id = db_get_field(
                "SELECT ?f FROM ?:?f WHERE ?p = ?s",
                $owning_schema['owner_field'],
                $owning_schema['table'],
                $owning_schema['table_field'],
                $request[$owning_schema['request_param']]
            );
        }
    }

    return (int)$vendor_id;
}

/**
 * Hook handler: provides style file URL to reload styles when using the Theme editor.
 *
 * @param \Tygh\Ajax $ajax_instance       Ajax instance
 * @param string     $text                HTML content of a page loaded via Ajax or empty string for Comet request
 * @param bool       $embedded_is_enabled Whether store is loaded in the Widget mode
 */
function fn_mve_ajax_destruct_before_response(&$ajax_instance, &$text, &$embedded_is_enabled)
{
    if ($ajax_instance->full_render && preg_match('/<link [^>]*href=[\'"]([^\'"]+standalone\.[^\'"]+\.css)[\'"]/', $text, $m)) {
        $ajax_instance->assign('style_file_url', $m[1]);
    }
}

/**
 * Hook handler: changes from message parameter admin address for vendor if setting is enabled
 *
 * @param \Tygh\Mailer\Mailer          $mailer    Mailer instance
 * @param array                        $message   Message params
 * @param string                       $area      Current working area (A-admin|C-customer)
 * @param string                       $lang_code Language code
 * @param \Tygh\Mailer\ITransport      $transport Instance of transport for send mail
 * @param \Tygh\Mailer\AMessageBuilder $builder   Message builder instance
 */
function fn_mve_mailer_create_message_before($mailer, &$message, $area, $lang_code, $transport, $builder)
{

    if (!empty($message['company_id']) && !empty($message['from'])
        && Registry::get('settings.Emails.mailer_send_from_admin') === 'Y'
    ) {
        $from = $builder->getMessageFrom($message['from'], 0, $lang_code);

        if (!empty($from)) {
            if (!isset($message['reply_to'])) {
                // move vendor real address to reply_to field
                $message['reply_to'] = $message['from'];
            }

            $from_vendor = $builder->getMessageFrom($message['from'], $message['company_id'], $lang_code);
            $message['from'] = array(
                'email' => key($from),
                'name' => !empty($from_vendor) ? reset($from_vendor) : '',
            );
        }
    }
}

/**
 * Hook handler: groups and reorders categories when editing a product.
 *
 * @param int   $product_id          Product ID
 * @param array $product_data        Edited product data
 * @param array $existing_categories Existing categories data
 * @param bool  $rebuild             Whether categories tree is changed
 * @param int   $company_id          Company ID
 */
function fn_mve_update_product_categories_post($product_id, $product_data, $existing_categories, &$rebuild, $company_id)
{
    if (empty($product_data['category_ids'])) {
        return;
    }

    $is_resorted = fn_sort_product_categories($product_id, $product_data['category_ids']);
    $rebuild = $rebuild || $is_resorted;
}
/*
 * Hook handler: saves company profile fields
 *
 * @param array  $company_data Company data
 * @param int    $company_id   Company ID
 * @param string $lang_code    Two-letter language code
 * @param string $action       Action
 */
function fn_mve_update_company($company_data, $company_id, $lang_code, $action)
{
    if (empty($company_id)) {
        return;
    }

    fn_store_profile_fields($company_data, $company_id, ProfileDataTypes::SELLER);

    if (!empty($company_data['invitation_key'])) {
        VendorServicesProvider::getInvitationsRepository()->deleteByKey($company_data['invitation_key']);
    } elseif (!empty($company_data['email'])) {
        VendorServicesProvider::getInvitationsRepository()->deleteByEmail(trim($company_data['email']));
    }
}

/**
 * Hook handler: fetches company profile data
 *
 * @param int    $company_id   Company ID
 * @param string $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
 * @param array  $extra        Array with extra parameters
 * @param array  $company_data Array with company data
 */
function fn_mve_get_company_data_post($company_id, $lang_code, $extra, &$company_data)
{
    if ($company_id && $company_data) {
        $company_data['fields'] = fn_get_profile_fields_data(ProfileDataTypes::SELLER, $company_id);
    }
}

/**
 * Extracts company's profile field values (e.g. array(53 => 'Alex') to array('firstname' => 'Alex'))
 *
 * @param array $profile_fields_data Company profile data
 *
 * @return array
 */
function fn_mve_extract_company_data_from_profile($profile_fields_data)
{
    $company_data = array(
        'admin_firstname' => '',
        'admin_lastname'  => '',
    );

    $params = array(
        'profile_type'     => ProfileTypes::CODE_SELLER,
        'skip_email_field' => false,
    );

    $profile_fields = fn_get_profile_fields('A', array(), CART_LANGUAGE, $params);

    foreach ($profile_fields as $section => $fields) {

        foreach ($fields as $id => $field) {

            if (isset($profile_fields_data[$id])) {
                $company_data[$field['field_name']] = $profile_fields_data[$id];
            }
        }
    }
    return $company_data;
}

/**
 * Transfers company's profiled field values to user profile field values based on matching fields names
 *
 * @param array $company_fields Entered company profile fields values
 *
 * @return array
 */
function fn_mve_profiles_match_company_and_user_fields($company_fields)
{
    if (!$company_fields) {
        return [];
    }

    $matched_fields = [];
    $params = [
        'profile_type'     => ProfileTypes::CODE_SELLER,
        'skip_email_field' => false,
    ];

    $company_profile_fields = fn_get_profile_fields('ALL', [], CART_LANGUAGE, $params);
    $company_profile_fields = call_user_func_array('array_replace', array_values($company_profile_fields));

    $params['profile_type'] = ProfileTypes::CODE_USER;
    $user_profile_fields = fn_get_profile_fields('ALL', [], CART_LANGUAGE, $params);
    $user_profile_fields = call_user_func_array('array_replace', array_values($user_profile_fields));

    foreach ($company_fields as $c_field_id => $c_field_value) {
        if (!isset($company_profile_fields[$c_field_id]['field_name'])) {
            continue;
        }

        $field_name = $company_profile_fields[$c_field_id]['field_name'];
        foreach ($user_profile_fields as $u_field_id => $u_field_data) {
            if ($u_field_data['field_name'] !== $field_name) {
                continue;
            }

            $matched_fields[$u_field_id] = $c_field_value;
            // no break because of duplicating field names for S and B sections
        }
    }

    return $matched_fields;
}

/**
 * Get company status for vendor, who logged in customer area
 *
 * @param array  $auth      Authentication data
 * @param array  $user_data User data are filled into auth
 * @param string $area      One-letter site area identifier
 */
function fn_mve_fill_auth(&$auth, $user_data, $area)
{
    if ($auth['user_type'] == 'V' && $area == 'C' && !empty($auth['company_id'])) {
        $auth['company_status'] = fn_get_company_data($auth['company_id'])['status'];
    }

    // phpcs:ignore
    if (UserTypes::isAdmin($auth['user_type'])) {
        $auth['storefront_id'] = empty($user_data['storefront_id']) ? 0 : $user_data['storefront_id'];
    }
}

/**
 * Obtains vendor's root admin or the first admin if root is not found.
 *
 * @param int $company_id Vendor ID
 *
 * @return int Admin ID
 */
function fn_get_company_admin_user_id($company_id)
{
    $query_template = 'SELECT user_id FROM ?:users'
        . ' WHERE ?w'
        . ' ORDER BY user_id ASC'
        . ' LIMIT 1';
    $search_params = array(
        'is_root'    => 'Y',
        'user_type'  => 'V',
        'company_id' => $company_id,
    );

    $admin_id = db_get_field($query_template, $search_params);

    if (!$admin_id) {
        unset($search_params['is_root']);

        $admin_id = db_get_field($query_template, $search_params);
    }

    return (int) $admin_id;
}

/**
 * The "delete_product_feature" hook handler.
 *
 * Actions performed:
 *  - checks if vendors product feature in use of vendor or someone else's products
 *
 * @see fn_delete_feature
 *
 * @param int    $feature_id   Feature ID
 * @param string $feature_type Feature type
 * @param bool   $can_delete   Permission to delete feature
 */
function fn_mve_delete_product_feature($feature_id, $feature_type, &$can_delete)
{
    $company_id = Registry::get('runtime.company_id');

    if ($company_id === 0) {
        return;
    }

    $features = db_get_field('SELECT feature_id FROM ?:product_features_values as product_features_values WHERE feature_id = ?i LIMIT 1', $feature_id);

    if (!empty($features)) {
        fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('feature_already_in_use'));
        $can_delete = false;
    }
}

/**
 * The "delete_product_feature" hook handler.
 *
 * Actions performed:
 *  - adds additional conditions to get product features
 *
 * @see fn_get_product_features
 *
 * @param array  $fields    Fields list
 * @param string $join      JOIN parameters
 * @param string $condition WHERE query condition
 * @param array  $params    Permission to delete feature
 */
function fn_mve_get_product_features(array $fields, $join, &$condition, array $params)
{
    if (
        empty($params['vendor_features_only'])
        || YesNo::isFalse($params['vendor_features_only'])
    ) {
        return;
    }

    $condition .= db_quote(' AND pf.company_id != ?i', 0);
}

/**
 * The "get_product_features_pre" hook handler.
 *
 * Actions performed:
 * - Hides features of another vendors from current vendor and his products.
 *
 * @param array<string, mixed> $params         Search parameters
 * @param int                  $items_per_page Features per page
 * @param string               $lang_code      Two-letter language code
 *
 * @psalm-param array{
 *   company_id: array<int>
 * } $params
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
function fn_mve_get_product_features_pre(array &$params, $items_per_page, $lang_code)
{
    $runtime_company_id = (int) Registry::get('runtime.company_id');

    if (!$runtime_company_id) {
        if (isset($params['product_company_id'])) {
            $params['company_id'] = [0, $params['product_company_id']];
        }

        return;
    }

    /** @var int $runtime_company_id */
    $params['company_id'] = [0, $runtime_company_id];
}

/**
 * The "delete_product_feature_variants_pre" hook handler.
 *
 * Actions performed:
 *  - checks if vendors product feature variants in use of someone else's products
 *
 * @see fn_delete_product_feature_variants
 *
 * @param int   $feature_id  Deleted feature identifier
 * @param array $variant_ids Deleted feature variants
 */
function fn_mve_delete_product_feature_variants_pre($feature_id, array &$variant_ids)
{
    $company_id = Registry::get('runtime.company_id');

    if ($company_id === 0) {
        return;
    }

    $variant_ids_in_use = db_get_fields('SELECT DISTINCT variant_id FROM ?:product_features_values WHERE variant_id IN (?n)', $variant_ids);

    $variant_ids_not_in_use = array_diff($variant_ids, $variant_ids_in_use);

    if (count($variant_ids) != count($variant_ids_not_in_use)) {
        fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('variants_already_in_use'));
        $variant_ids = $variant_ids_not_in_use;
    }
}

/**
 * The "prepare_bottom_panel_data" hook handler.
 *
 * Actions performed:
 *  - Gets vendor logos and ekey for login if vendor is authorized
 *
 * @param array $bottom_panel_data Bottom panel data for this user
 *
 * @see fn_prepare_bottom_panel_data()
 */
function fn_mve_prepare_bottom_panel_data(array &$bottom_panel_data)
{
    if (
        empty(Tygh::$app['session']['auth'])
        || empty(Tygh::$app['session']['auth']['user_type'])
        || (Tygh::$app['session']['auth']['user_type'] !== UserTypes::VENDOR)
    ) {
        return;
    }

    $company_id = isset(Tygh::$app['session']['auth']['company_id']) ? Tygh::$app['session']['auth']['company_id'] : null;
    $logo = fn_get_logos($company_id);

    $bottom_panel_data['logo'] = $logo ? $logo : [];
}

/**
 * Gets payment methods that vendor owns or can use if he owns none.
 *
 * @param array<string, int|string> $params Search parameters.
 *
 * @return array<string, string> List of suitable payment methods and information about them.
 */
function fn_get_vendor_payment_methods(array $params)
{
    if (empty($params['company_id'])) {
        return [];
    }

    $payments = fn_get_payments(['company_id' => $params['company_id']]);
    if (empty($payments)) {
        $params['company_id'] = 0;
    }
    return fn_get_payments($params);
}

/**
 * Returns runtime company identifier if define, or try to find it by company name otherwise
 *
 * @param string $company_name Company name for searching
 *
 * @return int|null Company identifier if company was found, null otherwise
 */
function fn_mve_get_vendor_id_for_product($company_name = '')
{
    if (Registry::get('runtime.company_id')) {
        return Registry::get('runtime.company_id');
    }

    if (!empty($company_name)) {
        return fn_get_company_id_by_name($company_name);
    }

    return 0;
}

/**
 * The "update_product_categories_pre" hook handler.
 * Actions performed:
 * - Prevents vendors from creating categories when editing a product.
 *
 * @param int                   $product_id   Product identifier
 * @param array<string, string> $product_data Product data
 * @param bool                  $rebuild      Whether to rebuild product categories tree
 * @param int                   $company_id   Company identifier
 */
function fn_mve_update_product_categories_pre($product_id, array &$product_data, $rebuild, $company_id)
{
    if (!fn_get_runtime_company_id()) {
        return;
    }

    unset($product_data['add_new_category']);
}

/**
 * The "user_init" hook handler.
 *
 * Actions performed:
 *  - Adds company status to user session information.
 *
 * @param array<string, string>     $auth       Current user session data.
 * @param array<string, int|string> $user_info  User information.
 * @param bool                      $first_init True if stored in session data used to log in the user.
 *
 * @param-out array<string, int|string> $user_info
 */
function fn_mve_user_init(array $auth, array &$user_info, $first_init)
{
    if (
        empty($user_info['company_id'])
        || $user_info['user_type'] !== UserTypes::VENDOR
        || !SiteArea::isStorefront(AREA)
    ) {
        return;
    }
    /** @var string $status */
    $status = fn_get_company_data((int) $user_info['company_id'])['status'];
    $user_info['company_status'] = $status;
}

/**
 * The "pre_get_orders" hook handler.
 *
 * Actions performed:
 *  - Adds storefront id to params
 *
 * @param array $params Orders search params.
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_mve_pre_get_orders(array &$params)
{
    fn_prepare_storefront_id($params, 'storefront_id');
}

/**
 * The "get_categories_pre" hook handler.
 *
 * Actions performed:
 *  - Adds storefront id to params
 *
 * @param array $params Category search params.
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_mve_get_categories_pre(array &$params)
{
    if (!fn_allowed_for('MULTIVENDOR:ULTIMATE') || empty(Tygh::$app['session']['auth'])) {
        return;
    }

    $auth = Tygh::$app['session']['auth'];

    // phpcs:ignore
    if (!empty($auth['storefront_id']) && SiteArea::isAdmin(AREA)) {
        $params['storefront_ids'] = [$auth['storefront_id'], 0];
    }
}

/**
 * The "get_users_pre" hook handler.
 *
 * Actions performed:
 *  - Adds storefront id to params
 *
 * @param array $params User search params.
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_mve_get_users_pre(array &$params)
{
    fn_prepare_storefront_id($params, 'storefront_id');
}


/**
 * The "storefront_repository_find_pre" hook handler.
 *
 * Actions performed:
 *  - Change params before find storefront.
 *
 * @param array $params Storefront search params.
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_mve_storefront_repository_find_pre(array &$params)
{
    if (!fn_allowed_for('MULTIVENDOR:ULTIMATE') || !Tygh::$app->hasInstance('session') || empty(Tygh::$app['session']['auth'])) {
        return;
    }

    $auth = Tygh::$app['session']['auth'];

    // phpcs:ignore
    if (!empty($auth['storefront_id']) && SiteArea::isAdmin(AREA)) {
        $params['is_default'] = null;
        $params['storefront_id'] = null;
    }
}

/**
 * The "storefront_repository_find" hook handler.
 *
 * Actions performed:
 *  - Modify SQL query parts.
 *
 * @param array    $params         Search parameters
 * @param int      $items_per_page Amount of items per page
 * @param string[] $fields         Fields to fetch
 * @param string[] $join           JOIN parts of the query
 * @param string[] $conditions     WHERE parts of the query
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_mve_storefront_repository_find(array $params, $items_per_page, array $fields, array $join, array &$conditions)
{
    if (!fn_allowed_for('MULTIVENDOR:ULTIMATE') || !Tygh::$app->hasInstance('session') || empty(Tygh::$app['session']['auth'])) {
        return;
    }

    $auth = Tygh::$app['session']['auth'];

    // phpcs:ignore
    if (!empty($auth['storefront_id']) && SiteArea::isAdmin(AREA)) {
        /** @var \Tygh\Storefront\Normalizer $normalizer */
        $normalizer = Tygh::$app['storefront.normalizer'];

        $conditions['storefront_id'] = db_quote(' AND storefronts.storefront_id IN (?n)', $normalizer->getEnumeration($auth['storefront_id']));
    }
}

/**
 * The "get_payments_pre" hook handler.
 *
 * Actions performed:
 *  - Adds storefront id to params
 *
 * @param array $params Payment search params.
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_mve_get_payments_pre(array &$params)
{
    fn_prepare_storefront_id($params, 'storefront_id');
}

/**
 * The "get_carts_pre" hook handler.
 *
 * Actions performed:
 *  - Adds storefront id to params
 *
 * @param array $params Carts search params.
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_mve_get_carts_pre(array &$params)
{
    fn_prepare_storefront_id($params, 'storefront_id');
}

/**
 * Adds storefront id to params.
 *
 * @param array  $params Search params.
 * @param string $field  Specifies in which field to substitute the storefront id.
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_prepare_storefront_id(array &$params, $field)
{
    if (!fn_allowed_for('MULTIVENDOR:ULTIMATE') || empty(Tygh::$app['session']['auth'])) {
        return;
    }

    $auth = Tygh::$app['session']['auth'];

    // phpcs:ignore
    if (!empty($auth['storefront_id']) && SiteArea::isAdmin(AREA)) {
        $params[$field] = $auth['storefront_id'];
    }
}

/**
 * The "get_companies_list" hook handler.
 *
 * Actions performed:
 *  - Adds company ids to params
 *
 * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR).
 * @param string $pattern   Search pattern
 * @param int    $start     String containing the SQL-query start for LIMIT field.
 * @param int    $limit     String containing the SQL-query LIMIT field.
 * @param array  $params    Company search params.
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_mve_get_companies_list($condition, $pattern, $start, $limit, array &$params)
{
    if (!fn_allowed_for('MULTIVENDOR:ULTIMATE') || empty(Tygh::$app['session']['auth'])) {
        return;
    }

    $auth = Tygh::$app['session']['auth'];

    // phpcs:ignore
    if (!empty($auth['storefront_id']) && SiteArea::isAdmin(AREA)) {
        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];

        $storefront = $repository->findById($auth['storefront_id']);
        if ($storefront) {
            $params['ids'] = $storefront->getCompanyIds();
        }
    }
}

/**
 * The "get_products_pre" hook handler.
 *
 * Actions performed:
 *  - Update search params
 *
 * @param array $params Product search params
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_mve_get_products_pre(array &$params)
{
    if (!fn_allowed_for('MULTIVENDOR:ULTIMATE') || empty(Tygh::$app['session']['auth'])) {
        return;
    }

    $auth = Tygh::$app['session']['auth'];

    // phpcs:ignore
    if (!empty($auth['storefront_id']) && SiteArea::isAdmin(AREA)) {
        $params['for_current_storefront'] = true;
    }
}

// phpcs:disable
/**
 * @param array                   $params Params
 * @param \Tygh\SmartyEngine\Core $smarty Smarty object
 *
 * @return void
 */
function fn_mve_smarty_eval_string_pre($params, &$smarty)
{
    $security = new SmartySecurity($smarty);
    $smarty->enableSecurity($security);
}
// phpcs:enable
