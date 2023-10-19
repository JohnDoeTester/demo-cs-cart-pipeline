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
use Tygh\Common\Robots;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $redirect_params = array();
    $writable = null;
    $robots = new Robots;

    if ($mode == 'check') {
        $writable = $robots->check();
        if ($writable) {
            fn_set_notification('N', __('notice'), __('text_permissions_changed'));
        } else {
            if (defined('AJAX_REQUEST')) {
                fn_set_notification('E', __('error'), __('cannot_write_file', array('[file]' => 'robots.txt')));
                exit;
            }
        }
    }

    if ($mode == 'update') {
        if (!empty($_REQUEST['robots_data']) && isset($_REQUEST['robots_data']['content'])) {
            if (fn_allowed_for("ULTIMATE") && isset($_REQUEST['robots_data']['update_content'])) {
                /** @var \Tygh\Storefront\repository $repository */
                $repository = Tygh::$app['storefront.repository'];
                list($all_storefronts,) = $repository->find();
                foreach ($all_storefronts as $storefront) {
                    $robots->setRobotsDataForStorefrontId($storefront->storefront_id, $_REQUEST['robots_data']['content']);
                }
            } else {
                /** @var \Tygh\Storefront\Storefront $storefront */
                $storefront = Tygh::$app['storefront'];
                $storefront_id = $storefront->storefront_id;

                $robots->setRobotsDataForStorefrontId($storefront_id, $_REQUEST['robots_data']['content']);
            }
        }
    }


    if ($mode == 'update_via_ftp') {
        if (
            !empty($_REQUEST['robots_data'])
            && !empty($_REQUEST['robots_data']['edit'])
            && $_REQUEST['robots_data']['edit'] = 'Y'
            && !empty($_REQUEST['robots_data']['content'])
            && !empty($_REQUEST['ftp_access'])
        ) {
            $ftp_settings = array(
                'hostname' => $_REQUEST['ftp_access']['ftp_hostname'],
                'username' => $_REQUEST['ftp_access']['ftp_username'],
                'password' => $_REQUEST['ftp_access']['ftp_password'],
                'directory' => $_REQUEST['ftp_access']['ftp_directory'],
            );
            list($result, $error_text) = $robots->updateViaFtp($_REQUEST['robots_data']['content'], $ftp_settings);
            if (!$result) {
                fn_delete_notification('changes_saved');
                fn_set_notification('E', __('error'), $error_text);
                $writable = false;
                if (defined('AJAX_REQUEST')) {
                    exit;
                }
            }
        }
    }

    if (!is_null($writable)) {
        if (!$writable) {
            fn_set_notification('E', __('error'), __('cannot_write_file', array('[file]' => 'robots.txt')));
            $redirect_params['is_not_writable'] = true;
        }
        if (!empty($_REQUEST['robots_data']['content'])) {
            $redirect_params['content'] = $_REQUEST['robots_data']['content'];
        }
    }

    return array(CONTROLLER_STATUS_OK, 'robots.manage?' . http_build_query($redirect_params));
}

if ($mode == 'manage') {
    
    $storefront = Tygh::$app['storefront'];
    $storefront_id = $storefront->storefront_id;

    $robots = new Robots;
    $robots_data = $robots->getRobotsDataByStorefrontId($storefront_id);

    $robots_data_content = isset($robots_data['data']) ? $robots_data['data'] : '';

    $content = $robots->getRobotsTxtContent();

    if (isset($content)) {
        if (!isset($robots_data['robots_id'])) {
            $robots->setRobotsDataForStorefrontId($storefront_id, $content);
        } else {
            $content = $robots_data_content;
        }

        fn_set_notification('W', __('notice'), __('information_file_roobots'));

    } else {
        $content = $robots_data_content;
    }

    Tygh::$app['view']->assign('robots', $content);

}
