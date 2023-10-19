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

namespace Tygh\Enum;

/**
 *  UserTypes contains possible values for `users`.`user_type` DB field.
 *
 * @package Tygh\Enum
 */
class UserTypes
{
    const ADMIN = 'A';
    const CUSTOMER = 'C';
    const VENDOR = 'V';

    /**
     * @param string $user_type User type
     *
     * @return bool
     */
    public static function isVendor($user_type)
    {
        return $user_type === self::VENDOR;
    }

    /**
     * @param string $user_type User type
     *
     * @return bool
     */
    public static function isAdmin($user_type)
    {
        return $user_type === self::ADMIN;
    }

    /**
     * @param string $user_type User type
     *
     * @return bool
     */
    public static function isCustomer($user_type)
    {
        return $user_type === self::CUSTOMER;
    }
}
