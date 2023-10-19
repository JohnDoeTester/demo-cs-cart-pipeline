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
use Tygh\Registry;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return;
}

if (
    $mode === 'update'
    && $_REQUEST['addon'] === 'searchanise'
) {
    $tabs = Registry::get('navigation.tabs');
    unset($tabs['subscription']);
    Registry::set('navigation.tabs', $tabs);
    Tygh::$app['view']->assign('personal_review', true);
}

if ($mode === 'manage') {
    /** @var array<string, array<string>> $addon_list */
    $addon_list = Tygh::$app['view']->getTemplateVars('addons_list');
    if (isset($addon_list['searchanise'])) {
        $addon_list['searchanise']['hide_post_review'] = true;
    }
    Tygh::$app['view']->assign('addons_list', $addon_list);
}
