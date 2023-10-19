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

namespace Tygh\Enum\Addons\StripeConnect;

use Stripe\Account;

class AccountTypes
{
    const STANDARD = 'S';
    const EXPRESS = 'E';

    /**
     * Converts Stripe account type value to the system representation.
     *
     * @param string $value Stripe account type value
     *
     * @return string
     */
    public static function toId($value)
    {
        if ($value === Account::TYPE_EXPRESS) {
            return self::EXPRESS;
        }

        return self::STANDARD;
    }

    /**
     * Checks if the account type is Express
     *
     * @param string $account_type Account type
     *
     * @return bool
     */
    public static function isExpress($account_type)
    {
        return $account_type === self::EXPRESS;
    }

    /**
     * Checks if the account type is Standard
     *
     * @param string $account_type Account type
     *
     * @return bool
     */
    public static function isStandard($account_type)
    {
        return $account_type === self::STANDARD;
    }
}
