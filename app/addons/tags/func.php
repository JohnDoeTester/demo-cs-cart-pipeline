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

use Tygh\Registry;
use Tygh\Navigation\LastView;
use Tygh\Addons\ProductVariations\ServiceProvider as ProductVariationsServiceProvider;
use Tygh\Addons\MasterProducts\ServiceProvider as MasterProductsServiceProvider;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_get_tags_company_condition($field)
{
    if (fn_allowed_for('ULTIMATE')) {
        return fn_get_company_condition($field);
    }

    return '';
}

function fn_tags_build_conditions($params)
{
    $conditions = fn_get_tags_company_condition('?:tags.company_id');

    if (!empty($params['object_type'])) {
        $conditions .= db_quote(" AND ?:tag_links.object_type = ?s", $params['object_type']);
    }

    if (!empty($params['status'])) {
        $conditions .= db_quote(" AND ?:tags.status IN (?a)", $params['status']);
    }

    if (!empty($params['object_id'])) {
        $conditions .= db_quote(" AND ?:tag_links.object_id = ?i", $params['object_id']);
    }

    if (isset($params['tag']) && fn_string_not_empty($params['tag'])) {
        $conditions .= db_quote(" AND ?:tags.tag LIKE ?l", "%".trim($params['tag'])."%");
    }

    if (!empty($params['period']) && $params['period'] != 'A') {
        list($params['time_from'], $params['time_to']) = fn_create_periods($params);

        $conditions .= db_quote(" AND (?:tags.timestamp >= ?i AND ?:tags.timestamp <= ?i)", $params['time_from'], $params['time_to']);
    }

    return $conditions;

}

function fn_get_tag_names($params = array())
{
    $join = db_quote("LEFT JOIN ?:tag_links ON ?:tags.tag_id = ?:tag_links.tag_id");
    $conditions = fn_tags_build_conditions($params);

    return db_get_fields("SELECT DISTINCT tag FROM ?:tags ?p WHERE 1 ?p", $join, $conditions);
}

function fn_get_tags($params = array(), $items_per_page = 0)
{
    // Init filter
    $params = LastView::instance()->update('tags', $params);

    $default_params = array(
        'page' => 1,
        'items_per_page' => $items_per_page
    );

    /**
     * Change parameters for getting tags
     *
     * @param array $params Params list
     * @param int $items_per_page Tags per page
     * @param array $default_params Default params
     */
    fn_set_hook('get_tags_pre', $params,  $items_per_page, $default_params);

    $params = array_merge($default_params, $params);

    $fields = array(
        '?:tags.tag_id',
        '?:tag_links.object_id',
        '?:tag_links.object_type',
        '?:tags.tag',
        '?:tags.status',
        'COUNT(?:tag_links.tag_id) as popularity'
    );

    $joins = array('LEFT JOIN ?:tag_links ON ?:tag_links.tag_id = ?:tags.tag_id');
    $conditions = fn_tags_build_conditions($params);

    // Define sort fields
    $sortings = array (
        'tag' => '?:tags.tag',
        'status' => '?:tags.status',
        'popularity' => 'popularity',
        'users' => 'users'
    );
    $sorting = db_sort($params, $sortings, 'tag', 'asc');
    $group = 'GROUP BY ?:tags.tag_id';

    // Restrict to active objects only
    if (!empty($params['only_active_objects'])) {
        $active_object_join = fn_tags_build_enabled_products_join($params);
        if ($active_object_join !== false) $joins[] = $active_object_join;
    }

    /**
     * Gets tags
     *
     * @param array $params Params list
     * @param int $items_per_page Tags per page
     * @param array $fields List of SQL fields to be selected in an SQL-query
     * @param array $joins List of strings with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $conditions String containing the SQL-query conditions prepended with a logical operator (AND or OR)
     * @param string $group String containing the SQL-query GROUP BY field
     * @param string $sorting String containing the SQL-query ORDER BY field
     */
    fn_set_hook('get_tags', $params, $items_per_page, $fields, $joins, $conditions, $group, $sorting);

    $limit = '';
    if (!empty($params['limit'])) {
        $limit = db_quote(' LIMIT 0, ?i', $params['limit']);
    } elseif (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(DISTINCT(?:tags.tag_id)) FROM ?:tags LEFT JOIN ?:tag_links ON ?:tags.tag_id = ?:tag_links.tag_id WHERE 1 ?p", $conditions);
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $tags = db_get_hash_array(
        "SELECT " . implode(', ', $fields) . " "
        . "FROM ?:tags " . implode(' ', $joins) . " WHERE 1 ?p $group $sorting $limit",
        'tag_id', $conditions
    );

    if (!empty($params['count_objects'])) {
        $objs = db_get_array(
            "SELECT tag_id, COUNT(DISTINCT(object_id)) as count, object_type "
            ."FROM ?:tag_links WHERE tag_id IN (?n) GROUP BY tag_id, object_type",
            array_keys($tags)
        );
        foreach ($objs as $v) {
            $tags[$v['tag_id']]['objects_count'][$v['object_type']] = $v['count'];
        }
    }

    // Generate popularity level
    foreach ($tags as $k => $v) {
        $level = ceil(log($v['popularity']));
        $tags[$k]['level'] = ($level > TAGS_MAX_LEVEL) ? TAGS_MAX_LEVEL : $level;
    }

    if (!empty($params['sort_popular'])) {
        $tags = fn_sort_array_by_key($tags, 'tag', SORT_ASC);
    }

    /**
     * Change tags
     *
     * @param array $params Params list
     * @param int $items_per_page Tags per page
     * @param array $tags Tags
     */
    fn_set_hook('get_tags_post', $params, $items_per_page, $tags);

    return array($tags, $params);
}

function fn_tags_update_product_post(&$product_data, $product_id)
{
    if (isset($product_data['tags'])) {
        if (!empty($product_data['tags'])) {
            fn_update_tags([
                'object_type' => 'P',
                'object_id' => $product_id,
                'values' => $product_data['tags']
            ], false);
        } else {
            fn_delete_tags_by_params([
                'object_id' => (int) $product_id,
                'object_type' => 'P',
                'company_id' => (int) Registry::get('runtime.company_id')
            ]);
        }
    }
}

function fn_tags_update_page_post(&$page_data, &$page_id)
{
    if (!empty($page_data['tags'])) {
        fn_update_tags(array(
            'object_type' => 'A',
            'object_id' => $page_id,
            'values' => $page_data['tags']
        ), false);
    }
}

function fn_delete_tag($tag_id)
{
    fn_delete_tags(array($tag_id));

    return true;
}

/**
 * Deletes the data from the `tags` table
 *
 * @param int[] $tag_ids The numeric identifiers of the tags
 *
 * @return bool true
 */
function fn_delete_tags($tag_ids)
{
    fn_delete_tags_by_params([
        'tag_ids' => $tag_ids
    ]);

    /**
     * This hook is executed after the tags are deleted from the database by their numeric identifiers
     *
     * @param array $tag_ids The numeric identifiers of the tags
     */
    fn_set_hook('delete_tags_post', $tag_ids);

    return true;
}

/**
 * Deletes the tag data by parameters.
 *
 * @param array $params The parameters for searching the tags
 *
 * @return bool
 *
 * @psalm-param array{
 *  object_id?: int,
 *  object_type?: string,
 *  tag?: string,
 *  tag_id?: int,
 *  tag_ids?: int[],
 *  company_id?: int,
 *  use_company_condition?: bool
 * } $params
 */
function fn_delete_tags_by_params(array $params)
{
    $condition = $condition2 = '';
    $join = db_quote('LEFT JOIN ?:tag_links ON ?:tags.tag_id = ?:tag_links.tag_id ');

    if (!empty($params['object_id'])) {
        $condition .= db_quote(' AND object_id = ?i', $params['object_id']);
    }

    if (!empty($params['object_type'])) {
        $condition .= db_quote(' AND object_type = ?s', $params['object_type']);
    }

    if (!empty($params['tag'])) {
        $condition2 = db_quote(' AND tag = ?s', $params['tag']);
    }

    if (!empty($params['tag_id'])) {
        $condition2 = db_quote(' AND ?:tags.tag_id = ?i', $params['tag_id']);
    }

    if (!empty($params['tag_ids'])) {
        $condition2 .= db_quote(' AND ?:tags.tag_id IN (?n)', $params['tag_ids']);
    }

    if (!empty($params['company_id']) || !empty($params['use_company_condition'])) {
        $condition2 .= fn_get_tags_company_condition('?:tags.company_id');
    }

    $tag_ids = db_get_fields('SELECT DISTINCT ?:tags.tag_id FROM ?:tags ?p WHERE 1 ?p ?p', $join, $condition, $condition2);

    db_query('DELETE FROM ?:tag_links WHERE tag_id IN (?n) ?p', $tag_ids, $condition);

    // Check if tags have links and delete them if not
    $_tag_ids = db_get_fields('SELECT DISTINCT tag_id FROM ?:tag_links WHERE tag_id IN (?n)', $tag_ids);
    $deleted_tag_ids = array_diff($tag_ids, $_tag_ids);
    if (!empty($deleted_tag_ids)) {
        db_query('DELETE FROM ?:tags WHERE tag_id IN (?n)', $deleted_tag_ids);
    }

    /**
     * Actions after deleting the tag data by parameters.
     *
     * @param array $params          This hook is executed after the tags are deleted by the specified parameters
     * @param array $tag_ids         List of founded by params tag identifiers
     * @param array $deleted_tag_ids List of deleted tag identifiers
     */
    fn_set_hook('delete_tags_by_params_post', $params, $tag_ids, $deleted_tag_ids);

    return true;
}

function fn_tags_delete_product_post(&$product_id)
{
    return fn_delete_tags_by_params([
        'object_id' => (int) $product_id,
        'object_type' => 'P'
    ]);
}

function fn_tags_delete_page(&$page_id)
{
    return fn_delete_tags_by_params([
        'object_id' => (int) $page_id,
        'object_type' => 'A'
    ]);
}

//
// This function clones product tags
//
function fn_tags_clone_product(&$product_id, &$pid)
{
    $tags = db_get_array("SELECT * FROM ?:tag_links WHERE object_type = 'P' AND object_id = ?i", $product_id);
    foreach ($tags as $tag) {
        $tag['object_id'] = $pid;
        db_query("INSERT INTO ?:tag_links ?e", $tag);
    }
}

function fn_tags_clone_page(&$page_id, &$pid)
{
    $tags = db_get_array("SELECT * FROM ?:tag_links WHERE object_type = 'A' AND object_id = ?i", $page_id);
    foreach ($tags as $tag) {
        $tag['object_id'] = $pid;
        db_query("INSERT INTO ?:tag_links ?e", $tag);
    }
}

function fn_update_tag($tag_data, $tag_id = 0)
{
    // check if such tag is exists
    $existing_id = db_get_field("SELECT tag_id FROM ?:tags WHERE tag = ?s ?p", $tag_data['tag'], fn_get_tags_company_condition('?:tags.company_id'));

    // Update tag
    if (!empty($tag_id)) {
        if (empty($existing_id) || $tag_id == $existing_id) {
            $update_id = $tag_id;
        } else {
            $update_id = $existing_id;
            fn_delete_tags_by_params([
                'tag_id' => (int) $tag_id,
                'use_company_condition' => true
            ]);
        }

        db_query("UPDATE ?:tags SET ?u WHERE tag_id = ?i ?p", $tag_data, $update_id, fn_get_tags_company_condition('?:tags.company_id'));

        $tag_id = $update_id;

        // New tag
    } elseif (empty($existing_id)) {
        if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id') && empty($tag_data['company_id'])) {
            $tag_data['company_id'] = Registry::get('runtime.company_id');
        }
        $tag_data['timestamp'] = TIME;
        $tag_id = db_query("INSERT INTO ?:tags ?e", $tag_data);

        // New tag, but tag with same name exists
    } elseif (!empty($tag_data['status'])) {
        db_query("UPDATE ?:tags SET status = ?s WHERE tag_id = ?i ?p", $tag_data['status'], $existing_id, fn_get_tags_company_condition('?:tags.company_id'));
        $tag_id = $existing_id;

    } else {
        $tag_id = $existing_id;
    }

    if (!empty($tag_data['object_id'])) {
        $_data = array(
            'object_id' => $tag_data['object_id'],
            'object_type' => $tag_data['object_type'],
            'tag_id' => $tag_id
        );

        db_query("REPLACE INTO ?:tag_links ?e", $_data);
    }

    return $tag_id;
}

/**
 * Updates the tag data.
 *
 * @param array $tags_data          The data required for updating the tag.
 * @param bool  $for_all_companies  The parameter that determines whether or not to update the tag data for all companies; true - update tag data for all companies.
 *
 * @return boolean true
 */
function fn_update_tags($tags_data, $for_all_companies = true)
{
    $tag_ids = [];
    $condition = '';
    if (!$for_all_companies) {
        $condition = fn_get_tags_company_condition('?:tags.company_id');
    }

    $values = $tags_data['values'];
    foreach ($values as $tag) {
        if (empty($tag)) {
            continue;
        }

        $tag_id = db_get_field('SELECT tag_id FROM ?:tags WHERE tag = ?s ?p', $tag, $condition);
        if (empty($tag_id)) {
            $_data = [
                'tag' => $tag,
                'status' => (AREA === 'A') ? 'A' : 'P',
                'timestamp' => TIME
            ];

            if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
                $_data['company_id'] = Registry::get('runtime.company_id');
            }

            $tag_id = db_query('INSERT INTO ?:tags ?e', $_data);
        }

        $tag_ids[] = $tag_id;

        db_replace_into('tag_links', [
            'object_id' => $tags_data['object_id'],
            'object_type' => $tags_data['object_type'],
            'tag_id' => $tag_id
        ]);
    }

    /** @var array<array-key, int> $tag_ids_to_delete */
    $tag_ids_to_delete  = db_get_hash_single_array(
        'SELECT ?:tags.tag_id FROM ?:tag_links '
        . 'LEFT JOIN ?:tags ON ?:tags.tag_id = ?:tag_links.tag_id '
        . 'WHERE object_id = ?i AND object_type = ?s AND ?:tags.tag_id NOT IN (?n) ?p ',
        ['tag_id', 'tag_id'],
        $tags_data['object_id'],
        $tags_data['object_type'],
        $tag_ids,
        $condition
    );

    if (!empty($tag_ids_to_delete)) {
        fn_delete_tags_by_params([
            'tag_ids' => $tag_ids_to_delete,
            'object_id' => (int) $tags_data['object_id'],
            'object_type' => (string) $tags_data['object_type']
        ]);
    }

    /**
     * This hook is executed after the data of the tags has been updated.
     *
     * @param array $tags_data          The data required for updating the tag.
     * @param bool  $for_all_companies  The parameter that determines whether or not to update the tag data for all companies; true - update tag data for all companies.
     * @param array $tag_ids_to_delete  List of deleted tag identifiers
     */
    fn_set_hook('update_tags_post', $tags_data, $for_all_companies, $tag_ids_to_delete);

    return true;
}

function fn_tags_get_products(&$params, &$fields, &$sortings, &$condition, &$join)
{
    if (Registry::get('addons.tags.tags_for_products') == 'Y') {
        if (isset($params['tag']) && fn_string_not_empty($params['tag'])) {
            $join .= db_quote(" INNER JOIN ?:tag_links ON ?:tag_links.object_id = products.product_id AND ?:tag_links.object_type = ?s", 'P');
            $join .= db_quote(" INNER JOIN ?:tags ON ?:tag_links.tag_id = ?:tags.tag_id ?p", fn_get_tags_company_condition('?:tags.company_id'));
            $condition .= db_quote(" AND (?:tags.tag = ?s)", trim($params['tag']));
            if (AREA == 'C') {
                $condition .= db_quote(" AND ?:tags.status = ?s", 'A');
            }
        }
    }

    return true;
}

function fn_tags_get_pages(&$params, &$join, &$conditions, &$fields, &$group_by, &$sortings)
{
    if (Registry::get('addons.tags.tags_for_pages') == 'Y') {
        if (isset($params['tag']) && fn_string_not_empty($params['tag'])) {
            $fields[] = '?:tag_links.*, ?:tags.tag, ?:tags.tag_id, ?:tags.timestamp';
            $join .= db_quote(" INNER JOIN ?:tag_links ON ?:pages.page_id = ?:tag_links.object_id AND ?:tag_links.object_type = ?s", 'A');
            $join .= db_quote(" INNER JOIN ?:tags ON ?:tag_links.tag_id = ?:tags.tag_id ?p", fn_get_tags_company_condition('?:tags.company_id'));
            $conditions .= db_quote(" AND (?:tags.tag = ?s)", trim($params['tag']));
            if (AREA == 'C') {
                $conditions .= db_quote(" AND ?:tags.status = ?s", 'A');
            }
        }
    }

    return true;
}

function fn_tags_seo_is_indexed_page(&$indexed_dispatches)
{
    $indexed_dispatches['tags.view'] = array('index' => array('tag'));
}

function fn_tags_get_predefined_statuses(&$type, &$statuses)
{
    if ($type == 'tags') {
        $statuses['tags'] = array(
            'A' => __('active'),
            'D' => __('disabled')
        );
    }
}

/**
 * Builds JOIN expression that limits resulting set of a query at the {{fn_get_tags()}} function.
 * Only tags that are linked to objects with an "active" status will be selected.
 *
 * @param array $params fn_get_tags parameter list
 *
 * @return string JOIN expression to be appended to query or empty string in the case of an unknown object type.
 */
function fn_tags_build_enabled_products_join($params)
{
    $selects = array(
        'A' => 'SELECT ?:pages.page_id AS obj_id, "A" AS object_type FROM ?:pages WHERE ?:pages.status = "A"',
        'P' => 'SELECT ?:products.product_id AS obj_id, "P" AS object_type FROM ?:products WHERE ?:products.status = "A"',
    );

    $join_expression = "INNER JOIN ((";
    $on_expression = ")) AS statuses ON statuses.obj_id = ?:tag_links.object_id AND statuses.object_type = ?:tag_links.object_type";

    if (empty($params['object_type'])) {
        return $join_expression . $selects['A'] . ") UNION (" . $selects['P'] . $on_expression;
    } else if (isset($selects[$params['object_type']])) {
        return $join_expression . $selects[$params['object_type']] . $on_expression;
    }

    return '';
}

function fn_product_variations_update_tags_post($tags_data, $for_all_companies)
{
    $object_id = isset($tags_data['object_id']) ? $tags_data['object_id'] : null;
    $object_type = isset($tags_data['object_type']) ? $tags_data['object_type'] : null;

    if ($object_id && $object_type === 'P') {
        $sync_service = ProductVariationsServiceProvider::getSyncService();

        $sync_service->onTableChanged('tag_links', $object_id);
    }
}

/**
 * The "update_tags_post" hook handler.
 *
 * Actions performed:
 *  - Sync tag data for offer of the master product.
 *
 * @param array<string, int|string> $tags_data         The data required for updating the tag.
 * @param bool                      $for_all_companies The parameter that determines whether or not to update the tag data for all companies; true - update tag data for all companies.
 *
 * @see fn_update_tags()
 */
function fn_master_products_update_tags_post($tags_data, $for_all_companies)
{
    $object_id = isset($tags_data['object_id']) ? (int) $tags_data['object_id'] : null;
    $object_type = isset($tags_data['object_type']) ? (string) $tags_data['object_type'] : null;

    if (!$object_id || $object_type !== 'P') {
        return;
    }
    $sync_service = MasterProductsServiceProvider::getService();
    $sync_service->onTableChanged('tag_links', $object_id);
}
