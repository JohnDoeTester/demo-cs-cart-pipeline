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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reason = '';
    $product_ids = isset($_REQUEST['product_ids'])
        ? $_REQUEST['product_ids']
        : [];

    if (!$product_ids && $action) {
        $product_ids = [$action];
    }

    if (count($product_ids) === 1) {
        $declined_product_id = reset($product_ids);
        $reason = isset($_REQUEST['product_approval'][$declined_product_id]['reason'])
            ? $_REQUEST['product_approval'][$declined_product_id]['reason']
            : '';
    }

    if (!$reason && isset($_REQUEST['product_approval'][0]['reason'])) {
        $reason = $_REQUEST['product_approval'][0]['reason'];
    }

    if (($mode == 'm_approve' || $mode == 'm_decline') && $product_ids) {
        if ($mode == 'm_approve') {
            fn_vendor_data_premoderation_approve_products($product_ids, true);
        } else {
            fn_vendor_data_premoderation_disapprove_products($product_ids, true, $reason);
        }
    }
}
