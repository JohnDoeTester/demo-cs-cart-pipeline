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

/** @var string $mode */

if ($mode === 'bulk_print_pdf' && !empty($_REQUEST['order_ids'])) {
    echo(fn_print_order_invoices($_REQUEST['order_ids'], ['pdf' => true]));

    return [CONTROLLER_STATUS_NO_CONTENT];
}

if ($mode === 'packing_slip_pdf' && !empty($_REQUEST['order_ids'])) {
    echo(fn_print_order_packing_slips($_REQUEST['order_ids'], ['pdf' => true]));

    return [CONTROLLER_STATUS_NO_CONTENT];
}
