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

if (!isset($schema['central']['products']['items']['products']['subitems'])) {
    $schema['central']['products']['items']['products']['subitems'] = [];
}

$schema['central']['products']['items']['products']['subitems']['master_products.products_being_sold'] = [
    'href'     => 'products.manage',
    'position' => 100,
];

$schema['central']['products']['items']['products']['subitems']['master_products.products_that_vendors_can_sell'] = [
    'href'     => 'products.master_products',
    'position' => 200,
];

return $schema;
