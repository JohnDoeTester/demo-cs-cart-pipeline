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

use Tygh\Enum\ReceiverSearchMethods;
use Tygh\Enum\UserTypes;

defined('BOOTSTRAP') or die('Access denied');

$schema['__default'][UserTypes::VENDOR] = [
    'is_configurable' => true,
    'methods'         => [
        ReceiverSearchMethods::USERGROUP_ID => true,
        ReceiverSearchMethods::VENDOR_EMAIL => true,
    ],
];

return $schema;
