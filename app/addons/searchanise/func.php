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
use Tygh\Enum\ProductTracking;
use Tygh\Enum\ProductFeatures;
use Tygh\Tools\Math;
use Tygh\Http;
use Tygh\Registry;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\YesNo;
use Tygh\Enum\ProductFilterProductFieldTypes;
use Tygh\Enum\NotificationSeverity;
use Tygh\Addons\ProductVariations\Product\Type\Type as ProductVariationTypes;
use Tygh\Addons\MasterProducts\ServiceProvider as MasterProductsServiceProvider;
use Tygh\Addons\ProductVariations\ServiceProvider as ProductVariationsServiceProvider;
use Tygh\Enum\Addons\Searchanise\ImportStatuses;
use Tygh\Enum\Addons\Searchanise\QueueActions;
use Tygh\Enum\Addons\Searchanise\QueueStatuses;
use Tygh\Enum\Addons\Searchanise\AddonStatuses;
use Tygh\Enum\Addons\Searchanise\SignupStatuses;
use Tygh\Enum\Addons\Searchanise\ServerErrors;
use Tygh\Enum\SiteArea;

defined('BOOTSTRAP') or die('Access denied');

//
// Configurable constants
//
fn_define('SE_SEARCH_TIMEOUT', 3); //Search and navigation request timeout
fn_define('SE_REQUEST_TIMEOUT', 10); // API request timeout
fn_define('SE_PRODUCTS_PER_PASS', 100); // Number of products submitted in a single API request during a full catalog synchronization
fn_define('SE_USE_RELEVANCE_AS_DEFAULT_SORTING', YesNo::YES); // Y or N  (Set Sorting by relevance as the default sorting on product search in the storefront)

//
// Not configurable constants
//
fn_define('SE_VERSION', '1.3');
fn_define('SE_IMAGE_SIZE', 100);
fn_define('SE_MEMORY_LIMIT', 512);
fn_define('SE_MAX_ERROR_COUNT', 15);
fn_define('SE_MAX_PROCESSING_TIME', 720);
fn_define('SE_MAX_SEARCH_REQUEST_LENGTH', '8000');
fn_define('SE_SERVICE_URL', 'https://searchserverapi.com');
fn_define('SE_PLATFORM', 'cs-cart4');
fn_define('SE_CONTACT_EMAIL', 'feedback@searchanise.com');

fn_define('SE_NOT_DATA', 'N;');
fn_define('SE_PRICE_USERGROUP_PREFIX', 'price_');
fn_define('SE_GROUPED_PREFIX', 'se_grouped_');

// phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
// phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification

function fn_searchanise_dispatch_assign_template($controller, $mode, $area)
{
    $_prices = [];
    if (AREA != 'C') {
        return;
    }

    if ((is_array(Tygh::$app['session']['auth']['usergroup_ids']) || Tygh::$app['session']['auth']['usergroup_ids'] instanceof \Countable ? count(Tygh::$app['session']['auth']['usergroup_ids']) : 0) > USERGROUP_GUEST) {
        foreach (Tygh::$app['session']['auth']['usergroup_ids'] as $usergroup_id) {
            $_prices[] = SE_PRICE_USERGROUP_PREFIX . $usergroup_id;
        }

        Tygh::$app['view']->assign('searchanise_prices', join('|', $_prices));
    }

    fn_se_check_import_is_done(fn_se_get_company_id(), CART_LANGUAGE);

    Tygh::$app['view']->assign('searchanise_api_key', fn_se_get_api_key(fn_se_get_company_id(), CART_LANGUAGE));
    Tygh::$app['view']->assign('searchanise_search_allowed', fn_se_is_search_allowed(fn_se_get_company_id(), CART_LANGUAGE) ? YesNo::YES : YesNo::NO);

    // Fix for Twigmo
    if (Tygh::$app['view']->getTemplateVars('searchanise_search_allowed') == YesNo::YES) {
        Tygh::$app['view']->assign('searchanise_import_status', ImportStatuses::DONE);
    }
}

/**
 * Check if the search is available
 *
 * @param number $company_id  Company identifier
 * @param string $lang_code   2 letters language code
 *
 * @return boolean
 */
function fn_se_is_search_allowed($company_id, $lang_code = CART_LANGUAGE)
{
    $searchanise_import_status = fn_se_get_import_status($company_id, $lang_code);

    return in_array($searchanise_import_status, [
        ImportStatuses::QUEUED,
        ImportStatuses::PROCESSING,
        ImportStatuses::SENT,
        ImportStatuses::DONE,
    ]);
}

function fn_se_get_active_company_ids($joined = false)
{
    static $companies = [];

    if (fn_allowed_for('MULTIVENDOR')) {
        $null_company = [0]; //workaround;
        $companies = array_merge($null_company, db_get_fields("SELECT company_id FROM ?:companies WHERE status = ?s", ObjectStatuses::ACTIVE));
    }

    return $joined? join('|', $companies) : $companies;
}

function fn_se_check_company_id($company_id = null)
{
    if (!fn_allowed_for('ULTIMATE')) {
        $company_id = 0;
    }

    return $company_id;
}

function fn_se_get_company_id()
{
    $company_id = 0;
    if (fn_allowed_for('ULTIMATE')) {
        if (Registry::get('runtime.company_id')) {
            $company_id = Registry::get('runtime.company_id');
        }
        if (Registry::get('runtime.forced_company_id')) {
            $company_id = Registry::get('runtime.forced_company_id');
        }
    }

    return $company_id;
}

function fn_se_get_all_settings($update = false)
{
    static $settings = [];

    if (empty($settings) || !empty($update)) {
        $_settings = db_get_array("SELECT * FROM ?:se_settings");
        foreach ($_settings as $s) {
            $settings[$s['company_id']][$s['lang_code']][$s['name']] = $s['value'];
        }

        if (empty($settings)) {
            $settings = [null];
        }
    }

    return $settings;
}

function fn_se_get_setting($name, $company_id, $lang_code)
{
    $settings = fn_se_get_all_settings();

    return $settings[$company_id][$lang_code][$name] ?? null;
}

function fn_se_set_setting($name, $company_id, $lang_code, $value)
{
    if (empty($name) || empty($lang_code)) {
        return;
    }

    db_replace_into('se_settings', [
        'name'       => $name,
        'company_id' => $company_id,
        'lang_code'  => $lang_code,
        'value'      => $value,
    ]);

    fn_se_get_all_settings(true);// call to update cache
}

function fn_se_get_simple_setting($name)
{
    return fn_se_get_setting($name, fn_se_get_company_id(), DEFAULT_LANGUAGE);
}

function fn_se_set_simple_setting($name, $value)
{
    if (empty($name)) {
        return;
    }

    fn_se_set_setting($name, fn_se_get_company_id(), DEFAULT_LANGUAGE, $value);
}

function fn_se_set_import_status($status, $company_id, $lang_code)
{
    fn_se_set_setting('import_status', $company_id, $lang_code, $status);
}

function fn_se_get_import_status($company_id, $lang_code)
{
    return fn_se_get_setting('import_status', $company_id, $lang_code);
}

function fn_se_get_parent_private_key($company_id, $lang_code)
{
    $settings = fn_se_get_all_settings();
    if (!empty($settings[$company_id])) {
        if (!empty($settings[$company_id][$lang_code]['parent_private_key'])) {
            return $settings[$company_id][$lang_code]['parent_private_key'];
        } else {
            foreach ((array) $settings[$company_id] as $lang_settings) {
                if (!empty($lang_settings['parent_private_key'])) {
                    return $lang_settings['parent_private_key'];
                }
            }
        }
    }

    return null;
}

function fn_se_get_private_key($company_id, $lang_code)
{
    return fn_se_get_setting('private_key', $company_id, $lang_code);
}

function fn_se_get_api_key($company_id, $lang_code)
{
    return fn_se_get_setting('api_key', $company_id, $lang_code);
}

function fn_se_get_engines_count($company_id = null)
{
    return db_get_field("SELECT count(*) FROM ?:se_settings WHERE name = 'private_key' AND company_id = ?i", $company_id);
}

function fn_se_is_registered()
{
    return (bool) db_get_field("SELECT count(*) FROM ?:se_settings WHERE name = 'parent_private_key'");
}

function fn_se_add_action($action, $data = null, $company_id = null, $lang_code = null)
{
    if (fn_se_is_registered() == false) {
        return;
    }

    $data = [serialize((array) $data)];
    $company_id = fn_se_check_company_id($company_id);

    if ($action == QueueActions::PREPARE_FULL_IMPORT) {
        if (is_null($company_id)) {
            if (empty($lang_code)) {
                db_query('TRUNCATE ?:se_queue');
            } else {
                db_query('DELETE FROM ?:se_queue WHERE lang_code = ?s', $lang_code);
            }
        } else {
            db_query('DELETE FROM ?:se_queue WHERE company_id = ?i ?p',
                $company_id,
                empty($lang_code) ? '' : db_quote('AND lang_code = ?s', $lang_code)
            );
        }
    }

    $engines_data = fn_se_get_engines_data($company_id, $lang_code);

    foreach ($data as $d) {
        foreach ($engines_data as $engine_data) {
            db_query('DELETE FROM ?:se_queue WHERE status = ?s AND action = ?s AND data = ?s AND company_id = ?i AND lang_code = ?s', QueueStatuses::PENDING, $action, $d, $engine_data['company_id'], $engine_data['lang_code']);

            if (fn_se_get_import_status($engine_data['company_id'], $engine_data['lang_code']) === ImportStatuses::SUSPENDED) {
                continue;
            }

            db_query('INSERT INTO ?:se_queue ?e', [
                'action'     => $action,
                'data'       => $d,
                'company_id' => $engine_data['company_id'],
                'lang_code'  => $engine_data['lang_code'],
            ]);
        }
    }
}

function fn_se_add_chunk_product_action($action, $product_ids, $company_id = null, $lang_code = null)
{
    if (!empty($product_ids)) {
        $product_ids = array_chunk($product_ids, SE_PRODUCTS_PER_PASS);

        foreach ($product_ids as $_product_ids) {
            fn_se_add_action($action, $_product_ids, $company_id, $lang_code);
        }
    }

    return true;
}

function fn_searchanise_update_product_amount($new_amount, $product_id, $cart_id, $tracking)
{
    if ($tracking === ProductTracking::DO_NOT_TRACK) {
        return;
    }

    // track whole product inventory only - we don't use combinations yet
    fn_se_add_action(QueueActions::UPDATE_PRODUCTS, (int) $product_id);

    $united_product_ids = fn_se_get_united_product_ids((array) $product_id);

    if (empty($united_product_ids)) {
        return;
    }

    foreach ($united_product_ids as $parent_id) {
        fn_se_add_action(QueueActions::UPDATE_PRODUCTS, (int) $parent_id);
    }
}

/**
* Update product data (running after fn_update_product() function)
*
* @param array   $product_data Product data
* @param int     $product_id   Product integer identifier
* @param string  $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
* @param boolean $create       Flag determines if product was created (true) or just updated (false).
*
* @see \fn_update_product()
*/
function fn_searchanise_update_product_post(array $product_data, $product_id, $lang_code, $create)
{
    $united_product_ids = fn_se_get_united_product_ids((array) $product_id);
    if (!empty($united_product_ids)) {
        foreach ($united_product_ids as $product_id) {
            fn_se_add_action(QueueActions::UPDATE_PRODUCTS, (int) $product_id);
        }
    }
}

function fn_searchanise_clone_product($product_id, $pid)
{
    $united_product_ids = fn_se_get_united_product_ids((array) $pid);
    if (!empty($united_product_ids)) {
        foreach ($united_product_ids as $product_id) {
            fn_se_add_action(QueueActions::UPDATE_PRODUCTS, (int) $product_id);
        }
    }
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
function fn_searchanise_global_update_products($table, $field, $value, $type, $msg, $update_data)
{
    if (!empty($update_data['product_ids'])) {
        $united_product_ids = fn_se_get_united_product_ids((array) $update_data['product_ids']);
        if (!empty($united_product_ids)) {
            foreach ($united_product_ids as $product_id) {
                fn_se_add_action(QueueActions::UPDATE_PRODUCTS, (int) $product_id);
            }
        }
    } elseif (fn_se_get_import_status(fn_se_get_company_id(), CART_LANGUAGE) !== ImportStatuses::SUSPENDED) {
        fn_se_queue_import();
    }
}

/**
 * Check product delete (run before product is deleted)
 *
 * @param int     $product_id Product identifier
 * @param boolean $status     Flag determines if product can be deleted, if false product is not deleted
 */
function fn_searchanise_delete_product_pre($product_id, $status)
{
    $united_product_ids = fn_se_get_united_product_ids((array) $product_id, false);
    if (!empty($united_product_ids)) {
        Registry::set('se_united_product_ids_' . $product_id, $united_product_ids);
    }
}

/**
 * Process product delete (run after product is deleted)
 *
 * @param int  $product_id      Product identifier
 * @param bool $product_deleted True if product was deleted successfully, false otherwise
 */
function fn_searchanise_delete_product_post($product_id, $product_deleted)
{
    if ($product_deleted) {
        fn_se_add_action(QueueActions::DELETE_PRODUCTS, (int) $product_id);
    }

    $united_product_ids = Registry::get('se_united_product_ids_' . $product_id);
    if (!empty($united_product_ids)) {
        if ($product_deleted) {
            foreach ($united_product_ids as $parent_id) {
                // Update parent products
                fn_se_add_action(QueueActions::UPDATE_PRODUCTS, (int) $parent_id);
            }
        }

        Registry::set('se_united_product_ids_' . $product_id, null);
    }
}

/**
 * Update category data (running after fn_update_category() function)
 *
 * @param array  $category_data Category data
 * @param int    $category_id   Category identifier
 * @param string $lang_code     Two-letter language code (e.g. 'en', 'ru', etc.)
 */
function fn_searchanise_update_category_post(array $category_data, $category_id, $lang_code)
{
    $product_ids = db_get_fields('SELECT product_id FROM ?:products_categories WHERE category_id = ?i', $category_id);

    if (!empty($category_data['usergroup_to_subcats']) && $category_data['usergroup_to_subcats'] == YesNo::YES) {
        $id_path = db_get_field('SELECT id_path FROM ?:categories WHERE category_id = ?i', $category_id);
        $product_ids = array_merge($product_ids, db_get_fields("SELECT pc.product_id FROM ?:products_categories AS pc LEFT JOIN ?:categories AS c ON pc.category_id = c.category_id WHERE id_path LIKE ?l", "$id_path/%"));
    }

    fn_se_add_chunk_product_action(QueueActions::UPDATE_PRODUCTS, $product_ids);
    if (!empty($category_data['status']) && $category_data['status'] != ObjectStatuses::ACTIVE) {
        fn_se_add_action(QueueActions::DELETE_CATEGORIES, (int) $category_id);
    } else {
        fn_se_add_action(QueueActions::UPDATE_CATEGORIES, (int) $category_id);
    }
}

/**
 * Actions after category and its related data removal
 *
 * @param int     $category_id  Category identifier to delete
 * @param boolean $recurse      Flag that defines if category should be deleted recursively
 * @param array   $category_ids Category identifiers that were removed
 *
 * @see \fn_delete_category()
 */
function fn_searchanise_delete_category_post($category_id, $recurse, array $category_ids)
{
    fn_se_add_action(QueueActions::DELETE_CATEGORIES, $category_ids);
}

/**
 * Actions after page update
 *
 * @param array  $page_data     Page data
 * @param int    $page_id       Page idetifier, if equals zero new page will be created
 * @param string $lang_code     2 letters language code
 * @param bool   $create        True if page was created, falce otherwise
 * @param array  $old_page_data Page data before update
 *
 * @see \fn_update_page()
 */
function fn_searchanise_update_page_post(array $page_data, $page_id, $lang_code, $create, array $old_page_data)
{
    if (!empty($page_data['status']) && $page_data['status'] != ObjectStatuses::ACTIVE) {
        fn_se_add_action(QueueActions::DELETE_PAGES, (int) $page_id);
    } else {
        fn_se_add_action(QueueActions::UPDATE_PAGES, (int) $page_id);
    }
}

function fn_searchanise_clone_page($page_id, $new_page_id)
{
    fn_se_add_action(QueueActions::UPDATE_PAGES, (int) $new_page_id);
}

function fn_searchanise_delete_page($page_id)
{
    fn_se_add_action(QueueActions::DELETE_PAGES, (int) $page_id);
}

/**
 * Hook is executed after changing add-on status (i.e. after add-on enabling or disabling).
 *
 * @param string                  $addon             Add-on name
 * @param string                  $status            New addon status - "A" for enabled, "D" for disabled
 * @param bool                    $show_notification Display notification if set to true
 * @param bool                    $on_install        If status was changed right after install process
 * @param bool                    $allow_unmanaged   Whether to allow change status for unmanaged addons in non-console environment
 * @param string                  $old_status        Previous addon status - "A" for enabled, "D" for disabled
 * @param \Tygh\Addons\AXmlScheme $scheme            Add-on scheme
 *
 * @see fn_update_addon_status()
 */
function fn_searchanise_update_addon_status_post($addon, $status, $show_notification, $on_install, $allow_unmanaged, $old_status, $scheme)
{
    $reindexation_addons = [
        'age_verification',
        'product_variations',
        'master_products',
    ];

    if (in_array($addon, $reindexation_addons) && $status == ObjectStatuses::ACTIVE && $on_install == false) {
        fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('text_se_re_indexation_required', [
            '[link]' => fn_url('addons.update?addon=searchanise')
        ]));
    }
}

function fn_searchanise_tools_change_status($params, $result)
{
    if (fn_se_is_registered() == false) {
        return;
    }

    if ($params['table'] == 'products' && !empty($result)) {
        $united_products = fn_se_get_united_product_ids((array) $params['id']);

        if (!empty($united_products)) {
            foreach ($united_products as $product_id) {
                fn_se_add_action(QueueActions::UPDATE_PRODUCTS, (int) $product_id);
            }
        }
    } elseif ($params['table'] == 'product_filters' && !empty($result) && !empty($params['id_name']) && $params['id_name'] == 'filter_id' && !empty($params['id'])) {
        // It used exist function-hook
        if ($params['status'] == ObjectStatuses::ACTIVE) {
            fn_searchanise_update_product_filter_post(null, $params['id']);
        } elseif ($params['status'] == ObjectStatuses::DISABLED) {
            fn_searchanise_delete_product_filter_pre($params['id']);
        }
    } elseif ($params['table'] == 'categories' && !empty($result) && !empty($params['id_name']) && $params['id_name'] == 'category_id' && !empty($params['id'])) {
        // It used exist function-hook
        fn_searchanise_update_category_post([
            'status' => $params['status']
        ], $params['id'], DESCR_SL);
    } elseif ($params['table'] == 'languages' && !empty($result)) {
        if ($params['status'] == ObjectStatuses::ACTIVE) {
            fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('text_se_re_indexation_required', [
                '[link]' => fn_url('addons.update?addon=searchanise')
            ]));
        }
    }
}

/**
 * Event: uninstall add-on
 * Get data of all engines and send request about the add-on status as deleted
 */
function fn_searchanise_send_deleted_status()
{
    foreach (fn_se_get_engines_data() as $engine) {
        if (!empty($engine['private_key'])) {
            fn_se_send_request('/api/state/update/json', $engine['private_key'], ['addon_status' => 'deleted']);
        }
    }
}

function fn_se_get_engines_data($company_id = null, $lang_code = null, $skip_available_check = false)
{
    static $engines_data = [];

    $company_id = fn_se_check_company_id($company_id);

    if (empty($engines_data) || !empty($skip_available_check)) {
        $languages = [];
        $available = $skip_available_check ? '1' : "status = 'A'";
        $all_languages = db_get_array('SELECT * FROM ?:languages WHERE ?p', $available);

        if (fn_allowed_for('ULTIMATE')) {
            [$storefronts, ] = Tygh::$app['storefront.repository']->find([
                'get_total' => false,
            ]);

            foreach ($storefronts as $storefront) {
                $language_ids = $storefront->getLanguageIds();
                $s_c_ids = $storefront->getCompanyIds();

                foreach ($all_languages as $l) {
                    // Check if language is shared for any storefront or not
                    $exist_lang_id = db_get_field('SELECT language_id FROM ?:storefronts_languages WHERE language_id = ?i', $l['lang_id']);

                    if (empty($exist_lang_id) || in_array($l['lang_id'], $language_ids)) {
                        $languages[] = [
                            'lang_code'  => $l['lang_code'],
                            'company_id' => $s_c_ids[0], // one to one relation for ULT
                            'storefront' => $storefront->url,
                            'status'     => $l['status'],
                            'name'       => $l['name'],
                        ];
                    }
                }
            }
        } else {
            $languages = $all_languages;
        }

        foreach ($languages as $l) {
            $l_code = $l['lang_code'];

            if (fn_allowed_for('ULTIMATE')) {
                $c_id = $l['company_id'];
                $url = 'http://' . $l['storefront'] . '/?sl=' . $l_code;
            } else {
                $c_id = 0;
                $url = 'http://' . Registry::get('config.http_host') . Registry::get('config.http_path') . '/?sl=' . $l_code;
            }

            $engines_data[$c_id][$l_code] = [
                'lang_code'          => $l_code,
                'status'             => $l['status'],
                'company_id'         => $c_id,
                'language_name'      => $l['name'],
                'url'                => $url,
                'api_key'            => fn_se_get_api_key($c_id, $l_code),
                'private_key'        => fn_se_get_private_key($c_id, $l_code),
                'import_status'      => fn_se_get_import_status($c_id, $l_code),
                'parent_private_key' => fn_se_get_parent_private_key($c_id, $l_code),
            ];
        }
    }

    $return = [];
    foreach ($engines_data as $s_keys_data) {
        foreach ($s_keys_data as $s_l_keys_data) {
            if (!is_null($lang_code) && !is_null($company_id) && $s_l_keys_data['lang_code'] == $lang_code && $s_l_keys_data['company_id'] == $company_id) {
                $return[] = $s_l_keys_data;
            } elseif (!is_null($lang_code) && is_null($company_id) && $s_l_keys_data['lang_code'] == $lang_code) {
                $return[] = $s_l_keys_data;
            } elseif (is_null($lang_code) && !is_null($company_id) && $s_l_keys_data['company_id'] == $company_id) {
                $return[] = $s_l_keys_data;
            } elseif (is_null($lang_code) && is_null($company_id)) {
                $return[] = $s_l_keys_data;
            }
        }
    }

    if (!empty($skip_available_check)) {
        $engines_data = [];
    }

    return $return;
}

/**
 * Returns signup status
 *
 * @return string (available values: 'done'|'failed'|timestamp)
 */
function fn_se_get_signup_status()
{
    $status = fn_se_get_simple_setting('signup_status');

    return !empty($status) ? $status : '';
}

function fn_se_signup($_company_id = null, $_lang_code = null, $show_notification = true)
{
    @ignore_user_abort(1);
    @set_time_limit(3600);

    $connected = $is_showed = false;

    if ((!empty($_company_id) || !empty($_lang_code)) && fn_se_is_registered() == false) {
        return false;
    }

    $email = Registry::ifGet('user_info.email', db_get_field("SELECT email FROM ?:users WHERE user_id = 1"));

    $engines_data = fn_se_get_engines_data($_company_id, $_lang_code);

    fn_se_set_simple_setting('signup_status', TIME);

    foreach ($engines_data as $engine_data) {
        $lang_code          = $engine_data['lang_code'];
        $company_id         = $engine_data['company_id'];
        $private_key        = $engine_data['private_key'];
        $parent_private_key = fn_se_get_parent_private_key($company_id, $lang_code);

        if (!empty($private_key)) {
            continue;
        }

        if ($show_notification == true && empty($is_showed)) {
            fn_se_echo_connect_progress('Connecting to Searchanise..');
            $is_showed = true;
        }

        $response = Http::post(SE_SERVICE_URL . '/api/signup/json', [
            'url'                => $engine_data['url'],
            'email'              => $email,
            'version'            => SE_VERSION,
            'language'           => $lang_code,
            'parent_private_key' => $parent_private_key,
            'platform'           => SE_PLATFORM,
        ]);

        if ($show_notification == true) {
            fn_se_echo_connect_progress('.');
        }

        if (!empty($response)) {
            $response = fn_se_parse_response($response, true);

            /**
             * @psalm-suppress InvalidArrayAccess
             */
            if (!empty($response['keys']['api']) && !empty($response['keys']['private'])) {
                $api_key = (string) $response['keys']['api'];
                $private_key = (string) $response['keys']['private'];

                if (empty($api_key) || empty($private_key)) {
                    return false;
                }

                if (empty($parent_private_key)) {
                    fn_se_set_setting('parent_private_key', $company_id, $lang_code, $private_key);
                }

                fn_se_set_setting('api_key', $company_id, $lang_code, $api_key);
                fn_se_set_setting('private_key', $company_id, $lang_code, $private_key);

                $connected = true;
            } else {
                if (!fn_allowed_for('ULTIMATE')) {
                    if ($show_notification == true) {
                        fn_se_echo_connect_progress(' Error<br />');
                    }

                    return false;
                }
            }
        }

        fn_se_set_import_status(ImportStatuses::NONE, $company_id, $lang_code);
    }

    if ($connected == true && $show_notification == true) {
        fn_se_echo_connect_progress(' Done<br />');
        fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('text_se_just_connected'));
    }

    fn_se_set_simple_setting('signup_status', $connected ? SignupStatuses::DONE : SignupStatuses::FAILED);

    fn_set_hook('searchanise_signup_post', $connected);

    return $connected;
}

function fn_se_echo_connect_progress($text)
{
    if (!defined('AJAX_REQUEST')) {
        fn_echo($text);
    }
}

function fn_se_queue_import($company_id = null, $lang_code = null, $show_notification = true)
{
    if (fn_se_is_registered() == false) {
        return;
    }

    $engines_data = fn_se_get_engines_data($company_id, $lang_code);

    foreach ($engines_data as $engine_data) {
        fn_se_set_import_status(ImportStatuses::QUEUED, $engine_data['company_id'], $engine_data['lang_code']);
        fn_se_add_action(QueueActions::PREPARE_FULL_IMPORT, null, $engine_data['company_id'], $engine_data['lang_code']);
        fn_se_send_addon_version($engine_data['company_id'], $engine_data['lang_code']);
    }

    if ($show_notification == true) {
        fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('text_se_import_status_queued'));
    }
}

function fn_searchanise_database_restore($files)
{
    if (fn_se_is_registered() == false) {
        return;
    }

    fn_set_notification(NotificationSeverity::WARNING, __('notice'), __('text_se_database_restore_notice', [
        '[link]' => fn_url('addons.update?addon=searchanise')
    ]));

    return true;
}

function fn_se_get_facet_valid_locations()
{
    return [
        'index.index',
        'products.search',
        'categories.view',
        'product_features.view'
    ];
}

function fn_se_get_valid_sortings()
{
    return [
        'position',
        'product',
        'price',
        'relevance',
        'timestamp',
        'null',
        'popularity', //TODO: server may have not actual `popularity` values.
        'bestsellers',
        'on_sale'
    ];
}

function fn_se_check_product_filter_block()
{
    return Block::instance()->isBlockTypeActiveOnCurrentLocation('product_filters');
}

function fn_searchanise_send_search_request($params, $lang_code = CART_LANGUAGE)
{
    $company_id = fn_se_get_company_id();
    $api_key = fn_se_get_api_key($company_id, $lang_code);
    if (empty($api_key)) {
        return;
    }

    $default_params = [
        'items'       => 'true',
        'facets'      => 'true',
        'output'      => 'json',
    ];

    $params = array_merge($default_params, $params);
    if (empty($params['restrictBy'])) {
        unset($params['restrictBy']);
    }

    if (empty($params['union'])) {
        unset($params['union']);
    }

    $query = http_build_query($params);

    if (fn_se_check_debug()) {
        fn_print_r(SE_SERVICE_URL . '/search?api_key=' . $api_key . '&' . http_build_query($params), $params);
    }

    Registry::set('log_cut', true);
    if (strlen($query) > SE_MAX_SEARCH_REQUEST_LENGTH && fn_check_curl()) {
        $received = Http::post(SE_SERVICE_URL . '/search?api_key=' . $api_key, $params, [
            'timeout' => SE_SEARCH_TIMEOUT
        ]);
    } else {
        $params['api_key'] = $api_key;

        $received = Http::get(SE_SERVICE_URL . '/search', $params, [
            'timeout' => SE_SEARCH_TIMEOUT
        ]);
    }

    if (empty($received)) {
        return false;
    }

    $result = json_decode(trim($received), true);
    if (fn_se_check_debug()) {
        fn_print_r($result);
    }

    if (isset($result['error'])) {
        if ($result['error'] == ServerErrors::NEED_RESYNC_YOUR_CATALOG) {
            fn_se_queue_import($company_id, $lang_code, false);

            return false;
        }
    }

    if (empty($result) || !is_array($result) || !isset($result['totalItems'])) {
        return false;
    }

    return $result;
}

function fn_searchanise_products_sorting(&$sorting, $simple_mode)
{
    if (
        AREA == 'C' &&
        !empty($_REQUEST['search_performed']) &&
        SE_USE_RELEVANCE_AS_DEFAULT_SORTING == YesNo::YES &&
        fn_se_is_search_allowed(fn_se_get_company_id(), CART_LANGUAGE)
    ) {
        $sorting = array_merge(['relevance' => ['description' => __('se_relevance'), 'default_order' => 'asc', 'desc' => false]], $sorting);
        $list = array_merge(Registry::get('settings.Appearance.available_product_list_sortings'), ['relevance-asc' => YesNo::YES]);
        Registry::set('settings.Appearance.available_product_list_sortings', $list);
        if (Tygh::$app->hasInstance('view')) {
            Tygh::$app['view']->assign('settings', Registry::get('settings'));
        }
    }
}

function fn_se_prepare_request_params($params)
{
    $_prices = [];
    $restrict_by = $query_by = $union = [];

    //
    // Hide products with empty categories and wrong usergroup categories
    //
    $restrict_by['empty_categories'] = YesNo::NO;
    $restrict_by['category_usergroup_ids'] = join('|', Tygh::$app['session']['auth']['usergroup_ids']);

    //
    // Visibility
    //
    $restrict_by['status'] = ObjectStatuses::ACTIVE;
    $restrict_by['usergroup_ids'] = join('|', Tygh::$app['session']['auth']['usergroup_ids']);

    if (
        Registry::get('settings.General.inventory_tracking') !== YesNo::NO
        && Registry::get('settings.General.show_out_of_stock_products') === YesNo::NO
        && SiteArea::isStorefront(AREA)
    ) {
        $restrict_by['amount'] = '1,';
    }

    if (Registry::ifGet('addons.vendor_data_premoderation.status', ObjectStatuses::NEW_OBJECT) == ObjectStatuses::ACTIVE) {
        $restrict_by['approved'] = YesNo::YES;
    }

    if (Registry::ifGet('addons.age_verification.status', ObjectStatuses::NEW_OBJECT) == ObjectStatuses::ACTIVE) {
        if (!empty(Tygh::$app['session']['auth']['age']) && AREA == 'C') {
            $restrict_by['age_limit'] = '0,' . Tygh::$app['session']['auth']['age'];
        } else {
            $restrict_by['age_limit'] = ',0';
        }
    }

    if (Registry::ifGet('addons.product_variations.status', ObjectStatuses::NEW_OBJECT) == ObjectStatuses::ACTIVE) {
        $restrict_by['product_type'] = ProductVariationTypes::PRODUCT_TYPE_SIMPLE;
    }

    //
    // Company_id
    //
    if (fn_allowed_for('MULTIVENDOR')) {
        $restrict_by['active_company'] = YesNo::YES;

        if (Registry::ifGet('addons.master_products.status', ObjectStatuses::NEW_OBJECT) === ObjectStatuses::ACTIVE) {
            $restrict_by['master_product_status'] = ObjectStatuses::ACTIVE;

            if (!empty($params['company_id'])) {
                $restrict_by['company_id'] = $params['company_id'];
            } else {
                $restrict_by['company_id'] = 0;
            }
        } elseif (!empty($params['company_id'])) {
            $restrict_by['company_id'] = $params['company_id'];
        }
    }

    //
    // Features
    //
    if (!empty($params['features_hash'])) {
        $selected_filters = fn_parse_filters_hash($params['features_hash']);
        if (!empty($selected_filters)) {
            [$filters, ] = fn_get_product_filters(['filter_id' => array_keys($selected_filters), 'status' => ObjectStatuses::ACTIVE, 'get_variants' => false], 0);

            foreach ($filters as $filter) {
                $filter_id = $filter['filter_id'];
                $feature_id = $filter['feature_id'];
                if (!empty($feature_id)) {
                    $restrict_by["feature_{$feature_id}" ] = join('|', $selected_filters[$filter_id]);
                } elseif ($filter['field_type'] == ProductFilterProductFieldTypes::IN_STOCK) {
                    $restrict_by["in_stock"] = $selected_filters[$filter_id][0];
                } elseif ($filter['field_type'] == ProductFilterProductFieldTypes::FREE_SHIPPING) {
                    $restrict_by['free_shipping'] = $selected_filters[$filter_id][0];
                } elseif ($filter['field_type'] == ProductFilterProductFieldTypes::VENDOR) {
                    $restrict_by['company_id'] = join('|', $selected_filters[$filter_id]);
                }
            }
        }
    }

    //
    // Timestamp
    //
    if (!empty($params['period']) && $params['period'] != ObjectStatuses::ACTIVE) {
        [$params['time_from'], $params['time_to']] = fn_create_periods($params);
        $restrict_by['timestamp'] = "{$params['time_from']},{$params['time_to']}";
    }

    //
    // Price Union
    //
    if ((is_array(Tygh::$app['session']['auth']['usergroup_ids']) || Tygh::$app['session']['auth']['usergroup_ids'] instanceof \Countable ? count(Tygh::$app['session']['auth']['usergroup_ids']) : 0) > 1) {
        foreach (Tygh::$app['session']['auth']['usergroup_ids'] as $usergroup_id) {
            $_prices[] = SE_PRICE_USERGROUP_PREFIX . $usergroup_id;
        }

        $union['price']['min'] = join('|', $_prices);
    }

    //
    // Price
    //
    $is_price_from = (isset($params['price_from']) && fn_is_numeric($params['price_from']));
    $is_price_to   = (isset($params['price_to']) && fn_is_numeric($params['price_to']));

    if ($is_price_from || $is_price_to) {
        $restrict_by['price'] = (($is_price_from)? $params['price_from'] : '') . ',' . (($is_price_to)? $params['price_to'] : '');
    }

    //
    // Weight
    //
    $is_weight_from = (isset($params['weight_from']) && fn_is_numeric($params['weight_from']));
    $is_weight_to   = (isset($params['weight_to']) && fn_is_numeric($params['weight_to']));

    if ($is_weight_from || $is_weight_to) {
        $restrict_by['weight'] = (($is_weight_from)? $params['weight_from'] : '') . ',' . (($is_weight_to)? $params['weight_to'] : '');
    }

    //
    // Amount
    //
    $is_amount_from = (isset($params['amount_from']) && fn_is_numeric($params['amount_from']));
    $is_amount_to   = (isset($params['amount_to']) && fn_is_numeric($params['amount_to']));

    if ($is_amount_from || $is_amount_to) {
        $restrict_by['amount'] = (($is_amount_from)? $params['amount_from'] : '') . ',' . (($is_amount_to)? $params['amount_to'] : '');
    }

    //
    // Popularity
    //
    $is_popularity_from = (isset($params['popularity_from']) && fn_is_numeric($params['popularity_from']));
    $is_popularity_to   = (isset($params['popularity_to']) && fn_is_numeric($params['popularity_to']));

    if ($is_popularity_from || $is_popularity_to) {
        $restrict_by['popularity'] = (($is_popularity_from)? $params['popularity_from'] : '') . ',' . (($is_popularity_to)? $params['popularity_to'] : '');
    }

    if (isset($params['pcode']) && fn_string_not_empty($params['pcode'])) {
        if (empty($params['pcode_from_q'])) { // FIXME This is a workaround. See @1-14957 for details.
            $query_by['product_code'] = trim($params['pcode']);
        }
    }

    /**
     * Process final product data
     *
     * @param array $params      Search parameters
     * @param array $restrict_by Prepared filter criteria
     * @param array $query_by    Prepared text-search criteria
     * @param array $union       Prepared union criteria
     */
    fn_set_hook('se_prepare_request_params_post', $params, $restrict_by, $query_by, $union);

    return [$restrict_by, $query_by, $union];
}

function fn_searchanise_get_products(&$params, &$fields, &$sortings, &$condition, &$join, &$sorting, &$group_by, &$lang_code, &$having)
{
    if (!empty($params['for_searchanise']) && $params['area'] == 'A') {
        if (empty($fields['position'])) {
            $fields['position'] = 'products_categories.position';
        }

        /*
         * Support for add-on "Bestsellers & On-Sale Products".
         */
        if (Registry::ifGet('addons.bestsellers.status', ObjectStatuses::NEW_OBJECT) == ObjectStatuses::ACTIVE) {
            if (in_array('sales', $params['extend'])) { // Get count of sales for "Sort by Bestselling".
                $fields['sales'] = 'sales.amount as sales';
                $join .= db_quote(' LEFT JOIN ?:product_sales as sales ON sales.product_id = products.product_id');
            }

            if (in_array('discount', $params['extend'])) { // Get value of discount for "Sort by discount".
                $fields['discount'] = '(CASE'
                    . ' WHEN products.list_price > 0 THEN 100 - MIN(prices.price) * 100 / products.list_price'
                    . ' ELSE 0'
                    . ' END) AS discount';
            }
        }

        /**
         * Suport for add-on "Age Verification"
         */
        if (Registry::ifGet('addons.age_verification.status', ObjectStatuses::NEW_OBJECT) == ObjectStatuses::ACTIVE) {
            fn_age_verification_extend_product_fields($fields, 'need_age_verification', 'searchanise_age_limit');
        }
    }
}

function fn_searchanise_get_products_before_select(&$params, &$join, &$condition, &$u_condition, &$inventory_condition, &$sortings, &$total, &$items_per_page, &$lang_code, &$having)
{
    if (
        AREA != 'C' ||
        empty($params['search_performed']) ||
        empty($params['dispatch']) ||
        $params['dispatch'] != 'products.search' && $params['dispatch'] != 'companies.products' ||
        (!empty($_REQUEST['sort_by']) && !in_array($_REQUEST['sort_by'], fn_se_get_valid_sortings())) ||
        fn_se_check_disabled() ||
        !empty($params['disable_searchanise']) ||
        !fn_se_is_search_allowed(fn_se_get_company_id(), CART_LANGUAGE)
    ) {
        return;
    }

    [$restrict_by, $query_by, $union] = fn_se_prepare_request_params($params);

    //
    // Categories
    //
    if (!empty($params['cid'])) {
        $cids = is_array($params['cid']) ? $params['cid'] : [$params['cid']];

        $c_condition = '';

        if (AREA == 'C') {
            $_c_statuses = [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN];// Show enabled categories
            $cids = db_get_fields("SELECT a.category_id FROM ?:categories as a WHERE a.category_id IN (?n) AND a.status IN (?a)", $cids, $_c_statuses);
            $c_condition = db_quote('AND a.status IN (?a) AND (' . fn_find_array_in_set(Tygh::$app['session']['auth']['usergroup_ids'], 'a.usergroup_ids', true) . ')', $_c_statuses);
        }

        $sub_categories_ids = db_get_fields("SELECT a.category_id FROM ?:categories as a LEFT JOIN ?:categories as b ON b.category_id IN (?n) WHERE a.id_path LIKE CONCAT(b.id_path, '/%') ?p", $cids, $c_condition);
        $sub_categories_ids = fn_array_merge($cids, $sub_categories_ids, false);

        if (empty($sub_categories_ids)) {
            $params['force_get_by_ids'] = true;
            $params['pid'] = $params['product_id'] = 0;

            return;
        }

        if (!empty($params['subcats']) && $params['subcats'] == YesNo::YES) {
            $restrict_by['category_id'] = join('|', $sub_categories_ids);
        } else {
            $restrict_by['category_id'] = join('|', $cids);
        }
    }

    //
    // Sortings
    //
    if (!empty($_REQUEST['search_performed']) && empty($_REQUEST['sort_by']) && SE_USE_RELEVANCE_AS_DEFAULT_SORTING == YesNo::YES) {
        $params['sort_by'] = 'relevance';
        $params['sort_order'] = 'asc';
    }

    if (!empty($params['sort_by']) && !in_array($params['sort_by'], fn_se_get_valid_sortings())) {
        return;
    }

    if ($params['sort_by'] == 'product') {
        $sort_by = 'title';
    } elseif ($params['sort_by'] == 'relevance') {
        $params['sort_order'] = 'asc';
        $sort_by = 'relevance';
    } elseif ($params['sort_by'] == 'bestsellers') {
        $sort_by = 'sales';
    } elseif ($params['sort_by'] == 'on_sale') {
        $sort_by = 'discount';
    } else {
        $sort_by = $params['sort_by'];
    }

    $sort_order = ($params['sort_order'] == 'asc') ? 'asc' : 'desc';

    //
    // Items_per_page
    //
    $items_per_page = empty($params['items_per_page']) ? 10 : (int) $params['items_per_page'];

    if (!empty($params['limit'])) {
        $max_results = $params['limit'];
    } else {
        $max_results = $items_per_page;
    }

    $get_items = true;
    $get_facets = true;

    $request_params = [
        'sortBy'     => $sort_by,
        'sortOrder'  => $sort_order,

        'union'      => $union,
        'queryBy'    => $query_by,
        'restrictBy' => $restrict_by,

        'items'      => ($get_items == true)? 'true' : 'false',
        'facets'     => ($get_facets == true)? 'true' : 'false',

        'maxResults' => $max_results,
        'startIndex' => ($params['page'] - 1) * $items_per_page,
    ];

    if ($request_params['sortBy'] == 'null') {
        unset($request_params['sortBy']);
    }

    if (!empty($params['q']) && fn_strlen($params['q']) > 0) {
        $request_params['q'] = $params['q'];
        $request_params['suggestions'] = 'true';
        $request_params['query_correction'] = 'false';
        $request_params['suggestionsMaxResults'] = 1;
    } else {
        $request_params['q'] = '';
    }

    $result = fn_searchanise_send_search_request($request_params, $lang_code);
    if ($result == false) {
        //revert to standart sorting
        if ($params['sort_by'] == 'relevance') {
            $params = array_merge($params, fn_get_default_products_sorting());
        }
        Registry::set('runtime.se_use_relevance_sorting', false);

        return;
    }

    if (!empty($result['suggestions']) && (is_array($result['suggestions']) || $result['suggestions'] instanceof \Countable ? count($result['suggestions']) : 0) > 0) {
        $params['suggestion'] = reset($result['suggestions']);
    }

    if (!empty($result['items'])) {
        foreach ($result['items'] as $product) {
            $params['pid'][] = $product['product_id'];
        }
        if ($params['sort_by'] == 'relevance') {
            $sortings['relevance'] = "FIELD(products.product_id, '" . join("','", $params['pid']) . "')";
            $params['sort_order'] = 'asc';
        }
    } else {
        $products = [];
        $params['force_get_by_ids'] = true;
        $params['pid'] = $params['product_id'] = 0;
    }

    if (isset($result['facets'])) {
        Registry::set('searchanise.received_facets', $result['facets']);
    }

    $total = $result['totalItems'];
    $params['limit'] = $items_per_page; // need to set it manually for proper pagination

    // reset condition with text search && filtering params  - we are get all control under process of  text search and filtering
    $condition = '';
    $join = '';

    return;
}

/**
 * Changes SQL parameters for product filters select
 *
 * @param string   $fields    String of comma-separated SQL fields to be selected in an SQL-query
 * @param string   $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
 * @param string   $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
 * @param string   $group_by  String containing the SQL-query GROUP BY field
 * @param string   $sorting   String containing the SQL-query ORDER BY clause
 * @param string   $limit     String containing the SQL-query LIMIT clause
 * @param string[] $params    Products filter search params
 * @param string   $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
 *
 * @return void
 *
 * @see fn_get_product_filters()
 */
function fn_searchanise_get_product_filters_before_select($fields, $join, &$condition, $group_by, $sorting, $limit, array $params, $lang_code)
{
    if (empty($params['for_searchanise'])) {
        return;
    }

    $received_facets = (array) Registry::get('searchanise.received_facets');
    $feature_ids = [];
    $field_type = array_keys(fn_get_product_filter_fields());

    foreach ($received_facets as $facet) {
        if (substr($facet['attribute'], 0, 8) === 'feature_') {
            $feature_ids[] = substr($facet['attribute'], 8);
        }
    }

    if (!empty($feature_ids)) {
        $condition .= db_quote(' AND (?:product_features.feature_id IN (?n) OR ?:product_filters.field_type IN (?a))', $feature_ids, $field_type);
    } else {
        $condition .= db_quote(' AND ?:product_filters.field_type IN (?a)', $field_type);
    }
}

/**
 * Changes product filters data
 *
 * @param string[] $filters   Product filters
 * @param string[] $params    Products filter search params
 * @param string   $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
 *
 * @return void
 *
 * @see fn_get_product_filters()
 */
function fn_searchanise_get_product_filters_post(array &$filters, array $params, $lang_code)
{
    if (empty($params['for_searchanise'])) {
        return;
    }

    $received_facets = (array) Registry::get('searchanise.received_facets');
    $variant_ids = [];

    foreach ($received_facets as $facet) {
        if (substr($facet['attribute'], 0, 8) === 'feature_') {
            $variant_ids[substr($facet['attribute'], 8)] = array_column($facet['buckets'], 'value');
        }
    }

    foreach ($filters as &$filter) {
        if (!empty($filter['feature_id']) && key_exists($filter['feature_id'], $variant_ids)) {
            [$variants] = fn_get_product_feature_variants([
                'feature_id' => $filter['feature_id'],
                'variant_id' => $variant_ids[$filter['feature_id']]
            ]);

            foreach ($variants as $variant_id => $variant) {
                $filter['variants'][$variant_id] = $variant;
            }
        }
    }
    unset($filter);
}

/**
 * Gets available filters according to current products set
 *
 * @param array  $params    Products filter search params
 * @param string $lang_code 2 letters language code
 *
 * @return array
 */
function fn_searchanise_get_filters_products_count(array $params = [], $lang_code = CART_LANGUAGE)
{
    if (
        empty($params['search_performed']) ||
        empty($params['dispatch']) ||
        $params['dispatch'] != 'products.search' && $params['dispatch'] != 'companies.products' ||
        fn_se_check_disabled() ||
        !empty($params['disable_searchanise']) ||
        !fn_se_is_search_allowed(fn_se_get_company_id(), CART_LANGUAGE)
    ) {
        return fn_product_filters_get_filters_products_count($params, $lang_code);
    }

    $received_facets = Registry::get('searchanise.received_facets');
    if (is_null($received_facets)) {
        return fn_product_filters_get_filters_products_count($params, $lang_code);
    }

    static $inner_cache = [];

    $inner_cache_key = md5(serialize(array_filter($params))) . '_' . $lang_code;

    if (isset($inner_cache[$inner_cache_key])) {
        return $inner_cache[$inner_cache_key];
    }

    $_params = ['status' => ObjectStatuses::ACTIVE, 'get_variants' => false, 'for_searchanise' => true];
    if (!empty($params['item_ids'])) {
        $_params['item_ids'] = $params['item_ids'];
    }
    [$stored_filters, ] = fn_get_product_filters($_params, 0, $lang_code);

    if (!empty($params['features_hash'])) {
        //
        // Get without
        //
        [$restrict_by, $query_by, $union] = fn_se_prepare_request_params(array_merge($params, ['features_hash' => '']));

        $request_params = [
            'items'  => 'false',
            'facets' => 'true',
            'q'      => $params['q'],

            'union'      => $union,
            'queryBy'    => $query_by,
            'restrictBy' => $restrict_by,
        ];
        $result = fn_searchanise_send_search_request($request_params);

        if (empty($result)) {
            return fn_product_filters_get_filters_products_count($params);
        } else {
            $clear_rfacets = $result['facets'];
        }
    }

    $filters = [];
    foreach ($stored_filters as $sfilter) {
        $sfilter_id = $sfilter['filter_id'];
        $filter = [
            'feature_id'    => $sfilter['feature_id'],
            'filter_id'     => $sfilter_id,
            'field_type'    => $sfilter['field_type'],
            'round_to'      => $sfilter['round_to'],
            'display'       => $sfilter['display'],
            'display_count' => $sfilter['display_count'],
            'filter'        => $sfilter['filter'],
            'feature_type'  => $sfilter['feature_type'],
            'prefix'        => $sfilter['prefix'],
            'suffix'        => $sfilter['suffix'],
        ];

        $rfilter = false;
        foreach ($received_facets as $rfacet) {
            if (
                $sfilter['field_type'] == ProductFilterProductFieldTypes::PRICE && $rfacet['attribute'] == "price" ||
                $sfilter['field_type'] == ProductFilterProductFieldTypes::FREE_SHIPPING && $rfacet['attribute'] == "free_shipping" ||
                $sfilter['field_type'] == ProductFilterProductFieldTypes::IN_STOCK && $rfacet['attribute'] == "in_stock" ||
                $sfilter['field_type'] == ProductFilterProductFieldTypes::VENDOR && $rfacet['attribute'] == "company_id" ||
                "feature_{$sfilter['feature_id']}" == $rfacet['attribute']
            ) {
                $rfilter = $rfacet;
                break;
            }
        }

        if (empty($rfilter)) {
            continue;
        }

        $crfilter = false;
        foreach ($clear_rfacets as $crfacet) {
            if (
                $sfilter['field_type'] == ProductFilterProductFieldTypes::PRICE && $crfacet['attribute'] == "price" ||
                $sfilter['field_type'] == ProductFilterProductFieldTypes::FREE_SHIPPING && $crfacet['attribute'] == "free_shipping" ||
                $sfilter['field_type'] == ProductFilterProductFieldTypes::IN_STOCK && $crfacet['attribute'] == "in_stock" ||
                $sfilter['field_type'] == ProductFilterProductFieldTypes::VENDOR && $crfacet['attribute'] == "company_id" ||
                "feature_{$sfilter['feature_id']}" == $crfacet['attribute']
            ) {
                $crfilter = $crfacet;
                break;
            }
        }

        if (
            $sfilter['feature_type'] == ProductFeatures::NUMBER_FIELD ||
            $sfilter['feature_type'] == ProductFeatures::NUMBER_SELECTBOX ||
            $sfilter['feature_type'] == ProductFeatures::DATE ||
            (!empty($sfilter['condition_type']) && $sfilter['condition_type'] == 'D')
        ) {
            if ($sfilter['field_type'] == ProductFilterProductFieldTypes::PRICE) {
                $filter_fields = fn_get_product_filter_fields();
                $convert = $filter_fields['P']['convert'];
                [$rfilter['buckets'][0]['from'], $rfilter['buckets'][0]['to']] = $convert($rfilter['buckets'][0]['from'], $rfilter['buckets'][0]['to']);
                if (!empty($rfilter['buckets'][0]['selected'])) {
                    [$rfilter['buckets'][0]['left'], $rfilter['buckets'][0]['right']] = $convert($rfilter['buckets'][0]['left'], $rfilter['buckets'][0]['right']);
                }
            }

            // Needs for check to disabling slider.
            $min = Math::floorToPrecision((float) $rfilter['buckets'][0]['from'], (float) $sfilter['round_to'] * 0.1);
            $max = Math::floorToPrecision((float) $rfilter['buckets'][0]['to'], (float) $sfilter['round_to'] * 0.1);

            $filter['min']     = Math::ceilToPrecision((float) $min, (float) $sfilter['round_to']);
            $filter['max']     = Math::ceilToPrecision((float) $max, (float) $sfilter['round_to']);
            $filter['extra']   = CART_SECONDARY_CURRENCY; //TODO
            $filter['disable'] = round(abs($max - $min), 2) < $sfilter['round_to'];
            $filter['slider']  = true;

            if (!empty($rfilter['buckets'][0]['selected'])) {
                $filter['left']  = Math::floorToPrecision((float) $rfilter['buckets'][0]['left'], (float) $sfilter['round_to']);
                $filter['right'] = Math::floorToPrecision((float) $rfilter['buckets'][0]['right'], (float) $sfilter['round_to']);
                $filter['selected_range'] = true;
            }
        } elseif (
            $sfilter['feature_type'] == ProductFeatures::SINGLE_CHECKBOX ||
            $sfilter['field_type'] == ProductFilterProductFieldTypes::FREE_SHIPPING && $rfilter['attribute'] == "free_shipping" ||
            $sfilter['field_type'] == ProductFilterProductFieldTypes::IN_STOCK && $rfilter['attribute'] == "in_stock"
        ) {
            if (!empty($rfilter['buckets'][0])) {
                $rvariant = $rfilter['buckets'][0];
                $insert_to = (!empty($rvariant['selected']))? 'selected_variants' : 'variants';
                $filter[$insert_to][YesNo::YES] = [
                    'variant_id' => YesNo::YES,
                    'variant'    => __('yes'),
                ];
            }
        } else {
            if ($sfilter['field_type'] == ProductFilterProductFieldTypes::VENDOR && $rfilter['attribute'] == "company_id") {
                $sfilter['variants'] = db_get_hash_array("SELECT c.company_id as variant_id, c.company as variant FROM ?:companies AS c WHERE c.status = ?s ORDER BY c.company ASC", 'variant_id', ObjectStatuses::ACTIVE);
            }

            //
            // Fill Variants
            //
            foreach ($rfilter['buckets'] as $rvariant) {
                $variant_id = $rvariant['value'];
                $insert_to = (!empty($rvariant['selected']))? '_selected_variants' : '_variants';
                $filter[$insert_to][$variant_id] = [
                    'variant_id' => $variant_id,
                    'variant'    => $sfilter['variants'][$variant_id]['variant'],
                    'position'   => 0,
                ];
            }

            //
            // Disabled
            //
            if (!empty($crfilter)) {
                foreach ($crfilter['buckets'] as $crvariant) {
                    $variant_id = $crvariant['value'];
                    if (empty($filter['_selected_variants'][$variant_id]) && empty($filter['_variants'][$variant_id])) {
                        $filter['_variants'][$variant_id] = [
                            'variant_id' => $variant_id,
                            'variant'    => $sfilter['variants'][$variant_id]['variant'],
                            'position'   => 0,
                            'disabled'  => true,
                        ];
                    }
                }
            }

            //
            // Sort
            //
            foreach ($sfilter['variants'] as $variant_id => $variant) {
                if (!empty($filter['_variants'][$variant_id]) && empty($filter['_variants'][$variant_id]['disabled'])) {
                    $filter['variants'][$variant_id] = $filter['_variants'][$variant_id];
                } elseif (!empty($filter['_selected_variants'][$variant_id])) {
                    $filter['selected_variants'][$variant_id] = $filter['_selected_variants'][$variant_id];
                }
            }

            foreach ($sfilter['variants'] as $variant_id => $variant) {
                if (!empty($filter['_variants'][$variant_id]) && !empty($filter['_variants'][$variant_id]['disabled'])) {
                    $filter['variants'][$variant_id] = $filter['_variants'][$variant_id];
                }
            }

            unset($filter['_variants'], $filter['_selected_variants']);
        }

        $filters[$sfilter_id] = $filter;
    }

    $selected_filters = !empty($params['features_hash']) && empty($params['skip_advanced_variants'])
        ? fn_parse_filters_hash($params['features_hash'])
        : [];

    /**
     * Modifies filters
     *
     * @param array  $params            Parameters of filters selection
     * @param string $lang_code         Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param array  $filters           Filters array
     * @param array  $selected_filters  Selected filters array
     */
    fn_set_hook('get_filters_products_count_post', $params, $lang_code, $filters, $selected_filters);

    $inner_cache[$inner_cache_key] = [$filters];

    return [$filters];
}

/**
 * Adds additional actions after product feature updating
 *
 * @param array  $feature_data     Feature data
 * @param int    $feature_id       Feature identifier
 * @param array  $deleted_variants Deleted product feature variants identifiers
 * @param string $lang_code        2-letters language code
 */
function fn_searchanise_update_product_feature_post($feature_data, $feature_id, $deleted_variants, $lang_code)
{
    //Send products with Select->Number feature
    if (!empty($feature_id) && !empty($feature_data['feature_type']) && $feature_data['feature_type'] == ProductFeatures::NUMBER_SELECTBOX) {
        $product_ids = db_get_fields('SELECT product_id FROM ?:product_features_values WHERE feature_id = ?i AND lang_code = ?s', $feature_id, DEFAULT_LANGUAGE);

        fn_se_add_chunk_product_action(QueueActions::UPDATE_PRODUCTS, $product_ids);
    }
}

function fn_searchanise_update_product_filter_post($filter_data, $filter_id)
{
    fn_se_add_action(QueueActions::UPDATE_FACETS, $filter_id);
}

function fn_searchanise_delete_product_filter_pre($filter_id)
{
    $filter = db_get_row("SELECT * FROM ?:product_filters WHERE filter_id = ?i LIMIT 1", $filter_id);

    if (!empty($filter['feature_id'])) {
        $facet_attribute = 'feature_' . $filter['feature_id'];
    } elseif ($filter['field_type'] == ProductFilterProductFieldTypes::PRICE) {
        $facet_attribute = 'price';
    } elseif ($filter['field_type'] == ProductFilterProductFieldTypes::FREE_SHIPPING) {
        $facet_attribute = 'free_shipping';
    } elseif ($filter['field_type'] == ProductFilterProductFieldTypes::VENDOR) {
        $facet_attribute = 'company_id';
    } elseif ($filter['field_type'] == ProductFilterProductFieldTypes::IN_STOCK) {
        $facet_attribute = 'amount';
    } else {
        return;
    }

    $dublicate = db_get_field("SELECT count(*) FROM ?:product_filters WHERE feature_id = ?i AND field_type = ?s LIMIT 1", $filter['feature_id'], $filter['field_type']);

    if ($dublicate > 1) {
        return; // we have dublicate filter => no request
    }

    fn_se_add_action(QueueActions::DELETE_FACETS, $facet_attribute);
}

function fn_se_get_json_header()
{
    return [
        'header' => [
            'id'      => Registry::get('config.http_location'),
            'updated' => date('c'),
        ],
    ];
}

/**
 * Change addon status
 *
 * @param string $status     New addon status
 * @param int    $company_id Company identifier
 * @param string $lang_code  2 letters lang code
 */
function fn_se_send_addon_status_request($status = AddonStatuses::ENABLED, $company_id = null, $lang_code = null)
{
    $engines_data = fn_se_get_engines_data($company_id, $lang_code, true);

    if ($engines_data) {
        foreach ($engines_data as $engine_data) {
            $private_key = fn_se_get_private_key($engine_data['company_id'], $engine_data['lang_code']);
            fn_se_send_request('/api/state/update/json', $private_key, ['addon_status' => $status]);
        }
    }
}

/**
 * Sends addon version to Searchanise
 *
 * @param int    $company_id Company identifier
 * @param string $lang_code  Two letters lang code
 *
 * @return bool
 */
function fn_se_send_addon_version($company_id = null, $lang_code = null)
{
    static $processed_keys = [];

    $result = false;
    $parent_private_key = fn_se_get_parent_private_key($company_id, $lang_code);

    if (!empty($parent_private_key) && empty($processed_keys[$parent_private_key])) {
        $result = fn_se_send_request('/api/state/update/json', $parent_private_key, [
            'addon_version'    => fn_get_addon_version('searchanise'),
            'platform_edition' => PRODUCT_EDITION,
            'platform_version' => PRODUCT_VERSION,
        ]);
        $processed_keys[$parent_private_key] = true;
    }

    return $result;
}

function fn_se_send_request($url_part, $private_key, $data)
{
    if (empty($private_key)) {
        return;
    }

    $params = ['private_key' => $private_key] + $data;

    Registry::set('log_cut', true);

    $result = Http::post(SE_SERVICE_URL . $url_part, $params, [
        'timeout' => SE_REQUEST_TIMEOUT
    ]);

    $response = fn_se_parse_response($result, false, static function ($err) use ($private_key) {
        if ($err === 'inactive_private_key') {
            $engines_data = fn_se_get_engines_data();

            foreach ($engines_data as $e) {
                if ($e['private_key'] === $private_key) {
                    fn_se_set_import_status(ImportStatuses::SUSPENDED, $e['company_id'], $e['lang_code']);
                    db_query('DELETE FROM ?:se_queue WHERE company_id = ?i AND lang_code = ?s', $e['company_id'], $e['lang_code']);
                    break;
                }
            }
        }
    });

    fn_se_set_simple_setting('last_request', TIME);

    return $response;
}

/**
 * Parses response from Searchanise server
 *
 * @param string        $response          Searchanise response
 * @param bool          $show_notification If true and error occurs, it will be shown
 * @param callable|null $err_callback      Error callback action
 *
 * @return bool|array
 */
function fn_se_parse_response($response, $show_notification = false, $err_callback = null)
{
    $data = json_decode($response, true);

    if (empty($data)) {
        return false;
    }

    if ($data === 'ok') {
        return true;
    }

    if (!empty($data['errors']) && is_array($data['errors'])) {
        foreach ($data['errors'] as $e) {
            if ($show_notification == true) {
                fn_set_notification(NotificationSeverity::ERROR, __('error'), 'Searchanise: ' . (string) $e);
            }

            if (!is_callable($err_callback)) {
                continue;
            }

            call_user_func($err_callback, $e);
        }

        return false;
    }

    return $data;
}

function fn_se_parse_state_response($response)
{
    $data = [];
    if (empty($response)) {
        return false;
    }

    if (!empty($response['variable'])) {
        foreach ($response['variable'] as $name => $v) {
            $data[$name] = (string) $v;
        }

        return $data;
    }

    return false;
}

function fn_se_get_ids($items, $name = 'product_id')
{
    $ids = [];

    foreach ((array) $items as $v) {
        $ids[] = $v[$name];
    }

    return $ids;
}

function fn_se_delete_keys($company_id = null, $lang_code = null)
{
    $engines_data = fn_se_get_engines_data($company_id, $lang_code, true);
    foreach ($engines_data as $engine_data) {
        $c_id = $engine_data['company_id'];
        $l_code = $engine_data['lang_code'];

        fn_se_set_import_status(ImportStatuses::NONE, $c_id, $l_code);
        fn_se_send_addon_status_request(AddonStatuses::DELETED, $c_id, $l_code);
        db_query("DELETE FROM ?:se_queue WHERE company_id = ?i AND lang_code = ?s", $c_id, $l_code);
    }

    fn_se_get_all_settings(true);// call to update cache

    return true;
}

/**
 * Adds additional actions before languages deleting
 *
 * @param array $lang_ids List of language ids
 */
function fn_searchanise_delete_languages_pre($lang_ids)
{
    if (!empty($lang_ids)) {
        $lang_codes = db_get_hash_single_array('SELECT lang_id, lang_code FROM ?:languages WHERE lang_id IN (?n)', ['lang_id', 'lang_code'], (array) $lang_ids);

        foreach ((array) $lang_codes as $lang_code) {
            fn_se_delete_keys(null, $lang_code);
        }
    }
}

/**
 * Adds additional actions after language update
 *
 * @param array  $language_data Language data
 * @param string $lang_id       language id
 * @param string $action        Current action ('add', 'update' or bool false if failed to update language)
 */
function fn_searchanise_update_language_post($language_data, $lang_id, $action)
{
    fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('text_se_re_indexation_required', [
        '[link]' => fn_url('addons.update?addon=searchanise')
    ]));
}

function fn_searchanise_delete_company_pre($company_id)
{
    if (fn_allowed_for('ULTIMATE')) {
        fn_se_delete_keys($company_id, null);
    }
}

/**
 * Adds an action about vendor removal
 *
 * @param int     $company_id  Company identifier to delete
 * @param boolean $result      Result of actions
 * @param array   $storefronts Storefronts
 *
 * @see \fn_delete_company()
 */
function fn_searchanise_delete_company($company_id, $result, $storefronts)
{
    if ($result && fn_allowed_for('MULTIVENDOR')) {
        fn_se_add_action(QueueActions::DELETE_VENDORS, (int) $company_id);
    }
}

/**
 * Adds additional actions after company/vendor update
 *
 * @param array  $company_data Company data
 * @param int    $company_id   Company integer identifier
 * @param string $lang_code    Two-letter language code
 * @param string $action       Flag determines if company was created (add) or just updated (update).
 *
 * @see \fn_update_company()
 */
function fn_searchanise_update_company($company_data, $company_id, $lang_code, $action)
{
    if (fn_se_signup($company_id, null, false) && fn_se_get_import_status(fn_se_get_company_id(), $lang_code) !== ImportStatuses::SUSPENDED) {
        fn_se_queue_import($company_id, null, false);
        fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('text_se_new_engine_store', [
            '[store]' => $company_data['company']
        ]));
    }

    if (fn_allowed_for('MULTIVENDOR')) {
        fn_se_add_action(QueueActions::UPDATE_VENDORS, [['company_id' => $company_id]]);
    }
}

function fn_searchanise_change_company_status_pre($company_id, $status_to, $reason, $status_from, $skip_query, $notify)
{
    if (
        $status_to !== $status_from &&
        fn_allowed_for('MULTIVENDOR') &&
        fn_se_get_import_status(fn_se_get_company_id(), CART_LANGUAGE) !== ImportStatuses::SUSPENDED
    ) {
        fn_se_queue_import($company_id, null, false);
    }
}


function fn_se_check_import_is_done($company_id = null, $lang_code = null)
{
    $skip_time_check = false;
    $engines_data = fn_se_get_engines_data($company_id, $lang_code);

    if ($engines_data) {
        foreach ($engines_data as $engine_data) {
            $c_id = $engine_data['company_id'];
            $l_code = $engine_data['lang_code'];

            if ($engine_data['import_status'] == ImportStatuses::SENT) {
                if ((TIME - fn_se_get_simple_setting('last_request')) > 10 ||
                    (fn_se_get_simple_setting('last_request') - 10) > TIME || // It is need if last_request incorrect.
                    $skip_time_check == true) {
                    $response = fn_se_send_request('/api/state/get/json', fn_se_get_private_key($c_id, $l_code), ['status' => '', 'full_import' => '']);

                    $variables = fn_se_parse_state_response($response);

                    if (!empty($variables) && isset($variables['status'])) {
                        if ($variables['status'] == 'normal' && $variables['full_import'] == ImportStatuses::DONE) {
                            $skip_time_check = true;
                            fn_se_set_import_status(ImportStatuses::DONE, $c_id, $l_code);
                        } elseif ($variables['status'] == 'disabled') {
                            fn_se_set_import_status(ImportStatuses::SUSPENDED, $c_id, $l_code); //disable status check for disabled engine
                        }
                    }
                }
            }
        }
    }
}

function fn_se_check_disabled()
{
    $check = false;
    if (isset($_REQUEST['disabled_module_searchanise'])) {
        $check = ($_REQUEST['disabled_module_searchanise'] == YesNo::YES);
    }

    return $check;
}

function fn_se_check_debug()
{
    $check = false;
    if (isset($_REQUEST['debug_module_searchanise'])) {
        $check = ($_REQUEST['debug_module_searchanise'] == YesNo::YES);
    }

    return $check;
}

/**
 * Display addon notice
 *
 * @param string $addon Addon identifiter
 */
function fn_se_display_addon_notice($addon)
{
    if (fn_se_is_registered() == true) {
        $notice_var = "text_se_{$addon}_settings_notice";

        fn_set_notification(NotificationSeverity::WARNING, __('notice'), __($notice_var, [
            '[link]' => fn_url('addons.update?addon=searchanise')
        ]));
    }
}

/**
 * Test if Searchanise api server is available
 *
 * @param int $timeout Request timeout in seconds
 *
 * @return boolean
 */
function fn_se_check_connect($timeout = 3)
{
    $response = Http::get(SE_SERVICE_URL . '/api/test', [], ['timeout' => $timeout]);

    if ($response != 'OK') {
        fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('se_connection_warning', [
            '[email]' => SE_CONTACT_EMAIL
        ]));
        return false;
    }

    return true;
}

/**
 * Test Searchanise queue status
 *
 * @param boolean $display_notice  If true, notice will be displayed on queue error
 *
 * @return boolean
 */
function fn_se_check_queue($display_notice = true)
{
    $q = db_get_row("SELECT * FROM ?:se_queue ORDER BY queue_id ASC LIMIT 1");

    if (empty($q)) {
        return true;
    }

    if ($q['error_count'] >= SE_MAX_ERROR_COUNT) {
        // Maximum attemps reached
        $status = false;
    } elseif ($q['status'] == 'processing' && ($q['started'] + SECONDS_IN_HOUR < TIME)) {
        // Queue item processed more than one hour
        $status = false;
    } else {
        $status = true;
    }

    if ($display_notice && !$status) {
        fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('se_queue_warning', [
            '[email]' => SE_CONTACT_EMAIL
        ]));
    }

    return $status;
}

/**
 * Returns children product ids
 *
 * @param  array Product identifiers
 *
 * @return array Product ids
 */
function fn_se_get_children_product_ids(array $product_ids)
{
    $children_ids = [];

    /**
     * Get children products for specific product identifiers
     *
     * @param array $product_id   Product identifiers
     * @param array $children_ids Returned child product identifiers
     */
    fn_set_hook('se_get_children_product_ids', $product_ids, $children_ids);

    return $children_ids;
}

/**
 * Get children products for specific product identifiers
 *
 * @param array $product_id   Product identifiers
 * @param array $children_ids Returned child product identifiers
 *
 * @see \fn_se_get_children_product_ids()
 */
function fn_master_products_se_get_children_product_ids(array $product_ids, array &$children_ids)
{
    foreach ($product_ids as $product_id) {
        $vendor_product_ids = MasterProductsServiceProvider::getProductRepository()->findVendorProductIds($product_id);

        if (!empty($vendor_product_ids)) {
            $children_ids = array_merge($children_ids, $vendor_product_ids);
        }
    }

    $children_ids = array_unique($children_ids);
}

/**
 * Returns parent product ids
 *
 * @param mixed Product identifiers
 *
 * @return array Product ids
 */
function fn_se_get_parent_product_ids(array $product_ids)
{
    $parent_ids = [];

    /**
     * Get parent products for specific product identifiers
     *
     * @param array $product_id Product identifiers
     * @param array $parent_ids Returned parent product identifiers
     */
    fn_set_hook('se_get_parent_product_ids', $product_ids, $parent_ids);

    return $parent_ids;
}

/**
 * Get parent products for specific product identifiers
 *
 * @param array $product_id Product identifiers
 * @param array $parent_ids Returned parent product identifiers
 *
 * @see \fn_se_get_parent_product_ids()
 */
function fn_product_variations_se_get_parent_product_ids(array $product_ids, array &$parent_ids)
{
    foreach ($product_ids as $product_id) {
        $parent_id = ProductVariationsServiceProvider::getGroupRepository()->getParentProductId($product_id);

        if (!empty($parent_id)) {
            $parent_ids[] = $parent_id;
        }
    }
}

/**
 * Returns united product ids
 *
 * @param  array $product_ids  Product identifiers
 * @param  bool  $include_self If true, returns incoming product identifiers in united array
 *
 * @return array Product ids
 */
function fn_se_get_united_product_ids(array $product_ids, $include_self = true)
{
    $parent_product_ids = fn_se_get_children_product_ids($product_ids);
    $children_product_ids = fn_se_get_parent_product_ids($product_ids);
    $products = array_merge($parent_product_ids, $children_product_ids);

    if ($include_self) {
        $products = array_merge($product_ids, $products);
    }

    return array_unique($products);
}

/**
 * Executes after vendor product created.
 *
 * @param int                          $master_product_id Master product ID
 * @param int                          $company_id        Vendor ID
 * @param array                        $product           Master product data
 * @param int                          $vendor_product_id Vendor product ID
 * @param \Tygh\Common\OperationResult $result            Result of operation
 */
function fn_searchanise_master_products_create_vendor_product($master_product_id, $company_id, $product, $vendor_product_id, $result)
{
    $united_products = fn_se_get_united_product_ids([$master_product_id, $vendor_product_id]);
    if (!empty($united_products)) {
        foreach ($united_products as $product_id) {
            fn_se_add_action(QueueActions::UPDATE_PRODUCTS, (int) $product_id);
        }
    }
}

/**
 * Executes after the variation group is saved;
 * allows to perform additional actions and react to events that occur to variation group
 *
 * @param \Tygh\Addons\ProductVariations\Service                       $this
 * @param \Tygh\Addons\ProductVariations\Product\Group\Group           $group
 * @param \Tygh\Addons\ProductVariations\Product\Group\Events\AEvent[] $events
 */
function fn_searchanise_variation_group_save_group($service, $group, $events)
{
    foreach ($events as $event) {
        if ($event instanceof Tygh\Addons\ProductVariations\Product\Group\Events\ProductRemovedEvent) {
            $united_products = fn_se_get_united_product_ids([
                $event->getProduct()->getProductId(),
                $event->getProduct()->getParentProductId()
            ]);

            if (!empty($united_products)) {
                foreach ($united_products as $product_id) {
                    fn_se_add_action(QueueActions::UPDATE_PRODUCTS, (int) $product_id);
                }
            }
        }

        if ($event instanceof Tygh\Addons\ProductVariations\Product\Group\Events\ProductAddedEvent) {
            $united_products = fn_se_get_united_product_ids([
                $event->getProduct()->getProductId(),
                $event->getProduct()->getParentProductId()
            ]);

            if (!empty($united_products)) {
                foreach ($united_products as $product_id) {
                    fn_se_add_action(QueueActions::UPDATE_PRODUCTS, (int) $product_id);
                }
            }
        }

        if ($event instanceof Tygh\Addons\ProductVariations\Product\Group\Events\ProductUpdatedEvent) {
            if (!$event->getFrom()->hasSameParentProductId($event->getTo()->getParentProductId())) {
                $from_group_product = $event->getFrom();
                $to_group_product = $event->getTo();

                $united_products = fn_se_get_united_product_ids([
                    $from_group_product->getProductId(),
                    $to_group_product->getProductId(),
                    $from_group_product->getParentProductId(),
                    $to_group_product->getParentProductId(),
                ]);

                if (!empty($united_products)) {
                    foreach ($united_products as $product_id) {
                        if (!empty($product_id)) {
                            fn_se_add_action(QueueActions::UPDATE_PRODUCTS, (int) $product_id);
                        }
                    }
                }
            }
        }

        if ($event instanceof Tygh\Addons\ProductVariations\Product\Group\Events\ParentProductChangedEvent) {
            $from_group_product = $event->getFrom(); // Instance of the old parent product
            $to_group_product = $event->getTo();     // Instance of the new parent product
            $to_parent_product_id = $to_group_product->getProductId();
            $from_parent_product_id = $from_group_product->getProductId();

            $to_children_product_ids = $group->getChildProductIds($to_parent_product_id);
            $from_children_product_ids = $group->getChildProductIds($from_parent_product_id);

            $united_products = fn_se_get_united_product_ids(array_merge([
                $to_parent_product_id,
                $from_parent_product_id,
            ], $to_children_product_ids, $from_children_product_ids));

            if (!empty($united_products)) {
                foreach ($united_products as $product_id) {
                    fn_se_add_action(QueueActions::UPDATE_PRODUCTS, (int) $product_id);
                }
            }
        }
    }
}
