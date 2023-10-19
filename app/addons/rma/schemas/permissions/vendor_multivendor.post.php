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

use Tygh\Enum\ObjectStatuses;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');
/**
 * @psalm-var array{
 *   controllers: array{
 *     modes: array<string, array{
 *       permissions: string|bool
 *     }>
 *   }
 * } $schema
 */
$schema['controllers']['rma']['modes']['returns']['permissions'] = true;
$schema['controllers']['rma']['modes']['details']['permissions'] = true;
$schema['controllers']['rma']['modes']['print_slip']['permissions'] = true;

// FIXME: Workaround for correct privilege detection in Vendor privileges add-on
if (Registry::ifGet('addons.vendor_privileges.status', ObjectStatuses::DISABLED) === ObjectStatuses::ACTIVE) {
    $schema['controllers']['rma']['modes']['update_details']['permissions'] =
    $schema['controllers']['rma']['modes']['accept_products']['permissions'] =
    $schema['controllers']['rma']['modes']['decline_products']['permissions'] =
    $schema['controllers']['rma']['modes']['confirmation']['permissions'] =
        'manage_rma';
}

return $schema;
