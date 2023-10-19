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

/*
 * Support for add-on "Vendor data premoderation".
 */

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'products_approval' && !empty($_REQUEST['approval_data'])) {
        fn_se_add_action('update', $_REQUEST['approval_data']['product_id']);

    } elseif (($mode == 'm_approve' || $mode == 'm_decline') && !empty($_REQUEST['product_ids'])) {
        fn_se_add_action('update', $_REQUEST['product_ids']);
    }
}
