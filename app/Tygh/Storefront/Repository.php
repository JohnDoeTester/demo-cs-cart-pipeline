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

namespace Tygh\Storefront;

use Tygh\BlockManager\Layout;
use Tygh\Common\OperationResult;
use Tygh\Common\Robots;
use Tygh\Database\Connection;
use Tygh\Enum\StorefrontStatuses;
use Tygh\Enum\YesNo;
use Tygh\Settings;
use Tygh\Themes\Themes;

/**
 * Class Repository fetches, saves and removes Storefronts.
 *
 * @package Tygh\Storefront
 */
class Repository
{
    /**
     * @var \Tygh\Database\Connection
     */
    protected $db;

    /**
     * @var \Tygh\Storefront\Factory
     */
    protected $factory;

    /**
     * @var \Tygh\Storefront\Normalizer
     */
    protected $normalizer;

    /**
     * @var \Tygh\Common\Robots
     */
    protected $robots;

    /**
     * @var \Tygh\Storefront\Storefront[]
     */
    protected $cache_by_url = [];

    /**
     * @var \Tygh\Storefront\Storefront[]
     */
    protected $cache_by_id = [];

    /**
     * @var \Tygh\Storefront\Storefront[]
     */
    protected $cache_by_company_id = [];

    /**
     * @var \Tygh\Storefront\Storefront
     */
    protected $cache_default_storefront = null;

    /**
     * @var array
     */
    protected static $cache_queries = [];

    /**
     * @var \Tygh\Storefront\RelationsManager
     */
    protected $relations_manager;

    public function __construct(
        Connection $db,
        Factory $factory,
        Normalizer $normalizer,
        RelationsManager $relation_manager,
        Robots $robots
    ) {
        $this->db = $db;
        $this->factory = $factory;
        $this->normalizer = $normalizer;
        $this->relations_manager = $relation_manager;
        $this->robots = $robots;
    }

    /**
     * Gets storefront by its URL.
     *
     * @param string $url URL (host + port + path)
     *
     * @return \Tygh\Storefront\Storefront|null
     */
    public function findByUrl($url)
    {
        if (parse_url($url, PHP_URL_SCHEME) === null) {
            $url = '//' . $url;
        }

        if (!isset($this->cache_by_url[$url])) {
            $parsed_url = parse_url($url);

            if (!isset($parsed_url['host'])) {
                return null;
            }

            $host = $parsed_url['host'];

            if (isset($parsed_url['port'])) {
                $host .= ':' . $parsed_url['port'];
            }
            $host_without_www = preg_replace('/^www\d*\./', '', $host);
            $path = trim(isset($parsed_url['path']) ? $parsed_url['path'] : '', '/');

            list($storefronts_by_host,) = $this->find([
                'host'       => $host_without_www,
                'sort_by'    => 'host',
                'sort_order' => 'desc',
                'cache'      => true,
                'get_total'  => false
            ]);

            if (count($storefronts_by_host) === 1) {
                $this->cache_by_url[$url] = reset($storefronts_by_host);
            } else {
                $this->cache_by_url[$url] = $this->findClosestMatchingByPath($path, $storefronts_by_host);
            }

            $this->addStorefrontToCache($this->cache_by_url[$url]);
        }

        return $this->cache_by_url[$url];
    }

    /**
     * Gets storefront by its ID.
     *
     * @param int $storefront_id
     *
     * @return \Tygh\Storefront\Storefront|null
     */
    public function findById($storefront_id)
    {
        $storefront_id = (int) $storefront_id;

        if (!$storefront_id) {
            return null;
        }

        if (isset($this->cache_by_id[$storefront_id])) {
            return $this->cache_by_id[$storefront_id];
        }

        list($storefronts,) = $this->find(['storefront_id' => $storefront_id, 'get_total' => false]);

        $storefront = reset($storefronts);

        if (!$storefront) {
            return null;
        }

        $this->addStorefrontToCache($storefront);

        return $storefront;
    }

    /**
     * Gets default storefront.
     *
     * @return \Tygh\Storefront\Storefront|null
     */
    public function findDefault()
    {
        if ($this->cache_default_storefront !== null) {
            return $this->cache_default_storefront;
        }

        list($storefronts,) = $this->find(['is_default' => true, 'get_total' => false]);

        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = reset($storefronts);

        if (!$storefront) {
            return null;
        }

        $this->cache_default_storefront = $storefront;
        $this->addStorefrontToCache($storefront);

        return $storefront;
    }

    /**
     * Gets storefronts with a specific company assigned to.
     *
     * @param int  $company_id
     * @param bool $get_single
     *
     * @return \Tygh\Storefront\Storefront[]|\Tygh\Storefront\Storefront|null
     */
    public function findByCompanyId($company_id, $get_single = true)
    {
        if (isset($this->cache_by_company_id[$company_id])) {
            $storefronts = $this->cache_by_company_id[$company_id];
        } else {
            list($storefronts,) = $this->find(['company_ids' => [$company_id], 'get_total' => false]);
            $this->cache_by_company_id[$company_id] = $storefronts;
        }

        if ($get_single) {
            $storefront = reset($storefronts);

            return $storefront ? $storefront : null;
        }

        return $storefronts;
    }

    /**
     * Gets storefronts which are available for company.
     *
     * @param int  $company_id Company identifier
     * @param bool $get_single Get single flag
     *
     * @return \Tygh\Storefront\Storefront[]|\Tygh\Storefront\Storefront|null
     */
    public function findAvailableForCompanyId($company_id, $get_single = true)
    {
        list($storefronts,) = $this->find(['company_ids' => [$company_id], 'is_search' => true]);

        if ($get_single) {
            $storefront = reset($storefronts);

            return $storefront ? $storefront : null;
        }

        return $storefronts;
    }

    /**
     * Finds the most suitable storefront by its path.
     *
     * @param string                        $path        Requested path
     * @param \Tygh\Storefront\Storefront[] $storefronts Storefronts to search in
     *
     * @return \Tygh\Storefront\Storefront|null
     */
    public function findClosestMatchingByPath($path, array $storefronts)
    {
        $path_parts = explode('/', trim($path, '/'));

        $matching_storefront = null;
        $max_path_match = 0;

        foreach ($storefronts as $storefront) {
            $storefront_path = trim(parse_url('//' . $storefront->url, PHP_URL_PATH) ?: '', '/');
            $storefront_path_parts = explode('/', $storefront_path);

            if (!$storefront_path && !$matching_storefront) {
                $matching_storefront = $storefront;
                continue;
            }

            $matching_path_parts = array_intersect_assoc($storefront_path_parts, $path_parts);

            if (count($matching_path_parts) < $max_path_match) {
                continue;
            }
            $max_path_match = count($matching_path_parts);

            if (count($storefront_path_parts) > $max_path_match) {
                continue;
            }
            $matching_storefront = $storefront;
        }

        return $matching_storefront;
    }

    /**
     * Searches storefronts by the specified params.
     *
     * @param array $params         Search parameters
     * @param int   $items_per_page Amount of items per page
     *
     * @return array Contains found storefronts and search parameters
     */
    public function find(array $params = [], $items_per_page = 0)
    {
        $params = $this->populateDefaultFindParameters($params);

        $fields = [
            '' => 'storefronts.*',
        ];

        /**
         * Prepare params before find storefront.
         *
         * @param array    $params         Search parameters
         * @param int      $items_per_page Amount of items per page
         * @param string[] $fields         Fields to fetch
         */
        fn_set_hook('storefront_repository_find_pre', $params, $items_per_page, $fields);

        $join = $this->buildJoins($params);
        $conditions = $this->buildConditions($params);
        $order_by = $this->buildOrderBy($params);
        $group_by = $this->buildGroupBy($params);
        $having = [];
        $limit = $this->buildLimit($params, $items_per_page);

        /**
         * Executes when searching storefronts before the query is executed,
         * allows you to modify SQL query parts.
         *
         * @param array    $params         Search parameters
         * @param int      $items_per_page Amount of items per page
         * @param string[] $fields         Fields to fetch
         * @param string[] $join           JOIN parts of the query
         * @param string[] $conditions     WHERE parts of the query
         * @param string   $group_by       GROUP BY part of the query
         * @param string[] $having         HAVING parts of the query
         * @param string   $order_by       ORDER BY part of the query
         * @param string   $limit          LIMIT part of the query
         */
        fn_set_hook(
            'storefront_repository_find',
            $params,
            $items_per_page,
            $fields,
            $join,
            $conditions,
            $group_by,
            $having,
            $order_by,
            $limit
        );

        $sql = $this->db->quote(
            'SELECT ?p FROM ?:storefronts AS storefronts ?p WHERE ?p ?p ?p ?p ?p',
            implode(',', $fields),
            implode(' ', $join),
            implode(' ', $conditions),
            $group_by
                ? 'GROUP BY ' . $group_by
                : '',
            $having
                ? 'HAVING ' . implode(' ', $having)
                : '',
            $order_by,
            $limit
        );

        if (!empty($params['cache'])) {
            $cache_key = md5($sql);

            if (isset(self::$cache_queries[$cache_key])) {
                $storefronts = self::$cache_queries[$cache_key];
            } else {
                self::$cache_queries[$cache_key] = $storefronts = $this->db->getHash($sql, 'storefront_id');
            }
        } else {
            $storefronts = $this->db->getHash($sql, 'storefront_id');
        }

        foreach ($storefronts as &$storefront) {
            $storefront = $this->factory->fromArray($storefront);
        }
        unset($storefront);

        if ($params['get_total']) {
            $params['total_items'] = $this->getCount($params);
        }

        return [$storefronts, $params];
    }

    /**
     * Counts amount of storefronts that match criteria.
     *
     * @param array $params Search parameters
     *
     * @return int
     */
    public function getCount(array $params = [])
    {
        $params = $this->populateDefaultFindParameters($params);

        $fields = [
            '' => 'COUNT(DISTINCT storefronts.storefront_id)',
        ];
        $join = $this->buildJoins($params);
        $conditions = $this->buildConditions($params);

        /**
         * Executes when counting storefronts before the query is executed,
         * allows you to modify SQL query parts.
         *
         * @param array    $params     Search parameters
         * @param string[] $fields     Fields to fetch
         * @param string[] $join       JOIN parts of the query
         * @param string[] $conditions WHERE parts of the query
         */
        fn_set_hook(
            'storefront_repository_get_count',
            $params,
            $fields,
            $join,
            $conditions
        );

        $sql = $this->db->quote(
            'SELECT ?p FROM ?:storefronts AS storefronts ?p WHERE ?p',
            implode(',', $fields),
            implode(' ', $join),
            implode(' ', $conditions)
        );

        if (!empty($params['cache'])) {
            $cache_key = md5($sql);

            if (isset(self::$cache_queries[$cache_key])) {
                $count = self::$cache_queries[$cache_key];
            } else {
                self::$cache_queries[$cache_key] = $count = (int) $this->db->getField($sql);
            }
        } else {
            $count = (int) $this->db->getField($sql);
        }

        return $count;
    }

    /**
     * Updates or creates a storefront.
     *
     * @param \Tygh\Storefront\Storefront $storefront
     *
     * @return \Tygh\Common\OperationResult
     */
    public function save(Storefront $storefront)
    {
        $validation_result = $this->validateBeforeSave($storefront);
        if (!$validation_result->isSuccess()) {
            return $validation_result;
        }

        $save_result = new OperationResult(true);

        $storefront_data = $storefront->toArray(false);
        $storefront_data = $this->normalizeDataBeforeSave($storefront_data);

        /**
         * Executes before save storefront, allows to perform additional actions
         *
         * @param \Tygh\Storefront\Storefront $storefront      Storefront
         * @param array                       $storefront_data Storefront data
         */
        fn_set_hook('storefront_repository_save_pre', $storefront, $storefront_data);

        $storefront_id = $this->updateStorefront($storefront->storefront_id, $storefront_data);

        foreach ($this->relations_manager->getRelations() as $relation_name) {
            if (isset($storefront_data[$relation_name])) {
                $this->relations_manager->updateRelations($storefront_id, $relation_name, $storefront_data[$relation_name]);
            }
        }

        $copy_layouts_from_storefront_id = null;
        if (!empty($storefront->extra['copy_layouts_from_storefront_id'])) {
            $source_storefront = $this->findById($storefront->extra['copy_layouts_from_storefront_id']);
            $storefront->theme_name = $source_storefront->theme_name;
            $copy_layouts_from_storefront_id = $source_storefront->storefront_id;
        }
        if (!$this->isThemeInstalled($storefront_id, $storefront->theme_name)) {
            $this->installTheme($storefront_id, $storefront->theme_name, $copy_layouts_from_storefront_id);
        } else {
            $this->setTheme($storefront_id, $storefront->theme_name);
        }

        if (!$storefront->storefront_id) {
            $clone_storefront_id = null;
            if (!empty($_REQUEST['company_data']['clone_from'])) {
                $clone_storefront = $this->findByCompanyId($_REQUEST['company_data']['clone_from']);
                $clone_storefront_id = $clone_storefront->storefront_id;
            }
            $this->updateRobotsData($storefront_id, $clone_storefront_id);
        }

        if ($storefront->is_default) {
            $this->undefaultOherStorefronts($storefront_id);
        }

        $save_result->setData($storefront_id);

        $this->clearInnerCache();

        /**
         * Executes when saving storefront, allows to perform additional actions
         *
         * @param \Tygh\Storefront\Storefront  $storefront  storefront
         * @param \Tygh\Common\OperationResult $save_result result of the save process
         */
        fn_set_hook(
            'storefront_repository_save_post',
            $storefront,
            $save_result
        );

        return $save_result;
    }

    /**
     * Deletes a storefront.
     *
     * @param \Tygh\Storefront\Storefront $storefront
     *
     * @return \Tygh\Common\OperationResult
     */
    public function delete(Storefront $storefront)
    {
        $operation_result = new OperationResult(true);

        foreach ($this->relations_manager->getRelations() as $relation_name) {
            $this->relations_manager->deleteRelations($storefront->storefront_id, $relation_name);
        }
        $this->deleteStorefront($storefront->storefront_id);

        Settings::instance()->removeStorefrontSettings($storefront->storefront_id);
        $this->deleteLayouts($storefront->storefront_id);
        $this->deleteLogos($storefront->storefront_id);
        $this->deleteCategories($storefront->storefront_id);
        $this->deleteRobotsData($storefront->storefront_id);
        $this->clearInnerCache();

        /**
         * Executes when deleting storefront, allows you to clear additional storefront data
         *
         * @param \Tygh\Storefront\Storefront  $storefront       storefront for remove
         * @param \Tygh\Common\OperationResult $operation_result result of the storefront removal process
         */
        fn_set_hook(
            'storefront_repository_delete_post',
            $storefront,
            $operation_result
        );

        return $operation_result;
    }

    /**
     * Updates storefront itself.
     *
     * @param int   $storefront_id
     * @param array $storefront_data
     *
     * @return int Updated storefront ID or created storefront ID
     */
    protected function updateStorefront($storefront_id, array $storefront_data)
    {
        if ($storefront_id) {
            $storefront_data['storefront_id'] = $storefront_id;
        }

        $this->db->replaceInto('storefronts', $storefront_data);
        if (!$storefront_id) {
            $storefront_id = $this->db->getInsertId();
        }

        return $storefront_id;
    }

    /**
     * Deletes storefront.
     *
     * @param int $storefront_id
     */
    protected function deleteStorefront($storefront_id)
    {
        $this->db->query('DELETE FROM ?:storefronts WHERE storefront_id = ?i', $storefront_id);
    }

    /**
     * Provides a set of strings that are used in an SQL query to search a storefront by host.
     *
     * @param string $host Storefront host
     *
     * @return string[]
     */
    protected function getHostVariants($host)
    {
        $www_host = "www.{$host}";
        $www_n_host = "www_.{$host}";
        $dir = "{$host}/%";
        $www_dir = "www.{$host}/%";
        $www_n_dir = "www_.{$host}/%";

        return [
            $host,
            $www_host,
            $www_n_host,
            $dir,
            $www_dir,
            $www_n_dir,
        ];
    }

    /**
     * Provides WHERE part data of an SQL query for storefronts search.
     *
     * @param array $params Search parameters
     *
     * @return string[]
     */
    protected function buildConditions(array $params)
    {
        $conditions = [
            '' => '1 = 1',
        ];

        if ($params['storefront_id'] !== null) {
            $conditions['storefront_id'] = $this->db->quote(
                'AND storefronts.storefront_id IN (?n)',
                $this->normalizer->getEnumeration($params['storefront_id'])
            );
        }
        if ($params['status']) {
            $conditions['status'] = $this->db->quote(
                'AND storefronts.status IN (?a)',
                $this->normalizer->getEnumeration($params['status'])
            );
        }
        if ($params['redirect_customer']) {
            $conditions['redirect_customer'] = $this->db->quote(
                'AND storefronts.redirect_customer IN (?a)',
                YesNo::toId($params['redirect_customer'])
            );
        }
        if ($params['is_default'] !== null) {
            $conditions['is_default'] = $this->db->quote(
                'AND storefronts.is_default IN (?a)',
                YesNo::toId($params['is_default'])
            );
        }
        if ($params['url'] !== null && $params['url'] !== '') {
            if ($params['is_search']) {
                $conditions['url'] = $this->db->quote('AND storefronts.url LIKE ?l', "%{$params['url']}%");
            } else {
                $conditions['url'] = $this->db->quote('AND storefronts.url = ?s', $params['url']);
            }
        }

        if ($params['host'] !== null) {
            list($host, $www_host, $www_n_host, $dir, $www_dir, $www_n_dir) = $this->getHostVariants($params['host']);

            $conditions['host'] = $this->db->quote(
                'AND (storefronts.url = ?s OR storefronts.url = ?s OR storefronts.url LIKE ?l OR storefronts.url LIKE ?l OR storefronts.url LIKE ?l OR storefronts.url LIKE ?l)',
                $host,
                $www_host,
                $www_n_host,
                $dir,
                $www_dir,
                $www_n_dir
            );
        }

        if ($params['name']) {
            if ($params['is_search']) {
                $conditions['name'] = $this->db->quote('AND storefronts.name LIKE ?l', "%{$params['name']}%");
            } else {
                $conditions['name'] = $this->db->quote('AND storefronts.name = ?s', $params['name']);
            }
        }

        if ($params['theme_name']) {
            $conditions['theme_name'] = $this->db->quote('AND storefronts.theme_name = ?s', $params['theme_name']);
        }

        $is_search = YesNo::toBool($params['is_search']);
        foreach ($this->relations_manager->getRelations() as $relation_name) {
            if ($params[$relation_name]) {
                $conditions[$relation_name] = $this->relations_manager->buildCondition(
                    $relation_name,
                    $this->normalizer->getEnumeration($params[$relation_name]),
                    $is_search
                );
            }
        }

        return $conditions;
    }

    /**
     * Provides JOIN part data of an SQL query for storefronts search.
     *
     * @param array $params Search parameters
     *
     * @return string[]
     */
    protected function buildJoins(array $params)
    {
        $joins = [];

        foreach ($this->relations_manager->getRelations() as $relation_name) {
            if ($params[$relation_name]) {
                $joins[$relation_name] = $this->relations_manager->buildJoin($relation_name);
            }
        }

        return $joins;
    }

    /**
     * Provides ORDER BY part data of an SQL query for storefronts search.
     *
     * @param array $params Search parameters
     *
     * @return string
     */
    protected function buildOrderBy(array &$params)
    {
        $sortings = [
            'storefront_id'     => 'storefronts.storefront_id',
            'url'               => 'storefronts.url',
            'status'            => 'storefronts.status',
            'redirect_customer' => 'storefronts.redirect_customer',
            'is_default'        => 'storefronts.is_default',
            'name'              => 'storefronts.name',
            'theme_name'        => 'storefronts.theme_name',
        ];

        if ($params['host'] !== null) {
            list($host, $www_host, $www_n_host, $dir, $www_dir, $www_n_dir) = $this->getHostVariants($params['host']);

            $sortings['host'] = $this->db->quote(
                'storefronts.url = ?s DESC, storefronts.url = ?s DESC, storefronts.url LIKE ?l DESC, storefronts.url LIKE ?l DESC, storefronts.url LIKE ?l DESC, storefronts.url LIKE ?l',
                $host,
                $www_host,
                $www_n_host,
                $dir,
                $www_dir,
                $www_n_dir
            );
        }

        $order_by = db_sort($params, $sortings, 'is_default', 'desc');

        return $order_by;
    }

    /**
     * Populates default storefronts search parameters.
     *
     * @param array $params Search parameters
     *
     * @return array
     */
    protected function populateDefaultFindParameters(array $params)
    {
        $default_params = [
            'host'              => null,
            'url'               => null,
            'storefront_id'     => null,
            'status'            => null,
            'is_default'        => null,
            'redirect_customer' => null,
            'sort_by'           => 'is_default',
            'sort_order'        => 'desc',
            'page'              => 1,
            'name'              => null,
            'theme_name'        => null,
            'is_search'         => false,
            'group_by'          => 'storefront_id',
            'get_total'         => true,
        ];

        // lazy-loaded fields
        foreach ($this->relations_manager->getRelations() as $relation_name) {
            $default_params[$relation_name] = [];
        }

        $populated_params = array_merge($default_params, $params);

        return $populated_params;
    }

    /**
     * Provides LIMIT part data of an SQL query for storefronts search.
     *
     * @param array $params         Search parameters
     * @param int   $items_per_page Items per page
     *
     * @return string[]
     */
    protected function buildLimit(array $params, $items_per_page = 0)
    {
        $limit = '';
        if ($items_per_page !== 0) {
            $limit = db_paginate($params['page'], $items_per_page);
        }

        return $limit;
    }

    /**
     * Provides GROUP BY part data of an SQL query for storefronts search.
     *
     * @param array $params Search parameters
     *
     * @return string
     */
    protected function buildGroupBy(array $params)
    {
        $grouppings = [
            'storefront_id' => 'storefronts.storefront_id',
            'none'          => '',
        ];

        if (isset($grouppings[$params['group_by']])) {
            return $grouppings[$params['group_by']];
        }

        return '';
    }

    /**
     * Normalizes storefront data before save.
     *
     * @param array $data
     *
     * @return array
     */
    protected function normalizeDataBeforeSave(array $data)
    {
        unset($data['storefront_id']);

        $data = $this->normalizer->normalizeStorefrontData($data);

        if (isset($data['is_default'])) {
            $data['is_default'] = YesNo::toId($data['is_default']);
        }

        if (isset($data['is_accessible_for_authorized_customers_only'])) {
            $data['is_accessible_for_authorized_customers_only'] = YesNo::toId($data['is_accessible_for_authorized_customers_only']);
        }

        if (isset($data['redirect_customer'])) {
            $data['redirect_customer'] = YesNo::toId($data['redirect_customer']);
        }

        return $data;
    }

    /**
     * Adds companies to storefronts.
     *
     * @param int[] $company_ids
     * @param int[] $storefront_ids
     */
    public function addCompaniesToStorefronts($company_ids, $storefront_ids)
    {
        $company_ids = (array) $company_ids;

        /** @var \Tygh\Storefront\Storefront[] $storefronts */
        list($storefronts) = $this->find(['storefront_id' => $storefront_ids, 'get_total' => false]);
        foreach ($storefronts as $storefront) {
            $storefront_company_ids = array_merge($storefront->getCompanyIds(), $company_ids);
            $storefront_company_ids = array_unique($storefront_company_ids);
            $storefront->setCompanyIds($storefront_company_ids);
            $this->save($storefront);
        }
    }

    /**
     * Removes companies from storefronts.
     *
     * @param int[] $company_ids
     * @param int[] $storefront_ids
     */
    public function removeCompaniesFromStorefronts($company_ids, $storefront_ids)
    {
        $company_ids = (array) $company_ids;

        /** @var \Tygh\Storefront\Storefront[] $storefronts */
        list($storefronts) = $this->find(['storefront_id' => $storefront_ids, 'get_total' => false]);
        foreach ($storefronts as $storefront) {
            $storefront_company_ids = array_diff($storefront->getCompanyIds(), $company_ids);
            $storefront->setCompanyIds($storefront_company_ids);
            $this->save($storefront);
        }
    }

    /**
     * Validates storefront before saving it.
     *
     * @param \Tygh\Storefront\Storefront $storefront
     *
     * @return \Tygh\Common\OperationResult
     */
    protected function validateBeforeSave(Storefront $storefront)
    {
        $result = new OperationResult(true);

        if ($storefront->storefront_id && !$storefront->is_default) {
            $current_storefront = $this->findById($storefront->storefront_id);
            $is_default_changed = $current_storefront->is_default != $storefront->is_default;
            if ($is_default_changed && $this->getCount(['is_default' => true]) === 1) {
                $result->setSuccess(false);
                $result->addError(1, 'default_storefront_must_exist');
            }
        }

        list($storefronts_with_same_url,) = $this->find(['url' => $storefront->url, 'get_total' => false]);
        foreach ($storefronts_with_same_url as $storefront_with_same_url) {
            if ($storefront_with_same_url->storefront_id != $storefront->storefront_id) {
                $result->setSuccess(false);
                $result->addError(2, 'storefront_with_same_url_exists');
                break;
            }
        }

        return $result;
    }

    /**
     * Disables default status from the previous default storefront after the new default storefront was selected.
     *
     * @param int $new_default_storefront_id
     */
    protected function undefaultOherStorefronts($new_default_storefront_id)
    {
        list($default_storefronts,) = $this->find(['is_default' => true, 'get_total' => false]);
        foreach ($default_storefronts as $old_default_storefront) {
            if ($old_default_storefront->storefront_id != $new_default_storefront_id) {
                $old_default_storefront->is_default = false;
                $this->save($old_default_storefront);
            }
        }
    }

    /**
     * Checks whether a theme that was set for the storefront is installed.
     *
     * @param int    $storefront_id
     * @param string $theme_name
     *
     * @return bool
     */
    public function isThemeInstalled($storefront_id, $theme_name)
    {
        return Themes::factory($theme_name)->isInstalled($storefront_id);
    }

    /**
     * Installs a theme for the storefront.
     *
     * @param int    $storefront_id
     * @param string $theme_name
     * @param int    $copy_layouts_from_storefront_id
     */
    public function installTheme($storefront_id, $theme_name, $copy_layouts_from_storefront_id = null)
    {
        $install_layouts = !$copy_layouts_from_storefront_id;

        fn_install_theme($theme_name, 0, $install_layouts, $storefront_id);

        if ($copy_layouts_from_storefront_id) {
            fn_clone_layouts([], 0, 0, $copy_layouts_from_storefront_id, $storefront_id);
        }

        $this->setTheme($storefront_id, $theme_name);
    }

    /**
     * Deletes layouts of the storefront's theme.
     *
     * @param int $storefront_id
     */
    protected function deleteLayouts($storefront_id)
    {
        $layouts_instance = Layout::instance(0, [], $storefront_id);
        $layouts = $layouts_instance->getList();
        foreach ($layouts as $layout) {
            $layouts_instance->delete($layout['layout_id']);
        }
    }

    /**
     * Gets storefronts with a specific layout assigned to.
     *
     * @param int|int[] $layout_id
     * @param bool      $get_single
     *
     * @return \Tygh\Storefront\Storefront[]|\Tygh\Storefront\Storefront
     *
     * @psalm-suppress InvalidReturnType
     */
    public function findByLayoutId($layout_id, $get_single = true)
    {
        $layout_ids = (array) $layout_id;
        $storefronts_by_layout = $this->db->getSingleHash(
            'SELECT layout_id, storefront_id FROM ?:bm_layouts WHERE layout_id IN (?n)',
            ['layout_id', 'storefront_id'],
            $layout_ids
        );

        /** @var \Tygh\Storefront\Storefront[] $storefronts */
        list($storefronts,) = $this->find(['storefront_id' => $storefronts_by_layout, 'get_total' => false]);

        if ($get_single) {
            $storefront = reset($storefronts);

            return $storefront;
        }

        foreach ($storefronts_by_layout as $layout_id => $storefront_id) {
            $storefronts_by_layout[$layout_id] = $storefronts[$storefront_id];
        }

        /** @psalm-suppress InvalidReturnStatement */
        return $storefronts_by_layout;
    }

    /**
     * Sets a theme for the storefront.
     *
     * @param int    $storefront_id Storefront ID
     * @param string $theme_name    Theme name
     */
    public function setTheme($storefront_id, $theme_name)
    {
        $this->db->query(
            'UPDATE ?:storefronts SET theme_name = ?s WHERE storefront_id = ?i',
            $theme_name,
            $storefront_id
        );

        $this->clearInnerCache();
    }

    /**
     * Deletes logos for a storefront.
     *
     * @param int $storefront_id
     */
    protected function deleteLogos($storefront_id)
    {
        $logo_ids = $this->db->getColumn('SELECT logo_id FROM ?:logos WHERE storefront_id = ?i', $storefront_id);

        if ($logo_ids) {
            foreach ($logo_ids as $logo_id) {
                fn_delete_image_pairs($logo_id, 'logos');
            }

            $this->db->query('DELETE FROM ?:logos WHERE logo_id IN (?n)', $logo_ids);
        }
    }

    /**
     * Checks whether there are multiple storefronts that relate to the same object.
     *
     * @param array $params Search parameters
     *
     * @return bool[] Contains two boolean flags; the first one specifies if the sharing is possible at all,
     *                the second one indicates if there are multiple storefronts that relate to the same object.
     */
    public function getSharingDetails(array $params)
    {
        $is_sharing_enabled = $this->getCount() > 1;
        $is_shared = false;

        if ($is_sharing_enabled) {
            $is_shared = $this->getCount($params) > 1;
        }

        return [$is_sharing_enabled, $is_shared];
    }

    /**
     * Updates or create new robots.txt records assigned to a storefront
     *
     * @param int      $storefront_id       Id of storefront which robots data will be updated.
     * @param int|null $clone_storefront_id Id of storefront from robots data will be cloned.
     */
    protected function updateRobotsData($storefront_id, $clone_storefront_id)
    {
        $this->robots->addRobotsDataForNewStorefront($storefront_id, $clone_storefront_id);
    }

    /*
     * Deletes robots.txt data assigned to a stofefront
     *
     * @param int $storefront_id
     */
    protected function deleteRobotsData($storefront_id)
    {
        $this->robots->deleteRobotsDataByStorefrontId($storefront_id);
    }

    /**
     * Adds storefront to inner cache
     *
     * @param \Tygh\Storefront\Storefront|null $storefront
     */
    protected function addStorefrontToCache(Storefront $storefront = null)
    {
        if ($storefront === null) {
            return;
        }

        $this->cache_by_id[$storefront->storefront_id] = $storefront;
    }

    /**
     * Clears inner cache
     */
    protected function clearInnerCache()
    {
        $this->cache_by_id = [];
        $this->cache_by_url = [];
        $this->cache_by_company_id = [];
        $this->cache_default_storefront = null;
        self::$cache_queries = [];
    }
  
    /**
     * Returns the first active storefront.
     *
     * @return \Tygh\Storefront\Storefront|null
     */
    public function findFirstActiveStorefront()
    {
        list($storefronts,) = $this->find(['status' => StorefrontStatuses::OPEN, 'get_total' => false], 1);

        $storefront = array_shift($storefronts);

        return $storefront;
    }

    /**
     * Deletes categories assigned to a storefront
     *
     * @param int $storefront_id Storefront identifier
     *
     * @throws \Tygh\Exceptions\DatabaseException Wrong sql query or issues with execution.
     *
     * @return void
     */
    protected function deleteCategories($storefront_id)
    {
        $cat_ids = $this->db->getColumn('SELECT category_id FROM ?:categories WHERE storefront_id = ?i', $storefront_id);
        foreach ($cat_ids as $cat_id) {
            fn_delete_category($cat_id, false);
            $this->db->query('DELETE FROM ?:products_categories WHERE category_id = ?i', $cat_id);
        }
    }
}
