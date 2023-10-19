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

use Symfony\Component\Lock\Key;

/**
 * @package Tygh\Lock
 */
class Factory
{
    /**
     * @var \Tygh\Lock\StoreInterface
     */
    protected $store;

    /**
     * Factory constructor.
     *
     * @param \Tygh\Lock\StoreInterface $store
     */
    public function __construct(StoreInterface $store)
    {
        $this->store = $store;
    }

    /**
     * @inheritdoc
     */
    public function createLock($resource, $ttl = 30.0, $auto_release = true)
    {
        return new Lock(new Key($resource), $this->store, $ttl, $auto_release);
    }
}