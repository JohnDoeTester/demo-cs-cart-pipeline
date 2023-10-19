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


use Tygh\Addons\ProductVariations\Product\Sync\Table\OneToManyViaFieldTable;
use Tygh\Addons\ProductVariations\Product\Sync\Table\OneToManyViaRelationTable;

/** @var array $schema */

$schema['buy_together'] = OneToManyViaFieldTable::create('buy_together', ['chain_id'], 'product_id');
$schema['buy_together_descriptions'] = OneToManyViaRelationTable::create('buy_together_descriptions', ['chain_id', 'lang_code'], 'buy_together', ['chain_id' => 'chain_id'], 'product_id');

return $schema;