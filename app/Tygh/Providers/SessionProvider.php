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
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Storage;
use Tygh\Tools\Url;
use Tygh\Web\Session;

/**
 * Class SessionProvider is used to register session-related components at Application container.
 *
 * @package Tygh\ServiceProviders
 *
 * phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
 */
class SessionProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        // Session component
        $app['session'] = static function ($app) {
            $session = new Session($app);

            // Configure conditions of session start
            if ((defined('NO_SESSION') && NO_SESSION) || (defined('CONSOLE') && CONSOLE)) {
                $session->start_on_init = false;
                $session->start_on_read = false;
                $session->start_on_write = false;

                return $session;
            }

            $name_suffix = '_' . substr(md5(Registry::get('config.http_location')), 0, 5);

            if (defined('HTTPS') && Registry::ifGet('config.tweaks.secure_cookies', false)) {
                $name_suffix = '_s' . $name_suffix;
                $session->cookie_secure = true;
            }

            // Configure session component
            $session->setSessionNamePrefix('sid_');
            $session->setSessionNameSuffix($name_suffix);
            $session->setName(ACCOUNT_TYPE);
            $session->setSessionIDSuffix('-' . AREA);

            $session->cache_limiter = 'nocache';
            $session->cookie_lifetime = SESSIONS_STORAGE_ALIVE_TIME;
            $cookie_path = Registry::ifGet('config.current_path', '');
            if ($cookie_path === '') {
                $cookie_path = '/';
            }
            $session->cookie_path = $cookie_path;

            $https_location = new Url(Registry::get('config.https_location'));
            $http_location = new Url(Registry::get('config.http_location'));

            // We shouldn't set secure subdomain as a cookie domain because it will cause
            // two SID cookies with the same name but different domains
            if (defined('HTTPS') && !$https_location->isSubDomainOf($http_location)) {
                $cookie_domain_host = $https_location->getHost();
            } else {
                $cookie_domain_host = $http_location->getHost();
            }

            if (($pos = strpos($cookie_domain_host, '.')) !== false) {
                $cookie_domain_host = $pos === 0 ? $cookie_domain_host : '.' . $cookie_domain_host;
            } else {
                // For local hosts set this to empty value
                $cookie_domain_host = '';
            }

            if (!preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $cookie_domain_host, $matches)) {
                $cookie_domain = $cookie_domain_host;
            } else {
                $cookie_domain = ini_get('session.cookie_domain');
            }

            $session->cookie_domain = $cookie_domain;

            $session->start_on_init = true;
            $session->start_on_read = false;
            $session->start_on_write = false;

            $session->gc_handlers[] = self::getLogUserLogoutGCHandler();
            $session->gc_handlers[] = self::getCustomFilesGCHandler();
            $session->writability_handlers[] = self::getWritabilityHandlerByReadOnlySchema();

            return $session;
        };

        // Session data storage driver class
        $app['session.storage.class'] = static function () {
            $storage_class = Registry::ifGet('config.session_backend', 'database');
            $storage_class = '\\Tygh\\Backend\\Session\\' . ucfirst($storage_class);

            return $storage_class;
        };

        // Session data storage driver instance
        $app['session.storage'] = static function ($app) {
            return new $app['session.storage.class'](
                Registry::get('config'),
                [
                    'ttl'         => SESSION_ALIVE_TIME,
                    'ttl_storage' => SESSIONS_STORAGE_ALIVE_TIME,
                    'ttl_online'  => SESSION_ONLINE
                ]
            );
        };
    }

    /**
     * Gets garbage collector handler for log user logout
     *
     * @return \Closure
     */
    private static function getLogUserLogoutGCHandler()
    {
        return static function ($gc_period_in_seconds) {
            $current_time = time();
            $last_activity_time = $current_time - SESSION_ALIVE_TIME;

            $users = db_get_array(
                'SELECT user_id, last_login FROM ?:users'
                . ' WHERE last_activity > ?i AND last_activity < ?i',
                $last_activity_time - $gc_period_in_seconds,
                $last_activity_time
            );

            if (empty($users)) {
                return;
            }

            $are_sttings_exists = Registry::isExist('settings');

            if (!$are_sttings_exists) {
                Registry::set('settings.Logging.log_type_users', [
                    'session' => YesNo::YES
                ]);
            }

            foreach ($users as $user) {
                fn_log_user_logout([
                    'user_id'    => (int) $user['user_id'],
                    'this_login' => (int) $user['last_login'],
                ]);
            }

            if (!$are_sttings_exists) {
                Registry::del('settings');
            }
        };
    }

    /**
     * Gets garbage collector handler for custom files
     *
     * @return \Closure
     */
    private static function getCustomFilesGCHandler()
    {
        return static function () {
            // Delete custom files (garbage) from unlogged customers
            $files = Storage::instance('custom_files')->getList('sess_data');

            if (empty($files)) {
                return;
            }

            foreach ($files as $file) {
                $fdate = fileatime(Storage::instance('custom_files')->getAbsolutePath('sess_data/' . $file));

                if ($fdate >= (TIME - SESSIONS_STORAGE_ALIVE_TIME)) {
                    continue;
                }

                Storage::instance('custom_files')->delete('sess_data/' . $file);
            }
        };
    }

    /**
     * Gets writability handler by read_only schema.
     *
     * @return \Closure
     */
    private static function getWritabilityHandlerByReadOnlySchema()
    {
        return static function () {
            $controller = Registry::get('runtime.controller');
            $mode = Registry::get('runtime.mode');

            if (!$controller) {
                return true;
            }

            $read_only_session_schema = fn_get_schema('session', 'read_only');

            if (
                isset($read_only_session_schema['controllers'][$controller])
                && YesNo::isTrue($read_only_session_schema['controllers'][$controller])
                || isset($read_only_session_schema['controllers'][$controller]['modes'][$mode])
                && YesNo::isTrue($read_only_session_schema['controllers'][$controller]['modes'][$mode])
            ) {
                return false;
            }

            return true;
        };
    }
}
