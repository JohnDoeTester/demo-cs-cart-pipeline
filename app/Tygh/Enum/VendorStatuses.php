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

class VendorStatuses
{
    const ACTIVE = 'A';
    const PENDING = 'P';
    const DISABLED = 'D';
    const NEW_ACCOUNT = 'N';
    const SUSPENDED = 'S';

    /**
     * Gets all statuses, which can be set to (except NEW status)
     *
     * @return array<string>
     */
    public static function getStatusesTo()
    {
        return self::getList([VendorStatuses::NEW_ACCOUNT]);
    }

    /**
     * Gets all vendor statuses
     *
     * @param array<string> $exclude List of type codes of vendor statuses to be excluded
     *
     * @return array<string>
     */
    public static function getList(array $exclude = [])
    {
        $statuses = [
            self::ACTIVE,
            self::PENDING,
            self::DISABLED,
            self::SUSPENDED,
            self::NEW_ACCOUNT,
        ];

        return array_filter($statuses, static function ($status_code) use ($exclude) {
            return !in_array($status_code, $exclude);
        });
    }
}
