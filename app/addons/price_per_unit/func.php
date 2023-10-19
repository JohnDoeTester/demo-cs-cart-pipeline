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

/**
 * The "load_products_extra_data" hook handler.
 *
 * Actions performed:
 * - Adds 'unit_name' field in product getting query.
 *
 * @param array<string|array> $extra_fields Extra fields
 * @param array<string|array> $products     List of products
 * @param array<string>       $product_ids  List of product identifiers
 * @param array<string|array> $params       Parameters passed to fn_get_products()
 *
 * @return void
 *
 * @see fn_load_products_extra_data()
 */
function fn_price_per_unit_load_products_extra_data(array &$extra_fields, array $products, array $product_ids, array $params)
{
    if (!in_array('prices', $params['extend'])) {
        return;
    }
    $extra_fields['?:product_descriptions']['fields'][] = 'unit_name';
}
