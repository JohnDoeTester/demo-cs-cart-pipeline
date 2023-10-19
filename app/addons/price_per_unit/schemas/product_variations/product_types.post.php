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
 * 'copyright.txt' FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

use Tygh\Addons\ProductVariations\Product\Type\Type;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

/**
 * @var array $schema
 */

$schema[Type::PRODUCT_TYPE_VARIATION]['fields'][] = 'unit_name';
$schema[Type::PRODUCT_TYPE_VARIATION]['fields'][] = 'units_in_product';
$schema[Type::PRODUCT_TYPE_VARIATION]['fields'][] = 'show_price_per_x_units';

return $schema;
