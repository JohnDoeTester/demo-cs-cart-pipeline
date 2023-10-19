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



namespace Tygh\Lock;

use Symfony\Component\Lock\Exception\InvalidArgumentException;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Exception\LockExpiredException;
use Symfony\Component\Lock\Exception\LockReleasingException;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\LockInterface;
use Exception;

/**
 * Lock is implementation of the LockInterface.
 *
 * @package Tygh\Lock
 */
class Lock implements LockInterface
{
    /**
     * @var \Tygh\Lock\StoreInterface
     */
    protected $store;

    /**
     * @var \Symfony\Component\Lock\Key
     */
    protected $key;

    /**
     * @var float|null
     */
    protected $ttl;

    /**
     * @var bool
     */
    private $auto_release;

    /**
     * @var bool
     */
    private $dirty = false;

    /**
     * @param \Symfony\Component\Lock\Key $key          Resource to lock
     * @param StoreInterface              $store        Store used to handle lock persistence
     * @param float|null                  $ttl          Maximum expected lock duration in seconds
     * @param bool                        $auto_release Whether to automatically release the lock or not when the lock
     *                                                 instance is destroyed
     */
    public function __construct(Key $key, StoreInterface $store, $ttl = null, $auto_release = true)
    {
        $this->store = $store;
        $this->key = $key;
        $this->ttl = $ttl;
        $this->auto_release = (bool) $auto_release;
    }

    /**
     * Automatically releases the underlying lock when the object is destructed.
     */
    public function __destruct()
    {
        if (!$this->auto_release || !$this->dirty || !$this->isAcquired()) {
            return;
        }

        $this->release();
    }

    /**
     * {@inheritdoc}
     */
    public function acquire($blocking = false)
    {
        try {
            if (!$blocking) {
                $this->store->save($this->key);
            } else {
                $this->store->waitAndSave($this->key);
            }

            $this->dirty = true;

            if ($this->ttl) {
                $this->refresh();
            }

            if ($this->key->isExpired()) {
                throw new LockExpiredException(sprintf('Failed to store the "%s" lock.', $this->key));
            }

            return true;
        } catch (LockConflictedException $e) {
            $this->dirty = false;

            if ($blocking) {
                throw $e;
            }

            return false;
        } catch (\Exception $e) {
            throw new LockAcquiringException(sprintf('Failed to acquire the "%s" lock.', $this->key), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refresh()
    {
        if (!$this->ttl) {
            throw new InvalidArgumentException('You have to define an expiration duration.');
        }

        try {
            $this->key->resetLifetime();
            $this->store->putOffExpiration($this->key, $this->ttl);
            $this->dirty = true;

            if ($this->key->isExpired()) {
                throw new LockExpiredException(sprintf('Failed to put off the expiration of the "%s" lock within the specified time.', $this->key));
            }
        } catch (LockConflictedException $e) {
            $this->dirty = false;
            throw $e;
        } catch (\Exception $e) {
            throw new LockAcquiringException(sprintf('Failed to define an expiration for the "%s" lock.', $this->key), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAcquired()
    {
        return $this->dirty = $this->store->exists($this->key);
    }

    /**
     * {@inheritdoc}
     */
    public function release()
    {
        $this->store->delete($this->key);
        $this->dirty = false;

        if ($this->store->exists($this->key)) {
            throw new LockReleasingException(sprintf('Failed to release the "%s" lock.', $this->key));
        }
    }

    /**
     * Waits until a key becomes free
     *
     * @param int $retry_sleep Duration in ms between 2 retry
     * @param int $retry_count Maximum amount of retry
     *
     * @return bool
     */
    public function wait($retry_sleep = 100, $retry_count = PHP_INT_MAX)
    {
        $retry = 0;
        $sleep_randomness = (int) ($retry_sleep / 10);

        do {
            if (!$this->store->exists($this->key, false)) {
                return true;
            }

            try {
                $random_int = random_int(-$sleep_randomness, $sleep_randomness);
            } catch (Exception $exception) {
                $random_int = rand(-$sleep_randomness, $sleep_randomness);
            }

            usleep(($retry_sleep + $random_int) * 1000);
        } while (++$retry < $retry_count);

        return true;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return $this->key->isExpired();
    }

    /**
     * Returns the remaining lifetime.
     *
     * @return float|null Remaining lifetime in seconds. Null when the lock won't expire.
     */
    public function getRemainingLifetime()
    {
        return $this->key->getRemainingLifetime();
    }
}
