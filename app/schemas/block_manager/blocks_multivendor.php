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

/** @var array $schema */
$schema['cart_content']['is_managed_by'] = ['ROOT'];
$schema['categories']['is_managed_by'] = ['ROOT'];
$schema['checkout']['is_managed_by'] = ['ROOT'];
$schema['currencies']['is_managed_by'] = ['ROOT'];
$schema['languages']['is_managed_by'] = ['ROOT'];
$schema['menu']['is_managed_by'] = ['ROOT'];
$schema['my_account']['is_managed_by'] = ['ROOT'];
$schema['our_brands']['is_managed_by'] = ['ROOT'];
$schema['payment_methods']['is_managed_by'] = ['ROOT'];
$schema['product_filters_home']['is_managed_by'] = ['ROOT'];
$schema['shipping_methods']['is_managed_by'] = ['ROOT'];
$schema['smarty_block']['is_managed_by'] = ['ROOT'];
$schema['safe_smarty_block']['is_managed_by'] = ['ROOT'];
$schema['template']['is_managed_by'] = ['ROOT'];
$schema['vendors']['is_managed_by'] = ['ROOT'];

return $schema;
