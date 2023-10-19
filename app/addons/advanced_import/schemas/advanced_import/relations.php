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

use Tygh\Enum\Addons\AdvancedImport\RelatedObjectTypes;

$schema = array(
    'products' => array(
        RelatedObjectTypes::FEATURE => array(
            'description'        => 'features',
            'items_function'     => 'fn_advanced_import_get_product_features_list',
            'aggregate_field'    => 'Advanced Import: Features',
            'aggregate_function' => 'fn_advanced_import_aggregate_features',
        ),
    ),
);

return $schema;