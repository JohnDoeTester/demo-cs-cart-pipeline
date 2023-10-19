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


namespace Tygh\Addons\ProductVariations\Product\Type;

use Tygh\Addons\ProductVariations\ServiceProvider;

/**
 * Class ProductType
 *
 * @package Tygh\Addons\ProductVariations\Product
 */
class Type
{
    /** Product type variation */
    const PRODUCT_TYPE_VARIATION = 'V';

    /** Simple product type */
    const PRODUCT_TYPE_SIMPLE = 'P';

    /** @var array */
    protected $schema = array();

    /** @var string */
    protected $type;

    /**
     * ProductType constructor.
     *
     * @param array $schema Schema
     */
    public function __construct($type, array $schema)
    {
        $this->type = $type;
        $this->schema = $schema;
    }

    /**
     * @param string $type
     *
     * @return \Tygh\Addons\ProductVariations\Product\Type\Type
     */
    public static function create($type)
    {
        return ServiceProvider::getTypeCollection()->get($type);
    }

    /**
     * @param array $product
     *
     * @return \Tygh\Addons\ProductVariations\Product\Type\Type
     */
    public static function createByProduct(array $product)
    {
        $type = isset($product['product_type']) ? $product['product_type'] : self::PRODUCT_TYPE_SIMPLE;
        $product_id = isset($product['product_id']) ? (int) $product['product_id'] : 0;

        /**
         * @param array  $product Product data
         * @param string $type    Current product type
         */
        fn_set_hook('product_type_create_by_product', $product, $product_id, $type);

        return self::create($type);
    }

    /**
     * Whether to field is available.
     *
     * @param string $field Product field
     *
     * @return bool
     */
    public function isFieldAvailable($field)
    {
        if (isset($this->schema['field_aliases'][$field])) {
            $field = $this->schema['field_aliases'][$field];
        }

        if (isset($this->schema['disable_fields']) && in_array($field, $this->schema['disable_fields'], true)) {
            return false;
        }

        return !isset($this->schema['fields']) || in_array($field, $this->schema['fields'], true);
    }

    /**
     * Gets product type fields
     *
     * @return array
     */
    public function getFields()
    {
        return isset($this->schema['fields']) ? $this->schema['fields'] : [];
    }

    /**
     * Whether to product tab is available.
     *
     * @param string $tab_id Product tab identifier
     *
     * @return bool
     */
    public function isTabAvailable($tab_id)
    {
        return !isset($this->schema['tabs']) || in_array($tab_id, $this->schema['tabs'], true);
    }


    /**
     * Creates search criteria by product type
     *
     * @param string $table
     *
     * @return string
     */
    public function createProductSearchCriteria($table = 'products')
    {
        if (isset($this->schema['search_criteria_callback'])) {
            return call_user_func($this->schema['search_criteria_callback'], $table);
        }

        return db_quote(sprintf("%s.product_type = ?s", $table), $this->type);
    }

    /**
     * @return bool
     */
    public function isAllowGenerateVariations()
    {
        return isset($this->schema['allow_generate_variations']) ? (bool) $this->schema['allow_generate_variations'] : true;
    }
}