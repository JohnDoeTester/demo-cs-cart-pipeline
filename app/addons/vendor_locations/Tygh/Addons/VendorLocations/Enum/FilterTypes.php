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

namespace Tygh\Addons\VendorLocations\Enum;

/**
 * Class FilterTypes
 * Describes types of filters by geolocation
 *
 * @package Tygh\Addons\VendorLocations\Enum
 */
class FilterTypes
{
    const REGION = 'R';
    const ZONE = 'Z';

    /**
     * @return array
     */
    public static function all()
    {
        return array(self::REGION, self::ZONE);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function has($type)
    {
        return in_array($type, self::all(), true);
    }
}
