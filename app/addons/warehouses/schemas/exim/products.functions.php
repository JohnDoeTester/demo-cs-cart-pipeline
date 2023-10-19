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
 * 'copyright.txt' FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

use Tygh\Enum\YesNo;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Sets quantities of imported product for selected warehouses
 *
 * @param int                             $product_id    Product ID
 * @param array<int, int>                 $quantity_list Quantity list from fn_warehouses_aggregate_quantities
 * @param array<string, int|string|float> $row           Imported product data
 */
function fn_warehouses_exim_set_product_quantities($product_id, array $quantity_list, array $row)
{
    if (!$quantity_list) {
        return;
    }

    /** @var Tygh\Addons\Warehouses\Manager $manager */
    $manager = Tygh::$app['addons.warehouses.manager'];

    $warehouse_amounts = fn_warehouses_exim_create_product_stock($quantity_list);
    if (fn_allowed_for('MULTIVENDOR') && !empty($row['company'])) {
        $product_company_id = fn_mve_get_vendor_id_for_product((string) $row['company']);
        $available_warehouses = $manager->getWarehouses($product_company_id);
        $warehouse_amounts = array_filter(
            $warehouse_amounts,
            static function ($warehouse) use ($available_warehouses) {
                return in_array($warehouse['warehouse_id'], array_keys($available_warehouses));
            }
        );
    }

    if (empty($warehouse_amounts)) {
        return;
    }
    $default_stock = $manager->getProductWarehousesStock($product_id);
    if ($default_stock->hasStockSplitByWarehouses()) {
        $new_amount = array_sum(array_column($warehouse_amounts, 'amount'));
        if ($default_stock->getAmount() <= 0 && $new_amount > 0) {
            fn_send_product_notifications($product_id);
        }
    }

    $product_warehouses = $manager->getProductWarehousesData($product_id);

    foreach ($warehouse_amounts as $warehouse_amount) {
        $product_warehouses[$warehouse_amount['warehouse_id']] = $warehouse_amount;
    }

    $product_stock = $manager->createProductStockFromWarehousesData($product_id, $product_warehouses);

    $manager->saveProductStock($product_stock, false);
}

/**
 *  Return list of all warehouses for export fields list
 *
 * @return array
 */
function fn_warehouses_exim_get_list()
{
    $company_id = fn_get_runtime_company_id();

    /** @var Tygh\Addons\Warehouses\Manager $manager */
    $manager = Tygh::$app['addons.warehouses.manager'];
    $warehouses = $manager->getWarehouses($company_id);

    foreach ($warehouses as &$warehouse) {
        $warehouse['show_description'] = true;
        $warehouse['description'] = $warehouse['name'];
        $warehouse['show_name'] = false;
    }
    unset($warehouse);

    return $warehouses;
}

/**
 * Aggregate all warehouses for import
 *
 * @param array $item            Imported item
 * @param array $aggregated_data Selected records of warehouses quantities
 *
 * @return mixed
 */
function fn_warehouses_exim_aggregate_quantities(array $item, array $aggregated_data)
{
    foreach ($aggregated_data['values'] as $key => $value) {
        unset($aggregated_data['values'][$key]);

        if (
            !is_string($value)
            || !fn_string_not_empty($value)
        ) {
            continue;
        }

        list(, $key) = explode('_', $key);
        $aggregated_data['values'][$key] = $value;
    }

    return $aggregated_data['values'];
}

/**
 * Gets amount of exported product from selected warehouse
 *
 * @param int $product_id   Product ID
 * @param int $warehouse_id Warehouse ID
 *
 * @return bool|int
 */
function fn_warehouses_exim_get_amount($product_id, $warehouse_id)
{
    if (!(isset($product_id) && isset($warehouse_id))) {
        return 0;
    }
    /** @var Tygh\Addons\Warehouses\Manager $manager */
    $manager = Tygh::$app['addons.warehouses.manager'];
    $product_stock = $manager->getProductWarehousesStock($product_id);

    return $product_stock->getAmountForWarehouse($warehouse_id);
}

/**
 * Gets quantity of exported product from all warehouses.
 *
 * @param int $product_id Product ID
 *
 * @return int
 */
function fn_warehouses_exim_get_quantity($product_id)
{
    if (!isset($product_id)) {
        return 0;
    }

    /** @var Tygh\Addons\Warehouses\Manager $manager */
    $manager = Tygh::$app['addons.warehouses.manager'];
    $product_stock = $manager->getProductWarehousesStock($product_id);

    $amount = $product_stock->getAmount();
    if ($amount === false) {
        return fn_get_product_amount($product_id);
    }
    return (int) $amount;
}

/**
 * Transforms quantity list to needed for creating ProductStock structure
 *
 * @param array $quantity_list List of amounts of product on certain warehouses.
 *
 * @return array
 */
function fn_warehouses_exim_create_product_stock($quantity_list)
{
    $warehouse_amounts = [];
    foreach ($quantity_list as $warehouse_id => $quantity) {
        $warehouse_amounts[$warehouse_id] = ['warehouse_id' => $warehouse_id, 'amount' => $quantity];
    }
    return $warehouse_amounts;
}

/**
 * Resets specified warehouses inventory.
 *
 * @param array  $import_data     All imported data by fields.
 * @param string $reset_inventory Option for reset all quantities on mentioned warehouses.
 */
function fn_warehouses_exim_reset_inventory(array $import_data, $reset_inventory)
{
    if (!YesNo::toBool($reset_inventory)) {
        return;
    }
    /** @var Tygh\Addons\Warehouses\Manager $manager */
    $manager = Tygh::$app['addons.warehouses.manager'];

    $warehouse_ids = [];
    $import_product_codes = [];
    $import_products_ids = [];

    foreach ($import_data as $data) {
        if (empty($data['Advanced Import: Warehouse stock']) || !is_array($data['Advanced Import: Warehouse stock'])) {
            continue;
        }
        $product_warehouse_ids = array_keys($data['Advanced Import: Warehouse stock']);

        if (fn_allowed_for('MULTIVENDOR') && !empty($data['company'])) {
            $product_company_id = fn_mve_get_vendor_id_for_product((string) $data['company']);
            $available_warehouses = $manager->getWarehouses($product_company_id);
            $product_warehouse_ids = array_intersect($product_warehouse_ids, array_keys($available_warehouses));
        }
        $warehouse_ids = array_unique(array_merge($warehouse_ids, $product_warehouse_ids));

        if (!isset($data['product_id'])) {
            $import_product_codes[] = $data['product_code'];
        } else {
            $import_products_ids[] = (int) $data['product_id'];
        }
    }

    if (!$warehouse_ids) {
        return;
    }

    $is_need_get_ids = !empty($import_product_codes);
    if ($is_need_get_ids) {
        $product_ids = db_get_array('SELECT product_id FROM ?:products WHERE product_code IN (?a)', $import_product_codes);
        $import_products_ids = array_merge($import_products_ids, array_column($product_ids, 'product_id'));
    }

    $products_ids_to_update = db_get_fields(
        'SELECT product_id FROM ?:warehouses_products_amount WHERE warehouse_id IN (?n)',
        array_values($warehouse_ids)
    );
    $products_ids_to_update = array_diff($products_ids_to_update, $import_products_ids);

    foreach ($products_ids_to_update as &$p_id) {
        $p_id = (int) $p_id;

        /** @var Tygh\Addons\Warehouses\ProductStock $product_stock */
        $product_stock = $manager->getProductWarehousesStock($p_id);
        $product_stock->resetAmount($warehouse_ids);
        $manager->saveProductStock($product_stock, false);
    }
}
