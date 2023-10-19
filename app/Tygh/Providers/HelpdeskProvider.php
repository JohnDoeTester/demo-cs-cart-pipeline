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

namespace Tygh\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Enum\SiteArea;
use Tygh\Helpdesk\AuthProvider;
use Tygh\Helpdesk\AuthService;
use Tygh\Helpdesk\AuthStorage\DatabaseStorage;
use Tygh\Helpdesk\AuthStorage\RuntimeStorage;
use Tygh\Helpdesk\LicenseActivateMailRequester;
use Tygh\Helpdesk\PermissionService;
use Tygh\Http;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Tools\Url;
use Tygh\Tygh;

class HelpdeskProvider implements ServiceProviderInterface
{
    /**
     * @param \Pimple\Container $app Application container
     *
     * @return void
     */
    public function register(Container $app)
    {
        $app['helpdesk.config'] = static function () {
            return Registry::get('config')['helpdesk'];
        };

        $app['helpdesk.service_url'] = static function () {
            return rtrim(self::getConfig()['url'], '/');
        };

        $app['helpdesk.auth_provider.factory'] = static function ($app) {
            $config = static::getConfig();
            $service_url = static::getServiceUrl();

            return static function ($redirect_url) use ($config, $service_url, $app) {
                $redirect_uri = fn_url('helpdesk_connector.oauth_process');
                if ($redirect_url !== null) {
                    $redirect_uri = fn_link_attach($redirect_uri, 'return_url=' . urlencode($redirect_url));
                }

                return new AuthProvider(
                    $config['client_id'],
                    $config['client_secret'],
                    $service_url . '/authorize',
                    $service_url . '/token',
                    $service_url . '/profile',
                    $app['session'],
                    $redirect_uri
                );
            };
        };

        $app['helpdesk.auth_service'] = static function ($app) {
            return new AuthService([
                new RuntimeStorage($app['session']),
                new DatabaseStorage($app['db']),
            ]);
        };

        $app['helpdesk.permission_service'] = static function ($app) {
            $admin_address = fn_url('', SiteArea::ADMIN_PANEL);
            $store_domain = parse_url($admin_address, PHP_URL_HOST);

            return new PermissionService(
                Registry::get('user_info'),
                $store_domain,
                new Http(),
                self::getServiceUrl(),
                Settings::instance()->getValue('license_number', 'Upgrade_center'),
                Registry::ifGet('config.tweaks.show_helpdesk_logs', false)
            );
        };

        $app['helpdesk.connect_url'] = static function ($app) {
            return Url::buildUrn(
                ['helpdesk_connector', 'oauth_process'],
                ['return_url' => Registry::get('config.current_url')]
            );
        };

        $app['helpdesk.disconnect_url'] = static function ($app) {
            return Url::buildUrn(
                ['helpdesk_connector', 'oauth_disconnect'],
                ['return_url' => Registry::get('config.current_url')]
            );
        };

        $app['helpdesk.connected_account_reporter'] = static function ($app) {
            return static function () {
                $connected_helpdesk_accounts = db_get_fields(
                    'SELECT helpdesk_user_id FROM ?:users WHERE helpdesk_user_id <> 0'
                );
                if (!$connected_helpdesk_accounts) {
                    return;
                }

                self::getPermissionService()->reportConnection($connected_helpdesk_accounts);
            };
        };

        $app['helpdesk.license_activate_mail_requester'] = static function ($app) {
            return new LicenseActivateMailRequester(
                Registry::get('user_info'),
                self::getServiceUrl(),
                new Http(),
                Registry::get('settings.Upgrade_center.license_number')
            );
        };
    }

    /**
     * @return array<string, string>
     */
    private static function getConfig()
    {
        return Tygh::$app['helpdesk.config'];
    }

    /**
     * @return string
     */
    public static function getServiceUrl()
    {
        return Tygh::$app['helpdesk.service_url'];
    }

    /**
     * @param string|null $redirect_url URL in the store to redirect a customer to
     *
     * @return \Tygh\Helpdesk\AuthProvider
     */
    public static function getAuthProvider($redirect_url = null)
    {
        /** @var callable $factory */
        $factory = Tygh::$app['helpdesk.auth_provider.factory'];

        return $factory($redirect_url);
    }

    /**
     * @return \Tygh\Helpdesk\AuthService
     */
    public static function getAuthService()
    {
        return Tygh::$app['helpdesk.auth_service'];
    }

    /**
     * @return \Tygh\Helpdesk\PermissionService
     */
    public static function getPermissionService()
    {
        return Tygh::$app['helpdesk.permission_service'];
    }

    /**
     * @return \Closure
     */
    public static function getAccountConnectionReporter()
    {
        return Tygh::$app['helpdesk.connected_account_reporter'];
    }

    /**
     * @return \Tygh\Helpdesk\LicenseActivateMailRequester
     */
    public static function getLicenseActivateMailRequester()
    {
        return Tygh::$app['helpdesk.license_activate_mail_requester'];
    }
}
