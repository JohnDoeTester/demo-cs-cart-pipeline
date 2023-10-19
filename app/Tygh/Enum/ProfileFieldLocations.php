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
 *  ProfileFieldLocations contains possible location values for function fn_get_profile_fields.
 *
 * @package Tygh\Enum
 */
class ProfileFieldLocations
{
    const CUSTOMER_FIELDS = 'C';
    const CHECKOUT_FIELDS = 'O';
    const ADMIN_FIELDS = 'A';
    const VENDOR_FIELDS = 'V';
    const EXTRA_FIELDS = 'I';
}
