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

use Tygh\Exceptions\DeveloperException;

/**
 * Class State stores an object state collected from multiple data sources.
 *
 * @package Tygh\Addons\VendorDataPremoderation
 */
class State
{
    /**
     * @var array
     */
    protected $state;

    public function __construct(array $state)
    {
        $this->state = $state;
    }

    /**
     * Gets list of source identifiers that store an object data.
     *
     * @return array
     */
    public function getSources()
    {
        return array_keys($this->state);
    }

    /**
     * Gets all data from the source.
     *
     * @param string $source_name
     *
     * @return mixed
     */
    public function getSourceData($source_name)
    {
        if (!isset($this->state[$source_name])) {
            throw new DeveloperException("{$source_name} is not present in the object state");
        }

        return $this->state[$source_name];
    }

    /**
     * Gets fields of data items stored in the source.
     *
     * @param string $source_name
     *
     * @return array
     */
    public function getSourceSchema($source_name)
    {
        $source = $this->getSourceData($source_name);
        if (!$source) {
            return [];
        }

        $item = reset($source);

        return array_keys($item);
    }

    /**
     * Return state array
     *
     * @return array<string, array<string, string|int>>
     */
    public function toArray()
    {
        return $this->state;
    }
}
