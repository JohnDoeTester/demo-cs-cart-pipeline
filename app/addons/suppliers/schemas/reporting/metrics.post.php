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

use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SuppliersObjectTypes;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */
$schema['suppliers_is_used'] = static function () {
    return (bool) db_get_field(
        'SELECT COUNT(1) FROM ?:suppliers as suppliers'
        . ' LEFT JOIN ?:supplier_links as links on links.supplier_id = suppliers.supplier_id'
        . ' LEFT JOIN ?:products as products on products.product_id = links.object_id'
        . ' WHERE links.object_type = ?s AND suppliers.status = ?s AND products.status = ?s',
        SuppliersObjectTypes::PRODUCT,
        ObjectStatuses::ACTIVE,
        ObjectStatuses::ACTIVE
    );
};

return $schema;
