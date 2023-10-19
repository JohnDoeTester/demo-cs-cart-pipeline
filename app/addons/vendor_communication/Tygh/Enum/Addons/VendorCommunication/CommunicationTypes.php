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

namespace Tygh\Enum\Addons\VendorCommunication;

/**
 * Class CommunicationTypes
 * Describes types of communications
 *
 * @package Tygh\Addons\VendorCommunication\Enum
 */
class CommunicationTypes
{
    const VENDOR_TO_CUSTOMER = 'vendor_to_customer';
    const VENDOR_TO_ADMIN = 'vendor_to_admin';

    /**
     * @return array
     */
    public static function all()
    {
        $types = [];
        $types[] = self::VENDOR_TO_CUSTOMER;
        if (fn_allowed_for('MULTIVENDOR')) {
            $types[] = self::VENDOR_TO_ADMIN;
        }
        return $types;
    }
}
