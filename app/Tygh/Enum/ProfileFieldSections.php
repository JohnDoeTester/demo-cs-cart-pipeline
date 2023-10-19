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

class ProfileFieldSections
{
    const ESSENTIALS = 'E';
    const CONTACT_INFORMATION = 'C';
    const BILLING_ADDRESS = 'B';
    const SHIPPING_ADDRESS = 'S';

    const STATUS_ACTIVE = 'A';
    const STATUS_DEPRECATED = 'R';

    // actually, is not a section, but is used on the profile field update page
    const BILLING_AND_SHIPPING_ADDRESS = 'BS';

    public static function getAll($lang_code = CART_LANGUAGE)
    {
        return [
            self::ESSENTIALS                   => '',
            self::CONTACT_INFORMATION          => __('contact_information', [], $lang_code),
            self::BILLING_ADDRESS              => __('billing_address', [], $lang_code),
            self::SHIPPING_ADDRESS             => __('shipping_address', [], $lang_code),
            self::BILLING_AND_SHIPPING_ADDRESS =>
                __('billing_address', [], $lang_code)
                . '/'
                . __('shipping_address', [], $lang_code),
        ];
    }
}