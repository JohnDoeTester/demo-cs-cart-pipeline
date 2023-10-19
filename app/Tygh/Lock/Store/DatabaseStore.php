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


use Symfony\Component\Lock\Exception\InvalidArgumentException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Exception\LockExpiredException;
use Symfony\Component\Lock\Exception\NotSupportedException;
use Symfony\Component\Lock\Key;
use Tygh\Database\Connection;
use Tygh\Exceptions\DatabaseException;
use Tygh\Lock\StoreInterface;
use Exception;

/**
 * DatabaseStore is a StoreInterface implementation using a database connection.
 *
 * @package Tygh\Backend\Lock
 */
class DatabaseStore implements StoreInterface
{
    /** @var Connection */
    protected $connection;

    /** @var int */
    protected $initial_ttl;

    /** @var float */
    protected $gc_probability;

    /**
     * DatabaseStore constructor.
     *
     * @param Connection $connection        Database connection
     * @param float      $gc_probability    Probability expressed as floating number between 0 and 1 to clean old locks
     * @param int        $initial_ttl       The expiration delay of locks in seconds
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Connection $connection, $gc_probability = 0.01, $initial_ttl = 300)
    {
        if ($gc_probability < 0 || $gc_probability > 1) {
            throw new InvalidArgumentException(sprintf('"%s" requires gcProbability between 0 and 1, "%f" given.', __METHOD__, $gc_probability));
        }

        if ($initial_ttl < 1) {
            throw new InvalidArgumentException(sprintf('%s() expects a strictly positive TTL. Got %d.', __METHOD__, $initial_ttl));
        }

        $this->connection = $connection;
        $this->gc_probability = $gc_probability;
        $this->initial_ttl = $initial_ttl;
    }

    /**
     * @inheritDoc
     */
    public function save(Key $key)
    {
        $key->reduceLifetime($this->initial_ttl);

        try {
            $this->query(
                'INSERT INTO ?:lock_keys (key_id, token, expiry_at) VALUES (?s, ?s, UNIX_TIMESTAMP(NOW()) + ?i)',
                $this->getHashedKey($key), $this->getToken($key), $this->initial_ttl
            );
        } catch (DatabaseException $exception) {
            // the lock is already acquired. It could be us. Let's try to put off.
            $this->putOffExpiration($key, $this->initial_ttl);
        }

        if ($key->isExpired()) {
            throw new LockExpiredException(sprintf('Failed to store the "%s" lock.', $key));
        }

        try {
            $random_int = random_int(0, PHP_INT_MAX);
        } catch (Exception $exception) {
            $random_int = rand(0, PHP_INT_MAX);
        }

        if ($this->gc_probability > 0 && (1.0 === $this->gc_probability || ($random_int / PHP_INT_MAX) <= $this->gc_probability)) {
            $this->prune();
        }
    }

    /**
     * @inheritDoc
     */
    public function waitAndSave(Key $key)
    {
        throw new NotSupportedException(sprintf('The store "%s" does not supports blocking locks.', __METHOD__));
    }

    /**
     * @inheritDoc
     */
    public function putOffExpiration(Key $key, $ttl)
    {
        if ($ttl < 1) {
            throw new InvalidArgumentException(sprintf('%s() expects a TTL greater or equals to 1 second. Got %s.', __METHOD__, $ttl));
        }

        $key->reduceLifetime($ttl);
        $token = $this->getToken($key);

        $result = $this->query(
            'UPDATE ?:lock_keys SET expiry_at = UNIX_TIMESTAMP(NOW()) + ?i, token = ?s'
            . ' WHERE key_id = ?s AND (token = ?s OR expiry_at <= UNIX_TIMESTAMP(NOW()))',
            $ttl, $token, $this->getHashedKey($key), $token
        );

        if (!$result && !$this->exists($key)) {
            throw new LockConflictedException();
        }

        if ($key->isExpired()) {
            throw new LockExpiredException(sprintf('Failed to put off the expiration of the "%s" lock within the specified time.', $key));
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(Key $key)
    {
        $this->query(
            'DELETE FROM ?:lock_keys WHERE key_id = ?s AND token = ?s',
            $this->getHashedKey($key),
            $this->getToken($key)
        );
    }

    /**
     * @inheritDoc
     */
    public function exists(Key $key, $owned_to_current_process = true)
    {
        $this->connection->raw = true;

        if ($owned_to_current_process) {
            return (bool) $this->connection->getField(
                'SELECT 1 FROM ?:lock_keys WHERE key_id = ?s AND token = ?s AND expiry_at > UNIX_TIMESTAMP(NOW())',
                $this->getHashedKey($key),
                $this->getToken($key)
            );
        } else {
            return (bool) $this->connection->getField(
                'SELECT 1 FROM ?:lock_keys WHERE key_id = ?s AND expiry_at > UNIX_TIMESTAMP(NOW())',
                $this->getHashedKey($key)
            );
        }
    }

    /**
     * Cleanups the table by removing all expired locks.
     */
    protected function prune()
    {
        $this->connection->raw = true;
        $this->connection->query('DELETE FROM ?:lock_keys WHERE expiry_at <= UNIX_TIMESTAMP(NOW())');
    }

    /**
     * Gets process token
     *
     * @param Key $key
     *
     * @return string
     */
    protected function getToken(Key $key)
    {
        if (!$key->hasState(__CLASS__)) {
            try {
                $token = base64_encode(random_bytes(32));
            } catch (Exception $exception) {
                $token = uniqid(uniqid(rand(0, 100000), true), true);
            }

            $key->setState(__CLASS__, $token);
        }

        return $key->getState(__CLASS__);
    }

    /**
     * Returns an hashed version of the key.
     *
     * @param Key $key
     *
     * @return string
     */
    protected function getHashedKey(Key $key)
    {
        return hash('sha256', $key);
    }

    /**
     * Executes sql query
     *
     * @param string $query
     * @param mixed ...$params
     *
     * @return mixed
     * @throws \Tygh\Exceptions\DatabaseException
     * @throws \Tygh\Exceptions\DeveloperException
     */
    protected function query($query, ...$params)
    {
        $this->connection->raw = true;
        $this->connection->log_error = false;

        try {
            $result = $this->connection->query($query, ...$params);
        } finally {
            $this->connection->log_error = true;
        }

        return $result;
    }
}