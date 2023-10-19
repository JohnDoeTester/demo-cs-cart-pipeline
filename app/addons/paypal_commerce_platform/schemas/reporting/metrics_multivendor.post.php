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

/** @var array<string, callable> $schema */
$schema['paypal_commerce_platform'] = static function () {
    $processor_data = fn_get_processor_data_by_name('paypal_commerce_platform.php');

    if ($processor_data) {
        $payment_ids = db_get_fields(
            'SELECT payment_id FROM ?:payments WHERE status = ?s AND processor_id = ?i',
            'A',
            $processor_data['processor_id']
        );

        foreach ($payment_ids as $payment_id) {
            $data = fn_get_processor_data($payment_id);

            if (
                empty($data['processor_params']['client_id'])
                || empty($data['processor_params']['secret'])
                || empty($data['processor_params']['payer_id'])
            ) {
                continue;
            }

            return true;
        }
    }

    return false;
};

return $schema;
