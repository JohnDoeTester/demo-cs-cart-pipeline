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

/**
 * @var string $mode
 */

use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'picker') {
    $bundle_flag = isset($_REQUEST['segment']) && $_REQUEST['segment'] === 'product_bundles';
    Tygh::$app['view']->assign('is_product_bundles', $bundle_flag);
}
