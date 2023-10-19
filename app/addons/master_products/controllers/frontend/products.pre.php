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

use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $mode */

if (
    defined('AJAX_REQUEST')
    && !empty($_REQUEST['product_id'])
    && in_array($mode, ['options', 'quick_view'])
) {
    if (
        !empty($_REQUEST['is_microstore'])
        && (
            !empty($_REQUEST['prev_url'])
            || !empty($_REQUEST['microstore_company_id'])
        )
    ) {
        Tygh::$app['view']->assign(
            'redirect_url',
            !empty($_REQUEST['prev_url'])
                ? $_REQUEST['prev_url']
                : 'companies.products?company_id=' . $_REQUEST['microstore_company_id']
        );
    } else {
        Tygh::$app['view']->assign('redirect_url', 'products.view?product_id=' . $_REQUEST['product_id']);
    }

    if (
        $mode === 'options'
        && !empty($_REQUEST['product_data'][$_REQUEST['product_id']]['product_id'])
    ) {
        Tygh::$app['ajax']->assign('common_product_id', $_REQUEST['product_id']);
    }
}
