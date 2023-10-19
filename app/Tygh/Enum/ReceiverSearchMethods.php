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
 * Class ReceiverSearchMethods contains possible search methods for Notifications center.
 *
 * @package Tygh\Enum
 */
class ReceiverSearchMethods
{
    const USER_ID = 'user_id';
    const USERGROUP_ID = 'usergroup_id';
    const EMAIL = 'email';
    const ORDER_MANAGER = 'order_manager';
    const VENDOR_OWNER = 'vendor_owner';
    const VENDOR_EMAIL = 'vendor_email';
}
