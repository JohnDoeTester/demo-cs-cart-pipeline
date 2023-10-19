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

if ($mode === 'shipping_estimation') {
    $params = array_merge([
        'product_id'          => null,
    ], $_REQUEST);

    /** @var \Tygh\Location\Manager $manager */
    $location_manager = Tygh::$app['location'];
    $destination_id = $location_manager->getDestinationId();
    $availability = fn_warehouses_get_availability_summary($params['product_id'], $destination_id);

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $view->assign($availability);
}

return [CONTROLLER_STATUS_OK];
