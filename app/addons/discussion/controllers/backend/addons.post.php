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
use Tygh\Registry;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if (
    $mode === 'update'
    && $_REQUEST['addon'] === 'discussion'
) {
    /** @var array<string, array> $options */
    $options = Tygh::$app['view']->getTemplateVars('options');
    /** @var array<string, array> $subsections */
    $subsections = Tygh::$app['view']->getTemplateVars('subsections');
    unset($options['orders'], $subsections['orders']);

    if (Registry::get('addons.product_reviews.status') === ObjectStatuses::ACTIVE) {
        unset($options['products'], $subsections['products']);
    }

    Tygh::$app['view']->assign([
        'options'     => $options,
        'subsections' => $subsections,
    ]);
}
