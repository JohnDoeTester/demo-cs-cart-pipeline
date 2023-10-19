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

namespace Tygh\Backend\Session;

use RedisException;
use Tygh\Debugger;
use Tygh\Exceptions\DatabaseException;

/**
 * Class Redis
 *
 * @package Tygh\Backend\Session
 */
class Redis extends ABackend
{
    /**
     * @var Redis
     */
    private $r;

    /**
     * @var int Max reconnect attempts
     */
    private $max_reconnects = 5;

    /**
     * @var int Current reconnect attempts
     */
    private $reconnects = 0;

    /**
     * @var int Sleep between reconnects
     */
    private $sleep = 200000; // 100 ms

    /**
     * @var string Connection host
     */
    private $host;

    /**
     * @var int Connection port
     */
    private $port = 6379;

    /**
     * @inheritDoc
     */
    public function __construct(array $config, array $params = [])
    {
        parent::__construct($config, $params);

        $this->config = fn_array_merge([
            'redis_server' => $config['session_redis_server'],
            'store_prefix' => !empty($config['store_prefix']) ? $config['store_prefix'] : null,
        ], $this->config);

        $this->host = $config['session_redis_server'];

        if (strncmp($this->host, '/', 1) === 0) {
            $this->port = null;
        } else {
            $parsed = parse_url($this->host);

            if ($parsed && isset($parsed['host'], $parsed['port'])) {
                $this->host = $parsed['host'];
                $this->port = $parsed['port'];
            }
        }

        $this->connect();
    }

    /**
     * @inheritDoc
     */
    public function read($sess_id)
    {
        $session = $this->query('hGetAll', $this->id($sess_id));

        if (empty($session)) {
            return false;
        }

        return $session['data'];
    }

    /**
     * @inheritDoc
     */
    public function write($sess_id, $data)
    {
        $this->query('hmSet', $this->id($sess_id), $data);
        $this->query('expire', $this->id($sess_id), $this->config['ttl']);

        $this->query('set', $this->id($sess_id, 'online:'), 1);
        $this->query('expire', $this->id($sess_id, 'online:'), $this->config['ttl_online']);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function regenerate($old_id, $new_id)
    {
        $this->query('rename', $this->id($old_id), $this->id($new_id));
        $this->query('rename', $this->id($old_id, 'online:'), $this->id($new_id, 'online:'));

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete($sess_id)
    {
        $this->query('del', $this->id($sess_id));

        return true;
    }

    /**
     * @inheritDoc
     */
    public function gc($max_lifetime)
    {
        return true;
    }

    /**
     * Gets sessions that were used less than number of seconds, defined in SESSION_ONLINE constant
     *
     * @param string $area Session area
     *
     * @return array<string> List of session IDs
     */
    public function getOnline($area)
    {
        $keys = $this->query('keys', $this->id('*_' . $area, 'online:'));

        return array_map(static function ($key) {
            return (string) substr($key, strrpos($key, ':') + 1);
        }, $keys);
    }

    /**
     * Generates prefix for session id to separate sessions with same ID but from different stores
     *
     * @param string $sess_id Session ID
     * @param string $prefix  Key prefix
     *
     * @return string prefixed session ID
     */
    protected function id($sess_id, $prefix = '')
    {
        return $prefix . 'session:' . (!empty($this->config['store_prefix']) ? $this->config['store_prefix'] . ':' : '') . $sess_id;
    }

    /**
     * Connects to the Redis server
     *
     * @return bool Returns true on success, false - otherwise
     */
    protected function connect()
    {
        $this->r = new \Redis();

        Debugger::checkpoint('Session: before redis connect');

        if ($this->r->connect($this->host, $this->port) === true) {
            $this->r->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
            Debugger::checkpoint('Session: after redis connect');

            return true;
        }

        return false;
    }

    /**
     * Queries Redis server and handle reconnects if case of failure
     *
     * @return mixed
     *
     * @throws \Tygh\Exceptions\DatabaseException If the number of attempts to complete the query is exceeded.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     */
    protected function query()
    {
        $args = func_get_args();
        $cmd = array_shift($args);

        try {
            return call_user_func_array([$this->r, $cmd], $args);
        } catch (RedisException $e) {
            if ($this->reconnects < $this->max_reconnects) {
                $this->reconnects++;
                usleep($this->sleep);
                $this->connect();

                return call_user_func_array([$this, 'query'], func_get_args());
            }

            throw new DatabaseException('Sessions: can not connect to the Redis server');
        }
    }
}
