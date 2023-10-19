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

use Tygh\Addons\VendorLocations\Dto\Zone;
use Tygh\Database\Connection;

/**
 * Class ZoneFilterType
 * Describes filter by distance type.
 * This type is used in filters by distance to the vendor.
 *
 * @package Tygh\Addons\VendorLocations\FilterTypes
 */
class ZoneFilterType extends BaseFilterType
{
    const EARTH_RADIUS_KM = 6371;

    const EARTH_RADIUS_MILES = 3959;

    /** @var \Tygh\Addons\VendorLocations\Dto\Zone  */
    protected $area;

    /** @var \Tygh\Database\Connection  */
    protected $connection;

    /** @var string  */
    protected $distance_sql_expression;

    /** @var string */
    protected $distance_unit;

    /** @var string  */
    protected $table_alias;

    /**
     * ZoneFilterType constructor.
     * @param \Tygh\Addons\VendorLocations\Dto\Zone $area
     * @param \Tygh\Database\Connection             $connection
     * @param                                       $distance_unit
     * @param string                                $table_alias
     */
    public function __construct(Zone $area, Connection $connection, $distance_unit, $table_alias = 'vendor_locations')
    {
        $this->area = $area;
        $this->connection = $connection;
        $this->distance_unit = $distance_unit;
        $this->table_alias = $table_alias;
        $this->distance_sql_expression = $this->buildDistanceSqlExpression();
    }

    /**
     * Builds 'where' condition with distance constrains
     *
     * @return string
     */
    public function buildSqlWhereConditions()
    {
        $result = '';

        if ($this->distance_sql_expression) {
            $result = $this->connection->quote(
                ' (?p) < ?d',
                $this->distance_sql_expression,
                $this->area->getRadius()
            );
        }

        return $result;
    }

    /**
     * Builds 'fields' set
     *
     * @return array
     */
    public function buildSqlSelectExpression()
    {
        $fields = array();

        if ($this->distance_sql_expression) {
            $fields = array(
                'lat' => "{$this->table_alias}.lat",
                'lng' => "{$this->table_alias}.lng",
                'distance' => $this->distance_sql_expression,
            );
        }

        return $fields;
    }

    /**
     * Builds calculate distance between two points SQL expression
     *
     * @return string
     */
    public function buildDistanceSqlExpression()
    {
        $units = array(
            'km' => self::EARTH_RADIUS_KM,
            'miles' => self::EARTH_RADIUS_MILES,
        );

        if ($this->area->getLat() && $this->area->getLng()) {
            return $this->connection->quote(
                "(?i * acos(cos(radians(?p)) * cos(radians({$this->table_alias}.lat)) * cos(radians({$this->table_alias}.lng) "
                . "- radians(?p)) + sin(radians(?p)) * sin(radians({$this->table_alias}.lat))))",
                $units[$this->distance_unit],
                $this->area->getLat(),
                $this->area->getLng(),
                $this->area->getLat()
            );
        }

        return false;
    }

    /**
     * Gets table alias
     *
     * @return string
     */
    public function getTableAlias()
    {
        return $this->table_alias;
    }
}
