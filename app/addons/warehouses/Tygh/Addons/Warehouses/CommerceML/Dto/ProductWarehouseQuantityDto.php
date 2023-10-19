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


namespace Tygh\Addons\Warehouses\CommerceML\Dto;


use Tygh\Addons\CommerceML\Dto\IdDto;

/**
 * Class ProductWarehouseQuantityDto
 *
 * @package Tygh\Warehouses\Addons\CommerceML\Dto
 */
class ProductWarehouseQuantityDto
{
    /**
     * @var \Tygh\Addons\CommerceML\Dto\IdDto
     */
    public $warehouse_id;

    /**
     * @var int
     */
    public $quantity;

    /**
     * ProductWarehouseQuantityDto constructor.
     *
     * @param \Tygh\Addons\CommerceML\Dto\IdDto $warehouse_id Warehouse ID
     * @param int                               $quantity     Warehouse quantity
     */
    public function __construct(IdDto $warehouse_id, $quantity = 0)
    {
        $this->warehouse_id = $warehouse_id;
        $this->quantity = $quantity;
    }

    /**
     * @param \Tygh\Addons\CommerceML\Dto\IdDto $warehouse_id Warehouse ID
     * @param int                               $quantity     Warehouse quantity
     *
     * @return \Tygh\Addons\Warehouses\CommerceML\Dto\ProductWarehouseQuantityDto
     */
    public static function create(IdDto $warehouse_id, $quantity = 0)
    {
        return new self($warehouse_id, $quantity);
    }
}
