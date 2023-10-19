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

namespace Tygh\Addons\ProductVariations\Request;

class GenerateProductsAndAttachToGroupRequest extends ABaseGenerateProductsRequest
{
    /**
     * @var int
     */
    protected $group_id = 0;

    /**
     * GenerateProductsAndAttachToVariationGroupCommand constructor.
     *
     * @param int      $group_id
     * @param int      $base_product_id
     * @param string[] $combination_ids
     * @param array    $combinations_data
     */
    public function __construct($group_id, $base_product_id, array $combinations_data)
    {
        $this->group_id = (int) $group_id;

        parent::__construct($base_product_id, $combinations_data);
    }

    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->group_id;
    }

    public static function create($group_id, $base_product_id, array $combination_ids)
    {
        $self = new self($group_id, $base_product_id, []);
        $self->setCombinationIds($combination_ids);

        return $self;
    }
}