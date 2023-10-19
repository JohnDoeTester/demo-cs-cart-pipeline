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

namespace Tygh\Addons\VendorDataPremoderation;

/**
 * Class StateFactory loads object states.
 *
 * @package Tygh\Addons\VendorDataPremoderation
 */
class StateFactory
{
    /**
     * @var callable $data_loader Loads data from object data sources
     */
    protected $data_loader;

    /**
     * @var array<array-key, array|int|string> $product_structure Describes conditions, joins etc. to extract object data from each source
     */
    protected $product_structure;

    /**
     * @var array<array-key, array|int|string> $joins
     */
    protected $joins;

    const OBJECT_ID_PLACEHOLDER = '$id';

    /**
     * @param array<array-key, array|int|string> $product_structure Product structure
     * @param callable                           $data_loader       Data loader
     */
    public function __construct(array $product_structure, callable $data_loader)
    {
        $this->product_structure = $product_structure;
        $this->data_loader = $data_loader;
    }

    /**
     * Gets object state.
     *
     * @param string|int $id Object identifier
     *
     * @return \Tygh\Addons\VendorDataPremoderation\State
     */
    public function getState($id)
    {
        $object_data = [];
        foreach ($this->product_structure as $source => $source_data) {
            $conditions = $this->prepareConditions($id, $source_data);
            $joins = $this->prepareJoins($id, $source_data);

            $object_data[$source] = call_user_func($this->data_loader, $source, $conditions, $joins);
        }

        return new State($object_data);
    }

    /**
     * Prepares conditions to load object data from a source.
     *
     * @param string|int                         $id          Object identifier
     * @param array<array-key, array|int|string> $source_data Source data
     *
     * @return array<array-key, array|int|string>
     */
    public function prepareConditions($id, array $source_data)
    {
        foreach ($source_data as $key => &$data) {
            if ($data === self::OBJECT_ID_PLACEHOLDER) {
                $data = $id;
            } elseif (is_array($data)) {
                unset($source_data[$key]);
            }
        }
        unset($data);

        return $source_data;
    }

    /**
     * Prepares joins to load additional object data from a source.
     *
     * @param string|int                         $id          Object identifier
     * @param array<array-key, array|int|string> $source_data Source data
     *
     * @return array<array-key, array|int|string>
     */
    public function prepareJoins($id, array $source_data)
    {
        foreach ($source_data as $key => &$data) {
            if (is_array($data)) {
                foreach ($data as &$field) {
                    if ($field === self::OBJECT_ID_PLACEHOLDER) {
                        $field = $id;
                        continue;
                    }
                }
            } else {
                unset($source_data[$key]);
            }
        }

        return $source_data;
    }
}
