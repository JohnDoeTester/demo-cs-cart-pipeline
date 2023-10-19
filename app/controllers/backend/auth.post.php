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

use Tygh\Enum\OrderStatuses;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'login') {

        fn_batch_cleanup_payment_info(array(
            'time_from' => TIME - 7 * SECONDS_IN_DAY,
            'time_to'   => TIME - SECONDS_IN_HOUR,
            'status'    => OrderStatuses::INCOMPLETED,
        ));
    }

    return array(CONTROLLER_STATUS_OK);
}