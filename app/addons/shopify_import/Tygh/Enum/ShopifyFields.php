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

namespace Tygh\Enum;

/**
 * Class ShopifyFields contains default Shopify fields from CSV-file.
 *
 * @package Tygh\Enum
 */
class ShopifyFields
{
    const HANDLE = 'Handle';
    const IMAGE_SRC = 'Image Src';
    const IMAGE_POSITION = 'Image Position';
    const STATUS = 'Status';
    const VARIANT_SKU = 'Variant SKU';
    const VARIANT_INVENTORY_POLICY = 'Variant Inventory Policy';
    const VARIANT_FULFILLMENT_SERVICE = 'Variant Fulfillment Service';
    const VARIANT_IMAGE = 'Variant Image';
    const TITLE = 'Title';
}
