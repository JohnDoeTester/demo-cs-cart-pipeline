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

defined('BOOTSTRAP') or die('Access denied');

/**
 * Provides customer location destination ID for caching purposes.
 *
 * @return string
 *
 * @internal
 */
function fn_warehouses_blocks_get_customer_destination_id()
{
    /** @var \Tygh\Location\Manager $location */
    $location = Tygh::$app['location'];

    return $location->getDestinationId();
}

/**
 * Provides list of stores for the "Availability in stores" block.
 *
 * @return array
 *
 * @internal
 */
function fn_warehouses_blocks_get_availability_in_stores()
{
    $params = array_merge([
        'product_id' => null,
    ], $_REQUEST);

    if (!$params['product_id']) {
        return [];
    }

    /** @var \Tygh\Location\Manager $manager */
    $location_manager = Tygh::$app['location'];
    $destination_id = $location_manager->getDestinationId();
    $availability = fn_warehouses_get_availability_summary($params['product_id'], $destination_id);

    if (!$availability) {
        return $availability;
    }

    return $availability['grouped_stores'];
}
