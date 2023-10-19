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

use Tygh\Addons\Warehouses\ServiceProvider;
use Tygh\Addons\CommerceML\Xml\SimpleXmlElement;
use Tygh\Addons\CommerceML\Storages\ImportStorage;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @var array<string, callable> $schema Declares parser callbacks for xml paths
 */
$schema['packages/warehouses/warehouse'] = static function (SimpleXmlElement $xml, ImportStorage $import_storage) {
    ServiceProvider::getWarehouseConvertor()->convert($xml, $import_storage);
};

return $schema;
