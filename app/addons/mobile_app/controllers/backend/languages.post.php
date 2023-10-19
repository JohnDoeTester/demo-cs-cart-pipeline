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

use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'translations') {

    $params = array_merge(
        [
            'name' => null,
        ],
        $_REQUEST
    );

    $sections = Registry::ifGet('navigation.dynamic.sections', []);
    $sections['mobile_app_translations'] = [
        'title' => __('mobile_app.app_translations'),
        'href'  => fn_url('languages.translations?name=mobile_app.mobile_'),
    ];

    Registry::set('navigation.dynamic.sections', $sections);
    if ($params['name'] === 'mobile_app.mobile_') {
        Registry::set('navigation.dynamic.active_section', 'mobile_app_translations');
    }
}