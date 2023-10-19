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

class ProductUpdatedEvent extends AEvent
{
    /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupProduct */
    protected $from;

    /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupProduct */
    protected $to;

    protected function __construct(GroupProduct $from, GroupProduct $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct
     */
    public function getTo()
    {
        return $this->to;
    }

    public static function create(GroupProduct $from, GroupProduct $to)
    {
        return new self($from, $to);
    }
}