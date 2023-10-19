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


/**
 * Class TypeCollection
 *
 * @package Tygh\Addons\ProductVariations\Product\Type
 */
class TypeCollection
{
    /** @var array  */
    protected $schema = [];

    /** @var \Tygh\Addons\ProductVariations\Product\Type\Type[] */
    protected $instances = [];

    /**
     * TypeCollection constructor.
     *
     * @param array $schema
     */
    public function __construct(array $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @param string $type
     *
     * @return \Tygh\Addons\ProductVariations\Product\Type\Type
     */
    public function get($type)
    {
        if (isset($this->instances[$type])) {
            return $this->instances[$type];
        }

        if (!isset($this->schema[$type])) {
            $type = Type::PRODUCT_TYPE_SIMPLE;
        }

        $this->instances[$type] = new Type($type, $this->schema[$type]);

        return $this->instances[$type];
    }

    /**
     * Gets type to type name map
     *
     * @return array
     */
    public function getTypeNames()
    {
        return array_combine(array_keys($this->schema), array_column($this->schema, 'name'));
    }
}