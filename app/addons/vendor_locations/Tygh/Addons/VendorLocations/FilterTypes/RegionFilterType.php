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

namespace Tygh\Addons\VendorLocations\FilterTypes;

use Tygh\Addons\VendorLocations\Dto\Region;
use Tygh\Database\Connection;

/**
 * Class RegionFilterType
 * Describes filter by address' components
 * This type is used in filters by address components.
 *
 * @package Tygh\Addons\VendorLocations\FilterTypes
 */
class RegionFilterType extends BaseFilterType
{
    /** @var \Tygh\Addons\VendorLocations\Dto\Region  */
    protected $region;

    /** @var \Tygh\Database\Connection  */
    protected $connection;

    /** @var string  */
    protected $table_alias;

    /**
     * RegionFilterType constructor.
     * @param \Tygh\Addons\VendorLocations\Dto\Region $region
     * @param \Tygh\Database\Connection               $connection
     * @param string                                  $table_alias
     */
    public function __construct(Region $region, Connection $connection, $table_alias = 'vendor_locations')
    {
        $this->region = $region;
        $this->connection = $connection;
        $this->table_alias = $table_alias;
    }

    /**
     * @return string
     */
    public function buildSqlWhereConditions()
    {
        $conditions = array();

        if ($this->region->getCountry()) {
            $conditions[] = $this->connection->quote("{$this->table_alias}.country = ?s", $this->region->getCountry());
        }

        if ($this->region->getState()) {
            $conditions[] = $this->connection->quote("{$this->table_alias}.state = ?s", $this->region->getState());
        }

        if ($this->region->getLocality()) {
            $conditions[] = $this->connection->quote("{$this->table_alias}.locality = ?s", $this->region->getLocality());
        }

        return implode(' AND ', $conditions);
    }

    /**
     * @return array
     */
    public function buildSqlSelectExpression()
    {
        return array();
    }
}
