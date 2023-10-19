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

use Tygh\Ajax;
use Tygh\Api;
use Tygh\Api\Response;
use Tygh\BlockManager\Layout;
use Tygh\Debugger;
use Tygh\Development;
use Tygh\Embedded;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Enum\StorefrontStatuses;
use Tygh\Enum\YesNo;
use Tygh\Exceptions\InitException;
use Tygh\Exceptions\PHPErrorException;
use Tygh\Languages\Languages;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\SmartyEngine\Core as SmartyCore;
use Tygh\Storage;
use Tygh\Themes\Styles;
use Tygh\Tools\DateTimeHelper;
use Tygh\Enum\UserTypes;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Init template engine
 *
 * @param string $area
 *
 * @return array
 * @throws \SmartyException
 * @throws \Tygh\Exceptions\PermissionsException
 */
function fn_init_templater($area = AREA)
{
    $auth = Tygh::$app['session']['auth'];
    $view = new SmartyCore();
    \SmartyException::$escape = false;

    /**
     * Change templater pre-init parameters
     *
     * @param object $view Templater object
     */
    fn_set_hook('init_templater', $view);

    $view->_dir_perms = DEFAULT_DIR_PERMISSIONS;
    $view->_file_perms = DEFAULT_FILE_PERMISSIONS;

    $view->registerResource('tygh', new Tygh\SmartyEngine\FileResource());

    // resource for shared templates loaded from backend
    $view->registerResource('backend', new Tygh\SmartyEngine\BackendResource());

    if ($area == 'A') {

        if (!empty($auth['user_id']) && fn_allowed_for('ULTIMATE')) {
            // Enable sharing for objects
            $view->registerFilter('output', array('Tygh\SmartyEngine\Filters', 'outputSharing'));
        }

        $view->registerFilter('pre', array('Tygh\SmartyEngine\Filters', 'preScript'));
    }

    if ($area == 'C') {
        $view->registerFilter('pre', array('Tygh\SmartyEngine\Filters', 'preTemplateWrapper'));

        if (Registry::get('runtime.customization_mode.design')) {
            $view->registerFilter('output', array('Tygh\SmartyEngine\Filters', 'outputTemplateIds'));
        }

        if (Registry::get('runtime.customization_mode.live_editor')) {
            $view->registerFilter('output', array('Tygh\SmartyEngine\Filters', 'outputLiveEditorWrapper'));
        }

        $view->registerFilter('output', array('Tygh\SmartyEngine\Filters', 'outputScript'));
    }

    if (Embedded::isEnabled()) {
        $view->registerFilter('output', array('Tygh\SmartyEngine\Filters', 'outputEmbeddedUrl'));
    }

    // CSRF form protection
    if (fn_is_csrf_protection_enabled($auth)) {
        $view->registerFilter('output', array('Tygh\SmartyEngine\Filters', 'outputSecurityHash'));
    }

    // Language variable retrieval optimization
    $view->registerFilter('variable_pre', ['Tygh\SmartyEngine\Filters', 'preTranslation']);
    $view->registerFilter('post', ['Tygh\SmartyEngine\Filters', 'postTranslation']);

    $smarty_plugins_dir = $view->getPluginsDir();
    $view->setPluginsDir(Registry::get('config.dir.functions') . 'smarty_plugins');
    $view->addPluginsDir($smarty_plugins_dir);

    $view->error_reporting = E_ALL & ~E_NOTICE;

    if (version_compare(phpversion(), '8.0.0', '>=')) {
        $view->error_reporting &= ~E_WARNING;
    }

    if (version_compare(phpversion(), '8.1.0', '>=')) {
        $view->error_reporting &= ~E_DEPRECATED;
    }

    $view->registerDefaultPluginHandler(['Tygh\SmartyEngine\Filters', 'smartyDefaultHandler']);

    $view->setArea($area);
    $view->use_sub_dirs = false;
    $view->compile_check = (Development::isEnabled('compile_check') || Debugger::isActive() || fn_is_development()) ? true : false;
    $view->setLanguage(CART_LANGUAGE);

    $view->assign('ldelim', '{');
    $view->assign('rdelim', '}');

    $view->assign('currencies', Registry::get('currencies'), false);
    $view->assign('primary_currency', CART_PRIMARY_CURRENCY, false);
    $view->assign('secondary_currency', CART_SECONDARY_CURRENCY, false);
    $view->assign('languages', Registry::get('languages'));

    if (SiteArea::isAdmin($area)) {
        $lang_params = [
            'status' => [
                ObjectStatuses::ACTIVE,
                ObjectStatuses::HIDDEN
            ]
        ];
        $view->assign('menu_languages', Languages::get($lang_params));
        $view->assign('addon_permissions_text', fn_get_addon_permissions_text());
    }

    $view->assign('localizations', fn_get_localizations(CART_LANGUAGE, true));
    if (defined('CART_LOCALIZATION')) {
        $view->assign('localization', fn_get_localization_data(CART_LOCALIZATION));
    }

    if (defined('THEMES_PANEL')) {
        if (fn_allowed_for('ULTIMATE')) {
            $storefronts = db_get_array('SELECT storefront, company, company_id FROM ?:companies');
            Registry::set('demo_theme.storefronts', $storefronts);
        }
        $view->assign('demo_theme', Registry::get('demo_theme'));
    }

    $view->assignByRef('app', Tygh::$app);
    Tygh::$app['view'] = $view;

    /**
     * Change templater parameters
     *
     * @param object $view Templater object
     */
    fn_set_hook('init_templater_post', $view);

    return array(INIT_STATUS_OK);
}

/**
 * Init crypt engine
 *
 * @return boolean always true
 */
function fn_init_crypt()
{
    Tygh::$app['crypt'] = function () {
        return new Crypt_Blowfish(Registry::get('config.crypt_key'));
    };

    return true;
}

/**
 * Init ajax engine
 *
 * @return array
 */
function fn_init_ajax()
{
    if (defined('AJAX_REQUEST')) {
        return array(INIT_STATUS_OK);
    }

    Embedded::init();

    if (Ajax::validateRequest($_REQUEST)) {
        Tygh::$app['ajax'] = new Ajax($_REQUEST);
        fn_define('AJAX_REQUEST', true);
    }

    return array(INIT_STATUS_OK);
}

/**
 * Init languages
 *
 * @param array  $params request parameters
 * @param string $area
 *
 * @return array
 */
function fn_init_language($params, $area = AREA)
{
    $default_language = Registry::get('settings.Appearance.' . fn_get_area_name($area) . '_default_language');
    $session_display_language = fn_get_session_data('cart_language' . $area);
    $session_description_language = fn_get_session_data('descr_sl');

    $languages_search_params = [
        'area'           => $area,
        'include_hidden' => $area !== 'C',
    ];

    $avail_languages = Registry::getOrSetCache(
        ['init_language', 'init_language_' . $area],
        ['languages', 'storefronts_languages'],
        ['static', 'storefront'],
        static function () use ($languages_search_params) {
            return Languages::getAvailable($languages_search_params);
        }
    );

    $display_language = null;
    if (
        !empty($params['sl'])
        && (
            !empty($avail_languages[$params['sl']])
            || SiteArea::isAdmin($area)
        )
    ) {
        $display_language = $params['sl'];
    } elseif ($session_display_language && !empty($avail_languages[$session_display_language])) {
        $display_language = $session_display_language;
    } elseif ($browser_language = fn_get_browser_language($avail_languages)) {
        $display_language = $browser_language;
    } elseif (!empty($avail_languages[$default_language])) {
        $display_language = $default_language;
    } else {
        reset($avail_languages);
        $display_language = key($avail_languages);
    }

    // For the backend, set description language
    $description_language = null;
    if (!empty($params['descr_sl']) && !empty($avail_languages[$params['descr_sl']])) {
        $description_language = $params['descr_sl'];
        fn_set_session_data('descr_sl', $params['descr_sl'], COOKIE_ALIVE_TIME);
    } elseif ($session_description_language && !empty($avail_languages[$session_description_language])) {
        $description_language = $session_description_language;
    } else {
        $description_language = $display_language;
    }

    /**
     * Executes after the display language and the description language are determined right before setting them,
     * allows you change the languages that will be used and the list of available languages
     *
     * @param array  $params                   Request parameters
     * @param string $area                     Site area identifer (A for the admininstration panel, C for storefront)
     * @param string $default_language         Two-letter default language code
     * @param string $session_display_language Display language stored in the session
     * @param array  $avail_languages          List of available languages
     * @param string $display_language         Display language
     * @param string $description_language     Description language
     * @param string $browser_language         Browser language
     */
    fn_set_hook(
        'init_language_post',
        $params,
        $area,
        $default_language,
        $session_display_language,
        $avail_languages,
        $display_language,
        $description_language,
        $browser_language
    );

    if ($display_language !== $session_display_language) {
        fn_set_session_data('cart_language' . $area, $display_language, COOKIE_ALIVE_TIME);

        // set language_changed flag only if $session_language was set before
        if (Embedded::isEnabled() && defined('AJAX_REQUEST') && $session_display_language) {
            Tygh::$app['ajax']->assign('language_changed', true);
        }
    }

    Registry::set('languages', $avail_languages);
    fn_define('CART_LANGUAGE', $display_language);
    fn_define('DESCR_SL', $description_language);

    return array(INIT_STATUS_OK);
}

/**
 * Init company data
 * Company data array will be saved in the registry runtime.company_data
 *
 * @param array $params request parameters
 *
 * @return array with init data (init status, redirect url in case of redirect)
 */
function fn_init_company_data($params)
{
    $company_data = array(
        'company' => __('all_vendors'),
    );

    $company_id = Registry::get('runtime.company_id');
    if ($company_id) {
        $company_data = fn_get_company_data($company_id);
    }

    fn_set_hook('init_company_data', $params, $company_id, $company_data);

    Registry::set('runtime.company_data', $company_data);

    return array(INIT_STATUS_OK);
}

/**
 * Init selected company
 * Selected company id will be saved in the registry runtime.company_id
 *
 * @param array $params request parameters
 *
 * @return array with init data (init status, redirect url in case of redirect)
 */
function fn_init_company_id(&$params)
{
    $company_id = 0;
    $available_company_ids = array();
    $result = array(INIT_STATUS_OK);

    if (isset($params['switch_company_id'])) {
        $switch_company_id = intval($params['switch_company_id']);
    } else {
        $switch_company_id = false;
    }

    $all_available_company_ids = Registry::getOrSetCache(
        'all_available_company_ids',
        ['companies'],
        'static',
        static function () {
            $ids = fn_get_available_company_ids();
            return array_combine($ids, $ids);
        }
    );

    if (defined('API')) {
        $api = Tygh::$app['api'];
        $api_response_status = false;
        if ($api instanceof Api) {
            if (AREA == 'A') {
                if ($user_data = $api->getUserData()) {
                    $company_id = 0;

                    if ($user_data['company_id']) {
                        $company_id = $user_data['company_id'];
                    }

                    $store = array();
                    if (preg_match('/(stores|vendors)\/(\d+)\/.+/', $api->getRequest()->getResource(), $store)) {

                        if ($company_id && $company_id != $store[2]) {
                            $api_response_status = Response::STATUS_FORBIDDEN;
                        }

                        $company_id = intval($store[2]);
                        if (!isset($all_available_company_ids[$company_id])) {
                            $company_id = 0;
                        }
                    }
                } else {
                    $api_response_status = Response::STATUS_UNAUTHORIZED;
                }
            }
        } else {
            $api_response_status = Response::STATUS_FORBIDDEN;
        }

        if ($api_response_status) {
            $response = new Response($api_response_status);
            /**
             * Here is exit.
             */
            $response->send();
        }
    }
    // set company_id for vendor's admin
    if (AREA == 'A' && !empty(Tygh::$app['session']['auth']['company_id'])) {
        $company_id = intval(Tygh::$app['session']['auth']['company_id']);
        $available_company_ids = array($company_id);
        if (!isset($all_available_company_ids[$company_id])) {
            return fn_init_company_id_redirect($params, 'access_denied');
        }
    }

    // admin switching company_id
    if (!$company_id) {
        if ($switch_company_id !== false) { // request not empty
            if ($switch_company_id) {
                if (isset($all_available_company_ids[$switch_company_id])) {
                    $company_id = $switch_company_id;
                } else {
                    return fn_init_company_id_redirect($params, 'company_not_found');
                }
            }
            fn_set_session_data('company_id', $company_id, COOKIE_ALIVE_TIME);
        } else {
            $company_id = fn_init_company_id_find_in_session();
        }
    }

    if (empty($available_company_ids)) {
        $available_company_ids = array_values($all_available_company_ids);
    }

    fn_set_hook('init_company_id', $params, $company_id, $available_company_ids, $result);

    if (AREA === 'A') {
        fn_init_storefronts_stats($company_id);
    }

    Registry::set('runtime.company_id', $company_id);
    Registry::set('runtime.companies_available_count', count($available_company_ids));
    Registry::resetCacheCompanyId();

    unset($params['switch_company_id']);

    return $result;
}

/**
 * Form error notice and make redirect. Used in fn_init_company_id
 *
 * @param array  $params              request parameters
 * @param string $message             language variable name for message
 * @param int    $redirect_company_id New company id for redirecting, if null, company id saved in session will be used
 *
 * @return array with init data (init status, redirect url in case of redirect)
 */
function fn_init_company_id_redirect(&$params, $message, $redirect_company_id = null)
{
    $redirect_url = '';

    if ('access_denied' == $message) {
        Tygh::$app['session']['auth'] = array();
        $redirect_url = 'auth.login_form' . (!empty($params['return_url']) ? '?return_url=' . urldecode($params['return_url']) : '');
    } elseif ('company_not_found' == $message) {
        $dispatch = !empty($params['dispatch']) ? $params['dispatch'] : 'auth.login_form';
        unset($params['dispatch']);
        $params['switch_company_id'] = (null === $redirect_company_id) ? fn_init_company_id_find_in_session() : $redirect_company_id;

        $redirect_url = $dispatch . '?' . http_build_query($params);
    }

    if (!defined('CART_LANGUAGE')) {
        fn_init_language($params); // we need CART_LANGUAGE in Tygh\Languages\Values::getLangVar()
        fn_init_currency($params); // we need CART_SECONDARY_CURRENCY in Tygh\Languages\Values::getLangVar()
        $params['dispatch'] = 'index.index'; // we need dispatch in Tygh\Languages\Values::getLangVar()
    }
    fn_set_notification('E', __('error'), __($message));

    return array(INIT_STATUS_REDIRECT, $redirect_url);
}

/**
 * Tryes to find company id in session
 *
 * @return int Company id if stored in session, 0 otherwise
 */
function fn_init_company_id_find_in_session()
{
    $session_company_id = intval(fn_get_session_data('company_id'));
    if ($session_company_id && !fn_get_available_company_ids($session_company_id)) {
        fn_delete_session_data('company_id');
        $session_company_id = 0;
    }

    return $session_company_id;
}

/**
 * Init currencies
 *
 * @param array  $params request parameters
 * @param string $area   Area ('A' for admin or 'C' for customer)
 * @param string $account_type Current user account type
 *
 * @return array
 */
function fn_init_currency($params, $area = AREA, $account_type = null)
{
    if ($area !== SiteArea::STOREFRONT
        && $account_type === null
        && defined('ACCOUNT_TYPE')
    ) {
        $account_type = ACCOUNT_TYPE;
    }

    /**
     * Performs actions before initializing currencies
     *
     * @param array  $params request parameters
     * @param string $area   Area ('A' for admin or 'C' for customer)
     */
    fn_set_hook('init_currency_pre', $params, $area, $account_type);

    $currency_params = [
        'status' => [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN]
    ];

    if ($account_type === 'vendor') {
        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        $storefronts = $repository->findByCompanyId(Registry::get('runtime.company_id'), false);
        if ($storefronts) {
            $currency_params['storefront_id'] = array_column($storefronts, 'storefront_id');
        }
    }

    $currencies = Registry::getOrSetCache(
        ['init_currency', 'init_currency' . md5(serialize($currency_params))],
        [
            'currencies',
            'currency_descriptions',
            'storefronts_currencies'
        ],
        ['static', 'storefront', 'lang'],
        static function () use ($currency_params, $area) {
            return fn_get_currencies_list($currency_params, $area, CART_LANGUAGE);
        }
    );

    $primary_currency = '';

    foreach ($currencies as $v) {
        if ($v['is_primary'] == 'Y') {
            $primary_currency = $v['currency_code'];
            break;
        }
    }

    if (empty($primary_currency)) { // Restore primary currency if it empty
        $primary_currencies = fn_get_currencies_list(
            [
                'only_primary'  => true,
                'raw_query'     => true,
                'storefront_id' => false
            ],
            $area,
            CART_LANGUAGE
        );
        foreach ($primary_currencies as $key => $currency) {
            $primary_currencies[$key]['status'] = 'H'; // Hide unavailable currencies
        }
        $currencies = fn_sort_array_by_key($currencies + $primary_currencies, 'position');
        $primary_currency = key($primary_currencies);
    }

    $default_storefront_currency = Registry::get('settings.Appearance.frontend_default_currency');

    if (!empty($params['currency']) && !empty($currencies[$params['currency']])) {
        $secondary_currency = $params['currency'];
    } elseif (($c = fn_get_session_data('secondary_currency' . $area)) && !empty($currencies[$c])) {
        $secondary_currency = $c;
    } elseif (!empty($currencies[$default_storefront_currency])) {
        $secondary_currency = $default_storefront_currency;
    } else {
        $secondary_currency = $primary_currency;
    }

    if (empty($secondary_currency)) {
        reset($currencies);
        $secondary_currency = key($currencies);
    }

    if ($secondary_currency != fn_get_session_data('secondary_currency' . $area)) {
        fn_set_session_data('secondary_currency' . $area, $secondary_currency, COOKIE_ALIVE_TIME);
    }

    // Hide secondary currency in frontend if it is hidden
    if ($area == 'C' && $currencies[$secondary_currency]['status'] != 'A') {
        $first_currency = [];
        foreach ($currencies as $key => $currency) {
            if ($currency['status'] != 'A' && $currency['is_primary'] != 'Y') {
                unset($currencies[$key]);
            } elseif ($currency['status'] == 'A' && !$first_currency) {
                $first_currency = $currency;
            }
        }

        if ($first_currency) {
            $secondary_currency = $first_currency['currency_code'];
        }
    }

    /**
     * Sets currencies
     *
     * @param array  $params             request parameters
     * @param string $area               Area ('A' for admin or 'C' for customer)
     * @param string $primary_currency   Primary currency code
     * @param string $secondary_currency Secondary currency code
     * @param array  $currencies         List of all currencies
     */
    fn_set_hook('init_currency_post', $params, $area, $primary_currency, $secondary_currency, $currencies);

    define('CART_PRIMARY_CURRENCY', $primary_currency);
    define('CART_SECONDARY_CURRENCY', $secondary_currency);

    Registry::set('currencies', $currencies);

    return array(INIT_STATUS_OK);
}

/**
 * Init layout
 *
 * @param array $params request parameters
 *
 * @return array
 */
function fn_init_layout($params)
{
    if (defined('SKIP_LAYOUT_INIT')) {
        return [INIT_STATUS_OK];
    }

    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];

    $storefront_id = $storefront->storefront_id;

    $embedded_suffix = Embedded::isEnabled()
        ? '_embedded'
        : '';
    $key_name = "stored_layout_{$storefront_id}{$embedded_suffix}";
    $stored_layout = fn_get_session_data($key_name);

    if (!empty($params['s_layout'])) {
        $stored_layout = $params['s_layout'];

        fn_set_session_data($key_name, $params['s_layout']);
    }

    // Replace default theme with selected for current area
    if (!empty($stored_layout)) {
        $layout = Layout::instance(0, [], $storefront_id)->get($stored_layout);

        if (!isset($layout['theme_name'])
            || $layout['theme_name'] !== fn_get_theme_path('[theme]', 'C', null, true, $storefront_id)
        ) {
            unset($layout);
        }
    }

    if (empty($layout)) {
        $layout = Layout::instance(0, [], $storefront_id)->getDefault(); // get default
    }

    $available_styles = Styles::factory($layout['theme_name'])->getList(array(
        'short_info' => true
    ));

    if (!isset($available_styles[$layout['style_id']])) {
        $layout['style_id'] = Styles::factory($layout['theme_name'])->getDefault();
    }

    /**
     * Executes before storing viewed layout data into Registry, allows to modify layout data.
     *
     * @param array $params Request parameters
     * @param array $layout Layout data
     */
    fn_set_hook('init_layout', $params, $layout);

    Registry::set('runtime.layout', $layout);

    return [INIT_STATUS_OK];
}

/**
 * Init user
 *
 * @param string $area Current area
 *
 * @return array{int}
 */
function fn_init_user($area = AREA)
{
    $session = Tygh::$app['session'];
    $user_info = [];

    if (!empty($session['auth']['user_id'])) {
        $user_info = fn_get_user_short_info($session['auth']['user_id']);
        if (empty($user_info)) { // user does not exist in the database, but exists in session
            $session['auth'] = [];
        } else {
            $session['auth']['usergroup_ids'] = fn_define_usergroups([
                'user_id'   => $session['auth']['user_id'],
                'user_type' => $user_info['user_type']
            ]);

            // If the user has change the password or company, log out from other devices
            if (
                (isset($session['auth']['password_change_timestamp'], $user_info['password_change_timestamp'])
                    && (int) $session['auth']['password_change_timestamp'] !== (int) $user_info['password_change_timestamp'])
                || ((isset($session['auth']['company_id'], $user_info['company_id'])
                    && (int) $session['auth']['company_id'] !== (int) $user_info['company_id']))
            ) {
                $session['auth'] = [];
                $session->regenerateID();
            }
        }
    }

    $first_init = false;
    if (empty($session['auth'])) {
        $user_id = fn_get_session_data($area . '_user_id');

        if (defined('CONSOLE') && SiteArea::isAdmin($area)) {
            $user_id = db_get_field('SELECT user_id FROM ?:users WHERE user_type = ?s AND is_root = ?s AND company_id = 0', UserTypes::ADMIN, YesNo::YES);
        }

        if ($user_id) {
            fn_define('LOGGED_VIA_COOKIE', true);
        }

        fn_login_user($user_id);

        if (!defined('NO_SESSION')) {
            $session['cart'] = isset($session['cart']) ? $session['cart'] : [];
        }

        if (
            (defined('LOGGED_VIA_COOKIE') && !empty($session['auth']['user_id']))
            || ($cu_id = fn_get_session_data('cu_id'))
        ) {
            $first_init = true;

            if (!empty($cu_id)) {
                fn_define('COOKIE_CART', true);
            }

            // Cleanup cached shipping rates
            unset($session['shipping_rates']);

            $_utype = empty($session['auth']['user_id']) ? 'U' : 'R';
            $_uid = empty($session['auth']['user_id']) ? $cu_id : $session['auth']['user_id'];

            fn_extract_cart_content($session['cart'], $_uid, 'C', $_utype);
            fn_save_cart_content($session['cart'], $_uid, 'C', $_utype);

            if (!empty($session['auth']['user_id'])) {
                $session['cart']['user_data'] = fn_get_user_info($session['auth']['user_id']);
                $user_info = fn_get_user_short_info($session['auth']['user_id']);
            }
        }
    }

    if (fn_is_expired_storage_data('cart_products_next_check', SECONDS_IN_HOUR * 12)) {
        db_query("DELETE FROM ?:user_session_products WHERE user_type = 'U' AND timestamp < ?i", (TIME - SECONDS_IN_DAY * 30));
    }

    // If administrative account has usergroup, it means the access restrictions are in action
    if (
        !empty($session['auth']['usergroup_ids'])
        && SiteArea::isAdmin($area)
        && !UserTypes::isVendor($session['auth']['user_type']) // vendor cannot be restricted
    ) {
        fn_define('RESTRICTED_ADMIN', true);
    }

    if (
        !empty($session['customization']['modes'])
        && (
            SiteArea::isStorefront($area)
            && (int) Registry::get('runtime.storefront_id') === (int) $session['customization']['storefront_id']
            || (
                !empty($session['auth']['act_as_user']) && !empty($session['auth']['act_as_area'])
                && UserTypes::isVendor($session['auth']['user_type']) && SiteArea::isVendor($session['auth']['act_as_area'])
                || Registry::ifGet('config.demo_mode', false)
            )
        )
    ) {
        Registry::set('runtime.customization_mode', array_filter($session['customization']['modes']));

        if (Embedded::isEnabled()) {
            Registry::set('runtime.customization_mode.live_editor', false);
        }
    }

    if (
        !empty($user_info['user_id'])
        && (empty($session['auth']['last_activity']) || $session['auth']['last_activity'] < strtotime('-5 minutes'))
    ) {
        $session['auth']['last_activity'] = time();
        db_query('UPDATE ?:users SET last_activity = ?i WHERE user_id = ?i', $session['auth']['last_activity'], $user_info['user_id']);
    }

    /**
     * Executes after user initialization
     *
     * @param array $session['auth'] Current user session data
     * @param array $user_info       User information
     * @param bool  $first_init      True if stored in session data used to log in the user
     */
    fn_set_hook('user_init', $session['auth'], $user_info, $first_init);

    Registry::set('user_info', $user_info);

    return [INIT_STATUS_OK];
}

/**
 * Init localizations
 *
 * @param array $params request parameters
 *
 * @return array
 */
function fn_init_localization($params)
{
    if (AREA != 'C') {
        return array(INIT_STATUS_OK);
    }

    $locs = db_get_hash_array("SELECT localization_id, custom_weight_settings, weight_symbol, weight_unit FROM ?:localizations WHERE status = 'A'", 'localization_id');

    if (!empty($locs)) {
        if (!empty($_REQUEST['lc']) && !empty($locs[$_REQUEST['lc']])) {
            $cart_localization = $_REQUEST['lc'];

        } elseif (($l = fn_get_session_data('cart_localization')) && !empty($locs[$l])) {
            $cart_localization = $l;

        } else {
            $_ip = fn_get_ip(true);
            $_country = fn_get_country_by_ip($_ip['host']);
            $_lngs = db_get_hash_single_array("SELECT lang_code, 1 as 'l' FROM ?:languages WHERE status = 'A'", array('lang_code', 'l'));
            $_language = fn_get_browser_language($_lngs);

            $cart_localization = db_get_field("SELECT localization_id, COUNT(localization_id) as c FROM ?:localization_elements WHERE (element = ?s AND element_type = 'C') OR (element = ?s AND element_type = 'L') GROUP BY localization_id ORDER BY c DESC LIMIT 1", $_country, $_language);

            if (empty($cart_localization) || empty($locs[$cart_localization])) {
                $cart_localization = db_get_field("SELECT localization_id FROM ?:localizations WHERE status = 'A' AND is_default = 'Y'");
            }
        }

        if (empty($cart_localization)) {
            reset($locs);
            $cart_localization = key($locs);
        }

        if ($cart_localization != fn_get_session_data('cart_localization')) {
            fn_set_session_data('cart_localization', $cart_localization, COOKIE_ALIVE_TIME);
        }

        if ($locs[$cart_localization]['custom_weight_settings'] == 'Y') {
            Registry::set('config.localization.weight_symbol', $locs[$cart_localization]['weight_symbol']);
            Registry::set('config.localization.weight_unit', $locs[$cart_localization]['weight_unit']);
        }

        fn_define('CART_LOCALIZATION', $cart_localization);
    }

    return array(INIT_STATUS_OK);
}

/**
 * Detect user agent
 *
 * @return array
 */
function fn_init_ua()
{
    static $crawlers = array(
        'google',
        'bot',
        'yahoo',
        'spider',
        'archiver',
        'curl',
        'python',
        'nambu',
        'Twitterbot',
        'perl',
        'sphere',
        'PEAR',
        'java',
        'wordpress',
        'radian',
        'crawl',
        'yandex',
        'eventbox',
        'monitor',
        'mechanize',
        'facebookexternal',
        'bingpreview',
    );

    $http_ua = fn_strtolower($_SERVER['HTTP_USER_AGENT']);

    if (strpos($http_ua, 'shiretoko') !== false || strpos($http_ua, 'firefox') !== false) {
        $ua = 'firefox';
    } elseif (strpos($http_ua, 'chrome') !== false) {
        $ua = 'chrome';
    } elseif (strpos($http_ua, 'safari') !== false) {
        $ua = 'safari';
    } elseif (strpos($http_ua, 'opera') !== false) {
        $ua = 'opera';
    } elseif (
        strpos($http_ua, 'msie') !== false
        || (
            strpos($http_ua, 'trident/7.0;') !== false
            && strpos($http_ua, 'rv:11.0') !== false
        )
    ) {
        $ua = 'ie';
        Registry::set('runtime.unsupported_browser', true);
    } else {
        $ua = 'unknown';
    }

    if (preg_match('/(' . implode('|', $crawlers) . ')/', $http_ua, $m)) {
        $ua = 'crawler';
        fn_define('CRAWLER', $m[1]);
        fn_define('NO_SESSION', true); // do not start session for crawler
    }

    if (isset($_REQUEST['no_session']) && $_REQUEST['no_session'] === 'Y') {
        fn_define('NO_SESSION', true);
    }

    fn_define('USER_AGENT', $ua);

    return array(INIT_STATUS_OK);
}

/**
 * @param array $params
 *
 * @return array
 */
function fn_check_cache($params)
{
    $dir_root = Registry::get('config.dir.root') . '/';

    if (isset($params['ct']) && ((AREA == 'A' && !(fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id'))) || Debugger::isActive() || fn_is_development())) {
        Storage::instance('images')->deleteDir('thumbnails');
    }

    // Clean up cache
    if (isset($params['cc']) && ((AREA == 'A' && !(fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id'))) || Debugger::isActive() || fn_is_development())) {
        fn_clear_cache();
    }

    // Clean up templates cache
    if (isset($params['ctpl']) && ((AREA == 'A' && !(fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id'))) || Debugger::isActive() || fn_is_development())) {
        fn_clear_template_cache();
    }

    if (!in_array(AREA, array('A', 'V'))) {
        return array(INIT_STATUS_OK);
    }

    /* Add extra files for cache checking if needed */
    $core_hashes = array(
        '47e4664f2628c1cad1676279637ee055f8c5ad72' => [
            'file'   => 'cuc.xfrqcyrU/utlG/ccn',
            'notice' => 'nqzva_cnary_jvyy_or_oybpxrq'
        ],
        '7f569494ab3a41730dbc76ea2a544408106107f7' => [
            'file'   => 'cuc.8sgh/ergeriabp_ynergvy/fnzrupf/ccn',
            'notice' => 'nqzva_cnary_jvyy_or_oybpxrq'
        ],
    );

    foreach ($core_hashes as $hash => $file) {

        if ($hash != sha1_file($dir_root . strrev(str_rot13($file['file'])))) {
            if (filemtime($dir_root . strrev(str_rot13($file['file']))) < TIME - SECONDS_IN_DAY * 2) { // 2-days cache
                Tygh::$app['cache']->regenerate($hash, $file['file']);
            }

            fn_process_cache_notifications($file['notice']);

            break;
        }
    }

    return array(INIT_STATUS_OK);
}

/**
 * @return array
 */
function fn_init_settings()
{
    // FIXME: Settings probably should be cached by [static, storefront] only.
    Registry::registerCache('settings', [
        'settings_objects',
        'settings_vendor_values',
        'settings_descriptions',
        'settings_sections',
        'settings_variants'
    ], Registry::cacheLevel(['static', 'storefront', 'company']));

    if (Registry::isExist('settings') == false) {
        $settings = Settings::instance()->getValues();

        //initialization remote settings for compatibility with third-party addons
        //deprecated settings
        $default_elements = $settings['Appearance']['admin_elements_per_page'];
        $settings['Appearance']['admin_products_per_page'] = $default_elements;
        $settings['Appearance']['admin_orders_per_page'] = $default_elements;
        $settings['Appearance']['admin_pages_per_page'] = $default_elements;

        //settings were moved to Checkout from General, mapping for backward compatibility
        $checkout_setting = [
            'order_start_id',
            'tax_calculation',
            'min_order_amount',
            'allow_anonymous_shopping',
            'checkout_redirect',
            'estimate_shipping_cost',
            'default_address',
            'default_zipcode',
            'default_city',
            'default_country',
            'default_state',
            'default_phone'
        ];

        foreach ($checkout_setting as $setting) {
            $settings['General'][$setting] = $settings['Checkout'][$setting];
        }

        Registry::set('settings', $settings);
    }

    fn_init_time_zone(Registry::get('settings.Appearance.timezone'));

    fn_define('DEFAULT_LANGUAGE', Registry::get('settings.Appearance.backend_default_language'));

    return [INIT_STATUS_OK];
}

/**
 * Sets the given timezone as the PHP runtime timezone and as the current MySQL connection timezone.
 *
 * @param string $time_zone_name The name of a timezone like "Europe/London"
 */
function fn_init_time_zone($time_zone_name)
{
    $valid_timezone_identifiers = timezone_identifiers_list();

    if (is_array($valid_timezone_identifiers) && in_array($time_zone_name, $valid_timezone_identifiers)) {
        date_default_timezone_set($time_zone_name);
        $offset_string = DateTimeHelper::getTimeZoneOffsetString($time_zone_name);

        if ($offset_string) {
            db_query('SET time_zone = ?s', $offset_string);
        } else {
            error_log("Unable to get timezone offset for {$time_zone_name}");
        }
    }
}

/**
 * Initialize all enabled addons
 *
 * @return array
 */
function fn_init_addons()
{
    // FIXME: Settings probably should be cached by [static, storefront] only.
    Registry::registerCache('addons', [
        'addons',
        'settings_objects',
        'settings_vendor_values',
        'settings_descriptions',
        'settings_sections',
        'settings_variants'
    ], Registry::cacheLevel(['static', 'storefront', 'company']));

    if (Registry::isExist('addons') == false) {
        $_addons = db_get_hash_array('SELECT addon, priority, status, unmanaged FROM ?:addons WHERE 1 ORDER BY priority', 'addon');

        foreach ($_addons as $k => $v) {
            $_addons[$k] = Settings::instance()->getValues($v['addon'], Settings::ADDON_SECTION, false);
            if ($_addons[$k] === false) {
                $_addons[$k] = [];
            }

            unset($_addons[$k]['status']);
            if (fn_check_addon_snapshot($k)) {
                $_addons[$k]['status'] = $v['status'];
            } else {
                $_addons[$k]['status'] = ObjectStatuses::DISABLED;
            }
            $_addons[$k]['is_disabled'] = false;
            if (fn_is_addon_init_disabled($v['addon'])) {
                $_addons[$k]['status'] = ObjectStatuses::DISABLED;
                $_addons[$k]['is_disabled'] = true;
            }
            $_addons[$k]['priority'] = $v['priority'];
            $_addons[$k]['unmanaged'] = $v['unmanaged'];
        }

        Registry::set('addons', $_addons);
    }

    foreach ((array) Registry::get('addons') as $addon_name => $data) {
        if (empty($data['status'])) {
            // FIX ME: Remove me
            error_log("ERROR: Addons initialization: Bad '$addon_name' addon data:" . serialize($data) . " Addons Registry:" . serialize(Registry::get('addons')));
        } elseif ($data['status'] === ObjectStatuses::ACTIVE) {
            fn_load_addon($addon_name);
        }
    }

    Registry::set('addons_initiated', true, true);

    return [INIT_STATUS_OK];
}

/**
 * Initialize unmanaged addons
 *
 * @return array{0: int} INIT_STATUS_OK
 */
function fn_init_unmanaged_addons()
{
    $addons = Registry::getOrSetCache('unmanaged_addons', ['addons'], 'static', static function () {
        return db_get_fields(
            'SELECT addon FROM ?:addons WHERE unmanaged = 1 AND status = ?s ORDER BY priority',
            ObjectStatuses::ACTIVE
        );
    });

    foreach ($addons as $addon_name) {
        fn_load_addon($addon_name);
    }

    return [INIT_STATUS_OK];
}

/**
 * @param array $request
 *
 * @return array
 */
function fn_init_full_path($request)
{
    // Display full paths cresecure payment processor
    if (isset($request['display_full_path']) && ($request['display_full_path'] == 'Y')) {
        define('DISPLAY_FULL_PATHS', true);
        Registry::set('config.full_host_name', (defined('HTTPS') ? 'https://' . Registry::get('config.https_host') : 'http://' . Registry::get('config.http_host')));
    } else {
        Registry::set('config.full_host_name', '');
    }

    return array(INIT_STATUS_OK);
}

/**
 * @return bool
 */
function fn_init_stack()
{
    $stack = Registry::get('init_stack');
    if (empty($stack)) {
        $stack = array();
    }

    $stack_data = func_get_args();

    foreach ($stack_data as $data) {
        $stack[] = $data;
    }

    Registry::set('init_stack', $stack);

    return true;
}

/**
 * Run init functions
 *
 * @param array $request $_REQUEST global variable
 *
 * @return bool always true
 * @throws \Tygh\Exceptions\InitException
 */
function fn_init(&$request)
{
    $allow_external_redirect = false;

    // New init functions can be added to stack while init
    while ($stack = Registry::get('init_stack')) {
        $function_data = array_shift($stack);
        $function = array_shift($function_data);

        // Remove function from stack
        Registry::set('init_stack', $stack);

        if (!is_callable($function)) {
            continue;
        }

        $result = call_user_func_array($function, $function_data);

        $status = !empty($result[0]) ? $result[0] : INIT_STATUS_OK;
        $url = !empty($result[1]) ? $result[1] : '';
        $message = !empty($result[2]) ? $result[2] : '';
        $permanent = !empty($result[3]) ? $result[3] : '';
        $allow_external_redirect = !empty($result[4]) ? $result[4] : false;

        if ($status == INIT_STATUS_OK && !empty($url)) {
            $redirect_url = $url;

        } elseif ($status == INIT_STATUS_REDIRECT && !empty($url)) {
            $redirect_url = $url;
            break;

        } elseif ($status == INIT_STATUS_FAIL) {
            if (empty($message)) {
                $message = 'Initialization failed in <b>' . (is_array($function) ? implode('::', $function) : $function) . '</b> function';
            }

            throw new InitException($message);
        }
    }

    if (!empty($redirect_url)) {
        if (!defined('CART_LANGUAGE')) {
            fn_init_language($request); // we need CART_LANGUAGE in fn_url function that called in fn_redirect
        }
        fn_redirect($redirect_url, $allow_external_redirect, !empty($permanent));
    }

    Debugger::init(true);

    return true;
}

/**
 * Init paths for storage store data (mse, saas)
 *
 * @return array{0: int} INIT_STATUS_OK
 */
function fn_init_storage()
{
    fn_set_hook('init_storage');

    $storage = Registry::getOrSetCache(
        'init_storage_settings',
        ['settings_objects'],
        'static',
        static function () {
            $storage = Settings::instance([
                'company_id'               => 0,
                'storefront_id'            => 0,
                'has_multiple_storefronts' => false,
                'area'                     => AREA,
            ])->getValue('storage', '');

            return unserialize($storage);
        }
    );

    Registry::set('runtime.storage', $storage);

    return [INIT_STATUS_OK];
}

/**
 * Init api object and put it to Application container.
 */
function fn_init_api()
{
    Tygh::$app['api'] = new Api();

    return array(INIT_STATUS_OK);
}

/**
 * Registers image manipulation library object at Application container.
 *
 * @return array
 */
function fn_init_imagine()
{
    Tygh::$app['image'] = function () {
        $driver = Registry::ifGet('config.tweaks.image_resize_lib', 'gd');

        if ($driver == 'auto') {
            try {
                return new Imagine\Imagick\Imagine();
            } catch (\Exception $e) {
                try {
                    return new Imagine\Gd\Imagine();
                } catch (\Exception $e) {
                    return null;
                }
            }
        } else {
            switch ($driver) {
                case 'gd':
                    return new Imagine\Gd\Imagine();
                    break;
                case 'imagick':
                    return new Imagine\Imagick\Imagine();
                    break;
            }
        }

        return null;
    };

    return array(INIT_STATUS_OK);
}

/**
 * Registers archiver object at Application container.
 *
 * @return array
 */
function fn_init_archiver()
{
    Tygh::$app['archiver'] = function () {
        return new \Tygh\Tools\Archiver();
    };

    return array(INIT_STATUS_OK);
}

/**
 * Registers custom error handlers
 *
 * @return array
 */
function fn_init_error_handler()
{
    // Fatal error handler
    defined('AREA') && AREA == 'C' && register_shutdown_function(function () {
        $error = error_get_last();

        // Check whether error is fatal (i.e. couldn't have been catched with trivial error handler)
        if (isset($error['type']) &&
            in_array($error['type'], array(
                E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING
            ))
        ) {
            // Try to hide PHP's fatal error message
            fn_clear_ob();

            $exception = new PHPErrorException($error['message'], $error['type'], $error['file'], $error['line']);
            $exception->output();

            exit(1);
        }
    });

    // Non-fatal errors, warnings and notices are caught and properly formatted
    defined('DEVELOPMENT')
    && DEVELOPMENT
    && !extension_loaded('xdebug')
    && set_error_handler(function($code, $message, $filename, $line) {
        if (error_reporting() & $code) {
            switch ($code) {
                // Non-fatal errors, code execution wouldn't be stopped
                case E_NOTICE:
                case E_USER_NOTICE:
                case E_WARNING:
                case E_USER_WARNING:
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    $exception = new PHPErrorException($message, $code, $filename, $line);
                    $exception->output();

                    error_log(addslashes((string) $exception), 0);

                    return true;
                break;
            }
        }

        // Let PHP's internal error handler handle other cases
        return false;
    });

    return array(INIT_STATUS_OK);
}

/**
 * Provides rules of applying regional redirection to specific controllers.
 *
 * When the "Redirect visitors of this storefront to the one that has countries to which the visitors' IP addresses
 * belong defined" setting is enabled for a storefront, any request will be automatically redirected to the proper
 * storefront based on location.
 *
 * While being conveniet for customers, this behaviour, however, should be disabled for some controllers:
 * e.g. requests to payment_notification shouldn't be redirected as they are intended for use with the specific store.
 *
 * @return array Array where controller names are keys and bool flags are values indicating whether regional
 *               redirection should be applied to a controller. Regional redirection applies to all controllers by
 *               default unless otherwise stated
 */
function fn_get_regional_redirection_rules()
{
    return [
        'payment_notification' => false,
    ];
}

/**
 * Redirects a customer to a regional storefront when it's necessary.
 *
 * @param array  $request Request parameters
 * @param string $url     Requested URL
 *
 * @return array Initialization status
 *
 * @internal
 */
function fn_init_redirect_to_regional_storefront($request, $url)
{
    if (SiteArea::isAdmin(AREA) || defined('API')) {
        return [INIT_STATUS_OK];
    }

    /** @var \Tygh\Storefront\Repository $repository */
    $repository = Tygh::$app['storefront.repository.init'];
    $current_storefront = $repository->findByUrl($url) ?: $repository->findDefault();

    $do_redirect = !defined('CRAWLER')
        && empty($_SERVER['PHP_AUTH_USER'])
        && $current_storefront->redirect_customer
        && !fn_get_cookie('storefront_redirect_' . $current_storefront->storefront_id);

    if ($do_redirect && isset($request['dispatch'])) {
        $dispatch = is_array($request['dispatch']) ? key($request['dispatch']) : $request['dispatch'];
        list($controller, ) = explode('.', str_replace('/', '.', $dispatch));
        $redirection_rules = fn_get_regional_redirection_rules();
        $do_redirect = !(isset($redirection_rules[$controller]) && $redirection_rules[$controller] === false);
    }

    if ($do_redirect) {
        $ip = fn_get_ip(true);
        $country_code = fn_get_country_by_ip($ip['host']);

        $target_storefront = null;
        if (!empty($country_code)) {
            /** @var \Tygh\Storefront\Storefront[] $regional_storefronts */
            list($regional_storefronts,) = $repository->find(['country_codes' => [$country_code]]);
            foreach ($regional_storefronts as $storefront) {
                if ($storefront->storefront_id !== $current_storefront->storefront_id) {
                    $target_storefront = $storefront;
                    break;
                }
            }
        }

        if ($target_storefront) {
            $url = 'http://' . $target_storefront->url;
            fn_set_cookie('storefront_redirect_' . $current_storefront->storefront_id, true);

            // FIXME: company ID must be initialized for redirection process to work
            if (fn_allowed_for('ULTIMATE')) {
                list($params['switch_company_id']) = $current_storefront->getCompanyIds();
                fn_init_company_id($params);
            }

            return [INIT_STATUS_REDIRECT, $url, '', '', true];
        }
    }

    return [INIT_STATUS_OK];
}

/**
 * Calculates closed storefronts statistics and saves to runtime storage.
 *
 * @param int $company_id
 *
 * @return array Initialization status
 *
 * @internal
 */
function fn_init_storefronts_stats($company_id = null)
{
    /** @var \Tygh\Storefront\Repository $repository */
    $repository = Tygh::$app['storefront.repository'];

    /** @var \Tygh\Storefront\Storefront[] $storefronts */
    if ($company_id && fn_allowed_for('MULTIVENDOR')) {
        list($storefronts,) = $repository->find(['get_total' => false, 'company_ids' => [$company_id], 'is_search' => true]);
    } else {
        list($storefronts,) = $repository->find(['get_total' => false]);
    }

    $storefronts_count = count($storefronts);
    $is_multiple_storefronts = ($storefronts_count > 1);
    $have_closed_storefronts = false;
    $are_all_storefronts_closed = true;
    $is_current_storefront_closed = false;
    $access_key = '';

    if ($company_id === null && fn_allowed_for('ULTIMATE')) {
        $company_id = Registry::get('runtime.company_id');
    }

    foreach ($storefronts as $storefront_key => $storefront) {
        if ($storefront->status === StorefrontStatuses::CLOSED) {
            $have_closed_storefronts = true;
            if (fn_allowed_for('ULTIMATE') && $company_id && in_array($company_id, $storefront->getCompanyIds())
                || $storefronts_count === 1
                || fn_allowed_for('MULTIVENDOR') && $storefront->is_default
            ) {
                $access_key = $storefront->access_key;
                $is_current_storefront_closed = true;
            }
        } else {
            $are_all_storefronts_closed = false;
        }
    }

    Registry::set('runtime.storefront_access_key', $access_key);
    Registry::set('runtime.storefronts_count', $storefronts_count);
    Registry::set('runtime.is_multiple_storefronts', $is_multiple_storefronts);
    Registry::set('runtime.is_current_storefront_closed', $is_current_storefront_closed);
    Registry::set('runtime.are_all_storefronts_closed', $are_all_storefronts_closed);
    Registry::set('runtime.have_closed_storefronts', $have_closed_storefronts);

    return [INIT_STATUS_OK];
}

/**
 * Detects host, path and location by the current Storefront and stores them in the Registry cache.
 *
 * @param array  $request Request parameters
 * @param string $url     Requested URL
 *
 * @return array Initialization status
 *
 * @internal
 */
function fn_init_http_params_by_storefront(&$request, $url)
{
    if (AREA === 'A') {
        return [INIT_STATUS_OK];
    }

    /** @var \Tygh\Storefront\Repository $repository */
    $repository = Tygh::$app['storefront.repository.init'];

    if (defined('CONSOLE') && isset($request['switch_storefront_id'])) {
        $storefront = $repository->findById($request['switch_storefront_id']) ?: $repository->findDefault();
        unset($request['switch_storefront_id']);
    } else {
        $storefront = $repository->findByUrl($url) ?: $repository->findDefault();
    }

    $parsed_url = parse_url('//' . $storefront->url);
    /** @psalm-suppress PossiblyUndefinedArrayOffset */
    $host = $parsed_url['host'];

    $path = isset($parsed_url['path']) ? rtrim($parsed_url['path'], '/') : '';

    if (isset($parsed_url['port'])) {
        $host .= ':' . $parsed_url['port'];
    }

    $config = Registry::get('config');

    $config['origin_http_location'] = 'http://' . $config['http_host'] . $config['http_path'];
    $config['origin_https_location'] = 'https://' . $config['https_host'] . $config['https_path'];

    $config['http_path'] = $config['https_path'] = $config['current_path'] = $path;
    $config['http_host'] = $config['https_host'] = $config['current_host'] = $host;

    $config['http_location'] = 'http://' . $host . $path;
    $config['https_location'] = 'https://' . $host . $path;

    $config['current_location'] = defined('HTTPS')
        ? $config['https_location']
        : $config['http_location'];

    // FIXME: #Storefronts: Company ID initialization in storefront is something special
    if (fn_allowed_for('ULTIMATE')) {
        $storefront_companies = $storefront->getCompanyIds();
        $request['switch_company_id'] = reset($storefront_companies);
    }

    Registry::set('config', $config);
    /** @psalm-suppress PossiblyNullPropertyFetch */
    Registry::set('runtime.storefront_id', $storefront->storefront_id);

    return [INIT_STATUS_OK];
}

/**
 * Sends Content Security Policy http header
 *
 * @return array<int>
 */
function fn_init_http_content_security()
{
    if (defined('CONSOLE') || headers_sent()) {
        return [INIT_STATUS_OK];
    }

    $directives = fn_get_schema('security', 'http_content_policy');

    $frame_ancestors_tweak_list = Registry::get('config.tweaks.csp_frame_ancestors');
    $frame_ancestors_tweak_list = is_array($frame_ancestors_tweak_list) ? $frame_ancestors_tweak_list : [];

    $frame_ancestors_allows = $directives['frame-ancestors']['allow'];
    $directives['frame-ancestors']['allow'] = array_merge($frame_ancestors_allows, $frame_ancestors_tweak_list);

    $header = fn_build_content_security_policy_header($directives);

    if ($header) {
        header($header);
    }

    return [INIT_STATUS_OK];
}
