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
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Languages\Languages;
use Tygh\Navigation\LastView;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Storefront\Storefront;
use Tygh\Tools\SecurityHelper;
use Tygh\Enum\YesNo;
use Tygh\Enum\ObjectStatuses;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Gets subcategories list for current category (first-level categories only)
 *
 * @param  int    $category_id Category identifier
 * @param  array  $params      Params
 * @param  string $lang_code   2-letters language code
 * @return array
 */
function fn_get_subcategories($category_id = '0', $params = array(), $lang_code = CART_LANGUAGE)
{
    if (is_string($params)) { // Backward compatibility
        $lang_code = $params;
        $params = array();
    }

    $params = array_merge(array(
        'category_id' => $category_id,
        'visible' => true,
        'get_images' => true,
    ), $params);

    /**
     * Change params before subcategories select
     *
     * @param int    $category_id Category identifier
     * @param int    $params      Params of subcategories search
     * @param string $lang_code   2-letters language code
     */
    fn_set_hook('get_subcategories_params', $category_id, $lang_code, $params);

    list($categories) = fn_get_categories($params, $lang_code);

    /**
     * Change subcategories
     *
     * @param int    $params     Params of subcategories search
     * @param string $lang_code  2-letters language code
     * @param array  $categories Subcategories
     */
    fn_set_hook('get_subcategories_post', $params, $lang_code, $categories);

    return $categories;
}

/**
 * Gets categories tree (multidimensional) from the current category
 *
 * @param int $category_id Category identifier
 * @param boolean $simple Flag that defines if category names path and product count should not be gathered
 * @param string $lang_code 2-letters language code
 * @return array Array of subcategories as a hierarchical tree
 */
function fn_get_categories_tree($category_id = '0', $simple = true, $lang_code = CART_LANGUAGE)
{
    $params = array (
        'category_id' => $category_id,
        'simple' => $simple
    );

    /**
     * Change params before categories tree select
     *
     * @param int     $category_id Category identifier
     * @param boolean $simple      Flag that defines if category names path and product count should not be gathered
     * @param string  $lang_code   2-letters language code
     * @param int     $params      Params of subcategories search
     */
    fn_set_hook('get_categories_tree_params', $category_id, $simple, $lang_code, $params);

    list($categories, ) = fn_get_categories($params, $lang_code);

    /**
     * Change categories tree
     *
     * @param int    $params     Params of subcategories search
     * @param string $lang_code  2-letters language code
     * @param array  $categories Categories tree
     */
    fn_set_hook('get_categories_tree_post', $params, $lang_code, $categories);

    return $categories;
}

/**
 * Gets categories tree (plain) from the current category
 *
 * @param int          $category_id    Category identifier
 * @param bool         $simple         Flag that defines if category names path and product count should not be gathered
 * @param string       $lang_code      Two-letters language code
 * @param int[]|string $company_ids    Identifiers of companies for that categories should be gathered
 * @param int[]|null   $storefront_ids Storefront identifiers
 *
 * @return array Array of subategories as a simple list
 */
function fn_get_plain_categories_tree($category_id = '0', $simple = true, $lang_code = CART_LANGUAGE, $company_ids = '', $storefront_ids = null)
{
    $params = [
        'category_id'    => $category_id,
        'simple'         => $simple,
        'visible'        => false,
        'plain'          => true,
        'company_ids'    => $company_ids,
        'storefront_ids' => $storefront_ids,
    ];

    if (fn_allowed_for('MULTIVENDOR') && UserTypes::isVendor(Tygh::$app['session']['auth']['user_type'])) {
        $params['status'] = [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN];
    }

    /**
     * Change params before plain categories tree select
     *
     * @param int     $category_id Category identifier
     * @param boolean $simple      Flag that defines if category names path and product count should not be gathered
     * @param string  $lang_code   2-letters language code
     * @param array   $company_ids Identifiers of companies for that categories should be gathered
     * @param int     $params      Params of subcategories search
     */
    fn_set_hook('get_plain_categories_tree_params', $category_id, $simple, $lang_code, $company_ids, $params);

    list($categories, ) = fn_get_categories($params, $lang_code);

    /**
     * Change categories tree
     *
     * @param int    $params     Params of subcategories search
     * @param string $lang_code  2-letters language code
     * @param array  $categories Categories tree
     */
    fn_set_hook('get_plain_categories_tree_post', $params, $lang_code, $categories);

    return $categories;
}

/**
 * Categories sorting function, compares two categories
 *
 * @param array $a First category data
 * @param array $b Second category data
 * @return int Result of comparison categories positions or categories names( if both categories positions are empty)
 */
function fn_cat_sort($a, $b)
{
    /**
     * Changes categories data before the comparison
     *
     * @param array $a First category data
     * @param array $b Second category data
     */
    fn_set_hook('cat_sort_pre', $a, $b);

    $result = 0;

    if (empty($a["position"]) && empty($b['position'])) {
        $result = strnatcmp($a["category"], $b["category"]);
    } else {
        $result = strnatcmp($a["position"], $b["position"]);
    }

    /**
     * Changes the result of categories comparison
     *
     * @param array $a      First category data
     * @param array $b      Second category data
     * @param int   $result Result of comparison categories positions or categories names( if both categories positions are empty)
     */
    fn_set_hook('cat_sort_post', $a, $b, $result);

    return $result;
}

/**
 * Gets categories tree beginning from category identifier defined in params or root category
 * @param array $params Categories search params
 *      category_id - Root category identifier
 *      visible - Flag that defines if only visible categories should be included
 *      current_category_id - Identifier of current node for visible categories
 *      simple - Flag that defines if category path should be getted as set of category IDs
 *      plain - Flag that defines if continues list of categories should be returned
 *      --------------------------------------
 *      Examples:
 *      Gets whole categories tree:
 *      fn_get_categories()
 *      --------------------------------------
 *      Gets subcategories tree of the category:
 *      fn_get_categories(array(
 *          'category_id' => 123
 *      ))
 *      --------------------------------------
 *      Gets all first-level nodes of the category
 *      fn_get_categories(array(
 *          'category_id' => 123,
 *          'visible' => true
 *      ))
 *      --------------------------------------
 *      Gets all visible nodes of the category, start from the root
 *      fn_get_categories(array(
 *          'category_id' => 0,
 *          'current_category_id' => 234,
 *          'visible' => true
 *      ))
 * @param string $lang_code 2-letters language code
 * @return array Categories tree
 */
function fn_get_categories($params = array(), $lang_code = CART_LANGUAGE)
{
    /**
     * Changes params for the categories search
     *
     * @param array  $params    Categories search params
     * @param string $lang_code 2-letters language code
     */
    fn_set_hook('get_categories_pre', $params, $lang_code);

    $default_params = [
        'category_id'         => 0,
        'visible'             => false,
        'current_category_id' => 0,
        'simple'              => true,
        'plain'               => false,
        'limit'               => 0,
        'item_ids'            => '',
        'group_by_level'      => true,
        'get_images'          => false,
        'category_delimiter'  => '/',
        'get_frontend_urls'   => false,
        'max_nesting_level'   => null,    // null means no limitation
        'get_company_name'    => false,
        'storefront_ids'      => null,
    ];

    $params = array_merge($default_params, $params);

    if (SiteArea::isStorefront(AREA) && !$params['storefront_ids']) {
        $params['storefront_ids'] = [0, StorefrontProvider::getStorefront()->storefront_id];
    }

    $auth = & Tygh::$app['session']['auth'];

    if (SiteArea::isAdmin(AREA) && UserTypes::isVendor($auth['user_type'])) {
        $storefront_repository = StorefrontProvider::getRepository();
        /** @var array $company_storefronts */
        $company_storefronts = $storefront_repository->findAvailableForCompanyId($auth['company_id'], false) ?: [];
        $storefront_ids = array_map(
            static function ($company_storefront) {
                return $company_storefront->storefront_id;
            },
            $company_storefronts
        );
        $storefront_ids[] = 0;
        if (isset($params['storefront_ids'])) {
            $params['storefront_ids'] = array_intersect((array) $params['storefront_ids'], $storefront_ids);
        } else {
            $params['storefront_ids'] = $storefront_ids;
        }
    }

    $sortings = [
        'timestamp' => '?:categories.timestamp',
        'name'      => '?:category_descriptions.category',
        'position'  => [
            '?:categories.is_trash',
            '?:categories.position',
            '?:category_descriptions.category'
        ],
        'product_count' => '?:categories.product_count',
        'status' => '?:categories.status'
    ];

    $fields = [
        '?:categories.category_id',
        '?:categories.parent_id',
        '?:categories.id_path',
        '?:category_descriptions.category',
        '?:categories.position',
        '?:categories.status',
        '?:categories.company_id',
        '?:categories.storefront_id',
    ];

    if (!$params['simple']) {
        $fields[] = '?:categories.product_count';
    }

    if (empty($params['current_category_id']) && !empty($params['product_category_id'])) {
        $params['current_category_id'] = $params['product_category_id'];
    }

    $condition = '';

    if (AREA == 'C') {
        $_statuses = array('A'); // Show enabled products/categories
        $condition .= fn_get_localizations_condition('?:categories.localization', true);
        $condition .= " AND (" . fn_find_array_in_set($auth['usergroup_ids'], '?:categories.usergroup_ids', true) . ")";
        $condition .= db_quote(" AND ?:categories.status IN (?a)", $_statuses);
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(" AND ?:categories.status IN (?a)", $params['status']);
    }

    if (isset($params['parent_category_id'])) {
        // set parent id, that was set in block properties
        $params['category_id'] = $params['parent_category_id'];
    }

    if ($params['visible'] == true && empty($params['b_id'])) {
        if (!empty($params['current_category_id'])) {
            $cur_id_path = db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $params['current_category_id']);
            if (!empty($cur_id_path)) {
                $parent_categories_ids = explode('/', $cur_id_path);
            }
        }
        if (!empty($params['category_id']) || empty($parent_categories_ids)) {
            $parent_categories_ids[] = $params['category_id'];
        }
        $condition .= db_quote(" AND ?:categories.parent_id IN (?n)", $parent_categories_ids);
    }

    if (!empty($params['category_id'])) {
        $from_id_path = db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $params['category_id']);
        $condition .= db_quote(" AND ?:categories.id_path LIKE ?l", "$from_id_path/%");
    } elseif (!empty($params['category_ids']) && is_array($params['category_ids'])) {
        $condition .= db_quote(' AND ?:categories.category_id IN (?n)', $params['category_ids']);
    }

    if (!empty($params['item_ids'])) {
        $condition .= db_quote(' AND ?:categories.category_id IN (?n)', explode(',', $params['item_ids']));
    }

    if (!empty($params['except_id']) && (empty($params['item_ids']) || !empty($params['item_ids']) && !in_array($params['except_id'], explode(',', $params['item_ids'])))) {
        $condition .= db_quote(' AND ?:categories.category_id != ?i AND ?:categories.parent_id != ?i', $params['except_id'], $params['except_id']);
    }

    if (!empty($params['period']) && $params['period'] != 'A') {
        list($params['time_from'], $params['time_to']) = fn_create_periods($params);
        $condition .= db_quote(" AND (?:categories.timestamp >= ?i AND ?:categories.timestamp <= ?i)", $params['time_from'], $params['time_to']);
    }

    if (!empty($params['max_nesting_level'])) {
        if (!empty($params['parent_category_id'])) {
            $parent_nesting_level = (int) db_get_field("SELECT level FROM ?:categories WHERE category_id = ?i", $params['parent_category_id']);
        } else {
            $parent_nesting_level = 0;
        }
        $condition .= db_quote(" AND ?:categories.level <= ?i", $params['max_nesting_level'] + $parent_nesting_level);
    }

    if (isset($params['search_query']) && !fn_is_empty($params['search_query'])) {
        $condition .= db_quote(' AND ?:category_descriptions.category LIKE ?l', '%' . trim($params['search_query']) . '%');
    }

    if (isset($params['search_by_category_name']) && !fn_is_empty($params['search_by_category_name'])) {
        $condition .= db_quote(' AND ?:category_descriptions.category = ?s', trim($params['search_by_category_name']));
    }

    if (!empty($params['company_ids']) && is_array($params['company_ids'])) {
        $condition .= fn_get_company_condition('?:categories.company_id', true, $params['company_ids']);
    }

    if ($params['storefront_ids'] !== null) {
        $condition .= db_quote(' AND ?:categories.storefront_id IN (?n)', $params['storefront_ids']);
    }

    $limit = $join = $group_by = '';

    /**
     * Changes SQL params for the categories search
     *
     * @param array  $params    Categories search params
     * @param string $join      Join parametrs
     * @param string $condition Request condition
     * @param array  $fields    Selectable fields
     * @param string $group_by  Group by parameters
     * @param array  $sortings  Sorting fields
     * @param string $lang_code Language code
     */
    fn_set_hook('get_categories', $params, $join, $condition, $fields, $group_by, $sortings, $lang_code);

    if ($params['get_company_name']) {
        $fields[] = '?:companies.company';
        $join .= ' LEFT JOIN ?:companies ON ?:companies.company_id = ?:categories.company_id';
    }

    if (!empty($params['limit'])) {
        $limit = db_quote(' LIMIT 0, ?i', $params['limit']);
    }

    $sorting = db_sort($params, $sortings, 'position', 'asc');

    if (!empty($params['get_conditions'])) {
        return array($fields, $join, $condition, $group_by, $sorting, $limit);
    }

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field(
            'SELECT COUNT(DISTINCT(?:categories.category_id)) FROM ?:categories'
            . ' LEFT JOIN ?:category_descriptions ON ?:categories.category_id = ?:category_descriptions.category_id' // if we move this join inside the $join variable some add-ons may fail
            . ' AND ?:category_descriptions.lang_code = ?s'
            . ' ?p WHERE 1=1 ?p ?p ?p',
            $lang_code,
            $join,
            $condition,
            $group_by,
            $sorting
        );
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $categories = db_get_hash_array(
        'SELECT ?p FROM ?:categories'
        . ' LEFT JOIN ?:category_descriptions ON ?:categories.category_id = ?:category_descriptions.category_id' // if we move this join inside the $join variable some add-ons may fail
        . ' AND ?:category_descriptions.lang_code = ?s'
        . ' ?p WHERE 1=1 ?p ?p ?p ?p',
        'category_id',
        implode(',', $fields),
        $lang_code,
        $join,
        $condition,
        $group_by,
        $sorting,
        $limit
    );

    /**
     * Process categories list after getting it
     * @param array  $categories Categories list
     * @param array  $params     Categories search params
     * @param string $join       Join parametrs
     * @param string $condition  Request condition
     * @param array  $fields     Selectable fields
     * @param string $group_by   Group by parameters
     * @param array  $sortings   Sorting fields
     * @param string $sorting    Sorting parameters
     * @param string $limit      Limit parameter
     * @param string $lang_code  Language code
     */
    fn_set_hook('get_categories_after_sql', $categories, $params, $join, $condition, $fields, $group_by, $sortings, $sorting, $limit, $lang_code);

    if (empty($categories)) {
        return array(array(), $params);
    }

    // @TODO remove from here, because active category may not exist in the resulting set. This is the job for controller.
    if (!empty($params['active_category_id']) && !empty($categories[$params['active_category_id']])) {
        $categories[$params['active_category_id']]['active'] = true;
        Registry::set('runtime.active_category_ids', explode('/', $categories[$params['active_category_id']]['id_path']));
    }

    $categories_list = array();
    if ($params['simple'] == true || $params['group_by_level'] == true) {
        $child_for = array_keys($categories);
        $where_condition = !empty($params['except_id']) ? db_quote(' AND category_id != ?i', $params['except_id']) : '';
        $has_children = db_get_hash_array("SELECT category_id, parent_id FROM ?:categories WHERE parent_id IN(?n) ?p", 'parent_id', $child_for, $where_condition);
    }

    $category_ids = array();
    // Group categories by the level (simple)
    if ($params['simple']) {
        foreach ($categories as $k => $v) {
            $v['level'] = substr_count($v['id_path'], '/');
            if (isset($has_children[$k])) {
                $v['has_children'] = $has_children[$k]['category_id'];
            }
            $categories_list[$v['level']][$v['category_id']] = $v;
            $category_ids[] = $v['category_id'];
        }
    } elseif ($params['group_by_level']) {
        $categories_for_parents = $categories;
        /**
         * When searching categories by parent product ID, parent categories are not present in the resulting
         * $categories array and must be fetched to get the full category path.
         */
        if ($params['plain']
            && (!empty($params['parent_category_id'])
                || !empty($params['item_ids'])
            )
        ) {
            $categories_for_parents = fn_get_categories_list_with_parents(
                array_column($categories, 'category_id'),
                $lang_code
            );

            foreach ($categories_for_parents as $category_for_parents) {
                if (!empty($category_for_parents['parents'])) {
                    $categories_for_parents += $category_for_parents['parents'];
                }
            }
        }

        // Group categories by the level (simple) and literalize path
        foreach ($categories as $k => $v) {
            $path = explode('/', $v['id_path']);
            $category_path = array();
            foreach ($path as $__k => $__v) {
                $category_path[$__v] = @$categories_for_parents[$__v]['category'];
            }
            $v['category_path'] = implode($params['category_delimiter'], $category_path);
            $v['level'] = substr_count($v['id_path'], "/");
            if (isset($has_children[$k])) {
                $v['has_children'] = $has_children[$k]['category_id'];
            }
            $categories_list[$v['level']][$v['category_id']] = $v;
            $category_ids[] = $v['category_id'];
        }
    } else {
        // @FIXME: Seems that this code isn't being executed anywhere
        $categories_list = $categories;
        $category_ids = fn_fields_from_multi_level($categories_list, 'category_id', 'category_id');
    }

    ksort($categories_list, SORT_NUMERIC);
    $categories_list = array_reverse($categories_list, !$params['simple'] && !$params['group_by_level']);

    // Lazy-load category image pairs
    if ($params['get_images']) {
        $image_pairs_for_categories = fn_get_image_pairs($category_ids, 'category', 'M', true, true, $lang_code);
    }

    // Rearrangement of subcategories and filling with images
    foreach ($categories_list as $level => $categories_of_level) {
        // Fill categories' image pairs for plain structure of array
        if ($params['get_images']
            && !$params['simple']
            && !$params['group_by_level']
            && !empty($image_pairs_for_categories[$level])
        ) {
            $categories_list[$level]['main_pair'] = reset($image_pairs_for_categories[$level]);
        }
        foreach ($categories_of_level as $category_id => $category_data) {
            // Fill categories' image pairs for multi-level structure of array
            if ($params['get_images']
                && !empty($image_pairs_for_categories[$category_id])
                && ($params['simple'] || $params['group_by_level'])
            ) {
                $categories_list[$level][$category_id]['main_pair'] = reset($image_pairs_for_categories[$category_id]);
            }

            // Move subcategories to their parents' elements
            if (
                isset($category_data['parent_id'])
                &&
                isset($categories_list[$level + 1][$category_data['parent_id']])
            ) {
                $categories_list[$level + 1][$category_data['parent_id']]['subcategories'][] = $categories_list[$level][$category_id];
                unset($categories_list[$level][$category_id]);
            }
        }
    }

    if (!empty($params['get_frontend_urls'])) {
        foreach ($categories_list as &$category) {
            $category['url'] = fn_url('categories.view?category_id=' . $category['category_id'], 'C');
        }
    }

    if ($params['group_by_level'] == true) {
        $categories_list = array_pop($categories_list);
    }

    if ($params['plain'] == true) {
        $categories_list = fn_multi_level_to_plain($categories_list, 'subcategories');
    }

    if (!empty($params['item_ids'])) {
        $categories_list = fn_sort_by_ids($categories_list, explode(',', $params['item_ids']), 'category_id');
    }

    if (!empty($params['add_root'])) {
        array_unshift($categories_list, array('category_id' => 0, 'category' => $params['add_root']));
    }

    /**
     * Process categories list before cutting second and fird levels
     *
     * @param array $categories_list Categories list
     * @param array $params          Categories search params
     */
    fn_set_hook('get_categories_before_cut_levels', $categories_list, $params);

    if (
        empty($params['skip_group_by_storefronts'])
        && (
            fn_allowed_for('ULTIMATE')
            && !Registry::get('runtime.company_id')
            || fn_allowed_for('MULTIVENDOR')
            && SiteArea::isAdmin(AREA)
            && Registry::get('runtime.is_multiple_storefronts')
        )
    ) {
        $category_prefix = fn_allowed_for('ULTIMATE') ? __('vendor') : __('storefront');
        fn_increase_category_level($categories_list);
        if (empty($params['category_id'])) {
            $tmp = $categories_list;
            if (!empty($params['group_by_level']) && empty($params['plain'])) {
                foreach ($tmp as $category_id => $category) {
                    if (!isset($category['storefront_id'])) {
                        continue;
                    }

                    unset($tmp[$category_id]);
                    $_key = 'storefront_' . $category['storefront_id'];
                    $tmp[$_key]['storefront_categories'] = YesNo::YES;
                    $tmp[$_key]['category_id'] = $category['category_id'];
                    $tmp[$_key]['storefront_id'] = $category['storefront_id'];
                    $tmp[$_key]['subcategories'][] = $category;
                    //legacy
                    $tmp[$_key]['company_categories'] = YesNo::YES;
                    $tmp[$_key]['company_id'] = $category['company_id'];
                }

                foreach ($tmp as $key => $storefront_categories) {
                    if (!empty($storefront_categories['storefront_id'])) {
                        $tmp[$key]['category'] = $category_prefix . ': ' . fn_get_storefront($storefront_categories['storefront_id'])->name;
                    } elseif (fn_allowed_for('MULTIVENDOR') && $storefront_categories['category_id']) {
                        $tmp[$key]['category'] = __('storefront_all_common_categories');
                    }
                }
            } elseif (!empty($params['group_by_level']) && !empty($params['plain'])) {
                $result_cat = [];

                foreach ($tmp as $cat) {
                    $result_cat[$cat['storefront_id']][] = $cat;
                }
                $tmp = [];
                foreach ($result_cat as $storefront_id => $categories) {
                    $tmp[] = [
                        'category' => $storefront_id || fn_allowed_for('ULTIMATE')
                            ? $category_prefix . ': ' . fn_get_storefront($storefront_id)->name
                            : __('storefront_all_common_categories'),
                        'store'    => true
                    ];
                    $tmp = array_merge($tmp, $categories);
                }
            }
            $categories_list = $tmp;
        }
    }

    fn_dropdown_appearance_cut_second_third_levels($categories_list, 'subcategories', $params);

    /**
     * Process final category list
     *
     * @param array  $categories_list Categories list
     * @param array  $params          Categories search params
     * @param string $lang_code       Language code
     */
    fn_set_hook('get_categories_post', $categories_list, $params, $lang_code);

    // process search results
    if (!empty($params['save_view_results'])) {
        $request = $params;
        $request['page'] = 1;
        $categories_res = ($params['plain'] == true)
            ?  $categories_list
            : fn_multi_level_to_plain($categories_list, 'subcategories');
        foreach ($categories_res as $key => $item) {
            if (empty($item['category_id'])) {
                unset($categories_res[$key]);
            }
        }
        $request['total_items'] = $request['items_per_page'] = count($categories_res);
        LastView::instance()->processResults('categories', $categories_res, $request);
    }

    return array($categories_list, $params);
}

/**
 * Fetches plain (without grouping and nesting) categories list with parents names
 *
 * @param array  $category_ids Category ids to fetch
 * @param string $lang_code    Two-letter lantguage code
 *
 * @return array
 */
function fn_get_categories_list_with_parents(array $category_ids, $lang_code = CART_LANGUAGE)
{
    $result = array();
    $category_ids_with_parents = fn_get_category_ids_with_parent($category_ids);

    if ($category_ids) {
        list($categories_list) = fn_get_categories(array(
            'simple'                   => false,
            'group_by_level'           => false,
            'get_company_name'         => true,
            'ignore_company_condition' => true,
            'items_per_page'           => 0,
            'category_ids'             => $category_ids_with_parents,
        ), $lang_code);

        foreach ($category_ids as $category_id) {
            $category = isset($categories_list[$category_id]) ? $categories_list[$category_id] : array();

            if (empty($category)) {
                continue;
            }

            $parent_ids = explode('/', $category['id_path']);
            array_pop($parent_ids);

            $category['parents'] = fn_get_items_by_ids(
                $categories_list,
                array_combine($parent_ids, $parent_ids),
                'category_id'
            );

            $result[$category_id] = $category;
        }
    }

    return $result;
}

/**
 * Gets full category data by its id
 *
 * @param int $category_id ID of category
 * @param string $lang_code 2-letters language code
 * @param string $field_list List of categories table' fields. If empty, data from all fields will be returned.
 * @param boolean $get_main_pair Get or not category image
 * @param boolean $skip_company_condition Select data for other stores categories. By default is false. This flag is used in ULT for displaying common categories in picker.
 * @param boolean $preview Category is requested in a preview mode
 * @param boolean $get_full_path Get full category path with all ancestors
 * @return mixed Array with category data.
 */
function fn_get_category_data($category_id = 0, $lang_code = CART_LANGUAGE, $field_list = '', $get_main_pair = true, $skip_company_condition = false, $preview = false, $get_full_path = false)
{
    // @TODO: remove in 4.3.2, this line is needed for backward compatibility since 4.3.1
    $field_list = str_replace(
        array('selected_layouts', 'default_layout', 'product_details_layout'),
        array('selected_views', 'default_view', 'product_details_view'),
        $field_list
    );

    /**
     * Changes select category data conditions
     *
     * @param int     $category_id            Category ID
     * @param array   $field_list             List of fields for retrieving
     * @param boolean $get_main_pair          Get or not category image
     * @param boolean $skip_company_condition Select data for other stores categories. By default is false. This flag is used in ULT for displaying common categories in picker.
     * @param string  $lang_code              2-letters language code
     */
    fn_set_hook('get_category_data_pre', $category_id, $field_list, $get_main_pair, $skip_company_condition, $lang_code);

    $auth = & Tygh::$app['session']['auth'];

    $conditions = '';
    if (AREA == 'C' && !$preview) {
        $conditions = "AND (" . fn_find_array_in_set($auth['usergroup_ids'], '?:categories.usergroup_ids', true) . ")";
    }

    if (empty($field_list)) {
        $descriptions_list = "?:category_descriptions.*";
        $field_list = "?:categories.*, $descriptions_list";
    }

    if (fn_allowed_for('ULTIMATE') && !$skip_company_condition) {
        $conditions .= fn_get_company_condition('?:categories.company_id');
    }

    $join = '';

    /**
     * Changes SQL parameters before select category data
     *
     * @param int    $category_id Category ID
     * @param array  $field_list  SQL fields to be selected in an SQL-query
     * @param string $join        String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $lang_code   2-letters language code
     * @param string $conditions  Condition params
     */
    fn_set_hook('get_category_data', $category_id, $field_list, $join, $lang_code, $conditions);

    $category_data = db_get_row(
        "SELECT $field_list FROM ?:categories"
        . " LEFT JOIN ?:category_descriptions"
        . " ON ?:category_descriptions.category_id = ?:categories.category_id"
        . " AND ?:category_descriptions.lang_code = ?s ?p"
        . " WHERE ?:categories.category_id = ?i ?p",
        $lang_code, $join, $category_id, $conditions
    );

    if (!empty($category_data)) {
        $category_data['category_id'] = $category_id;

        // Generate meta description automatically
        if (empty($category_data['meta_description']) && defined('AUTO_META_DESCRIPTION') && AREA != 'A') {
            $category_data['meta_description'] = !empty($category_data['description']) ? fn_generate_meta_description($category_data['description']) : '';
        }

        if ($get_main_pair == true) {
            $category_data['main_pair'] = fn_get_image_pairs($category_id, 'category', 'M', true, true, $lang_code);
        }

        if (!empty($category_data['selected_views'])) {
            $category_data['selected_views'] = unserialize($category_data['selected_views']);
        } else {
            $category_data['selected_views'] = array();
        }

        // @TODO: remove in 4.3.2 - these three (3) conditions are needed for backward compatibility since 4.3.1
        if (isset($category_data['selected_views'])) {
            $category_data['selected_layouts'] = $category_data['selected_views'];
        }
        if (isset($category_data['default_view'])) {
            $category_data['default_layout'] = $category_data['default_view'];
        }
        if (isset($category_data['product_details_view'])) {
            $category_data['product_details_layout'] = $category_data['product_details_view'];
        }

        if ($get_full_path) {
            $path = explode('/', $category_data['id_path']);
            if ($path) {
                $ancestors = db_get_array(
                    "SELECT ?:categories.category_id, ?:category_descriptions.category"
                    . " FROM ?:categories"
                    . " LEFT JOIN ?:category_descriptions"
                    . " ON ?:category_descriptions.category_id = ?:categories.category_id"
                    . " AND ?:category_descriptions.lang_code = ?s"
                    . " WHERE ?:categories.category_id IN (?n)",
                    $lang_code,
                    $path
                );
                $ancestors = array_column(fn_sort_by_ids($ancestors, $path, 'category_id'), 'category', 'category_id');
                $category_data['path_names'] = $ancestors;
            }
        }
    }

    /**
     * Changes category data
     *
     * @param int     $category_id            Category ID
     * @param array   $field_list             List of fields for retrieving
     * @param boolean $get_main_pair          Get or not category image
     * @param boolean $skip_company_condition Select data for other stores categories. By default is false. This flag is used in ULT for displaying common categories in picker.
     * @param string  $lang_code              2-letters language code
     * @param array   $category_data          Array with category fields
     */
    fn_set_hook('get_category_data_post', $category_id, $field_list, $get_main_pair, $skip_company_condition, $lang_code, $category_data);

    return (!empty($category_data) ? $category_data : false);
}

/**
 * Gets category name by category identifier
 *
 * @param int/array $category_id Category identifier or array of category identifiers
 * @param string $lang_code 2-letters language code
 * @param boolean $as_array Flag if false one category name is returned as simple string, if true category names are always returned as array
 * @return string/array Category name or array with category names
 */
function fn_get_category_name($category_id = 0, $lang_code = CART_LANGUAGE, $as_array = false)
{
    /**
     * Changes parameters for getting category name
     *
     * @param int/array $category_id Category identifier or array of category identifiers
     * @param string    $lang_code   2-letters language code
     * @param boolean   $as_array    Flag if false one category name is returned as simple string, if true category names are always returned as array
     */
    fn_set_hook('get_category_name_pre', $category_id, $lang_code, $as_array);

    $name = array();

    if (!empty($category_id)) {
        if (!is_array($category_id) && strpos($category_id, ',') !== false) {
            $category_id = explode(',', $category_id);
        }
        if (is_array($category_id) || $as_array == true) {
            $name = db_get_hash_single_array("SELECT category_id, category FROM ?:category_descriptions WHERE category_id IN (?n) AND lang_code = ?s", array('category_id', 'category'), $category_id, $lang_code);
        } else {
            $name = db_get_field("SELECT category FROM ?:category_descriptions WHERE category_id = ?i AND lang_code = ?s", $category_id, $lang_code);
        }
    }

    /**
     * Changes category names
     *
     * @param int|array    $category_id Category identifier or array of category identifiers
     * @param string       $lang_code   2-letters language code
     * @param boolean      $as_array    Flag if false one category name is returned as simple string, if true category names are always returned as array
     * @param string|array $name        Category name or array with category names
     */
    fn_set_hook('get_category_name_post', $category_id, $lang_code, $as_array, $name);

    return $name;
}

/**
 * Gets category path by category identifier
 *
 * @param int $category_id Category identifier
 * @param string $lang_code 2-letters language code
 * @param string $path_separator String character(s) separating the catergories
 * @return string Category path
 */
function fn_get_category_path($category_id = 0, $lang_code = CART_LANGUAGE, $path_separator = '/')
{
    /**
     * Change parameters for getting category path
     *
     * @param int    $category_id    Category identifier
     * @param string $lang_code      2-letters language code
     * @param string $path_separator String character(s) separating the catergories
     */
    fn_set_hook('fn_get_category_path_pre', $category_id, $lang_code, $path_separator);

    $category_path = false;

    if (!empty($category_id)) {

        $id_path = db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $category_id);

        $category_names = db_get_hash_single_array(
            "SELECT category_id, category FROM ?:category_descriptions WHERE category_id IN (?n) AND lang_code = ?s",
            array('category_id', 'category'), explode('/', $id_path), $lang_code
        );

        $path = explode('/', $id_path);
        $_category_path = '';
        foreach ($path as $v) {
            $_category_path .= $category_names[$v] . $path_separator;
        }
        $_category_path = rtrim($_category_path, $path_separator);

        $category_path = (!empty($_category_path) ? $_category_path : false);
    }

    /**
     * Change category path
     *
     * @param int    $category_id    Category identifier
     * @param string $lang_code      2-letters language code
     * @param string $path_separator String character(s) separating the catergories
     * @param string $category_path  Category path
     */
    fn_set_hook('fn_get_category_path_post', $category_id, $lang_code, $path_separator, $category_path);

    return $category_path;
}

/**
 * Reduces given list of category IDs, removing IDs of categories which will be removed anyway within
 * the recursive deletion of their parent categories.
 * For example, if input categories are:
 * - Electronics
 * -- Desktops
 * -- Laptops
 * - Road Bikes
 * Ouput categories will be:
 * - Electronics
 * - Road Bikes
 *
 * @param array $category_ids Category IDs to be deleted
 *
 * @return array Reduced list of category IDs
 */
function fn_filter_redundant_deleting_category_ids(array $category_ids)
{
    $result = array();

    $category_ids_from_db = db_get_hash_single_array(
        "SELECT category_id, parent_id FROM ?:categories WHERE category_id IN(?n)",
        array('category_id', 'parent_id'),
        $category_ids
    );

    // We select only the least nested categories, because deletion is recursive
    foreach ($category_ids_from_db as $category_id => $parent_id) {
        $category_id = (int) $category_id;
        $parent_id = (int) $parent_id;

        if (!isset($category_ids_from_db[$parent_id]) && !in_array($category_id, $result)) {
            $result[] = $category_id;
        }
    }

    return $result;
}

/**
 * Removes category by identifier
 *
 * @param int $category_id Category identifier
 * @param boolean $recurse Flag that defines if category should be deleted recursively
 * @return array/boolean Identifiers of deleted categories or false if categories were not found
 */
function fn_delete_category($category_id, $recurse = true)
{
    /**
     * Actions before category and its related data removal
     *
     * @param  int         $category_id Category identifier to delete
     * @param  boolean     $recurse     Flag that defines if category should be deleted recursively
     * @return int|boolean Identifiers of deleted categories or false if categories were not found
     */
    fn_set_hook('delete_category_pre', $category_id, $recurse);

    if (empty($category_id)) {
        return false;
    }

    // Log category deletion
    fn_log_event('categories', 'delete', array(
        'category_id' => $category_id,
    ));

    // Delete all subcategories
    if ($recurse == true) {
        $id_path = db_get_field("SELECT id_path FROM ?:categories WHERE category_id = ?i", $category_id);
        // Order is important
        $category_ids = db_get_fields(
            "SELECT category_id FROM ?:categories WHERE id_path LIKE ?l ORDER BY id_path ASC",
            "$id_path/%"
        );
        // The very first item is category that is being deleted
        array_unshift($category_ids, $category_id);
    } else {
        $category_ids[] = $category_id;
    }

    foreach ($category_ids as $k => $category_id) {
        // When deleting trash category, remove products from it
        if (fn_is_trash_category($category_id)) {
            fn_empty_trash($category_id);
        }

        /**
         * Process category delete (run before category is deleted)
         *
         * @param int $category_id Category identifier
         */
        fn_set_hook('delete_category_before', $category_id);

        Block::instance()->removeDynamicObjectdata('categories', $category_id);

        // Deleting category
        db_query("DELETE FROM ?:categories WHERE category_id = ?i", $category_id);
        db_query("DELETE FROM ?:category_descriptions WHERE category_id = ?i", $category_id);

        // Remove this category from features assignments
        db_query("UPDATE ?:product_features SET categories_path = ?p", fn_remove_from_set('categories_path', $category_id));

        if (fn_allowed_for('MULTIVENDOR')) {
            // Deleting products which had the deleted category as their main category
            $products_to_delete = db_get_fields(
                "SELECT product_id FROM ?:products_categories WHERE category_id = ?i AND link_type = 'M'",
                $category_id
            );

            if (!empty($products_to_delete)) {
                foreach ($products_to_delete as $key => $value) {
                    fn_delete_product($value);
                }
            }

            db_query("DELETE FROM ?:products_categories WHERE category_id = ?i", $category_id);
        }

        // Deleting category images
        fn_delete_image_pairs($category_id, 'category');

        /**
         * Process category delete (run after category is deleted)
         *
         * @param int $category_id Category identifier
         */
        fn_set_hook('delete_category_after', $category_id);
    }

    /**
     * Actions after category and its related data removal
     *
     * @param int   $category_id  Category identifier to delete
     * @param bool  $recurse      Flag that defines if category should be deleted recursively
     * @param array $category_ids Category identifiers that were removed
     */
    fn_set_hook('delete_category_post', $category_id, $recurse, $category_ids);

    return $category_ids; // Returns ids of deleted categories
}

/**
 * Checks whether category with given ID exists at database.
 *
 * @param int         $category_id          Category ID
 * @param string|null $additional_condition Optional checking condition
 *
 * @return bool
 */
function fn_category_exists($category_id, $additional_condition = null)
{
    return (bool) db_get_field(
        'SELECT COUNT(*) FROM ?:categories WHERE category_id = ?i ' . $additional_condition,
        $category_id
    );
}

/**
 * Recalculates and updates products quantity in categories
 *
 * @param array $category_ids List of categories identifiers for update. When empty list given,
 *                            all categories will be updated.
 *
 * @return true
 */
function fn_update_product_count($category_ids = array())
{

    $category_ids = array_unique((array) $category_ids);

    /**
     * Update product count (running before update)
     *
     * @param array $category_ids List of category ids for update
     */
    fn_set_hook('update_product_count_pre', $category_ids);

    $condition = empty($category_ids) ? '' : db_quote(' WHERE ?:categories.category_id IN (?n)', $category_ids);

    db_query(
        'UPDATE ?:categories SET ?:categories.product_count = ('
        . ' SELECT COUNT(*) FROM ?:products_categories WHERE ?:products_categories.category_id = ?:categories.category_id)'
        . $condition
    );

    /**
     * Update product count (running after update)
     *
     * @param array $category_ids List of category ids for update
     */
    fn_set_hook('update_product_count_post', $category_ids);

    return true;
}

/**
 * Adds or updates category
 *
 * @param array $category_data Category data
 * @param int $category_id Category identifier
 * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
 * @return int New or updated category identifier
 */
function fn_update_category($category_data, $category_id = 0, $lang_code = CART_LANGUAGE)
{
    // @TODO: remove in 4.3.2 - these three (3) conditions are needed for backward compatibility since 4.3.1
    if (isset($category_data['selected_layouts'])) {
        $category_data['selected_views'] = $category_data['selected_layouts'];
        unset($category_data['selected_layouts']);
    }
    if (isset($category_data['default_layout'])) {
        $category_data['default_view'] = $category_data['default_layout'];
        unset($category_data['default_layout']);
    }
    if (isset($category_data['product_details_layout'])) {
        $category_data['product_details_view'] = $category_data['product_details_layout'];
        unset($category_data['product_details_layout']);
    }

    $default_params = [
        'category_id' => $category_id
    ];
    if (!$category_id) {
        $default_params['storefront_id'] = '';
    }
    $category_data = array_merge($default_params, $category_data);

    /**
     * Update category data (running before fn_update_category() function)
     *
     * @param array  $category_data Category data
     * @param int    $category_id   Category identifier
     * @param string $lang_code     Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('update_category_pre', $category_data, $category_id, $lang_code);

    SecurityHelper::sanitizeObjectData('category', $category_data);

    $category_info = db_get_row("SELECT company_id, id_path FROM ?:categories WHERE category_id = ?i", $category_id);

    // category title required
    if (empty($category_data['category'])) {
        //return false; // FIXME: management page doesn't have category name
    }

    if (isset($category_data['localization'])) {
        $category_data['localization'] = empty($category_data['localization']) ? '' : fn_implode_localizations($category_data['localization']);
    }
    if (isset($category_data['usergroup_ids'])) {
        $category_data['usergroup_ids'] = empty($category_data['usergroup_ids']) ? '0' : implode(',', $category_data['usergroup_ids']);
    }

    if (fn_allowed_for('ULTIMATE') && isset($category_data['storefront_id'])) {
        $storefront = StorefrontProvider::getRepository()->findById($category_data['storefront_id']);
        if ($storefront instanceof Storefront) {
            $company_ids = $storefront->getCompanyIds();
            $category_data['company_id'] = reset($company_ids);
        }
    }

    $_data = $category_data;
    unset($_data['parent_id']);

    if (isset($category_data['timestamp'])) {
        $_data['timestamp'] = fn_parse_date($category_data['timestamp']);
    }

    if (isset($_data['position']) && empty($_data['position']) && $_data['position'] != '0' && isset($category_data['parent_id'])) {
        $_data['position'] = (int) db_get_field("SELECT max(position) FROM ?:categories WHERE parent_id = ?i", $category_data['parent_id']);
        $_data['position'] = $_data['position'] + 10;
    }

    if (isset($_data['selected_views'])) {
        $_data['selected_views'] = serialize($_data['selected_views']);
    }

    if (isset($_data['use_custom_templates']) && $_data['use_custom_templates'] == 'N') {
        // Clear the layout settings if the category custom templates were disabled
        $_data['product_columns'] = $_data['selected_views'] = $_data['default_view'] = '';
    }

    // create new category
    if (empty($category_id)) {
        if (fn_allowed_for('ULTIMATE') && empty($_data['storefront_id']) && !empty($_data['company_id'])) {
            $storefront = StorefrontProvider::getRepository()->findByCompanyId($_data['company_id']);
            if ($storefront instanceof Storefront) {
                $_data['storefront_id'] = $storefront->storefront_id;
            }
        }

        if (fn_allowed_for('ULTIMATE') && empty($_data['storefront_id'])) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('need_storefront_id'));

            return false;
        }

        $create = true;

        $category_id = db_query("INSERT INTO ?:categories ?e", $_data);
        $_data['category_id'] = $category_id;

        foreach (Languages::getAll() as $_data['lang_code'] => $v) {
            db_query("INSERT INTO ?:category_descriptions ?e", $_data);
        }

        $category_data['parent_id'] = !empty($category_data['parent_id']) ? $category_data['parent_id'] : 0;

        // update existing category
    } else {
        if ($category_info) {
            $category_data['old_company_id'] = $category_info['company_id'];
            db_query('UPDATE ?:categories SET ?u WHERE category_id = ?i', $_data, $category_id);
            db_query(
                'UPDATE ?:category_descriptions SET ?u WHERE category_id = ?i AND lang_code = ?s',
                $_data, $category_id, $lang_code
            );
        } else {
            fn_set_notification('E', __('error'), __('object_not_found', array('[object]' => __('category'))),'','404');
            $category_id = false;
        }
    }

    if ($category_id) {

        // regenerate id_path for all child categories of the updated category
        if (isset($category_data['parent_id'])) {
            fn_change_category_parent($category_id, intval($category_data['parent_id']));
        }

        // Log category add/update
        fn_log_event('categories', !empty($create) ? 'create' : 'update', array(
            'category_id' => $category_id,
        ));

        // Assign usergroup to all subcategories
        if (!empty($_data['usergroup_to_subcats'])
            && $_data['usergroup_to_subcats'] == 'Y'
            && isset($category_info['id_path'])
        ) {
            $id_path = $category_info['id_path'];
            db_query(
                'UPDATE ?:categories SET usergroup_ids = ?s WHERE id_path LIKE ?l',
                $_data['usergroup_ids'], "$id_path/%"
            );
        }
    }

    $id_path = db_get_field('SELECT id_path FROM ?:categories WHERE category_id = ?i', $category_id);

    if (isset($category_data['storefront_id'])) {
        // Change storefront_id of subcategories
        db_query('UPDATE ?:categories SET storefront_id = ?s WHERE id_path LIKE ?l', $category_data['storefront_id'], $id_path . '/%');
    }

    /**
     * Update category data (running after fn_update_category() function)
     *
     * @param array  $category_data Category data
     * @param int    $category_id   Category identifier
     * @param string $lang_code     Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param bool   $create        True if category was created, false otherwise
     */
    fn_set_hook('update_category_post', $category_data, $category_id, $lang_code, $create);

    return $category_id;

}

/**
 * Changes category's parent to another category. Modifies "id_path and "level" attributes of category and its children.
 *
 * @param int $category_id Category identifier
 * @param int $new_parent_id Identifier of new category parent
 * @return bool True on success, false otherwise
 */
function fn_change_category_parent($category_id, $new_parent_id)
{
    if (empty($category_id) || $category_id == $new_parent_id) { return false; }
    /**
     * Adds additional actions before category parent updating
     *
     * @param int $category_id   Category identifier
     * @param int $new_parent_id Identifier of new category parent
     */
    fn_set_hook('update_category_parent_pre', $category_id, $new_parent_id);

    $categories = db_get_hash_array(
        "SELECT `category_id`, `parent_id`, `id_path`, `level` FROM ?:categories WHERE `category_id` IN (?n)",
        'category_id',
        array($new_parent_id, $category_id)
    );
    if (empty($categories[$category_id])
        || (!empty($new_parent_id) && empty($categories[$new_parent_id]))
    ) {
        return false;
    }

    $category_modified = $categories[$category_id];
    if (!empty($new_parent_id) && !empty($categories[$new_parent_id])) {
        $category_modified['parent_id'] = $new_parent_id;
        $category_modified['level'] = ($categories[$new_parent_id]['level'] + 1);
        $category_modified['id_path'] = $categories[$new_parent_id]['id_path'] . '/' . $category_id;
    } else {
        $category_modified['parent_id'] = 0;
        $category_modified['level'] = 1;
        $category_modified['id_path'] = $category_id;
    }

    // Update category's tree position
    db_query(
        "UPDATE ?:categories SET `parent_id` = ?i, `id_path` = ?s, `level` = ?i WHERE `category_id` = ?i",
        $category_modified['parent_id'],
        $category_modified['id_path'],
        $category_modified['level'],
        $category_id
    );

    // Update existing category's children tree position
    if (isset($categories[$category_id]['parent_id']) && $categories[$category_id]['parent_id'] != $new_parent_id) {
        db_query(
            "UPDATE ?:categories
            SET
              `id_path` = CONCAT(?s, SUBSTRING(`id_path`, ?i)),
              `level` = `level` + ?i
            WHERE `id_path` LIKE ?l",
            $category_modified['id_path'] . "/",
            strlen($categories[$category_id]['id_path'] . '/') + 1,
            ((int) $category_modified['level'] - (int) $categories[$category_id]['level']),
            $categories[$category_id]['id_path'] . '/%'
        );

        /**
         * Adds additional actions after category parent updating
         *
         * @param int $category_id   Category identifier
         * @param int $new_parent_id Identifier of new category parent
         */
        fn_set_hook('update_category_parent_post', $category_id, $new_parent_id);
    }

    return true;
}

function fn_convert_categories($category_ids)
{
    $c_ids = explode(',', $category_ids);
    $categories = array();
    $main_category = 0;
    foreach ($c_ids as $v) {
        if (strpos($v, 'M') !== false) {
            $main_category = intval($v);
        }
        if (!in_array(intval($v), $categories)) {
            $categories[] = intval($v);
        }
    }

    if (empty($main_category)) {
        $main_category = reset($categories);
    }

    return array($categories, $main_category);
}

function fn_get_categories_list($category_ids, $lang_code = CART_LANGUAGE)
{
    /**
     * Change params for getting categories list
     *
     * @param array  $category_ids Category identifier
     * @param string $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_categories_list_pre', $category_ids, $lang_code);

    static $max_categories = 10;
    $c_names = array();
    if (!empty($category_ids)) {
        $c_ids = fn_explode(',', $category_ids);
        $tr_c_ids = array_slice($c_ids, 0, $max_categories);
        $c_names = fn_get_category_name($tr_c_ids, $lang_code);
        if (sizeof($tr_c_ids) < sizeof($c_ids)) {
            $c_names[] = '... (' . sizeof($c_ids) . ')';
        }
    } else {
        $c_names[] = __('all_categories', [], $lang_code);
    }

    /**
     * Change categories list
     *
     * @param array  $c_names      Categories names list
     * @param array  $category_ids Category identifier
     * @param string $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_categories_list_post', $c_names, $category_ids, $lang_code);

    return $c_names;
}

function fn_get_product_counts_by_category($params, $lang_code = CART_LANGUAGE)
{
    $default_params = array(
        'company_id' => 0,
        'sort_by' => 'position',
        'sort_order' => 'asc',
    );

    $params = array_merge($default_params, $params);

    $sort_fields = array(
        'position' => '?:categories.position',
        'category' => '?:category_descriptions.category',
        'count' => 'count',
    );

    $sort = db_sort($params, $sort_fields, $default_params['sort_by'], $default_params['sort_order']);

    $condition = $join = '';
    if (!empty($params['company_id'])) {
        if (is_array($params['company_id'])) {
            $condition .= db_quote(" AND ?:products.company_id IN (?n) ", $params['company_id']);
        } else {
            $condition .= db_quote(" AND ?:products.company_id = ?i ", $params['company_id']);
        }
    }
    $condition .= db_quote(" AND ?:category_descriptions.lang_code = ?s ", $lang_code);

    $join .= 'JOIN ?:products ON ?:products_categories.product_id = ?:products.product_id ';
    $join .= 'JOIN ?:categories ON ?:products_categories.category_id = ?:categories.category_id ';
    $join .= 'JOIN ?:category_descriptions ON ?:products_categories.category_id = ?:category_descriptions.category_id ';

    $result = db_get_array("SELECT COUNT(*) as count, ?:category_descriptions.category, ?:category_descriptions.category_id FROM ?:products_categories ?p WHERE 1 ?p GROUP BY ?:products_categories.category_id ?p", $join, $condition, $sort);

    return $result;
}

/**
 * Gets categefories and products totals data
 *
 * @return array Array with categories and products totals
 */
function fn_get_categories_stats()
{
    $stats = [];
    $params = [];

    $stats['products_total'] = fn_get_products_count($params);

    $params = [
        'get_conditions' => true
    ];
    list(, , $condition) = fn_get_categories($params);
    $stats['categories_total'] = db_get_field('SELECT COUNT(*) FROM ?:categories WHERE 1 ?p', $condition);

    $params = [
        'get_conditions' => true,
        'status' => 'A'
    ];
    list(, , $condition) = fn_get_categories($params);
    $stats['categories_active'] = db_get_field('SELECT COUNT(*) FROM ?:categories WHERE 1 ?p', $condition);

    $params = [
        'get_conditions' => true,
        'status' => 'H'
    ];
    list(, , $condition) = fn_get_categories($params);
    $stats['categories_hidden'] = db_get_field('SELECT COUNT(*) FROM ?:categories WHERE 1 ?p', $condition);

    $params = [
        'get_conditions' => true,
        'status' => 'D'
    ];
    list(, , $condition) = fn_get_categories($params);
    $stats['categories_disabled'] = db_get_field('SELECT COUNT(*) FROM ?:categories WHERE 1 ?p', $condition);

    return $stats;
}

/**
 * Change parameters before updating product categories
 *
 * @param int   $product_id   Product ID
 * @param array $product_data Product data
 * @param bool  $rebuild      Determines whether or not the tree of categories must be rebuilt
 * @param int   $company_id   The identifier of the company. If an identifier is passed to the function,
 *                            then the changes will affect only those categories that belong to the specified company.
 * @return array $product data List with product fields
 */
function fn_update_product_categories($product_id, $product_data, $rebuild = false, $company_id = 0)
{
    /**
     * Change parameters before updating product categories
     *
     * @param int   $product_id   Product ID
     * @param array $product_data Product data
     * @param bool  $rebuild      Determines whether or not the tree of categories must be rebuilt
     * @param int   $company_id   The identifier of the company. If an identifier is passed to the function,
     *                            then the changes will affect only those categories that belong to the specified
     *                            company.
     */
    fn_set_hook('update_product_categories_pre', $product_id, $product_data, $rebuild, $company_id);

    // Save new product categories which was added by select2
    if (isset($product_data['add_new_category'])) {
        $filtered_category_list = array_filter($product_data['add_new_category'], 'fn_string_not_empty');
        foreach ($filtered_category_list as $key => $value) {
            $parent_id = 0;
            $category_parts = explode('/', $value);
            array_walk($category_parts, 'fn_trim_helper');
            if (fn_allowed_for('ULTIMATE')) {
                if (empty($company_id) && !empty($product_data['company_id'])) {
                    $company_id = $product_data['company_id'];
                } elseif (empty($company_id)) {
                    $company_id = Registry::ifGet('runtime.company_id', fn_get_default_company_id());
                }
            }
            $category_parts = fn_get_categories_from_path(array_values(array_filter($category_parts, 'fn_string_not_empty')), $company_id);
            foreach ($category_parts as $category_part_key => $category_part) {
                if (isset($category_parts[$category_part['parent']]['id'])) {
                    $parent_id = $category_parts[$category_part['parent']]['id'];
                }

                if (!$category_part['id']) {
                    $category_data = [
                        'category' => $category_part['name'],
                        'parent_id' => $parent_id,
                        'status' => 'A',
                        'position' => '',
                        'timestamp' => TIME
                    ];

                    if (fn_allowed_for('ULTIMATE')) {
                        $category_data['company_id'] = $company_id;
                    }

                    $category_id = fn_update_category($category_data);

                    $category_parts[$category_part_key]['id'] = $category_id;
                }
            }

            if (isset($category_id)) {
                $insert_category_key = array_search($value, $product_data['category_ids']);
                $product_data['category_ids'][$insert_category_key] = $category_id;
            }
        }
    }

    $fields = array(
        '?:products_categories.category_id',
        '?:products_categories.link_type',
        '?:products_categories.position'
    );

    $join = '';

    $condition = db_quote('WHERE product_id = ?i', $product_id);

    if ($company_id && !empty($product_data['category_ids'])) {
        $category_ids = db_get_hash_array(
            'SELECT category_id FROM ?:categories WHERE category_id IN (?n) ?p',
            'category_id',
            $product_data['category_ids'],
            fn_get_company_condition('?:categories.company_id', true, $company_id)
        );

        $category_ids = fn_sort_by_ids($category_ids, $product_data['category_ids'], 'category_id');
        $saved_category_ids = $product_data['category_ids'];
        $product_data['category_ids'] = array_column($category_ids, 'category_id');

        $fields[] = '?:categories.company_id';
        $join = ' LEFT JOIN ?:categories ON ?:categories.category_id = ?:products_categories.category_id';
        $condition .= fn_get_company_condition('?:categories.company_id', true, $company_id);
    }

    $existing_categories = db_get_hash_array(
        'SELECT ?p FROM ?:products_categories ?p ?p',
        'category_id',
        implode(', ', $fields),
        $join,
        $condition
    );

    $new_categories = array();

    if (!empty($product_data['category_ids'])) {
        $new_categories = $product_data['category_ids'];

        $product_data['category_ids'] = array_unique($product_data['category_ids']);

        if (empty($product_data['main_category']) || !in_array($product_data['main_category'], $product_data['category_ids'])) {
            $product_data['main_category'] = reset($product_data['category_ids']);
        }

        if (sizeof($product_data['category_ids']) == sizeof($existing_categories)) {
            if (isset($existing_categories[$product_data['main_category']])
                && $existing_categories[$product_data['main_category']]['link_type'] != 'M'
            ) {
                $rebuild = true;
            }

            foreach ($product_data['category_ids'] as $cid) {
                if (!isset($existing_categories[$cid])) {
                    $rebuild = true;
                }
            }
        } else {
            $rebuild = true;
        }
    }

    if ($rebuild) {
        if ($new_categories) {

            if ($company_id) {
                db_query(
                    'DELETE FROM ?:products_categories WHERE product_id = ?i AND category_id IN (?n)',
                    $product_id, array_keys($existing_categories)
                );
            } else {
                db_query('DELETE FROM ?:products_categories WHERE product_id = ?i', $product_id);
            }

            foreach ($product_data['category_ids'] as $cid) {
                $_data = [
                    'product_id'  => $product_id,
                    'category_id' => $cid,
                    'position'    => isset($existing_categories[$cid])
                        ? $existing_categories[$cid]['position']
                        : (isset($product_data['position'])  // Available on bulk product addition
                            ? (int) $product_data['position']
                            : 0
                        ),
                    'link_type' => $product_data['main_category'] == $cid ? 'M' : 'A',
                ];

                if ($company_id && $company_id != $product_data['company_id']) {
                    $_data['link_type'] = 'A';
                }

                db_query('INSERT INTO ?:products_categories ?e', $_data);
            }
        }

        fn_update_product_count(fn_array_merge($new_categories, array_keys($existing_categories), false));
    }

    /**
     * Post processing after updating product categories
     *
     * @param int   $product_id          Product ID
     * @param array $product_data        Product data
     * @param array $existing_categories Original product categories
     * @param bool  $rebuild             Determines whether or not the tree of categories must be rebuilt
     * @param int   $company_id          The identifier of the company. If an identifier is passed to the function,
     *                                   then the changes will affect only those categories that belong to the
     *                                   specified company.
     * @param array $saved_category_ids  Saved product category ids belonging to companies
     */
    fn_set_hook(
        'update_product_categories_post',
        $product_id,
        $product_data,
        $existing_categories,
        $rebuild,
        $company_id,
        $saved_category_ids
    );

    return $product_data;
}

/**
 * Updates product position in the chosen category
 *
 * @param int $product_id  Product id
 * @param int $category_id Category id where the product position will be updated
 * @param int $position    New product position in the category
 */
function fn_update_product_position_in_category($product_id, $category_id, $position)
{
    db_query(
        'UPDATE ?:products_categories SET position = ?i WHERE category_id = ?i AND product_id = ?i',
        $position,
        $category_id,
        $product_id
    );

    /**
     * Post processing after updating product position in category
     *
     * @param int $product_id  Product data
     * @param int $category_id Category id where the product position was updated
     * @param int $position    New product position in the category
     */
    fn_set_hook('update_product_position_in_category_post', $product_id, $category_id, $position);
}


/**
 * Checks if product linked to any category from the owner company
 *
 * @param int $product_id Product ID
 * @param array $category_ids List of category ids
 * @return bool True if linked
 */
function fn_check_owner_categories($company_id, $category_ids)
{
    $linked_to_categories =  db_get_field('SELECT COUNT(*) FROM ?:categories WHERE company_id = ?i AND category_id IN (?n)', $company_id, $category_ids);

    return !empty($linked_to_categories);
}

/**
 * Creates category used for trash
 *
 * @param int $company_id Company ID
 * @return int ID of trash category
 */
function fn_create_trash_category($company_id)
{
    $category_data = array(
        'category' => __('trash_category'),
        'description' => __('trash_category_description'),
        'status' => 'D', // disabled
        'is_trash' => 'Y',
        'company_id' => $company_id,
        'timestamp' => time(),
        'selected_views' => '',
        'product_details_view' => 'default',
        'use_custom_templates' => 'N'
    );
    $trash_id = fn_update_category($category_data);
    return $trash_id;
}

/**
 * Gets default category ID
 *
 * @param int $company_id Company ID
 *
 * @return int|bool ID of default category
 */
function fn_get_default_category_id($company_id)
{
    $category_id = db_get_field(
        'SELECT category_id'
        . ' FROM ?:categories'
        . ' WHERE is_default = ?s AND company_id = ?i',
        YesNo::YES,
        $company_id
    );

    if (!is_numeric($category_id)) {
        return false;
    }

    return (int) $category_id;
}

/**
 * Creates default category for products
 *
 * @param int $company_id Company ID
 *
 * @return int ID of default category
 */
function fn_create_default_category($company_id)
{
    return fn_update_category([
        'category' => __('products_without_category'),
        'description' => __('products_without_category_description'),
        'status' => ObjectStatuses::HIDDEN,
        'is_default' => YesNo::YES,
        'company_id' => $company_id,
        'timestamp' => time(),
        'selected_views' => '',
        'product_details_view' => 'default',
        'use_custom_templates' => YesNo::NO,
        'parent_id' => 0,
        'position' => ''
    ]);
}

/**
 * Gets default category ID or creates it if necessary
 *
 * @param int $company_id Company ID
 *
 * @return int
 */
function fn_get_or_create_default_category_id($company_id)
{
    $category_id = fn_get_default_category_id($company_id);
    if ($category_id === false) {
        $category_id = fn_create_default_category($company_id);
    }

    return (int) $category_id;
}

/**
 * Gets all default categories
 *
 * @return array<array{company_id: int, category_id: int}>
 */
function fn_get_all_default_categories_ids()
{
    return db_get_hash_single_array(
        'SELECT company_id, category_id FROM ?:categories WHERE is_default = ?s',
        ['company_id', 'category_id'],
        YesNo::YES
    );
}

/**
 * Returns identifier of category used for trash
 *
 * @param int $company_id Company identifier
 * @return int|boolean Identifier of trash category, false when none exists
 */
function fn_get_trash_category($company_id)
{
    $trash_id = db_get_field(
        "SELECT category_id"
        . " FROM ?:categories"
        . " WHERE is_trash = 'Y'"
        . " AND company_id = ?i", $company_id
    );

    if (!is_numeric($trash_id)) {
        $trash_id = false;
    }

    return $trash_id;
}

/**
 * Checks if category is used for trash
 *
 * @param int $category_id Category ID to check for
 * @return boolean Category is used for trash
 */
function fn_is_trash_category($category_id)
{
    $is_trash = db_get_field(
        "SELECT is_trash"
        . " FROM ?:categories"
        . " WHERE category_id = ?i",
        $category_id
    );
    return $is_trash == 'Y';
}

/**
 * Adds product to trash category
 *
 * @param int $product_id Product ID
 * @param int $trash_category_id Trash category ID
 */
function fn_add_product_to_trash($product_id, $trash_category_id)
{
    $data = array(
        'product_id' => $product_id,
        'category_id' => $trash_category_id,
        'position' => 0,
        'link_type' => 'M'
    );
    db_query("INSERT INTO ?:products_categories ?e", $data);
}

/**
 * Assign a new main category to a product that had its main category deleted.
 *
 * @param array $category_ids The identifiers of deleted categories
 * @return array The identifiers of products that had new main categories assigned.
 */
function fn_adopt_orphaned_products($category_ids)
{
    $products_ids = array();

    if ($category_ids) {
        $products_list_with_main_category = db_get_fields(
            'SELECT DISTINCT product_id'
            . ' FROM ?:products_categories'
            . ' WHERE category_id IN (?n) AND link_type = ?s',
            $category_ids, 'M'
        );

        if (!empty($products_list_with_main_category)) {
            // Assigning a main category to products that only have secondary categories left
            $products_ids = db_get_hash_single_array(
                'SELECT DISTINCT p.product_id, c.category_id'
                . ' FROM ?:products p'
                . ' INNER JOIN ?:products_categories as pc ON p.product_id = pc.product_id'
                . ' INNER JOIN ?:categories as c ON pc.category_id = c.category_id AND p.company_id = c.company_id'
                . ' WHERE p.product_id in (?n) AND pc.link_type = ?s',
                array('product_id', 'category_id'), $products_list_with_main_category, 'A'
            );

            foreach ($products_ids as $product_id => $category_id) {
                db_query(
                    'UPDATE ?:products_categories SET link_type = ?s WHERE product_id = ?i AND category_id = ?i',
                    'M', $product_id, $category_id
                );
            }
        }
    }

    return $products_ids;
}

/**
 * Moves products left without categories in their store to trash
 *
 * @param array $category_ids Deleted categories identifiers
 * @return array Deleted products identifiers
 */
function fn_trash_orphaned_products($category_ids)
{
    $orphaned_products = array();
    $trashes = array();
    $category_ids = array_unique($category_ids);

    if ($category_ids) {
        $narrowed_products_list = db_get_fields(
            "SELECT DISTINCT product_id"
            . " FROM ?:products_categories"
            . " WHERE category_id IN (?n)",
            $category_ids
        );

        if (!empty($narrowed_products_list)) {
            $orphaned_products = db_get_hash_single_array(
                'SELECT'
                . ' cp.product_id,'
                . ' p.company_id,'
                . ' c.category_id,'
                . ' GROUP_CONCAT(NULLIF(c.category_id, ?s)) AS owner_groups'
                . ' FROM ?:products p'
                . ' LEFT JOIN ?:products_categories cp'
                . ' ON p.product_id = cp.product_id'
                . ' LEFT JOIN ?:categories c'
                . ' ON cp.category_id = c.category_id'
                . ' AND p.company_id = c.company_id'
                . ' WHERE p.product_id in (?n)'
                . ' GROUP BY cp.product_id'
                . ' HAVING owner_groups IS NULL',
                ['product_id', 'company_id'],
                '',
                $narrowed_products_list
            );

            db_query("DELETE FROM ?:products_categories"
                . " WHERE category_id IN (?n)",
                $category_ids
            );

            if (!empty($orphaned_products)) {
                // Deleting product associations
                db_query("DELETE FROM ?:products_categories"
                    . " WHERE product_id IN (?n)",
                    array_keys($orphaned_products)
                );

                // Moving products to trash
                foreach($orphaned_products as $product_id => $company_id) {
                    if (!isset($trashes[$company_id])) {
                        $trash_category_id = fn_get_trash_category($company_id);
                        if (!$trash_category_id) {
                            $trash_category_id = fn_create_trash_category($company_id);
                        }
                        $trashes[$company_id] = $trash_category_id;
                    }
                    fn_add_product_to_trash($product_id, $trashes[$company_id]);
                }

                fn_update_product_count();
            }
        }
    }

    return array($orphaned_products, $trashes);
}

/**
 * Deletes products from trash category
 *
 * @param int $trash_category_id Trash category identifier
 * @return array Deleted product identifiers
 */
function fn_empty_trash($trash_category_id)
{
    $products_to_delete = db_get_fields(
        "SELECT DISTINCT product_id"
        . " FROM ?:products_categories"
        . " WHERE category_id = ?i",
        $trash_category_id
    );

    if (!empty($products_to_delete)) {
        foreach($products_to_delete as $product_id) {
            fn_delete_product($product_id);
        }
    }

    return $products_to_delete;
}

/**
 * Gets list of category identifiers with parent categories.
 *
 * @param array|int $category_ids List of category identifier
 * @return array
 */
function fn_get_category_ids_with_parent($category_ids)
{
    static $cache = array();

    if (empty($category_ids)) {
        return array();
    }

    $category_ids = (array) $category_ids;
    sort($category_ids);

    $key = implode('_', $category_ids);

    if (!isset($cache[$key])) {
        $result = explode('/', implode('/', db_get_fields("SELECT id_path FROM ?:categories WHERE category_id IN (?n)", $category_ids)));
        $cache[$key] = array_unique($result);
    }

    return $cache[$key];
}

/**
 * Reorders product categories sequentially in the database.
 *
 * @param int   $product_id   Product identifier
 * @param array $category_ids Category identifiers
 *
 * @return bool Whether at lest one product category position was updated
 */
function fn_sort_product_categories($product_id, array $category_ids)
{
    $position = 0;
    $is_position_updated = false;

    foreach ($category_ids as $category_id) {
        $is_single_position_updated = db_query(
            'UPDATE ?:products_categories SET category_position = ?i WHERE product_id = ?i AND category_id = ?i',
            $position,
            $product_id,
            $category_id
        );
        $position += 10;

        $is_position_updated = $is_position_updated || $is_single_position_updated;
    }

    return $is_position_updated;
}

/**
 * Gets array of object with categories data from category path
 *
 * @param array $category_names Parts of fully qualified category name
 * @param int   $company_id Id of company which owns searchiable categories
 * @param string $lang_code Current language of searching categories
 *
 * @return array Each element contains category name, category id in database (empty string if not exists) and index of parent category in this array (null of not exists)
 */
function fn_get_categories_from_path($category_names = [], $company_id = 0, $lang_code = CART_LANGUAGE)
{
    $categories = [];
    if (empty($category_names)) {
        return $categories;
    }

    foreach ($category_names as $index => $category_name) {
        $current_category = [];
        $current_category['name'] = $category_name;

        $current_category['parent'] = $index <= 0
            ? null
            : $index - 1;

        $parent_id = isset($current_category['parent'])
            ? $categories[$current_category['parent']]['id']
            : 0;

        $current_category['id'] = db_get_field(
            'SELECT ?:category_descriptions.category_id FROM ?:category_descriptions'
            . ' LEFT JOIN ?:categories ON ?:category_descriptions.category_id = ?:categories.category_id'
            . ' WHERE ?:category_descriptions.category = ?s AND ?:category_descriptions.lang_code = ?s'
            . ' AND ?:categories.parent_id = ?i AND ?:categories.company_id = ?i',
            $category_name,
            $lang_code,
            $parent_id,
            $company_id
        );

        $categories[] = $current_category;
    }

    return $categories;
}

/**
 * Changes status of the specified category.
 *
 * @param int    $category_id Category ID.
 * @param string $status_to   New status for category.
 *
 * @return bool
 */
function fn_change_category_status($category_id, $status_to)
{
    if (empty($category_id) || empty($status_to)) {
        return false;
    }

    return fn_tools_update_status(
        [
            'table'             => 'categories',
            'status'            => $status_to,
            'id_name'           => 'category_id',
            'id'                => $category_id,
            'show_error_notice' => false
        ]
    );
}

/**
 * Increases level of all categories by one.
 *
 * phpcs:ignore
 * @param array $categories_tree Categories tree
 *
 * @return void
 */
function fn_increase_category_level(array &$categories_tree)
{
    foreach ($categories_tree as &$cat) {
        if (isset($cat['level'])) {
            $cat['level']++;
        }

        if (empty($cat['subcategories'])) {
            continue;
        }

        fn_increase_category_level($cat['subcategories']);
    }
}
