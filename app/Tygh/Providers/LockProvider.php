<?php


namespace Tygh\Providers;


use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Lock\Factory;
use Tygh\Lock\Store\RedisStore;
use Tygh\Lock\Store\RetryTillSaveStore;
use Redis;
use Tygh\Lock\Store\DatabaseStore;
use Tygh\Lock\Store\DummyStore;
use Tygh\Registry;

/**
 * The Lock component allows mutual execution of concurrent processes in order to prevent "race conditions".
 *
 * This is achieved by using a "lock" mechanism.
 * Each possibly concurrent thread cooperates by acquiring a lock before accessing the corresponding data.
 *
 * Usage example:
 *
 * ```
 * $lock = Tygh::$app['lock.factory']->createLock('pdf-invoice-generation');
 *
 * if ($lock->acquire()) {
 *      // The resource "pdf-invoice-generation" is locked.
 *      // You can compute and generate invoice safely here.
 *
 *      $lock->release();
 * }
 *
 * ```
 * @package Tygh\Providers
 */
class LockProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['lock.factory'] = function (Container $app) {
            $provider = Registry::ifGet('config.lock_backend', 'dummy');

            if (!isset($app['lock.provider.' . $provider])) {
                $provider = 'dummy';
            }

            return new Factory($app['lock.provider.' . $provider]);
        };

        $app['lock.provider.dummy'] = function (Container $app) {
            return new DummyStore();
        };

        $app['lock.provider.database'] = function (Container $app) {
            return new RetryTillSaveStore(
                new DatabaseStore($app['db'])
            );
        };

        $app['lock.provider.redis'] = function (Container $app) {
            return new RetryTillSaveStore(
                new RedisStore($app['lock.provider.redis.client'])
            );
        };

        $app['lock.provider.redis.client'] = function (Container $app) {
            $redis = new Redis();
            $host = Registry::ifGet('config.lock_redis_server', 'localhost');
            $password = Registry::ifGet('config.lock_redis_server_password', null);

            if (strncmp($host, '/', 1) === 0) {
                $port = null;
            } else {
                $parsed = parse_url($host);

                if ($parsed && isset($parsed['host'], $parsed['port'])) {
                    $host = $parsed['host'];
                    $port = $parsed['port'];
                }
            }

            $redis->connect($host, !empty($port) ? $port : 6379);

            if ($password) {
                $redis->auth($password);
            }

            $redis->setOption(Redis::OPT_PREFIX, 'cscart.lock.' . Registry::ifGet('store_prefix', ''));

            return $redis;
        };
    }
}