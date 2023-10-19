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

use Tygh\Addons\GraphqlApi\InputType as Type;

$schema = fn_get_schema('graphql_types', 'update_product_input');

$schema['name'] = 'CreateProductInput';
$schema['description'] = 'Represents a set of data required to create a product';

$schema['fields']['category_ids']['type'] = Type::nonNull($schema['fields']['category_ids']['type']);

$schema['fields']['product']['type'] = Type::nonNull($schema['fields']['product']['type']);

$schema['fields']['price']['type'] = Type::nonNull($schema['fields']['price']['type']);

return $schema;
