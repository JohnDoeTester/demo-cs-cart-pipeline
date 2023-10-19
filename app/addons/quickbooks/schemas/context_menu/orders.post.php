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

defined('BOOTSTRAP') or die('Access denied!');

/** @var array $schema */
$schema['items']['actions']['items']['quickbooks.export_to_quickbooks'] = [
    'name'                => ['template' => 'export_to_quickbooks'],
    'dispatch'            => 'quickbooks_export.export_to_iif',
    'permission_callback' => static function ($request, $auth, $runtime) {
        return !$runtime['company_id'];
    },
    'position'            => 35,
];

return $schema;
