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

namespace Tygh\Backend\Cache;

use Tygh\Registry;

/**
 * Cache backend class, implements 8 methods:
 */
abstract class ABackend
{
    /** @var int */
    protected $_company_id = 0;

    /** @var array */
    protected $_config = [];

    /**
     * Object constructor
     *
     * @param array $config configuration options
     */
    public function __construct($config)
    {
        $this->resetCompanyId();
    }

    /**
     * Resets company ID
     */
    public function resetCompanyId()
    {
        $this->_company_id = (int) Registry::get('runtime.company_id');
    }

    /**
     * Set data to the cache storage
     *
     * @param string      $name
     * @param mixed       $data
     * @param array|int   $condition
     * @param string|null $cache_level
     * @param int|null    $ttl
     */
    public function set($name, $data, $condition, $cache_level = null)
    {
        return false;
    }

    /**
     * Gets data from the cache storage
     *
     * @param string      $name
     * @param string|null $cache_level
     *
     * @return array|bool
     */
    public function get($name, $cache_level = null)
    {
        return false;
    }

    /**
     * Clears expired data
     *
     * @param $tags
     *
     * @return bool
     */
    public function clear($tags)
    {
        return false;
    }

    /**
     * Deletes all cached data
     *
     * @return mixed
     */
    public function cleanup()
    {
        return false;
    }

    /**
     * Gets expiry time for cache item
     *
     * @param int|array $condition
     * @param string    $cache_level
     * @param int       $default
     *
     * @return int
     */
    protected function getCacheExpiryTime($condition, $cache_level, $ttl, $default)
    {
        $ttl = $this->getCacheTimeToLive($condition, $cache_level, $ttl);

        if ($ttl === null) {
            return $default;
        }

        return $ttl + TIME;
    }

    /**
     * Gets time to live for cache item
     *
     * @param int|array $condition
     * @param string    $cache_level
     * @param int|null  $default
     *
     * @return int|null
     */
    protected function getCacheTimeToLive($condition, $cache_level, $default = null)
    {
        if ($cache_level === Registry::cacheLevel('time')) {
            return (int) $condition;
        }

        return $default;
    }
}
