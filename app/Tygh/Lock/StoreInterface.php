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
use Symfony\Component\Lock\StoreInterface as BaseStoreInterface;

/**
 * Extends StoreInterface
 *
 * @package Tygh\Lock
 */
interface StoreInterface extends BaseStoreInterface
{
    /**
     * Returns whether or not the resource exists in the storage.
     *
     * @param \Symfony\Component\Lock\Key $key                      Key
     * @param bool                        $owned_to_current_process Whether to check if key owned to current process
     *
     * @return bool
     */
    public function exists(Key $key, $owned_to_current_process = true);
}