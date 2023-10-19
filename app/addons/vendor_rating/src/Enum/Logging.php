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

namespace Tygh\Addons\VendorRating\Enum;

/**
 * Class Logging contains logging-specific constants and methods.
 *
 * @package Tygh\Addons\VendorRating\Enum
 */
class Logging
{
    const LOG_TYPE_VENDOR_RATING = 'vendor_rating';
    const ACTION_SUCCESS = 'vr_success';
    const ACTION_FAILURE = 'vr_failure';

    public static function getActions()
    {
        return [
            self::ACTION_SUCCESS,
            self::ACTION_FAILURE,
        ];
    }
}
