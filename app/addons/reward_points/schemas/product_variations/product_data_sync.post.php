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


use Tygh\Addons\ProductVariations\Product\Sync\Table\OneToManyViaPrimaryKeyTable;

/** @var array $schema */

$schema['reward_points'] = OneToManyViaPrimaryKeyTable::create('reward_points', ['object_id', 'object_type', 'usergroup_id', 'company_id'], 'object_id', ['reward_point_id'], ['conditions' => ['object_type' => 'P']]);
$schema['product_point_prices'] = OneToManyViaPrimaryKeyTable::create('product_point_prices', ['product_id', 'lower_limit', 'usergroup_id'], 'product_id', ['point_price_id']);

return $schema;