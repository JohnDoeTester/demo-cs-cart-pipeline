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

use Tygh\Addons\Warehouses\CommerceML\Dto\WarehouseDto;
use Tygh\Addons\Warehouses\Manager;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @var array<string, array{is_creatable: array<callable>|bool, items_provider: array<callable>}> $schema Declares mapping for entities sync
 */
$schema[WarehouseDto::REPRESENT_ENTITY_TYPE] = [
    'is_creatable'   => true,
    'items_provider' => static function () {
        $items = [];
        $params = [
            'store_types' => [Manager::STORE_LOCATOR_TYPE_WAREHOUSE, Manager::STORE_LOCATOR_TYPE_STORE],
        ];

        if (fn_allowed_for('MULTIVENDOR')) {
            $params['company_id'] = fn_get_runtime_company_id();
        }

        list($warehouses,) = fn_get_store_locations($params);

        foreach ($warehouses as $warehouse) {
            $items[$warehouse['store_location_id']] = $warehouse['name'];
        }

        return $items;
    }
];

return $schema;
