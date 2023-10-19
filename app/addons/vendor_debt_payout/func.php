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

use Tygh\Enum\Addons\VendorDebtPayout\CategoryTypes;
use Tygh\Enum\Addons\VendorDebtPayout\ProductTypes;
use Tygh\Enum\Addons\VendorDebtPayout\VendorDebtStatuses;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ProductZeroPriceActions;
use Tygh\Enum\ReceiverSearchMethods;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Enum\VendorPayoutApprovalStatuses;
use Tygh\Enum\VendorPayoutTypes;
use Tygh\Enum\VendorStatuses;
use Tygh\Enum\YesNo;
use Tygh\Models\Company;
use Tygh\Models\VendorPlan;
use Tygh\Notifications\Receivers\SearchCondition;
use Tygh\Registry;
use Tygh\SmartyEngine\Core as SmartyCore;
use Tygh\Tools\Url;
use Tygh\VendorPayouts;

/**
 * Adds add-on data upon its installation.
 */
function fn_vendor_debt_payout_install()
{
    // prevent other add-ons from interrupting the category and the product creation
    fn_define('DISABLE_HOOK_CACHE', true);

    // indicate that add-on is being installed
    fn_define('VENDOR_DEBT_PAYOUT_INSTALL', true);

    $current_hooks = Registry::get('hooks');
    Registry::set('hooks', [], true);

    $category_id = fn_update_category(
        [
            'category'             => __('vendor_debt_payout.debt_payout'),
            'parent_id'            => 0,
            'description'          => '',
            'status'               => 'H',
            'page_title'           => '',
            'meta_description'     => '',
            'meta_keywords'        => '',
            'usergroup_ids'        => 0,
            'position'             => '',
            'product_details_view' => 'default',
            'use_custom_templates' => 'N',
            'category_type'        => CategoryTypes::DEBT_PAYOUT,
        ]
    );

    // FIXME: find a better way to attach product image
    $_REQUEST['product_main_image_data'] = [
        [
            'detailed_alt' => '',
            'type'         => 'M',
            'object_id'    => 0,
            'position'     => 0,

        ],
    ];

    $current_allow_external_uploads = Registry::ifGet('runtime.allow_upload_external_paths', false);
    Registry::set('runtime.allow_upload_external_paths', true, true);

    $_REQUEST['file_product_main_image_detailed'] = [
        fn_get_theme_path('[themes]/[theme]/media/images/addons/vendor_debt_payout/product_image.png'),
    ];
    $_REQUEST['type_product_main_image_detailed'] = [
        'server',
    ];

    $product_id = fn_update_product(
        [
            'product'              => __('vendor_debt_payout.debt_payout'),
            'company_id'           => 0,
            'category_ids'         => [$category_id],
            'main_category'        => $category_id,
            'price'                => 0,
            'full_description'     => '',
            'status'               => 'H',
            'options_type'         => '',
            'exceptions_type'      => '',
            'product_code'         => '',
            'list_price'           => 0,
            'amount'               => '1',
            'zero_price_action'    => '',
            'tracking'             => '',
            'min_qty'              => null,
            'max_qty'              => null,
            'qty_step'             => null,
            'list_qty_count'       => null,
            'tax_ids'              => '',
            'usergroup_ids'        => 0,
            'avail_since'          => '',
            'out_of_stock_actions' => 'N',
            'details_layout'       => 'default',
            'short_description'    => '',
            'search_words'         => '',
            'promo_text'           => '',
            'page_title'           => '',
            'meta_description'     => '',
            'meta_keywords'        => '',
            'weight'               => 0,
            'free_shipping'        => 'Y',
            'shipping_freight'     => 0,
            'min_items_in_box'     => 0,
            'max_items_in_box'     => 0,
            'prices'               => [
                1 => [
                    'lower_limit'  => '',
                    'price'        => 0,
                    'type'         => 'A',
                    'usergroup_id' => 0,
                ],
            ],
            'product_features'     => [],
            'product_type'         => ProductTypes::DEBT_PAYOUT,
            'is_edp'               => 'Y',
        ]
    );

    fn_update_notification_receiver_search_conditions(
        'group',
        'vendor_debt_payout',
        UserTypes::VENDOR,
        [
            new SearchCondition(ReceiverSearchMethods::VENDOR_OWNER, ReceiverSearchMethods::VENDOR_OWNER),
        ]
    );

    list($root_admins,) = fn_get_users([
        'is_root' => YesNo::YES,
        'user_type' => UserTypes::ADMIN,
        'company_id' => 0,
    ], Tygh::$app['session']['auth']);

    $root_admin = reset($root_admins);

    fn_update_notification_receiver_search_conditions(
        'group',
        'vendor_debt_payout',
        UserTypes::ADMIN,
        [
            new SearchCondition(ReceiverSearchMethods::USER_ID, $root_admin['user_id']),
        ]
    );

    /** @var Tygh\Models\Company[] $vendors_without_vendor_plan */
    $vendors_without_vendor_plan = Company::model()->findMany(['plan_id' => [0]]);

    if (!empty($vendors_without_vendor_plan)) {
        $default_plan = new VendorPlan();
        $default_plan->attributes([
            'plan'                           => __('vendor_plans.default_vendor_plan'),
            'status'                         => ObjectStatuses::HIDDEN,
            'lowers_allowed_balance'         => null,
            'grace_period_to_refill_balance' => null,
            'vendor_store'                   => 1
        ]);
        $plan_saved = $default_plan->save();

        if ($plan_saved) {
            foreach ($vendors_without_vendor_plan as $vendor) {
                $vendor->attributes(['plan_id' => $default_plan->plan_id]);
                $vendor->save();
            }
        }
    }

    Registry::set('runtime.allow_upload_external_paths', $current_allow_external_uploads, true);
    Registry::set('hooks', $current_hooks, true);
}

/**
 * Uninstalls add-on data upon its removal.
 */
function fn_vendor_debt_payout_uninstall()
{
    // indicate that add-on is being uninstalled
    fn_define('VENDOR_DEBT_PAYOUT_UNINSTALL', true);

    $product_id = fn_vendor_debt_payout_get_payout_product();

    fn_delete_product($product_id);

    $category_id = fn_vendor_debt_payout_get_payout_category();

    fn_delete_category($category_id);
}

/**
 * Obtains ID of the category where the debt payout product is stored.
 *
 * @return int Category ID
 */
function fn_vendor_debt_payout_get_payout_category()
{
    static $payout_category_id = null;
    if ($payout_category_id !== null) {
        return $payout_category_id;
    }

    return $payout_category_id = (int) db_get_field(
        'SELECT category_id'
        . ' FROM ?:categories'
        . ' WHERE category_type = ?s',
        CategoryTypes::DEBT_PAYOUT
    );
}

/**
 * Obtains ID of the debt payout product.
 *
 * @return int Product ID
 */
function fn_vendor_debt_payout_get_payout_product()
{
    static $payout_product_id = null;
    if ($payout_product_id !== null) {
        return $payout_product_id;
    }

    return $payout_product_id = (int) db_get_field(
        'SELECT product_id'
        . ' FROM ?:products'
        . ' WHERE product_type = ?s',
        ProductTypes::DEBT_PAYOUT
    );
}

/**
 * Obtains vendor's root admin or the first admin if root is not found.
 *
 * @param int $vendor_id Vendor ID
 *
 * @return int Admin ID
 */
function fn_vendor_debt_payout_get_vendor_admin($vendor_id)
{
    return fn_get_company_admin_user_id($vendor_id);
}

/**
 * Provides "Pay the debt" URL.
 *
 * @param int   $vendor_id Company ID of the vendor
 * @param array $auth      Authentication data from the session
 * @param float $amount    Amount to refill balance
 *
 * @return string URL
 */
function fn_vendor_debt_payout_get_pay_url($vendor_id, array $auth, $amount = 0.0)
{
    if ($auth['user_type'] === UserTypes::ADMIN) {
        $user_id = fn_vendor_debt_payout_get_vendor_admin($vendor_id);
    } else {
        $user_id = $auth['user_id'];
    }

    $pay_url_params = [];
    if (!empty($amount)) {
        $pay_url_params['currency'] = CART_SECONDARY_CURRENCY;
        $pay_url_params['refill_amount'] = $amount;
    }

    $pay_debt_url = Url::buildUrn(
        ['debt', 'pay'],
        $pay_url_params
    );

    $pay_debt_url = Url::buildUrn(['profiles', 'act_as_user'], [
        'area'         => 'C',
        'user_id'      => $user_id,
        'redirect_url' => $pay_debt_url,
    ]);

    return fn_url($pay_debt_url);
}

/**
 * The "get_products" hook handler.
 *
 * Actions performed:
 *     - Removes payouts from the list of products
 *
 * @param array<string, string> $params    Array of get companies params
 * @param array<int, string>    $fields    Array of fields to get for products
 * @param array                 $sortings  Sorting fields
 * @param string                $condition Condition for selecting products
 *
 * @see \fn_get_products()
 */
function fn_vendor_debt_payout_get_products(array $params, array $fields, array $sortings, &$condition)
{
    if (empty($params['pid'])) {
        $condition .= db_quote(' AND products.product_type != ?s', ProductTypes::DEBT_PAYOUT);
    }
}

/**
 * The "get_products_pre" hook handler.
 *
 * Actions performed:
 *     - Adds suspended company status to company condition
 *
 * @param array<string, string> $params Array of get companies params
 *
 * @see \fn_get_products()
 */
function fn_vendor_debt_payout_get_products_before_select(array &$params)
{
    if (
        YesNo::toBool(Registry::get('addons.vendor_debt_payout.hide_products'))
        || !SiteArea::isStorefront($params['area'])
    ) {
        return;
    }

    $params['company_status'] = [VendorStatuses::ACTIVE, VendorStatuses::SUSPENDED];
}

/**
 * Hook handler: removes payouts from the list of categories.
 */
function fn_vendor_debt_payout_get_categories_pre(&$params, &$lang_code)
{
    if (empty($params['except_id'])) {
        $params['except_id'] = fn_vendor_debt_payout_get_payout_category();
    }
}

/**
 * The "change_order_status" hook handler.
 *
 * Actions performed:
 *     - Creates compensating vendor payout when the status of the debt payout order is changed.
 *
 * @param string $status_to   Status to letter
 * @param string $status_from Status from letter
 * @param array  $order_info  Array of the order data
 *
 * @throws \Tygh\Exceptions\DeveloperException When notification event for receiver and transport was not found.
 *
 * @see \fn_change_order_status()
 */
function fn_vendor_debt_payout_change_order_status($status_to, $status_from, array $order_info)
{
    if (
        !in_array($status_to, fn_get_settled_order_statuses())
        || !empty($order_info['is_debt_paid'])
    ) {
        return;
    }

    foreach ($order_info['products'] as $product) {
        if (!isset($product['extra']['vendor_debt_payout'])) {
            continue;
        }

        $vendor_id = $product['extra']['vendor_debt_payout']['vendor_id'];
        $payouts_manager = VendorPayouts::instance(['vendor' => $vendor_id]);

        $payouts_to_approve = $payouts_manager->getSimple([
            'payout_type'     => [VendorPayoutTypes::PAYOUT],
            'approval_status' => VendorPayoutApprovalStatuses::PENDING,
            'sort_by'         => 'sort_period',
            'sort_order'      => 'desc',
        ]);
        $payout_debt = abs(array_sum(array_column($payouts_to_approve, 'payout_amount')));

        if ($payout_debt) {
            list($balance,) = $payouts_manager->getBalance();
            $payout_debt = $balance >= 0 ? $payout_debt : abs($balance);
        }
        if ($payout_debt <= $order_info['subtotal']) {
            foreach ($payouts_to_approve as $payout) {
                $payouts_manager->update([
                    'approval_status' => VendorPayoutApprovalStatuses::COMPLETED,
                ], $payout['payout_id']);
            }
        }

        $payouts_manager->update([
            'payout_type'     => VendorPayoutTypes::PAYOUT,
            'payout_amount'   => -$order_info['subtotal'],
            'comments'        => __('vendor_debt_payout.debt_payout_w_order', ['[id]' => $order_info['order_id']]),
            'approval_status' => VendorPayoutApprovalStatuses::COMPLETED,
        ]);

        if (!empty($order_info['payment_surcharge'])) {
            VendorPayouts::instance(['vendor' => 0])->update([
                'payout_type'     => VendorPayoutTypes::PAYOUT,
                'payout_amount'   => $order_info['payment_surcharge'],
                'comments'        => __('vendor_debt_payout.payment_surcharge_w_order', ['[id]' => $order_info['order_id']]),
                'approval_status' => VendorPayoutApprovalStatuses::COMPLETED,
            ]);
        }

        // mark order as paid-off
        db_replace_into('order_data', [
            'order_id' => $order_info['order_id'],
            'type'     => 'D',
            'data'     => serialize(true),
        ]);

        fn_vendor_debt_payout_check_vendor_debt($vendor_id);
    }
}

/**
 * Checks whether vendor's negative balance is lower than max allowed vendor debt.
 *
 * @param \Tygh\VendorPayouts $payouts_manager Pre-configured payouts manager
 *
 * @return array{bool|null, float, float|null}
 *      Array with the following values:
 *          - Whether debt limit is exceeded
 *          - Vendor balance
 *          - Minimal allowed balance
 */
function fn_vendor_debt_payout_is_debt_limit_exceeded(VendorPayouts $payouts_manager)
{
    $is_debt_limit_exceeded = false;

    /** @var float $balance */
    list($balance,) = $payouts_manager->getBalance();
    $vendor_id = $payouts_manager->getVendor();

    $plan = fn_vendor_plans_get_vendor_plan_by_company_id($vendor_id);

    if (empty($plan)) {
        return [null, $balance, null];
    }

    $plan_data = $plan->attributes();

    $debt_limit = $plan_data['lowers_allowed_balance'];
    if (!is_numeric($debt_limit)) {
        return [$is_debt_limit_exceeded, $balance, null];
    }

    $minimal_balance = (float) $debt_limit;

    if ($balance < $minimal_balance) {
        $is_debt_limit_exceeded = true;
    }

    return [$is_debt_limit_exceeded, $balance, $minimal_balance];
}

/**
 * Checks whether vendor has overdue payouts.
 *
 * @param \Tygh\VendorPayouts $payouts_manager Configured payouts manager
 *
 * @return array
 */
function fn_vendor_debt_payout_has_overdue_payouts(VendorPayouts $payouts_manager)
{
    $pending_payouts = fn_vendor_debt_payout_get_pending_vendor_plan_payouts($payouts_manager);

    $overdue_limit = Registry::get('addons.vendor_debt_payout.payout_overdue_limit');
    if (!is_numeric($overdue_limit)) {
        return [false, null, null, $pending_payouts];
    }

    foreach ($pending_payouts as $payout_data) {
        $overdue = (int) ceil((TIME - $payout_data['payout_date']) / SECONDS_IN_DAY);
        if ($overdue > $overdue_limit) {
            return [true, $overdue, $overdue_limit, $pending_payouts];
        }
    }

    return [false, null, $overdue_limit, $pending_payouts];
}

/**
 * Checks whether vendor can access specified dispatch when his/her debt exceeds allowed limits.
 *
 * @param string $controller Dispatch controller
 * @param string $mode       Dispatch mode
 * @param array  $schema     Permission schema
 *
 * @return bool
 */
function fn_vendor_debt_payout_is_dispatch_allowed_for_blocked_vendor($controller, $mode, array $schema)
{
    if (isset($schema[$controller]['modes'][$mode]['permissions_blocked'])) {
        return $schema[$controller]['modes'][$mode]['permissions_blocked'];
    }

    if (isset($schema[$controller]['permissions_blocked'])) {
        return $schema[$controller]['permissions_blocked'];
    }

    return false;
}

/**
 * Notifies vendor about ongoing block if his/her debt exceeds half of the allowed debt limit.
 *
 * @param int   $vendor_id Company ID of the vendor
 * @param array $auth      User authentication data
 *
 * @return array
 */
function fn_vendor_debt_payout_get_vendor_debt_notifications($vendor_id, array $auth)
{
    $vendor_id = $vendor_id ?: $auth['company_id'];
    if (!$vendor_id) {
        return array();
    }

    $notifications = array();

    $payouts_manager = VendorPayouts::instance(array('vendor' => $vendor_id));
    list(, $balance, $minimal_balance) = fn_vendor_debt_payout_is_debt_limit_exceeded($payouts_manager);
    if ($balance < 0 && $minimal_balance !== null) {
        $notify_threshold = Registry::ifGet('addons.vendor_debt_payout.vendor_debt_limit_notify_threshold', 50) / 100;
        if ($balance > $minimal_balance && $balance < $minimal_balance * $notify_threshold) {

            /** @var \Tygh\Tools\Formatter $formatter */
            $formatter = Tygh::$app['formatter'];

            $textual_replacements = array(
                '[current_balance]' => $formatter->asPrice($balance),
                '[minimal_balance]' => $formatter->asPrice($minimal_balance),
                '[pay_url]'         => fn_vendor_debt_payout_get_pay_url($vendor_id, $auth),
            );

            $notifications['debt_near_limit'] = array(
                'type'   => NotificationSeverity::WARNING,
                'title'  => 'warning',
                'text'   => 'vendor_debt_payout.debt_near_limit_message',
                'params' => $textual_replacements,
                'state'  => 'S',
            );
        }
    }

    return $notifications;
}

/**
 * Obtains pending payouts for a vendor.
 *
 * @param \Tygh\VendorPayouts $payouts_manager Pre-configured payouts manager
 *
 * @return array
 */
function fn_vendor_debt_payout_get_pending_payouts(VendorPayouts $payouts_manager)
{
    return $payouts_manager->getSimple([
        'payout_type'     => [VendorPayoutTypes::PAYOUT],
        'approval_status' => VendorPayoutApprovalStatuses::PENDING,
        'sort_by'         => 'sort_period',
        'sort_order'      => 'desc',
    ]);
}

/**
 * Obtains pending vendor plan payouts for a vendor.
 *
 * @param \Tygh\VendorPayouts $payouts_manager Pre-configured payouts manager
 *
 * @return array
 */
function fn_vendor_debt_payout_get_pending_vendor_plan_payouts(VendorPayouts $payouts_manager)
{
    $pending_payouts = fn_vendor_debt_payout_get_pending_payouts($payouts_manager);

    $pending_payouts = array_filter($pending_payouts, function ($payout) {
        return !empty($payout['plan_id']);
    });

    return $pending_payouts;
}

/**
 * Gets debt data of the vendor
 *
 * @param int $vendor_id Vendor identifier
 *
 * @return array{status: string, suspend_date: int, grace_period_start: int, debt_status: string}
 */
function fn_vendor_debt_payout_get_vendor_debt_data($vendor_id)
{
    /** @var array{status: string, suspend_date: int, grace_period_start: int} $vendor_debt_data */
    $vendor_debt_data = db_get_row(
        'SELECT status, suspend_date, grace_period_start'
        . ' FROM ?:companies'
        . ' WHERE company_id = ?i',
        $vendor_id
    );

    $vendor_debt_data['debt_status'] = fn_vendor_debt_payout_get_vendor_debt_status($vendor_debt_data);

    return $vendor_debt_data;
}

/**
 * Hook handler: prevents vendor access to everything if his/her debt exceeds maximum allowed value.
 *
 * @param bool   $permission        Whether the action is allowed
 * @param string $controller        Dispatch controller
 * @param string $mode              Dispatch mode
 * @param string $request_method    Request method ('GET', 'POST')
 * @param array  $request_variables Request parameters
 * @param string $extra             (Not used) Legacy paramter
 * @param array  $schema            Permission schema
 */
function fn_vendor_debt_payout_check_company_permissions(
    &$permission,
    &$controller,
    &$mode,
    &$request_method,
    &$request_variables,
    &$extra,
    &$schema
) {
    static $vendor_id;
    static $vendor_data;

    if ($vendor_id === null) {
        $vendor_id = Registry::get('runtime.company_id');
    }

    if ($vendor_data === null) {
        $vendor_data = fn_get_company_data($vendor_id);
    }

    if (
        !isset($vendor_data['status'])
        || $vendor_data['status'] !== VendorStatuses::SUSPENDED
        || !YesNo::toBool(Registry::get('addons.vendor_debt_payout.block_admin_panel'))
        || !$permission
    ) {
        return;
    }

    $permission = fn_vendor_debt_payout_is_dispatch_allowed_for_blocked_vendor($controller, $mode, $schema);
}

/**
 * Hook handler: prevents fees product removal.
 *
 * @param int  $product_id Product ID
 * @param bool $status     Whether removal is allowed
 */
function fn_vendor_debt_payout_delete_product_pre(&$product_id, &$status)
{
    if ($product_id == fn_vendor_debt_payout_get_payout_product()
        && !defined('VENDOR_DEBT_PAYOUT_UNINSTALL')
    ) {
        $status = false;
    }
}

/**
 * Hook handler: prevents fees category removal.
 *
 * @param int  $category_id Category ID
 * @param bool $recurse     Whether to remove all nested categories
 */
function fn_vendor_debt_payout_delete_category_pre(&$category_id, &$recurse)
{
    if ($category_id != fn_vendor_debt_payout_get_payout_category()
        || defined('VENDOR_DEBT_PAYOUT_UNINSTALL')
    ) {
        return;
    }

    $category_id = null;
}

/**
 * Hook handler: prevents crucial data modificaiton for fees product.
 *
 * @param array  $product_data Edited product data
 * @param int    $product_id   Product ID
 * @param string $lang_code    Two-letter language code
 * @param bool   $can_update   Whether product can be edited
 */
function fn_vendor_debt_payout_update_product_pre(&$product_data, &$product_id, &$lang_code, &$can_update)
{
    if ($product_id != fn_vendor_debt_payout_get_payout_product()
        || defined('VENDOR_DEBT_PAYOUT_INSTALL')
    ) {
        return;
    }

    $product_data['company_id'] = 0;
    $product_data['category_id'] = array(fn_vendor_debt_payout_get_payout_category());
    $product_data['status'] = 'H';
    $product_data['zero_price_action'] = 'R';
    $product_data['tracking'] = 'D';
    $product_data['out_of_stock_actions'] = 'N';
    $product_data['is_edp'] = 'Y';
    $product_data['product_type'] = ProductTypes::DEBT_PAYOUT;
}

/**
 * Hook handler: removes all tabs but General from fee payout product update page.
 */
function fn_vendor_debt_payout_dispatch_before_display()
{
    $controller = Registry::get('runtime.controller');
    $mode = Registry::get('runtime.mode');

    if (AREA !== 'A' || $controller !== 'products' || $mode !== 'update') {
        return;
    }

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    /** @var array $product_data */
    $product_data = $view->getTemplateVars('product_data');
    if (
        !isset($product_data['product_type'])
        || $product_data['product_type'] !== ProductTypes::DEBT_PAYOUT
    ) {
        return;
    }

    $tabs = Registry::get('navigation.tabs');

    if (isset($tabs['detailed'])) {
        $tabs = array(
            'detailed' => $tabs['detailed'],
        );

        Registry::set('navigation.tabs', $tabs);
    }
}

/**
 * Hook handler: sets order paid-off status.
 *
 * @param array $order           Order info
 * @param array $additional_data Additional order data
 */
function fn_vendor_debt_payout_get_order_info(&$order, &$additional_data)
{
    if (!empty($additional_data['D'])) {
        $order['is_debt_paid'] = unserialize($additional_data['D']);
    }
}

/**
 * Hook handler: allows to skip clearing the cart when the catalog mode is enabled
 *
 * @param array $product_data List of products data
 * @param array $cart         Array of cart content and user information necessary for purchase
 * @param array $auth         Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @param bool  $update       Flag, if true that is update mode. Usable for order management
 * @param bool  $can_delete   Flag, if true that is cart cleared. Usable to pay off the vendor debt.
 */
function fn_vendor_debt_payout_catalog_mode_pre_add_to_cart(&$product_data, $cart, $auth, $update, &$can_delete)
{
    foreach ($product_data as $product) {
        if (isset($product['extra'])) {
            foreach ($product['extra'] as $key => $value) {
                if ($key == 'vendor_debt_payout') {
                    $can_delete = false;
                }
            }
        }
    }
}

/**
 * Hook handler: allows to skip applying cart or catalog promotions if vendor debt in cart
 *
 * @param array  $promotions    List of promotions
 * @param string $zone          Promotion zone (catalog, cart)
 * @param array  $data          data array (product - for catalog rules, cart - for cart rules)
 * @param array  $auth          (optional) - auth array (for car rules)
 * @param array  $cart_products (optional) - cart products array (for car rules)
 */
function fn_vendor_debt_payout_promotion_apply_pre(&$promotions, $zone, $data, $auth, $cart_products)
{
    static $debt_payout_product_id = null;

    if ($debt_payout_product_id === null) {
        $debt_payout_product_id = fn_vendor_debt_payout_get_payout_product();
    }

    if (!isset($data['products'])
        && (!isset($data['product_id']) || $data['product_id'] !=  $debt_payout_product_id)
    ) {
        return;
    }

    if (isset($data['products'])) {
        $cart = $data['products'];
        foreach ($cart as $id => $product) {
            if (isset($product['extra']['vendor_debt_payout'])) {
                $promotions = [];
            }
        }
    }

    if (isset($data['product_id']) && $data['product_id'] ==  $debt_payout_product_id) {
        $promotions = [];
    }
}

/**
 * Check vendor debt
 *
 * @param int $vendor_id Vendor identifier
 *
 * @throws \Tygh\Exceptions\DeveloperException When notification event for receiver and transport was not found.
 */
function fn_vendor_debt_payout_check_vendor_debt($vendor_id)
{
    $vendor_debt_data = fn_vendor_debt_payout_get_vendor_debt_data($vendor_id);

    if (
        $vendor_debt_data['debt_status'] === VendorDebtStatuses::ACTIVE
        && fn_vendor_debt_payout_is_grace_period_need_start($vendor_id)
    ) {
        fn_vendor_debt_payout_start_grace_period($vendor_id);
    } elseif (
        $vendor_debt_data['debt_status'] === VendorDebtStatuses::IN_GRACE_PERIOD
        && fn_vendor_debt_payout_is_vendor_need_to_suspend($vendor_id)
    ) {
        fn_vendor_debt_payout_start_suspend_period($vendor_id);
    } elseif (
        $vendor_debt_data['debt_status'] === VendorDebtStatuses::SUSPENDED
        && fn_vendor_debt_payout_is_vendor_need_to_disable($vendor_id)
        && YesNo::toBool(Registry::get('addons.vendor_debt_payout.disable_suspended_vendors'))
    ) {
        fn_vendor_debt_payout_disable_vendor($vendor_id);
    }

    if (!fn_vendor_debt_payout_is_need_to_drop_debts($vendor_id)) {
        return;
    }

    fn_vendor_debt_payout_drop_vendor_debts($vendor_id);
}

/**
 * Gets vendor debt status
 *
 * @param array{status: string, suspend_date: int, grace_period_start: int} $vendor_debt_data Vendor debt data
 *
 * @return string
 */
function fn_vendor_debt_payout_get_vendor_debt_status(array $vendor_debt_data)
{
    if (
        $vendor_debt_data['status'] !== VendorStatuses::SUSPENDED
        && $vendor_debt_data['status'] !== VendorStatuses::DISABLED
        && empty($vendor_debt_data['grace_period_start'])
    ) {
        return VendorDebtStatuses::ACTIVE;
    }

    if (
        $vendor_debt_data['status'] !== VendorStatuses::SUSPENDED
        && $vendor_debt_data['status'] !== VendorStatuses::DISABLED
        && !empty($vendor_debt_data['grace_period_start'])
    ) {
        return VendorDebtStatuses::IN_GRACE_PERIOD;
    }

    if ($vendor_debt_data['status'] === VendorStatuses::SUSPENDED && !empty($vendor_debt_data['suspend_date'])) {
        return VendorDebtStatuses::SUSPENDED;
    }

    return VendorDebtStatuses::DISABLED;
}

/**
 * Checks if vendor gets debts and need to change debt status
 *
 * @param int $vendor_id Vendor identifier
 *
 * @return bool
 */
function fn_vendor_debt_payout_is_grace_period_need_start($vendor_id)
{
    $payouts_manager = VendorPayouts::instance(['vendor' => $vendor_id]);
    list($balance) = $payouts_manager->getBalance();

    $vendor_plan = fn_vendor_plans_get_vendor_plan_by_company_id($vendor_id);
    if (!empty($vendor_plan) && $balance < fn_vendor_debt_payout_get_lowers_allowed_balance($vendor_plan)) {
        return true;
    }

    return false;
}

/**
 * Starts grace period
 *
 * @param int $vendor_id Vendor identifier
 *
 * @throws \Tygh\Exceptions\DeveloperException When notification event for receiver and transport was not found.
 */
function fn_vendor_debt_payout_start_grace_period($vendor_id)
{
    db_query(
        'UPDATE ?:companies SET ?u WHERE company_id = ?i',
        ['grace_period_start' => TIME],
        $vendor_id
    );

    fn_vendor_debt_payout_send_email_notification_about_grace_period($vendor_id);
}

/**
 * Checks if vendor need to be suspended
 *
 * @param int $vendor_id Vendor identifier
 *
 * @return bool
 */
function fn_vendor_debt_payout_is_vendor_need_to_suspend($vendor_id)
{
    $vendor_debt_data = fn_vendor_debt_payout_get_vendor_debt_data($vendor_id);
    $plan = fn_vendor_plans_get_vendor_plan_by_company_id($vendor_id);

    $grace_period = TIME - $vendor_debt_data['grace_period_start'];

    if (!empty($plan) && $grace_period >= (fn_vendor_debt_payout_get_grace_period_to_refill_balance($plan) * SECONDS_IN_DAY)) {
        return true;
    }

    return false;
}

/**
 * Starts suspend period
 *
 * @param int $vendor_id Vendor identifier
 *
 * @throws \Tygh\Exceptions\DeveloperException When notification event for receiver and transport was not found.
 */
function fn_vendor_debt_payout_start_suspend_period($vendor_id)
{
    $reason_data = fn_vendor_debt_payout_get_block_reason_data($vendor_id, VendorStatuses::SUSPENDED);

    if (empty($reason_data)) {
        return;
    }

    fn_change_company_status(
        $vendor_id,
        VendorStatuses::SUSPENDED,
        __('vendor_debt_payout.vendor_status_changed.reason', $reason_data)
    );

    fn_vendor_debt_payout_notify_admin_about_blocked_vendor($vendor_id, VendorStatuses::SUSPENDED);
}

/**
 * Gets block reason data
 *
 * @param int    $vendor_id    Vendor identifier
 * @param string $block_status Block status
 *
 * @return false|array<string, string>
 */
function fn_vendor_debt_payout_get_block_reason_data($vendor_id, $block_status)
{
    $date_of_block = fn_vendor_debt_payout_get_date_of_block($vendor_id, $block_status);

    if (empty($date_of_block)) {
        return false;
    }

    /** @var \Tygh\Tools\Formatter $formatter */
    $formatter = Tygh::$app['formatter'];

    return [
        '[amount]' => $formatter->asPrice(fn_vendor_debt_payout_get_amount_to_pay($vendor_id)),
        '[date]'   => $formatter->asDatetime($date_of_block),
    ];
}

/**
 * Checks if vendor need to be disabled
 *
 * @param int $vendor_id Vendor identifier
 *
 * @return bool
 */
function fn_vendor_debt_payout_is_vendor_need_to_disable($vendor_id)
{
    $vendor_debt_data = fn_vendor_debt_payout_get_vendor_debt_data($vendor_id);

    $suspend_period = TIME - $vendor_debt_data['suspend_date'];

    if ($suspend_period >= (Registry::get('addons.vendor_debt_payout.days_before_disable') * SECONDS_IN_DAY)) {
        return true;
    }

    return false;
}

/**
 * Disables vendor
 *
 * @param int $vendor_id Vendor identifier
 *
 * @throws \Tygh\Exceptions\DeveloperException When notification event for receiver and transport was not found.
 */
function fn_vendor_debt_payout_disable_vendor($vendor_id)
{
    $reason_data = fn_vendor_debt_payout_get_block_reason_data($vendor_id, VendorStatuses::DISABLED);

    if (empty($reason_data)) {
        return;
    }

    fn_change_company_status(
        $vendor_id,
        VendorStatuses::DISABLED,
        __('vendor_debt_payout.vendor_status_changed.reason', $reason_data)
    );

    fn_vendor_debt_payout_notify_admin_about_blocked_vendor($vendor_id, VendorStatuses::DISABLED);
}

/**
 * Returns amount to pay for the debt
 *
 * @param int $vendor_id Vendor identifier
 *
 * @return float
 */
function fn_vendor_debt_payout_get_amount_to_pay($vendor_id)
{
    $payouts_manager = VendorPayouts::instance(['vendor' => $vendor_id]);
    list($balance) = $payouts_manager->getBalance();

    $vendor_plan = fn_vendor_plans_get_vendor_plan_by_company_id($vendor_id);

    if (empty($vendor_plan)) {
        return 0;
    }

    $total_debt = $balance - fn_vendor_debt_payout_get_lowers_allowed_balance($vendor_plan);
    if ($total_debt < 0) {
        $total_debt = abs($total_debt);
    } else {
        $total_debt = 0;
    }

    return $total_debt;
}

/**
 * Returns date when vendor will be blocked
 *
 * @param int    $vendor_id    Vendor identifier
 * @param string $block_status Status in which vendor will be blocked
 *
 * @return int|false
 */
function fn_vendor_debt_payout_get_date_of_block($vendor_id, $block_status)
{
    $vendor_plan = fn_vendor_plans_get_vendor_plan_by_company_id($vendor_id);

    if (empty($vendor_plan)) {
        return false;
    }

    $vendor_debt_data = fn_vendor_debt_payout_get_vendor_debt_data($vendor_id);

    $period_start = $period_limit = 0;

    if ($block_status === VendorStatuses::SUSPENDED) {
        $period_start = $vendor_debt_data['grace_period_start'];
        $period_limit = fn_vendor_debt_payout_get_grace_period_to_refill_balance($vendor_plan) * SECONDS_IN_DAY;
    } elseif ($block_status === VendorStatuses::DISABLED) {
        $period_start = $vendor_debt_data['suspend_date'];
        $period_limit = Registry::get('addons.vendor_debt_payout.days_before_disable') * SECONDS_IN_DAY;
    }

    return $period_start + $period_limit;
}

/**
 * Checks if vendor need to drop debt data
 *
 * @param int $vendor_id Vendor identifier
 *
 * @return bool
 */
function fn_vendor_debt_payout_is_need_to_drop_debts($vendor_id)
{
    $vendor_plan = fn_vendor_plans_get_vendor_plan_by_company_id($vendor_id);

    if (empty($vendor_plan)) {
        return false;
    }

    $vendor_debt_data = fn_vendor_debt_payout_get_vendor_debt_data($vendor_id);

    $payouts_manager = VendorPayouts::instance(['vendor' => $vendor_id]);

    list($balance) = $payouts_manager->getBalance();

    if (
        $vendor_debt_data !== VendorDebtStatuses::ACTIVE
        && $balance >= fn_vendor_debt_payout_get_lowers_allowed_balance($vendor_plan)
        && !empty($vendor_debt_data['grace_period_start'])
    ) {
        return true;
    }

    return false;
}

/**
 * Drop vendor's debts
 *
 * @param int $vendor_id Vendor identifier
 */
function fn_vendor_debt_payout_drop_vendor_debts($vendor_id)
{
    fn_change_company_status(
        $vendor_id,
        VendorStatuses::ACTIVE
    );

    $update_data = [
        'last_time_suspended'         => TIME,
        'grace_period_start'          => 0,
        'last_debt_notification_time' => 0,
    ];

    db_query('UPDATE ?:companies SET ?u WHERE company_id = ?i', $update_data, $vendor_id);
}

/**
 * Checks debts of all vendors
 *
 * @param int $vendor_id Vendor identifier
 *
 * @throws \Tygh\Exceptions\DeveloperException When notification event for receiver and transport was not found.
 */
function fn_vendor_debt_payout_check_debts($vendor_id = 0)
{
    if ($vendor_id === 0) {
        $vendor_statuses = [
            VendorStatuses::ACTIVE,
            VendorStatuses::SUSPENDED,
        ];

        $vendor_ids = db_get_fields('SELECT company_id FROM ?:companies WHERE status IN (?a)', $vendor_statuses);
    } else {
        $vendor_ids = [$vendor_id];
    }

    foreach ($vendor_ids as $vendor_id) {
        fn_vendor_debt_payout_check_vendor_debt($vendor_id);
    }
}

/**
 * Sends internal notification to pay the debt
 *
 * @param int $vendor_id Vendor identifier
 *
 * @throws \Tygh\Exceptions\DeveloperException When notification event for receiver and transport was not found.
 */
function fn_vendor_debt_payout_send_internal_notification_about_negative_balance($vendor_id)
{
    $amount = fn_vendor_debt_payout_get_amount_to_pay($vendor_id);
    $date_of_block = fn_vendor_debt_payout_get_date_of_block($vendor_id, VendorStatuses::SUSPENDED);

    if (empty($date_of_block)) {
        return;
    }

    /** @var \Tygh\Tools\Formatter $formatter */
    $formatter = Tygh::$app['formatter'];

    $notification_data = [
        'to_company_id' => $vendor_id,
        'amount'        => $formatter->asPrice($amount),
        'action_url'    => fn_vendor_debt_payout_get_pay_url($vendor_id, Tygh::$app['session']['auth'], $amount),
    ];

    $vendor_plan = fn_vendor_plans_get_vendor_plan_by_company_id($vendor_id);

    if (empty($vendor_plan)) {
        return;
    }

    $vendor_debt_data = fn_vendor_debt_payout_get_vendor_debt_data($vendor_id);

    if ($vendor_debt_data['debt_status'] === VendorDebtStatuses::IN_GRACE_PERIOD) {
        $notification_data['date'] = $formatter->asDatetime($date_of_block);
    } elseif ($vendor_debt_data['debt_status'] === VendorDebtStatuses::SUSPENDED) {
        $notification_data['vendor_plan_lowers_allowed_balance'] = $formatter->asPrice(fn_vendor_debt_payout_get_lowers_allowed_balance($vendor_plan));
    }

    /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
    $event_dispatcher = Tygh::$app['event.dispatcher'];

    $force_notification = [
        UserTypes::VENDOR => true,
    ];

    /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
    $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];

    $notification_rules = $notification_settings_factory->create($force_notification);
    $event_dispatcher->dispatch(
        'vendor_debt_payout.negative_balance_reached',
        $notification_data,
        $notification_rules
    );
}

/**
 * Notifies administrator about blocked or suspended vendor
 *
 * @param int    $vendor_id        Vendor identifier
 * @param string $vendor_status_to Status which vendor will changed to
 *
 * @throws \Tygh\Exceptions\DeveloperException When notification event for receiver and transport was not found.
 */
function fn_vendor_debt_payout_notify_admin_about_blocked_vendor($vendor_id, $vendor_status_to)
{
    $amount = fn_vendor_debt_payout_get_amount_to_pay($vendor_id);
    $date_of_block = fn_vendor_debt_payout_get_date_of_block($vendor_id, $vendor_status_to);

    if (empty($date_of_block)) {
        return;
    }

    /** @var \Tygh\Tools\Formatter $formatter */
    $formatter = Tygh::$app['formatter'];

    $notification_data = [
        'vendor_name' => fn_get_company_name($vendor_id),
        'amount'      => $formatter->asPrice($amount),
        'date'        => $formatter->asDatetime($date_of_block),
        'action_url'  => fn_url('admin:companies.update?company_id=' . $vendor_id, SiteArea::ADMIN_PANEL),
    ];

    $event_id = 'vendor_debt_payout.vendor_status_changed_to_suspended';

    if ($vendor_status_to === VendorStatuses::SUSPENDED) {
        $notification_data['status_to'] = __('suspended');
    } elseif ($vendor_status_to === VendorStatuses::DISABLED) {
        $event_id = 'vendor_debt_payout.vendor_status_changed_to_disabled';

        $notification_data['status_to'] = __('disabled');
    }

    $reason_data = [
        '[amount]' => $formatter->asPrice($amount),
        '[date]'   => $formatter->asDatetime($date_of_block),
    ];

    $notification_data['reason'] = __('vendor_debt_payout.vendor_status_changed.reason', $reason_data);

    /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
    $event_dispatcher = Tygh::$app['event.dispatcher'];

    $force_notification = [
        UserTypes::ADMIN => true,
    ];

    /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
    $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];

    $notification_rules = $notification_settings_factory->create($force_notification);
    $event_dispatcher->dispatch($event_id, $notification_data, $notification_rules);
}

/**
 * Sends email notification to vendors in grace period
 *
 * @param int $vendor_id Vendor identifier
 *
 * @throws \Tygh\Exceptions\DeveloperException When notification event for receiver and transport was not found.
 */
function fn_vendor_debt_payout_send_email_to_vendors_in_grace_period($vendor_id = 0)
{
    $condition = $vendor_id === 0
        ? ''
        : db_quote(' AND company_id = ?i', $vendor_id);

    $vendors_ids_list = db_get_hash_array(
        'SELECT company_id, grace_period_start, last_debt_notification_time FROM ?:companies'
        . ' WHERE status = ?s AND grace_period_start > 0 ?p',
        'company_id',
        VendorStatuses::ACTIVE,
        $condition
    );

    foreach ($vendors_ids_list as $vendor_id => $vendor_data) {
        $vendor_plan = fn_vendor_plans_get_vendor_plan_by_company_id($vendor_id);

        if (empty($vendor_plan)) {
            continue;
        }

        $date_of_block = fn_vendor_debt_payout_get_date_of_block($vendor_id, VendorStatuses::SUSPENDED);

        if (empty($date_of_block)) {
            continue;
        }

        $grace_period = fn_vendor_debt_payout_get_grace_period_to_refill_balance($vendor_plan);

        $middle_of_grace_period = $vendor_data['grace_period_start'] + ($grace_period / 2) * SECONDS_IN_DAY;
        $day_before_block = $date_of_block - SECONDS_IN_DAY;

        // Sends email notification about grace period:
        // if grace period of the plan >= 3
        //      Sends notification one day before grace period will ended
        // if grace period of the plan >= 6
        //      Also, sends notification in the middle of the grace period
        if (
            !(
                fn_vendor_debt_payout_get_grace_period_to_refill_balance($vendor_plan) >= 6
                && TIME > $middle_of_grace_period
                && $vendor_data['last_debt_notification_time'] < $middle_of_grace_period
            )
            && !(
                fn_vendor_debt_payout_get_grace_period_to_refill_balance($vendor_plan) >= 3
                && TIME > $day_before_block
                && $vendor_data['last_debt_notification_time'] < $day_before_block
            )
        ) {
            continue;
        }

        fn_vendor_debt_payout_send_email_notification_about_grace_period($vendor_id);
    }
}

/**
 * Sends email notification to vendor about grace period
 *
 * @param int $vendor_id Vendor identifier
 *
 * @throws \Tygh\Exceptions\DeveloperException When notification event for receiver and transport was not found.
 */
function fn_vendor_debt_payout_send_email_notification_about_grace_period($vendor_id)
{
    $vendor_admin_id = fn_get_company_admin_user_id($vendor_id);
    $vendor_admin_email = fn_get_user_email($vendor_admin_id);
    $vendor_admin_language = db_get_field('SELECT lang_code FROM ?:users WHERE user_id = ?i', $vendor_admin_id);
    $is_rtl_vendor_admin_language = fn_is_rtl_language($vendor_admin_language);

    $vendor_plan = fn_vendor_plans_get_vendor_plan_by_company_id($vendor_id);

    if (empty($vendor_plan)) {
        return;
    }

    $date_of_block = fn_vendor_debt_payout_get_date_of_block($vendor_id, VendorStatuses::SUSPENDED);

    if (empty($date_of_block)) {
        return;
    }

    $amount = fn_vendor_debt_payout_get_amount_to_pay($vendor_id);
    $vendor_registered_from_storefront_id = db_get_field('SELECT registered_from_storefront_id FROM ?:companies WHERE company_id = ?i', $vendor_id);

    $payouts_manager = VendorPayouts::instance(['vendor' => $vendor_id]);
    list($balance) = $payouts_manager->getBalance();

    /** @var \Tygh\Tools\Formatter $formatter */
    $formatter = Tygh::$app['formatter'];

    $reason_data = [
        '[balance]'                => $formatter->asPrice($balance, CART_SECONDARY_CURRENCY, true, false, $is_rtl_vendor_admin_language),
        '[lowers_allowed_balance]' => $formatter->asPrice(
            fn_vendor_debt_payout_get_lowers_allowed_balance($vendor_plan),
            CART_SECONDARY_CURRENCY,
            true,
            false,
            $is_rtl_vendor_admin_language
        ),
        '[amount]'                 => $formatter->asPrice($amount, CART_SECONDARY_CURRENCY, true, false, $is_rtl_vendor_admin_language),
        '[date]'                   => $formatter->asDatetime($date_of_block),
        '[link]'                   => fn_vendor_debt_payout_get_pay_url($vendor_id, Tygh::$app['session']['auth'], $amount)
    ];

    $notification_data = [
        'marketplace'           => Registry::get('settings.Company.company_name'),
        'lang_code'             => $vendor_admin_language,
        'reason'                => __('vendor_debt_payout.reason_block', $reason_data, $vendor_admin_language),
        'vendor_email'          => $vendor_admin_email,
        'to_company_id'         => $vendor_id,
        'action_url'            => fn_vendor_debt_payout_get_pay_url($vendor_id, Tygh::$app['session']['auth'], $amount),
        'storefront_id'         => $vendor_registered_from_storefront_id
    ];

    /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
    $event_dispatcher = Tygh::$app['event.dispatcher'];

    $force_notification = [
        UserTypes::VENDOR => true,
    ];

    /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
    $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];

    $notification_rules = $notification_settings_factory->create($force_notification);
    $event_dispatcher->dispatch(
        'vendor_debt_payout.vendor_days_before_suspend',
        $notification_data,
        $notification_rules
    );

    db_query('UPDATE ?:companies SET last_debt_notification_time = ?i WHERE company_id = ?i', TIME, $vendor_id);
}

/**
 * The "change_company_status_before_mail" hook handler.
 *
 * Actions performed:
 *  - Adds timestamp of the date when the Vendor status was changed to Suspended.
 *
 * @param int    $vendor_id   Vendor identifier
 * @param string $status_to   Status to letter
 * @param string $reason      Reason text
 * @param string $status_from Status from letter
 *
 * @see \fn_change_company_status()
 */
function fn_vendor_debt_payout_change_company_status_before_mail($vendor_id, $status_to, $reason, $status_from)
{
    $data = [];

    $vendor_debt_data = fn_vendor_debt_payout_get_vendor_debt_data($vendor_id);

    if (
        ($status_from === VendorStatuses::SUSPENDED || $status_from === VendorStatuses::DISABLED)
        && ($status_to === VendorStatuses::ACTIVE || $status_to === VendorStatuses::PENDING)
        && (!empty($vendor_debt_data['grace_period_start']) || !empty($vendor_debt_data['suspend_date']))
    ) {
        $data = [
            'grace_period_start' => TIME,
        ];
    }

    if (
        $status_to === VendorStatuses::SUSPENDED
        && (!empty($reason) && !empty($vendor_debt_data['grace_period_start']))
    ) {
        $data = [
            'suspend_date'        => TIME,
            'last_time_suspended' => TIME,
        ];
    }

    if (empty($data)) {
        return;
    }

    db_query('UPDATE ?:companies SET ?u WHERE company_id = ?i', $data, $vendor_id);
}

/**
 * The "vendor_payouts_update_post" hook handler
 *
 * Actions performed:
 *     - Checks vendor debts
 *
 * @param VendorPayouts         $payouts_manager VendorPayouts instance
 * @param array<string, string> $data            Payout data
 * @param int                   $payout_id       Created/saved payout identifier
 * @param string                $action          Performed action: 'create' or 'update'
 *
 * @throws \Tygh\Exceptions\DeveloperException When notification event for receiver and transport was not found.
 *
 * @see \Tygh\VendorPayouts::update()
 */
function fn_vendor_debt_payout_vendor_payouts_update_post(VendorPayouts $payouts_manager, array $data, $payout_id, $action)
{
    if (!isset($data['company_id'])) {
        return;
    }

    fn_vendor_debt_payout_check_vendor_debt((int) $data['company_id']);
}

/**
 * The "get_companies_pre" hook handler.
 *
 * Actions performed:
 *     - Adds suspended companies, if "hide_products" setting is disabled
 *     - Selects only suspended companies, if 'get_suspended' parameter is present
 *
 * @param array<string, string> $params Array of get companies params
 *
 * @see \fn_get_companies()
 */
function fn_vendor_debt_payout_get_companies_pre(array &$params)
{
    if (
        !YesNo::toBool(Registry::get('addons.vendor_debt_payout.hide_products'))
        && !empty($params['status'])
        && SiteArea::isStorefront(AREA)
    ) {
        $statuses = is_array($params['status']) ? $params['status'] : (array) $params['status'];
        $params['status'] = array_merge($statuses, [VendorStatuses::SUSPENDED]);
    } elseif (isset($params['get_suspended']) && YesNo::toBool($params['get_suspended'])) {
        $params['status'] = VendorStatuses::SUSPENDED;
    }
}

/**
 * The "login_user_post" hook handler.
 *
 * Actions performed:
 *     - Checks vendor's debts after login
 *
 * @param int                   $user_id   User identifier
 * @param int                   $cu_id     Cart user identifier
 * @param array<string, string> $udata     User data
 * @param array<string, string> $auth      Authentication data
 * @param string                $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
 * @param string                $result    Result user login
 *
 * @throws \Tygh\Exceptions\DeveloperException When notification event for receiver and transport was not found.
 *
 * @see \fn_login_user()
 */
function fn_vendor_debt_payout_login_user_post($user_id, $cu_id, array $udata, array $auth, $condition, $result)
{
    if (
        $result !== LOGIN_STATUS_OK
        || ($udata['user_type'] !== UserTypes::ADMIN && $udata['user_type'] !== UserTypes::VENDOR)
        || SiteArea::isStorefront(AREA)
    ) {
        return;
    }

    $vendor_id = (int) $udata['company_id'];
    fn_vendor_debt_payout_check_debts($vendor_id);

    if ($udata['user_type'] === UserTypes::VENDOR) {
        $vendor_debt_data = fn_vendor_debt_payout_get_vendor_debt_data($vendor_id);

        if (
            $vendor_debt_data['debt_status'] === VendorDebtStatuses::IN_GRACE_PERIOD
            || $vendor_debt_data['debt_status'] === VendorDebtStatuses::SUSPENDED
        ) {
            fn_vendor_debt_payout_send_internal_notification_about_negative_balance($vendor_id);
        }
    }

    fn_vendor_debt_payout_send_email_to_vendors_in_grace_period();

    if ($udata['user_type'] !== UserTypes::ADMIN) {
        return;
    }

    fn_vendor_debt_payout_send_email_weekly_digest_to_admin($auth);
}

/**
 * The "dashboard_get_vendor_activities_post" hook handler.
 *
 * Actions performed:
 *     - Adds suspended vendors statistic
 *     - Adds maximum total vendor debt statistic
 *
 * @param int                   $timestamp_from             From timestamp
 * @param int                   $timestamp_to               To timestamp
 * @param array<string, string> $dashboard_vendors_activity Array of the dashboard vendor activities
 *
 * @see \fn_dashboard_get_vendor_activities()
 */
function fn_vendor_debt_payout_dashboard_get_vendor_activities_post($timestamp_from, $timestamp_to, array &$dashboard_vendors_activity)
{
    $params = [
        'time_from'      => $timestamp_from,
        'time_to'        => $timestamp_to,
        'get_suspended'  => true,
        'get_conditions' => true,
    ];

    $auth = [];

    list(, $joins, $conditions) = fn_get_companies($params, $auth);

    $dashboard_vendors_activity['suspended_vendors'] = db_get_field(
        'SELECT COUNT(DISTINCT ?:companies.company_id) FROM ?:companies ?p WHERE 1 ?p',
        $joins,
        $conditions
    );
}

/**
 * The "get_companies" hook handler.
 *
 * Actions performed:
 *     - Find companies by additional params
 *
 * @param array<string, string> $params    Params to get vendors list
 * @param array<string>         $fields    Array of fields to get about companies
 * @param array<string, string> $sortings  Array with sort params to sort companies
 * @param string                $condition SQL condition to get companies
 *
 * @see \fn_get_companies()
 */
function fn_vendor_debt_payout_get_companies(array $params, array &$fields, array $sortings, &$condition)
{
    $fields[] = '?:companies.suspend_date';
    $fields[] = '?:companies.last_time_suspended';

    if (!isset($params['get_suspended']) || !YesNo::toBool($params['get_suspended'])) {
        return;
    }

    $condition .= db_quote(
        'AND suspend_date > ?i AND suspend_date < ?i',
        $params['time_from'],
        $params['time_to']
    );
}

/**
 * The "dispatch_before_send_response" hook handler
 *
 * Actions performed:
 *     - Sends a notification about negative balance on the each page
 *
 * @param string $status     Controller response status
 * @param string $area       Currently running application area
 * @param string $controller Controller
 * @param string $mode       Mode
 *
 * @see \fn_dispatch()
 */
function fn_vendor_debt_payout_dispatch_before_send_response($status, $area, $controller, $mode)
{
    if (
        ($controller === 'index' && $mode === 'index')
        || $area === SiteArea::STOREFRONT
    ) {
        return;
    }

    /** @var \Tygh\Web\Session $session */
    $session = Tygh::$app['session'];

    $auth = $session['auth'];

    if ($area !== SiteArea::ADMIN_PANEL || $auth['user_type'] !== UserTypes::VENDOR) {
        return;
    }

    if (!empty($session['vendor_debt_payout']['debt_notification_count'])) {
        $session['vendor_debt_payout']['debt_notification_count'] ++;
        if ($session['vendor_debt_payout']['debt_notification_count'] === 15) {
            $session['vendor_debt_payout']['debt_notification_count'] = 0;
        }
        return;
    }

    $vendor_id = (int) $auth['company_id'];

    $vendor_debt_data = fn_vendor_debt_payout_get_vendor_debt_data($vendor_id);

    if ($vendor_debt_data['debt_status'] === VendorDebtStatuses::ACTIVE) {
        return;
    }

    $amount_to_pay = fn_vendor_debt_payout_get_amount_to_pay($vendor_id);
    if (!$amount_to_pay) {
        return;
    }

    /** @var \Tygh\Tools\Formatter $formatter */
    $formatter = Tygh::$app['formatter'];

    $message_data = [
        '[amount]' => $formatter->asPrice($amount_to_pay),
        '[link]'   => fn_vendor_debt_payout_get_pay_url($vendor_id, Tygh::$app['session']['auth'], $amount_to_pay),
    ];

    $session['vendor_debt_payout']['debt_notification_count'] = 1;

    if ($vendor_debt_data['debt_status'] === VendorDebtStatuses::IN_GRACE_PERIOD) {
        $date_of_block = fn_vendor_debt_payout_get_date_of_block($vendor_id, VendorStatuses::SUSPENDED);

        if (empty($date_of_block)) {
            return;
        }

        $message_data['[date]'] = $formatter->asDatetime($date_of_block);

        fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('vendor_debt_payout.warning_debt_notification', $message_data));
        return;
    }

    $vendor_plan = fn_vendor_plans_get_vendor_plan_by_company_id($vendor_id);

    if (empty($vendor_plan)) {
        return;
    }

    $message_data['[vendor_plan_lowers_allowed_balance]'] = $formatter->asPrice(fn_vendor_debt_payout_get_lowers_allowed_balance($vendor_plan));

    fn_set_notification(NotificationSeverity::ERROR, __('warning'), __('vendor_debt_payout.suspend_debt_notification', $message_data));
}

/**
 * The "get_product_data_pre" hook handler
 *
 * Actions performed:
 *     - Adds Suspended company status to get product data
 *
 * @param int                          $product_id             Product identifier
 * @param array<string, string>        $auth                   Array with authorization data
 * @param string                       $lang_code              Two-letters language code
 * @param string                       $field_list             List of fields for retrieving
 * @param bool                         $get_add_pairs          Get additional images
 * @param bool                         $get_main_pair          Get main images
 * @param bool                         $get_taxes              Get taxes
 * @param bool                         $get_qty_discounts      Get quantity discounts
 * @param bool                         $preview                Is product previewed by admin
 * @param bool                         $features               Get product features
 * @param bool                         $skip_company_condition Skip company condition and retrieve product data for
 *                                                                 displaying on other store page. (Works only in ULT)
 * @param array<string, array<string>> $params                 Array of additional params
 *
 * @see \fn_get_product_data()
 */
function fn_vendor_debt_payout_get_product_data_pre(
    $product_id,
    array $auth,
    $lang_code,
    $field_list,
    $get_add_pairs,
    $get_main_pair,
    $get_taxes,
    $get_qty_discounts,
    $preview,
    $features,
    $skip_company_condition,
    array &$params
)
{
    if (!isset($params['company_statuses']) || YesNo::toBool(Registry::get('addons.vendor_debt_payout.hide_products'))) {
        return;
    }

    $params['company_statuses'] = array_merge($params['company_statuses'], [VendorStatuses::SUSPENDED]);
}

/**
 * The "pre_get_cart_product_data" hook handler
 *
 * Actions performed:
 *     - Adds Suspended company status to params
 *
 * @param string                          $hash             Unique product HASH
 * @param array<string, int|string|array> $product          Product data
 * @param bool                            $skip_promotion   Skip promotion calculation
 * @param array<string, int|string|array> $cart             Array of cart content and user information necessary for purchase
 * @param array<string, int|string|array> $auth             Array with authorization data
 * @param int                             $promotion_amount Amount of product in promotion (like Free products, etc)
 * @param array<string, string>           $fields           SQL query fields
 * @param string                          $join             JOIN statement
 * @param array<string, array<string>>    $params           Array of additional params
 *
 * @see \fn_get_cart_product_data()
 */
function fn_vendor_debt_payout_pre_get_cart_product_data(
    $hash,
    array $product,
    $skip_promotion,
    array $cart,
    array $auth,
    $promotion_amount,
    array $fields,
    $join,
    array &$params
)
{
    if (!isset($params['company_statuses']) || YesNo::toBool(Registry::get('addons.vendor_debt_payout.hide_products'))) {
        return;
    }

    $params['company_statuses'] = array_merge($params['company_statuses'], [VendorStatuses::SUSPENDED]);
}

/**
 * The "init_templater_post" hook handler.
 *
 * Actions performed:
 *  - Adds smarty components to view
 *
 * @param SmartyCore $view Current view
 *
 * @see fn_init_templater
 */
function fn_vendor_debt_payout_init_templater_post(SmartyCore &$view)
{
    $view->addPluginsDir(Registry::get('config.dir.addons') . 'vendor_debt_payout/functions/smarty_plugins');
}

/**
 * Gets lowers allowed balance from vendor plan
 *
 * @param Tygh\Models\VendorPlan $plan Information about plan
 *
 * @return float Lowers allowed balance value if set, 'default' otherwise
 */
function fn_vendor_debt_payout_get_lowers_allowed_balance(VendorPlan $plan)
{
    $global_lowers_allowed_balance = Registry::get('addons.vendor_debt_payout.global_lowers_allowed_balance');

    if (isset($global_lowers_allowed_balance)) {
        return (float) $global_lowers_allowed_balance;
    }

    if (isset($plan->lowers_allowed_balance)) {
        return (float) $plan->lowers_allowed_balance;
    }

    return (float) Registry::ifGet('addons.vendor_debt_payout.default_lowers_allowed_balance', 0);
}

/**
 * Gets grace period to refill balance from vendor plan
 *
 * @param Tygh\Models\VendorPlan $plan Information about plan
 *
 * @return int Grace period to refill balance value if set, 'default' otherwise
 */
function fn_vendor_debt_payout_get_grace_period_to_refill_balance(VendorPlan $plan)
{
    $global_grace_period_to_refill_balance = Registry::get('addons.vendor_debt_payout.global_grace_period_to_refill_balance');

    if (isset($global_grace_period_to_refill_balance)) {
        return (int) $global_grace_period_to_refill_balance;
    }

    if (isset($plan->grace_period_to_refill_balance)) {
        return (int) $plan->grace_period_to_refill_balance;
    }

    return (int) Registry::ifGet('addons.vendor_debt_payout.default_grace_period_to_refill_balance', 0);
}

/**
 * The "get_product_data_post" hook handler.
 *
 * Actions performed:
 *  - Set zero price action to Not allow add to cart for Debt payout product
 *
 * @param array<string, int|string|array> $product_data Product data
 */
function fn_vendor_debt_payout_get_product_data_post(&$product_data)
{
    if (empty($product_data['product_type']) || $product_data['product_type'] !== ProductTypes::DEBT_PAYOUT) {
        return;
    }

    $product_data['zero_price_action'] = ProductZeroPriceActions::NOT_ALLOW_ADD_TO_CART;
}

/**
 * Sends weekly digest of debtors
 *
 * @param array<string, string> $auth Authentication data
 */
function fn_vendor_debt_payout_send_email_weekly_digest_to_admin(array $auth)
{
    $last_notification = fn_get_storage_data('vendor_debt_payout.weekly_digest_last_timestamp');

    if (!empty($last_notification) && (TIME - $last_notification) < 7 * SECONDS_IN_DAY) {
        return;
    }

    $last_notification = empty($last_notification) ? 0 : $last_notification;
    $before_last_notification = fn_get_storage_data('vendor_debt_payout.weekly_digest_before_last_timestamp');
    $before_last_notification = empty($before_last_notification) ? 0 : $before_last_notification;

    $total_debt = 0;
    $suspended_vendors = $active_vendors = [];

    list($vendor_list) = fn_get_companies([], $auth);

    /** @var \Tygh\Tools\Formatter $formatter */
    $formatter = Tygh::$app['formatter'];

    if (!empty($vendor_list)) {
        foreach ($vendor_list as $vendor) {
            $plan = fn_vendor_plans_get_vendor_plan_by_company_id($vendor['company_id']);

            if (empty($plan)) {
                continue;
            }

            $lowers_allowed_balance = fn_vendor_debt_payout_get_lowers_allowed_balance($plan);

            $vendor_payouts = VendorPayouts::instance(['vendor' => $vendor['company_id']]);
            list($balance) = $vendor_payouts->getBalance();
            $debt = $lowers_allowed_balance - $balance;
            $debt = $debt > 0 ? $debt : 0;

            $total_debt += $debt;

            if ($vendor['status'] !== VendorStatuses::ACTIVE && $vendor['status'] !== VendorStatuses::SUSPENDED) {
                continue;
            }

            $vendor['debt'] = $formatter->asPrice($debt);
            $vendor['balance'] = $formatter->asPrice($balance);

            if (
                $vendor['status'] === VendorStatuses::ACTIVE
                && (int) $vendor['suspend_date'] !== 0
                && (int) $vendor['suspend_date'] <= $last_notification
                && (int) $vendor['suspend_date'] >= $before_last_notification
            ) {
                $active_vendors[] = $vendor;
                continue;
            }

            if ($vendor['status'] !== VendorStatuses::SUSPENDED || (int) $vendor['last_time_suspended'] <= $last_notification) {
                continue;
            }

            $suspended_vendors[] = $vendor;
        }
    }

    $notification_data = [
        'marketplace_name'  => Registry::get('settings.Company.company_name'),
        'suspended_vendors' => $suspended_vendors,
        'active_vendors'    => $active_vendors,
        'total_debt'        => $formatter->asPrice($total_debt),
        'href'              => fn_url('companies.manage?status=' . VendorStatuses::SUSPENDED)
    ];

    /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
    $event_dispatcher = Tygh::$app['event.dispatcher'];

    $force_notification = [
        UserTypes::ADMIN => true,
    ];

    /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
    $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];

    $notification_rules = $notification_settings_factory->create($force_notification);
    $event_dispatcher->dispatch(
        'vendor_debt_payout.weekly_digest_of_debtors',
        $notification_data,
        $notification_rules
    );

    fn_set_storage_data('vendor_debt_payout.weekly_digest_before_last_timestamp', (string) $last_notification);
    fn_set_storage_data('vendor_debt_payout.weekly_digest_last_timestamp', TIME);
}

/**
 * The "get_checkout_settings_post" hook handler
 *
 * Actions performed:
 *  - Set zero for minimum order amount in checkout settings to allow refill
 * balance if refill value less than minimum order amount value
 *
 * @param array<string, mixed>            $cart            Cart information.
 * @param array<string, int|string|array> $checkout_config Checkout config information
 *
 * @return void
 *
 * @see fn_get_checkout_settings
 *
 * @psalm-param array{
 *   products: array<
 *     string, array{
 *       product_id: int
 *     }
 *   >
 * } $cart
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
function fn_vendor_debt_payout_get_checkout_settings_post(array $cart, array &$checkout_config)
{
    if (empty($cart['products'])) {
        return;
    }

    $cart_product = reset($cart['products']);
    $product_id = fn_vendor_debt_payout_get_payout_product();
    if ($cart_product && $cart_product['product_id'] === $product_id) {
        $checkout_config['min_order_amount'] = 0;
        return;
    }
}

/**
 * The "get_available_company_statuses_post" hook handler.
 *
 * Actions performed:
 * - Shows vendors in the Suspended status if the corresponding add-on setting is enabled.
 *
 * @param array<string> $statuses Available company statuses
 *
 * @return void
 *
 * @see \fn_get_available_company_statuses()
 */
function fn_vendor_debt_payout_get_available_company_statuses_post(array &$statuses)
{
    if (YesNo::toBool(Registry::get('addons.vendor_debt_payout.hide_products'))) {
        return;
    }

    $statuses[] = VendorStatuses::SUSPENDED;
}

/**
 * The "smarty_component_configurable_page_field_before_output" hook handler.
 *
 * Actions performed:
 * - Hides almost everything on the Marketplace fees product and category editing pages.
 *
 * @param string                         $entity       Page entity
 * @param string                         $tab          Tab of the field on the page
 * @param string                         $section      Section of the field in the tab
 * @param string                         $field        Field identifier
 * @param array<string, string|bool|int> $field_config Field configuration
 * @param array<string, string>          $params       Component parameters
 * @param string                         $content      Output field content
 * @param \Smarty_Internal_Template      $template     Template instance
 *
 * @return void
 *
 * @see \smarty_component_configurable_page_field
 */
function fn_vendor_debt_payout_smarty_component_configurable_page_field_before_output(
    $entity,
    $tab,
    $section,
    $field,
    array &$field_config,
    array $params,
    $content,
    Smarty_Internal_Template $template
) {
    if ($entity === 'products') {
        /** @var array<string, string> $product_data */
        $product_data = $template->getTemplateVars('product_data');
        if (
            empty($product_data['product_id'])
            || (int) $product_data['product_id'] !== fn_vendor_debt_payout_get_payout_product()
        ) {
            return;
        }

        $field_config['is_optional'] = $field !== 'product' && $field !== 'images';
        $field_config['is_visible'] = !$field_config['is_optional'];
    }

    // phpcs:ignore
    if ($entity === 'categories') {
        /** @var array<string, string> $category_data */
        $category_data = $template->getTemplateVars('category_data');
        if (
            empty($category_data['category_id'])
            || (int) $category_data['category_id'] !== fn_vendor_debt_payout_get_payout_category()
        ) {
            return;
        }

        $field_config['is_optional'] = $field !== 'category' && $field !== 'images';
        $field_config['is_visible'] = !$field_config['is_optional'];
    }
}

/**
 * The "smarty_component_configurable_page_section_before_output" hook handler.
 *
 * Actions performed:
 * - Hides almost everything on the Marketplace fees product and category editing pages.
 *
 * @param string                         $entity         Page entity
 * @param string                         $tab            Tab on the page
 * @param string                         $section        Section in the tab
 * @param array<string, string|bool|int> $section_config Section configuration
 * @param array<string, string>          $params         Component parameters
 * @param string                         $content        Output section content
 * @param \Smarty_Internal_Template      $template       Template instance
 *
 * @return void
 *
 * @see \smarty_component_configurable_page_section
 */
function fn_vendor_debt_payout_smarty_component_configurable_page_section_before_output(
    $entity,
    $tab,
    $section,
    array &$section_config,
    array $params,
    $content,
    Smarty_Internal_Template $template
) {
    if ($entity === 'products') {
        /** @var array<string, string> $product_data */
        $product_data = $template->getTemplateVars('product_data');
        if (
            empty($product_data['product_id'])
            || (int) $product_data['product_id'] !== fn_vendor_debt_payout_get_payout_product()
        ) {
            return;
        }

        $section_config['is_optional'] = $section !== 'information';
        $section_config['is_visible'] = $section === 'information';
    }

    // phpcs:ignore
    if ($entity === 'categories') {
        /** @var array<string, string> $category_data */
        $category_data = $template->getTemplateVars('category_data');
        if (
            empty($category_data['category_id'])
            || (int) $category_data['category_id'] !== fn_vendor_debt_payout_get_payout_category()
        ) {
            return;
        }

        $section_config['is_optional'] = $section !== 'information';
        $section_config['is_visible'] = $section === 'information';
    }
}

/**
 * The "allow_place_order_post" hook handler.
 *
 * Actions performed:
 * - Allows order placement with no shipping for balance refilling.
 *
 * @param array<string> $cart            Cart data
 * @param array<string> $auth            Authorization data
 * @param int|null      $parent_order_id Parent order identificatior
 * @param float         $total           Total price
 * @param bool          $result          Whether to allow placing order or not
 *
 * @return void
 *
 * @see \fn_allow_place_order()
 */
function fn_vendor_debt_payout_allow_place_order_post(array $cart, array $auth, $parent_order_id, $total, &$result)
{
    if ($result || empty($cart['is_refill_balance'])) {
        return;
    }

    $result = true;
}
