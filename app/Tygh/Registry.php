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

namespace Tygh;

use Closure;
use Tygh\Exceptions\DeveloperException;
use Tygh\Providers\StorefrontProvider;

/**
 * Class Registry
 *
 * @package Tygh
 *
 * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
class Registry
{
    private static $_storage = array();
    private static $_cached_keys = array();
    private static $_changed_tables = array();
    private static $_storage_cache = array();
    private static $_storage_cache_children_keys = array();
    private static $_cache_levels = array();
    private static $_cache_handlers = array();
    private static $_cache_handlers_are_updated = false;

    /**
     * @var \Tygh\Backend\Cache\ABackend $_cache Cache provider
     */
    private static $_cache = null;

    const NOT_FOUND = '/#not found#/';

    /**
     * Table where cache handlers are stored
     */
    const CACHE_HANDLERS_TABLE = 'cache_handlers';

    /**
     * @var Application
     */
    protected static $application = array();

    /**
     * @var array Keys that should be fetched from Application container
     */
    protected static $legacy_keys = array('crypt' => true, 'view' => true, 'api' => true, 'ajax' => true, 'class_loader' => true);

    public static function getCachedKeys()
    {
        return self::$_cached_keys;
    }

    /**
     * Puts variable to registry
     *
     * @param string  $key      key name
     * @param mixed   $value    key value
     * @param boolean $no_cache if set to true, data won't be cache even if it's registered in the cache
     *
     * @return boolean always true
     */
    public static function set($key, $value, $no_cache = false)
    {
        if (isset(self::$legacy_keys[$key])) {
            // Development::deprecated('Usage of Registry class for storing services is deprecated. Use Tygh::$app instead.');
            self::$application[$key] = $value;
            return true;
        }

        if (strpos($key, '.') !== false) {
            list($_key) = explode('.', $key);
        } else {
            $_key = $key;
        }

        $var = & self::_varByKey('create', $key);
        $var = $value;

        if ($no_cache == false && isset(self::$_cached_keys[$_key]) && self::$_cached_keys[$_key]['track'] == false) { // save cache immediatelly
            $_var = (strpos($key, '.') !== false) ? self::get($_key) : $value;

            self::_saveCache($_key, $_var);
            unset(self::$_cached_keys[$_key]);
        }

        return true;
    }

    /**
     * Gets variable from registry (value can be returned by reference)
     *
     * @param string $key key name
     *
     * @return mixed key value
     */
    public static function get($key)
    {
        if (isset(self::$legacy_keys[$key])) {
            // Development::deprecated('Usage of Registry class for storing services is deprecated. Use Tygh::$app instead.');

            return self::$application[$key];
        }

        $val = self::_varByKey('get', $key);

        return ($val !== self::NOT_FOUND) ? $val : null;
    }

    /**
     * @return array All variables registered at registry.
     */
    public static function getAll()
    {
        return self::$_storage;
    }

    /**
     * Pushes data to array
     *
     * @param string $key key name
     * @paramN mixed values to push to the key value
     *
     * @return boolean always true
     */
    public static function push()
    {
        $args = func_get_args();
        $key = array_shift($args);

        $data = self::get($key);
        if (!is_array($data)) {
            $data = array();
        }

        $data =	array_merge($data, $args);

        return self::set($key, $data);
    }

    /**
     * Deletes key from registry
     *
     * @param string $key key name
     *
     * @return boolean true if key found, false - otherwise
     */
    public static function del($key)
    {
        if (self::_varByKey('delete', $key) === self::NOT_FOUND) {
            return false;
        }

        return true;
    }

    /**
     * Private: performs key action
     *
     * @param string $action key action (get, create, delete)
     * @param string $key    key name
     *
     * @return mixed key value
     */
    private static function & _varByKey($action, $key)
    {
        if ($action === 'get' && isset(self::$_storage_cache[$key])) {
            return self::$_storage_cache[$key];
        }

        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);
            $length = sizeof($parts);
        } else {
            $parts = (array) $key;
            $length = 1;
        }

        $piece = & self::$_storage;

        if ($action === 'get') {
            $parent_key = '';

            foreach ($parts as $i => $part) {
                if (!is_array($piece) || !array_key_exists($part, $piece)) {
                    $result = self::NOT_FOUND;
                    return $result;
                }

                $piece = & $piece[$part];
                $parent_key .= $i === 0 ? $part : '.' . $part;

                self::$_storage_cache_children_keys[$parent_key][$key] = $key;
            }

            // cache complex keys only
            if ($length !== 1) {
                self::$_storage_cache[$key] = & $piece;
                return self::$_storage_cache[$key];
            }

            return $piece;
        } elseif ($action === 'create') {
            foreach ($parts as $i => $part) {
                if (!isset($piece[$part])) {
                    $piece[$part] = array();
                }

                $piece = & $piece[$part];
            }
        } elseif ($action === 'delete') {
            $removed = self::NOT_FOUND;

            foreach ($parts as $i => $part) {
                if (!is_array($piece) || !array_key_exists($part, $piece)) {
                    break;
                }

                if (($i + 1) === $length) {
                    unset($piece[$part], self::$_storage_cache[$key]);
                    $removed = true;
                    break;
                }

                $piece = & $piece[$part];
            }

            unset($piece);
            $piece = $removed;
        }

        if (isset(self::$_storage_cache_children_keys[$key])) {
            foreach (self::$_storage_cache_children_keys[$key] as $child_key) {
                unset(self::$_storage_cache_children_keys[$child_key], self::$_storage_cache[$child_key]);
            }
        }

        return $piece;
    }

    /**
     * Conditional get, returns default value if key does not exist in registry
     *
     * @param string $key     key name
     * @param mixed  $default default value
     *
     * @return mixed key value if exist, default value otherwise
     */
    public static function ifGet($key, $default)
    {
        $var = self::get($key);

        return $var !== null ? $var : $default;
    }

    /**
     * Checks if key exists in the registry
     *
     * @param string $key key name
     *
     * @return boolean true if key exists, false otherwise
     */
    public static function isExist($key)
    {
        $var = self::_varByKey('get', $key);

        return $var !== self::NOT_FOUND;
    }

    /**
     * Marks table as changed
     *
     * @param string $table table name
     *
     * @return boolean always true
     */
    public static function setChangedTables($table)
    {
        if ($table != self::CACHE_HANDLERS_TABLE) {
            self::$_changed_tables[$table] = true;
        }

        return true;
    }

    /**
     * Registers variable in the cache
     *
     * @param mixed  $key         key name. Array with 2 values can be passed: first - key name, second - key alias
     * @param mixed  $condition   cache reset condition - array with table names of expiration time (int)
     * @param string $cache_level indicates the cache dependencies on controller, language, user group, etc
     * @param bool   $track       if set to true, cache data will be collection during script execution and saved when
     *                            it finished
     *
     * @return boolean true if data is cached and valid, false - otherwise
     */
    public static function registerCache($key, $condition, $cache_level = null, $track = false)
    {
        if (empty(self::$_cache)) {
            self::cacheInit();
        }

        if (is_array($key)) {
            list($tag, $alias) = $key;
        } else {
            $alias = $key;
            $tag = '';
        }

        if (empty(self::$_cached_keys[$alias])) {
            self::$_cached_keys[$alias] = [
                'condition'       => $condition,
                'cache_level'     => $cache_level . (!empty($tag) ? $alias : ''),
                'track'           => $track,
                'hash'            => '',
                'tag'             => $tag,
                'cache_ttl'       => null,
            ];

            if (is_array($condition) && array_key_exists('update_handlers', $condition) && array_key_exists('ttl', $condition)) {
                self::$_cached_keys[$alias]['condition'] = $condition['update_handlers'];
                self::$_cached_keys[$alias]['cache_ttl'] = $condition['ttl'];
            }

            return !self::isExist($alias) && self::loadFromCache($alias);
        }

        return false;
    }

    /**
     * Retrieves a value from cache with a specified key.
     *
     * @param array|string $key Key name
     *
     * @return bool
     */
    public static function loadFromCache($key)
    {
        if (!isset(self::$_cached_keys[$key])) {
            return false;
        }

        $tag = self::$_cached_keys[$key]['tag'];
        $val = self::_getCache(!empty($tag) ? $tag : $key, self::$_cached_keys[$key]['cache_level']);

        if ($val !== null) {
            self::set($key, $val, true);

            // Get hash of original value for tracked data
            if (self::$_cached_keys[$key]['track'] == true) {
                self::$_cached_keys[$key]['hash'] = md5(serialize($val));
            }

            return true;
        }

        return false;
    }

    /**
     * Inits cache backend
     *
     * @return bool Always true
     */
    public static function cacheInit()
    {
        if (empty(self::$_cache)) {
            $_cache_class = self::ifGet('config.cache_backend', 'file');
            $_cache_class = '\\Tygh\\Backend\\Cache\\' . ucfirst($_cache_class);

            self::$_cache = new $_cache_class(self::get('config'));
        }

        return true;
    }

    /**
     * Resets cache instance component
     *
     * @internal
     */
    public static function resetCacheCompanyId()
    {
        if (empty(self::$_cache)) {
            return;
        }

        self::$_cache->resetCompanyId();
    }

    /**
     * Gets cached data
     *
     * @param string $key         key name
     * @param string $cache_level indicates the cache dependencies on controller, language, user group, etc
     *
     * @return mixed cached data if exist, NULL otherwise
     */
    private static function _getCache($key, $cache_level = NULL)
    {
        $time_start = microtime(true);
        $data = self::$_cache->get($key, $cache_level);
        Debugger::set_cache_query($key . '::' . $cache_level, microtime(true) - $time_start);

        return (($data !== false) && (!empty($data[0]))) ? $data[0] : NULL;
    }

    /**
     * Assigns database tables to cache key for future cache update
     * @param string $key         key name
     * @param array  $condition   tables list
     * @param string $cache_level cache level
     */
    private static function _updateHandlers($key, $condition, $cache_level)
    {
        if ($cache_level != self::cacheLevel('time')) {
            foreach ($condition as $table) {
                if (empty(self::$_cache_handlers[$table])) {
                    self::$_cache_handlers[$table] = array();
                }

                self::$_cache_handlers[$table][$key] = true;
                self::$_cache_handlers_are_updated = true;
            }
        }
    }

    /**
     * Saves data to cache
     * @param string $key key name
     * @param mixed  $val value
     */
    private static function _saveCache($key, $val)
    {
        if (empty(self::$_cached_keys[$key]['hash']) || self::$_cached_keys[$key]['hash'] != md5(serialize(self::$_storage[$key]))) {
            $_key = !empty(self::$_cached_keys[$key]['tag']) ? self::$_cached_keys[$key]['tag'] : $key;

            self::$_cache->set(
                $_key,
                $val,
                self::$_cached_keys[$key]['condition'],
                self::$_cached_keys[$key]['cache_level'],
                self::$_cached_keys[$key]['cache_ttl']
            );

            self::_updateHandlers($_key, self::$_cached_keys[$key]['condition'], self::$_cached_keys[$key]['cache_level']);
        }
    }

    /**
     * Saves tracked cached data and clears expired cache
     *
     * @return boolean true if data saved, false if no caches defined
     */
    public static function save()
    {
        /**
         * Hook is being executed before saving cache data to persistent storage and clearing expired cache.
         *
         * @param array $changed_tables List of DB tables that were modified at the runtime.
         * @param array $cached_keys List of registered cache keys.
         */
        fn_set_hook('registry_save_pre', self::$_changed_tables, self::$_cached_keys);

        if (empty(self::$_cache)) {
            return false;
        }

        foreach (self::$_cached_keys as $key => $arg) {
            if (isset(self::$_storage[$key]) && $arg['track'] == true) {
                self::_saveCache($key, self::$_storage[$key]);
            }
        }
        self::$_cached_keys = array();

        if (self::$_cache_handlers_are_updated == true) {
            self::saveCacheHandlers(self::$_cache_handlers);
            self::$_cache_handlers_are_updated = false;
        }

        // Get tags to clear expired cache
        if (!empty(self::$_changed_tables)) {
            $cache_handlers = self::getCacheHandlers(array_keys(self::$_changed_tables));
            $tags = array();
            foreach (self::$_changed_tables as $table => $flag) {
                if (!empty($cache_handlers[$table])) {
                    $tags = array_merge($tags, array_keys($cache_handlers[$table]));
                }
            }

            foreach ($tags as $tag) {
                self::del($tag);
            }

            self::$_cache->clear($tags);
            self::$_changed_tables = array();
        }

        return true;
    }

    /**
     * Cleans up cache data
     *
     * @return boolean always true
     */
    public static function cleanup()
    {
        if (empty(self::$_cache)) {
            self::cacheInit();
        }
        self::$_cache_handlers = array();

        // remove information about cache handlers
        self::cleanCacheHandlers(array('full' => true));

        // remove all the cache
        return self::$_cache->cleanup();
    }


    public static function clearCachedKeyValues()
    {
        foreach (self::$_cached_keys as $key => $definition) {
            self::del($key);
        }
    }

    /**
     * Generates cache level value for key
     *
     * @param string|string[] $id Cache level name
     *
     * @return string Cache level value
     */
    public static function cacheLevel($id)
    {
        if (is_array($id)) {
            return implode('__', array_map(static function ($id) {
                return self::cacheLevel($id);
            }, $id));
        }

        if (!isset(self::$_cache_levels[$id])) {
            $usergroups_condition = '';
            if (Tygh::$app->hasInstance('session') && !empty(Tygh::$app['session']['auth']['usergroup_ids'])) {
                $usergroups_condition = implode('_', Tygh::$app['session']['auth']['usergroup_ids']);
            }

            if ($id === 'time') {
                $key = 'time';
            } elseif ($id === 'static') {
                $key = 'cache_' . ACCOUNT_TYPE;
            } elseif ($id === 'day') {
                $key = date('z', TIME);
            } elseif ($id === 'company') {
                $key = fn_get_runtime_company_id();
            } elseif ($id === 'storefront') {
                $key = Registry::get('runtime.storefront_id');

                if (!$key) {
                    $storefront = StorefrontProvider::getStorefront();
                    $key = $storefront->storefront_id;
                }
            } elseif ($id === 'lang') {
                $key = CART_LANGUAGE;
            } elseif ($id === 'currency') {
                $key = defined('CART_SECONDARY_CURRENCY') ? CART_SECONDARY_CURRENCY : '_';
            } elseif ($id === 'locale') {
                $key = (defined('CART_LOCALIZATION') ? (CART_LOCALIZATION . '_') : '')
                    . CART_LANGUAGE . '_' . CART_SECONDARY_CURRENCY;
            } elseif ($id === 'dispatch') {
                $key = AREA . '_' . $_SERVER['REQUEST_METHOD'] . '_' . str_replace('.', '_', $_REQUEST['dispatch'])
                    . '_' . (defined('CART_LOCALIZATION') ? (CART_LOCALIZATION . '_') : '')
                    . CART_LANGUAGE . '_' . CART_SECONDARY_CURRENCY;
            } elseif ($id === 'user') {
                $key = AREA . '_' . $_SERVER['REQUEST_METHOD'] . '_' . str_replace('.', '_', $_REQUEST['dispatch'])
                    . '.' . $usergroups_condition
                    . '.' . (defined('CART_LOCALIZATION') ? (CART_LOCALIZATION . '_') : '')
                    . CART_LANGUAGE . '.' . CART_SECONDARY_CURRENCY;
            } elseif ($id === 'locale_auth') {
                $key = AREA . '_' . $_SERVER['REQUEST_METHOD'] . '_' . (Tygh::$app->hasInstance('session') && !empty(Tygh::$app['session']['auth']['user_id']) ? 1 : 0)
                    . '.' . $usergroups_condition
                    . (defined('CART_LOCALIZATION') ? (CART_LOCALIZATION . '_') : '')
                    . CART_LANGUAGE . '.' . CART_SECONDARY_CURRENCY;
            } elseif ($id === 'html_blocks') {
                $promotion_condition = '';
                if (Tygh::$app->hasInstance('session') && !empty(Tygh::$app['session']['auth']['user_id'])) {
                    $active_promotions = db_get_fields(
                        "SELECT promotion_id FROM ?:promotions"
                        . " WHERE status = 'A' AND zone = 'catalog' AND users_conditions_hash LIKE ?l",
                        "%," . \Tygh::$app['session']['auth']['user_id'] . ",%"
                    );
                    if (!empty($active_promotions)) {
                        $promotion_condition = \Tygh::$app['session']['auth']['user_id'];
                    }
                }
                $https_condition = defined('HTTPS') ? '__https' : '';
                $host_condition = REAL_HOST;

                $key = (defined('CART_LOCALIZATION') ? (CART_LOCALIZATION . '__') : '') . CART_LANGUAGE
                    . '__' . self::cacheLevel('day') . '__'
                    . $usergroups_condition
                    . '__' . $promotion_condition . $https_condition . $host_condition;
            }

            if (!isset($key)) {
                DeveloperException::undefinedCacheLevel($id);
            }

            self::$_cache_levels[$id] = $key;
        }

        return self::$_cache_levels[$id];
    }

    /**
     * Clears defined cache levels to redefine them again later
     */
    public static function clearCacheLevels()
    {
        self::$_cache_levels = array();
    }

    public static function setAppInstance(Application $application)
    {
        self::$application = $application;
    }

    /**
     * Saves cache handlers in the DB.
     *
     * @param array $cache_handlers         Array of cache handlers to store.
     *                                      Has the same format as self::$_cache_handlers:
     *                                      <code>
     *                                      [
     *                                          'table_name_1' => [
     *                                              'cache_key_1' => true,
     *                                              ...
     *                                              'cache_key_n' => true,
     *                                          ],
     *                                          ...
     *                                          'table_name_n' => [
     *                                              ...
     *                                          ]
     *                                      ];
     *                                      </code>
     *
     * @return bool Always true
     */
    public static function saveCacheHandlers(array $cache_handlers = [])
    {
        if (!$cache_handlers) {
            return true;
        }

        /** @var \Tygh\Database\Connection $db */
        $db = self::$application['db'];
        $data = [];
        ksort($cache_handlers);

        foreach ($cache_handlers as $table_name => $handlers) {
            if (empty($handlers)) {
                continue;
            }

            foreach (array_keys($handlers) as $cache_key) {
                $data[] = [
                    'table_name' => $table_name,
                    'cache_key'  => $cache_key
                ];
            }
        }

        if (empty($data)) {
            return true;
        }

        // disables hooks processing when performing a query
        $db->raw = true;
        $db->replaceInto(self::CACHE_HANDLERS_TABLE, $data, true);

        return true;
    }

    /**
     * Removes cache handlers from the DB.
     *
     * @param array $params         Parameters to remove cache handers
     *                              Has the following format:
     *                              <code>
     *                              [
     *                                  // removes all stored cache handlers
     *                                  'full' => true,
     *                                  // removes stored cache handlers for the specified tables
     *                                  'table_names' => [
     *                                      'table_name_1', ..., 'table_name_n'
     *                                  ],
     *                                  // removes stored cache handlers for the specified keys
     *                                  'cache_keys' => [
     *                                      'cache_key_1', ..., 'cache_key_n'
     *                                  ]
     *                              ]
     *                              </code>
     *
     * @return bool Always true
     */
    public static function cleanCacheHandlers(array $params)
    {
        $condition = array();

        if (!empty($params['full'])) {
            $condition[] = '1';
        } else {
            if (!empty($params['table_names'])) {
                $condition[] = self::$application['db']->quote('table_name IN (?a)', $params['table_names']);
            }

            if (!empty($params['cache_keys'])) {
                $condition[] = self::$application['db']->quote('cache_key IN (?a)', $params['cache_keys']);
            }
        }

        if ($condition) {
            self::$application['db']->raw = true;
            self::$application['db']->query(
                'DELETE FROM ?:?f WHERE ?p',
                self::CACHE_HANDLERS_TABLE,
                implode(' OR ', $condition)
            );
        }

        return true;
    }

    /**
     * Gets cache handlers that relate to the specified tables.
     *
     * @param array $tables Tables to get cache handlers for
     *
     * @return array Array of cache handlers.
     *               Has the same format as self::$_cache_handlers
     *
     */
    protected static function getCacheHandlers($tables = array())
    {
        $condition = $tables ? self::$application['db']->quote('AND table_name IN (?a)', $tables) : '';

        self::$application['db']->raw = true;
        return self::$application['db']->getMultiHash(
            'SELECT table_name, cache_key, 1 AS field_value'
            . ' FROM ?:?f'
            . ' WHERE 1 ?p',
            array('table_name', 'cache_key', 'field_value'),
            self::CACHE_HANDLERS_TABLE,
            $condition
        );
    }

    /**
     * Method combines both set() and get() methods to retrieve value identified by a $key, or to store the result of $callable execution if there is no cache available for the $key.
     *
     * @param string|string[]                                      $key         Key name. Array with 2 values can be passed: first - key name, second - key alias.
     * @param string[]|array{update_handlers: string[], ttl?: int} $conditions  Cache reset condition - array with table names.
     * @param string|string[]                                      $cache_level Indicates the cache dependencies on controller, language, user group, etc.
     * @param \Closure                                             $callable    The callable or closure that will be used to generate a value to be cached.
     * @param bool                                                 $remove_key  Remove key from runtime storage
     *
     * @return mixed
     * @throws DeveloperException If an erroneus error key is detected in development mode.
     */
    public static function getOrSetCache($key, $conditions, $cache_level, Closure $callable, $remove_key = true)
    {
        if (is_array($key)) {
            list(, $cache_key) = $key;
        } else {
            $cache_key = $key;
        }
        
        if (defined('DEVELOPMENT') && !preg_match('~^[a-zA-Z0-9_-]*$~', $cache_key)) {
            throw new DeveloperException('Invalid cache key "' . $cache_key . '" used. ');
        }

        Registry::registerCache($key, $conditions, Registry::cacheLevel($cache_level));

        if (Registry::isExist($cache_key) === false) {
            Registry::set($cache_key, $callable());
        }

        $result = Registry::get($cache_key);

        if ($remove_key) {
            Registry::del($cache_key);
        }

        return $result;
    }
}
