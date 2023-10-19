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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'place_order') {
    // This workaround is used to save current payment intent ID when order is updated with no payment data
    if (
        isset($_REQUEST['payment_info']['stripe.payment_intent_id'])
        && empty($_REQUEST['payment_info']['stripe.payment_intent_id'])
    ) {
        unset($_REQUEST['payment_info']['stripe.payment_intent_id']);
    }
}
