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
use Symfony\Component\Lock\Store\RetryTillSaveStore as BaseRetryTillSaveStore;
use Tygh\Lock\StoreInterface;

/**
 * Class RetryTillSaveStore
 *
 * @package Tygh\Lock\Store
 */
class RetryTillSaveStore extends BaseRetryTillSaveStore implements StoreInterface
{
    /**
     * @var StoreInterface
     */
    protected $decorated;

    /**
     * @param StoreInterface $decorated   The decorated StoreInterface
     * @param int            $retry_sleep Duration in ms between 2 retry
     * @param int            $retry_count Maximum amount of retry
     */
    public function __construct(StoreInterface $decorated, $retry_sleep = 100, $retry_count = PHP_INT_MAX)
    {
        parent::__construct($decorated, $retry_sleep, $retry_count);

        $this->decorated = $decorated;
    }

    /**
     * @inheritDoc
     */
    public function exists(Key $key, $owned_to_current_process = true)
    {
        return $this->decorated->exists($key, $owned_to_current_process);
    }
}