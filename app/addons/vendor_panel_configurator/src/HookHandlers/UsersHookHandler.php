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

namespace Tygh\Addons\VendorPanelConfigurator\HookHandlers;

use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Registry;

class UsersHookHandler
{
    /**
     * The "init_user_session_data" hook handler.
     *
     * Actions performed:
     * - Notifies admin that vendor panel looks different when acting as vendor.
     *
     * @param array|\Tygh\Web\Session $sess_data        Session data
     * @param int                     $user_id          User identifier
     * @param bool                    $skip_cart_saving Whether cart data should be saved on session initialization
     *
     * @psalm-var array{
     *   auth: array{
     *     act_as_user?: bool,
     *     user_type?: string,
     *     area?: string,
     *   },
     *   notifications?: array<
     *     string, array{
     *       type: string,
     *       title: string,
     *       message: string,
     *       message_state: string,
     *       new: bool,
     *       extra: string,
     *       init_message: bool,
     *     }
     *   >
     * } $sess_data
     *
     * @return void
     *
     * @see \fn_init_user_session_data()
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function onInitSessionData(&$sess_data, $user_id, $skip_cart_saving)
    {
        if (
            empty($sess_data['auth']['act_as_user'])
            || empty($sess_data['auth']['user_type'])
            || empty($sess_data['auth']['act_as_area'])
            || !UserTypes::isVendor($sess_data['auth']['user_type'])
            || !SiteArea::isVendor($sess_data['auth']['act_as_area'])
        ) {
            return;
        }

        $sess_data['notifications'] = empty($sess_data['notifications'])
            ? []
            : $sess_data['notifications'];
        $return_url = fn_url('', SiteArea::VENDOR_PANEL);

        $sess_data['notifications'][md5(TIME)] = [
            'type'          => NotificationSeverity::NOTICE,
            'title'         => __('notice'),
            'message'       => __(
                'vendor_panel_configurator.configure_vendor_panel_notice',
                [
                    '[block_manager_link]' => fn_url(
                        'customization.update_mode?type=block_manager&status=enable&return_url=' . $return_url,
                        SiteArea::VENDOR_PANEL
                    ),
                    '[theme_editor_link]' => fn_url(
                        'customization.update_mode?type=theme_editor&status=enable&return_url=' . $return_url,
                        SiteArea::VENDOR_PANEL
                    ),
                ]
            ),
            'message_state' => 'K',
            'new'           => true,
            'extra'         => '',
            'init_message'  => false,
        ];
    }

    /**
     * The "login_user_post" hook handler.
     *
     * Actions performed:
     * - Notifies that vendor panel looks different when loging in as vendor on demo site.
     *
     * @param int    $user_id   User identifier
     * @param int    $cu_id     Cart user identifier
     * @param array  $udata     User data
     * @param array  $auth      Authentication data
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $result    Result user login
     *
     * @return void
     *
     * @see \fn_login_user()
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function onLoginUserPost($user_id, $cu_id, array $udata, array $auth, $condition, $result)
    {
        if (
            !$result || empty($user_id)
            || !Registry::ifGet('config.demo_mode', false)
            || empty($auth['user_type']) || !UserTypes::isVendor($auth['user_type'])
            || !empty($auth['act_as_user'])
            || empty($auth['area']) || !SiteArea::isAdmin($auth['area'])
        ) {
            return;
        }

        $return_url = fn_url('', SiteArea::VENDOR_PANEL);
        fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __(
            'vendor_panel_configurator.configure_vendor_panel_notice',
            [
                '[block_manager_link]' => fn_url(
                    'customization.update_mode?type=block_manager&status=enable&return_url=' . $return_url,
                    SiteArea::VENDOR_PANEL
                ),
                '[theme_editor_link]' => fn_url(
                    'customization.update_mode?type=theme_editor&status=enable&return_url=' . $return_url,
                    SiteArea::VENDOR_PANEL
                ),
            ]
        ));
    }
}
