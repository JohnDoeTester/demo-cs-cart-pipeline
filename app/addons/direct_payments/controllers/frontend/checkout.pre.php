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

/** @var \Tygh\Addons\DirectPayments\Cart\Service $cart_service */
$cart_service = Tygh::$app['addons.direct_payments.cart.service'];

if (isset($_REQUEST['vendor_id'])) {
    $cart_service->setCurrentVendorId((int) $_REQUEST['vendor_id']);
}

/**
 * Store current cart in the session to remove the need to override controllers from another add-ons
 */
$cart_service->loadSessionCart();

return [CONTROLLER_STATUS_OK];
