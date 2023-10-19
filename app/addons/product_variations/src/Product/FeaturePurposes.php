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


namespace Tygh\Addons\ProductVariations\Product;

/**
 * Class FeaturePurposes
 *
 * @package Tygh\Addons\ProductVariations\Product
 */
class FeaturePurposes
{
    const CREATE_CATALOG_ITEM = 'group_catalog_item';

    const CREATE_VARIATION_OF_CATALOG_ITEM = 'group_variation_catalog_item';

    public static function isCreateCatalogItem($purpose)
    {
        return $purpose === self::CREATE_CATALOG_ITEM;
    }

    public static function isCreateVariationOfCatalogItem($purpose)
    {
        return $purpose === self::CREATE_VARIATION_OF_CATALOG_ITEM;
    }

    public static function getAll()
    {
        return [self::CREATE_CATALOG_ITEM, self::CREATE_VARIATION_OF_CATALOG_ITEM];
    }
}