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

use Tygh\Addons\MasterProducts\ServiceProvider as MasterProductsServiceProvider;
use Tygh\Addons\ProductVariations\Product\Type\Type as ProductVariationTypes;
use Tygh\Addons\ProductVariations\ServiceProvider as ProductVariationsServiceProvider;
use Tygh\Enum\Addons\Searchanise\ImportStatuses;
use Tygh\Enum\Addons\Searchanise\QueueActions;
use Tygh\Enum\Addons\Searchanise\QueueStatuses;
use Tygh\Enum\Addons\Searchanise\ValueTypes;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ProductFeatures;
use Tygh\Enum\ProductFilterProductFieldTypes;
use Tygh\Enum\ProductTracking;
use Tygh\Enum\UsergroupTypes;
use Tygh\Enum\YesNo;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if ($mode == 'async') {

    $company_id = fn_se_get_company_id();
    if (empty($_REQUEST['parent_private_key']) || fn_se_get_parent_private_key($company_id, DEFAULT_LANGUAGE) !== $_REQUEST['parent_private_key']) {
        $check_key = false;
    } else {
        $check_key = true;
    }

    @ignore_user_abort(1);
    @set_time_limit(0);
    if ($check_key && isset($_REQUEST['display_errors']) && $_REQUEST['display_errors'] === YesNo::YES) {
        @error_reporting (E_ALL);
        @ini_set('display_errors', 1);
    } else {
        @ini_set('display_errors', 0);
    }

    if (defined('SE_MEMORY_LIMIT')) {
        if (substr(ini_get('memory_limit'), 0, -1) < SE_MEMORY_LIMIT) {
            @ini_set('memory_limit', SE_MEMORY_LIMIT . 'M');
        }
    }
    $fl_ignore_processing = false;
    if ($check_key && isset($_REQUEST['ignore_processing']) && $_REQUEST['ignore_processing'] === YesNo::YES) {
        $fl_ignore_processing = true;
    }

    $q = fn_se_get_next_queue();

    fn_echo('.');

    $json_header = fn_se_get_json_header();

    while (!empty($q)) {
        if (fn_se_check_debug()) {
            fn_print_r($q);
        }
        $xml = '';
        $status = true;
        $company_id = $q['company_id'];
        $lang_code  = $q['lang_code'];
        $data = (array) unserialize($q['data']);
        $private_key = fn_se_get_private_key($company_id, $lang_code);

        if (empty($private_key)) {
            db_query("DELETE FROM ?:se_queue WHERE queue_id = ?i", $q['queue_id']);
            $q = [];
            continue;
        }

        //Note: $q['started'] can be in future.
        if ($q['status'] == QueueStatuses::PROCESSING && ($q['started'] + SE_MAX_PROCESSING_TIME > TIME)) {
            if (!$fl_ignore_processing) {
                die('PROCESSING');
            }
        }

        if ($q['error_count'] >= SE_MAX_ERROR_COUNT) {
            fn_se_set_import_status(ImportStatuses::ERROR, $company_id, $lang_code);
            die('DISABLED');
        }

        // Set queue to processing state
        db_query("UPDATE ?:se_queue SET ?u WHERE queue_id = ?i", [
            'status'  => QueueStatuses::PROCESSING,
            'started' => TIME
        ], $q['queue_id']);

        if ($q['action'] == QueueActions::PREPARE_FULL_IMPORT) {

            db_query("DELETE FROM ?:se_queue WHERE action != ?s AND company_id = ?i AND lang_code = ?s", QueueActions::PREPARE_FULL_IMPORT, $company_id, $lang_code);

            db_query("INSERT INTO ?:se_queue ?e", [
                'data'       => SE_NOT_DATA,
                'action'     => QueueActions::START_FULL_IMPORT,
                'company_id' => $company_id,
                'lang_code'  => $lang_code
            ]);

            $i = 0;
            $step = SE_PRODUCTS_PER_PASS * 50;

            $sqls_arr = [];

            $min_max = db_get_row('SELECT MIN(product_id) as min, MAX(product_id) as max FROM ?:products');

            $start = (int) $min_max['min'];
            $max   = (int) $min_max['max'];

            do {
                $end = $start + $step;

                $_product_ids = db_get_fields('SELECT product_id FROM ?:products WHERE product_id >= ?i AND product_id <= ?i LIMIT ?i', $start, $end, $step);

                $start = $end + 1;

                if (empty($_product_ids)) {
                    continue;
                }
                $_product_ids = array_chunk($_product_ids, SE_PRODUCTS_PER_PASS);

                foreach ($_product_ids as $product_ids) {
                    $sqls_arr[] = [
                        'data'       => serialize($product_ids),
                        'action'     => QueueActions::UPDATE_PRODUCTS,
                        'company_id' => $company_id,
                        'lang_code'  => $lang_code
                    ];
                }

                if (count($sqls_arr) >= 30) {
                    db_query("INSERT INTO ?:se_queue ?m", $sqls_arr);
                    fn_echo('.');
                    $sqls_arr = [];
                }

            } while ($end <= $max);

            if (count($sqls_arr) > 0) {
                db_query("INSERT INTO ?:se_queue ?m", $sqls_arr);
            }

            fn_echo('.');

            //
            // reSend all active filters
            //

            db_query('INSERT INTO ?:se_queue ?e', [
                'data'       => SE_NOT_DATA,
                'action'     => QueueActions::DELETE_FACETS_ALL,
                'company_id' => $company_id,
                'lang_code'  => $lang_code
            ]);

            [$filters, ] = fn_get_product_filters([
                'get_descriptions' => false,
                'get_variants'     => false,
                'status'           => ObjectStatuses::ACTIVE
            ]);

            if (!empty($filters)) {
                $filter_ids = array_column($filters, 'filter_id');
                db_query('INSERT INTO ?:se_queue ?e', [
                    'data'       => serialize($filter_ids),
                    'action'     => QueueActions::UPDATE_FACETS,
                    'company_id' => $company_id,
                    'lang_code'  => $lang_code
                ]);
            }

            db_query('INSERT INTO ?:se_queue ?e', [
                'data'       => SE_NOT_DATA,
                'action'     => QueueActions::UPDATE_PAGES,
                'company_id' => $company_id,
                'lang_code'  => $lang_code
            ]);

            if (fn_allowed_for('MULTIVENDOR')) {
                db_query('INSERT INTO ?:se_queue ?e', [
                    'data'       => SE_NOT_DATA,
                    'action'     => QueueActions::UPDATE_VENDORS,
                    'company_id' => $company_id,
                    'lang_code'  => $lang_code
                ]);
            }

            db_query('INSERT INTO ?:se_queue ?e', [
                'data'       => SE_NOT_DATA,
                'action'     => QueueActions::UPDATE_CATEGORIES,
                'company_id' => $company_id,
                'lang_code'  => $lang_code
            ]);

            db_query('INSERT INTO ?:se_queue ?e', [
                'data'       => SE_NOT_DATA,
                'action'     => QueueActions::END_FULL_IMPORT,
                'company_id' => $company_id,
                'lang_code'  => $lang_code
            ]);

            $status = true;

        } elseif ($q['action'] == QueueActions::START_FULL_IMPORT) {

            $status = fn_se_send_request('/api/state/update/json', $private_key, ['full_import' => 'start']);

            if ($status == true) {
                fn_se_set_import_status(ImportStatuses::PROCESSING, $company_id, $lang_code);
            }

        } elseif ($q['action'] == QueueActions::END_FULL_IMPORT) {
            $status = fn_se_send_request('/api/state/update/json', $private_key, ['full_import' => 'done']);

            if ($status == true) {
                fn_se_set_import_status(ImportStatuses::SENT, $company_id, $lang_code);
                fn_se_set_simple_setting('last_resync', TIME);
            }

        } elseif ($q['action'] == QueueActions::UPDATE_CATEGORIES) {
            $data = fn_se_get_categories_data($data, $company_id, $lang_code);

            if (!empty($data)) {
                $data = json_encode(array_merge($json_header, ['categories' => $data]));
                $status = fn_se_send_request('/api/items/update/json', $private_key, ['data' => $data]);
            }

        } elseif ($q['action'] == QueueActions::UPDATE_PAGES) {
            $data = fn_se_get_pages_data($data, $company_id, $lang_code);

            if (!empty($data)) {
                $data = json_encode(array_merge($json_header, ['pages' => $data]));
                $status = fn_se_send_request('/api/items/update/json', $private_key, ['data' => $data]);
            }
        } elseif ($q['action'] == QueueActions::UPDATE_VENDORS) {
            $data = fn_se_get_vendors_data($data, $company_id, $lang_code);

            if ($data) {
                $data = json_encode(array_merge($json_header, ['vendors' => $data]));
                $status = fn_se_send_request('/api/items/update/json', $private_key, ['data' => $data]);
            }
        } elseif ($q['action'] == QueueActions::UPDATE_FACETS) {
            [$filters, ] = fn_get_product_filters([
                'filter_id'    => $data,
                'get_variants' => false
            ], 0, $lang_code);

            $facets = [];
            foreach ($filters as $filter_data) {
                $facets[] = fn_se_prepare_facet_data($filter_data);
            }

            if (!empty($facets)) {
                $data = json_encode(array_merge($json_header, ['schema' => $facets]));
                $status = fn_se_send_request('/api/items/update/json', $private_key, ['data' => $data]);
            }

        } elseif ($q['action'] == QueueActions::UPDATE_PRODUCTS) {
            $data = fn_se_get_products_data($data, $company_id, $lang_code, true);

            if (!empty($data)) {
                $data = json_encode(array_merge($json_header, $data));

                if (function_exists('gzcompress')) {
                    $_data = gzcompress($data, 5);
                    if (!empty($_data)) {//workaround for some servers
                        $data = $_data;
                    }
                }

                $status = fn_se_send_request('/api/items/update/json', $private_key, ['data' => $data]);
            }

        } elseif ($q['action'] == QueueActions::DELETE_FACETS_ALL) {
            $status = fn_se_send_request('/api/facets/delete/json', $private_key, ['all' => true]);

        } elseif (
            in_array(
                $q['action'],
                [
                    QueueActions::DELETE_PRODUCTS,
                    QueueActions::DELETE_CATEGORIES,
                    QueueActions::DELETE_PAGES,
                    QueueActions::DELETE_FACETS,
                    QueueActions::DELETE_VENDORS
                ]
            )
        ) {
            if ($q['action'] == QueueActions::DELETE_PRODUCTS) {
                $type = 'items';
            } elseif ($q['action'] == QueueActions::DELETE_CATEGORIES) {
                $type = 'categories';
            } elseif ($q['action'] == QueueActions::DELETE_PAGES) {
                $type = 'pages';
            } elseif ($q['action'] == QueueActions::DELETE_VENDORS) {
                $type = 'vendors';
            } elseif ($q['action'] == QueueActions::DELETE_FACETS) {
                $type = 'facets';
            }

            foreach ($data as $id) {
                $status = fn_se_send_request("/api/{$type}/delete/json", $private_key, ($q['action'] == QueueActions::DELETE_FACETS) ? ['attribute' => $id] : ['id' => $id]);
                fn_echo('.');
                if ($status == false) {
                    break;
                }
            }

        } elseif ($q['action'] == QueueActions::DELETE_PRODUCTS_ALL) {
            $status = fn_se_send_request('/api/items/delete/json', $private_key, ['all' => true]);

        }

        if (fn_se_check_debug()) {
            fn_print_r('status', $status);
        }

        // Change queue item status
        if ($status == true) {
            db_query("DELETE FROM ?:se_queue WHERE queue_id = ?i", $q['queue_id']);// Done, cleanup queue

            $q = fn_se_get_next_queue($q['queue_id']);

        } else {
            $next_started_time = (TIME - SE_MAX_PROCESSING_TIME) + $q['error_count'] * 60;

            db_query("UPDATE ?:se_queue SET status = ?s, error_count = error_count + 1, started = ?s WHERE queue_id = ?i", QueueStatuses::PROCESSING, $next_started_time, $q['queue_id']);

            break; //try later
        }
        fn_echo('.');
    }

    die('OK');
}

if ($mode == 'info') {
    fn_se_check_import_is_done();
    $company_id = fn_se_get_company_id();
    $company_data = fn_get_company_data($company_id);
    $engines_data = fn_se_get_engines_data($company_id, NULL, true);
    $options = [];

    if (empty($_REQUEST['parent_private_key']) || fn_se_get_parent_private_key($company_id, DEFAULT_LANGUAGE) !== $_REQUEST['parent_private_key']) {
        foreach ($engines_data as $e) {
            $options[$e['company_id']][$e['lang_code']] = $e['api_key'];
        }
    } else {
        if (isset($_REQUEST['product_id'])) {
            $lang_code = DEFAULT_LANGUAGE;
            if (isset($_REQUEST['lang_code'])) {
                $lang_code = $_REQUEST['lang_code'];
            } elseif (isset($_REQUEST['sl'])) {
                $lang_code = $_REQUEST['sl'];
            }

            $options = fn_se_get_products_data([$_REQUEST['product_id']], $company_id, $lang_code, false);

        } elseif (isset($_REQUEST['resync']) && $_REQUEST['resync'] === YesNo::YES) {
            fn_se_signup(NULL, NULL, true);
            fn_se_queue_import(NULL, NULL, true);

        } else {
            $options = $engines_data;
            if (!$options) {
                $options = [];
            }

            $options['core_edition'] = PRODUCT_NAME;
            $options['core_version'] = PRODUCT_VERSION;
            $options['core_status']  = PRODUCT_STATUS;
            $options['core_build']   = PRODUCT_BUILD;

            if (!empty($company_data)) {
                $options['company_id'] = $company_data['company_id'];
                $options['status'] = $company_data['status'];
            }

            $options['next_queue'] = fn_se_get_next_queue();
            $options['total_items_in_queue'] = fn_se_get_total_items_queue();

            $options['max_execution_time'] = ini_get('max_execution_time');
            @set_time_limit(0);
            $options['max_execution_time_after'] = ini_get('max_execution_time');

            $options['ignore_user_abort'] = ini_get('ignore_user_abort');
            @ignore_user_abort(1);
            $options['ignore_user_abort_after'] = ini_get('ignore_user_abort_after');

            $options['memory_limit'] = ini_get('memory_limit');
            if (defined('SE_MEMORY_LIMIT')) {
                if (substr(ini_get('memory_limit'), 0, -1) < SE_MEMORY_LIMIT) {
                    @ini_set('memory_limit', SE_MEMORY_LIMIT . 'M');
                }
            }
            $options['memory_limit_after'] = ini_get('memory_limit');
        }
    }

    if (isset($_REQUEST['output'])) {
        fn_echo(json_encode($options));
    } else {
        fn_print_r($options);
    }

    die();
}

function fn_se_get_total_items_queue()
{
    $total_items = 0;

    if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
        $total_items = db_get_field('SELECT COUNT(queue_id) FROM ?:se_queue WHERE company_id = ?i', Registry::get('runtime.company_id'));
    } elseif (!fn_allowed_for('ULTIMATE')) {
        $total_items = db_get_field('SELECT COUNT(queue_id) FROM ?:se_queue WHERE 1');
    }

    return $total_items;
}

function fn_se_get_next_queue($queue_id = 0)
{
    $q = [];
    $conditions = '';

    if (empty($queue_id)) {
        $conditions .= db_quote(' AND queue_id > ?i', $queue_id);
    }

    if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
        $q = db_get_row("SELECT * FROM ?:se_queue WHERE company_id = ?i $conditions ORDER BY queue_id ASC LIMIT 1", Registry::get('runtime.company_id'));
    } elseif (!fn_allowed_for('ULTIMATE')) {
        $q = db_get_row("SELECT * FROM ?:se_queue WHERE 1 $conditions ORDER BY queue_id ASC LIMIT 1");
    }

    return $q;
}

/**
 * Prepare vendors data for indexation
 *
 * @param array  $companies_data Companies data
 * @param int    $company_id     Company identifier
 * @param string $lang_code      Two-letter language code
 *
 * @return array
 */
function fn_se_get_vendors_data($companies_data = [], $company_id = 0, $lang_code = CART_LANGUAGE)
{
    [$vendors, ] = fn_get_companies([
        'company_id'      => array_column($companies_data, 'company_id'),
        'status'          => ObjectStatuses::ACTIVE,
        'for_searchanise' => true,
    ], Tygh::$app['session']['auth'], 0, $lang_code);

    $vendors = array_map(static function($vendor) use ($lang_code) {
        $vendor = fn_array_merge($vendor, fn_get_company_data($vendor['company_id'], $lang_code));
        $vendor['logos'] = fn_get_logos($vendor['company_id']);
        return $vendor;
    }, $vendors);

    $data = [];
    foreach ($vendors as $vendor) {
        $data[] = [
            'id'         => $vendor['company_id'],
            'type'       => 'vendor',
            'link'       => fn_url('companies.products?company_id=' . $vendor['company_id'], 'C', 'http', $lang_code),
            'title'      => $vendor['company'],
            'summary'    => $vendor['company_description'],
            'image_link' => $vendor['logos']['theme']['image']['http_image_path'] ?? '',
            'status'     => $vendor['status'],
        ];
    }

    /**
     * Modifies vendors data for indexing
     *
     * @param array  $companies_data Companies data
     * @param array  $vendors        Vendors data
     * @param array  $data           Indexing data
     * @param int    $company_id     Company identifier
     * @param string $lang_code      Two-letter language code
     */
    fn_set_hook('se_get_vendors_data_post', $companies_data, $vendors, $data, $company_id, $lang_code);

    return $data;
}

/**
 * Prepare pages data for indexation
 * 
 * @param array  $pages_ids  Pages idetifiers
 * @param int    $company_id Company identifier
 * @param string $lang_code  Two-letter language code
 * 
 * @return array
 */
function fn_se_get_pages_data(array $pages_ids = [], $company_id = 0, $lang_code = CART_LANGUAGE)
{
    [$pages, ] = fn_get_pages([
        'for_searchanise' => true,
        'status'          => ObjectStatuses::ACTIVE,
        'page_type'       => ['T', 'B'],
        'item_ids'        => join(',', $pages_ids),
    ], 0, $lang_code);

    $data = [];
    foreach ($pages as $page) {
        $page_id = $page['page_id'];
        $data[] = [
            'id'      => $page_id,
            'link'    => ($page['page_type'] == PAGE_TYPE_LINK)? fn_url($page['link']) : fn_url('pages.view?page_id=' . $page_id, 'C', 'http', $lang_code),
            'title'   => $page['page'],
            'summary' => $page['description'],
        ];
    }

    /**
     * Modifies pages data for indexing
     *
     * @param array  $pages      Pages list
     * @param array  $data       Indexing data
     * @param int[]  $page_ids   Page identifies
     * @param int    $company_id Company identifier
     * @param string $lang_code  Two-letter language code
     */
    fn_set_hook('se_get_pages_data_post', $pages, $data, $pages_ids, $company_id, $lang_code);

    return $data;
}

/**
 * Prepare categories data for indexation
 * 
 * @param array  $categories_ids Categories idetifiers
 * @param int    $company_id     Company identifier
 * @param string $lang_code      Two-letter language code
 * 
 * @return array
 */
function fn_se_get_categories_data(array $categories_ids = [], $company_id = 0, $lang_code = CART_LANGUAGE)
{
    [$categories, ] = fn_get_categories([
        'for_searchanise' => true,
        'plain'           => true,
        'simple'          => false,
        'status'          => ObjectStatuses::ACTIVE,
        'item_ids'        => join(',', $categories_ids),
        'group_by_level'  => false,
    ], $lang_code);

    $data = [];
    $categories_ids = fn_se_get_ids($categories, 'category_id');
    $categories_data = db_get_hash_array("SELECT c.category_id, cd.description FROM ?:categories AS c LEFT JOIN ?:category_descriptions AS cd ON cd.category_id = c.category_id AND cd.lang_code = ?s WHERE c.category_id IN (?n)", 'category_id', $lang_code, $categories_ids);
    foreach ($categories as &$category) {
        $category_id = $category['category_id'];
        if (!isset($categories_data[$category_id])) {
            $categories_data[$category_id] = [];
        }

        $category  = array_merge($category, ['description' => ''], $categories_data[$category_id]);

        $data[] = [
            'id'        => $category['category_id'],
            'parent_id' => $category['parent_id'],
            'link'      => fn_url('categories.view?category_id=' . $category['category_id'], 'C', 'http', $lang_code),
            'title'     => $category['category'],
            'summary'   => $category['description'],
        ];
    }

    return $data;
}

/**
 * Prepare products data for indexation
 * 
 * @param array  $product_ids Products idetifiers
 * @param int    $company_id  Company identifier
 * @param string $lang_code   Two-letter language code
 * @param bool   $fl_echo     If true, process will be printed
 * 
 * @return array
 */
function fn_se_get_products_data(array $product_ids, $company_id = 0, $lang_code = NULL, $fl_echo = true)
{
    $xml = '';
    $products = $schema = $items = [];

    if (!empty($product_ids)) {
        [$products] = fn_get_products([
            'for_searchanise'     => true,
            'disable_searchanise' => true,
            'use_caching'         => false,
            'area'                => 'A',
            'sort_by'             => 'null',
            'pid'                 => $product_ids,
            'extend'              => ['description', 'search_words', 'popularity', 'sales', 'discount', 'companies',],
        ], 0, $lang_code);
    }

    if ($fl_echo) {
        fn_echo('.');
    }

    if (!empty($products)) {
        foreach ($products as &$_product) {
            $_product['exclude_from_calculate'] = true; //pass additional params to fn_gather_additional_products_data for some speed up
        }

        fn_gather_additional_products_data($products, [
            'for_searchanise'  => true,
            'get_features'     => false,
            'get_icon'         => true,
            'get_detailed'     => true,
            'get_options'      => false,
            'get_discounts'    => false,
            'get_taxed_prices' => false
        ]);

        if ($fl_echo) {
            fn_echo('.');
        }

        $usergroups = empty($usergroups)
            ? array_merge(fn_get_default_usergroups(), db_get_hash_array('SELECT a.usergroup_id, a.status, a.type FROM ?:usergroups as a WHERE a.type = ?s ORDER BY a.usergroup_id', 'usergroup_id', UsergroupTypes::TYPE_CUSTOMER))
            : $usergroups;

        fn_se_get_products_additionals($products, $company_id, $lang_code);

        fn_se_get_products_features($products, $company_id, $lang_code);

        foreach ($products as $product) {
            $item = [];
            $data = fn_se_prepare_product_data($product, $usergroups, $company_id, $lang_code);

            foreach ($data as $name => $d) {
                $name = $d['name'] ?? $name;

                if (isset($d['value'])) {
                    $item[$name] = $d['value'];
                    unset($d['value']);
                } else {
                    $item[$name] = '';
                }

                if (!empty($d)) {
                    $schema[$name] = $d;
                }
            }
            $items[] = $item;
        }
    }

    return [
        'schema' => $schema,
        'items'  => $items
    ];
}

/**
 * Adds product features to products list
 * 
 * @param array  $products   Products list
 * @param int    $company_id Company identifier
 * @param string $lang_code  Two-letter language code
 */
function fn_se_get_products_features(array &$products, $company_id, $lang_code)
{
    $product_ids = fn_se_get_ids($products, 'product_id');

    $features_data = db_get_array("SELECT v.feature_id, v.value, v.value_int, v.variant_id, f.feature_type, fd.description as feature, vd.variant, v.product_id FROM ?:product_features_values as v LEFT JOIN ?:product_features as f ON f.feature_id = v.feature_id LEFT JOIN ?:product_features_descriptions as fd ON fd.feature_id = f.feature_id AND fd.lang_code = ?s LEFT JOIN ?:product_feature_variants fv ON fv.variant_id = v.variant_id LEFT JOIN ?:product_feature_variant_descriptions as vd ON vd.variant_id = fv.variant_id AND vd.lang_code = ?s WHERE v.product_id IN (?n) AND (v.variant_id != 0 OR (f.feature_type != ?s AND v.value != '') OR (f.feature_type = ?s) OR v.value_int != '') AND v.lang_code = ?s", $lang_code, $lang_code, $product_ids, ProductFeatures::SINGLE_CHECKBOX, ProductFeatures::SINGLE_CHECKBOX, $lang_code);

    if (!empty($features_data)) {
        foreach ($features_data as $_data) {
            $product_id = $_data['product_id'];
            $feature_id = $_data['feature_id'];

            if (empty($products_features[$product_id][$feature_id])) {
                $products_features[$product_id][$feature_id] = $_data;
            }

            if (!empty($_data['variant_id'])) { // feature has several variants
                $products_features[$product_id][$feature_id]['variants'][$_data['variant_id']] = $_data;
            }
        }

        foreach ($products as &$product) {
            $product['product_features'] = $products_features[$product['product_id']] ?? [];
        }

        unset($product);
    }
}

/**
 * Adds additional products data to products list
 * 
 * @param array  $products   Products data
 * @param int    $company_id Company identifier
 * @param string $lang_code  Two-letter language code
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_se_get_products_additionals(array &$products, $company_id, $lang_code)
{
    $product_ids = fn_se_get_ids($products, 'product_id');

    if (fn_allowed_for('ULTIMATE')) {
        $shared_prices = db_get_hash_multi_array('SELECT product_id, (IF(percentage_discount = 0, price, price - (price * percentage_discount)/100)) as price, usergroup_id FROM ?:ult_product_prices WHERE company_id = ?i AND product_id IN (?n) AND lower_limit = 1', ['product_id', 'usergroup_id'], $company_id, $product_ids);
        $prices = db_get_hash_multi_array('SELECT product_id, (IF(percentage_discount = 0, price, price - (price * percentage_discount)/100)) as price, usergroup_id FROM ?:product_prices WHERE product_id IN (?n) AND lower_limit = 1', ['product_id', 'usergroup_id'], $product_ids);
        $product_categories = db_get_hash_multi_array("SELECT pc.product_id, c.category_id, c.usergroup_ids, c.status FROM ?:categories AS c LEFT JOIN ?:products_categories AS pc ON c.category_id = pc.category_id WHERE c.company_id = ?i AND product_id IN (?n) AND c.status IN (?a)", ['product_id', 'category_id'], $company_id, $product_ids, [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN]);
        $shared_descriptions = db_get_hash_array("SELECT product_id, full_description FROM ?:ult_product_descriptions WHERE company_id = ?i AND product_id IN (?n) AND lang_code = ?s", 'product_id', $company_id, $product_ids, $lang_code);
    } else {
        $prices = db_get_hash_multi_array('SELECT product_id, (IF(percentage_discount = 0, price, price - (price * percentage_discount)/100)) as price, usergroup_id FROM ?:product_prices WHERE product_id IN (?n) AND lower_limit = 1', ['product_id', 'usergroup_id'], $product_ids);
        $product_categories = db_get_hash_multi_array("SELECT pc.product_id, c.category_id, c.usergroup_ids, c.status FROM ?:categories AS c LEFT JOIN ?:products_categories AS pc ON c.category_id = pc.category_id WHERE product_id IN (?n) AND c.status IN (?a)", ['product_id', 'category_id'], $product_ids, [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN]);
    }

    $descriptions = db_get_hash_array("SELECT product_id, full_description FROM ?:product_descriptions WHERE 1 AND product_id IN (?n) AND lang_code = ?s", 'product_id', $product_ids, $lang_code);

    if (fn_allowed_for('MULTIVENDOR')) {
       $company_ids = fn_se_get_active_company_ids();
    }

    foreach ($products as &$product) {
        $product_id = $product['product_id'];

        if (isset($shared_prices[$product_id])) {
            $product['se_prices'] = $shared_prices[$product_id];
        } elseif (isset($prices[$product_id])) {
            $product['se_prices'] = $prices[$product_id];
        } else {
            $product['se_prices'] = ['0' => ['price' => 0]];
        }

        if (!empty($shared_descriptions[$product_id]['full_description'])) {
            $product['se_full_description'] = $shared_descriptions[$product_id]['full_description'];
        } elseif (!empty($descriptions[$product_id]['full_description'])) {
            $product['se_full_description'] = $descriptions[$product_id]['full_description'];
        }

        $product['category_ids'] = $product['category_usergroup_ids'] = [];

        if (!empty($product_categories[$product_id])) {
            foreach ($product_categories[$product_id] as $pc) {
                $product['category_ids'][] = $pc['category_id'];
                $product['category_usergroup_ids'] = array_merge($product['category_usergroup_ids'], explode(',', $pc['usergroup_ids']));
            }
        }

        if (fn_allowed_for('MULTIVENDOR')) {
            $product['active_company'] = in_array($product['company_id'], $company_ids) ? YesNo::YES : YesNo::NO;
        } else {
            $product['active_company'] = YesNo::YES;
        }

        $product['empty_categories'] = (empty($product['category_ids'])) ? YesNo::YES : YesNo::NO;
    }

    unset($product);
}

/**
 * Returns amount for product
 *
 * @param array $product_data    Product data
 * @param array $united_products United products
 *
 * @return int
*/
function fn_se_get_product_quantity(array $product_data, $united_products = [])
{
    if (!empty($united_products) && !empty($product_data['is_master_product'])) {
        $quantity = 0;

        foreach ($united_products as $product) {
            if (!empty($product['is_vendor_product'])) {
                $quantity += fn_se_get_product_quantity($product);
            }
        }

    } elseif (isset($product_data['tracking']) && $product_data['tracking'] === ProductTracking::DO_NOT_TRACK) {
        $quantity = 1;

    } else {
        $quantity = $product_data['amount'];
    }

    return max(-1, min(1, $quantity));
}

/**
 * Returns children products data
 *
 * @param array $product_data Product data
 * @param int   $company_id   Company identifier
 * @param int   $lang_code    2-letters language code
 *
 * @return array Products list
*/
function fn_se_get_children_products(array &$product_data, $company_id, $lang_code)
{
    if (Registry::get('addons.product_variations.status') != ObjectStatuses::ACTIVE) {
        return [];
    }

    $products = $children_product_ids = $vendor_product_ids = [];

    // Find children products (for product_variations)
    if (!empty($product_data['product_type'])) {
        if ($product_data['product_type'] == ProductVariationTypes::PRODUCT_TYPE_VARIATION) {
            // It's children product, skip

        } elseif ($product_data['product_type'] == ProductVariationTypes::PRODUCT_TYPE_SIMPLE && !empty($product_data['variation_group_id'])) {
            // It's main product
            $children_product_ids = ProductVariationsServiceProvider::getGroupRepository()->getProductChildrenIds($product_data['product_id']);
        }

    } else {
        $product_data['product_type'] = ProductVariationTypes::PRODUCT_TYPE_SIMPLE;
    }

    // Find vendor products (for master_products)
    if (Registry::get('addons.master_products.status') == ObjectStatuses::ACTIVE) {
        $product_id_map = MasterProductsServiceProvider::getProductIdMap();

        $product_data['is_master_product'] = $product_id_map->isMasterProduct($product_data['product_id']);
        $product_data['is_vendor_product'] = $product_id_map->isVendorProduct($product_data['product_id']);

        if ($product_data['is_master_product']) {
            $vendor_product_ids = MasterProductsServiceProvider::getProductRepository()->findVendorProductIds($product_data['product_id']);

            if (empty($vendor_product_ids)) {
                // Do not display master products if there is no any vendor products
                $product_data['master_product_status'] = ObjectStatuses::HIDDEN;
            }
        }

    } else {
        $product_data['is_master_product'] = false;
        $product_data['is_vendor_product'] = false;
    }

    $all_childen_ids = array_unique(array_merge(
        $children_product_ids,
        $vendor_product_ids
    ));

    if (!empty($all_childen_ids)) {
        [$products] = fn_get_products([
            'for_searchanise'     => true,
            'disable_searchanise' => true,
            'use_caching'         => false,
            'area'                => 'A',
            'sort_by'             => 'null',
            'pid'                 => $all_childen_ids,
            'extend'              => ['description', 'search_words', 'popularity', 'sales', 'discount', 'companies'],
        ], 0, $lang_code);

        foreach ($products as &$product) {
            if (in_array($product['product_id'], $vendor_product_ids)) {
                $product['is_vendor_product'] = true;
            }
        }

        unset($product);

        fn_se_get_products_additionals($products, $company_id, $lang_code);
        fn_se_get_products_features($products, $company_id, $lang_code);
    }

    return $products;
}

/**
 * Returns values for products attribute
 *
 * @param array  $products       Products list
 * @param string $attribute_code Attribute code
 *
 * @return array Values list
*/
function fn_se_get_attribute_values(array $products, $attribute_code)
{
    $values = [];

	foreach ($products as $product) {
	    if (!empty($product[$attribute_code]) && !is_array($product[$attribute_code])) {
	        $values[] = trim($product[$attribute_code]);
	    }
	}

	return $values;
}

/**
 * Generate feature values for united products
 * 
 * @param array $united_products United product list
 * @param int   $feature_id      Feature identifier
 * @param mixed $variant_name    Feature name and value
 * 
 * @return array Feature values
 */
function fn_se_get_united_products_feature_values(array $united_products, $feature_id, $variant_name)
{
    $features = [];

    if (empty($united_products) || empty($feature_id) || empty($variant_name)) {
        return $features;
    }

    foreach ($united_products as $product_data) {
        if (isset($product_data['product_features'][$feature_id])) {
            $f = $product_data['product_features'][$feature_id];

            if (is_array($variant_name)) {
                [$key_name, $value_name] = $variant_name;

                if (!empty($f[$key_name]) && is_array($f[$key_name])) {
                    foreach ($f[$key_name] as $fv) {
                        if (isset($fv[$value_name])) {
                            $features[] = $fv[$value_name];
                        }
                    }
                }
            } else {
                $features[] = $f[$variant_name];
            }
        }
    }

    return array_unique($features);
}

/**
 * Prepare product features from united products
 * 
 * @param array $united_products United product list
 * 
 * @return array Products features data
 */
function fn_se_prepare_product_features_data(array $united_products)
{
    $types_map = [
        ProductFeatures::DATE              => ValueTypes::TYPE_INT,   // timestamp  (others -> date)
        ProductFeatures::MULTIPLE_CHECKBOX => ValueTypes::TYPE_TEXT,  // multicheckbox with enter other input
        ProductFeatures::TEXT_SELECTBOX    => ValueTypes::TYPE_TEXT,  // select text with enter other input
        ProductFeatures::NUMBER_SELECTBOX  => ValueTypes::TYPE_FLOAT, // select number with enter other input
        ProductFeatures::EXTENDED          => ValueTypes::TYPE_TEXT,  // extended
        ProductFeatures::SINGLE_CHECKBOX   => ValueTypes::TYPE_TEXT,  // single checkbox (not avilable for filter)
        ProductFeatures::TEXT_FIELD        => ValueTypes::TYPE_TEXT,  // input  (others -> text) (not avilable for filterering)
        ProductFeatures::NUMBER_FIELD      => ValueTypes::TYPE_FLOAT, // input for number (others -> number)
    ];

    /**
     * Extends product feature type mapping
     *
     * @param array $types_map Product feature type mapping
     */
    fn_set_hook('se_get_feature_type_mapping', $types_map);

    $entry = [];

    if (empty($united_products)) {
        return $entry;
    }

    $product_data = current($united_products);
    $hasUnitedProducts = count($united_products) > 1;

    if (!empty($product_data['product_features'])) {
        foreach ($product_data['product_features'] as $f) {
            if ($f['feature_type'] == ProductFeatures::NUMBER_FIELD || $f['feature_type'] == ProductFeatures::DATE) {
                continue;
            }

            $name = "f_{$f['feature_id']}";
            $entry[$name] = [
                'name'        => $name,
                'title'       => $f['feature'],
                'text_search' => YesNo::YES,
                'weight'      => 60,
            ];

            if ($hasUnitedProducts) {
                $entry[SE_GROUPED_PREFIX . $name] = [
                    'name'        => SE_GROUPED_PREFIX . $name,
                    'title'       => $f['feature'] . ' - Grouped',
                    'text_search' => YesNo::YES,
                    'weight'      => 60,
                ];
            }

            if ($f['feature_type'] == ProductFeatures::TEXT_SELECTBOX || $f['feature_type'] == ProductFeatures::EXTENDED) {
                $entry[$name]['value'] = $f['variant'];

                if ($hasUnitedProducts) {
                    $entry[SE_GROUPED_PREFIX . $name]['value'] = fn_se_get_united_products_feature_values($united_products, $f['feature_id'], 'variant');
                }

            } elseif ($f['feature_type'] == ProductFeatures::SINGLE_CHECKBOX) {
                $entry[$name]['value'] = $f['feature'];

                if ($hasUnitedProducts) {
                    $entry[SE_GROUPED_PREFIX . $name]['value'] = fn_se_get_united_products_feature_values($united_products, $f['feature_id'], 'feature');
                }

            } elseif ($f['feature_type'] == ProductFeatures::MULTIPLE_CHECKBOX || $f['feature_type'] == ProductFeatures::NUMBER_SELECTBOX) {
                if (!empty($f['variants']) && is_array($f['variants'])) {
                    foreach ($f['variants'] as $fv) {
                        $entry[$name]['value'][] = $fv['variant'];
                    }
                }

                if ($hasUnitedProducts) {
                    $entry[SE_GROUPED_PREFIX . $name]['value'] = fn_se_get_united_products_feature_values($united_products, $f['feature_id'], ['variants', 'variant']);
                }
            } else {
                $entry[$name]['value'] = $f['value'];

                if ($hasUnitedProducts) {
                    $entry[SE_GROUPED_PREFIX . $name]['value'] = fn_se_get_united_products_feature_values($united_products, $f['feature_id'], 'value');
                }
            }
        }

        foreach ($product_data['product_features'] as $f) {
            if (empty($f['feature_type']) || empty($types_map[$f['feature_type']]) || $f['feature_type'] === ProductFeatures::GROUP) {
                continue;
            }

            $name = "feature_{$f['feature_id']}";
            $entry[$name] = [
                'name'  => $name,
                'type'  => $types_map[$f['feature_type']],
                'title' => str_replace('[id]', $f['feature'], __("se_for_feature_id")) . ' - Grouped',
            ];

            if (count($united_products) > 1) {
                $entry[SE_GROUPED_PREFIX . $name] = [
                    'name'  => SE_GROUPED_PREFIX . $name,
                    'type'  => $types_map[$f['feature_type']],
                    'title' => SE_GROUPED_PREFIX . str_replace('[id]', $f['feature'], __("se_for_feature_id")),
                ];
            }

            if ($f['feature_type'] == ProductFeatures::MULTIPLE_CHECKBOX) {
                if (!empty($f['variants']) && is_array($f['variants'])) {
                    foreach ($f['variants'] as $fv) {
                        $entry[$name]['value'][] = $fv['variant_id'];
                    }
                }

                if ($hasUnitedProducts) {
                    $entry[SE_GROUPED_PREFIX . $name]['value'] = fn_se_get_united_products_feature_values($united_products, $f['feature_id'], ['variants', 'variant_id']);
                }
            } else {
                if ($f['feature_type'] == ProductFeatures::TEXT_SELECTBOX || $f['feature_type'] == ProductFeatures::EXTENDED) {
                    $entry[$name]['value'] = $f['variant_id'];

                    if ($hasUnitedProducts) {
                        $entry[SE_GROUPED_PREFIX . $name]['value'] = fn_se_get_united_products_feature_values($united_products, $f['feature_id'], 'variant_id');
                    }

                } elseif ($f['feature_type'] == ProductFeatures::NUMBER_SELECTBOX) {
                    $entry[$name]['value'] = $f['variant'];

                    if ($hasUnitedProducts) {
                        $entry[SE_GROUPED_PREFIX . $name]['value'] = fn_se_get_united_products_feature_values($united_products, $f['feature_id'], 'variant');
                    }

                } elseif ($f['feature_type'] == ProductFeatures::NUMBER_FIELD || $f['feature_type'] == ProductFeatures::DATE) {
                    $entry[$name]['value'] = $f['value_int'];

                    if ($hasUnitedProducts) {
                        $entry[SE_GROUPED_PREFIX . $name]['value'] = fn_se_get_united_products_feature_values($united_products, $f['feature_id'], 'value_int');
                    }

                } elseif ($f['feature_type'] == ProductFeatures::SINGLE_CHECKBOX) {
                    $entry[$name]['value'] = ($f['value'] == YesNo::YES) ? YesNo::YES : '';

                    if ($hasUnitedProducts) {
                        $entry[SE_GROUPED_PREFIX . $name]['value'] = array_map(function($v) {
                            return $v == YesNo::YES ? YesNo::YES : '';
                        }, fn_se_get_united_products_feature_values($united_products, $f['feature_id'], 'value'));
                    }

                } else {// ProductFeatures::TEXT_FIELD
                    $entry[$name]['value'] = $f['value'];

                    if ($hasUnitedProducts) {
                        $entry[SE_GROUPED_PREFIX . $name]['value'] = fn_se_get_united_products_feature_values($united_products, $f['feature_id'], 'value');
                    }
                }
            }
        }
    }

    return $entry;
}

/**
 * Prepare products data for import
 * 
 * @param array  $product_data Product data
 * @param array  $usergroups   Usergroups data
 * @param int    $company_id   Company identifier
 * @param string $lang_code    Two-letter language code
 * 
 * @return array prepared product data
 */
function fn_se_prepare_product_data(array $product_data, array $usergroups, $company_id, $lang_code)
{
    $thumbnail = [];
    $united_products = array_merge([$product_data], fn_se_get_children_products($product_data, $company_id, $lang_code));

    $entry = [
        'id' => [
            'value' => $product_data['product_id'],
            'title' => __('se_product_id'),
        ],
        'title' => [
            'value' => $product_data['product'],
            'title' => __('se_title'),
        ],
        'summary' => [
            'value' => (!empty($product_data['short_description']) ? $product_data['short_description'] : $product_data['full_description']),
            'title' => __('se_summary'),
        ],
        'link' => [
            'value' => fn_url('products.view?product_id=' . $product_data['product_id'], 'C', 'http', $lang_code),
            'title' => __('se_link'),
        ],
        'price'  => [
            'value' => fn_format_price(min(fn_se_get_attribute_values($united_products, 'price'))),
            'title' => __('se_price'),
        ],
        'list_price'  => [
            'value' => fn_format_price(min(fn_se_get_attribute_values($united_products, 'list_price'))),
            'title' => __('se_list_price'),
        ],
        'quantity' => [
            'value' => fn_se_get_product_quantity($product_data, $united_products),
            'title' => __('se_quantity'),
        ],
        'product_code' => [
            'value' => $product_data['product_code'],
            'title' => __('se_product_code'),
        ],
        'image_link' => [
            'title' => __('se_image_link'),
        ],
    ];

    if (!empty($product_data['main_pair'])) {
        $thumbnail = fn_image_to_display($product_data['main_pair'], SE_IMAGE_SIZE, SE_IMAGE_SIZE);
    }

    if (!empty($thumbnail['image_path'])) {
        $image_link = $thumbnail['image_path'];

    } elseif (!empty($product_data['main_pair']['detailed']['http_image_path'])) {
        $image_link = $product_data['main_pair']['detailed']['http_image_path'];

    } else {
        $image_link = '';
    }

    $entry['image_link']['value'] = htmlspecialchars($image_link);

    $united_products = array_merge([$product_data], fn_se_get_children_products($product_data, $company_id, $lang_code));

    if (!empty($product_data['search_words'])) {
        $entry['search_words'] = [
            'name'        => 'search_words',
            'title'       => __('search_words'),
            'text_search' => YesNo::YES,
            'weight'      => 100,
            'value'       => $product_data['search_words'],
        ];
    }

    $entry += fn_se_prepare_product_features_data($united_products);

    if (!empty($product_data['short_description']) && !empty($product_data['se_full_description'])) {
        $entry['full_description'] = [
            'name'        => 'full_description',
            'title'       => __('full_description'),
            'text_search' => YesNo::YES,
            'weight'      => 40,
        ];
        $entry['full_description']['value'] = $product_data['se_full_description'];
    }

    //
    //
    //
    $entry['category_id'] = [
        'name'  => 'category_id',
        'title' => __('se_category_Id'),
        'value' => []
    ];
    foreach ($product_data['category_ids'] as $category_id) {
        $entry['category_id']['value'][] = $category_id;
    }

    //
    //
    //
    $entry['category_usergroup_ids'] = [
        'name'  => 'category_usergroup_ids',
        'title' => __('se_category_usergroup_ids'),
        'value' => []
    ];
    foreach (array_unique($product_data['category_usergroup_ids']) as $usergroup_id) {
        $entry['category_usergroup_ids']['value'][] = (int) $usergroup_id;
    }

    //
    //
    //
    $entry['usergroup_ids'] = [
        'name'  => 'usergroup_ids',
        'title' => __('se_usergroup_ids'),
        'value' => []
    ];
    $product_data['usergroup_ids'] = empty($product_data['usergroup_ids']) ? [USERGROUP_ALL] : explode(',', $product_data['usergroup_ids']);
    foreach (array_unique($product_data['usergroup_ids']) as $usergroup_id) {
        $entry['usergroup_ids']['value'][] = (int) $usergroup_id;
    }

    //
    //
    //
    foreach ($usergroups as $usergroup) {
        $usergroup_id = $usergroup['usergroup_id'];
        $name = SE_PRICE_USERGROUP_PREFIX . intval($usergroup_id);
        $prices = array_map(function($product_data) use ($usergroup_id) {
            return !empty($product_data['se_prices'][$usergroup_id]['price']) ? $product_data['se_prices'][$usergroup_id]['price'] : $product_data['se_prices'][USERGROUP_ALL]['price'];
        }, $united_products);

        $entry[$name] = [
            'name'  => $name,
            'title' => str_replace('[id]', $usergroup_id, __("se_price_for_usergroup_id")),
            'type'  => ValueTypes::TYPE_FLOAT,
            'value' => min($prices),
        ];
    }

    //
    //
    //
    if ($entry['quantity']['value'] > 0) {
        $entry['in_stock'] = [
            'name'  => 'in_stock',
            'title' =>  __('in_stock'),
            'type'  => ValueTypes::TYPE_TEXT,
            'value' => YesNo::YES,
        ];
    }

    if (!empty($product_data['company_name'])) {
        $entry['company_name'] = [
            'name'        => 'company_name',
            'title'       => __('company'),
            'text_search' => YesNo::YES,
            'value'       => $product_data['company_name'],
        ];
    }

    //
    //
    //
    if ($product_data['free_shipping'] == YesNo::YES) {
        $entry['free_shipping'] = [
            'name'  => 'free_shipping',
            'title' =>  __('free_shipping'),
            'type'  => ValueTypes::TYPE_TEXT,
            'value' => YesNo::YES,
        ];
    }

    // Collect grouped data
    foreach ($entry as $entry_id => &$entry_data) {
        if (in_array($entry_id, ['product_code', 'title', 'full_description', 'search_words'])) {
            $attribute = $entry_id == 'title'
                ? 'product'
                : ($entry_id == 'full_description' ? 'se_full_description' : $entry_id);
            $united_values = fn_se_get_attribute_values($united_products, $attribute);

            if (count(array_unique($united_values)) > 1) {
                $entry[SE_GROUPED_PREFIX . $entry_id] = [
                    'name'        => SE_GROUPED_PREFIX . $entry_id,
                    'value'       => array_unique(array_slice($united_values, 1)),
                    'title'       => $entry_data['title'] . ' - Grouped',
                    'text_search' => YesNo::YES,
                    'type'        => ValueTypes::TYPE_TEXT,
                    'sorting'     => YesNo::NO,
                ];

                if (isset($entry_data['weight'])) {
                    $entry[SE_GROUPED_PREFIX . $entry_id]['weight'] = $entry_data['weight'];
                }
            }
        }
    }

    /**
     * Company_id
     */
    $entry['company_id'] = [
        'name'  => 'company_id',
        'title' => __('se_company_id'),
        'type'  => ValueTypes::TYPE_TEXT,
        'value' => fn_allowed_for('MULTIVENDOR')
            ? array_unique([0, $product_data['company_id']])
            : $product_data['company_id'],
    ];

    /*
     * Support for add-on "Vendor data premoderation".
     */
    if (!empty($product_data['approved']) && in_array($product_data['approved'], [YesNo::YES, YesNo::NO])) {
        $entry['approved'] = [
            'name'  => 'approved',
            'title' => __('product_approval'),
            'type'  => ValueTypes::TYPE_TEXT,
            'value' => $product_data['approved']
        ];
    }

    /**
     * Support for add-on "Product variations".
     */
    if (isset($product_data['product_type'])) {
        $entry['product_type'] = [
            'name'  => 'product_type',
            'title' => __('product_type'),
            'type'  => ValueTypes::TYPE_TEXT,
            'value' => $product_data['product_type'],
        ];
    }

    if (isset($product_data['parent_product_id'])) {
        $entry['parent_product_id'] = [
            'name'  => 'parent_product_id',
            'title' => __('variation_parent_product_id'),
            'type'  => ValueTypes::TYPE_TEXT,
            'value' => (int) $product_data['parent_product_id'],
        ];
    }

    /**
     * Support for add-on "Master products".
     */
    if (isset($product_data['master_product_status'])) {
        $entry['master_product_status'] = [
            'name'  => 'master_product_status',
            'title' => __('se_master_product_status'),
            'type'  => ValueTypes::TYPE_TEXT,
            'value' => $product_data['master_product_status'],
        ];
    }

    if (isset($product_data['is_master_product'])) {
        $entry['is_master_product'] = [
            'name'  => 'is_master_product',
            'title' => __('se_is_master_product'),
            'type'  => ValueTypes::TYPE_NONE,
            'value' => YesNo::toId($product_data['is_master_product']),
        ];

        if ($product_data['is_master_product']) {
            $entry['company_id']['value'] = 0;
        }
    }

    if (isset($product_data['is_vendor_product']) && $product_data['is_vendor_product']) {
        $entry['company_id']['value'] = $product_data['company_id'];
    }

    /**
     * Support for add-on "Age verification".
     */
    if (!empty($product_data['need_age_verification'])) {
        $entry['age_limit'] = [
            'name'  => 'age_limit',
            'title' => __('age_limit'),
            'type'  => ValueTypes::TYPE_INT,
            'value' => $product_data['need_age_verification'] == YesNo::YES ? $product_data['searchanise_age_limit'] : 0,
        ];
    }

    /**
     * Support for add-on "Bestsellers".
     */
    if (Registry::ifGet('addons.bestsellers.status', ObjectStatuses::NEW_OBJECT) == ObjectStatuses::ACTIVE) {
        // Count of sales for "Sort by Bestselling".
        $entry['sales'] = [
            'name'  => 'sales',
            'title' => __('sales_amount'),
            'type'  => ValueTypes::TYPE_INT,
            'value' => is_null($product_data['sales']) ? 0 : $product_data['sales']
        ];

        // Value of discount for "Sort by discount".
        $entry['discount'] = [
            'name'  => 'discount',
            'title' => __('discount'),
            'type'  => ValueTypes::TYPE_FLOAT,
            'value' => is_null($product_data['discount']) ? 0 : $product_data['discount']
        ];
    }

    //
    //
    //
    $additional_attrs = [
        'weight'           => ValueTypes::TYPE_FLOAT,
        'popularity'       => ValueTypes::TYPE_FLOAT,
        'amount'           => ValueTypes::TYPE_INT,
        'timestamp'        => ValueTypes::TYPE_INT,
        'position'         => ValueTypes::TYPE_INT,
        'empty_categories' => ValueTypes::TYPE_TEXT,
        'status'           => ValueTypes::TYPE_TEXT,
        'active_company'   => ValueTypes::TYPE_TEXT,
    ];

    if (!empty($product_data['sales_amount'])) {
        $additional_attrs['sales_amount'] = ValueTypes::TYPE_INT;
    }

    foreach ($additional_attrs as $name => $type) {
        if ($name === 'empty_categories') {
            $title = __('se_empty_categories');
        } elseif ($name == 'active_company') {
            $title = __('se_active_companies');
        } else {
            $title = __($name);
        }

        $entry[$name] = [
            'name'  => $name,
            'title' => $title,
            'type'  => $type,
            'value' => $product_data[$name] ?? '',
        ];
    }

    /**
     * Process final product data
     *
     * @param array  $product_data Product data
     * @param array  $usergroups   Usergroups data
     * @param int    $company_id   Company identifier
     * @param string $lang_code    Two-letter language code
     * @param array  $entry        Final product data
     */
    fn_set_hook('se_prepare_product_data_post', $product_data, $usergroups, $company_id, $lang_code, $entry);

    return $entry;
}

function fn_se_prepare_facet_data($filter_data)
{
    $entry = [];

    if (!empty($filter_data['feature_id'])) {
        $entry['name'] = "feature_{$filter_data['feature_id']}";

    } elseif (!empty($filter_data['field_type']) && $filter_data['field_type'] == ProductFilterProductFieldTypes::PRICE) {
        $entry['name'] = "price";

    } elseif (!empty($filter_data['field_type']) && $filter_data['field_type'] == ProductFilterProductFieldTypes::FREE_SHIPPING) {
        $entry['name'] = "free_shipping";

    } elseif (!empty($filter_data['field_type']) && $filter_data['field_type'] == ProductFilterProductFieldTypes::VENDOR) {
        $entry['name'] = "company_id";

    } elseif (!empty($filter_data['field_type']) && $filter_data['field_type'] == ProductFilterProductFieldTypes::IN_STOCK) {
        $entry['name'] = "in_stock";

    } else {
        return []; //unknown attribute
    }

    $entry['facet']['title']    = $filter_data['filter'];
    $entry['facet']['position'] = $filter_data['position'];

    if (
        $filter_data['feature_type'] == ProductFeatures::NUMBER_FIELD ||
        $filter_data['feature_type'] == ProductFeatures::NUMBER_SELECTBOX ||
        $filter_data['feature_type'] == ProductFeatures::DATE ||
        (!empty($filter_data['condition_type']) && $filter_data['condition_type'] == 'D')
    ) {
        $entry['facet']['type'] = "slider";
    }

    return $entry;
}
