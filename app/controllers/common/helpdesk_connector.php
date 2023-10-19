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

use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;
use Tygh\Helpdesk;
use Tygh\Providers\HelpdeskProvider;
use Tygh\Registry;
use Tygh\Snapshot;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $mode */
/** @var array $auth */

$user_id = empty($auth['user_id'])
    ? null
    : (int) $auth['user_id'];

$helpdesk_user_id = empty($auth['helpdesk_user_id'])
    ? null
    : (int) $auth['helpdesk_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        $mode === 'validate_request'
        && !empty($_REQUEST['token'])
    ) {
        $result = 'invalid';
        if (fn_get_storage_data('hd_request_code') === trim($_REQUEST['token'])) {
            $result = 'valid';
        }

        echo $result;

        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    if ($mode === 'messages') {
        if (
            Helpdesk::isValidRequest(
                $_REQUEST,
                ['license_number' => Registry::get('settings.Upgrade_center.license_number')]
            )
        ) {
            $data = simplexml_load_string(urldecode($_REQUEST['request']));

            Helpdesk::processMessages($data->Messages, true, $data->License);

            echo 'OK';
        }

        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    if ($mode === 'activate_license') {
        if (Helpdesk::isValidRequest($_REQUEST)) {
            fn_set_storage_data('free_mode', YesNo::YES);

            echo 'OK';
        }

        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'auth') {
    header('Content-Type: image/png');
    echo base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAlJJREFUeNqk' .
        'UztoVEEUPfN5k7gf4q4J6yduxKDRQhBEUCQ2KbaJCiI2Wtgt0cJCUEGxshJs/EBSWCoWFhKxULtFDUYXBUFMjJFl1WVBVkX39/a9N96Zfbtu' .
        'oYU4cLgz8+45986ZeUxrjf8ZMjfBTIxxjqzgyAiJpBT0Qf4ZXKDCGR4whhni/ZQ0ASE7mE4cH9qYGFYRpRhlciHATeyA1owQtFpurVgYbXwq' .
        'msKXJVU1AhlDrjerquU3IShREqkDhGRGbQrGVDQ9MkwCmbaAtAJJU9mQV1/4CFAMKh8QFB5Dv7kDXi2DE5kxe1xw1afIuqSd2/MK2DZN5ebc' .
        'dbRe3QLqXyG3H4Vz+DbE1gNdsjG9DVjYDs03HrbtPr1iozbrwU3g46eBveeIyqAXZi0Zvt8V4F13w/PGTy1gxdQ8nMmrVhSPzgCll8Cuk9Cx' .
        'NcQlsuch+JtAkL8B/f4hWGobMDlNF5yCfnKJkvqBLQfhGTJBB0AQ9Aiw0AM9fw3IXQTuHmu/lJ0noCvL0NQFG9nTFfCJ7PcIuKZdIyCmKDGb' .
        'B358Bgo5YO0O23bwZREsseG3gAeXABm+5FLnnjvDmlWvgDlRS5JujQr0tQVIsFpFyaSw14eMv5gZ2zd+RDZq0d6rMpUNoRdmT6lINX/v3U3y' .
        'ICuelYGJVUi7nh6NrxsaEPGoRD8ZphTgONSBA04QBIf2ghZvLL6oLBWX6/fPL+G5eR3p9RGkzo5h/+YYdkNjpXG347IfRgsfdHB8e/sdc9Nl' .
        'zJY9lI3AAIFKQvzjn0xyaPwSYACS4hG3ZjB6zgAAAABJRU5ErkJggg=='
    );

    if (
        SiteArea::isAdmin(Tygh::$app['session']['auth']['area'])
        && $user_id
    ) {
        $domains = [];

        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];

        /** @var \Tygh\Storefront\Storefront[] $storefronts */
        list($storefronts,) = $repository->find();
        $domains = array_map(static function ($storefront) {
            return $storefront->url;
        }, $storefronts);

        $extra_fields = [
            'token'     => Helpdesk::token(true),
            'store_key' => Helpdesk::getStoreKey(),
            'domains'   => implode(',', $domains),
        ];

        $data = Helpdesk::getLicenseInformation('', $extra_fields);
        Helpdesk::parseLicenseInformation($data, Tygh::$app['session']['auth']);

        Snapshot::notifyCoreChanges();
        Helpdesk::sendReportMetrics();
        fn_cleanup_old_logs();
    }

    return [CONTROLLER_STATUS_NO_CONTENT];
}

if ($mode === 'get_software_information') {
    if (Helpdesk::isValidRequest($_REQUEST)) {
        $params = array_merge([
            'response_type'   => 'html',
            'get_last_action' => YesNo::NO
        ], $_REQUEST);

        Helpdesk::getSoftwareInformation(true, $params['response_type'], $params['get_last_action']);
    }

    return [CONTROLLER_STATUS_NO_PAGE];
}

if ($mode === 'oauth_process') {
    if (
        !$user_id
        || !UserTypes::isAdmin($auth['user_type'])
    ) {
        return [CONTROLLER_STATUS_DENIED];
    }

    $params = array_merge([
        'code'       => '',
        'state'      => '',
        'return_url' => 'profiles.update?user_id=' . $user_id,
        'error'      => '',
    ], $_REQUEST);

    $auth_provider = HelpdeskProvider::getAuthProvider(fn_url($params['return_url']));

    if ($params['error']) {
        fn_set_notification(
            NotificationSeverity::ERROR,
            __('error'),
            __('oauth_error.' . $params['error'])
        );

        return [CONTROLLER_STATUS_DENIED];
    }

    if (!$params['code']) {
        $auth_url = $auth_provider->getAuthorizationUrl();
        $auth_provider->rememberAuthState();

        return [
            CONTROLLER_STATUS_REDIRECT,
            $auth_url,
            true,
        ];
    }

    if (!$auth_provider->isValidAuthState($params['state'])) {
        $auth_provider->resetAuthState();

        fn_set_notification(
            NotificationSeverity::ERROR,
            __('error'),
            'reset_auth_state'
        );

        return [CONTROLLER_STATUS_DENIED];
    }

    try {
        $access_token = $auth_provider->getAccessTokenByAuthorizationCode($params['code']);
        $resource_owner = $auth_provider->getResourceOwner($access_token);
        HelpdeskProvider::getAuthService()->setExternalUserId(
            $user_id,
            $resource_owner->getId()
        );
        HelpdeskProvider::getPermissionService()->reportConnection([$resource_owner->getId()]);
    } catch (Exception $e) {
        fn_set_notification(
            NotificationSeverity::ERROR,
            __('error'),
            $e->getMessage()
        );
    }

    return [CONTROLLER_STATUS_REDIRECT, $params['return_url']];
}

if ($mode === 'oauth_disconnect' && $user_id) {
    $params = array_merge(
        [
            'return_url' => 'profiles.update?user_id=' . $user_id,
        ],
        $_REQUEST
    );
    HelpdeskProvider::getAuthService()->resetExternalUserId($user_id);

    return [CONTROLLER_STATUS_REDIRECT, $params['return_url']];
}

if ($mode === 'visit_marketplace' || $mode === 'view_more_addons') {
    $redirect_url = $helpdesk_user_id
        ? HelpdeskProvider::getServiceUrl() . '/index.php?dispatch=oauth2.sso.marketplace'
        : Registry::get('config.resources.marketplace_url');

    $utm_parameters = '';
    switch ($mode) {
        case 'visit_marketplace':
            $utm_parameters = 'utm_source=cscart_installation&utm_medium=referal&utm_campaign=visit_button';
            break;
        case 'view_more_addons':
            $utm_parameters = 'utm_source=cscart_installation&utm_medium=referal&utm_campaign=searchanise';
            break;
    }

    $url = parse_url($redirect_url);
    if (isset($url['query'])) {
        $redirect_url = $redirect_url . '&' . $utm_parameters;
    } else {
        $redirect_url = $redirect_url . '?' . $utm_parameters;
    }

    return [
        CONTROLLER_STATUS_REDIRECT,
        $redirect_url,
        true
    ];
}

if ($mode === 'activate_license_mail_request') {
    if (
        !$user_id
        || $auth['company_id']
        || !UserTypes::isAdmin($auth['user_type'])
        || !YesNo::toBool($auth['is_root'])
    ) {
        return [CONTROLLER_STATUS_DENIED];
    }
    $result = HelpdeskProvider::getLicenseActivateMailRequester()->requestMail();

    if ($result->isSuccess()) {
        fn_set_notification(
            NotificationSeverity::NOTICE,
            __('notice'),
            __('helpdesk_account.activate_free_license_message_send', ['[email]' => $result->getData('email')])
        );
    } else {
        $result->showNotifications();
    }

    return [CONTROLLER_STATUS_OK];
}

return [CONTROLLER_STATUS_NO_PAGE];
