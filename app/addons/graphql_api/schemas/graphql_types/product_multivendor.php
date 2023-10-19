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

use Tygh\Addons\GraphqlApi\Type;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */

// this field has to be described globally, so mobile app can specify it in its queries regardless of the add-on status
$schema['fields']['master_product_id'] = [
    'type'        => Type::int(),
    'description' => 'ID of a common product for this product',
];

return $schema;
