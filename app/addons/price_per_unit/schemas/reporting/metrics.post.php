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

/** @var array $schema */
$schema['price_per_unit_is_used'] = static function () {
    $products_using_price_per_unit = db_get_field(
        'SELECT COUNT(1) FROM ?:products as products'
        . ' LEFT JOIN ?:product_descriptions as descriptions on descriptions.product_id = products.product_id'
        . ' WHERE descriptions.unit_name != "" AND products.units_in_product != "0.000"'
    );

    return $products_using_price_per_unit > 4;
};

return $schema;
