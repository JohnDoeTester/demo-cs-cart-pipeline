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

use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */

$schema['vendor_communication'] = function () {
    $installation_timestamp = (int) db_get_field('SELECT install_datetime FROM ?:addons WHERE addon = ?s', 'vendor_communication');

    if ($installation_timestamp) {
        $two_days_after = new DateTime();
        $two_days_after
            ->setTimestamp($installation_timestamp)
            ->add(new DateInterval('P2D'));

        return (bool) db_get_field('SELECT COUNT(*) FROM ?:vendor_communication_messages WHERE timestamp > ?i', $two_days_after->getTimestamp());
    } else {
        return false;
    }
};

return $schema;