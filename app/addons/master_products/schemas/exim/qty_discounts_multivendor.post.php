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

require_once Registry::get('config.dir.addons') . 'master_products/schemas/exim/products.functions.php';

$runtime_company_id = Registry::get('runtime.company_id');

/**
 * @var array<string, array> $schema
 */
$schema['import_get_primary_object_id']['master_products_exim_set_company_id'] = [
    'function'    => 'fn_master_products_exim_set_company_id',
    'args'        => ['$alt_keys', '$skip_get_primary_object_id', $runtime_company_id],
    'import_only' => true,
];

return $schema;
