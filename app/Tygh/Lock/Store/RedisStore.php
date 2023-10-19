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

namespace Tygh\Lock\Store;

use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Store\RedisStore as BaseRedisStore;
use Tygh\Lock\StoreInterface;

/**
 * RedisStore
 *
 * @package Tygh\Lock\Store
 */
class RedisStore extends BaseRedisStore implements StoreInterface
{
    /**
     * @var \Predis\Client|\Redis|\RedisArray|\RedisCluster|\Tygh\Lock\Store\RedisProxy
     */
    protected $redis;

    /**
     * @param \Redis|\RedisArray|\RedisCluster|\Predis\Client $redis_client
     * @param float                                           $initial_ttl the expiration delay of locks in seconds
     */
    public function __construct($redis_client, $initial_ttl = 300.0)
    {
        parent::__construct($redis_client, $initial_ttl);

        $this->redis = $redis_client;
    }

    /**
     * @inheritDoc
     */
    public function exists(Key $key, $owned_to_current_process = true)
    {
        if ($owned_to_current_process) {
            return parent::exists($key);
        } else {
            return $this->redis->get((string) $key) !== false;
        }
    }
}