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

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Registry;

if (($mode == 'update' || $mode == 'delete')
    && !empty($_REQUEST['promotion_id'])
) {
    $vendor_id = Registry::get('runtime.company_id');

    if (!fn_direct_payments_check_promotion_owner($vendor_id, $_REQUEST['promotion_id'])) {
        
        return array(CONTROLLER_STATUS_DENIED);
    }
}