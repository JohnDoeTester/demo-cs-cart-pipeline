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
use Tygh\Addons\VendorLocations\Dto\Region;
use RuntimeException;
use Tygh\Tygh;

/**
 * Class BaseFilterType
 * Abstract class for geolocation filter types
 *
 * @package Tygh\Addons\VendorLocations\FilterTypes
 */
abstract class BaseFilterType
{
    /**
     * @return string
     */
    abstract public function buildSqlWhereConditions();

    /**
     * @return string
     */
    abstract public function buildSqlSelectExpression();
}
