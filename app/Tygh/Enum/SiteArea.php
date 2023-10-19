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
 * SiteArea contains possible site area values.
 *
 * @see AREA
 *
 * @package Tygh\Enum
 */
class SiteArea
{
    const STOREFRONT = 'C';
    const VENDOR_PANEL = 'V';
    const ADMIN_PANEL = 'A';

    /**
     * @param string $area Area
     *
     * @return bool
     */
    public static function isStorefront($area)
    {
        return $area === self::STOREFRONT;
    }

    /**
     * @param string $area Area
     *
     * @return bool
     */
    public static function isAdmin($area)
    {
        return $area === self::ADMIN_PANEL;
    }

    /**
     * @param string $area Area
     *
     * @return bool
     */
    public static function isVendor($area)
    {
        return $area === self::VENDOR_PANEL;
    }
}
