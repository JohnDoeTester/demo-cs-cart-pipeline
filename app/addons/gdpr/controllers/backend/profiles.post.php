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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

if ($mode == 'update') {

    if (!empty($_REQUEST['user_id'])
        && isset($_REQUEST['user_type']) && $_REQUEST['user_type'] === 'C'
    ) {
        Registry::set('navigation.tabs.gdpr_user_data', array(
            'title'        => __('gdpr.gdpr_user_data'),
            'href'         => sprintf('gdpr.get_user_data?user_id=%s', $_REQUEST['user_id']),
            'ajax'         => true,
            'ajax_onclick' => true,
        ));

        /** @var \Tygh\Addons\Gdpr\Service $service */
        $service = Tygh::$app['addons.gdpr.service'];
        $anonymized = $service->isUserAnonymized($_REQUEST['user_id']);

        Tygh::$app['view']->assign('anonymized', $anonymized);
    }
}
