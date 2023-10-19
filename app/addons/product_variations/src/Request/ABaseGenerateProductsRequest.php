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

abstract class ABaseGenerateProductsRequest
{
    /**
     * @var int
     */
    protected $base_product_id = 0;

    /**
     * @var array<int, string>
     */
    protected $combination_ids = [];

    /**
     * @var array<string, mixed>
     */
    protected $combinations_data = [];

    /**
     * @var array<int, array<int, int>> (feature_id => [variant_id => variant_id])
     */
    protected $features_variants_map = [];

    /**
     * ABaseGenerateProductsCommand constructor.
     *
     * @param int      $base_product_id
     * @param array    $combinations_data
     */
    public function __construct($base_product_id, array $combinations_data)
    {
        $this->base_product_id = (int) $base_product_id;
        $this->combinations_data = $combinations_data;
    }

    /**
     * @param array $feature_variants_map
     */
    public function setFeaturesVariantsMap(array $feature_variants_map)
    {
        $this->features_variants_map = $feature_variants_map;
    }

    /**
     * @param string[] $combination_ids
     */
    public function setCombinationIds(array $combination_ids)
    {
        $this->combination_ids = $combination_ids;
    }

    /**
     * @return int
     */
    public function getBaseProductId()
    {
        return $this->base_product_id;
    }

    /**
     * @return string[]
     */
    public function getCombinationIds()
    {
        return $this->combination_ids;
    }

    /**
     * @return array
     */
    public function getCombinationsData()
    {
        return $this->combinations_data;
    }

    /**
     * @return array
     */
    public function getFeaturesVariantsMap()
    {
        return $this->features_variants_map;
    }
}
