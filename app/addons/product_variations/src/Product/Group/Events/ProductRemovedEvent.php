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


namespace Tygh\Addons\ProductVariations\Product\Group\Events;


use Tygh\Addons\ProductVariations\Product\Group\GroupProduct;

class ProductRemovedEvent extends AEvent
{
    protected $product;

    protected function __construct(GroupProduct $product)
    {
        $this->product = $product;
    }

    public static function create(GroupProduct $product)
    {
        return new self($product);
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct
     */
    public function getProduct()
    {
        return $this->product;
    }
}