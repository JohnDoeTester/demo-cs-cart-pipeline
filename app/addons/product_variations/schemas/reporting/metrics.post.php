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

use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Addons\ProductVariations\Product\Group\Repository as GroupRepository;
use Tygh\Addons\ProductVariations\Product\Repository as ProductRepository;
use Tygh\Enum\YesNo;
use Tygh\Registry;

/** @var array $schema */

$schema['variations'] = function () {
    $query = ServiceProvider::getQueryFactory()->createQuery(
        GroupRepository::TABLE_GROUPS,
        [],
        ['g.id'],
        'g'
    );

    $query->addCondition('code NOT IN (?a)', [['PV-27186628F']]);
    $query->addInnerJoin('gp', GroupRepository::TABLE_GROUP_PRODUCTS, ['id' => 'group_id']);
    $query->addInnerJoin('p', ProductRepository::TABLE_PRODUCTS, ['gp.product_id' => 'product_id']);
    $query->addConditions(['status' => ['A']], 'p');
    $query->setLimit(1);

    return (bool) $query->scalar();
};

$schema['product_variations_qty_discount'] = YesNo::toBool(Registry::get('addons.product_variations.quantity_discount_on_different_variations'));

return $schema;