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

use Tygh\Enum\Addons\VendorCommunication\CommunicationTypes;
use Tygh\Registry;
use Tygh\Enum\YesNo;

if (fn_vendor_communication_is_communication_type_active(CommunicationTypes::VENDOR_TO_ADMIN)) {
    $schema['central']['vendors']['items']['vendor_communication.message_center_vendor_name'] = [
        'attrs' => [
            'class' => 'is-addon'
        ],
        'href' => 'vendor_communication.threads?communication_type=' . CommunicationTypes::VENDOR_TO_ADMIN,
        'alt' => 'vendor_communication.threads?communication_type=' . CommunicationTypes::VENDOR_TO_ADMIN,
        'position' => 900,
    ];
}

return $schema;
