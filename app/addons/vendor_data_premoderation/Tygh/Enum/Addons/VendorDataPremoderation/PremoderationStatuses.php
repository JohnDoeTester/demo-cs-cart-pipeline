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

namespace Tygh\Enum\Addons\VendorDataPremoderation;

/**
 * Class PremoderationStatuses
 *
 * @deprecated since 4.11.1. Use specific approval methods and proper product statuses instead.
 *
 * @package    Tygh\Enum\Addons\VendorDataPremoderation
 *
 * @see        fn_vendor_data_premoderation_approve_products
 * @see        fn_vendor_data_premoderation_disapprove_products
 * @see        fn_vendor_data_premoderation_request_approval_for_products
 * @see        \Tygh\Enum\Addons\VendorDataPremoderation\ProductStatuses
 */
class PremoderationStatuses
{
    const APPROVED = 'Y';
    const DISAPPROVED = 'N';
    const PENDING = 'P';
}
