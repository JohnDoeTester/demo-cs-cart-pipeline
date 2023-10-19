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
 * Class Diff stores a set of changed object data sources.
 *
 * @package Tygh\Addons\VendorDataPremoderation
 */
class Diff
{
    /**
     * @var array<string, bool>
     */
    protected $diff = [];

    /**
     * @var array<string, array<string, bool>>
     */
    protected $fields = [];

    /**
     * Adds changed data source.
     *
     * @param string $source_name
     */
    public function addChangedSource($source_name)
    {
        $this->diff[$source_name] = true;
    }

    /**
     * Adds changed data field.
     *
     * @param string $source_name Source name
     * @param string $field_name  Field name
     *
     * @return void
     */
    public function addChangedField($source_name, $field_name)
    {
        $this->diff[$source_name] = true;
        $this->fields[$source_name][$field_name] = true;
    }

    /**
     * Checks whether there are changed sources.
     *
     * @return bool
     */
    public function hasChanges()
    {
        return count($this->diff) > 0 || count($this->fields) > 0;
    }

    /**
     * Gets list of changed sources.
     *
     * @return array
     */
    public function getChangedSources()
    {
        return array_keys($this->diff);
    }

    /**
     * Return diff array
     *
     * @return array<string, bool>
     */
    public function getSources()
    {
        return $this->diff;
    }

    /**
     * Return fields array
     *
     * @return array<string, array<string, bool>>
     */
    public function getFields()
    {
        return $this->fields;
    }
}
