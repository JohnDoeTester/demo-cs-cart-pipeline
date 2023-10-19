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
use Tygh\Enum\YesNo;

if (YesNo::toBool(Registry::get('addons.call_requests.enable_call_requests_for_vendors'))) {
    $schema['controllers']['call_requests'] = [
        'permissions' => true,
    ];

    $schema['controllers']['tools']['modes']['update_status']['param_permissions']['table']['call_requests'] = true;
}

return $schema;
