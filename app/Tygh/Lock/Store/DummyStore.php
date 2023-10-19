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
use Tygh\Lock\StoreInterface;

/**
 * DummyStore is a dummy store.
 * Usable to disable locks mechanism.
 *
 * @package Tygh\Backend\Lock
 */
class DummyStore implements StoreInterface
{
    /**
     * @inheritDoc
     */
    public function save(Key $key)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function waitAndSave(Key $key)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function putOffExpiration(Key $key, $ttl)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(Key $key)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function exists(Key $key, $owned_to_current_process = true)
    {
        return false;
    }
}