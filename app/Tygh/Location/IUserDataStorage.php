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

namespace Tygh\Location;

/**
 * Interface IUserDataStorage describes an interface of the user data storage object for the customer location manager.
 *
 * @see \Tygh\Location\Manager
 *
 * @package Tygh\Location
 */
interface IUserDataStorage
{
    /**
     * Gets storage item value.
     *
     * @param string|int $key
     *
     * @return mixed
     */
    public function get($key);

    /**
     * Gets all values from storage.
     *
     * @return array
     */
    public function getAll();

    /**
     * Sets storage item value.
     *
     * @param string|int $key
     * @param mixed      $value
     */
    public function set($key, $value);

    /**
     * Deletes storage item.
     *
     * @param string|int $key
     */
    public function delete($key);
}
