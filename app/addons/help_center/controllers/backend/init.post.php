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
use Tygh\Http;
use Tygh\NotificationsCenter\NotificationsCenter;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if (Registry::isExist('config.help_center.server_url')) {
    Tygh::$app['view']->assign('help_center_server_url', Registry::get('config.help_center.server_url'));
} elseif (defined('HELP_CENTER_SERVER_URL')) {
    Tygh::$app['view']->assign('help_center_server_url', HELP_CENTER_SERVER_URL);
}

$auth = & Tygh::$app['session']['auth'];

$helpdesk_user_id = empty($auth['helpdesk_user_id']) ? 0 : $auth['helpdesk_user_id'];
$license_number = Registry::get('settings.Upgrade_center.license_number');

Tygh::$app['view']->assign('help_center_timestamp_last_view', fn_get_storage_data('help_center_last_view_' . $auth['user_id'], 0));

if (!$helpdesk_user_id || !$license_number) {
    return [CONTROLLER_STATUS_OK];
}

$customer_care_data = fn_get_storage_data('help_center_customer_care_data', null);

if (empty($auth['help_center_get_customer_care_timestamp'])) {
    $auth['help_center_get_customer_care_timestamp'] = TIME;
}

if ($auth['help_center_get_customer_care_timestamp'] + HELP_CENTER_CUSTOMER_CARE_REFRESH_INTERVAL < TIME) {
    $auth['help_center_get_customer_care_timestamp'] = TIME + HELP_CENTER_CUSTOMER_CARE_REFRESH_INTERVAL;

    $service_url = Registry::get('config.helpdesk.url');

    $data = [
        'dispatch'       => 'help_center.get_support_data',
        'user_id'        => $helpdesk_user_id,
        'license_number' => $license_number,
    ];

    $logging = Http::$logging;
    Http::$logging = false;

    $new_customer_care_data = Http::get($service_url, $data, ['execution_timeout' => HELP_CENTER_CUSTOMER_CARE_EXECUTION_TIMEOUT]);

    Http::$logging = $logging;

    if (Http::getStatus() === Http::STATUS_OK) {
        $_old_customer_care_data = @json_decode($customer_care_data, true);
        $_new_customer_care_data = @json_decode($new_customer_care_data, true);
        if (isset($_new_customer_care_data['tickets']) && is_array($_new_customer_care_data['tickets'])) {
            $is_tickets_changed = false;

            foreach ($_new_customer_care_data['tickets'] as $ticket_id => $ticket_data) {
                if (
                    isset($_old_customer_care_data['tickets'][$ticket_id]['hash'], $ticket_data['hash'])
                    && $_old_customer_care_data['tickets'][$ticket_id]['hash'] !== $ticket_data['hash']
                ) {
                    $is_tickets_changed = true;
                    break;
                }
            }

            if ($is_tickets_changed) {
                $url = $service_url . '/index.php?dispatch=communication.tickets';

                fn_set_notification(
                    NotificationSeverity::NOTICE,
                    '',
                    __(
                        'help_center.customer_care.few_tickets_require_your_attention',
                        [
                            '[url]'    => $url,
                            '[target]' => '_blank',
                        ]
                    ),
                    'S'
                );

                /** @var \Tygh\NotificationsCenter\NotificationsCenter $notifications_center */
                $notifications_center = Tygh::$app['notifications_center'];

                $notifications_center->add([
                    'user_id'       => $auth['user_id'],
                    'title'         => __('help_center.help_center'),
                    'message'       => __(
                        'help_center.customer_care.few_tickets_require_your_attention',
                        [
                            '[url]'    => '#',
                            '[target]' => '',
                        ]
                    ),
                    'area'          => SiteArea::ADMIN_PANEL,
                    'section'       => NotificationsCenter::SECTION_ADMINISTRATION,
                    'tag'           => 'help_center.customer_care',
                    'action_url'    => $url,
                    'language_code' => Registry::get('settings.Appearance.backend_default_language'),
                ]);

                fn_set_storage_data('help_center_customer_care_data_last_update', TIME);
            }
        }

        $customer_care_data = $new_customer_care_data;
        fn_set_storage_data('help_center_customer_care_data', $customer_care_data);
    }
}

Tygh::$app['view']->assign('help_center_customer_care_data', @json_decode($customer_care_data, true));
Tygh::$app['view']->assign('help_center_customer_care_data_last_update', fn_get_storage_data('help_center_customer_care_data_last_update', false));

return [CONTROLLER_STATUS_OK];
