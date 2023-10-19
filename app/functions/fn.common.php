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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\BackendMenu;
use Tygh\BlockManager\Exim;
use Tygh\BlockManager\Layout;
use Tygh\Bootstrap;
use Tygh\Common\OperationResult;
use Tygh\Debugger;
use Tygh\Development;
use Tygh\Embedded;
use Tygh\Enum\BackendMenuLocations;
use Tygh\Enum\MoneyTransferTypes;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\OrderStatuses;
use Tygh\Enum\RegistrationFlowTypes;
use Tygh\Enum\ProfileFieldTypes;
use Tygh\Enum\ProfileTypes;
use Tygh\Enum\ReceiverSearchMethods;
use Tygh\Enum\SettingTypes;
use Tygh\Enum\SiteArea;
use Tygh\Enum\StorefrontStatuses;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;
use Tygh\Exceptions\DeveloperException;
use Tygh\Languages\Helper as LanguageHelper;
use Tygh\Languages\Languages;
use Tygh\Languages\Values as LanguageValues;
use Tygh\Less;
use Tygh\Notifications\Receivers\SearchCondition;
use Tygh\Providers\EventDispatcherProvider;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Storage;
use Tygh\Storefront\Storefront;
use Tygh\Themes\Styles;
use Tygh\Themes\Themes;
use Tygh\Tools\DateTimeHelper;
use Tygh\Tools\SecurityHelper;
use Tygh\Tools\Url;
use Tygh\Tygh;
use Tygh\Web\Antibot;
use Tygh\Web\Antibot\NullDriver;

/**
 * Returns True if the object can be saved, otherwise False.
 *
 * @param array $object_data Object data
 * @param string $object_type Object name
 * @param bool $skip_edition_checking Skip edition checking condition
 * @return bool Returns True if the object can be saved, otherwise False.
 */
function fn_allow_save_object($object_data, $object_type, $skip_edition_checking = false)
{
    /**
     * Perform actions before object checking
     *
     * @param array  $object_data Object data
     * @param string $object_type Object name
     */
    fn_set_hook('allow_save_object_pre', $object_data, $object_type);

    $allow = true;

    $selected_company_id = Registry::get('runtime.company_id');

    if ($skip_edition_checking) {
        if ($selected_company_id) {
            $allow = false;
        }

    } else {
        if (
            isset($object_data['company_id']) && $selected_company_id
            && $selected_company_id != $object_data['company_id']
        ) {
            $allow = false;
        }
    }

    /**
     * Perform actions after object checking
     *
     * @param array  $object_data Object data
     * @param string $object_type Object name
     * @param string $allow       True if the object can be saved, otherwise False.
     */
    fn_set_hook('allow_save_object_post', $object_data, $object_type, $allow);

    return $allow;
}

/**
 * Returns theme path in the required format
 *
 * Examples:
 * [themes] -> /var/www/design/themes/
 * [themes]/[theme] -> /var/www/design/themes/theme
 * [relative]/[theme] -> design/themes/theme
 * [repo]/[theme] -> /var/www/var/themes_repository/theme
 *
 * In format string:
 * [theme] will be replaced by actual theme name
 * [repo] will be replaced by real path to repository
 * [themes] will be replaced by real path of actual themes folder
 * [relative] will be replaced by path of actual themes folder relative root directory
 *
 * @param string   $path          Format string.
 * @param string   $area          Area (C/A) to get setting for
 * @param int|null $company_id    Company identifier.
 *                                This parameter is deprecated and will be removed in v5.0.0.
 *                                Use $storefront_id instead.
 * @param bool     $use_cache     Value will be get from the DB directly if use_cache is equal to false or from already
 *                                generated cache otherwise
 * @param int|null $storefront_id Storefront ID
 *
 * @return string Path to theme
 */
function fn_get_theme_path($path = '[theme]/', $area = AREA, $company_id = null, $use_cache = true, $storefront_id = null)
{
    static $theme_names = array();

    fn_set_hook('get_theme_path_pre', $path, $area, $company_id, $theme_names, $use_cache, $storefront_id);

    if ($area == 'A') {
        $theme_name = '';
        $dir_design = rtrim(Registry::get('config.dir.design_backend'), '/');

        if (strpos($path, '/[theme]/') !== false) { // FIXME THEMES: bad code
            $path = str_replace('/[theme]/', '/', $path);
        } elseif (strpos($path, '/[theme]') !== false) {
            $path = str_replace('/[theme]', '', $path);
        } elseif (strpos($path, '[theme]/') !== false) {
            $path = str_replace('[theme]/', '', $path);
        }
    } else {
        if (!$storefront_id) {
            /** @var \Tygh\Storefront\Storefront $storefront */
            $storefront = Tygh::$app['storefront'];
            $storefront_id = $storefront->storefront_id;
        }
        $storefront_id = (int) $storefront_id;

        if (empty($theme_names['c_' . $storefront_id]) || !$use_cache) {
            /** @var \Tygh\Storefront\Repository $repository */
            $repository = Tygh::$app['storefront.repository'];
            $storefront = $repository->findById($storefront_id);
            $theme_names['c_' . $storefront_id] = $storefront->theme_name;
        }

        $theme_name = $theme_names['c_' . $storefront_id];
        $dir_design = rtrim(Registry::get('config.dir.design_frontend'), '/');
    }

    $path = str_replace('[theme]', $theme_name, $path);
    $dir_repo = rtrim(Registry::get('config.dir.themes_repository'), '/');

    $path = str_replace('[relative]', str_replace(Registry::get('config.dir.root') . '/', '', $dir_design), $path);
    $path = str_replace('[themes]', $dir_design, $path);
    $path = str_replace('[repo]', $dir_repo, $path);

    fn_set_hook('get_theme_path', $path, $area, $dir_design, $company_id);

    return $path;
}

/**
 * Gets path for caching documents
 *
 * @param boolean $relative Flag that defines if path should be relative
 * @param string $area Area (C/A) to get setting for
 * @param integer $company_id Company identifier
 * @return string Path to files cache
 */
function fn_get_cache_path($relative = true, $area = AREA, $company_id = null)
{
    $path = Registry::get('config.dir.cache_misc');

    if ($relative) {
        $path = str_replace(Registry::get('config.dir.root') . '/', '', $path);
    }

    /**
     * Changes cache path
     *
     * @param string  $path       Path to files cache
     * @param boolean $relative   Flag that defines if flag should be relative
     * @param string  $area       Area (C/A) to get setting for
     * @param integer $company_id Company identifier
     */
    fn_set_hook('get_cache_path', $path, $relative, $area, $company_id);

    return $path;
}

/**
 * Prints any data like a print_r function
 * @param mixed ... Any data to be printed
 */
function fn_print_r()
{
    $args = func_get_args();

    $console = false;
    if (defined('CONSOLE') || empty($_SERVER['REQUEST_METHOD']) || defined('API')) {
        $console = true;
    }

    if ($console) {

        fn_echo(PHP_EOL);
        foreach ($args as $v) {
            fn_echo(print_r($v, true) . PHP_EOL);
        }
        fn_echo(PHP_EOL);

    } else {

        fn_echo('<ol style="font-family: Courier; font-size: 12px; border: 1px solid #dedede; background-color: #efefef; float: left; padding-right: 20px;">');
        foreach ($args as $v) {
            fn_echo('<li><pre>' . htmlspecialchars(print_r($v, true)) . "\n" . '</pre></li>');
        }
        fn_echo('</ol><div style="clear:left;"></div>');

        if (defined('AJAX_REQUEST')) {
            $ajax_vars = \Tygh\Registry::get('ajax')->getAssignedVars();
            if (!empty($ajax_vars['debug_info'])) {
                $args = array_merge($ajax_vars['debug_info'], $args);
            }
            \Tygh\Registry::get('ajax')->assign('debug_info', $args);
        }
    }
}

/**
 * Redirect browser to the new location
 *
 * @param string $location                Destination of redirect
 * @param bool   $allow_external_redirect Allow redirection to external resource
 * @param bool   $is_permanent            If true, perform 301 redirect
 */
function fn_redirect($location, $allow_external_redirect = false, $is_permanent = false)
{
    $external_redirect = false;
    $protocol = defined('HTTPS') ? 'https' : 'http';
    $meta_redirect = false;

    // Cleanup location from &amp; signs and call fn_url()
    $location = fn_url(str_replace(['&amp;', "\n", "\r"], ['&', '', ''], $location));

    // Convert absolute link with location to relative one
    if (strpos($location, '://') !== false || substr($location, 0, 7) === 'mailto:') {
        if (strpos($location, Registry::get('config.http_location') . '/') !== false) {
            $location = str_replace([Registry::get('config.http_location') . '/', Registry::get('config.http_location')], '', $location);
            $protocol = 'http';

        } elseif (strpos($location, Registry::get('config.https_location') . '/') !== false) {
            $location = str_replace([Registry::get('config.https_location') . '/', Registry::get('config.https_location')], '', $location);
            $protocol = 'https';

        } else {
            if ($allow_external_redirect === false) { // if external redirects aren't allowed, redirect to index script
                $location = '';
            } else {
                $external_redirect = true;
            }
        }

    // Convert absolute link without location to relative one
    } else {
        $_protocol = '';
        $_location = '';
        $http_path = Registry::get('config.http_path');
        $https_path = Registry::get('config.https_path');
        if (!empty($http_path) && substr($location, 0, strlen($http_path)) === $http_path) {
            $_location = substr($location, strlen($http_path) + 1);
            $_protocol = 'http';

        }
        if (!empty($https_path) && substr($location, 0, strlen($https_path)) === $https_path) {
            // if https path partially equal to http path check if https path is not just a part of http path
            // e. g. http://example.com/pathsimple & https://example.com/path
            if ($_protocol !== 'http' || empty($http_path) || substr($http_path, 0, strlen($https_path)) !== $https_path) {
                $_location = substr($location, strlen($https_path) + 1);
                $_protocol = 'https';
            }
        }
        $protocol = (Registry::get('config.http_path') !== Registry::get('config.https_path') && !empty($_protocol)) ? $_protocol : $protocol;
        $location = !empty($_protocol) ? $_location : $location;
    }

    if ($external_redirect === false) {
        fn_set_hook('redirect', $location);

        $protocol_changed = (defined('HTTPS') && $protocol === 'http') || (!defined('HTTPS') && $protocol === 'https');

        // For correct redirection, location must be absolute with path
        $location = (($protocol === 'http') ? Registry::get('config.http_location') : Registry::get('config.https_location')) . '/' . ltrim($location, '/');

        // Parse the query string
        $fragment = '';
        $query_array = [];
        $parsed_location = parse_url($location);
        if (!empty($parsed_location['query'])) {
            parse_str($parsed_location['query'], $query_array);
            $location = str_replace('?' . $parsed_location['query'], '', $location);
        }

        if (!empty($parsed_location['fragment'])) {
            $fragment = '#' . $parsed_location['fragment'];
            $location = str_replace($fragment, '', $location);
        }

        if ($protocol_changed && (Registry::get('config.http_host') !== Registry::get('config.https_host') || Registry::get('config.http_path') !== Registry::get('config.https_path'))) {
            $query_array[Tygh::$app['session']->getName()] = Tygh::$app['session']->getID();
        }

        // If this is not ajax request, remove ajax specific parameters
        if (!defined('AJAX_REQUEST')) {
            unset($query_array['is_ajax']);
            unset($query_array['result_ids']);
        } else {
            $query_array['result_ids'] = implode(',', Tygh::$app['ajax']->result_ids);
            $query_array['is_ajax'] = Tygh::$app['ajax']->redirect_type;
            $query_array['full_render'] = !empty($_REQUEST['full_render']) ? $_REQUEST['full_render'] : false;
            $query_array['callback'] = Tygh::$app['ajax']->callback;

            $ajax_assigned_vars = Tygh::$app['ajax']->getAssignedVars();
            if (!empty($ajax_assigned_vars['html'])) {
                unset($ajax_assigned_vars['html']);
            }
            $query_array['_ajax_data'] = $ajax_assigned_vars;
        }

        if (!empty($query_array)) {
            $location .= '?' . http_build_query($query_array) . $fragment;
        }

        // Redirect from https to http location
        if ($protocol_changed && defined('HTTPS')) {
            $meta_redirect = true;
        }
    }

    fn_set_hook('redirect_complete', $meta_redirect);

    if (!defined('AJAX_REQUEST') && Embedded::isEnabled()) {
        if (strpos($location, Registry::get('config.http_location')) === 0) {
            $location = str_replace(Registry::get('config.http_location'), '', $location);
        } elseif (strpos($location, Registry::get('config.https_location')) === 0) {
            $location = str_replace(Registry::get('config.https_location'), '', $location);
        }

        $location = Embedded::getUrl() . '#!' . urlencode($location);
        $meta_redirect = true;
    }

    if (defined('AJAX_REQUEST')) { // make in-script redirect during ajax request
        $_purl = parse_url($location);

        $_GET = [];
        $_POST = [];

        if (!empty($_purl['query'])) {
            parse_str($_purl['query'], $_GET);
        }
        $_REQUEST = Bootstrap::safeInput($_GET);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $_purl['path'];
        $_SERVER['QUERY_STRING'] = !empty($_purl['query']) ? $_purl['query'] : '';

        fn_get_route($_REQUEST);

        Registry::save(); // save registry cache to execute cleanup handlers
        fn_init_settings();
        fn_init_addons();

        // continue initialization process
        $stack = Registry::get('init_stack');
        if (!empty($stack)) {
            fn_init($_REQUEST);
        }

        Registry::clearCacheLevels();

        Tygh::$app['ajax']->updateRequest();

        fn_dispatch();
        return;

    } elseif (!ob_get_contents() && !headers_sent() && !$meta_redirect) {

        if ($is_permanent) {
            header('HTTP/1.0 301 Moved Permanently');
        }
        header('Location: ' . $location);
        exit;
    } else {
        $delay = ((Debugger::isActive() || fn_is_development()) && !Registry::get('runtime.comet')) ? 10 : 0;
        if ($delay != 0) {
            fn_echo('<a href="' . htmlspecialchars($location) . '" style="text-transform: lowercase;">continue</a>');
        }
        fn_echo('<meta http-equiv="Refresh" content="' . $delay . ';URL=' . htmlspecialchars($location) . '" />');
    }

    fn_flush();
    exit;
}

/**
 * Functions check if subarray with child exists in the given array
 *
 * @param array $data Array with nodes
 * @param string $childs_name Name of array with child nodes
 * @return boolean true if there are child subarray, false otherwise.
 */
function fn_check_second_level_child_array($data, $childs_name)
{
    foreach ($data as $l2) {
        if (!empty($l2[$childs_name]) && is_array($l2[$childs_name]) && count($l2[$childs_name])) {
            return true;
        }
    }

    return false;
}

/**
 * Sets notification message
 *
 * @param string $type          notification type (E - error, W - warning, N - notice, O - order error on checkout, I -
 *                              information)
 * @param string $title         notification title
 * @param string $message       notification message
 * @param string $message_state (S - notification will be displayed unless it's closed, K - only once, I - will be
 *                              closed by timer)
 * @param mixed  $extra         extra data to save with notification
 * @param bool   $init_message  $title and $message will be processed by __ function if true
 *
 * @return bool always true
 */
function fn_set_notification($type, $title, $message, $message_state = '', $extra = '', $init_message = false)
{
    // Back compabilities code
    if ($message_state === false) {
        $message_state = 'K';

    } elseif ($message_state === true) {
        $message_state = 'S';
    }
    // \back compabilities code

    /**
     * Сhanges the parameters of the notification
     *
     * @param string $type          notification type (E - error, W - warning, N - notice, O - order error on checkout, I - information)
     * @param string $title         notification title
     * @param string $message       notification message
     * @param string $message_state (S - notification will be displayed unless it's closed, K - only once, I - will be closed by timer)
     * @param mixed  $extra         extra data to save with notification
     * @param bool   $init_message  $title and $message will be processed by __ function if true
     */
    fn_set_hook('set_notification_pre', $type, $title, $message, $message_state, $extra, $init_message);

    if (empty($message_state) && $type == 'N') {
        $message_state = 'I';

    } elseif (empty($message_state)) {
        $message_state = 'K';
    }

    if (empty(Tygh::$app['session']['notifications'])) {
        Tygh::$app['session']['notifications'] = [];
    }

    $key = md5($type . $title . $message . $extra);

    Tygh::$app['session']['notifications'][$key] = [
        'type'          => $type,
        'title'         => $title,
        'message'       => $message,
        'message_state' => $message_state,
        'new'           => true,
        'extra'         => $extra,
        'init_message'  => $init_message,
    ];

    return true;
}

/**
 * Deletes notification message
 *
 * @param string $extra condition for "extra" parameter
 * @return boolean always true
 */
function fn_delete_notification($extra)
{
    if (!empty(Tygh::$app['session']['notifications'])) {
        foreach (Tygh::$app['session']['notifications'] as $k => $v) {
            if (!empty($v['extra']) && $v['extra'] == $extra) {
                unset(Tygh::$app['session']['notifications'][$k]);
            }
        }
    }

    return true;
}

/**
 * Checks if notification message exists
 * <i>$check_type</i> - type of notification existance checking
 * <ul>
 *      <li>any - checks if at least one notification exist</li>
 *      <li>extra - checks if notification with "extra" field equals to $value exist </li>
 *      <li>type - checks if at least one notification of certain type exist</li>
 * </ul>
 * @param string $check_type check type
 * @param string $value value to compare with
 * @return boolean true if notification exists, false - if not
 */
function fn_notification_exists($check_type, $value = '')
{
    if (!empty(Tygh::$app['session']['notifications'])) {
        if ($check_type == 'any') {
            return true;
        }

        foreach (Tygh::$app['session']['notifications'] as $k => $v) {
            if ($check_type == 'type' && $v['type'] == $value) {
                return true;
            }

            if ($check_type == 'extra' && !empty($v['extra']) && $v['extra'] == $value) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Gets notifications list
 *
 * @return array notifications list
 */
function fn_get_notifications()
{
    if (empty(Tygh::$app['session']['notifications'])) {
        Tygh::$app['session']['notifications'] = array();
    }

    $_notifications = array();

    foreach (Tygh::$app['session']['notifications'] as $k => $v) {
        if (!empty($v['init_message'])) {
            $placeholders = array();

            if (!empty($v['extra'])) {
                $extra = @unserialize($v['extra']);
                if (!empty($extra) && !empty($extra['placeholders'])) {
                    $placeholders = $extra['placeholders'];
                }
            }

            $v['title'] = __($v['title'], !empty($placeholders['title']) ? $placeholders['title'] : array());
            $v['message'] = __($v['message'], !empty($placeholders['message']) ? $placeholders['message'] : array());
        }

        // Display notification if this is not ajax request, or ajax request and notifiactions was just set
        if (!defined('AJAX_REQUEST') || (defined('AJAX_REQUEST') && $v['new'] == true)) {
            $_notifications[$k] = $v;
        }

        if ($v['message_state'] != 'S') {
            unset(Tygh::$app['session']['notifications'][$k]);
        } else {
            Tygh::$app['session']['notifications'][$k]['new'] = false; // preparing notification for display, reset new flag
        }
    }

    return $_notifications;
}

/**
 * Process' notifications of cache cleaning functionality
 *
 * @param string $notification String IDentifier
 * @return null
 */
function fn_process_cache_notifications($notification)
{
    fn_set_notification('W', 'warning', str_rot13($notification), 'K', serialize(array(
        'placeholders' => array(
            'message' => array(
                '[href]' => Registry::get('config.resources.helpdesk_url'),
            ),
        ),
    )), true);
}

/**
 * Saves data, posted to script via POST request in session to use it later
 *
 * @param string 1 or more keys of $_POST array
 * @return boolean always true
 */
function fn_save_post_data()
{
    Tygh::$app['session']['saved_post_data'] = array();
    $args = func_get_args();

    foreach ($args as $key) {
        if (isset($_POST[$key])) {
            Tygh::$app['session']['saved_post_data'][$key] = $_POST[$key];
        }
    }

    return true;
}

/**
 * Restores data, saved by fn_save_post_data function
 *
 * @param string $key key to restore
 * @return mixed restored data of success or null on failure
 */
function fn_restore_post_data($key)
{
    if (isset(Tygh::$app['session']['saved_post_data'][$key])) {
        $data = Tygh::$app['session']['saved_post_data'][$key];
        unset(Tygh::$app['session']['saved_post_data'][$key]);

        return $data;
    }

    return null;

}

/**
 * @deprecated will be removed in 5.0.1
 *
 * Gets language variable by name
 *
 * @param string $var_name Language variable name
 * @param string $lang_code 2-letter language code
 *
 * @return string Language variable value; in case the value is absent, language variable name with "_" prefix is
 *                returned
 */
function fn_get_lang_var($var_name, $lang_code = CART_LANGUAGE)
{
    return LanguageValues::getLangVar($var_name, $lang_code);
}

/**
 * @deprecated will be removed in 5.0.1
 *
 * Gets language variables by prefix
 *
 * @param string $prefix Language variable prefix
 * @param $lang_code 2-letter language code
 *
 * @return array of language variables
 */
function fn_get_lang_vars_by_prefix($prefix, $lang_code = CART_LANGUAGE)
{
    return LanguageValues::getLangVarsByPrefix($prefix, $lang_code);
}

/**
 * @deprecated will be removed in 5.0.1
 *
 * Loads received language variables into language cache
 *
 * @param array $var_names Language variable that to be loaded
 * @param string $lang_code 2-letter language code
 *
 * @return boolean True if any of received language variables were added into cache; false otherwise
 */
function fn_preload_lang_vars($var_names, $lang_code = CART_LANGUAGE)
{
    return LanguageHelper::preloadLangVars($var_names, $lang_code);
}

/**
 * @deprecatred
 * @param $tpl_var
 * @param $value
 */
function fn_update_lang_objects($tpl_var, &$value)
{
    return LanguageHelper::updateLangObjects($tpl_var, $value);
}

/**
 * Function defines and assigns pages
 *
 * @param array $params params to generate pagination from
 * @param string $area Area
 * @return array pagination structure
 */
function fn_generate_pagination($params, $area = AREA)
{
    if (empty($params['total_items']) || empty($params['items_per_page'])) {
        return array();
    }

    $deviation = ($area == 'A') ? 5 : 7;

    /**
     * This hook allows you to change the pagination data
     *
     * @param array  $params    Pagination data
     * @param string $area      One-letter area code
     * @param int    $deviation Number of pages to display before and after selected page
     */
    fn_set_hook('generate_pagination_pre', $params, $area, $deviation);

    $total_pages = ceil((int) $params['total_items'] / $params['items_per_page']);

    // Pagination in other areas displayed as in any search engine
    $page_from = fn_get_page_from($params['page'], $deviation);
    $page_to = fn_get_page_to($params['page'], $deviation, $total_pages);

    $pagination = array (
        'navi_pages' => range($page_from, $page_to),
        'prev_range' => ($page_from > 1) ? $page_from - 1 : 0,
        'next_range' => ($page_to < $total_pages) ? $page_to + 1: 0,
        'current_page' => $params['page'],
        'prev_page' => ($params['page'] > 1) ? $params['page'] - 1 : 0,
        'next_page' => ($params['page'] < $total_pages) ? $params['page'] + 1 : 0,
        'total_pages' => $total_pages,
        'total_items' => $params['total_items'],
        'items_per_page' => $params['items_per_page'],
        'per_page_range' => array(10, 25, 50, 100, 250),
        'range_from' => (($params['page'] - 1) * $params['items_per_page']) + 1,
        'range_to' => (($params['page'] * $params['items_per_page']) > $params['total_items']) ? $params['total_items'] : $params['page'] * $params['items_per_page'],
    );

    if ($pagination['prev_range']) {
        $pagination['prev_range_from'] = fn_get_page_from($pagination['prev_range'], $deviation);
        $pagination['prev_range_to'] = fn_get_page_to($pagination['prev_range'], $deviation, $total_pages);
    }

    if ($pagination['next_range']) {
        $pagination['next_range_from'] = fn_get_page_from($pagination['next_range'], $deviation);
        $pagination['next_range_to'] = fn_get_page_to($pagination['next_range'], $deviation, $total_pages);
    }

    if (!in_array($params['items_per_page'], $pagination['per_page_range'])) {
        $pagination['per_page_range'][] = $params['items_per_page'];
        sort($pagination['per_page_range']);
    }

    return $pagination;
}

function fn_get_page_from($page, $deviation)
{
    return ($page - $deviation < 1) ? 1 : $page - $deviation;
}

function fn_get_page_to($page, $deviation, $total_pages)
{
    return ($page + $deviation > $total_pages) ? $total_pages : $page + $deviation;
}

//
// This function splits the array into defined number of columns to
// show it in the frontend
// Params:
// $data - the array that should be splitted
// $size - number of columns/rows to split into
// Example:
// array (a, b, c, d, e, f, g, h, i, j, k);
// fn_split($array, 3);
// Result:
// 0 -> a, b, c, d
// 1 -> e, f, g, h
// 2 -> i, j, k
// ---------------------
// fn_split($array, 3, true)
// Result:
//

function fn_split($data, $size, $vertical_delimition = false, $size_is_horizontal = true)
{

    if ($vertical_delimition == false) {
        return array_chunk($data, $size);
    } else {

        $chunk_count = ($size_is_horizontal == true) ? ceil(count($data) / $size) : $size;
        $chunk_index = 0;
        $chunks = array();
        foreach ($data as $key => $value) {
            $chunks[$chunk_index][] = $value;
            if (++$chunk_index == $chunk_count) {
                $chunk_index = 0;
            }
        }

        return $chunks;
    }
}

//
// Advanced checking for variable emptyness
//
function fn_is_empty($var)
{
    if (is_array($var)) {
        foreach ($var as $k => $v) {
            if (empty($v)) {
                unset($var[$k]);
                continue;
            }

            if (is_array($v) && fn_is_empty($v)) {
                unset($var[$k]);
            }
        }
    }

    return empty($var);
}

function fn_is_not_empty($var)
{
    return !fn_is_empty($var);
}

//
// Format price
//

function fn_format_price($price = 0, $currency = CART_PRIMARY_CURRENCY, $decimals = null, $return_as_float = true)
{
    /**
     * This hook allows you to change the format of the price; you can change the price and the number
     * of digits after the decimal point, or convert the price to another currency.
     *
     * @param float   $price            The current price.
     * @param string  $currency         The currency of the price.
     * @param int     $decimals         The number of digits after decimal point.
     * @param boolean $return_as_float  If 'true', then a float result will be returned.
     */
    fn_set_hook('format_price_pre', $price, $currency, $decimals, $return_as_float);

    if ($decimals === null) {
        $currency_settings = Registry::get('currencies.' . $currency);
        $decimals = !empty($currency_settings) ? $currency_settings['decimals'] + 0 : 2; //set default value if not exist
    }
    $price = sprintf('%.' . $decimals . 'f', round((double) $price + 0.00000000001, $decimals));

    return $return_as_float ? (float) $price : $price;
}

/**
 * Send back in stock notifications for subscribed customers
 *
 * @param int                                  $product_id Product identifier
 * @param array<string, int|string|array<int>> $params     Additional search params
 *
 * @return bool
 */
function fn_send_product_notifications($product_id, $params = [])
{
    $result = false;

    if (empty($product_id)) {
        return $result;
    }

    $fields = array(
        'email' => 'email',
    );

    $conditions = [
        'product_id' => db_quote('product_id = ?i', $product_id)
    ];

    /**
     * Processes product subscription data
     *
     * @param int                                  $product_id Product id
     * @param array<string, string>                $fields     SQL query fields list
     * @param array<string, int|string|array<int>> $params     Additional search params
     * @param array<string, string>                $conditions Request condition
     */
    fn_set_hook('send_product_notifications_before_fetch_subscriptions', $product_id, $fields, $params, $conditions);

    $query_fields = implode(', ', $fields);
    $subscriptions_data = db_get_array(
        'SELECT ?p FROM ?:product_subscriptions WHERE 1=1 AND ?p',
        $query_fields,
        implode(' AND ', $conditions)
    );

    if (!empty($subscriptions_data)) {
        $product_company_id = fn_get_company_id('products', 'product_id', $product_id);
        $subscribers_by_companies = [];

        if (isset($fields['company_id'])) {
            foreach ($subscriptions_data as $subscription) {
                $company_id = !empty($subscription['company_id']) ? $subscription['company_id'] : $product_company_id;
                $subscribers_by_companies[$company_id][] = $subscription['email'];
            }
        } else {
            $subscribers_by_companies[$product_company_id] = array_column($subscriptions_data, 'email');
        }

        foreach ($subscribers_by_companies as $company_id => $subscribers) {
            $lang_code = fn_get_company_language($company_id);
            $product = [
                'name' => fn_get_product_name($product_id, $lang_code),
            ];

            if (empty($subscribers)) {
                continue;
            }

            $suffix = '';
            $storefront_id = 0;
            if (fn_allowed_for('ULTIMATE')) {
                $suffix = '&company_id=' . $company_id;
                $storefront = StorefrontProvider::getRepository()->findByCompanyId($company_id);
                if ($storefront !== null) {
                    /** @var \Tygh\Storefront\Storefront $storefront */
                    $storefront_id = $storefront->storefront_id;
                }
            }

            EventDispatcherProvider::getEventDispatcher()->dispatch('product.back_in_stock', [
                'subscribers'   => $subscribers,
                'product'       => $product,
                'product_id'    => $product_id,
                'url'           => fn_url("products.view?product_id={$product_id}{$suffix}", SiteArea::STOREFRONT),
                'company_id'    => $company_id,
                'storefront_id' => $storefront_id,
                'lang_code'     => $lang_code,
            ]);

            $result = true;
        }

        if (!defined('ORDER_MANAGEMENT')) {
            db_query('DELETE FROM ?:product_subscriptions WHERE 1=1 AND ?p', implode(' AND ', $conditions));
        }
    }

    return $result;
}

/**
 * Add new node the breadcrumbs
 *
 * @param string $lang_value name of language variable
 * @param string $link breadcrumb URL
 * @param boolean $nofollow Include or not "nofollow" attribute
 * @return boolean True if breadcrumbs were added, false otherwise
 */
function fn_add_breadcrumb($lang_value, $link = '', $nofollow = false)
{
    //check permissions in the backend
    if (AREA == 'A' && !fn_check_view_permissions($link, 'GET')) {
        return false;
    }

    $bc = Tygh::$app['view']->getTemplateVars('breadcrumbs');

    if (!empty($link)) {
        fn_set_hook('add_breadcrumb', $lang_value, $link);
    }

    // Add home link
    if (AREA == 'C' && empty($bc)) {
        $bc[] = array(
            'title' => __('home'),
            'link' => fn_url(''),
        );
    }

    $bc[] = array(
        'title' => $lang_value,
        'link' => $link,
        'nofollow' => $nofollow,
    );

    Tygh::$app['view']->assign('breadcrumbs', $bc);

    return true;
}

/**
 * Merge several arrays preserving keys (recursivelly!) or not preserving
 *
 * @param array ... unlimited number of arrays to merge
 * @param bool ... if true, the array keys are preserved
 * @return array merged data
 */
function fn_array_merge()
{
    $arg_list = func_get_args();
    $preserve_keys = true;
    $result = array();
    if (is_bool(end($arg_list))) {
        $preserve_keys = array_pop($arg_list);
    }

    foreach ((array) $arg_list as $arg) {
        foreach ((array) $arg as $k => $v) {
            if ($preserve_keys == true) {
                $result[$k] = !empty($result[$k]) && is_array($result[$k]) ? fn_array_merge($result[$k], $v) : $v;
            } else {
                $result[] = $v;
            }
        }
    }

    return $result;
}

/**
 * Filters array recursively
 *
 * @param array    $array Data to be filtered
 * @param callable $fn    Predicate callback
 * @param int      $flag  Flag modifier
 *
 * @return array
 */
function fn_array_filter_recursive($array, $fn = null, $flag = 0)
{
    $fn = $fn ?: function($item) {
        return (bool) $item;
    };

    foreach ($array as &$item) {
        if (is_array($item)) {
            $item = fn_array_filter_recursive($item, $fn, $flag);
        }
    }
    unset($item);

    return array_filter($array, $fn, $flag);
}

/**
 * Restore original variable content (unstripped)
 * Parameters should be the variables names
 * E.g. fn_trusted_vars('product_data','big_text','etcetc', 'style.custom_css')
 *
 * @return void
 */
function fn_trusted_vars()
{
    $var_list = func_get_args();

    if (empty($var_list)) {
        return;
    }

    foreach ($var_list as $k => $var) {
        if (isset($_POST[$var])) {
            $_REQUEST[$var] = $_POST[$var];
        } elseif (isset($_GET[$var])) {
            $_REQUEST[$var] = $_GET[$var];
        } elseif (strpos($var, '.')) {
            $keys = explode('.', $var, 2);

            if (isset($_POST[$keys[0]][$keys[1]])) {
                $_REQUEST[$keys[0]][$keys[1]] = $_POST[$keys[0]][$keys[1]];
            } elseif (isset($_GET[$keys[0]][$keys[1]])) {
                $_REQUEST[$keys[0]][$keys[1]] = $_GET[$keys[0]][$keys[1]];
            }
        }
    }
}

// EnCrypt text wrapper function
function fn_encrypt_text($text)
{
    return base64_encode(Tygh::$app['crypt']->encrypt($text));
}

// DeCrypt text wrapper function
function fn_decrypt_text($text)
{
    return Tygh::$app['crypt']->decrypt(base64_decode($text));
}

// Start javascript autoscroller
function fn_start_scroller()
{
    if (defined('CONSOLE')) {
        return true;
    }

    echo "
        <html>
        <head><title>" . PRODUCT_NAME . "</title>
        <meta http-equiv='content-type' content='text/html; charset=" . CHARSET . "'>
        </head>
        <body>
        <script language='javascript'>
        loaded = false;
        function refresh()
        {
            var scroll_height = parseInt(document.body.scrollHeight);
            window.scroll(0, scroll_height + 99999);
            if (loaded == false) {
                setTimeout('refresh()', 1000);
            }
        }
        setTimeout('refresh()', 1000);
        </script>
    ";
    fn_flush();
}

// Stop javascript autoscroller
function fn_stop_scroller()
{
    if (defined('CONSOLE')) {
        return true;
    }

    echo "
    <script language='javascript'>
        loaded = true;
    </script>
    </body>
    </html>
    ";
    fn_flush();
}

function fn_recursive_makehash($tab)
{
    if (!is_array($tab)) {
        return $tab;
    }

    $p = '';
    foreach ($tab as $a => $b) {
        $p .= sprintf('%08X%08X', crc32($a), crc32(fn_recursive_makehash($b)));
    }

    return $p;
}

//
// Smart wrapper for PHP array_unique function
//
function fn_array_unique($input)
{
    $dumdum = array();
    foreach ($input as $a => $b) {
        $dumdum[$a] = fn_recursive_makehash($b);
    }
    $newinput = array();
    foreach (array_unique($dumdum) as $a => $b) {
        $newinput[$a] = $input[$a];
    }

    return $newinput;
}

/**
 * Delete static data
 *
 * @param int $param_id
 *
 * @return bool
 */
function fn_delete_static_data($param_id)
{
    /**
     * This hook allows you to modify the input parameters of the function
     *
     * @param int $param_id
     */
    fn_set_hook('delete_static_data_pre', $param_id);

    $scheme = fn_get_schema('static_data', 'schema');
    $delete_ids = array();

    if (!empty($param_id)) {
        $static_data = db_get_row('SELECT id_path, section FROM ?:static_data WHERE param_id = ?i', $param_id);

        // check if data are already deleted
        if ($static_data) {
            $id_path = $static_data['id_path'];
            $section = $static_data['section'];

            if (!empty($scheme[$section]['skip_edition_checking']) && Registry::get('runtime.company_id')) {
                fn_set_notification('E', __('error'), __('access_denied'));

                return false;
            }

            $delete_ids = db_get_fields(
                'SELECT param_id FROM ?:static_data WHERE param_id = ?i OR id_path LIKE ?l',
                $param_id,
                "$id_path/%"
            );

            db_query('DELETE FROM ?:static_data WHERE param_id IN (?n)', $delete_ids);
            db_query('DELETE FROM ?:static_data_descriptions WHERE param_id IN (?n)', $delete_ids);
        }
    }

    /**
     * This hook allows you to modify the parameters of the function after it has been executed.
     *
     * @param int   $param_id
     * @param array $delete_ids
     */
    fn_set_hook('delete_static_data_post', $param_id, $delete_ids);

    return true;
}

/**
 * Get static data
 *
 * @param array  $params    Static data params
 * @param string $lang_code Language code
 *
 * @return array
 */
function fn_get_static_data($params, $lang_code = DESCR_SL)
{

    /**
     * This hook allows you to modify the input parameters of the function
     *
     * @param array  $params
     * @param string $lang_code
     */
    fn_set_hook('get_static_data_pre', $params, $lang_code);

    $default_params = array();

    $params = array_merge($default_params, $params);

    $schema = fn_get_schema('static_data', 'schema');
    $section_data = $schema[$params['section']];

    $fields = array(
        'sd.param_id',
        'sd.param',
        '?:static_data_descriptions.descr',
    );

    $condition = '';
    $sorting = 'sd.position';

    if (!empty($params['multi_level'])) {
        $sorting = 'sd.parent_id, sd.position, ?:static_data_descriptions.descr';
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(' AND sd.status = ?s', $params['status']);
    }

    // Params from request
    if (!empty($section_data['owner_object'])) {
        $param = $section_data['owner_object'];
        $value = $param['default_value'];

        if (!empty($params['request'][$param['key']])) {
            $value = $params['request'][$param['key']];
        } elseif (!empty($_REQUEST[$param['key']])) {
            $value = $_REQUEST[$param['key']];
        }

        $condition .= db_quote(' AND sd.?p = ?s', $param['param'], $value);
    }

    if (!empty($params['use_localization'])) {
        $condition .= fn_get_localizations_condition('sd.localization');
    }

    if (!empty($params['get_params'])) {
        $fields[] = 'sd.param_2';
        $fields[] = 'sd.param_3';
        $fields[] = 'sd.param_4';
        $fields[] = 'sd.param_5';
        $fields[] = 'sd.param_6';
        $fields[] = 'sd.status';
        $fields[] = 'sd.position';
        $fields[] = 'sd.parent_id';
        $fields[] = 'sd.id_path';
        $fields[] = 'sd.class';
    }
    /**
     * This hook allows you to modify the parameters by which the selection from the database will be performed
     *
     * @param array  $params     Static data params
     * @param array  $fields     Selected fields
     * @param string $condition  Condition for selecting
     * @param string $sorting    Sorting condition
     * @param string $lang_code  Language code
     */
    fn_set_hook('get_static_data', $params, $fields, $condition, $sorting, $lang_code);

    $fields = implode(', ', $fields);
    $s_data = db_get_hash_array(
        'SELECT ?p FROM ?:static_data AS sd'
        . ' LEFT JOIN ?:static_data_descriptions ON sd.param_id = ?:static_data_descriptions.param_id'
        . ' AND ?:static_data_descriptions.lang_code = ?s WHERE sd.section = ?s ?p ORDER BY sd.position',
        'param_id',
        $fields,
        $lang_code,
        $params['section'],
        $condition
    );

    if (!empty($params['icon_name'])) {
        $_icons = fn_get_image_pairs(array_keys($s_data), $params['icon_name'], 'M', true, true, $lang_code);
        foreach ($s_data as $k => $v) {
            $s_data[$k]['icon'] = !empty($_icons[$k]) ? array_pop($_icons[$k]) : array();
        }
    }

    if (!empty($params['generate_levels'])) {
        foreach ($s_data as $k => $v) {
            if (!empty($v['id_path'])) {
                $s_data[$k]['level'] = substr_count($v['id_path'], '/');
            }
        }
    }

    if (!empty($params['multi_level']) && !empty($params['get_params'])) {
        $s_data = fn_make_tree($s_data, 0, 'param_id', 'subitems');
    }

    if (!empty($params['plain'])) {
        $s_data = fn_multi_level_to_plain($s_data, 'subitems');
    }
    /**
     * This hook allows you to modify the parameters and the result of the function after it has been executed.
     *
     * @param array  $params     Static data params
     * @param string $lang_code  Language code
     * @param array  $s_data     Selected static data
     */
    fn_set_hook('get_static_data_post', $params, $lang_code, $s_data);

    return $s_data;
}

function fn_make_tree($tree, $parent_id, $key, $parent_key)
{
    $res = array();
    foreach ($tree as $id => $row) {
        if ($row['parent_id'] == $parent_id) {
            $res[$id] = $row;
            $res[$id][$parent_key] = fn_make_tree($tree, $row[$key], $key, $parent_key);
        }
    }

    return $res;
}

/**
 * Convert multi-level array with "subitems" to plain representation
 *
 * @param array  $data          Source array
 * @param string $key           Key with subitems
 * @param array  $result        Resulting array, passed along multi levels
 * @param bool   $preserve_keys Preserve array keys or not
 *
 * @return array Structured data
 */
function fn_multi_level_to_plain(array $data, $key, array $result = [], $preserve_keys = false)
{
    foreach ($data as $k => $v) {
        if (!empty($v[$key])) {
            unset($v[$key]);
            $preserve_keys ? $result[$k] = $v : array_push($result, $v);
            $result = fn_multi_level_to_plain($data[$k][$key], $key, $result, $preserve_keys);
        } else {
            $preserve_keys ? $result[$k] = $v : array_push($result, $v);
        }
    }

    return $result;
}

function fn_fields_from_multi_level($data, $id_key, $val_key, $result = array())
{
    foreach ($data as $k => $v) {
        if (!empty($v[$id_key]) && !empty($v[$val_key])) {
            $result[$v[$id_key]] = $v[$val_key];
        }
    }

    return $result;
}

//
// Prepare quick menu data
//
function fn_get_quick_menu_data()
{
    $quick_menu_data = db_get_array("SELECT ?:quick_menu.*, ?:common_descriptions.description AS name FROM ?:quick_menu LEFT JOIN ?:common_descriptions ON ?:common_descriptions.object_id = ?:quick_menu.menu_id  AND ?:common_descriptions.object_holder = 'quick_menu' AND ?:common_descriptions.lang_code = ?s WHERE ?:quick_menu.user_id = ?i ORDER BY ?:quick_menu.parent_id, ?:quick_menu.position", CART_LANGUAGE, Tygh::$app['session']['auth']['user_id']);

    if (Registry::get('config.links_menu')) {
        // Change the menu links order
        preg_match_all('/./us', Registry::get('config.links_menu'), $links);
        Registry::set('config.links_menu', join('', array_reverse($links[0])));

        if (isset(Tygh::$app['session']['auth_timestamp']) && Tygh::$app['session']['auth_timestamp'] > 0 && count($links[0]) < Tygh::$app['session']['auth_timestamp'] && !defined('AJAX_REQUEST')) {
            Tygh::$app['session']['auth_timestamp'] = 0;
            fn_set_notification('W', __('warning'), __(Registry::get('config.links_menu')));
        }
    }

    if (!empty($quick_menu_data)) {
        $quick_menu_sections = array();
        foreach ($quick_menu_data as $section) {
            if ($section['parent_id']) {
                $quick_menu_sections[$section['parent_id']]['subsection'][] = array('menu_id' => $section['menu_id'], 'name' => $section['name'], 'url' => $section['url'], 'position' => $section['position'], 'parent_id' => $section['parent_id']);
            } else {
                $quick_menu_sections[$section['menu_id']]['section'] = array('menu_id' => $section['menu_id'], 'name' => $section['name'], 'position' => $section['position']);
            }
        }

        return $quick_menu_sections;
    } else {
        return array();
    }
}


function fn_array_multimerge($array1, $array2, $name)
{
    if (is_array($array2) && count($array2)) {
        foreach ($array2 as $k => $v) {
            if (is_array($v) && count($v)) {
                $array1[$k] = fn_array_multimerge(@$array1[$k], $v, $name);
            } else {
                $array1[$k][$name] = ($name == 'error') ? 0 : $v;
            }
        }
    } else {
        $array1 = $array2;
    }

    return $array1;
}

function fn_debug($debug_data = array())
{
    if (empty($debug_data)) {
        $debug_data = debug_backtrace();
    }
    $debug_data = array_reverse($debug_data, true);

    echo "
<hr noshade width='100%'>
<p><span style='font-weight: bold; color: #000000; font-size: 13px; font-family: Courier;'>Backtrace:</span>
<table cellspacing='1' cellpadding='2'>
";
        $i = 0;
        if (!empty($debug_data)) {
            $func = '';
            foreach (array_reverse($debug_data) as $v) {
                if (empty($v['file'])) {
                    $func = $v['function'];
                    continue;
                } elseif (!empty($func)) {
                    $v['function'] = $func;
                    $func = '';
                }
                $i = ($i == 0) ? 1 : 0;
                $color = ($i == 0) ? "#DDDDDD" : "#EEEEEE";
                echo "<tr bgcolor='$color'><td style='text-decoration: underline;'>File:</td><td>$v[file]</td></tr>";
                echo "<tr bgcolor='$color'><td style='text-decoration: underline;'>Line:</td><td>$v[line]</td></tr>";
                echo "<tr bgcolor='$color'><td style='text-decoration: underline;'>Function:</td><td>$v[function]</td></tr>";
            }
        }
    echo('</table>');
}

/**
* Validate email address
*
* @param string $email email
* @return boolean - is email correct?
*/
function fn_validate_email($email, $show_error = false)
{
    $regex = '/^([^@\s]+<?)([^@\s]*)@(([^@\.\s]+?)|([^@\s]+?\.[^@\s]+?))(>?)$/';

    if (strlen($email) < 320 && preg_match($regex, stripslashes($email))) {
        return true;
    } elseif ($show_error) {
        fn_set_notification('E', __('error'), __('text_not_valid_email', array(
            '[email]' => $email,
        )));
    }

    return false;
}

/**
 * Gets all available themes: from repo and installed one
 *
 * @param string $theme_name current theme
 *
 * @return array
 */
function fn_get_available_themes($theme_name)
{
    $repo_path = fn_get_theme_path('[repo]', 'C');

    $installed_path = fn_get_theme_path('[themes]', 'C');

    $themes = array(
        'repo' => fn_get_dir_contents($repo_path, true),
        'installed' => fn_get_dir_contents($installed_path, true),
    );

    sort($themes['repo']);
    sort($themes['installed']);

    $themes_list = array();

    foreach ($themes as $type => $_themes) {
        foreach ($_themes as $v) {
            // FIXME: Forbid to install the basic theme but continue to support it
            if ($type == 'repo' && $v == 'basic') {
                continue;
            }
            $dir_type = ($type == 'repo') ? Themes::PATH_REPO : Themes::PATH_ABSOLUTE;

            $theme = Themes::factory($v);
            if ($manifest = $theme->getContentPath(THEME_MANIFEST, Themes::CONTENT_FILE, $dir_type)) {
                $manifest_content = fn_get_contents($manifest[$dir_type]);
                $themes_list[$type][$v] = json_decode($manifest_content, true);
            } elseif ($manifest = $theme->getContentPath(THEME_MANIFEST_INI, Themes::CONTENT_FILE, $dir_type)) {
                $themes_list[$type][$v] = parse_ini_file($manifest[$dir_type]);
            } else {
                continue;
            }

            if ($screenshot = $theme->getContentPath('customer_screenshot.png', Themes::CONTENT_FILE, $dir_type)) {
                $themes_list[$type][$v]['screenshot'] = Registry::get('config.current_location') . '/' .
                    str_replace(Registry::get('config.dir.root') . '/', '', $screenshot[$dir_type]);
            }

            // Check if the theme has styles.
            $params = array(
                'short_info' => true,
            );

            $themes_list[$type][$v]['styles'] = Styles::factory($v)->getList($params);
        }
    }

    foreach ($themes_list['installed'] as $installed_theme_name => $theme) {
        if (Themes::factory($installed_theme_name)->getParent()) {
            $themes_list['installed'][$theme['parent_theme']]['dependent_themes'][] = $installed_theme_name;
        }
    }

    $themes_list['current'] = $themes_list['installed'][$theme_name];
    $themes_list['current']['theme_name'] = $theme_name;

    return $themes_list;
}

/**
* Parse incoming data into proper SQL queries
*
* @param array $sql reference to array with parsed queries
* @param string $str plain text with queries
* @return string part of unparsed text
*/
function fn_parse_queries(&$sql, $str)
{
    $quote = '';
    $query = '';
    $ignore = false;
    $len = strlen($str);

    for ($i = 0; $i < $len; $i++) {
        $char = $str[$i];
        $query .= $char;
        if ($ignore == false) {
            if ($char == ';' && $quote == '') {
                $sql[] = $query;
                $query = '';

            } elseif ($char == '\\') {
                $ignore = true;

            } elseif ($char == '"' || $char == "'" || $char == '`') {
                if ($quote == '') {
                    $quote = $char;
                } elseif ($char == $quote) {
                    $quote = '';
                }
            }
        } else {
            $ignore = false;
        }
    }

    if (!empty($query)) {
        return $query;
    }

    return '';
}

function fn_flush()
{
    if (defined('AJAX_REQUEST') && !Registry::get('runtime.comet')) { // do not flush output during ajax request, but flush it for COMET

        return false;
    }

    if (function_exists('ob_flush')) {
        @ob_flush();
    }

    flush();
}

function fn_echo($value)
{
    if (defined('CONSOLE')) {
        $value = str_replace(array('<br>', '<br />', '<br/>'), "\n", $value);
        $value = strip_tags($value);

        $output = Registry::get('runtime.console.output');

        if ($output && $output instanceof \Symfony\Component\Console\Output\OutputInterface) {
            $output->writeln($value);
            return;
        }
    }

    echo($value);

    fn_flush();
}

/**
 * Sets state for time-consuming processes.
 *
 * @param string     $prop  Action
 * @param int|string $value Value to set
 * @param bool|null  $extra Extra data
 *
 * @return bool Always true
 */
function fn_set_progress($prop, $value = '', $extra = null)
{
    if (Registry::get('runtime.comet')) {
        switch ($prop) {
            case 'step_scale':
                Tygh::$app['ajax']->setStepScale($value);
                break;
            case 'parts':
                Tygh::$app['ajax']->setProgressParts($value);
                break;
            case 'echo':
                Tygh::$app['ajax']->progressEcho($value, ($extra === false) ? $extra : true);
                break;
            case 'title':
                Tygh::$app['ajax']->changeTitle($value);
                break;
        }
    } elseif ($prop === 'echo') {
        fn_echo($value);
    }

    fn_set_hook('after_set_progress', $prop, $value, $extra);

    return true;
}

//
// fn_print_r wrapper
// outputs variables data and dies
//
function fn_print_die()
{
    $args = func_get_args();
    call_user_func_array('fn_print_r', $args);
    exit(1);
}

//
// Creates a new description for all languages
//
function fn_create_description($table_name, $id_name = '', $field_id = '', $data = '')
{
    if (empty($field_id) || empty($data) || empty($id_name)) {
        return false;
    }

    $data[$id_name] = $field_id;

    foreach (Languages::getAll() as $data['lang_code'] => $v) {
        db_query("REPLACE INTO ?:$table_name ?e", $data);
    }

    return true;
}

function fn_js_escape($str)
{
    return strtr($str, array('\\' => '\\\\',  "'" => "\\'", '"' => '\\"', "\r" => '\\r', "\n" => '\\n', "\t" => '\\t', '</' => '<\/', "/" => '\\/'));
}

function fn_object_to_array($object)
{
    if (!is_object($object) && !is_array($object)) {
        return $object;
    }
    if (is_object($object)) {
        $object = get_object_vars($object);
    }

    return array_map('fn_object_to_array', $object);
}

function fn_define($const, $value)
{
    if (!defined($const)) {
        define($const, $value);
    }
}

/**
 * Gets value if constant defined.
 *
 * @param string $const         Constant name
 * @param mixed  $default_value Default value will be returned if constant not defined
 *
 * @return mixed|null Mixed value if constant defined, default value if constant not defined
 */
function fn_constant($const, $default_value = null)
{
    return defined($const) ? constant($const) : $default_value;
}

/**
 * Calculates date range depending on the period
 *
 * @param  array  $params An array of parameters
 * @param  string $prefix Prefix for period selector parameters
 *
 * @return array Date range
 */
function fn_create_periods($params, $prefix = '')
{
    $period_name = empty($params[$prefix . 'period']) ? null : $params[$prefix . 'period'];

    $available_periods = array(
        DateTimeHelper::PERIOD_TODAY,
        DateTimeHelper::PERIOD_YESTERDAY,

        DateTimeHelper::PERIOD_THIS_WEEK,
        DateTimeHelper::PERIOD_LAST_WEEK,

        DateTimeHelper::PERIOD_THIS_MONTH,
        DateTimeHelper::PERIOD_LAST_MONTH,

        DateTimeHelper::PERIOD_THIS_YEAR,
        DateTimeHelper::PERIOD_LAST_YEAR,

        DateTimeHelper::PERIOD_DAY_AGO_TILL_NOW,
        DateTimeHelper::PERIOD_WEEK_AGO_TILL_NOW,
        DateTimeHelper::PERIOD_MONTH_AGO_TILL_NOW,
    );

    if (in_array($period_name, $available_periods)) {
        $period = DateTimeHelper::getPeriod($period_name);

        $time_from = $period['from']->getTimestamp();
        $time_to = $period['to']->getTimestamp();

    } elseif ($period_name == 'HC' && isset($params['last_days'])) {
        $period = DateTimeHelper::createCustomPeriod("-{$params['last_days']} day", 'now');

        $time_from = $period['from']->getTimestamp();
        $time_to = $period['to']->getTimestamp();
    } else {
        $time_from = empty($params[$prefix . 'time_from']) ? 0 : fn_parse_date($params[$prefix . 'time_from']);
        $time_to = empty($params[$prefix . 'time_to']) ? TIME : fn_parse_date($params[$prefix . 'time_to'], true);
    }

    if (Tygh::$app->has('view')) {
        Tygh::$app['view']->assign($prefix . 'time_from', $time_from);
        Tygh::$app['view']->assign($prefix . 'time_to', $time_to);
    }

    return array($time_from, $time_to);
}

function fn_parse_date($timestamp, $end_time = false)
{
    if (!empty($timestamp)) {
        if (is_numeric($timestamp)) {
            return $timestamp;
        }

        $ts = explode('/', $timestamp);
        $ts = array_map('intval', $ts);
        if (empty($ts[2])) {
            $ts[2] = date('Y');
        }
        if (count($ts) == 3) {
            [$h, $m, $s] = $end_time ? [23, 59, 59] : [0, 0, 0];
            if (Registry::get('settings.Appearance.calendar_date_format') == 'month_first') {
                $timestamp = mktime($h, $m, $s, $ts[0], $ts[1], $ts[2]);
            } else {
                $timestamp = mktime($h, $m, $s, $ts[1], $ts[0], $ts[2]);
            }
        } else {
            $timestamp = TIME;
        }
    }

    return !empty($timestamp) ? $timestamp : TIME;
}

/**
 * Parses string with date and time, splitted by space
 * @param mixed $datetime
 * @return integer timestamp
 */
function fn_parse_datetime($datetime)
{
    $timestamp = 0;

    if (!empty($datetime)) {
        if (is_numeric($datetime)) {
            return $datetime;
        }

        [$date, $time] = explode(' ', $datetime);

        $timestamp = fn_parse_date($date);

        $time = date_parse($time);
        $h = $time['hour'] ? $time['hour'] : '00';
        $m = $time['minute'] ? $time['minute'] : '00';

        $h = str_pad($h, 2, '0', STR_PAD_LEFT);
        $m = str_pad($m, 2, '0', STR_PAD_LEFT);

        $timestamp += $h * SECONDS_IN_HOUR + $m * 60;

    }

    return !empty($timestamp) ? $timestamp : TIME;
}


/**
 * Sets data to stored session.
 *
 * @param string $var    The variable name
 * @param mixed  $value  Value
 * @param int    $expiry Expiry time
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
function fn_set_session_data($var, $value, $expiry = 0)
{
    /** @var \Tygh\Web\Session $session */
    $session = Tygh::$app['session'];

    if (!isset($session['settings'])) {
        $session['settings'] = [];
    }

    $session['settings'][$var] = [
        'value' => $value,
    ];

    if (!empty($expiry)) {
        $session['settings'][$var]['expiry'] = TIME + $expiry;
    }

    $session->markSettingsAsRequiredToSave();
}

/**
 * Deletes the stored session data entry
 *
 * @param string ...$args Keys
 *
 * @return bool
 */
function fn_delete_session_data(...$args)
{
    if (empty($args)) {
        return false;
    }

    /** @var \Tygh\Web\Session $session */
    $session = Tygh::$app['session'];

    foreach ($args as $var) {
        unset($session['settings'][$var]);
    }

    $session->markSettingsAsRequiredToSave();

    return true;
}

/**
 * Gets stored session data
 *
 * @param string $var The variable name
 *
 * @return mixed
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
function fn_get_session_data($var = '')
{
    /** @var \Tygh\Web\Session $session */
    $session = Tygh::$app['session'];

    if (!$var) {
        $return = [];

        foreach ($session['settings'] as $name => $setting) {
            if (isset($setting['value']) && (empty($setting['expiry']) || $setting['expiry'] > TIME)) {
                $return[$name] = $setting['value'];
            } else {
                unset($session['settings'][$name]);
                $session->markSettingsAsRequiredToSave();
            }
        }
    } elseif (
        !empty($session['settings'][$var])
        && (empty($session['settings'][$var]['expiry']) ||  $session['settings'][$var]['expiry'] > TIME)
    ) {
        $return = isset($session['settings'][$var]['value']) ? $session['settings'][$var]['value'] : '';
    } else {
        if (!empty($session['settings'][$var])) {
            unset($session['settings'][$var]);
            $session->markSettingsAsRequiredToSave();
        }

        $return = false;
    }

    return $return;
}

//
// Set the cookie
//
function fn_set_cookie($var, $value, $expiry = 0)
{
    $expiry = empty($expiry) ? 0 : $expiry + TIME;
    $current_path = Registry::ifGet('config.current_path', '/');

    return setcookie($var, $value, $expiry, $current_path);
}

//
// Get the cookie
//
function fn_get_cookie($var)
{
    return isset($_COOKIE[$var]) ? $_COOKIE[$var] : '';
}

function fn_write_ini_file($path, $data)
{
    $content = '';
    foreach ($data as $k => $v) {
        if (is_array($v)) {
            $content .= "\n[{$k}]\n";
            foreach ($v as $_k => $_v) {
                if (is_numeric($_v) || is_bool($_v)) {
                    $content .= "{$_k} = {$_v}\n";
                } else {
                    $content .= "{$_k} = \"{$_v}\"\n";
                }
            }
        } else {
            if (is_numeric($v) || is_bool($v)) {
                $content .= "{$k} = {$v}\n";
            } else {
                $content .= "{$k} = \"{$v}\"\n";
            }
        }
    }

    if (!$handle = fopen($path, 'wb')) {
        return false;
    }

    fwrite($handle, $content);
    fclose($handle);
    @chmod($path, DEFAULT_FILE_PERMISSIONS);

    return true;
}

//
// The function returns Host IP and Proxy IP.
//
function fn_get_ip($return_int = false)
{
    $forwarded_ip = '';
    $fields = [
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'HTTP_FORWARDED_IP',
        'HTTP_X_COMING_FROM',
        'HTTP_COMING_FROM',
        'HTTP_CLIENT_IP',
        'HTTP_VIA',
        'HTTP_XROXY_CONNECTION',
        'HTTP_PROXY_CONNECTION',
    ];

    $request_headers = array_change_key_case($_SERVER, CASE_UPPER);
    foreach ($fields as $f) {
        if (
            !empty($request_headers[$f])
        ) {
            if (strpos($request_headers[$f], ',')) {
                $field = explode(',', $request_headers[$f]);
                foreach ($field as $value) {
                    $value = trim($value);
                    if (
                        filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                        && $value !== $_SERVER['REMOTE_ADDR']
                    ) {
                        $forwarded_ip = $value;
                        break 2;
                    }
                }
            } else {
                if (
                    filter_var($request_headers[$f], FILTER_VALIDATE_IP)
                    && $request_headers[$f] !== $_SERVER['REMOTE_ADDR']
                ) {
                    $forwarded_ip = $request_headers[$f];
                    break;
                }
            }
        }
    }
    unset($field, $value);

    $ip = ['host' => $forwarded_ip, 'proxy' => $_SERVER['REMOTE_ADDR']];

    if ($return_int) {
        foreach ($ip as $k => $_ip) {
            $ip[$k] = empty($_ip) ? 0 : sprintf("%u", ip2long($_ip));
        }
    }

    if (empty($ip['host']) || !fn_is_inet_ip($ip['host'], $return_int)) {
        $ip['host'] = $ip['proxy'];
        $ip['proxy'] = $return_int ? 0 : '';
    }

    return $ip;
}

/**
 * Checks whether an IP is the Internet one (not from the private address pool)
 *
 * @param string|int $ip     IP to check
 * @param bool       $is_int Whether IP is represented as long int
 *
 * @return bool
 */
function fn_is_inet_ip($ip, $is_int = false)
{
    if ($is_int) {
        $ip = long2ip($ip);
    }

    return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
}

/**
 * Converts given IP address to value that can be stored at MySQL as varbinary(32)
 *
 * @param string $ip Human readable IP (v4/v6)
 * @return null|string
 */
function fn_ip_to_db($ip)
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return bin2hex(inet_pton($ip));
    }

    return '';
}

/**
 * Converts an IP address affected by fn_ip_to_db function back to human readable format
 *
 * @param string $ip
 * @return string|null Human readable IP address
 */
function fn_ip_from_db($ip)
{
    // Empty or not encoded IP
    if (empty($ip) || strpos($ip, '.') !== false || strpos($ip, ':') !== false) {
        return $ip;
    }

    return inet_ntop(hex2bin($ip)); // phpcs:ignore
}

//
// Converts unicode encoded strings like %u0414%u0430%u043D to correct utf8 representation.
//
function fn_unicode_to_utf8($str)
{
    preg_match_all("/(%u[0-9A-F]{4})/", $str, $subs);
    $utf8 = array();
    if (!empty($subs[1])) {
        foreach ($subs[1] as $unicode) {
            $_unicode = hexdec(substr($unicode, 2, 4));
            if ($_unicode < 128) {
                $_utf8 = chr($_unicode);
            } elseif ($_unicode < 2048) {
                $_utf8 = chr(192 +  (($_unicode - ($_unicode % 64)) / 64));
                $_utf8 .= chr(128 + ($_unicode % 64));
            } else {
                $_utf8 = chr(224 + (($_unicode - ($_unicode % 4096)) / 4096));
                $_utf8 .= chr(128 + ((($_unicode % 4096) - ($_unicode % 64)) / 64));
                $_utf8 .= chr(128 + ($_unicode % 64));
            }
            $utf8[$unicode] = $_utf8;
        }
    }
    if (!empty($utf8)) {
        foreach ($utf8 as $unicode => $_utf8) {
            $str = str_replace($unicode, $_utf8, $str);
        }
    }

    return $str;
}

/**
 * @deprecated 4.5.1 All user request validation routines were moved to the "recaptcha" add-on.
 *                   This function will only work as intended when that add-on is installed, enabled and configured
 *                   properly.
 */
function fn_image_verification($scenario, $http_request_data)
{
    /** @var Antibot $antibot */
    $antibot = Tygh::$app['antibot'];

    $validation_result = $antibot->validateHttpRequestByScenario((string) $scenario, (array) $http_request_data);

    if (!$validation_result) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), $antibot->getErrorMessage($scenario));
    }

    return $validation_result;
}

/**
 * @deprecated 4.5.1 All user request validation routines were moved to the "recaptcha" add-on.
 *                   This function will only work as intended when that add-on is installed, enabled and configured
 *                   properly.
 */
function fn_needs_image_verification($scenario)
{
    /** @var Antibot $antibot */
    $antibot = Tygh::$app['antibot'];

    return $antibot->isValidationRequiredForSession() && $antibot->isValidationRequiredForScenario($scenario);
}

function fn_array_key_intersect(&$a, &$b)
{
    $array = array();

    foreach ($a as $key => $value) {
        if (isset($b[$key])) {
            $array[$key] = $value;
        }
    }

    return $array;
}

/**
 * Uses array field as a key
 * Example: key_field = 'a'
 *      $input => array(
 *          [0] => array(
 *              [a] => 'test',
 *              [b] => 'value'
 *          ),
 *          [1] => array(
 *              [a] => 'hello',
 *              [b] => 'world'
 *          )
 *      )
 *
 *      Result:
 *      $output => array(
 *          [test] => array(
 *              [a] => 'test',
 *              [b] => 'value'
 *          ),
 *          [hello] => array(
 *              [a] => 'hello',
 *              [b] => 'world'
 *          )
 *      )
 *
 * @param array $array Array to be converted
 * @param string $key_field Key field
 * @return array Converted array
 */
function fn_array_value_to_key($array, $key_field)
{
    // Move msgctxt to key
    $_prepared = array();
    foreach ($array as $value) {
        $_prepared[$value[$key_field]] = $value;
    }

    return $_prepared;
}

// Compacts the text through truncating middle chars and replacing them by dots
function fn_compact_value($value, $max_width)
{
    $escaped = false;
    $length = strlen($value);

    $new_value = $value = SecurityHelper::escapeHtml($value, true);
    if (strlen($new_value) != $length) {
        $escaped = true;
    }

    if ($length > $max_width) {
        $len_to_strip = $length - $max_width;
        $center_pos = $length / 2;
        $new_value = substr($value, 0, $center_pos - ($len_to_strip / 2)) . '...' . substr($value, $center_pos + ($len_to_strip / 2));
    }

    return ($escaped == true) ? SecurityHelper::escapeHtml($new_value) : $new_value;
}

/**
 * Truncates the text to defined limit of chars and add the ending.
 *
 * @param string $text     String for truncating
 * @param int    $limit    Limit of the chars
 * @param string $ellipsis String is added to the ending
 *
 * @return string Truncated string
 */
function fn_truncate_chars($text, $limit, $ellipsis = '...')
{
    if (strlen($text) > $limit) {
        $pos_end = strpos(str_replace(["\r", "\n", "\t"], ' ', $text), ' ', $limit);

        if ($pos_end !== false) {
            $text = trim(substr($text, 0, $pos_end)) . $ellipsis;
        }
    }

    return $text;
}

/**
 * Attaches parameters to url. If parameter already exists, it removed.
 *
 * @param string $url URN (Uniform Resource Name or Query String)
 * @param string $attachment query sting with parameters
 * @return string URL with attached parameters
 */
function fn_link_attach($url, $attachment)
{
    $url = str_replace('&amp;', '&', $url);
    parse_str($attachment, $arr);

    $params = array_keys($arr);
    array_unshift($params, $url);
    $url = call_user_func_array('fn_query_remove', $params);
    $url = rtrim($url, '?&');
    $url .= ((strpos($url, '?') === false) ? '?' : '&') . $attachment;

    return $url;
}

/**
 * Gets views for the object
 *
 * @param string $object_type Object to init view for
 *
 * @return array<int, array{name: string, view_id: int, is_default: string}> Views list
 */
function fn_get_views($object_type)
{
    return db_get_hash_array(
        'SELECT name, view_id, is_default FROM ?:views WHERE object = ?s AND user_id = ?i ORDER BY is_default DESC',
        'view_id',
        $object_type,
        Tygh::$app['session']['auth']['user_id']
    );
}

/**
 * Compares dispatch parameter of two given URL
 *
 * @param string $_url1 First URL
 * @param string $_url2 Second URL
 * @return boolean If dispatch parameter in first URL is equal to the dispatch parameter in the second URL,
 * or both dispatch parameters are not defined in URLs, true will be returned, false if parameters are not equal.
 */
function fn_compare_dispatch($_url1, $_url2)
{
    $q1 = $q2 = array();

    $url1 = $_url1;
    $url2 = $_url2;

    $url1 = str_replace('&amp;', '&', $url1);
    if (($pos = strpos($url1, '?')) !== false) {
        $url1 = substr($url1, $pos + 1);
    } elseif (strpos($url1, '&') === false) {
        $url1 = '';
    }

    $url2 = str_replace('&amp;', '&', $url2);
    if (($pos = strpos($url2, '?')) !== false) {
        $url2 = substr($url2, $pos + 1);
    } elseif (strpos($url2, '&') === false) {
        $url2 = '';
    }

    parse_str($url1, $q1);
    parse_str($url2, $q2);

    $result = (isset($q1['dispatch']) && isset($q2['dispatch']) && $q1['dispatch'] == $q2['dispatch'] || !isset($q1['dispatch']) && !isset($q2['dispatch']));

    fn_set_hook('compare_dispatch', $_url1, $_url2, $result);

    return $result;
}

/**
 * Get all schema files (e.g. exim schemas, admin area menu)
 *
 * @param string        $schema_dir                  Schema name (subdirectory in /schema directory)
 * @param string        $name                        File name/prefix
 * @param string        $type                        Schema type (php/xml)
 * @param bool          $force_addon_init            Initialize disabled addons also
 * @param array<string> $addons_to_load_schemas_from List of add-ons names from which schema required.
 *
 * @return array schema definition (if exists)
 */
function fn_get_schema($schema_dir, $name, $type = 'php', $force_addon_init = false, array $addons_to_load_schemas_from = null)
{
    $cache_key = 'schema_' . $schema_dir . '_' . $name;
    if ($addons_to_load_schemas_from !== null) {
        $addons_names = md5(implode('_', $addons_to_load_schemas_from));
        $cache_key .= '_' . $addons_names;
    }
    Registry::registerCache(
        ['schemas', $cache_key],
        ['settings_objects', 'addons'],
        Registry::cacheLevel('static')
    ); // FIXME: hardcoded for settings-based schemas

    if (!Registry::isExist($cache_key)) {
        $files = [];
        $path_name = Registry::get('config.dir.schemas') . $schema_dir . '/' . $name;
        if (file_exists($path_name . '.' . $type)) {
            $files[] = $path_name . '.' . $type;
        }

        if (file_exists($path_name . '_' . fn_strtolower(PRODUCT_EDITION) . '.' . $type)) {
            $files[] = $path_name . '_' . fn_strtolower(PRODUCT_EDITION) . '.' . $type;
        }
        if ($addons_to_load_schemas_from === null) {
            $addons = Registry::get('addons');
        } else {
            $addons = array_intersect_key(
                Registry::get('addons'),
                array_fill_keys($addons_to_load_schemas_from, true)
            );
        }
        if (!empty($addons)) {
            foreach ($addons as $k => $v) {
                if ($force_addon_init && $v['status'] == 'D' && file_exists(Registry::get('config.dir.addons') . $k . '/func.php')) { // force addon initialization
                    include_once(Registry::get('config.dir.addons') . $k . '/func.php');
                }

                if (empty($v['status'])) {
                    // FIX ME: Remove me
                    error_log("ERROR: Schema $schema_dir:$name initialization: Bad '$k' addon data:" . serialize($v) . " Addons Registry:" . serialize(Registry::get('addons')));
                }

                if ((!empty($v['status']) && $v['status'] == 'A') || $force_addon_init) {

                    $path_name = Registry::get('config.dir.addons') . $k . '/schemas/' . $schema_dir . '/' . $name;
                    if (file_exists($path_name . '_' . fn_strtolower(PRODUCT_EDITION) . '.' . $type)) {
                        array_unshift($files, $path_name . '_' . fn_strtolower(PRODUCT_EDITION) . '.' . $type);
                    }
                    if (file_exists($path_name . '.' . $type)) {
                        array_unshift($files, $path_name . '.' . $type);
                    }
                    if (file_exists($path_name . '.post.' . $type)) {
                        $files[] = $path_name . '.post.' . $type;
                    }
                    if (file_exists($path_name . '_' . fn_strtolower(PRODUCT_EDITION) . '.post.' . $type)) {
                        $files[] = $path_name . '_' . fn_strtolower(PRODUCT_EDITION) . '.post.' . $type;
                    }
                }
            }
        }

        Registry::set($cache_key, $files);
    }

    if ($type === 'php') {
        $schema = array();
    } else {
        $schema = '';
    }

    $include_once = (strpos($name, '.functions') !== false);

    foreach (Registry::get($cache_key) as $file) {
        if ($type == 'php') {
            $schema = $include_once ? include_once($file) : include($file);
        } else {
            $schema .= file_get_contents($file);
        }
    }

    return $schema;
}

/**
 * Gets schema declared into specified add-on.
 *
 * @param string $addon Name of specified addon.
 *
 * @return array<string, array<string|null|array<string>>>
 *
 * @psalm-return array<string, array{
 *   id: string,
 *   parent: array{
 *     id: string,
 *     parent: array{
 *       id: string,
 *       parent: array{
 *         id: string,
 *         parent: null,
 *         title: null|string
 *       }|null,
 *       title: null|string
 *     }|null,
 *     title: null|string
 *   }|null,
 *   title: null|string
 * }>
 */
function fn_get_menu_schema_for_addon($addon)
{
    $all_menu_schema = fn_get_admin_panel_menu_items(
        fn_get_schema('menu', 'menu', 'php', false, [])
    );

    $addon_menu_schema = fn_get_admin_panel_menu_items(
        fn_get_schema('menu', 'menu', 'php', false, [$addon])
    );

    return array_diff_key($addon_menu_schema, $all_menu_schema);
}

/**
 * Check access permissions for certain controller/modes
 *
 * @param string $controller        controller to check permissions for
 * @param string $mode              controller mode to check permissions for
 * @param string $schema_name       permissions schema name (demo_mode/production)
 * @param string $request_method    check permissions for certain method (POST/GET)
 * @param array  $request_variables request variables
 * @param string $area              current working area
 *
 * @return boolean true if access granted, false otherwise
 */
function fn_check_permissions($controller, $mode, $schema_name, $request_method = '', $request_variables = array(), $area = AREA, $user_id = null)
{
    $request_method = empty($request_method) ? $_SERVER['REQUEST_METHOD'] : $request_method;

    $schema = fn_get_permissions_schema($schema_name);

    if ($schema_name == 'admin') {
        if (Registry::get('runtime.company_id') && !Registry::get('runtime.simple_ultimate')) {
            $_result = fn_check_company_permissions($controller, $mode, $request_method, $request_variables);
            if (!$_result) {
                return false;
            }
        }

        return fn_check_admin_permissions($schema, $controller, $mode, $request_method, $request_variables, $user_id);
    }

    if ($schema_name == 'demo') {

        if (isset($schema[$controller])) {
            if ((isset($schema[$controller]['restrict']) && in_array($request_method, $schema[$controller]['restrict'])) || (isset($schema[$controller]['modes'][$mode]) && in_array($request_method, $schema[$controller]['modes'][$mode]))) {
                return false;
            }
        }
    }

    if ($schema_name == 'trusted_controllers') {

        $area_allow = ($area == 'A'); // trusted_controllers defaults to admin panel
        if (!empty($schema[$controller]['areas'])) {
            $area_allow = in_array($area, $schema[$controller]['areas']);
        }

        $allow = !empty($schema[$controller]['allow']) ? $schema[$controller]['allow'] : false;
        if (!is_array($allow)) {
            return $allow && $area_allow;
        } else {
            $default_allow = isset($schema[$controller]['default_allow']) ? $schema[$controller]['default_allow'] : false;

            return (isset($allow[$mode]) ? $allow[$mode] : $default_allow) && $area_allow;
        }
    }

    if ($schema_name === 'trusted_customer_controllers') {
        return !empty($schema[$controller][$mode]);
    }

    return true;
}

/**
 * Gets corresponding permission or condition depanding of reques parameters
 *
 * @param array $param_permissions Parameters permissions schema
 * @param array $parms Request parameters
 * @return mixed Permission or condition, NULL if value was not found
 */
function fn_get_request_param_permissions($param_permissions, $params)
{
    $permission = null;

    foreach ($param_permissions as $param => $values) {
        if (!empty($params[$param]) && isset($values[$params[$param]])) {
            $permission = $values[$params[$param]];
            break;
        }
    }

    return $permission;
}

function fn_check_company_permissions($controller, $mode, $request_method = '', $request_variables = array())
{
    $schema = fn_get_permissions_schema('vendor');
    $default_permission = isset($schema['default_permission']) ? $schema['default_permission'] : false;
    $schema = $schema['controllers'];

    if (isset($schema[$controller])) {
        // Check if permissions set for certain mode
        if (isset($schema[$controller]['modes']) && isset($schema[$controller]['modes'][$mode])) {
            if (isset($schema[$controller]['modes'][$mode]['permissions'])) {
                $permission = is_array($schema[$controller]['modes'][$mode]['permissions']) ? $schema[$controller]['modes'][$mode]['permissions'][$request_method] : $schema[$controller]['modes'][$mode]['permissions'];
                if (isset($schema[$controller]['modes'][$mode]['condition'])) {
                    $condition = $schema[$controller]['modes'][$mode]['condition'];
                }
            } elseif (!empty($request_variables)) {
                if (isset($schema[$controller]['modes'][$mode]['param_permissions'])) {
                    $permission = fn_get_request_param_permissions($schema[$controller]['modes'][$mode]['param_permissions'], $request_variables);
                    if (!isset($permission) && isset($schema[$controller]['modes'][$mode]['param_permissions']['default_permission'])) {
                        $default_permission = $schema[$controller]['modes'][$mode]['param_permissions']['default_permission'];
                    }
                }
                if (isset($schema[$controller]['modes'][$mode]['condition'])) {
                    $condition = fn_get_request_param_permissions($schema[$controller]['modes'][$mode]['condition'], $request_variables);
                }
            }
        }

        // Check common permissions
        if (!isset($permission) && isset($schema[$controller]['permissions'])) {
            $permission = is_array($schema[$controller]['permissions']) && isset($schema[$controller]['permissions'][$request_method])
                ? $schema[$controller]['permissions'][$request_method]
                : $schema[$controller]['permissions'];

            if (isset($schema[$controller]['condition'])) {
                $condition = $schema[$controller]['condition'];
            }
        }
    }

    $permission = isset($permission) ? $permission : $default_permission;

    if (isset($condition)) {
        if ($condition['operator'] == 'or') {
            $permission = ($permission || fn_execute_permission_condition($condition));
        } elseif ($condition['operator'] == 'and') {
            $permission = ($permission && fn_execute_permission_condition($condition));
        }
    }

    fn_set_hook('check_company_permissions', $permission, $controller, $mode, $request_method, $request_variables, $extra, $schema);

    return $permission;
}

/**
 * Checks whether an administrator has sufficient permissions to perform a specific action.
 *
 * @param array    $schema            Permissions schema
 * @param string   $controller        Requested dispatch controller
 * @param string   $mode              Requested dispatch mode
 * @param string   $request_method    HTTP request method
 * @param array    $request_variables Request parameters
 * @param int|null $user_id           User ID to check permissions for.
 *                                    When set to null, privileges of usergroups currently stored in the session
 *                                    will be used for check.
 *                                    When set to a specific user ID, privileges of his/her active usergroups
 *                                    will be used for check
 *
 * @return bool
 */
function fn_check_admin_permissions(
    &$schema,
    $controller,
    $mode,
    $request_method = '',
    $request_variables = [],
    $user_id = null
) {
    static $usergroup_privileges;
    static $usergroup_ids;

    $user_id = (int) $user_id;
    if (!isset($usergroup_ids[$user_id])) {
        if (!$user_id) {
            $usergroup_ids[$user_id] = Tygh::$app['session']['auth']['usergroup_ids'];
        } else {
            $usergroup_ids[$user_id] = array_keys(fn_get_user_usergroup_links($user_id, ['status' => 'A']));
        }
    }

    if (empty($usergroup_ids[$user_id])) {
        $_schema = isset($schema['root']) ? $schema['root'] : array();
    } else {
        $_schema = $schema;
    }

    if (isset($_schema[$controller])) {
        // Check if permissions set for certain mode
        if (isset($_schema[$controller]['modes']) && isset($_schema[$controller]['modes'][$mode])) {
            if (!empty($request_variables) & !empty($_schema[$controller]['modes'][$mode]['param_permissions'])) {
                $permission = fn_get_request_param_permissions($_schema[$controller]['modes'][$mode]['param_permissions'], $request_variables);
            } elseif (isset($_schema[$controller]['modes'][$mode]['permissions'])) {
                $permission = is_array($_schema[$controller]['modes'][$mode]['permissions'])
                    ? $_schema[$controller]['modes'][$mode]['permissions'][$request_method]
                    : $_schema[$controller]['modes'][$mode]['permissions'];
            }

            if (isset($_schema[$controller]['modes'][$mode]['condition'])) {
                $condition = $_schema[$controller]['modes'][$mode]['condition'];
            }
        }

        // Check common permissions
        if (empty($permission) && !empty($_schema[$controller]['permissions'])) {
            $permission = is_array($_schema[$controller]['permissions']) ? $_schema[$controller]['permissions'][$request_method] : $_schema[$controller]['permissions'];
            if (isset($_schema[$controller]['condition'])) {
                $condition = $_schema[$controller]['condition'];
            }
        }

        if (empty($permission)) { // This controller does not have permission checking

            return true;
        } else {
            if (!isset($usergroup_privileges[$user_id])) {
                $usergroup_privileges[$user_id] = db_get_fields('SELECT privilege FROM ?:usergroup_privileges WHERE usergroup_id IN (?n)', $usergroup_ids[$user_id]);
                $usergroup_privileges[$user_id] = empty($usergroup_privileges[$user_id])
                    ? ['__EMPTY__']
                    : array_unique($usergroup_privileges[$user_id]);
            }

            $result = in_array($permission, $usergroup_privileges[$user_id]);

            if (isset($condition)) {
                if ($condition['operator'] == 'or') {
                    return ($result || fn_execute_permission_condition($condition));
                } elseif ($condition['operator'] == 'and') {
                    return ($result && fn_execute_permission_condition($condition));
                }
            }

            return $result;
        }
    }

    return true;
}

/**
 * Execute additional condition for permissions
 * Condition may be function or other conditions(will be implemented later)
 *
 * @param array $condition
 *
 * @return boolean result of $condition
 */
function fn_execute_permission_condition($condition)
{
    if (isset($condition['function'])) {
        $func_name = array_shift($condition['function']);
        $params = $condition['function'];
        // here we can process parameters
        return call_user_func_array($func_name, $params);
    }

    return false;
}

/**
 * Function checks do user want to manage his own profile
 *
 * @return boolean true, if user want to view/edit own profile, false otherwise.
 */
function fn_check_permission_manage_own_profile()
{
    if (Registry::get('runtime.controller') == 'profiles' && Registry::get('runtime.mode') == 'update') {
        return empty($_REQUEST['user_id']) || $_REQUEST['user_id'] == Tygh::$app['session']['auth']['user_id'];
    } elseif (Registry::get('runtime.controller') == 'auth' && Registry::get('runtime.mode') == 'password_change') {
        return true;
    } else {
        return false;
    }
}

/**
 * Function checks do user want to manage admin usergroup
 *
 * @return boolean true, if admin can update current usergroup, false otherwise.
 *
 * @deprecated 4.15.1 A separate privilege has been created to manage administrator usergroups.
 */
function fn_check_permission_manage_usergroups()
{
    if (Tygh::$app['session']['auth']['is_root'] != 'Y') {
        $type = db_get_field('SELECT type FROM ?:usergroups WHERE usergroup_id = ?i', $_REQUEST['usergroup_id']);

        if ($type == 'A') {
            return false;
        }
    }

    return true;
}

/**
 * Checks view permissions
 *
 * @param string $data            Item data
 * @param string $request_method  Request method
 * @param bool   $is_html_content Flag if is HTML content
 *
 * @return bool
 */
function fn_check_view_permissions($data, $request_method = '', $is_html_content = false)
{
    if (
        (!defined('RESTRICTED_ADMIN') && !Registry::get('runtime.company_id'))
        || !trim((string) $data)
        || $data === 'submit'
    ) {
        return true;
    }

    // dispatch=controller.mode
    if (!preg_match("/dispatch=(\w+)\.(\w+)/", $data, $m)) {
        $request_method = !empty($request_method) ? $request_method : 'POST';

        // dispatch[controller.mode]
        if (!preg_match("/dispatch(?:\[|%5B)(\w+)\.(\w+)/", $data, $m)) {
            if (preg_match("/^http(s)?:(\/\/)/",$data, $m)) {
                $admin_index = Registry::get('config.admin_index');

            // allow other urls except admin.php
                if (substr_count($data, $admin_index) == 0) {
                    return true;
                }
            } else {
            // just get something :)
                if ($is_html_content) {
                    $pattern = '/input.+?(?:(?:name="dispatch").+?value="(\w+)\.?(\w+)?"|value="(\w+)\.?(\w+)?".+?(?:name="dispatch"))/';
                    if (preg_match($pattern, $data, $m)) {
                        if (empty($m[1]) && !empty($m[3])) {
                            $m[1] = $m[3];
                        }
                        if (empty($m[2]) && !empty($m[4])) {
                            $m[2] = $m[4];
                        }
                    } else {
                        return true;
                    }
                } else {
                    preg_match("/(\w+)\.?(\w+)?/", $data, $m);
                }
            }
        }
    } else {
        $request_method = !empty($request_method) ? $request_method : 'GET';
    }

    $url_object = new Url($data);
    $request_params = $url_object->getQueryParams();

    return fn_check_permissions($m[1], $m[2], 'admin', $request_method, $request_params);
}

function fn_check_html_view_permissions($data, $request_method = '')
{
    return fn_check_view_permissions($data, $request_method, true);
}

/**
 * Used in templates to check access to forms
 *
 * @return boolean True, if form should be restricted, false if form should be processed as usual
 */
function fn_check_form_permissions()
{
    if (Registry::get('runtime.company_id') || defined('RESTRICTED_ADMIN')) {
        return !fn_check_permissions(Registry::get('runtime.controller'), Registry::get('runtime.mode'), 'admin', 'POST');
    } else {
        return false;
    }
}

/**
 * Check permission for change store mode
 *
 * @return bool
 */
function fn_check_change_store_mode_permission()
{
    $auth = Tygh::$app['session']['auth'];

    return isset($auth['user_type']) && $auth['user_type'] === 'A' && empty($auth['company_id']);
}

/**
 * Checks permission for changing storefront in admin area
 *
 * @return bool
 */
function fn_check_change_storefront_permission()
{
    $auth = Tygh::$app['session']['auth'];

    return isset($auth['user_type']) && (
        ($auth['user_type'] === UserTypes::ADMIN && empty($auth['company_id']))
        || $auth['user_type'] === UserTypes::VENDOR
    );
}

/**
 * This function searches placeholders in the text and converts the found data.
 *
 * @param string $text
 *
 * @return string changed text
 */
function fn_text_placeholders($text)
{
    static $placeholders = array(
        'price',
        'weight',
    );

    $pattern = '/%([,\.\w]+):(' . implode('|', $placeholders) . ')%/U';
    $text = preg_replace_callback($pattern, 'fn_apply_text_placeholders', $text);

    return $text;
}

function fn_apply_text_placeholders($matches)
{
    if (isset($matches[1]) && !empty($matches[2])) {
        if ($matches[2] == 'price') {
            $currencies = Registry::get('currencies');
            $currency = $currencies[CART_SECONDARY_CURRENCY];
            $value = fn_format_rate_value($matches[1], 'F', $currency['decimals'], $currency['decimals_separator'], $currency['thousands_separator'], $currency['coefficient']);

            return $currency['after'] == 'Y' ? $value . $currency['symbol'] : $currency['symbol'] . $value;
        } elseif ($matches[2] == 'weight') {
            return $matches[1] . '&nbsp;' . Registry::get('settings.General.weight_symbol');
        }
    }
}

function fn_generate_code($prefix = '', $length = 12)
{
    $postfix = '';
    $chars = implode('', range('0', '9')) . implode('', range('A', 'Z'));

    for ($i = 0; $i < $length; $i++) {
        $ratio = (strlen(str_replace('-', '', $postfix)) + 1) / 4;
        $postfix .= $chars[rand(0, strlen($chars) - 1)];
           $postfix .= ((ceil($ratio) == $ratio) && ($i < $length - 1)) ? '-' : '';
    }

    return (!empty($prefix)) ?  strtoupper($prefix) . '-' . $postfix : $postfix;
}

function fn_get_shipping_images()
{
    $data = db_get_array("SELECT ?:shippings.shipping_id, ?:shipping_descriptions.shipping FROM ?:shippings INNER JOIN ?:images_links ON ?:shippings.shipping_id = ?:images_links.object_id AND ?:images_links.object_type = 'shipping' LEFT JOIN ?:shipping_descriptions ON ?:shippings.shipping_id = ?:shipping_descriptions.shipping_id AND ?:shipping_descriptions.lang_code = ?s WHERE ?:shippings.status = 'A' ORDER BY ?:shippings.position, ?:shipping_descriptions.shipping", CART_LANGUAGE);

    if (empty($data)) {
        return array ();
    }

    $images = array ();

    foreach ($data as $key => $entry) {
        $image = fn_get_image_pairs($entry['shipping_id'], 'shipping', 'M');

        if (!empty($image['icon'])) {
            $image['icon']['alt'] = empty($image['icon']['alt']) ? $entry['shipping'] : $image['icon']['alt'];
            $images[] = $image['icon'];
        }
    }

    return $images;
}

function fn_get_payment_methods_images()
{
    $data = db_get_array("SELECT ?:payments.payment_id, ?:payment_descriptions.payment FROM ?:payments INNER JOIN ?:images_links ON ?:payments.payment_id = ?:images_links.object_id AND ?:images_links.object_type = 'payment' LEFT JOIN ?:payment_descriptions ON ?:payments.payment_id = ?:payment_descriptions.payment_id AND ?:payment_descriptions.lang_code = ?s WHERE ?:payments.status = 'A' ORDER BY ?:payments.position, ?:payment_descriptions.payment", CART_LANGUAGE);

    if (empty($data)) {
        return array ();
    }

    $images = array ();

    foreach ($data as $key => $entry) {
        $image = fn_get_image_pairs($entry['payment_id'], 'payment', 'M');

        if (!empty($image['icon'])) {
            $image['icon']['alt'] = empty($image['icon']['alt']) ? $entry['payment'] : $image['icon']['alt'];
            $images[] = $image['icon'];
        }
    }

    return $images;
}

//
// Helper function: trims trailing and leading spaces
//
function fn_trim_helper(&$value)
{
    $value = is_string($value) ? trim($value) : $value;
}

/**
 * Sort array by key
 * @param array $array Array to be sorted
 * @param string $key Array key to sort by
 * @param int $order Sort order (SORT_ASC/SORT_DESC)
 * @return array Sorted array
 */
function fn_sort_array_by_key($array, $key, $order = SORT_ASC)
{
    $result = array();
    $values = array();
    foreach ($array as $id => $value) {
        $values[$id] = isset($value[$key]) ? $value[$key] : '';

        if (!is_numeric($values[$id])) {
            $values[$id] = strtolower($values[$id]);
        }
    }

    if ($order == SORT_ASC) {
        asort($values);
    } else {
        arsort($values);
    }

    foreach ($values as $key => $value) {
        $result[$key] = $array[$key];
    }

    return $result;
}

/**
* Explode string by delimiter and trim values
*
* @param string $delim - delimiter to explode by
* @param string $string - string to explode
* @return array
*/
function fn_explode($delim, $string)
{
    $a = explode($delim, $string);
    array_walk($a, 'fn_trim_helper');

    return $a;
}

/**
* Formats date using current language
*
* @param int $timestamp - timestamp of the date to format
* @param string $format - format string (see strftim)
* @return string formatted date
*/
function fn_date_format($timestamp, $format = '%b %e, %Y')
{
    if (substr(PHP_OS,0,3) == 'WIN') {
        $hours = strftime('%I', $timestamp);
        $short_hours = ($hours < 10) ? substr($hours, -1) : $hours;
        $_win_from = array ('%e', '%T', '%D', '%l');
        $_win_to = array ('%d', '%H:%M:%S', '%m/%d/%y', $short_hours);
        $format = str_replace($_win_from, $_win_to, $format);
    }

    $date = getdate($timestamp);
    $m = $date['mon'];
    $d = $date['mday'];
    $y = $date['year'];
    $w = $date['wday'];
    $hr = $date['hours'];
    $pm = ($hr >= 12);
    $ir = ($pm) ? ($hr - 12) : $hr;
    $dy = $date['yday'];
    $fd = getdate(mktime(0, 0, 0, 1, 1, $y)); // first day of year
    $wn = date('W', $timestamp);
    if ($ir == 0) {
        $ir = 12;
    }
    $min = $date['minutes'];
    $sec = $date['seconds'];

    // Preload language variables if needed
    $preload = array();
    if (strpos($format, '%a') !== false) {
        $preload[] = 'weekday_abr_' . $w;
    }
    if (strpos($format, '%A') !== false) {
        $preload[] = 'weekday_' . $w;
    }

    if (strpos($format, '%b') !== false) {
        $preload[] = 'month_name_abr_' . $m;
    }

    if (strpos($format, '%B') !== false) {
        $preload[] = 'month_name_' . $m;
    }

    LanguageHelper::preloadLangVars($preload);

    $s['%a'] = __('weekday_abr_'. $w); // abbreviated weekday name
    $s['%A'] = __('weekday_'. $w); // full weekday name
    $s['%b'] = __('month_name_abr_' . $m); // abbreviated month name
    $s['%B'] = __('month_name_' . $m); // full month name
    $s['%c'] = ''; // !!!FIXME: preferred date and time representation for the current locale
    $s['%C'] = 1 + floor($y / 100); // the century number
    $s['%d'] = ($d < 10) ? ('0' . $d) : $d; // the day of the month (range 01 to 31)
    $s['%e'] = $d; // the day of the month (range 1 to 31)
    $s['%ð'] = $s['%b'];
    $s['%H'] = ($hr < 10) ? ('0' . $hr) : $hr; // hour, range 00 to 23 (24h format)
    $s['%I'] = ($ir < 10) ? ('0' . $ir) : $ir; // hour, range 01 to 12 (12h format)
    $s['%j'] = ($dy < 100) ? (($dy < 10) ? ('00' . $dy) : ('0' . $dy)) : $dy; // day of the year (range 001 to 366)
    $s['%k'] = $hr; // hour, range 0 to 23 (24h format)
    $s['%l'] = $ir; // hour, range 1 to 12 (12h format)
    $s['%m'] = ($m < 10) ? ('0' . $m) : $m; // month, range 01 to 12
    $s['%M'] = ($min < 10) ? ('0' . $min) : $min; // minute, range 00 to 59
    $s['%n'] = "\n"; // a newline character
    $s['%p'] = $pm ? 'PM' : 'AM';
    $s['%P'] = $pm ? 'pm' : 'am';
    $s['%s'] = floor($timestamp / 1000);
    $s['%S'] = ($sec < 10) ? ('0' . $sec) : $sec; // seconds, range 00 to 59
    $s['%t'] = "\t"; // a tab character
    $s['%T'] = $s['%H'] .':'. $s['%M'] .':'. $s['%S'];
    $s['%U'] = $s['%W'] = $s['%V'] = $wn;
    $s['%u'] = $w + 1;  // the day of the week (range 1 to 7, 1 = MON)
    $s['%w'] = $w; // the day of the week (range 0 to 6, 0 = SUN)
    $s['%y'] = substr($y, 2, 2); // year without the century (range 00 to 99)
    $s['%Y'] = $y; // year with the century
    $s['%%'] = '%'; // a literal '%' character
    $s['%D'] = $s['%m'] .'/'. $s['%d'] .'/'. $s['%y']; // american date style: %m/%d/%y
    // FIXME: %x : preferred date representation for the current locale without the time
    // FIXME: %X : preferred time representation for the current locale without the date
    // FIXME: %G, %g (man strftime)
    // FIXME: %r : the time in am/pm notation %I:%M:%S %p
    // FIXME: %R : the time in 24-hour notation %H:%M
    return preg_replace_callback("/(%.)/", function($m) use ($s) {
        if (isset($s[$m[1]])) {
            return $s[$m[1]];
        } else {
            return false;
        }
    }, $format);
}

function fn_get_current_mode($request = array())
{
    if (empty($request['set_current_mode'])) {
        $current_mode = fn_get_storage_data('store_mode');
    } else {
        $current_mode = $request['set_current_mode'];
    }

    if (isset(Tygh::$app['view'])) {
        Tygh::$app['view']->assign(str_rot13('fgber_gevttre'), $current_mode);
    }

    return $current_mode;
}

function fn_text_diff($source, $dest, $side_by_side = false)
{
    $diff = new Text_Diff('auto', array(explode("\n", $source), explode("\n", $dest)));
    $renderer = new Text_Diff_Renderer_inline();

    if ($side_by_side == false) {
        $renderer->_split_level = 'words';
    }

    $res = $renderer->render($diff);

    if ($side_by_side == true) {
        $res = $renderer->sideBySide($res);
    }

    return $res;
}

/**
 * Sets storefront status (opened/closed)
 *
 * @param string $store_mode Store operation mode: open/closed
 * @param int    $company_id Company id
 *
 * @return boolean always true
 */
function fn_set_store_mode($store_mode, $company_id = null)
{
    // FIXME: Backward compatibility
    $store_mode = $store_mode === 'opened'
        ? 'open'
        : $store_mode;

    $is_mve_and_vendor_selected = !fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id');
    if ($is_mve_and_vendor_selected) {
        return false;
    }

    $result = true;
    $allowed_modes = [
        'open',
        'closed',
    ];

    if (in_array($store_mode, $allowed_modes)) {
        $mode = $store_mode == 'open'
            ? StorefrontStatuses::OPEN
            : StorefrontStatuses::CLOSED;

        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];

        if ($company_id) {
            $storefront = $repository->findByCompanyId($company_id);
        } else {
            $storefront = $repository->findDefault();
        }

        $storefront->status = $mode;
        $result = $repository->save($storefront);

        if ($result->isSuccess()) {
            fn_set_notification('W', __('information'), __('text_store_mode_' . $store_mode));
        }
    }

    return $result;
}

/**
 * Create array using $keys for keys and $value for values
 *
 * @param array $keys array keys
 * @param mixed $values if string/boolean, values array will be recreated with this value (e.g. $keys = array(1,2,3),
 *                      $values = true => $values = array(0=>true,1=>true,2=>true))
 * @return array combined array
 */
function fn_array_combine($keys, $values)
{
    if (empty($keys)) {
        return array();
    }

    if (!is_array($values)) {
        $values = array_fill(0, sizeof($keys), $values);
    }

    return array_combine($keys, $values);
}

/**
 * Return cleaned text string (for meta description use)
 *
 * @param string $html - html code to generate description from
 * @param int $max_letters - maximum letters in description
 *
 * @return string - cleaned text
 */
function fn_generate_meta_description($html, $max_letters = 250)
{
    $meta = array();
    if (!empty($html)) {
        $html = str_replace('&nbsp;', ' ', $html);
        $html = str_replace(array("\r\n", "\n", "\r"), ' ', html_entity_decode(trim($html), ENT_QUOTES, 'UTF-8'));
        $html = preg_replace('/\<br(\s*)?\/?\>/i', " ", $html);
        $html = preg_replace('|<style[^>]*>.*?</style>|i', '', $html);
        $html = strip_tags($html);
        $html = str_replace(array('.', ',', ':', ';', '`', '"', '~', '\'', '(', ')'), ' ', $html);
        $html = preg_replace('/\s+/', ' ', $html);
        $html = explode(' ', $html);
        $letters_count = 0;
        foreach ($html as $k => $v) {
            $letters_count += fn_strlen($v);
            if ($letters_count <= $max_letters) {
                $meta[] = $v;
            } else {
                break;
            }
        }
    }

    return implode(' ', $meta);
}

/**
 * Calculate unsigned crc32 sum
 *
 * @param string $key - key to calculate sum for
 *
 * @return int - crc32 sum
 */
function fn_crc32($key)
{
    return sprintf('%u', crc32($key));
}

/**
 * Checks whether string is UTF-8 encoded.
 *
 * @param string $str String to check encoding for
 *
 * @return boolean
 */
function fn_is_utf8($str)
{
    $c = 0; $b = 0;
    $bits = 0;
    $len = strlen($str);
    $byte_order_mark = chr(239) . chr(187) . chr(191);

    if (substr($str, 0, 3) === $byte_order_mark) {
        //string is UTF-8 with BOM
        return true;
    }

    for ($i = 0; $i < $len; $i++) {
        $c = ord($str[$i]);
        if ($c > 128) {
            if (($c >= 254)) {
                return false;
            } elseif ($c >= 252) {
                $bits = 6;
            } elseif ($c >= 248) {
                $bits = 5;
            } elseif ($c >= 240) {
                $bits = 4;
            } elseif ($c >= 224) {
                $bits = 3;
            } elseif ($c >= 192) {
                $bits = 2;
            } else {
                return false;
            }

            if (($i + $bits) > $len) {
                return false;
            }

            while ($bits > 1) {
                $i++;
                $b = ord($str[$i]);
                if ($b < 128 || $b > 191) {
                    return false;
                }
                $bits--;
            }
        }
    }

    return true;
}

/**
 * Checks whether string is UTF-16BE encoded.
 *
 * @param string $str String to check encoding for
 *
 * @return boolean
 */
function fn_is_utf16be($str)
{
    $utf16_big_endian_bom = chr(0xFE) . chr(0xFF);
    $first = substr($str, 0, 2);

    return $first == $utf16_big_endian_bom;
}

/**
 * Checks whether string is UTF-16LE encoded.
 *
 * @param string $str String to check encoding for
 *
 * @return boolean
 */
function fn_is_utf16le($str)
{
    $utf16_little_endian_bom = chr(0xFF) . chr(0xFE);
    $first = substr($str, 0, 2);

    return $first == $utf16_little_endian_bom;
}

/**
 * Detect the cyrillic encoding of string.
 *
 * @param string $str
 *
 * @return string cyrillic encoding
 */
function fn_detect_cyrillic_charset($str)
{
    fn_define('LOWERCASE', 3);
    fn_define('UPPERCASE', 1);

    $charsets = array(
        'KOI8-R' => 0,
        'CP1251' => 0,
        'CP866' => 0,
        'ISO-8859-5' => 0,
        'MAC-CYRILLIC' => 0,
    );

    for ($i = 0, $length = strlen($str); $i < $length; $i++) {
        $char = ord($str[$i]);
        //non-russian characters
        if ($char < 128 || $char > 256) {
            continue;
        }

        //CP866
        if (($char > 159 && $char < 176) || ($char > 223 && $char < 242)) {
            $charsets['CP866'] += LOWERCASE;
        }

        if (($char > 127 && $char < 160)) {
            $charsets['CP866'] += UPPERCASE;
        }

        //KOI8-R
        if (($char > 191 && $char < 223)) {
            $charsets['KOI8-R'] += LOWERCASE;
        }
        if (($char > 222 && $char < 256)) {
            $charsets['KOI8-R'] += UPPERCASE;
        }

        //CP1251
        if ($char > 223 && $char < 256) {
            $charsets['CP1251'] += LOWERCASE;
        }
        if ($char > 191 && $char < 224) {
            $charsets['CP1251'] += UPPERCASE;
        }

        //MAC-CYRILLIC
        if ($char > 221 && $char < 255) {
            $charsets['MAC-CYRILLIC'] += LOWERCASE;
        }
        if ($char > 127 && $char < 160) {
            $charsets['MAC-CYRILLIC'] += UPPERCASE;
        }

        //ISO-8859-5
        if ($char > 207 && $char < 240) {
            $charsets['ISO-8859-5'] += LOWERCASE;
        }
        if ($char > 175 && $char < 208) {
            $charsets['ISO-8859-5'] += UPPERCASE;
        }
    }

    arsort($charsets);

    return current($charsets) > 0 ? key($charsets) : '';
}

/**
 * Detect encoding by language
 *
 * @param string $resource string or file path
 * @param string $resource_type 'S' (string) or 'F' (file)
 * @param string $lang_code language of the file characters
 * @return string  detected encoding
 */

function fn_detect_encoding($resource, $resource_type = 'S', $lang_code = CART_LANGUAGE)
{
    $enc = '';
    $str = $resource;

    if ($resource_type == 'F') {
        $str = file_get_contents($resource);
        if ($str == false) {
            return $enc;
        }
    }

    if (!fn_is_utf8($str)) {
        if (fn_is_utf16be($str)) {
            $enc = 'UTF-16BE';
        } elseif (fn_is_utf16le($str)) {
            $enc = 'UTF-16LE';
        } elseif (in_array($lang_code, array('en', 'fr', 'es', 'it', 'nl', 'da', 'fi', 'sv', 'pt', 'nn', 'no'))) {
            $enc = 'ISO-8859-1';
        } elseif (in_array($lang_code, array('hu', 'cs', 'pl', 'bg', 'ro'))) {
            $enc = 'ISO-8859-2';
        } elseif (in_array($lang_code, array('et', 'lv', 'lt'))) {
            $enc = 'ISO-8859-4';
        } elseif ($lang_code == 'ru') {
            $enc = fn_detect_cyrillic_charset($str);
        } elseif ($lang_code == 'ar') {
            $enc = 'ISO-8859-6';
        } elseif ($lang_code == 'el') {
            $enc = 'ISO-8859-7';
        } elseif ($lang_code == 'he') {
            $enc = 'ISO-8859-8';
        } elseif ($lang_code == 'tr') {
            $enc = 'ISO-8859-9';
        }
    } else {
        $enc = 'UTF-8';
    }

    return $enc;
}

/**
 * Convert encoding of string or file
 *
 * @param string $from_enc      the encoding of the initial string/file
 * @param string $to_enc        the encoding of the result string/file
 * @param string $resource      string or file path
 * @param string $resource_type 'S' (string) or 'F' (file)
 *
 * @return string  string or file path
 */
function fn_convert_encoding($from_enc, $to_enc, $resource, $resource_type = 'S')
{
    if (empty($from_enc) || empty($to_enc) || ($resource_type == 'F' && empty($resource))) {
        return false;
    }

    if (strtoupper($from_enc) == strtoupper($to_enc)) {
        return $resource;
    }

    $str = $resource;
    if ($resource_type == 'F') {
        $str = file_get_contents($resource);
        if ($str == false) {
            return false;
        }
    }

    $_str = false;
    if (function_exists('iconv')) {
        if (strtoupper($to_enc) == 'UCS-2') {
            $to_enc = 'UCS-2BE';
        }
        $_str = iconv($from_enc, $to_enc . '//TRANSLIT', $str);
    } elseif (function_exists('mb_convert_encoding')) {
        $_str = mb_convert_encoding($str, $to_enc, $from_enc);
    }

    if ($resource_type == 'F') {
        if ($_str != false) {
            $f = fopen($resource, 'wb');
            if ($f) {
                fwrite($f, $_str);
                fclose($f);
                @chmod($resource, DEFAULT_FILE_PERMISSIONS);
            } else {
                $resource = false;
            }
        }

        return $resource;
    } else {
        return $_str;
    }
}

function fn_check_meta_redirect($url)
{
    if (empty($url)) {
        return false;
    }

    if (strpos($url, '://') && !(strpos($url, Registry::get('config.http_location')) === 0 || strpos($url, Registry::get('config.https_location')) === 0)) {
        return false;
    } else {
        return $url;
    }
}

function fn_get_notification_rules($params, $disable_notification = false)
{
    $force_notification = array();
    if ($disable_notification) {
        $force_notification = array('C' => false, 'A' => false, 'V' => false);
    } else {
        if (!empty($params['notify_user']) || $params === true) {
            $force_notification['C'] = true;
        } else {
            if (AREA == 'A') {
                $force_notification['C'] = false;
            }
        }
        if (!empty($params['notify_department']) || $params === true) {
            $force_notification['A'] = true;
        } else {
            if (AREA == 'A') {
                $force_notification['A'] = false;
            }
        }
        if (!empty($params['notify_vendor']) || $params === true) {
            $force_notification['V'] = true;
        } else {
            if (AREA == 'A') {
                $force_notification['V'] = false;
            }
        }
    }

    fn_set_hook('get_notification_rules', $force_notification, $params, $disable_notification);

    return $force_notification;
}

/**
* Generate security hash to protect forms from CRSF attacks
*
* @return string salted hash
*/
function fn_generate_security_hash()
{
    static $session;

    if ($session === null) {
        $session = Tygh::$app['session'];
    }

    if (empty($session['security_hash'])) {
        $session['security_hash'] = md5(Registry::get('config.crypt_key') . $session->getID());
    }

    return $session['security_hash'];
}

/**
 * substr() with full UTF-8 support
 *
 * @param string $string The input string.
 * @param integer $start If start  is non-negative, the returned string will start at the start 'th position in string
 *                       , counting from zero. If start is negative, the returned string will start at the start 'th
 *                       character from the end of string.
 * @param integer $length  If length  is given and is positive, the string returned will contain at most length
 *                         characters beginning from start  (depending on the length of string ). If length is given
 *                         and is negative, then that many characters will be omitted from the end of string (after the
 *                         start position has been calculated when a start is negative). If start denotes a position
 *                         beyond this truncation, an empty string will be returned.
 * @param integer $encoding The encoding parameter is the character encoding. If it is omitted, UTF-8 character
 *                          encoding value will be used.
 * @return mixed Returns the extracted part of string or false if string is less than or equal to start characters long
 */
function fn_substr($string, $start, $length = null, $encoding = 'UTF-8')
{
    if (empty($encoding)) {
        $encoding = 'UTF-8';
    }

    if ($length === null) {
        return fn_substr($string, $start, fn_strlen($string, $encoding), $encoding);
    }

    if (function_exists('iconv_substr')) {
        // there was strange bug in iconv_substr when use negative length parameter
        // so we recalculate start and length here
        if ($length < 0) {
            $length = ceil($length);
            $len = iconv_strlen($string, $encoding);
            if ($start < 0) {
                $start += $len;
            }
            $length += $len - $start;
        }

        return iconv_substr($string, $start, $length, $encoding);
    } elseif (function_exists('mb_substr')) {
        return mb_substr($string, $start, $length, $encoding);
    } else {
        preg_match_all('/./su', $string, $ar);

        return join('', array_slice($ar[0], $start, $length));
    }
}

/**
 * strlen() with full UTF-8 support
 *
 * @param string $string The string being measured for length.
 * @param string $encoding The encoding parameter is the character encoding. If it is omitted, UTF-8 character encoding
 *                         value will be used.
 * @return integer The length of the string on success, and 0 if the string is empty.
 */
function fn_strlen($string, $encoding = 'UTF-8')
{
    if (empty($encoding)) {
        $encoding = 'UTF-8';
    }

    if (function_exists('mb_strlen')) {
        return mb_strlen($string, $encoding);
    } elseif (function_exists('iconv_strlen')) {
        return @iconv_strlen($string, $encoding);
    } else {
        preg_match_all('/./su', $string, $ar);

        return count($ar[0]);
    }
}

/**
 * Converts URN to URI
 *
 * @param string      $url       URN (Uniform Resource Name or Query String)
 * @param string      $area      Area
 * @param string      $protocol  Output URL protocol (protocol://). If equals 'rel', no protocol will be included
 * @param string|null $lang_code Two letters language code. If null, the language code will not be substituted in the URL
 *
 * @return string URI
 */
function fn_url($url = '', $area = AREA, $protocol = 'current', $lang_code = CART_LANGUAGE)
{
    static $init_vars = false;
    static $indexes, $_admin_index, $vendor_index, $customer_index, $locations, $storefront_locations;
    static $undefined_storefront_id = 0;

    $admin_index_area = null;
    if ($area === SiteArea::ADMIN_PANEL) {
        if (strpos($url, 'admin:') === 0) {
            $admin_index_area = SiteArea::ADMIN_PANEL;
            $url = str_replace('admin:', '', $url);
        } elseif (strpos($url, 'vendor:') === 0) {
            $admin_index_area = SiteArea::VENDOR_PANEL;
            $url = str_replace('vendor:', '', $url);
        }
    }

    /**
     * Prepares parameters before building URL
     *
     * @param  string $url       URN (Uniform Resource Name or Query String)
     * @param  string $area      Area
     * @param  string $protocol  Output URL protocol (protocol://). If equals 'rel', no protocol will be included
     * @param  string $lang_code 2 letters language code
     * @return bool   Always true
     */
    fn_set_hook('url_pre', $url, $area, $protocol, $lang_code);

    if (!$init_vars) {
        $conf = Registry::get('config');
        $vendor_index = isset($conf['vendor_index'])
            ? $conf['vendor_index']
            : '';
        $_admin_index = $conf['admin_index'];
        $customer_index = $conf['customer_index'];

        $locations = [];
        $locations['C'] = [
            'http'    => $conf['http_location'],
            'https'   => $conf['https_location'],
            'current' => $conf['current_location'],
            'rel'     => $conf['current_location'],
        ];
        if (AREA !== 'C') {
            /** @var \Tygh\Storefront\Repository $repository */
            $repository = Tygh::$app['storefront.repository'];
            $default_storefront = $repository->findDefault();
            $locations['C'] = [
                'http'    => 'http://' . $default_storefront->url,
                'https'   => 'https://' . $default_storefront->url,
                'current' => defined('HTTPS')
                    ? 'https://' . $default_storefront->url
                    : 'http://' . $default_storefront->url,
                'rel'     => defined('HTTPS')
                    ? 'https://' . $default_storefront->url
                    : 'http://' . $default_storefront->url,
            ];
        }

        $admin_http_location = empty($conf['origin_http_location'])
            ? $conf['http_location']
            : $conf['origin_http_location'];
        $admin_https_location = empty($conf['origin_https_location'])
            ? $conf['https_location']
            : $conf['origin_https_location'];

        $locations['A'] = [
            'http'    => $admin_http_location,
            'https'   => $admin_https_location,
            'current' => defined('HTTPS')
                ? $admin_https_location
                : $admin_http_location,
            'rel'     => defined('HTTPS')
                ? $admin_https_location
                : $admin_http_location,
        ];

        /**
         * Allows to modify locations used for the store's area with the specified protocols
         *
         * @param string $url       URN (Uniform Resource Name or Query String)
         * @param string $area      Area
         * @param string $protocol  Output URL protocol (protocol://). If equals 'rel', no protocol will be included
         * @param string $lang_code 2 letters language code
         * @param array  $locations Locations used for different protocols in store's area.
         */
        fn_set_hook('url_set_locations', $url, $area, $protocol, $lang_code, $locations);

        $storefront_locations[$undefined_storefront_id] = $locations['C'];

        $init_vars = true;
    }

    $storefront_id = (int) fn_get_storefront_id_from_uri($url);
    $company_id_in_url = (int) fn_get_company_id_from_uri($url);

    if ($area === 'C') {
        /**
         * Executes when generating a URL to the storefront from the administation panel or from the vendor panel.
         * Allows you to modify the storefront detection parameters
         *
         * @param string $url               URL
         * @param string $area              URL's area
         * @param string $protocol          URL's protocol
         * @param int    $company_id_in_url Company identifier passed in the URL
         * @param int    $storefront_id     Storefront identifier passed in the URL
         */
        fn_set_hook('url_before_get_storefront_location', $url, $area, $protocol, $lang_code, $company_id_in_url, $storefront_id);

        if (!isset($storefront_locations[$storefront_id])) {
            $storefront = null;
            /** @var \Tygh\Storefront\Repository $repository */
            $repository = Tygh::$app['storefront.repository'];
            if ($storefront_id) {
                $storefront = $repository->findById($storefront_id);
                if ($storefront) {
                    $storefront_locations[$storefront_id] = [
                        'http'    => 'http://' . $storefront->url,
                        'https'   => 'https://' . $storefront->url,
                        'current' => defined('HTTPS')
                            ? 'https://' . $storefront->url
                            : 'http://' . $storefront->url,
                        'rel'     => defined('HTTPS')
                            ? 'https://' . $storefront->url
                            : 'http://' . $storefront->url,
                    ];
                } else {
                    $storefront_id = $undefined_storefront_id;
                }
            }
        }

        $locations['C'] = $storefront_locations[$storefront_id];
    }

    if ($admin_index_area === null) {
        $admin_index_area = SiteArea::ADMIN_PANEL;
        if ($area == SiteArea::ADMIN_PANEL && ACCOUNT_TYPE != 'customer') {
            $admin_index_area = '';
        } elseif ($area != SiteArea::ADMIN_PANEL && $area != SiteArea::STOREFRONT) {
            $admin_index_area = $area;
        }
    }

    if (isset($indexes[$admin_index_area])) {
        $admin_index = $indexes[$admin_index_area];
    } else {
        $admin_index = $indexes[$admin_index_area] = fn_get_index_script($admin_index_area);
    }

    $url = str_replace('&amp;', '&', $url);
    $parsed_url = parse_url($url);
    $no_shorted = false;
    $full_query = false;

    if (!empty($parsed_url['scheme']) || !empty($parsed_url['host'])) {

        if (!empty($parsed_url['scheme'])) { // do not prefix URL is its absolute already
            $protocol = 'no_prefix';
        }

        $no_shorted = true;
    } else {
        if (!empty($parsed_url['path'])) {
            if (stripos($parsed_url['path'], $_admin_index) !== false) {
                $area = 'A';
                $no_shorted = true;
            } elseif (stripos($parsed_url['path'], $customer_index) !== false) {
                $area = 'C';
                $no_shorted = true;
            } elseif (!empty($vendor_index) && stripos($parsed_url['path'], $vendor_index) !== false) {
                $area = 'A';
                $no_shorted = true;
            }
        } else {
            if (strpos($url, '?') === 0) {
                $full_query = true;
            } else {
                $no_shorted = true;
                $url = $_url = ($area == 'C') ? $customer_index : $admin_index;
            }
        }
    }

    $index_script = ($area == 'C') ? $customer_index : $admin_index;

    if ($no_shorted) {
        // full url passed
        $_url = $url;
    } elseif ($full_query) {
        // full query passed
        $_url = $index_script . $url;
    } else {
        $_url = $index_script . '?dispatch=' . str_replace('?', '&', $url);
    }

    if (in_array($protocol, array('http', 'https', 'current')) || defined('DISPLAY_FULL_PATHS')) {
        $target_protocol = defined('DISPLAY_FULL_PATHS') ? 'current' : $protocol;

        if ($target_protocol == 'current' && defined('CONSOLE')) {
            $target_protocol = fn_get_console_protocol($area);
        }

        $_url = $locations[$area][$target_protocol] . '/' . $_url;
    }

    if ($area === 'C') {
        $_url = fn_query_remove($_url, 'storefront_id');
    }

    /**
     * Prepares parameters before building URL
     *
     * @param string $_url              Output URL
     * @param string $area              Area
     * @param string $url               Input URL
     * @param string $lang_code         2 letters language code
     * @param string $protocol          Output URL protocol (protocol://). If equals 'rel', no protocol will be included
     * @param int    $company_id_in_url Equals company_id if it is present in $url, otherwise false
     * @param array  $locations         Locations
     * @param int    $storefront_id     Storefront ID represented in URL
     */
    fn_set_hook('url_post', $_url, $area, $url, $protocol, $company_id_in_url, $lang_code, $locations, $storefront_id);

    return $_url;
}

/**
 * Returns company_id if it is present in $uri, otherwise false
 *
 * @param string $uri URI | URN
 * @return int|bool company_id if it is present in $uri, otherwise false
 */
function fn_get_company_id_from_uri($uri)
{
    $company_id = false;

    if (preg_match("%(\?|&|&amp;)company_id=(\d+)%", $uri, $match)) {
        if (!empty($match[2])) {
            $company_id = $match[2];
        }
    }

    return $company_id;
}

/**
 * Returns storefront ID if it is present in $uri, otherwise false.
 *
 * @param string $uri URI | URN
 *
 * @return int|bool Storefront ID if it is present in $uri, otherwise false
 */
function fn_get_storefront_id_from_uri($uri)
{
    $storefront_id = false;

    if (preg_match('%(\?|&|&amp;)storefront_id=(\d+)%', $uri, $match)) {
        if (!empty($match[2])) {
            $storefront_id = $match[2];
        }
    }

    return $storefront_id;
}

/**
 * Checks can user get access to the some area or not.
 *
 * @param array  $user_data    Array with user data
 * @param string $account_type string First char of account type (Customer, Vendor, Admin)
 *
 * @return bool True, if user can access area, defined in the const ACCOUNT_TYPE, false otherwise
 */
function fn_check_user_type_access_rules($user_data, $account_type = ACCOUNT_TYPE)
{
    $rules = array(
        'A' => array('admin', 'customer'),
        'V' => array('vendor', 'customer'),
        'C' => array('customer'),
    );

    /**
     * Hook for changing incoming parameters and access rules.
     *
     * @param array  $user_data    Array with user data
     * @param string $account_type String First char of account type (Customer, Vendor, Admin)
     * @param array  $rules        Array with access rules, key is user_type, value is array(list) of available areas
     */
    fn_set_hook('check_user_type_access_rules_pre', $user_data, $account_type, $rules);

    $result = !empty($user_data['user_type']) && isset($rules[$user_data['user_type']]) && in_array($account_type, $rules[$user_data['user_type']]);

    /**
     * Hook for changing the result of checking.
     *
     * @param array  $user_data    Array with user data
     * @param string $account_type String First char of account type (Customer, Vendor, Admin)
     * @param array  $rules        Array with access rules, key is user_type, value is array(list) of available areas
     * @param bool   $result       bool Result of the check
     */
    fn_set_hook('check_user_type_access_rules_pre_post', $user_data, $account_type, $rules, $result);

    return $result;
}

/**
 * Check for non empty string
 *
 * @param string $str string
 * @return boolean string is not empty?
 */
function fn_string_not_empty($str)
{
    return strlen((trim($str))) > 0;
}

/**
 * Check for number
 *
 * @param string $num number
 * @return boolean string is number?
 */
function fn_is_numeric($num)
{
    return is_numeric(trim($num));
}

/**
 * Converts given value to integer or float type.
 *
 * Examples (input -> output):
 * '10' -> (int) 10
 * '10,5' -> (float) 10.5
 * null -> (int) 0
 * '10asd' -> (int) 10
 *
 * @param mixed $value
 * @return int|float
 */
function fn_convert_to_numeric($value)
{
    return str_replace(',', '.', trim($value)) + 0;
}

/**
 * @Fancy recursive function to search for substring in an array
 * @param string $neele
 * @param mixed $haystack
 * @return bool
 * @author andyye
 */
function fn_substr_in_array($what_str, $where_arr)
{
    foreach ($where_arr as $v) {
        if (is_array($v)) {
            $sub_arr = fn_substr_in_array($what_str, $v);
            if ($sub_arr) {
                return true;
            }
        } else {
            if (strpos($v, $what_str) !== false) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Converts string representation of size to bytes.
 *
 * @param string|int $val Size as specified in php.ini.
 *
 * @return int Size in bytes
 */
function fn_return_bytes($val)
{
    if ($val) {
        $suffix = fn_strtolower($val[fn_strlen($val) - 1]);
        $val = (int)$val;

        switch ($suffix) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
    }

    return $val;
}

/**
 * Funtion formats user-entered price into float.
 *
 * @param string $price
 * @param string $currency
 * @return float Well-formatted price.
 */
function fn_parse_price($price, $currency = CART_PRIMARY_CURRENCY)
{
    $decimals = Registry::get('currencies.' . $currency . '.decimals');
    $dec_sep = Registry::get('currencies.' . $currency . '.decimals_separator');
    $thous_sep = Registry::get('currencies.' . $currency . '.thousands_separator');

    if ($dec_sep == $thous_sep) {
        if (($last = strrpos($price, $dec_sep)) !== false) {
            if ($thous_sep == '.') {
                $price = str_replace('.', ',', $price);
            }
            $price = substr_replace($price, '.', $last, 1);
        }
    } else {
        if ($thous_sep == '.') {
            // is it really thousands separator?
            // if there is decimals separator, than we can replace ths_sep
            if (strpos($price, $dec_sep) !== false) {
                $price = str_replace($thous_sep, '', $price);
            } else {
                //if there are 3 digits rigth of the separator - it is ths_sep too.
                if (($last = strrpos($price, '.')) !== false) {
                    $last_part = substr($price, $last);
                    $last_part = preg_replace('/[^\d]/', '', $last_part);
                    if (strlen($last_part) == 3 && $decimals != 3) {
                        $price = str_replace($thous_sep, '', $price);
                    }
                }
            }
        }

        if ($dec_sep != '.') {
            $price = str_replace($dec_sep, '.', $price);
        }
    }

    $price = preg_replace('/[^\d\.]/', '', $price);

    return round(floatval($price), $decimals);
}

/**
 * Function replaces default table prefix to user's prefix.
 *
 * @param string $query Query
 * @return string Updated query
 */
function fn_check_db_prefix($query, $table_prefix = '', $default_table_prefix = DEFAULT_TABLE_PREFIX)
{
    if (empty($table_prefix)) {
        $table_prefix = Registry::get('config.table_prefix');
    }

    if ($table_prefix != $default_table_prefix) {
        $query = preg_replace('/(\s|`){1}' . $default_table_prefix . '/', '${1}' . $table_prefix, $query);
    }

    return $query;
}

/**
 * Function returns the index script for requested data.
 *
 * @param mixed $for. If array is given, then index script will be returned for $for['user_type'].
 * If $for is empty, index script will be returned for defined ACCOUNT_TYPE
 * The following string are allowed: 'A', 'admin', 'V', 'vendor', 'C', 'customer'
 * @return string Path to index script
 */
function fn_get_index_script($for = '')
{
    if (is_array($for)) {
        $for = !empty($for['user_type']) ? $for['user_type'] : '';
    }

    if (empty($for) || !in_array($for, array('A', 'admin', 'V', 'vendor', 'C', 'customer'))) {
        $for = ACCOUNT_TYPE;
    } elseif ($for == 'A') {
        $for = 'admin';
    } elseif ($for == 'V') {
        $for = 'vendor';
    } elseif ($for == 'C') {
        $for = 'customer';
    }

    return Registry::get("config.{$for}_index");
}

/**
 * Updates statuses
 * @param string $status One letter status code that should be updated
 * @param array $status_data Status information
 * @param string $type One letter status type
 * @param string $lang_code Language code
 * @return array Updated status data
 */
function fn_update_status($status, $status_data, $type, $lang_code = DESCR_SL)
{
    if (empty($status_data['status'])) {
        // Generate new status code
        $existing_codes = db_get_fields('SELECT status FROM ?:statuses WHERE type = ?s GROUP BY status', $type);
        $existing_codes[] = 'N'; // \Tygh\Enum\OrderStatuses::INCOMPLETED
        $existing_codes[] = 'T'; // \Tygh\Enum\OrderStatuses::PARENT
        $codes = array_diff(range('A', 'Z'), $existing_codes);
        $status_data['status'] = reset($codes);

        /**
         * Allows to change status code for added status
         *
         * @param string $status         One-letter status code
         * @param array  $status_data    Status description and properties
         * @param string $type           One-letter status type
         * @param string $lang_code      Two-letter language code
         * @param array  $existing_codes List of one-letter existing in db status code
         */
        fn_set_hook('update_status_new_status_code', $status, $status_data, $type, $lang_code, $existing_codes);

    } else {
        $is_default = !empty($status_data['is_default']) && $status_data['is_default'] == 'Y';
        $status_data['status_id'] = !empty($status_data['status_id']) ? $status_data['status_id'] : fn_get_status_id($status_data['status'], $type, $is_default);
    }

    $can_continue = !empty($status_data['status']);

    // Create status to have its identifier
    if ($can_continue && empty($status_data['status_id'])) {
        /** @var \Tygh\Template\Mail\Repository $repository */
        $repository = Tygh::$app['template.mail.repository'];
        /** @var \Tygh\Template\Mail\Service $service */
        $service = Tygh::$app['template.mail.service'];

        $status_data['type'] = $type;
        $status_data['status_id'] = db_query("INSERT INTO ?:statuses ?e", $status_data);

        if ($type == STATUSES_ORDER) {
            foreach (array('A', 'C') as $email_templates_area) {
                $email_template = $repository->findByCodeAndArea('order_notification_default', $email_templates_area);

                if ($email_template) {
                    $service->cloneTemplate(
                        $email_template,
                        array(
                            'code' => 'order_notification.' . strtolower($status_data['status']),
                            'area' => $email_templates_area,
                        )
                    );
                }
            }
        }
    }

    /**
     * Performs additional actions before status description and data updated
     *
     * @param string $status       One-letter status code
     * @param array  $status_data  Status description and properties
     * @param string $type         One-letter status type
     * @param string $lang_code    Two-letter language code
     * @param bool   $can_continue If true, status description and data will be updated
     */
    fn_set_hook('update_status_pre', $status, $status_data, $type, $lang_code, $can_continue);

    SecurityHelper::sanitizeObjectData('status', $status_data);

    if ($can_continue) {
        if (empty($status)) {
            $status = $status_data['status'];
            foreach (Languages::getAll() as $status_data['lang_code'] => $_v) {
                db_query('REPLACE INTO ?:status_descriptions ?e', $status_data);
            }
        } else {
            db_query("UPDATE ?:statuses SET ?u WHERE status_id = ?i", $status_data, $status_data['status_id']);
            db_query('UPDATE ?:status_descriptions SET ?u WHERE status_id = ?i AND lang_code = ?s', $status_data, $status_data['status_id'], $lang_code);
        }

        if (!empty($status_data['params'])) {
            foreach ((array) $status_data['params'] as $param_name => $param_value) {
                $_data = array(
                    'status_id' => $status_data['status_id'],
                    'param' => $param_name,
                    'value' => $param_value,
                );
                db_query("REPLACE INTO ?:status_data ?e", $_data);
            }
        }
    }

    fn_set_hook('update_status_post', $status, $status_data, $type, $lang_code);

    return $status_data['status'];
}

/**
 * Get simple statuses description (P - Processed, O - Open)
 * @param string $type One letter status type
 * @param boolean $additional_statuses Flag that determines whether additional (hidden) statuses should be retrieved
 * @param boolean $exclude_parent Flag that determines whether parent statuses should be excluded
 * @param string $lang_code Language code
 * @return array Statuses
 */
function fn_get_simple_statuses($type = STATUSES_ORDER, $additional_statuses = false, $exclude_parent = false, $lang_code = CART_LANGUAGE)
{
    $result = array();

    $statuses = fn_get_statuses($type, array(), $additional_statuses, $exclude_parent, $lang_code);

    foreach ($statuses as $key => $status) {
        $result[$key] = $status['description'];
    }

    return $result;
}

/**
 * Gets full information about particular statuses
 *
 * @param string  $type                One-letter status type
 * @param array   $status_to_select    Array of statuses that should be retrieved. If empty, all statuses will be
 * @param boolean $additional_statuses Flag that determines whether additional (hidden) statuses should be
 *                                     retrieved
 * @param boolean $exclude_parent      Flag that determines whether parent statuses should be excluded
 * @param string  $lang_code           Language code
 * @param int     $company_id          Company identifier
 *
 * @return array
 */
function fn_get_statuses(
    $type = STATUSES_ORDER,
    $status_to_select = array(),
    $additional_statuses = false,
    $exclude_parent = false,
    $lang_code = DESCR_SL,
    $company_id = 0
)
{
    /**
     * This hook allows you to modify the input parameters of the function.
     *
     * @param string  $type                One-letter status type
     * @param array   $status_to_select    Array of statuses that should be retrieved. If empty, all statuses will be
     * @param boolean $additional_statuses Flag that determines whether additional (hidden) statuses should be
     *                                     retrieved
     * @param boolean $exclude_parent      Flag that determines whether parent statuses should be excluded
     * @param string  $lang_code           Language code
     * @param int     $company_id          Company identifier
     */
    fn_set_hook(
        'get_statuses_pre',
        $type,
        $status_to_select,
        $additional_statuses,
        $exclude_parent,
        $lang_code,
        $company_id
    );

    $join = db_quote(
        ' LEFT JOIN ?:status_descriptions ON ?:status_descriptions.status_id = ?:statuses.status_id'
        . ' AND ?:status_descriptions.lang_code = ?s',
        $lang_code
    );

    $condition = db_quote(' AND ?:statuses.type = ?s', $type);

    if ($status_to_select) {
        $condition .= db_quote(' AND ?:statuses.status IN (?a)', $status_to_select);
    }

    $params = array('sort_by' => '?:statuses.position', 'sort_order' => 'asc');
    $sort = array('?:statuses.position' => '?:statuses.position');
    $order = db_sort($params, $sort);

    /**
     * This hook allows you to modify the parameters for selection from the database.
     *
     * @param string  $join                The parameters of joining with other tables
     * @param string  $condition           The condition of the selection
     * @param string  $type                One-letter status type
     * @param array   $status_to_select    Array of statuses that should be retrieved. If empty, all statuses will be
     * @param boolean $additional_statuses Flag that determines whether additional (hidden) statuses should be
     *                                     retrieved
     * @param boolean $exclude_parent      Flag that determines whether parent statuses should be excluded
     * @param string  $lang_code           Language code
     * @param int     $company_id          Company identifier
     * @param string  $order               The fields by which sorting must be performed
     */
    fn_set_hook(
        'get_statuses',
        $join,
        $condition,
        $type,
        $status_to_select,
        $additional_statuses,
        $exclude_parent,
        $lang_code,
        $company_id,
        $order
    );

    $statuses = db_get_hash_array(
        'SELECT ?:statuses.*, ?:status_descriptions.* FROM ?:statuses ?p WHERE 1 = 1 ?p ?p',
        'status',
        $join,
        $condition,
        $order
    );

    $statuses_params = db_get_hash_multi_array(
        'SELECT status_id, param, value'
        . ' FROM ?:status_data'
        . ' WHERE status_id IN (?n)',
        array('status_id', 'param'),
        array_keys(fn_get_statuses_by_type($type))
    );

    foreach ($statuses as $status => $status_data) {
        $statuses[$status]['params'] = array();
        if (isset($statuses_params[$status_data['status_id']])) {
            foreach ($statuses_params[$status_data['status_id']] as $param_name => $param_data) {
                $statuses[$status]['params'][$param_name] = $param_data['value'];
            }
        }
    }

    if ($type == STATUSES_ORDER && $additional_statuses && empty($status_to_select)) {
        $statuses[OrderStatuses::INCOMPLETED] = [
            'status' => OrderStatuses::INCOMPLETED,
            'status_id' => null,
            'description' => __('incompleted', '', $lang_code),
            'type' => STATUSES_ORDER,
            'params' => [
                'inventory' => 'I',
            ],
        ];

        if (empty($exclude_parent)) {
            $statuses[OrderStatuses::PARENT] = [
                'status' => OrderStatuses::PARENT,
                'status_id' => null,
                'description' => __('parent_order', '', $lang_code),
                'type' => STATUSES_ORDER,
                'params' => [
                    'inventory' => 'I',
                ],
            ];
        }
    }

    /**
     * This hook allows accessing the statuses retrieved from the database.
     *
     * @param array   $statuses            An array of statuses
     * @param string  $join                The parameters of joining with other tables
     * @param string  $condition           The condition of the selection
     * @param string  $type                One-letter status type
     * @param array   $status_to_select    Array of statuses that should be retrieved. If empty, all statuses will be
     * @param boolean $additional_statuses Flag that determines whether additional (hidden) statuses should be
     *                                     retrieved
     * @param boolean $exclude_parent      Flag that determines whether parent statuses should be excluded
     * @param string  $lang_code           Language code
     * @param int     $company_id          Company identifier
     * @param string  $order               The fields by which sorting must be performed
     */
    fn_set_hook(
        'get_statuses_post',
        $statuses,
        $join,
        $condition,
        $type,
        $status_to_select,
        $additional_statuses,
        $exclude_parent,
        $lang_code,
        $company_id,
        $order
    );

    return $statuses;
}

/**
 * Gets full information about a particular status
 *
 * @param string $status     One letter status code
 * @param string $type       One letter status type
 * @param int    $object_id  Recurring billing: ID of an object to be checked for subscriptions
 * @param string $lang_code  Language code
 * @param int    $company_id Company identifier
 *
 * @return array Status data
 */
function fn_get_status_data($status, $type, $object_id = 0, $lang_code = DESCR_SL, $company_id = 0)
{
    fn_set_hook('get_status_data_pre', $status, $type, $object_id, $lang_code, $company_id);

    if (empty($status) || empty($type)) {
        return array();
    }

    $status_data = fn_get_statuses($type, !is_array($status) ? (array) $status : $status, false, false, $lang_code, $company_id);

    $status_data = reset($status_data);

    fn_set_hook('get_status_data_post', $status_data, $status, $type, $object_id, $lang_code, $company_id);

    return $status_data;
}

/**
 * Gets full information about a particular status by identifier
 * @param int $status_id Status identifier
 * @param string $lang_code Language code
 * @return array Status idata
 */
function fn_get_status_by_id($status_id, $lang_code = DESCR_SL)
{
    $status_data = array();

    $status = db_get_row("SELECT status, type FROM ?:statuses WHERE status_id = ?i", $status_id);
    if (!empty($status)) {
        $status_data = fn_get_status_data($status['status'], $status['type'], 0, $lang_code);
    }

    return $status_data;
}

/**
 * Deletes status
 *
 * @param string $status     One-letter status code
 * @param string $type       One-letter status type
 * @param mixed  $is_default True if status is default, false if status is not default, null otherwise
 *
 * @return bool True or false depending on whether the status is removed
 */
function fn_delete_status($status, $type, $is_default = false)
{
    $status_id = fn_get_status_id($status, $type, $is_default);
    $can_delete = $status_id ? $status : "";

    /**
     * Modifies deleted status parameter
     *
     * @param string $status One-letter status code
     * @param string $type One-letter status type
     * @param string $can_delete One-letter status code if status can be deleted
     * @param mixed  $is_default True if status is default, false if status is not default, null otherwise
     * @param int|null $status_id Status identifier
     */
    fn_set_hook('delete_status_pre', $status, $type, $can_delete, $is_default, $status_id);

    if (!empty($can_delete)) {
        fn_delete_status_by_id($status_id);

        if ($type == STATUSES_ORDER) {
            /** @var \Tygh\Template\Mail\Service $service */
            $service = Tygh::$app['template.mail.service'];
            $service->removeTemplateByCode('order_notification.' . strtolower($status));
        }
    }

    /**
     * Performs additional actions after status removal
     *
     * @param string $status One-letter status code
     * @param string $type One-letter status type
     * @param string $can_delete One-letter status code if status can be deleted
     * @param mixed  $is_default True if status is default, false if status is not default, null otherwise
     * @param int|null $status_id Status identifier
     */
    fn_set_hook('delete_status_post', $status, $type, $can_delete, $is_default, $status_id);

    return !empty($can_delete);
}

function fn_array_to_xml($data)
{
    if (!is_array($data)) {
        return SecurityHelper::escapeHtml($data);
    }

    $return = '';
    foreach ($data as $key => $value) {
        $attr = '';
        if (is_array($value) && is_numeric(key($value))) {
            foreach ($value as $k => $v) {
                $arr = array(
                    $key => $v,
                );
                $return .= fn_array_to_xml($arr);
                unset($value[$k]);
            }
            unset($data[$key]);
            continue;
        }
        if (strpos($key, '@') !== false) {
            $data = explode('@', $key);
            $key = $data[0];
            unset($data[0]);

            if (count($data) > 0) {
                foreach ($data as $prop) {
                    if (strpos($prop, '=') !== false) {
                        $prop = explode('=', $prop);
                        $attr .= ' ' . $prop[0] . '="' . $prop[1] . '"';
                    } else {
                        $attr .= ' ' . $prop . '=""';
                    }
                }
            }
        }
        $return .= '<' . $key . $attr . '>' . fn_array_to_xml($value) . '</' . $key . '>';
    }

    return $return;
}

/**
 * Function print notice that function $old_function is deprecated and must be replaced by $new_function
 * @param string $old_function Name of the old function
 * @param string $new_function Name of the new function
 */
function fn_generate_deprecated_function_notice($old_function, $new_function)
{
    $message = __('function_deprecated', array(
        '[old_function]' => $old_function,
        '[new_function]' => $new_function,
    ));

    if (Debugger::isActive()) {
        fn_set_notification('E', __('error'), $message);
    }

    fn_log_event('general', 'deprecated', array(
        'function' => $old_function,
        'message' => $message,
        'backtrace' => debug_backtrace(),
    ));
}

/**
 * Clears cache - all or by type. Not cleans template cache.
 * @param string $type cache type (misc, registry, static or all)
 * @param string $extra extra data to pass to cache clear function
 * @see fn_clear_template_cache for clear template cache
 */
function fn_clear_cache($type = 'all', $extra = '')
{
    if ($type == 'misc' || $type == 'all') {
        fn_rm(fn_get_cache_path(false), false);
    }

    if ($type == 'assets' || $type == 'all' || $type == 'statics') { // FIXME: backward compatibility for "statics"
        Storage::instance('assets')->deleteDir($extra);
        fn_set_storage_data('cache_id', time());
    }

    if ($type == 'registry' || $type == 'all') {
        Registry::cleanup();
    }

    // static cache does not reset when all cache is reset
    if ($type == 'static') {
        fn_rm(Registry::get('config.dir.cache_static'));
    }

    fn_set_hook('clear_cache_post', $type, $extra);
}

/**
 * Builds hierarchic tree from array width id and parent_id
 * @param array $array array of data, must be sorted ASC by  parent_id
 * @param string $object_key  name of id key in array
 * @param string $parent_key name of parent key in array
 * @param string $cildren_key name of key whee sub elements will be located in tree
 * @return array
 */
function fn_build_hierarchic_tree(&$array, $object_key, $parent_key = 'parent_id', $child_key = 'children', &$current_item = null, $parent_id = 0)
{
    $sorted = array();

    foreach ($array as $_id => $item) {
        if ($item[$parent_key] == $parent_id) {
            $sorted[$item[$object_key]] = $item;

            $sorted[$item[$object_key]][$child_key] = fn_build_hierarchic_tree($array, $object_key, $parent_key, $child_key, $sorted[$item[$object_key]], $item[$object_key]);
        }
    }

    return $sorted;
}

/**
 * Converts array to string with PHP code of this array
 * @param array $object
 * @param int $indent
 * @param string $type
 * @return string
 */
function fn_array2code_string($object, $indent = 0, $type = '')
{
    $scheme = '';

    if ($type == '') {
        if (is_array($object)) {
            $type = 'array';
        } elseif (is_numeric($object)) {
            $type = 'integer';
        }
    }

    if ($type == 'array') {
        $scheme .= "array(";
        if (is_array($object)) {
            if (!empty($object)) {
                $scheme .= " \n";
            }
            foreach ($object as $k => $v) {
                $scheme .= str_repeat("\t", $indent + 1) . "'$k' => " . fn_array2code_string($v, $indent + 1). ", \n";
            }
        }
        $scheme .= str_repeat("\t", $indent) . ")";
    } elseif ($type == 'int' || $type == 'integer') {
        if ($object == '') {
            $scheme .= 0;
        } else {
            $scheme .= $object;
        }
    } else {
        $scheme = "'$object'";
    }

    return $scheme;
}

/**
 * @deprecated will be removed in 5.0.1
 */
function fn_update_lang_var($lang_data, $lang_code = DESCR_SL, $params = array())
{
    return LanguageValues::updateLangVar($lang_data, $lang_code, $params);
}

/**
 * Updates object status.
 *
 * @param array $params Object search parameters.
 *                      Must contain the following fields:
 *                      'table': the table an oject is stored in
 *                      'id_name': column of the table that contains object ID
 *                      'id': object ID
 *                      'status': new object status
 *                      May also contain:
 *                      'show_error_notice': whether an error notice should be displayed when status is not updated
 *
 * @return bool
 */
function fn_tools_update_status(array $params)
{
    if (!preg_match("/^[a-z0-9_]+$/", $params['table'])) {
        return false;
    }

    $old_status = db_get_field(
        'SELECT status FROM ?:?f WHERE ?w',
        $params['table'],
        [$params['id_name'] => $params['id']]
    );

    $permission = true;
    if (Registry::get('runtime.company_id')) {
        $cols = fn_get_table_fields($params['table']);
        if (in_array('company_id', $cols)) {

            $disable_sharing = false;

            if (fn_allowed_for('ULTIMATE')) {
                $sharing_scheme = fn_get_schema('sharing', 'schema');
                $disable_sharing = !empty($sharing_scheme[$params['table']]['skip_checking_status']);

                if ($disable_sharing) {
                    Registry::set('runtime.skip_sharing_selection', true);
                }
            }

            $condition = fn_get_company_condition("?:{$params['table']}.company_id");
            $permission = db_get_field(
                'SELECT company_id FROM ?:?f WHERE ?w ?p',
                $params['table'],
                [
                    $params['id_name'] => $params['id'],
                ],
                $condition
            );

            if (fn_allowed_for('ULTIMATE') && $disable_sharing) {
                Registry::set('runtime.skip_sharing_selection', false);
            }
        }
    }

    if (empty($permission)) {
        fn_set_notification('W',  __('warning'), __('access_denied'));

        if (defined('AJAX_REQUEST')) {
            Tygh::$app['ajax']->assign('return_status', $old_status);
        }

        return false;
    }

    $status_data = [
        'status' => $params['status'],
    ];
    $condition = [
        $params['id_name'] => $params['id'],
    ];

    /**
     * Executes when changing an object status right before updating status in the database,
     * allows you to change the saved data.
     *
     * @param array  $params      Object search parameters
     * @param string $old_status  Old object status
     * @param array  $status_data Updated object data
     * @param array  $condition   Update condition
     */
    fn_set_hook('tools_update_status_before_query', $params, $old_status, $status_data, $condition);

    $result = db_query(
        'UPDATE ?:?f SET ?u WHERE ?w',
        $params['table'],
        $status_data,
        $condition
    );

    /**
     * This hook provides an access to the parameters that control the DB request for status change,
     * and to the result of this request.
     *
     * @param array $params Parameters that control status changes (table, id_name, id)
     * @param array $result The result of the DB request for status change
     *
     * */
    fn_set_hook('tools_change_status', $params, $result);

    if ($result) {
        fn_set_notification('N', __('notice'), __('status_changed'));
    } else {

        if (isset($params['show_error_notice']) && !$params['show_error_notice']) {
            return true;
        }

        fn_set_notification('E', __('error'), __('error_status_not_changed'));
        Tygh::$app['ajax']->assign('return_status', $old_status);
    }

    return true;
}

function fn_userdir_prefix($path, $fs = true, $current_location = true)
{
    $prefix = ($fs == true) ? Registry::get('config.dir.root') : ($current_location ? Registry::get('config.current_location') : Registry::get('config.http_location'));

    fn_set_hook('userdir_prefix', $prefix);

    return $prefix . '/' . $path;
}

/**
 * Make a string lowercase
 *
 * @param string $string  The string being lowercased
 * @param string $charset Charset being used
 *
 * @return string
 */
function fn_strtolower($string, $charset = CHARSET)
{
    // FIXME: Workaround for problematic character
    $string = str_replace('İ', 'I', $string);

    if (function_exists('mb_strtolower')) {
        return mb_strtolower($string, $charset);
    }

    return strtolower($string);
}

/**
 * Make a string uppercase
 *
 * @param string $string  The string being uppercase
 * @param string $charset Charset being used
 *
 * @return string
 */
function fn_strtoupper($string, $charset = CHARSET)
{
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($string, $charset);
    }

    return strtoupper($string);
}

/**
 * @deprecated will be removed in 5.0.1
 *
 * Removes languages
 *
 * @param array $lang_codes List of 2-letters language codes
 * @param string $default_lang Default language code
 * @return bool Always true
 */
function fn_delete_languages($lang_ids, $default_lang = DEFAULT_LANGUAGE)
{
    return Languages::deleteLanguages($lang_ids, $default_lang);
}

/**
 * Checks and save languages integrity by enable
 * $default_lang language if all languages in cart disabled
 * and checks and changes appeareance settings if it are using hidden or disabled languages
 *
 * @param string $default_lang Two-letters language code, that will be set active, if there are no active languages.
 * @return bool Always true
 */

/**
 * @deprecated will be removed in 5.0.1
 *
 * @param string $default_lang
 * @return bool
 */
function fn_save_languages_integrity($default_lang = CART_LANGUAGE)
{
    return Languages::saveLanguagesIntegrity($default_lang);
}

/**
 * Returns list of tables that has language depended data
 *
 * @return array Array of table names without prefix
 */
function fn_get_description_tables()
{
    $description_tables = db_get_fields("SHOW TABLES LIKE '?:%_descriptions'");
    $description_tables[] = 'language_values';
    $description_tables[] = 'product_features_values';
    $description_tables[] = 'bm_blocks_content';
    $description_tables[] = 'custom_blocks_content';

    if (fn_allowed_for('ULTIMATE')) {
        $description_tables[] = 'ult_language_values';
    }

    foreach ($description_tables as $key => $table) {
        $description_tables[$key] = preg_replace('/^' . preg_quote(Registry::get('config.table_prefix')) . '/', '', $table);
    }

    /**
     * Process list of tables that has language depended data before return
     *
     * @param array $description_tables Array of table names without prefix
     */
    fn_set_hook('description_tables_post', $description_tables);

    return $description_tables;
}

/**
 * @deprecated will be removed in 5.0.1
 *
 * Clones language depended data from one language to other for all tables in cart
 *
 * @param string $to_lang 2 letters destination language code
 * @param string $from_lang 2 letters source language code
 * @return bool Always true
 */
function fn_clone_language($to_lang, $from_lang = CART_LANGUAGE)
{
    return LanguageHelper::cloneLanguage($to_lang, $from_lang);
}

/**
 * @deprecated will be removed in 5.0.1
 *
 * Clones language depended data from one language to other for $table
 *
 * @param string $table table name to clone values
 * @param string $to_lang 2 letters destination language code
 * @param string $from_lang 2 letters source language code
 * @return bool Always true
 */
function fn_clone_language_values($table, $to_lang, $from_lang = CART_LANGUAGE)
{
    return LanguageHelper::cloneLanguageValues($table, $to_lang, $from_lang);
}

/**
 * Gets installed themes
 *
 * @param int $company_id - company ID to get themes for
 *
 * @return array themes list
 */
function fn_get_installed_themes($company_id = NULL)
{
    return fn_get_dir_contents(fn_get_theme_path('[themes]', 'C', $company_id));
}

function fn_preg_replacement_quote($str)
{
    return preg_replace('/(\$|\\\\)(?=\d)/', '\\\\\1', $str);
}

/**
 * Checks if page is opened in a preview mode
 *
 * @param array $auth Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @param array $params Request parameters
 * @return bool True if page is in a preview mode, false otherwise
 */
function fn_is_preview_action($auth, $params)
{
    $result = $auth['area'] == 'A' && !empty($params['action']) && $params['action'] == 'preview';

    return $result;
}

/**
 * Delete installed payment
 *
 * @param int $payment_id Payment id to be deleted
 * @return bool True if payment was successfully deleted, false otherwise
 */
function fn_delete_payment($payment_id)
{
    $result = true;
    $payment_id = (int) $payment_id;

    if (empty($payment_id) || !fn_check_company_id('payments', 'payment_id', $payment_id)) {
        return false;
    }

    fn_set_hook('delete_payment_pre', $payment_id, $result);

    $res = db_query("DELETE FROM ?:payments WHERE payment_id = ?i", $payment_id);
    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id = ?i", $payment_id);

    fn_delete_image_pairs($payment_id, 'payment');

    /** @var \Tygh\Storefront\Repository $repository */
    $repository = Tygh::$app['storefront.repository'];
    [$storefronts,] = $repository->find(['payment_ids' => $payment_id]);
    /** @var \Tygh\Storefront\Storefront $storefront */
    foreach ($storefronts as $storefront) {
        $repository->save($storefront->removePaymentIds($payment_id));
    }

    $result = $result && $res;
    fn_set_hook('delete_payment_post', $payment_id, $result);

    /**
     * Delete the certificate file (if exists).
     */
    fn_rm(Registry::get('config.dir.certificates') . $payment_id);

    return $result;
}

/**
 * Gets count of directory subdirectories
 *
 * @param string $path directory path
 * @return int number of subdirectories
 */
function fn_dirs_count($path)
{
    $dirscount = 0;

    if (empty($path) || !is_dir($path) || !($dir = opendir($path))) {
        return $dirscount;
    }

    while (($file = readdir($dir)) !== false) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        if (is_dir($path . '/' . $file)) {
            $dirscount++;
            $dirscount += fn_dirs_count($path . '/' . $file);
        }
    }

    closedir($dir);

    return $dirscount;
}

/**
 * Checks if theme is installed
 * @param string $theme_name theme (directory) name
 * @return boolean true if installed, false - if not
 */
function fn_is_theme_installed($theme_name)
{
    $destination_theme = fn_get_theme_path('[themes]/' . $theme_name, 'C');

    if (is_dir($destination_theme)) {

        if (file_exists($destination_theme . '/' . THEME_MANIFEST) || file_exists($destination_theme . '/' . THEME_MANIFEST_INI)) {
            return true;
        }

        fn_set_notification('E', __('error'), __('error_theme_manifest_missed'));
    }

    return false;
}

/**
 * Installs theme
 *
 * @param string   $theme_name      Theme name
 * @param int|null $company_id      Company identifier.
 *                                  This parameter is deprecated and will be removed in v5.0.0.
 *                                  Use $storefront_id instead.
 * @param boolean  $install_layouts Whether to install layouts
 * @param int|null $storefront_id   Storefront ID
 *
 * @return int[] Created theme logo IDs
 */
function fn_install_theme($theme_name, $company_id = null, $install_layouts = true, $storefront_id = null)
{
    /**
     * Executes before installing theme, allows you to modify parameters passed to the function.
     *
     * @param string   $theme_name      Theme name
     * @param int|null $company_id      Company identifier.
     *                                  This parameter is deprecated and will be removed in v5.0.0.
     *                                  Use $storefront_id instead.
     * @param boolean  $install_layouts Whether to install layouts
     * @param int|null $storefront_id   Storefront ID
     */
    fn_set_hook('install_theme_pre', $theme_name, $company_id, $install_layouts, $storefront_id);

    // Copy files
    fn_install_theme_files($theme_name, $theme_name, true);

    /** @var \Tygh\Storefront\Repository $repository */
    $repository = Tygh::$app['storefront.repository'];

    if (!$storefront_id) {
        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = Tygh::$app['storefront'];
        $storefront_id = $storefront->storefront_id;
    }

    // Save theme in storefronts settings
    $repository->setTheme($storefront_id, $theme_name);

    $logo_ids = array();

    // Import theme layouts
    $theme = Themes::factory($theme_name);
    $layouts = $theme->getDirContents(array(
        'dir' => 'layouts',
        'get_dirs' => false,
        'get_files' => true,
        'extension' => '.xml',
    ), Themes::STR_SINGLE);

    // FIXME: Backward compability for layouts
    $legacy_layout = $theme->getContentPath('layouts.xml');
    if (empty($layouts) && $legacy_layout) {
        $layouts['layouts.xml'] = $legacy_layout;
    }

    $created_layouts = array();
    if (!empty($layouts) && $install_layouts) {
        foreach ($layouts as $layout_name => $path_info) {
            $layout_path = $path_info[Themes::PATH_ABSOLUTE];
            $layout_path = fn_get_dev_files($layout_path, true);

            if (file_exists($layout_path)) {
                $layout_id = Exim::instance($company_id, 0, $theme_name, $storefront_id)->importFromFile($layout_path, [
                    'override_by_dispatch' => true,
                    'clean_up'             => true,
                    'import_style'         => 'create',
                ]);

                if (empty($layout_id)) {
                    continue;
                }

                $created_layouts[] = $layout_id;

                $layout_data = Layout::instance($company_id, [], $storefront_id)->get($layout_id);

                $_o_ids = fn_create_theme_logos_by_layout_id($theme_name, $layout_id, $company_id, false, $layout_data['style_id']);
                $logo_ids = array_merge($logo_ids, $_o_ids);
            }
        }
    } else {
        $params = array(
            'theme_name' => $theme_name,
        );

        $exists = Layout::instance($company_id)->getList($params);

        if (empty($exists)) {
            $layout_id = Layout::instance($company_id)->update(array(
                'name' => __('main'),
                'theme_name' => $theme_name,
                'is_default' => 1,
            ));

            $created_layouts[] = $layout_id;

            $layout_data = Layout::instance($company_id)->get($layout_id);

            $logo_ids = fn_create_theme_logos_by_layout_id($theme_name, $layout_id, $company_id, false, $layout_data['style_id']);
        }
    }

    // Import addons layouts for all theme layouts
    if ($install_layouts) {
        [$addons] = fn_get_addons(['type' => 'installed']);
        foreach($addons as $addon_name => $addon) {
            $addon_layouts_path = fn_get_addon_layouts_path($addon_name, $theme_name);
            if ($addon_layouts_path) {
                foreach($created_layouts as $layout_id) {
                    Exim::instance($company_id, $layout_id)->importFromFile($addon_layouts_path);
                }
            }
        }
    }

    // Install translations
    $theme->installTranslations();

    return $logo_ids;
}

/**
 * Creates logotypes for companies and assign it to the layout
 *
 * @param string     $theme_name              Theme name
 * @param int        $layout_id               Layout ID
 * @param int        $company_id              Company ID
 * @param bool       $for_company             Get logos only for companies
 * @param string     $style_id                Style ID
 * @param null|array $whitelist_of_logo_types Filter by logo types
 *
 * @return array created logo IDs
 */
function fn_create_theme_logos_by_layout_id(
    $theme_name,
    $layout_id = 0,
    $company_id = null,
    $for_company = false,
    $style_id = '',
    $whitelist_of_logo_types = null
) {
    $storefront_id = null;

    if ($layout_id) {
        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        $storefront = $repository->findByLayoutId($layout_id);
        $storefront_id = $storefront ? $storefront->storefront_id : 0;
    }

    $logo_ids = [];
    $logo_types = fn_get_logo_types($for_company);

    Registry::set('runtime.allow_upload_external_paths', true);
    $logos = fn_get_logos(0, $layout_id ?: null, $style_id ?: null, $storefront_id);

    if ($for_company) {
        foreach ($logos as $logo) {
            if (!isset($logo_types[$logo['type']]) || empty($logo['image'])) {
                continue;
            }

            $logo_types[$logo['type']]['image_path'] = $logo['image']['absolute_path'];
        }
    }

    foreach ($logo_types as $type => $logo) {
        // Whitelist of logo types was specified, but it doesn't contain current logo type
        if (
            is_array($whitelist_of_logo_types)
            && !empty($whitelist_of_logo_types)
            && !in_array($type, $whitelist_of_logo_types)
        ) {
            continue;
        }

        $logo['image'] = isset($logo['image']) ? $logo['image'] : '';

        if (empty($logo['image_path'])) {
            $image_path = fn_get_logo_image_path($logo['image'], $type, $style_id, $theme_name);
        } else {
            $image_path = $logo['image_path'];
        }

        if (empty($image_path)) {
            continue;
        }

        $logo_data = [
            'type'       => $type,
            'layout_id'  => empty($logo['for_layout']) ? 0 : $layout_id,
            'image_path' => $image_path,
            'style_id'   => empty($logo['single_logo']) ? $style_id : '',
        ];

        if (fn_allowed_for('MULTIVENDOR') && !empty($company_id)) {
            if (!empty($logos['vendor']['image']['absolute_path'])) {
                $logo_data['image_path'] = $logos['vendor']['image']['absolute_path'];
            }
            // Vendors have only one global logo
            unset($logo_data['style_id'], $logo_data['layout_id']);
        }

        /**
         * FIXME: #STOREFRONTS:
         * In Multi-Vendor storefront logos are not attached to any vendor, while vendor logos are.
         * In CS-Cart storefront logos do not exist, and only company logos do.
         */
        $company_id_for_logo = $company_id;
        if (fn_allowed_for('MULTIVENDOR') && !$for_company) {
            $company_id_for_logo = 0;
        }

        $logo_ids[$type] = fn_update_logo($logo_data, $company_id_for_logo, $storefront_id);
    }

    Registry::set('runtime.allow_upload_external_paths', false);

    return $logo_ids;
}

/**
 * Installs theme files
 *
 * @param string $source_theme source theme name
 * @param string $dest_theme destination theme name
 * @param boolean $from_repo flag, if set to true, theme files are copied from themes_repository
 * @return boolean true if theme was installed, false otherwise
 */
function fn_install_theme_files($source_theme, $dest_theme, $from_repo = true)
{
    $path_dest = fn_get_theme_path('[themes]/' . $dest_theme, 'C');
    $theme = Themes::factory($dest_theme);

    if (!fn_is_theme_installed($dest_theme)) {
        if (!fn_mkdir($path_dest)) {
            fn_set_notification('E', __('error'), __('text_cannot_create_directory', array(
                '[directory]' => fn_get_rel_dir($path_dest),
            )));

            return false;
        }

        if ($from_repo) {
            $path_repo = fn_get_theme_path('[repo]/' . $source_theme, 'C');
        } else {
            $path_repo = fn_get_theme_path('[themes]/' . $source_theme, 'C');
        }

        fn_set_progress('parts', fn_dirs_count($path_repo) + 1);

        // FIXME: Backward compatibility. Create manifest.json if theme only has manifest.ini
        $is_legacy_theme = file_exists($path_repo . '/' . THEME_MANIFEST_INI) && !file_exists($path_repo . '/' . THEME_MANIFEST);
        if ($is_legacy_theme) {
            $manifest = parse_ini_file($path_repo . '/' . THEME_MANIFEST_INI);
        } else {
            $manifest = json_decode(fn_get_contents($path_repo . '/' . THEME_MANIFEST), true);
        }

        // install parent theme for dependent themes
        if ($parent = $theme->getParent()) {
            $status = fn_is_theme_installed($parent->getThemeName());
            if (!$status) {
                $status = $parent->getContentPath('', Themes::CONTENT_DIR, Themes::PATH_REPO);
                if ($status) {
                    fn_set_progress('echo', __('text_installing_theme_dependencies', array(
                        '[dependencies]' => $parent->getThemeName(),
                    )));
                    $status = fn_install_theme_files($parent->getThemeName(), $parent->getThemeName(), true);
                }
            }
            if (!$status) {
                fn_rm($path_dest);

                fn_set_notification('E', __('error'), __('text_unable_to_install_theme_dependencies', array(
                    '[dependencies]' => $parent->getThemeName(),
                )));

                return false;
            }

        }

        fn_copy($path_repo, $path_dest, false);

        if ($is_legacy_theme) {
            fn_put_contents($path_repo . '/' . THEME_MANIFEST, json_encode($manifest));
        }
    }

    // Re-install add-ons template files
    [$installed_addons] = fn_get_addons(['type' => 'installed']);
    foreach ($installed_addons as $addon) {
        fn_install_addon_templates($addon['addon'], array($dest_theme));
    }

    return true;
}

/**
 * Deletes theme, if it is not active
 *
 * @param string $theme_name theme name to delete. If empty - deletes all themes
 *
 * @return boolean true if deleted, false if not
 */
function fn_delete_theme($theme_name)
{
    $themes_dest = fn_get_theme_path('[themes]/' . $theme_name, 'C');
    $can_delete = !fn_is_theme_active($theme_name);

    if ($can_delete) {
        $layouts = db_get_array('SELECT layout_id, is_default FROM ?:bm_layouts WHERE theme_name = ?s', $theme_name);
        if (fn_rm($themes_dest)) {
            // Delete layout
            foreach ($layouts as $layout) {
                Layout::instance()->delete($layout['layout_id']);
            }

            return true;
        }
    } else {
        $error_text = (fn_allowed_for('ULTIMATE') || fn_allowed_for('MULTIVENDOR:ULTIMATE'))
            ? __('error_delete_theme_company')
            : __('cannot_remove_active_theme');
        fn_set_notification('E', __('error'), $error_text);

        return false;
    }
}

/**
 * Checks if theme is active at least on one of storefronts
 *
 * @param string $theme_name Name of the theme for checking
 *
 * @return bool State of the theme
 */
function fn_is_theme_active($theme_name)
{
    /** @var \Tygh\Storefront\Repository $repository */
    $storefronts_repo = Tygh::$app['storefront.repository'];

    [$storefronts, ] = $storefronts_repo->find(['theme_name' => $theme_name]);
    if (empty($storefronts)) {
        return false;
    }

    return true;
}

/**
 * Determines full path to image file of given logo.
 *
 * @param string $logo_image Path to image file defined at fn_get_logo_types() function
 * @param string $type       Logo type
 * @param string $style_id   Style ID
 * @param string $theme_name Theme name
 *
 * @return string Absolute path to logo file
 */
function fn_get_logo_image_path($logo_image, $type, $style_id, $theme_name = '')
{
    $logo_filepath = '';

    if (is_file(realpath($logo_image))) {
        $logo_filepath = realpath($logo_image);
    } else {
        if (!$theme_name) {
            $theme = Themes::areaFactory('C');
        } else {
            $theme = Themes::factory($theme_name);
        }

        $logos_path = 'media/images/logos/';

        if ($file = $theme->getContentPath($logos_path . $style_id . '/' . $logo_image)) {
            $logo_filepath = $file[Themes::PATH_ABSOLUTE];
        } elseif ($file = $theme->getContentPath($logos_path . $logo_image)) {
            $logo_filepath = $file[Themes::PATH_ABSOLUTE];
        } else {
            $manifest = $theme->getManifest();

            if (($type === 'favicon' || $type === 'mail' || $type === 'vendor') && !empty($manifest[$type])) {
                $file = $theme->getContentPath($manifest[$type]);
            } elseif (!empty($manifest['logo'])) {
                $file = $theme->getContentPath($manifest['logo']);
            } elseif (!empty($manifest['theme'])) {
                // FIXME: Backward compatibility
                $file = $theme->getContentPath($manifest['theme']);
            }

            if ($file) {
                $logo_filepath = $file[Themes::PATH_ABSOLUTE];
            }
        }
    }

    return $logo_filepath;
}

/**
 * Gets all logos
 *
 * @param int      $company_id    Company ID
 * @param int      $layout_id     Layout ID
 * @param string   $style_id      Style ID
 * @param int|null $storefront_id Storefront ID
 *
 * @return array<string, array> Logos list
 */
function fn_get_logos($company_id = null, $layout_id = null, $style_id = null, $storefront_id = null)
{
    /**
     * Changes params before selecting logo
     *
     * @param int      $company_id    company ID
     * @param int      $layout_id     layout ID
     * @param string   $style_id      Style ID
     * @param int|null $storefront_id Storefront ID
     */
    fn_set_hook('get_logos_pre', $company_id, $layout_id, $style_id, $storefront_id);

    $condition = array();
    $company_condition = '';

    if ($company_id === null) {
        if (Registry::get('runtime.company_id')) {
            $company_id = Registry::get('runtime.company_id');
        } elseif (fn_allowed_for('MULTIVENDOR')) {
            $company_id = 0;
        }
    }

    if ($company_id !== null) {
        $company_condition = db_quote(' AND company_id = ?i', $company_id);
    }

    if ($layout_id === null || $style_id === null || $storefront_id === null) {
        if (!empty($company_id) && fn_allowed_for('ULTIMATE')) {
            $layout_data = Layout::instance($company_id)->getDefault();
        } elseif (SiteArea::isStorefront(AREA)) {
                $layout_data = Registry::get('runtime.layout');
        } else {
            if ($storefront_id === null) {
                $storefront = StorefrontProvider::getRepository()->findDefault();
                $storefront_id = $storefront ? $storefront->storefront_id : null;
            }

            $layout_data = Layout::instance(0, [], $storefront_id)->getDefault();
        }

        $layout_id = $layout_id !== null
            ? $layout_id
            : $layout_data['layout_id'];
        $style_id = $style_id !== null
            ? $style_id
            : $layout_data['style_id'];
        $storefront_id = $storefront_id !== null
            ? $storefront_id
            : $layout_data['storefront_id'];
    }

    $condition[] = db_quote('layout_id IN (?n)', [$layout_id, 0]);
    if ($style_id) {
        $condition[] = db_quote('style_id IN (?a)', [$style_id, '']);
    }
    if ($storefront_id) {
        $condition[] = db_quote('storefront_id IN (?n)', [$storefront_id, 0]);
    }

    /**
     * Changes conditions before selecting logo
     *
     * @param int    $company_id        company ID
     * @param int    $layout_id         layout ID
     * @param string $style_id          Style ID
     * @param array  $condition         Selecting conditions
     * @param string $company_condition Condition by companies
     */
    fn_set_hook('get_logos', $company_id, $layout_id, $style_id, $condition, $company_condition);

    $logos = db_get_hash_array(
        'SELECT * FROM ?:logos WHERE ?p ?p',
        'type',
        implode(' AND ', $condition),
        $company_condition
    );

    $logo_ids = array();
    foreach ($logos as $l) {
        $logo_ids[] = $l['logo_id'];
    }

    $images = fn_get_image_pairs($logo_ids, 'logos', 'M', true, false);

    foreach ($logos as $k => $v) {
        if (empty($images[$v['logo_id']])) {
            $logos[$k]['image'] = array();
            continue;
        }

        $image = reset($images[$v['logo_id']]);
        $logos[$k]['image'] = $image['icon'];

    }

    /**
     * Changes logos before returning
     *
     * @param int                  $company_id    Company ID
     * @param int                  $layout_id     Layout ID
     * @param string               $style_id      Style ID
     * @param array<string, array> $logos         Selected logos
     * @param int|null             $storefront_id Storefront ID
     */
    fn_set_hook('get_logos_post', $company_id, $layout_id, $style_id, $logos, $storefront_id);

    return $logos;
}

/**
 * Adds logo
 *
 * @param array $logo_data     Logo data (layout_id, image path, type)
 * @param int   $company_id    Company ID
 * @param int   $storefront_id Storefront ID
 *
 * @return integer ID of created logo
 */
function fn_update_logo($logo_data, $company_id = null, $storefront_id = null)
{
    $condition = '';
    $logo_data['style_id'] = empty($logo_data['style_id']) ? '' : $logo_data['style_id'];

    if (!empty($logo_data['layout_id'])) {
        $condition .= db_quote(' AND layout_id = ?i', $logo_data['layout_id']);
        if (empty($storefront_id)) {
            /** @var \Tygh\Storefront\Repository $repository */
            $repository = Tygh::$app['storefront.repository'];
            $storefront = $repository->findByLayoutId($logo_data['layout_id']);
            $storefront_id = $storefront->storefront_id;
        }
    }

    if ($company_id) {
        $condition .= db_quote(' AND company_id = ?i', $company_id);
    }

    if ($storefront_id) {
        $condition .= db_quote(' AND storefront_id = ?i', $storefront_id);
    }

    $logo_id = db_get_field("SELECT logo_id FROM ?:logos WHERE type = ?s AND style_id = ?s ?p", $logo_data['type'], $logo_data['style_id'], $condition);

    $storefront_id = (int) $storefront_id;

    if (empty($logo_id)) {
        if ($company_id === null) {
            // We cannot insert new record with null company_id
            return false;
        }

        $logo_id = db_query('INSERT INTO ?:logos ?e', [
            'type'          => $logo_data['type'],
            'layout_id'     => !empty($logo_data['layout_id'])
                ? $logo_data['layout_id']
                : 0,
            'style_id'      => !empty($logo_data['style_id'])
                ? $logo_data['style_id']
                : '',
            'company_id'    => $company_id,
            'storefront_id' => $storefront_id,
        ]);
    } else {
        db_query('UPDATE ?:logos SET ?u WHERE logo_id = ?i', [
            'type'          => $logo_data['type'],
            'layout_id'     => !empty($logo_data['layout_id'])
                ? $logo_data['layout_id']
                : 0,
            'style_id'      => !empty($logo_data['style_id'])
                ? $logo_data['style_id']
                : '',
            'company_id'    => $company_id,
            'storefront_id' => $storefront_id,
        ], $logo_id);
    }

    if (!empty($logo_data['image_path'])) {
        Registry::set('runtime.skip_area_checking', true);
        Registry::set('runtime.allow_upload_external_paths', true);

        if (file_exists($logo_data['image_path'])) {
            $original_request = $_REQUEST;

            $_REQUEST['logotypes_image_data'] = array(
                array(
                    'type' => 'M',
                    'object_id' => $logo_id,
                ),
            );
            $_REQUEST['type_logotypes_image_icon'] = array('server');
            $_REQUEST['file_logotypes_image_icon'] = array($logo_data['image_path']);

            fn_attach_image_pairs('logotypes', 'logos');

            $_REQUEST = $original_request;
        }

        Registry::set('runtime.skip_area_checking', false);
        Registry::set('runtime.allow_upload_external_paths', false);
    }

    return $logo_id;
}

/**
 * Deletes logo by type
 * @param string $type logo type
 * @param integer $company_id - ID of company to delete logo for
 * @return bool always true
 */
function fn_delete_logo($type, $company_id = null, $style_id = '')
{
    $condition = '';
    if (!empty($company_id)) {
        $condition .= db_quote(" AND company_id = ?i", $company_id);
    }

    if (!empty($style_id)) {
        $condition .= db_quote(" AND style_id = ?s", $style_id);
    }

    $logo_ids = db_get_fields("SELECT logo_id FROM ?:logos WHERE type = ?s ?p", $type, $condition);

    foreach ($logo_ids as $logo_id) {
        fn_delete_image_pairs($logo_id, 'logos');
    }

    db_query("DELETE FROM ?:logos WHERE logo_id IN (?n)", $logo_ids);

    return true;
}

/**
 * Gets list of logo types
 *
 * @param boolean $for_company - indicates that logo types should be retrieved for company, not for root
 * @return array list of logo types
 */
function fn_get_logo_types($for_company = false)
{
    $types = [
        'theme' => [
            'for_layout' => true,
            'text'       => 'text_customer_area_logo',
            'image'      => 'logo.png',
        ],
        'favicon' => [
            'for_layout' => true,
            'text'       => '',
            'image'      => 'favicon.ico',
        ],
        'mail' => [
            'for_layout' => true,
            'text'       => 'text_mail_area_logo',
            'image'      => 'mail.png',
        ],
    ];

    fn_set_hook('logo_types', $types, $for_company);

    return $types;
}

/**
 * Gets area name by its type
 * @param string $area - area type
 * @return string area name
 */
function fn_get_area_name($area = AREA)
{
    return ($area == 'C') ? 'frontend' : 'backend';
}

/**
 * Add/remove html special chars
 *
 * @deprecated will be removed in 5.0.1. In favour of use Tygh\Tools\SecurityHelper::encodeHtml()
 * @since 4.3.1
 *
 * @param mixed $data data to filter
 * @param bool $revert if true, decode special chars
 * @return mixed filtered variable
 */
function fn_html_escape($data, $revert = false)
{
    return SecurityHelper::escapeHtml($data, $revert);
}

/**
 * Add slashes
 *
 * @param mixed $var variable to add slashes to
 * @param boolean $escape_nls if true, escape "new line" chars with extra slash
 * @return mixed filtered variable
 */
function fn_add_slashes(&$var, $escape_nls = false)
{
    if (!is_array($var)) {
        return ($var === null) ? null : (($escape_nls == true) ? str_replace("\n", "\\n", addslashes($var)) : addslashes($var));
    } else {
        $slashed = array();
        foreach ($var as $k => $v) {
            $sk = addslashes($k);
            if (!is_array($v)) {
                $sv = ($v === null) ? null : (($escape_nls == true) ? str_replace("\n", "\\n", addslashes($v)) : addslashes($v));
            } else {
                $sv = fn_add_slashes($v, $escape_nls);
            }
            $slashed[$sk] = $sv;
        }

        return ($slashed);
    }
}

/**
 * Gets and caches permissions schema
 * @param string $name schema name
 * @return array schema data
 */
function fn_get_permissions_schema($name)
{
    static $cache = array();

    if (empty($cache[$name])) {
        $cache[$name] = fn_get_schema('permissions', $name);
    }

    return $cache[$name];
}

/**
 * Gets available customization modes
 * @return array available customization modes
 */
function fn_get_customization_modes()
{
    $modes = [
        'live_editor'   => [
            'title' => 'live_editor_mode',
        ],
        'design'        => [
            'title' => 'design_mode',
        ],
        'theme_editor'  => [
            'title' => 'theme_editor_mode',
        ],
        'block_manager' => [
            'title' => 'block_manager_mode',
        ],
    ];

    $session = Tygh::$app['session'];

    $enabled_modes = isset($session['customization']['modes'])
        ? (array) $session['customization']['modes']
        : [];

    foreach (array_keys($enabled_modes) as $e_mode) {
        if (empty($modes[$e_mode])) {
            continue;
        }

        $modes[$e_mode]['enabled'] = true;
    }

    fn_set_hook('get_customization_modes', $modes, $enabled_modes);

    return $modes;
}

/**
 * Updates customization mode (design/translation/css editor)
 *
 * @param array<string, string> $modes         List of modes with statuses
 * @param int                   $storefront_id Storefront ID to set customization modes for
 *
 * @return bool true if mode updated, false - otherwise
 */
function fn_update_customization_mode(array $modes, $storefront_id = null)
{
    if (empty($modes)) {
        return false;
    }

    $session = Tygh::$app['session'];

    if ($storefront_id === null) {
        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = Tygh::$app['storefront'];
        $storefront_id = $storefront->storefront_id;
    }

    $available_modes = fn_get_customization_modes();
    $enabled_modes = isset($session['customization']['modes'])
        ? (array) $session['customization']['modes']
        : [];

    foreach ($modes as $c_mode => $c_status) {
        if (empty($available_modes[$c_mode])) { // skip enable/disable for non-existent modes
            continue;
        }

        if ($c_status === 'enable') {
            $enabled_modes[$c_mode] = true;
        } elseif ($c_status === 'disable') {
            unset($enabled_modes[$c_mode]);
        }
    }

    $enabled_modes = array_filter($enabled_modes, static function ($key) {
        return (bool) $key;
    }, ARRAY_FILTER_USE_KEY);

    /**
     * Hook is executed before saving enabled customization modes to DB.
     *
     * @param array $modes           List of modes to be changed passed to function
     * @param array $enabled_modes   Modes that will be enabled
     * @param array $available_modes All available modes
     */
    fn_set_hook('update_customization_mode', $modes, $enabled_modes, $available_modes);

    $session['customization'] = [
        'modes'         => $enabled_modes,
        'storefront_id' => $storefront_id,
    ];

    return true;
}

/**
 * Temporary disables translation mode for current script run
 */
function fn_disable_live_editor_mode()
{
    Registry::set('runtime.customization_mode.live_editor', false);
}

/**
 * Convert multi-level array to single-level array
 *
 * @param array $item Multi-level array
 * @param string $delimiter Delimiter name
 * @return array Single-level array
 */
function fn_foreach_recursive($item, $delimiter, $name = '', $arr = array())
{
    if (is_array($item)) {
        foreach ($item as $key => $value) {
            $new_key = $name === '' ? $key : $name . $delimiter . $key;
            $arr = fn_foreach_recursive ($value, $delimiter, $new_key, $arr);
        }
    } else {
        $arr[$name] = $item;
    }

    return $arr;
}

/**
 * Parse phpinfo information
 *
 * @param int $type Php info option
 * @return string Php info tables
 */
function fn_get_phpinfo($type = -1)
{
    ob_start();
    phpinfo($type);
    $info = ob_get_clean();

    preg_match('/<body>(.*?)<\/body>/msS', $info, $matches);

    if (isset($matches[1])) {
        $content = preg_replace('/\s?class\="\w+"/', '', $matches[1]);
        $content = str_replace(' border="0" cellpadding="3" width="600"', ' class="deb-table"', $content);
        $content = explode("\n", $content);
        $counter = 0;
        foreach ($content as &$row) {
            if (0 === strpos($row, '<tr>')) {
                $replace = '<tr>';
                $row = str_replace('<tr>', $replace, $row);
                $counter++;
            } else {
                $counter = 0;
            }
        }

        return implode("\n", $content);
    }

    return;
}

/**
 * Translate language variable wrapper (for usage in templates and scripts)
 * @param string $var variable to translate
 * @param array $params placeholder replacements
 * @param string $lang_code language code to get variable for
 * @return string variable value
 */
function __($var, $params = array(), $lang_code = CART_LANGUAGE)
{
    $is_live_editor = Registry::get('runtime.customization_mode.live_editor');
    $skip_live_editor = !empty($params['skip_live_editor']);

    if ($skip_live_editor && $is_live_editor) {
        Registry::set('runtime.customization_mode.live_editor', false);
    }

    $var = LanguageValues::getLangVar($var, $lang_code);

    if (!empty($params) && is_array($params)) {

        reset($params);
        if (key($params) === 0) { // if first parameter has number key, we need to get plural form

            if (Registry::get('runtime.customization_mode.live_editor')) {
                if (preg_match('/\[(lang) name\=([\w-]+?)\](.*?)\[\/\1\]/is', $var, $matches)) {
                    $var = $matches[4];
                }
            }

            $parts = explode('|', $var);

            $number = array_shift($params);
            $params['[n]'] = $number; // special placeholder "[n]" will be replaced with passed number

            $rule = fn_get_plural_rule($number, $lang_code);
            $var = isset($parts[$rule]) ? $parts[$rule] : $parts[0];

            if (Registry::get('runtime.customization_mode.live_editor') && !empty($matches)) {
                $var = str_replace($matches[4], $var, $matches[0]);
            }
        }

        $var = strtr($var, $params);
    }

    if ($skip_live_editor && $is_live_editor) {
        Registry::set('runtime.customization_mode.live_editor', true);
    }

    return $var;
}

/**
 * Get product edition acronym
 *
 * @staticvar array $edition_acronyms Array with PRODUCT_EDITION => acronym
 * @param string $edition Edition name
 * @return string Edition acronym or false, if nothing was found.
 */
function fn_get_edition_acronym($edition)
{
    static $edition_acronyms = array(
        'PROFESSIONAL' => 'pro',
        'MULTIVENDOR'  => 'mve',
        'ULTIMATE'     => 'ult',
    );

    return !empty($edition_acronyms[$edition]) ? $edition_acronyms[$edition] : false;
}

/**
 * Remove parameter from the URL query part
 *
 * @param string ... query
 * @param string ... parameters to remove
 * @return string modified query
 */
function fn_query_remove()
{
    $args = func_get_args();
    $url = array_shift($args);

    if (!empty($args)) {
        $url_object = new Url($url);
        $url_object->removeParams($args);
        $url = $url_object->build($url_object->getIsEncoded());
    }

    return $url;
}

/**
 * Replaces placeholders with request vars
 * @param string $href URL with placeholders
 * @param array $request Request parameters
 * @return string  processed URL
 */
function fn_substitute_vars($href, $request)
{
    if (strpos($href, '%') !== false) {
        [$dispatch, $params_list] = explode('?', $href);

        if (preg_match_all("/%(\w+)/", $params_list, $m)) {
            foreach ($m[1] as $value) {
                $_val = strtolower($value);
                if (!empty($request[$_val])) {
                    $params_list = str_replace('%' . $value, urlencode($request[$_val]), $params_list);
                }
            }
        }

        $href = $dispatch . '?' . $params_list;
    }

    return $href;
}

/**
 * Rounds a value down with a given step
 *
 * @param int $value
 * @param int $step
 * @return int Rounded value
 */
function fn_floor_to_step($value, $step)
{
    $floor = false;

    if (empty($step) && !empty($value)) {
        $floor = $value;

    } elseif (!empty($value) && !empty($step)) {
        if ($value % $step) {
            $floor = floor($value / $step) * $step;
        } else {
            $floor = $value;
        }
    }

    return $floor;
}

/**
 * Rounds a value up with a given step
 *
 * @param int $value
 * @param int $step
 * @return int Rounded value
 */
function fn_ceil_to_step($value, $step)
{
    $ceil = false;

    if (empty($step) && !empty($value)) {
        $ceil = $value;

    } elseif (!empty($value) && !empty($step)) {
        if ($value % $step) {
            $ceil = ceil($value / $step) * $step;
        } else {
            $ceil = $value;
        }
    }

    return $ceil;
}

/**
 * Gets plural rules for language (code got from Zend Framework)
 *
 * @param int    $number    Number to get plural for
 * @param string $lang_code Language code
 *
 * @return int plural form as number
 */
function fn_get_plural_rule($number, $lang_code)
{
    switch ($lang_code) {
        case 'bo':
        case 'dz':
        case 'id':
        case 'ja':
        case 'jv':
        case 'ka':
        case 'km':
        case 'kn':
        case 'ko':
        case 'ms':
        case 'th':
        case 'tr':
        case 'vi':
        case 'zh':
            return 0;
            break;

        case 'af':
        case 'az':
        case 'bn':
        case 'bg':
        case 'ca':
        case 'da':
        case 'de':
        case 'el':
        case 'en':
        case 'eo':
        case 'es':
        case 'et':
        case 'eu':
        case 'fa':
        case 'fi':
        case 'fo':
        case 'fur':
        case 'fy':
        case 'gl':
        case 'gu':
        case 'ha':
        case 'he':
        case 'hu':
        case 'is':
        case 'it':
        case 'ku':
        case 'lb':
        case 'ml':
        case 'mn':
        case 'mr':
        case 'nah':
        case 'nb':
        case 'ne':
        case 'nl':
        case 'nn':
        case 'no':
        case 'om':
        case 'or':
        case 'pa':
        case 'pap':
        case 'ps':
        case 'pt':
        case 'so':
        case 'sq':
        case 'sv':
        case 'sw':
        case 'ta':
        case 'te':
        case 'tk':
        case 'ur':
        case 'zu':
            return ($number == 1) ? 0 : 1;

        case 'am':
        case 'bh':
        case 'fil':
        case 'fr':
        case 'gun':
        case 'hi':
        case 'ln':
        case 'mg':
        case 'nso':
        case 'xbr':
        case 'ti':
        case 'wa':
            return (($number == 0) || ($number == 1)) ? 0 : 1;

        case 'be':
        case 'bs':
        case 'hr':
        case 'ru':
        case 'sr':
        case 'uk':
            return (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);

        case 'cs':
        case 'sk':
            return ($number == 1) ? 0 : ((($number >= 2) && ($number <= 4)) ? 1 : 2);

        case 'ga':
            return ($number == 1) ? 0 : (($number == 2) ? 1 : 2);

        case 'lt':
            return (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);

        case 'sl':
            return ($number % 100 == 1) ? 0 : (($number % 100 == 2) ? 1 : ((($number % 100 == 3) || ($number % 100 == 4)) ? 2 : 3));

        case 'mk':
            return ($number % 10 == 1) ? 0 : 1;

        case 'mt':
            return ($number == 1) ? 0 : ((($number == 0) || (($number % 100 > 1) && ($number % 100 < 11))) ? 1 : ((($number % 100 > 10) && ($number % 100 < 20)) ? 2 : 3));

        case 'lv':
            return ($number == 0) ? 0 : ((($number % 10 == 1) && ($number % 100 != 11)) ? 1 : 2);

        case 'pl':
            return ($number == 1) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 12) || ($number % 100 > 14))) ? 1 : 2);

        case 'cy':
            return ($number == 1) ? 0 : (($number == 2) ? 1 : ((($number == 8) || ($number == 11)) ? 2 : 3));

        case 'ro':
            return ($number == 1) ? 0 : ((($number == 0) || (($number % 100 > 0) && ($number % 100 < 20))) ? 1 : 2);

        case 'ar':
            return ($number == 0) ? 0 : (($number == 1) ? 1 : (($number == 2) ? 2 : ((($number >= 3) && ($number <= 10)) ? 3 : ((($number >= 11) && ($number <= 99)) ? 4 : 5))));

        default:
            return 0;
    }
}

/**
 * Merges CSS and LESS files
 *
 * @param array<array<string>>  $files                  Array with style files
 * @param string                $styles                 Style code
 * @param string                $prepend_prefix         Prepend prefix
 * @param array<string, string> $params                 Additional params
 * @param string                $area                   Site area ('A' for admin, 'C' for customer)
 * @param bool                  $is_local_path_required Whether local path should be returned alongside URL
 *
 * @return string|array<string>
 */
function fn_merge_styles(array $files, $styles = '', $prepend_prefix = '', array $params = [], $area = AREA, $is_local_path_required = false)
{
    $prefix = (!empty($prepend_prefix) ? 'embedded' : 'standalone');

    $make_rtl = false;
    if (fn_is_rtl_language()) {
        $prefix .= '-rtl';
        $make_rtl = true;
    }

    $css_suffix = 'css/';

    $output = '';
    $less_output = '';
    $less_reflection = array();
    $compiled_less = '';
    $compiled_css = '';
    $hashes = array();

    $theme = Themes::areaFactory($area);
    $theme_manifest = $theme->getManifest();
    $theme_search_paths = $theme->getThemeDirs();
    $relative_path = $theme->getThemeRelativePath() . '/' . $css_suffix;

    $files = array_map(function($v) use ($theme) {
        // remap style files
        if ($file_path = $theme->getContentPath($v['file'])) {
            $v['file']     = $file_path['absolute'];
            $v['relative'] = $file_path['relative'];
        }
        return $v;
    }, $files);

    $names = array_map(function($v) {
        return !empty($v['relative']) ? $v['relative'] : false;
    }, $files);

    // Check file changes
    if (Development::isEnabled('compile_check') || Debugger::isActive()) {
        $dir_root = Registry::get('config.dir.root');

        $css_files = $theme->getDirContents(array(
            'dir' => 'css',
            'get_dirs' => false,
            'get_files' => true,
            'extension' => array('.css', '.less'),
            'prefix' => '',
            'recursive' => true,
        ));

        $tracked_files = array_combine(
            array_keys($css_files),
            array_column($css_files, Themes::PATH_ABSOLUTE)
        );

        foreach ($names as $index => $name) {
            if (file_exists($dir_root . '/' . $name)) {
                $tracked_files[$name] = $dir_root . '/' . $name;
            }
        }

        if ($area == 'C') {
            $style_id = Registry::get('runtime.layout.style_id');

            if ($style_id) {
                /** @var Styles $style */
                $style = Styles::factory($theme->getThemeName());
                $less_file = $style->getStyleFile($style_id);
                $css_file = $style->getStyleFile($style_id, 'css');

                if (file_exists($less_file)) {
                    $tracked_files['less_' . $style_id] = $less_file;
                }

                if (file_exists($css_file)) {
                    $tracked_files['css_' . $style_id] = $css_file;
                }
            }
        }

        foreach ($tracked_files as $key => $file) {
            $hashes[] = $key . filemtime($file);
        }
    }

    $hashes[] = md5(implode('|', $names));
    $hashes[] = md5($styles);
    if ($area == 'C') {
        $hashes[] = Registry::get('runtime.layout.layout_id');
        $hashes[] = Registry::get('runtime.layout.style_id');
    }

    arsort($hashes);
    $hash = md5(implode(',', $hashes) . PRODUCT_VERSION);

    $css_dirs = array_map(function($theme_path) use ($css_suffix) {
        return $theme_path[Themes::PATH_RELATIVE] . $css_suffix;
    }, $theme_search_paths);

    /**
     * Executes before obtaining merged styles file (or merging style files when the one is missing), allows to modify the name of the loaded styles file.
     *
     * @param array  $files          Array with style files
     * @param string $styles         Style code
     * @param string $prepend_prefix Prepend prefix
     * @param array  $params         Additional params
     * @param string $area           Site's area ('A' for admin, 'C' for customer)
     * @param array  $css_dirs       Directories to load style files from
     * @param string $hash           Hash part of the compiled styles file
     */
    fn_set_hook('merge_styles_file_hash', $files, $styles, $prepend_prefix, $params, $area, $css_dirs, $hash);

    $filename = $prefix . '.' . $hash . fn_get_storage_data('cache_id') . '.css';

    $file_exists = Storage::instance('assets')->isExist($relative_path . $filename);

    if (!$file_exists) {
        /** @var \Tygh\Lock\Factory $lock_factory */
        $lock_factory = Tygh::$app['lock.factory'];

        $lock = $lock_factory->createLock($filename);

        if (!$lock->acquire() && $lock->wait()) {
            $file_exists = Storage::instance('assets')->isExist($relative_path . $filename);
        }
    }

    if (!$file_exists) {
        Debugger::checkpoint('Before styles compilation');
        foreach ($files as $src) {
            $m_prefix = '';
            $m_suffix = '';
            if (!empty($src['media'])) {
                $m_prefix = "\n@media " . $src['media'] . " {\n";
                $m_suffix = "\n}\n";
            }

            if (strpos($src['file'], '.css') !== false) {
                $output .= "\n" . $m_prefix . fn_get_contents($src['file']) . $m_suffix;
            } elseif ($area != 'C' || empty($theme_manifest['converted_to_css'])) {

                $less_output_chunk = '';
                if (file_exists($src['file'])) {
                    if ($area == 'C' && (empty($theme_manifest['parent_theme']) || $theme_manifest['parent_theme'] == 'basic')) {
                        $less_output_chunk = "\n" . $m_prefix . fn_get_contents($src['file']) . $m_suffix;
                    } else {
                        foreach ($theme_search_paths as $search_path) {
                            if (strpos($src['relative'], $search_path['relative'] . $css_suffix) === 0) {
                                $less_output_chunk = "\n" . $m_prefix . '@import "' . str_replace($search_path['relative'] . $css_suffix, '', $src['relative']) . '";' . $m_suffix;
                                break;
                            }
                        }
                    }
                }

                if (!empty($params['reflect_less'])) {

                    if (preg_match('{/addons/([^/]+)/}is', $src['relative'], $m)) {
                        $less_reflection['output']['addons'][$m[1]] .= $less_output_chunk;
                    } else {
                        $less_reflection['output']['main'] .= $less_output_chunk;
                    }
                }

                $less_output .= $less_output_chunk;

            }
        }

        $header = str_replace('[files]', implode("\n", $names), Registry::get('config.js_css_cache_msg'));

        if (!empty($styles)) {
            $less_output .= $styles;
        }

        // Prepend all styles with prefix
        if (!empty($prepend_prefix)) {
            $less_output = $output . "\n" . $less_output;
            $output = '';
        }

        if (!empty($output)) {
            $compiled_css = Less::parseUrls($output, Storage::instance('assets')->getAbsolutePath($relative_path), fn_get_theme_path('[themes]/[theme]/media', $area));
        }

        if (!empty($theme_manifest['converted_to_css']) && $area == 'C') {

            $theme_css_path = fn_get_theme_path('[themes]/[theme]', $area) . '/css';

            $pcl_filepath = $theme_css_path . '/' . Themes::$compiled_less_filename;
            if (file_exists($pcl_filepath)) {
                $compiled_css .= fn_get_contents($pcl_filepath);
            }

            [$installed_addons] = fn_get_addons(['type' => 'active']);
            foreach ($installed_addons as $addon) {
                $addon_pcl_filpath = $theme_css_path . "/addons/$addon[addon]/" . Themes::$compiled_less_filename;
                if (file_exists($pcl_filepath)) {
                    $compiled_css .= fn_get_contents($addon_pcl_filpath);
                }
            }
        }

        if (!empty($less_output)) {
            $less = new Less();

            if (!empty($params['compressed'])) {
                $less->setFormatter('compressed');
            }

            // perform @import from all folders of the theme
            $less->setImportDir($css_dirs);
            try {
                $compiled_less = $less->customCompile($less_output, Storage::instance('assets')->getAbsolutePath($relative_path), array(), $prepend_prefix, $area);
            } catch (Exception $e) {
                $skip_save = true;
                $shift = 4;
                $message = '<div style="border: 2px solid red; padding: 5px;">LESS ' . $e->getMessage();

                if (preg_match("/line: (\d+)/", $message, $m)) {
                    $lo = explode("\n", $less_output);
                    $message .= '<br /><br /><pre>' . implode("\n", array_splice($lo, intval($m[1]) - $shift, $shift * 2)) . '</pre>';
                }

                $message .= '</div>';
                fn_set_notification('E', __('error'), $message);
            }
        }

        if (empty($skip_save)) {
            $compiled_content = $compiled_css . "\n" . $compiled_less;

            // Move all @import links to the Top of the file.
            if (preg_match_all('/@import url.*?;/', $compiled_content, $imports)) {
                $compiled_content = preg_replace('/@import url.*?;/', '', $compiled_content);

                foreach ($imports[0] as $import_link) {
                    $compiled_content = $import_link . "\n" . $compiled_content;
                }
            }

            if ($make_rtl) {
                $compiled_content = \CSSJanus::transform($compiled_content);
                $compiled_content = "body {\ndirection: rtl;\n}\n" . $compiled_content;
            }

            Storage::instance('assets')->put($relative_path . $filename, array(
                'contents' => $header . $compiled_content,
                'compress' => false,
                'caching' => true,
            ));

            if (!empty($params['use_scheme'])) {
                fn_put_contents(fn_get_theme_editor_tmp_css_path($filename), $output . '#LESS#' . $less_output);
            }

            if (!empty($params['reflect_less'])) {
                $less_reflection['import_dirs'] = array_values($css_dirs);
                fn_put_contents(fn_get_cache_path(false) . 'less_reflection.json', json_encode($less_reflection));
            }
        }
        Debugger::checkpoint('After styles compilation');

        if (isset($lock)) {
            $lock->release();
        }
    }

    $url = Storage::instance('assets')->getUrl($relative_path . $filename);

    if ($is_local_path_required) {
        return [$url, $relative_path . $filename];
    }

    return $url;
}

/**
 * @deprecated will be removed in 5.0.1
 *
 * Gets list of all languages defined in store
 * used for adding desciptions and etc.
 *
 * @param boolean $edit Flag that determines if language list is used to be edited
 * @return array $languages Languages list
 */
function fn_get_translation_languages($edit = false)
{
    return Languages::getAll($edit);
}

/**
 * Gets currencies for blocks with "Currency" type
 *
 * @return array List of available currencies
 */
function fn_block_manager_get_currencies()
{
    $currencies = array_filter(Registry::get('currencies'), function($currency) {
        return $currency['status'] === 'A';
    });

    return $currencies;
}

/**
 * Returns currencies list from registry
 *
 * @return array Currencies list
 */
function fn_get_currencies()
{
    return Registry::get('currencies');
}

/**
 * Returns currencies list filtered by params
 *
 * @param array $params Parameters for filtering
 *      array(
 *          'status': Currency status (A - active, D - disabled, H - hidden)
 *          'currency_id': int number
 *          'currency_code': String code (USD, GBP, RUR, etc)
 *          'only_primary' Select only primary currency (Y/N)
 *      )
 * @param string $area 1-letter Area code (C, A, V)
 * @param string $lang_code 2-letters language code
 * @return array Currencies list
 */
function fn_get_currencies_list($params = array(), $area = AREA, $lang_code = CART_LANGUAGE)
{
    $default_params = [
        'status'        => null,
        'currency_id'   => null,
        'currency_code' => null,
        'only_primary'  => false,
        'storefront_id' => null,
    ];

    $params = array_merge($default_params, $params);
    if ($area === 'C' && $params['storefront_id'] === null) {
        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = Tygh::$app['storefront'];
        $params['storefront_id'] = $storefront->storefront_id;
    }

    /**
     * Performs actions before getting currencies list
     *
     * @param array $params Parameters for filtering
     *      array(
     *          'status': Currency status (A - active, D - disabled, H - hidden)
     *          'currency_id': int number
     *          'currency_code': String code (USD, GBP, RUR, etc)
     *          'only_primary' Select only primary currency (Y/N)
     *      )
     * @param string $area 1-letter Area code (C, A, V)
     * @param string $lang_code 2-letters language code
     */
    fn_set_hook('get_currencies_list_pre', $params, $area, $lang_code);

    $condition = $join = $order_by = '';

    if ($area == 'C' && defined('CART_LOCALIZATION')) {
        $join = " LEFT JOIN ?:localization_elements as c ON c.element = a.currency_code AND c.element_type = 'M'";
        $condition = db_quote('AND c.localization_id = ?i', CART_LOCALIZATION);
        $order_by = "ORDER BY c.position ASC";
    }

    if (!$order_by) {
        $order_by = 'ORDER BY a.position';
    }

    if ($params['status']) {
        $condition .= db_quote(' AND a.status IN (?a)', $params['status']);
    }

    if ($params['currency_id']) {
        $condition .= db_quote(' AND a.currency_id IN (?n)', (array) $params['currency_id']);
    }

    if ($params['currency_code']) {
        $condition .= db_quote(' AND a.currency_code = ?s', $params['currency_code']);
    }

    if (YesNo::toBool($params['only_primary'])) {
        $condition .= db_quote(' AND a.is_primary = ?s', 'Y');
    }

    if ($params['storefront_id']) {
        $join .= db_quote(
            ' LEFT JOIN ?:storefronts_currencies AS storefronts_currencies'
            . ' ON storefronts_currencies.currency_id = a.currency_id'
        );
        $condition .= db_quote(
            'AND (storefronts_currencies.storefront_id IN (?n) OR storefronts_currencies.storefront_id IS NULL)',
            (array) $params['storefront_id']
        );
    }

    /**
     * Sets currencies query parameters
     *
     * @param array $params Parameters for filtering
     *      array(
     *          'status': Currency status (A - active, D - disabled, H - hidden)
     *          'currency_id': int number
     *          'currency_code': String code (USD, GBP, RUR, etc)
     *          'only_primary' Select only primary currency (Y/N)
     *      )
     * @param string $area 1-letter Area code (C, A, V)
     * @param string $lang_code 2-letters language code
     * @param string $join String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $order_by Order SQL data
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     */
    fn_set_hook('get_currencies_list_before_select', $params, $area, $lang_code, $join, $order_by, $condition);

    if (!empty($params['raw_query'])) {
        Tygh::$app['db']->raw = true;
    }

    $currencies = db_get_hash_array(
        'SELECT a.*, b.description FROM ?:currencies as a'
        . ' LEFT JOIN ?:currency_descriptions as b ON a.currency_id = b.currency_id AND lang_code = ?s'
        . ' ?p'
        . ' WHERE 1 ?p'
        . ' ?p',
        'currency_code',
        $lang_code,
        $join,
        $condition,
        $order_by
    );

    array_walk($currencies, static function (&$currency) {
        if (!is_array($currency) || !isset($currency['currency_id'])) {
            return;
        }

        [$storefronts,] = StorefrontProvider::getRepository()->find(['currency_ids' => $currency['currency_id']]);
        $currency['storefront_ids'] = implode(',', array_keys($storefronts));
    });

    /**
     * Gets currencies list
     *
     * @param array $params Parameters for filtering
     *      array(
     *          'status': Currency status (A - active, D - disabled, H - hidden)
     *          'currency_id': int number
     *          'currency_code': String code (USD, GBP, RUR, etc)
     *          'only_primary' Select only primary currency (Y/N)
     *      )
     * @param string $area 1-letter Area code (C, A, V)
     * @param string $lang_code 2-letters language code
     * @param array $currencies Currencies list
     */
    fn_set_hook('get_currencies_list_post', $params, $area, $lang_code, $currencies);

    return $currencies;
}

function fn_get_currency_data($currency_id, $lang_code = DESCR_SL)
{
    $currency = db_get_row(
        'SELECT a.*, b.description'
        . ' FROM ?:currencies as a'
        . ' LEFT JOIN ?:currency_descriptions as b'
        . ' ON a.currency_id = b.currency_id AND lang_code = ?s'
        . ' WHERE a.currency_id = ?s',
        $lang_code,
        $currency_id
    );

    /** @var \Tygh\Storefront\Repository $repository */
    $repository = Tygh::$app['storefront.repository'];
    [$storefronts,] = $repository->find(['currency_ids' => $currency_id]);
    $currency['storefront_ids'] = implode(',', array_keys($storefronts));

    return $currency;
}

/**
 * Selects needed currencies for currencies picker
 *
 * @param array  $params    List of parameters to search
 * @param string $lang_code Two letters language code
 *
 * @return array Currencies list
 */
function fn_get_currencies_for_picker(array $params, $lang_code = DESCR_SL)
{
    $params = array_merge(
        [
            'ids'           => [],
            'storefront_id' => null,
            'page'          => null,
            'page_size'     => null,
            'q'             => '',
        ],
        $_REQUEST
    );
    $currencies = fn_get_currencies_list(['status' => ObjectStatuses::ACTIVE]);
    if ($params['ids']) {
        $currencies = array_filter(
            $currencies,
            static function ($currency) use ($params) {
                return in_array($currency['currency_id'], $params['ids']);
            }
        );
    }
    if ($params['q'] !== '') {
        $currencies = array_filter(
            $currencies,
            static function ($currency) use ($params) {
                $search_critiera = fn_strtolower($params['q']);
                $currency_name = fn_strtolower($currency['description']);
                return stristr($currency_name, $search_critiera) !== false;
            }
        );
    }

    $currencies = array_map(
        static function ($currency) {
            return [
                'id'   => (int) $currency['currency_id'],
                'text' => $currency['description'],
                'data' => [
                    'name' => $currency['description'],
                    'id'   => (int) $currency['currency_id'],
                ],
            ];
        },
        $currencies
    );
    $objects = array_values($currencies);
    if ($params['page'] && $params['page_size']) {
        $objects = array_slice($objects, ($params['page'] - 1) * $params['page_size'], $params['page_size']);
    }
    $total_objects = count($currencies);
    return [$objects, $total_objects];
}

/**
 * Selects needed languages for language picker
 *
 * @param array  $params    List of parameters to search
 * @param string $lang_code Two letters language code
 *
 * @return array Languages list
 */
function fn_get_languages_for_picker(array $params, $lang_code = DESCR_SL)
{
    $params = array_merge(
        [
            'ids'            => [],
            'storefront_id'  => null,
            'page'           => null,
            'page_size'      => null,
            'q'              => '',
        ],
        $_REQUEST
    );
    $languages = Languages::get(['status' => [ObjectStatuses::ACTIVE]]);
    if ($params['ids']) {
        $languages = array_filter(
            $languages,
            static function ($language) use ($params) {
                return in_array($language['lang_id'], $params['ids']);
            }
        );
    }
    if ($params['q'] !== '') {
        $languages = array_filter(
            $languages,
            static function ($language) use ($params) {
                $search_critiera = fn_strtolower($params['q']);
                $language_name = fn_strtolower($language['name']);
                return stristr($language_name, $search_critiera) !== false;
            }
        );
    }
    $languages = array_map(
        static function ($language) {
            $language_id = (int) $language['lang_id'];
            $language_name = $language['name'];
            return [
                'id'   => $language_id,
                'text' => $language_name,
                'data' => [
                    'name' => $language_name,
                    'id'   => $language_id,
                ],
            ];
        },
        $languages
    );
    $objects = array_values($languages);
    if ($params['page'] && $params['page_size']) {
        $objects = array_slice($objects, ($params['page'] - 1) * $params['page_size'], $params['page_size']);
    }
    $total_objects = count($languages);
    return [$objects, $total_objects];
}

/**
 * Updates list of currencies for the storefront
 *
 * @param array $params List of storefront parameters
 *
 * @return void
 */
function fn_update_storefront_currencies(array $params)
{
    $storefront_id = empty($params['storefront_id'])
        ? null
        : $params['storefront_id'];
    $new_storefront_id = !isset($params['new_storefront_id'])
        ? null
        : $params['new_storefront_id'];
    $all_storefront_ids = db_get_fields('SELECT storefront_id FROM ?:storefronts');

    $new_currencies = isset($params['currencies']) ? $params['currencies'] : [];

    $currencies = fn_get_currencies_list();
    $currency_ids = array_column($currencies, 'currency_id');
    $unattached_currency_ids = array_diff($currency_ids, $new_currencies);

    foreach ($currencies as $currency) {
        $storefront_ids = $currency['storefront_ids'] ? explode(',', $currency['storefront_ids']) : [];

        if ($new_storefront_id) {
            if ($storefront_ids) {
                $currency['storefront_ids'] = implode(',', array_merge($storefront_ids, [$new_storefront_id]));
            } else {
                $currency['storefront_ids'] = implode(',', $all_storefront_ids);
            }
            fn_update_currency($currency, $currency['currency_id']);

            continue;
        }

        if (
            !in_array($currency['currency_id'], $new_currencies)
            || in_array($storefront_id, $storefront_ids)
        ) {
            continue;
        }

        $currency['storefront_ids'] = implode(',', array_merge($storefront_ids, [$storefront_id]));
        fn_update_currency($currency, $currency['currency_id']);
    }

    foreach ($currencies as $currency) {
        $storefront_ids = $currency['storefront_ids'] ? explode(',', $currency['storefront_ids']) : [];

        if (
            !in_array($currency['currency_id'], $unattached_currency_ids)
            || !in_array($storefront_id, $storefront_ids) && $storefront_ids
            || count($all_storefront_ids) <= 1
        ) {
            continue;
        }
        if ($storefront_ids) {
            $currency['storefront_ids'] = implode(',', array_diff($storefront_ids, [$storefront_id]));
        } else {
            $currency['storefront_ids'] = implode(',', array_diff($all_storefront_ids, [$storefront_id]));
        }

        fn_update_currency($currency, $currency['currency_id']);

        if ($currency['storefront_ids']) {
            continue;
        }
        fn_set_notification(
            NotificationSeverity::WARNING,
            __('warning'),
            __(
                'storefronts_for_currency_absent',
                [
                    '[name]' => $currency['description']
                ]
            )
        );
    }
}

/**
 * Updates list of languages for the storefront
 *
 * @param array $params List of storefront parameters
 *
 * @return void
 */
function fn_update_storefront_languages(array $params)
{
    $storefront_id = empty($params['storefront_id'])
        ? null
        : $params['storefront_id'];
    $new_storefront_id = !isset($params['new_storefront_id'])
        ? null
        : $params['new_storefront_id'];

    $new_languages = isset($params['languages']) ? $params['languages'] : [];

    $languages = Languages::getAll();
    foreach ($languages as $language) {
        $storefront_ids = $language['storefront_ids'] ? explode(',', $language['storefront_ids']) : [];

        if ($new_storefront_id) {
            if (!$language['storefront_ids']) {
                $all_storefront_ids = db_get_fields('SELECT storefront_id FROM ?:storefronts');
                $language['storefront_ids'] = implode(',', $all_storefront_ids);
            } else {
                $language['storefront_ids'] = implode(',', array_merge($storefront_ids, [$new_storefront_id]));
            }
            fn_update_language_storefronts($language);

            continue;
        }

        if (
            in_array($storefront_id, $storefront_ids)
            && !in_array($language['lang_id'], $new_languages)
        ) {
            $language['storefront_ids'] = implode(',', array_diff($storefront_ids, [$storefront_id]));
            fn_update_language_storefronts($language);

            if (!$language['storefront_ids']) {
                fn_set_notification(
                    NotificationSeverity::WARNING,
                    __('warning'),
                    __(
                        'storefronts_for_language_absent',
                        [
                            '[name]' => $language['name']
                        ]
                    )
                );
            }
        } elseif (
            !in_array($storefront_id, $storefront_ids)
            && in_array($language['lang_id'], $new_languages)
            && $storefront_ids
        ) {
            $language['storefront_ids'] .= $language['storefront_ids'] ? ',' . $storefront_id : (string) $storefront_id;
            fn_update_language_storefronts($language);
        } elseif (
            !in_array($language['lang_id'], $new_languages)
            && !$storefront_ids
        ) {
            $all_storefront_ids = db_get_fields('SELECT storefront_id FROM ?:storefronts');
            $language['storefront_ids'] = implode(',', array_diff($all_storefront_ids, [$storefront_id]));
            fn_update_language_storefronts($language);
        }
    }
}

/**
 * Updates list of storefronts for the language
 *
 * @param array $lang_data Language data
 *
 * @return void
 */
function fn_update_language_storefronts(array $lang_data)
{
    $lc = false;

    $lc = Languages::update($lang_data, $lang_data['lang_id']);

    if ($lc !== false) {
        Languages::saveLanguagesIntegrity();
    }

    // phpcs:ignore
    if (!$lc) {
        fn_delete_notification('changes_saved');
    }
}

/**
 * Convert underscored string to CamelCase
 *
 * @param string    $string String
 * @param bool      $upper  Upper-camelcase/lower-camelcase
 *
 * @return string
 */
function fn_camelize($string, $upper = true)
{
    $regexp = $upper ? '/(?:^|_)(.?)/' : '/_(.?)/';

    return preg_replace_callback($regexp, function($matches) {
        return strtoupper($matches[1]);
    }, $string);
}

/**
 * Convert CamelCase (lower or upper) string to underscored
 *
 * @param string  $string    String
 * @param bool $delimiter Delimiter
 *
 * @return string
 */
function fn_uncamelize($string, $delimiter = '_')
{
    $string = preg_replace("/(?!^)[[:upper:]]+/", $delimiter . '$0', $string);

    return strtolower($string);
}

function fn_exim_json_encode($data)
{
    if (is_callable('mb_encode_numericentity') && is_callable('mb_decode_numericentity')) {
        $_data = fn_exim_prepare_data_to_convert($data);

        return mb_decode_numericentity(json_encode($_data), array (0x80, 0xffff, 0, 0xffff), 'UTF-8');
    } else {
        return json_encode($data);
    }
}

function fn_exim_prepare_data_to_convert($data)
{
    $_data = array();
    if (is_array($data) && is_callable('mb_encode_numericentity')) {
        foreach ($data as $k => $v) {
            $key = mb_encode_numericentity($k, array (0x80, 0xffff, 0, 0xffff), 'UTF-8');
            if (is_array($v)) {
                $_data[$key] = fn_exim_prepare_data_to_convert($v);
            } else {
                $_data[$key] = mb_encode_numericentity($v, array (0x80, 0xffff, 0, 0xffff), 'UTF-8');
            }
        }
    } else {
        $_data = $data;
    }

    return $_data;
}

function fn_format_long_string($str, $length)
{
    if (fn_strlen($str) <= $length) {
        return $str;
    }

    $length = $length - 3;

    return fn_substr($str, 0, $length) . '...';
}

/**
 * Gets uri for administrator's preview from common uri
 *
 * @param string     $uri         Common url
 * @param array      $object_data Preview object data
 * @param int|string $user_id     User identifier
 * @param string     $lang_code   Language code - two symbols
 *
 * @return string Preview uri
 */
function fn_get_preview_url($uri, array $object_data, $user_id, $lang_code = DESCR_SL)
{
    if (fn_allowed_for('ULTIMATE')) {
        $company_id = Registry::get('runtime.company_id') ? Registry::get('runtime.company_id') : $object_data['company_id'];
        $uri = fn_link_attach($uri, 'company_id=' . $company_id);
    }

    if ($object_data['status'] != 'A' || fn_allowed_for('MULTIVENDOR') || !empty($object_data['usergroup_ids'])) {
        $_uri = fn_link_attach($uri, 'action=preview');
        $_uri = urlencode($_uri);

        $preview_url = fn_url("profiles.view_product_as_user?user_id=$user_id&area=C&redirect_url=$_uri", 'A', 'current', $lang_code);
    } else {
        $preview_url = fn_url($uri, 'C', 'current', $lang_code);
    }

    fn_set_hook('get_preview_url_post', $uri, $object_data, $user_id, $preview_url);

    return $preview_url;
}

/**
 * Gets list of default statuses
 *
 * @param string $status current object status
 * @param boolean $add_hidden includes 'hiden' status
 * @param string $lang_code 2-letter language code (e.g. 'en', 'ru', etc.)
 * @return array statuses list
 */
function fn_get_default_statuses($status, $add_hidden, $lang_code = CART_LANGUAGE)
{
    $statuses = [
        ObjectStatuses::ACTIVE   => __('active', '', $lang_code),
        ObjectStatuses::DISABLED => __('disabled', '', $lang_code),
    ];

    if ($add_hidden) {
        $statuses[ObjectStatuses::HIDDEN] = __('hidden', '', $lang_code);
    }

    if ($status === ObjectStatuses::NEW_OBJECT) {
        $statuses[ObjectStatuses::PENDING] = __('pending', '', $lang_code);
    }

    return $statuses;
}

/**
 * Gets list of default status filters
 *
 * @param string $filter current filter
 * @param boolean $add_hidden includes 'hiden' status filter
 * @param string $lang_code 2-letter language code (e.g. 'en', 'ru', etc.)
 * @return array filters list
 */
function fn_get_default_status_filters($filter, $add_hidden, $lang_code = CART_LANGUAGE)
{
    $filters = [
        ObjectStatuses::ACTIVE   => __('check_active', '', $lang_code),
        ObjectStatuses::DISABLED => __('check_disabled', '', $lang_code),
    ];

    if ($add_hidden) {
        $filters[ObjectStatuses::HIDDEN] = __('check_hidden', '', $lang_code);
    }

    if ($filter === ObjectStatuses::NEW_OBJECT) {
        $filters[ObjectStatuses::PENDING] = __('check_pending', '', $lang_code);
    }

    return $filters;
}

/**
 * Gets list of predefined status for particular object type
 *
 * @param string $type   Object type
 * @param string $status Current status
 * @return array Object statuses
 */
function fn_get_predefined_statuses($type, $status = '')
{
    $statuses = array(
        'profiles' => array(
            'A' => __('active'),
            'P' => __('pending'),
            'F' => __('available'),
            'D' => __('declined'),
        ),
        'usergroups' => array(
            'A' => __('active'),
            'P' => __('pending'),
            'F' => __('available'),
            'D' => __('declined'),
        ),
    );

    /**
     * Prepare statuses for particular object type
     *
     * @param string $type     Object type
     * @param array  $statuses Array of statuses by type
     * @param string $status   Current status
     */
    fn_set_hook('get_predefined_statuses', $type, $statuses, $status);

    return $statuses[$type];
}

/**
 * Generates ekey for access to object
 *
 * @param int|string   $object_id Object ID
 * @param string       $type      Object type
 * @param int          $ttl       Ekey TTL (from the current time)
 * @param string       $ekey      Ekey ID if you generated it yourselves
 * @param array<mixed> $data      Ekey data
 *
 * @return string ekey ID
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
function fn_generate_ekey($object_id, $type, $ttl = 0, $ekey = '', array $data = [])
{
    $key = !empty($ekey) ? $ekey : SecurityHelper::generateRandomString();

    if (is_numeric($object_id)) {
        $field_name = 'object_id';
    } else {
        $field_name = 'object_string';
    }

    $_data = [
        'object_type' => $type,
        'ekey'        => $key,
        'ttl'         => time() + $ttl,
        'data'        => json_encode($data),
    ];

    $_data[$field_name] = $object_id;

    db_replace_into('ekeys', $_data);

    return $key;
}

/**
 * Gets object by ekey
 *
 * @param string $ekey ekey
 * @param string $type object type
 *
 * @return mixed object ID
 */
function fn_get_object_by_ekey($ekey, $type)
{
    $key_data = fn_get_ekeys([
        'ekey'        => $ekey,
        'object_type' => $type,
        'ttl'         => TIME,
    ]);

    $return = false;

    if (!empty($key_data)) {
        $key_data = reset($key_data);

        // Delete current key
        fn_delete_ekey($ekey, $type);

        $return = !empty($key_data['object_string']) ? $key_data['object_string'] : $key_data['object_id'];
    }

    return $return;
}

/**
 * Deletes ekey by params
 *
 * @param string          $ekey
 * @param string|null     $object_type
 * @param int|string|null $object_id
 */
function fn_delete_ekey($ekey, $object_type = null, $object_id = null)
{
    $condition = db_quote('ekey = ?s', $ekey);

    if ($object_id) {
        if (is_numeric($object_id)) {
            $field_name = 'object_id';
        } else {
            $field_name = 'object_string';
        }

        $condition .= db_quote(' AND ?f = ?i', $field_name, $object_id);
    }

    if ($object_type) {
        $condition .= db_quote(' AND object_type = ?s', $object_type);
    }

    db_query('DELETE FROM ?:ekeys WHERE ?p', $condition);
}

/**
 * Searches ekeys.
 *
 * @param array $params Search criteria (ekey, object_id, object_type, ttl)
 *
 * @return array Ekeys that meet criteria
 */
function fn_get_ekeys($params = array())
{
    if (!$params) {
        return array();
    }

    // Cleanup expired keys
    // FIXME: Shouldn't be here
    db_query("DELETE FROM ?:ekeys WHERE ttl > 0 AND ttl < ?i", TIME);

    $params = array_merge(array(
        'ekey' => '',
        'object_id' => 0,
        'object_type' => '',
        'ttl' => 0,
    ), $params);

    $condition = '1 = 1';
    if ($params['ekey']) {
        $condition .= db_quote(' AND ekey = ?s', $params['ekey']);
    }
    if ($params['object_id']) {
        $condition .= db_quote(' AND object_id = ?i', $params['object_id']);
    }
    if ($params['object_type']) {
        $condition .= db_quote(' AND object_type = ?s', $params['object_type']);
    }
    if ($params['ttl']) {
        $condition .= db_quote(' AND ttl > ?s', $params['ttl']);
    }

    $result = db_get_array('SELECT * FROM ?:ekeys WHERE ?p', $condition);

    foreach ($result as &$item) {
        if (!empty($item['data'])) {
            $item['data'] = @json_decode($item['data'], true);
        }

        $item['data'] = (array) $item['data'];
    }
    unset($item);

    return $result;
}

/**
 * Updates object data in live content editing mode.
 *
 * @param array $params Params to be updated; array with keys name, value, lang_code, and need_render
 *
 * @return bool
 */
function fn_live_editor_update_object($params)
{
    $updated = false;

    /**
     * Performs actions before updating a live editor object
     *
     * @param array $params  Params to be updated; array with keys name, value, lang_code, and need_render
     * @param bool  $updated The "already updated" flag
     */
    fn_set_hook('live_editor_update_object_pre', $params, $updated);

    if ($updated) {
        return true;
    }

    if (strpos($params['name'], ':')) {
        [$object, $field, $id] = explode(':', $params['name'], 3);

        $params['object_data']['object_id'] = isset($params['object_id']) ? $params['object_id'] : 0;
        $params['object_data']['object_type'] = isset($params['object_type']) ? $params['object_type'] : '';

        $schema = fn_get_schema('customization', 'live_editor_objects');

        if (!empty($schema[$object])) {
            $rule = $schema[$object];

            if (!empty($rule['function'])) {
                $vars = $params + compact('object', 'field', 'id', 'rule');
                $args = fn_live_editor_prepare_callback_args($rule['args'], $vars);
                call_user_func_array($rule['function'], $args);

            }

            if (!empty($rule['table'])) {

                $table = $rule['table'];

                $condition = array(
                    'id' => db_quote("?p = ?s", $rule['id_field'], $id),
                );

                if (!empty($rule['lang_code'])) {
                    $condition['lang_code'] = db_quote("lang_code = ?s", $params['lang_code']);
                }

                /**
                 * Prepares data for the live editor object update request
                 *
                 * @param array  $params    Params
                 * @param array  $rule      Rule
                 * @param array  $condition Conditions
                 * @param string $table     Table name
                 */
                fn_set_hook('live_editor_update_object_table', $params, $rule, $condition, $table);

                db_query("UPDATE ?:$table SET ?u WHERE ?p",
                    array($rule['value_field'] => $params['value']),
                    implode(' AND ', $condition)
                );

                $updated = true;

            }
        }
    }

    /**
     * Performs actions after updating a live editor object
     *
     * @param array $params  Params
     * @param bool  $updated The "already updated" flag
     */
    fn_set_hook('live_editor_update_object_post', $params, $updated);

    return $updated;
}

/**
 * Prepares callback arguments recursively
 *
 * @param array $schema Schema definition
 * @param array $vars   Array of available vars
 * @return array Processed schema
 */
function fn_live_editor_prepare_callback_args($schema, $vars)
{
    if (!empty($schema)) {
        $processed_schema = array();
        foreach ($schema as $key => $value) {
            // Process key and value
            foreach (array('key', 'value') as $var) {
                if (!is_array($$var) && strpos($$var, '$') === 0) {
                    $$var = str_replace('$', '', $$var);
                    if (isset($vars[$$var])) {
                        $$var = $vars[$$var];
                    }
                }
            }
            if (is_array($value)) {
                $value = fn_live_editor_prepare_callback_args($value, $vars);
            }
            $processed_schema[$key] = $value;
        }

        return $processed_schema;
    }

    return $schema;
}

/**
 * Compares two values by given operator
 *
 * @param $left_operand
 * @param $operator
 * @param $right_operand
 *
 * @return bool|null Boolean comparison result or null if unknown operator given
 */
function fn_compare_values_by_operator($left_operand, $operator, $right_operand)
{
    if ($operator === 'eq') {
        return ($left_operand == $right_operand);
    } elseif ($operator === 'neq') {
        return ($left_operand != $right_operand);
    } elseif ($operator === 'lte') {
        return ($left_operand <= $right_operand);
    } elseif ($operator === 'lt') {
        return ($left_operand < $right_operand);
    } elseif ($operator === 'gte') {
        return ($left_operand >= $right_operand);
    } elseif ($operator === 'gt') {
        return ($left_operand > $right_operand);
    } elseif ($operator === 'cont') {
        return (stripos((string) $left_operand, (string) $right_operand) !== false);
    } elseif ($operator === 'ncont') {
        return (stripos((string) $left_operand, (string) $right_operand) === false);
    } elseif ($operator === 'in') {
        $right_operand = is_array($right_operand) ? $right_operand : fn_explode(',', $right_operand);
        if (is_array($left_operand)) {
            foreach ($right_operand as $item) {
                if (sizeof($left_operand) !== sizeof($item)) {
                    if (sizeof(@array_intersect_assoc($left_operand, $item)) === sizeof($item)) {
                        return true;
                    }
                } else {
                    array_multisort($left_operand);
                    array_multisort($item);
                    if ($left_operand == $item) {
                        return true;
                    }
                }
            }

            return false;
        } else {
            return in_array($left_operand, $right_operand, is_bool($left_operand));
        }
    } elseif ($operator === 'nin') {
        $right_operand = is_array($right_operand) ? $right_operand : fn_explode(',', $right_operand);
        if (is_array($left_operand)) {
            foreach ($right_operand as $item) {
                if (sizeof($left_operand) !== sizeof($item)) {
                    if (sizeof(@array_intersect_assoc($left_operand, $item)) === sizeof($item)) {
                        return false;
                    }
                } else {
                    array_multisort($left_operand);
                    array_multisort($item);
                    if ($left_operand == $item) {
                        return false;
                    }
                }
            }

            return true;
        } else {
            return !in_array($left_operand, $right_operand);
        }
    }

    return null;
}

/**
 * Allows to get value from array by dot-notation path
 *
 * @param string $path          Dot-notation path to desired value, for example: "foo.bar"
 * @param array  $data          Data source
 * @param null   $default_value Value to return when no value found
 *
 * @return mixed Value
 */
function fn_dot_syntax_get($path, $data, $default_value = null)
{
    $context = $data;
    $pieces = explode('.', $path);

    foreach ($pieces as $piece) {
        if (!isset($context[$piece])) {
            return $default_value;
        }
        $context = &$context[$piece];
    }

    return $context;
}

function fn_array_elements_to_keys($data, $key_field)
{
    $tmp = array();
    foreach ($data as $item) {
        $tmp[$item[$key_field]] = $item;
    }

    return $tmp;
}

function fn_is_development()
{
    if (defined('DEVELOPMENT') && DEVELOPMENT === true) {
        return true;
    } else {
        return false;
    }
}

/**
 * @return string Currently selected currency
 */
function fn_get_secondary_currency()
{
    return CART_SECONDARY_CURRENCY;
}

/**
 * @param array $auth Tygh::$app['session']['auth'] array
 *
 * @return bool Whether CSRF protection should be enabled
 */
function fn_is_csrf_protection_enabled($auth)
{
    return Registry::get('config.tweaks.anti_csrf')
           && !defined('CONSOLE')
           && !defined('API');
}

/**
 * Performs CSRF validation of given request.
 *
 * @param array $params List of parameters:
 *                      * server - $_SERVER array
 *                      * request - $_REQUEST array
 *                      * session - Tygh::$app['session'] array
 *                      * controller - curently running controller
 *                      * mode - currently running mode of controller
 *                      * action & dispatch_extra - additional dispatch parameters
 *                      * auth - Tygh::$app['session']['auth'] data
 *                      * area - 'A' or 'C'
 *
 * @return bool Whether request is safe in terms of CSRF protection
 */
function fn_csrf_validate_request($params)
{
    $validation_required = null;

    /**
     * Allows to require CSRF validation in certain cases.
     *
     * @param array $params List of parameters:
     *                      * server - $_SERVER array
     *                      * request - $_REQUEST array
     *                      * session - Tygh::$app['session'] array
     *                      * controller - curently running controller
     *                      * mode - currently running mode of controller
     *                      * action & dispatch_extra - additional dispatch parameters
     *                      * auth - Tygh::$app['session']['auth'] data
     *                      * area - 'A' or 'C'
     *
     * @param bool $validation_required A flag indicating whether the CSRF validation is required, defaults to null (auto-detect)
     */
    fn_set_hook('csrf_validate_request_pre', $params, $validation_required);

    if (is_null($validation_required)) {
        $area = $params['area'];
        $controller = $params['controller'];
        $mode = $params['mode'];
        $schema = fn_get_schema('security', 'csrf_validation');

        if (isset($schema[$area]['controllers'][$controller]['modes'][$mode]['validate'])) {
            $validation_required = !empty($schema[$area]['controllers'][$controller]['modes'][$mode]['validate']);
        } elseif (isset($schema[$area]['controllers'][$controller]['validate'])) {
            $validation_required = !empty($schema[$area]['controllers'][$controller]['validate']);
        } elseif (isset($schema[$area]['validate'])) {
            $validation_required = !empty($schema[$area]['validate']);
        }
    }

    if ($validation_required) {
        if ($params['server']['REQUEST_METHOD'] == 'POST'
            && (
                empty($params['session']['security_hash']) ||
                empty($params['request']['security_hash']) ||
                $params['request']['security_hash'] != $params['session']['security_hash']
            )
        ) {
            return false;
        }
    }

    return true;
}

/**
 * Updates session variable value with the value from REQUEST. Returns the value from REQUEST.
 * If the variable is not in REQUEST, returns the value from SESSION.
 *
 * @param array &$session Tygh::$app['session']
 * @param array $request $_REQUEST
 * @param string $param_name Variable key
 * @return mixed
 */
function fn_change_session_param(&$session, $request, $param_name)
{
    $ret_val = null;

    if (isset($request[$param_name])) {
        $session[$param_name] = $ret_val = $request[$param_name];

    } elseif (isset($session[$param_name])) {
        $ret_val = $session[$param_name];
    }

    return $ret_val;
}

/**
 * Wrapper over php 5.5 array_column function
 * @param array $array input array
 * @param mixed $column_key key for column
 * @param mixed $index_key key for index
 *
 * @deprecated since 4.11.5. Use the native array_column instead.
 * @return array values of column key
 */
function fn_array_column($array, $column_key, $index_key = null)
{
    return array_column($array, $column_key, $index_key);
}

/**
 * Allows to safely execute code that may throw an exception.
 *
 * @param callable $func Function that will be called
 *
 * @return bool|Exception True if code execution was successfull, Exception instance otherwise.
 */
function fn_catch_exception($func)
{
    $args = func_get_args();
    array_shift($args);

    try {
        call_user_func_array($func, $args);

        return true;
    } catch (\Exception $e) {
        return $e;
    }
}

/**
 * Checks if language is RTL
 * @param string $lang_code language code
 * @return boolean true if RTL
 */
function fn_is_rtl_language($lang_code = CART_LANGUAGE)
{
    $languages = array(
        'ur',
        'he',
        'yi',
        'ar',
        'fa',
        'ku',
    );

    return in_array($lang_code, $languages);
}

/**
 * Clear template cache
 */
function fn_clear_template_cache()
{
    fn_rm(Registry::get('config.dir.cache_templates'));
}

/**
 * Deletes status, its description and data by status identifier
 *
 * @param int $status_id Status identifier
 */
function fn_delete_status_by_id($status_id)
{
    db_query('DELETE FROM ?:statuses WHERE status_id = ?i', $status_id);
    db_query('DELETE FROM ?:status_descriptions WHERE status_id = ?i', $status_id);
    db_query('DELETE FROM ?:status_data WHERE status_id = ?i', $status_id);
}

/**
 * Returns status identifier by status code and type
 *
 * @param string    $status     One-letter status code
 * @param string    $type       One-letter status type
 * @param bool|null $is_default True if status is default, false if status is not default, null otherwise
 *
 * @return int|null Status identifier
 */
function fn_get_status_id($status, $type, $is_default = null)
{
    $default_condition = '';
    if (isset($is_default)) {
        $default_condition = db_process(
            'AND is_default = ?s',
            [$is_default ? YesNo::YES : YesNo::NO]
        );
    }
    $status_id = db_get_field(
        'SELECT status_id FROM ?:statuses'
        . ' WHERE status = ?s AND type = ?s ?p',
        $status,
        $type,
        $default_condition
    );

    return $status_id ? (int) $status_id : null;
}

/**
 * Returns statuses of specified type
 *
 * @param string $type One-letter status type
 *
 * @return array (
 *     status identifier => status code
 * )
 */
function fn_get_statuses_by_type($type)
{
    return db_get_hash_single_array("SELECT status_id, status FROM ?:statuses WHERE type = ?s", array('status_id', 'status'), $type);
}

/**
 * Hook handler for template_email_get_name.
 * Gets template name for order notification template.
 *
 * @param \Tygh\Template\Mail\Template $template
 * @param string $name
 */
function fn_core_template_email_get_name($template, &$name)
{
    static $statuses = null;

    if (strpos($template->getCode(), 'order_notification.') === 0) {
        if ($statuses === null) {
            $statuses = fn_get_statuses(STATUSES_ORDER, array(), true, true);
        }

        [$code, $status] = explode('.', $template->getCode());
        $status = strtoupper($status);

        if (isset($statuses[$status])) {
            $name = __('email_template.order_notification', array('[name]' => $statuses[$status]['description']));
        }
    }
}

/**
 * Hook handler
 *
 * Updates the time of the last editing of the product in DB (products -> updated_timestamp) when
 * the product options are changed
 *
 * @param array  $option_data      Array with option data
 * @param int    $option_id        Option identifier
 * @param array  $deleted_variants Array with deleted variants ids
 * @param string $lang_code        Language code to add/update option for
 */
function fn_core_update_product_option_post($option_data, $option_id, $deleted_variants, $lang_code)
{
    if (!empty($option_data['product_id'])) {
        fn_update_product(array('updated_timestamp' => TIME), $option_data['product_id']);
    }
}

/**
 * Hook handler
 * Updates the time of the last editing of the product in DB (products -> updated_timestamp) when
 * the product options are deleted
 *
 * @param int  $option_id      Option identifier
 * @param int  $pid            Product identifier
 * @param bool $option_deleted True if option was successfully deleted, false otherwise
 * @param int  $product_id     Identifier of the product from which the option should be
 *                             removed (for not global options)
 */
function fn_core_delete_product_option_post($option_id, $pid, $option_deleted, $product_id)
{
    if (!empty($option_data['product_id'])) {
        fn_update_product(array('updated_timestamp' => TIME), $product_id);
    }
}

/**
 * Hook handler
 *
 * Updates the time of the last editing of the product in DB (products -> updated_timestamp) when
 * the product status are changed
 *
 * @param array $params Parameters that control status changes (table, id_name, id)
 * @param bool  $result The result of the DB request for status change
 */
function fn_core_tools_change_status($params, $result)
{
    if ($params['id_name'] !== 'product_id') {
        return;
    }

    fn_update_product(
        [
            'updated_timestamp' => TIME,
            'to_status_by_tools' => $params['status']
        ],
        $params['id']
    );
}

/**
 * Sort multi-level tree
 *
 * @param array  $items          Items tree
 * @param string $subitems_field Field name where subitems are stored
 * @param array $order_by        Fields to order by
 * @param string $order          Sorting direction (asc/desc)
 *
 * @return array Sorted tree
 */
function fn_sort_tree($items, $subitems_field = 'subitems', $order_by = array('position'), $order = 'asc')
{
    // sort top-level items
    uasort($items, function ($item1, $item2) use ($order_by, $order) {
        // traverse all ordering fields until different values found
        foreach ($order_by as $field) {
            if ($item1[$field] > $item2[$field]) {
                return $order == 'asc' ? 1 : -1;
            } elseif ($item1[$field] < $item2[$field]) {
                return $order == 'asc' ? -1 : 1;
            }
        }
        return 0;
    });

    // sort subitems
    foreach ($items as $item_id => $item) {
        if (!empty($item[$subitems_field])) {
            $items[$item_id][$subitems_field] = fn_sort_tree($item[$subitems_field], $subitems_field, $order_by, $order);
        }
    }

    return $items;
}

//
// Converts human-readable date to timestamp
//

function fn_date_to_timestamp($date)
{
    return strtotime($date);
}

//
// Converts timestamp to human-readable date
//

function fn_timestamp_to_date($timestamp)
{
    return !empty($timestamp) ? date('d M Y H:i:s', intval($timestamp)) : '';
}

/*
 * Provides list of overrides and additional files for SQL dumps and layouts.
 *
 * @param string $file_path Source file
 * @param bool $get_override Get override files for the file
 * @param bool $get_posts Get post files for the file
 * @param string $edition Product edition (ULTIMATE/MULTIVENDOR)
 * @param string $build Product build
 * @param bool $allow_usage Enable function usage
 *
 * @return array|string File paths (when loading with post-files) or the single file path
 */
function fn_get_dev_files($file_path, $get_override = false, $get_posts = false, $edition = PRODUCT_EDITION, $build = PRODUCT_BUILD, $allow_usage = false)
{
    $files = array($file_path);



    if ($allow_usage) {
        $dir = dirname($file_path);
        $ext = fn_get_file_ext($file_path);
        $name = basename($file_path, ".{$ext}");
        $edition = fn_get_edition_acronym($edition);

        $overrides = $posts = array();
        $posts[] = sprintf('%s/%s.%s.post.%s', $dir, $name, $edition, $ext);
        if ($build) {
            $build = strtolower($build);

            $overrides[] = sprintf('%s/%s.%s.%s.override.%s', $dir, $name, $edition, $build, $ext);
            $overrides[] = sprintf('%s/%s.%s.override.%s', $dir, $name, $build, $ext);

            $posts[] = sprintf('%s/%s.%s.post.%s', $dir, $name, $build, $ext);
            $posts[] = sprintf('%s/%s.%s.%s.post.%s', $dir, $name, $edition, $build, $ext);
        }
        $overrides[] = sprintf('%s/%s.%s.override.%s', $dir, $name, $edition, $ext);

        if ($get_override) {
            foreach ($overrides as $override_file) {
                if (file_exists($override_file)) {
                    $files = array($override_file);
                    break;
                }
            }
            unset($override_file);
        }

        if ($get_posts) {
            foreach ($posts as $post_file) {
                if (file_exists($post_file)) {
                    $files[] = $post_file;
                }
            }
            unset($post_file);
        }
    }

    return $get_posts ? $files : $files[0];
}

/*
 * Gets product features with pagination
 *
 * @param array     $request        Params to generate pagination
 * @param string    $auth           Array of user authentication data
 * @param array     $product_data   Array of product data
 * @param string    $lang_code      Language code
 *
 * @return array pagination structure
 */
function fn_get_paginated_product_features($request, $auth, $product_data = array(), $lang_code = DESCR_SL)
{
    $path = array();

    if (!empty($request['over'])) {
        // get features for categories of selected products only
        $categories = db_get_fields(
            'SELECT ?:categories.id_path'
            . ' FROM ?:products_categories'
            . ' LEFT JOIN ?:categories'
                . ' ON ?:categories.category_id = ?:products_categories.category_id'
            . ' WHERE product_id IN (?n)'
                . ' AND ?:categories.storefront_id IN (?n)',
            Tygh::$app['session']['product_ids'],
            [0, StorefrontProvider::getStorefront()->storefront_id]
        );
        foreach ($categories as $category) {
            $path = array_merge($path, explode('/', $category));
        }
        $path = array_unique($path);
    }

    if (empty($product_data) && !empty($request['product_id'])) {
        $product_data = fn_get_product_data($request['product_id'], $auth, $lang_code, '', false, false, false, false, false, false, false, false);
    }

    if (!empty($product_data['product_id'])) {
        $path = !empty($product_data['category_ids']) ? fn_get_category_ids_with_parent($product_data['category_ids']) : '';
    }

    $_params = fn_array_merge($request, array(
        'category_ids' => $path,
        'product_company_id' => !empty($product_data['company_id']) ? $product_data['company_id'] : 0,
        'statuses' => array('A', 'H'),
        'variants' => true,
        'plain' => false,
        'display_on' => '',
        'existent_only' => isset($request['show_existent_only']) ? $request['show_existent_only'] : false,
        'variants_selected_only' => false,
    ));

    return fn_get_product_features($_params, PRODUCT_FEATURES_THRESHOLD, $lang_code);
}

/**
 * Gets licensed store mode name.
 *
 * @param string $store_mode Store mode
 *
 * @return string
 */
function fn_get_licensed_mode_name($store_mode)
{
    $name_parts = array(PRODUCT_NAME);

    if ($store_mode) {
        $name_parts[] = __("store_mode.{$store_mode}");
    }
    if (PRODUCT_BUILD) {
        $name_parts[] = PRODUCT_BUILD;
    }

    return implode(' ', $name_parts);
}

/**
 * Gets product edition, build and store mode as a lower-cased string with "." used as a separator.
 * Empty parts of suffix are skipped.
 * The provided suffix is mainly used in language varibles.
 *
 * @param string $store_mode Store mode
 *
 * @return string Product state suffix as "edition_acronym.build.store_mode"
 */
function fn_get_product_state_suffix($store_mode)
{
    return fn_get_edition_acronym(PRODUCT_EDITION) .
        (PRODUCT_BUILD ? '.' : '') . strtolower(PRODUCT_BUILD) .
        ($store_mode ? '.' : '') . $store_mode;
}

/**
 * Returns company ID of the blocks owner.
 * Result of this function is often used as a boolean in various checks.
 *
 * @return int|false Company ID of the vendor if vendors can manage blocks, false otherwise.
 */
function fn_get_blocks_owner()
{
    if (
        fn_allowed_for('MULTIVENDOR')
        && ($vendor_id = Registry::ifGet('runtime.vendor_id', Registry::get('runtime.company_id')))
        && (Registry::get('settings.Vendors.can_edit_blocks') === YesNo::YES)
    ) {
        return $vendor_id;
    }

    return false;
}

/**
 * Gets the parameters for endless scrolling.
 *
 * @param  array  params  The array of data required to form the scrolling parameters;
 *                        includes the number of the initial element and
 *                        the total number of included elements
 *
 * @return array  The array of scrolling parameters that includes the data from the
 *                params array and the part number
 */
function fn_get_scrolling_parameters($params = array())
{
    $params['start_limit'] = isset($params['start_limit']) ? $params['start_limit'] : 0;
    $params['count_limit'] = isset($params['count_limit']) ? $params['count_limit'] : 20;

    $scroll_params = array (
        'start_limit' => $params['start_limit'],
        'count_limit' => $params['count_limit'],
        'count_part' => 0,
    );

    $scroll_params['scroll_id'] = (!empty($params['scroll_id'])) ? $params['scroll_id'] : 0;

    $scroll_params['limit'] = $params['start_limit'] . ', ' . $params['count_limit'];

    if ($params['count_limit'] == 0) {
        $scroll_params['count_part'] = 1;

    } elseif ($params['start_limit'] >= $params['count_limit']) {
        $scroll_params['count_part'] = $params['start_limit'] / $params['count_limit'];
    }

    return $scroll_params;
}

/**
 * Returns company ID of the styles owner.
 * Result of this function is often used as a boolean in various checks.
 *
 * @return int|false Company ID of the vendor if vendors can manage styles, false otherwise.
 */
function fn_get_styles_owner()
{
    if (
        fn_allowed_for('MULTIVENDOR')
        && ($vendor_id = Registry::ifGet('runtime.vendor_id', Registry::get('runtime.company_id')))
        && (
            Registry::get('settings.Vendors.can_edit_styles') === YesNo::YES
            || (
                !empty(Tygh::$app['session']['auth']['act_as_area'])
                && !empty(Tygh::$app['session']['auth']['act_as_user'])
                && !empty(Tygh::$app['session']['auth']['area'])
                && UserTypes::isVendor(Tygh::$app['session']['auth']['act_as_area'])
                && YesNo::isTrue(Tygh::$app['session']['auth']['act_as_user'])
                && SiteArea::isAdmin(Tygh::$app['session']['auth']['area'])
            )
        )
    ) {
        return $vendor_id;
    }

    return false;
}

/**
 * Returns path to store cached css file for the Theme editor.
 *
 * @param string $css_filename CSS filename
 *
 * @return string Path if the cache directory
 */
function fn_get_theme_editor_tmp_css_path($css_filename)
{
    $cache_dir = 'theme_editor/';

    /**
     * Executes when determining the path where to store temporary style file for the Theme editor, allows to modiy path.
     *
     * @param string $css_filename CSS filename
     */
    fn_set_hook('get_theme_editor_tmp_css_path', $css_filename, $cache_dir);

    return sprintf('%s/%s/%s', rtrim(fn_get_cache_path(false), '/'), rtrim($cache_dir, '/'), $css_filename);
}

/**
 *
 * Gets the communication protocol to use for URLs when accessing a store in the console mode.
 *
 * @param string $area Area (C/A) to get setting for
 *
 * @return string Output URL protocol (protocol://).
 *
 * @throws \Tygh\Exceptions\DeveloperException
 */
function fn_get_console_protocol($area)
{
    if (!defined('CONSOLE')) {
        throw new DeveloperException('The console setting was used outside the console mode.');
    }

    if (($area == 'C' && Registry::get('settings.Security.secure_storefront') === YesNo::YES)
        || ($area == 'A' && Registry::get('settings.Security.secure_admin') === YesNo::YES)
    ) {
        return 'https';
    }

    return 'http';
}

/**
 * Checks availability of translation for the variable.
 *
 * @param string $value The name of variable.
 *
 * @return bool True - if the translation of the variable finds, false otherwise.
 */
function fn_is_lang_var_exists($value)
{
    return __($value) !== '_' . strtolower($value);
}

/**
 * Gets the company ID of the vendor whose content is being viewed.
 *
 * @return int|false Company ID of the vendor.
 */
function fn_get_runtime_vendor_id()
{
    if (fn_allowed_for('MULTIVENDOR')
        && ($vendor_id = Registry::ifGet('runtime.vendor_id', Registry::get('runtime.company_id')))
    ) {
        return $vendor_id;
    }

    return false;
}


/**
 * Get statistics for database tables.
 *
 * @return array
 */
function fn_get_stats_tables()
{
    $status_data = db_get_array("SHOW TABLE STATUS");
    $database_size = 0;
    $all_tables = array();
    foreach ($status_data as $v) {
        $database_size += $v['Data_length'] + $v['Index_length'];
        $all_tables[] = $v['Name'];
    }

    return [$database_size, $all_tables];
}

/**
 * Provides a console command for controller launch.
 *
 * @param string $prefix Path to script
 * @param string $script Script name
 * @param array  $args   Arguments script
 *
 * @return string
 */
function fn_get_console_command($prefix, $script, $args)
{
    $args_string = '';
    foreach ($args as $arg => $value) {
        if (is_int($arg)) {
            $args_string = $args_string . ' -' . $args[$arg];
            unset($args[$arg]);
        }
    }
    $args_string = $args_string . '&' . rawurldecode(http_build_query($args));
    $args_string = preg_replace('/&/', ' --', $args_string);

    $prefix = rtrim($prefix, '/') . '/';
    $cmd = $prefix . $script . $args_string;

    /**
     * Allows change the console command.
     *
     * @param string $prefix URL path to script
     * @param string $script Script name
     * @param string $args   Arguments script
     * @param string $cmd    Command
     */
    fn_set_hook('fn_get_console_command', $prefix, $script, $args, $cmd);

    return $cmd;
}

/**
 * Returns a filtered list of phone masks in international format.
 *
 * @param boolean $encode_to_json Flag responsible for post-encoding the list into JSON
 *
 * @return array|string List of phone masks
 */
function fn_get_phone_masks($encode_to_json)
{
    $countries_list = fn_get_simple_countries(true);

    $phone_masks = array();
    $phone_masks_file_path = Registry::get('config.dir.root') . '/js/lib/inputmask-multi/phone-codes.json';

    if (file_exists($phone_masks_file_path)) {
        $phone_masks = json_decode(file_get_contents($phone_masks_file_path), true);
        $phone_masks = array_filter($phone_masks, function ($mask_data) use ($countries_list) {
            if (!is_array($mask_data['cc'])) {
                return isset($countries_list[$mask_data['cc']]);
            }

            foreach ($mask_data['cc'] as $country_code) {
                if (isset($countries_list[$country_code])) {
                    return true;
                }
            }

            return false;
        });
    }

    return $encode_to_json ? json_encode(array_values($phone_masks)) : $phone_masks;
}

/**
 * @param \Tygh\Storefront\Repository $repository
 * @param int                         $company_id
 *
 * @internal
 * @return string
 */
function fn_get_storefront_status_manage_url($repository = null, $company_id = null)
{
    if (fn_allowed_for('ULTIMATE')) {
        $url = Url::buildUrn(['companies', 'manage']);
        if ($company_id === null) {
            $company_id = (int) Registry::get('runtime.company_id');
        }
        $companies = fn_get_all_companies_ids();
        if (!$company_id && count($companies) === 1) {
            $company_id = reset($companies);
        }
        if ($company_id) {
            $url = Url::buildUrn(['companies', 'update'], ['company_id' => $company_id]);
        }
    } else {
        if ($repository === null) {
            /** @var \Tygh\Storefront\Repository $repository */
            $repository = Tygh::$app['storefront.repository'];
        }
        $url = Url::buildUrn(['storefronts', 'manage']);
        $storefront = null;
        if ($repository->getCount() === 1) {
            /** @var \Tygh\Storefront\Storefront[] $storefronts */
            [$storefronts,] = $repository->find();
            $storefront = reset($storefronts);
        }
        if ($storefront) {
            $url = Url::buildUrn(['storefronts', 'update'], ['storefront_id' => $storefront->storefront_id]);
        }
    }

    return fn_url($url);
}

/**
 * Gets all rules about notifications
 *
 * @return array Rules for notifications about all events to all receivers by all transports
 */
function fn_get_notification_settings()
{
    $table = db_get_array('SELECT * FROM ?:notification_settings');
    $result = [];
    foreach ($table as $events) {
        $result[$events['event_id']][$events['receiver']][$events['transport_id']] = ($events['is_allowed'] !== '0');
    }

    return $result;
}

/**
 * Set a rule that allow/disallow send notification by specified transport to specified receiver about specified event
 *
 * @param string $event_id     Specified identifier of event
 * @param string $transport_id Specified identifier of transport for sending notifications
 * @param string $receiver     Specified receiver of notifications
 * @param int    $allowed      Represent allowing (1) or disallowing (0) of sending notification
 */
function fn_set_notification_settings($event_id, $transport_id, $receiver, $allowed = 1)
{
    if (empty($event_id) || empty($transport_id) || empty($receiver)) {
        return;
    }
    $data = [
        'is_allowed'   => $allowed,
        'event_id'     => $event_id,
        'transport_id' => $transport_id,
        'receiver'     => $receiver,
    ];
    db_query('REPLACE INTO ?:notification_settings ?e', $data);
}

function fn_get_customer_layout_theme_path()
{
    $company_id = null;
    if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.company_id')) {
        $company_id = (int) db_get_field('SELECT MIN(company_id) FROM ?:companies');
    }

    $theme_name = fn_get_theme_path('[theme]', 'C', $company_id);
    $theme_path = fn_get_theme_path('[themes]/[theme]', 'C', $company_id);

    return array($theme_path, $theme_name);
}

/**
 * Checks if objects should be displayed in a picker
 *
 * @param string $table     Name of SQL table with objects
 * @param int    $threshold Value of the threshold after which the picker should be displayed
 *
 * @return boolean Flag that defines if picker should be displayed
 */
function fn_show_picker($table, $threshold)
{
    /**
     * Changes params for the 'fn_show_picker' function
     *
     * @param string $table     Table name
     * @param string $threshold Value of the threshold after which the picker should be displayed
     */
    fn_set_hook('show_picker_pre', $table, $threshold);

    $picker = db_has_table($table) && db_get_field("SELECT COUNT(*) FROM ?:$table") > $threshold
        ? true
        : false;

    /**
     * Changes result of the 'fn_show_picker' function
     *
     * @param string  $table     Table name
     * @param string  $threshold Value of the threshold after which the picker should be displayed
     * @param boolean $picker    Flag that defines if data should be displayed in picker
     */
    fn_set_hook('show_picker_post', $table, $threshold, $picker);

    return $picker;
}

/**
 * Recursively sorts an array using a user-supplied comparison function
 *
 * @param array    $array    Array for sorting
 * @param string   $key      Key of subarray for sorting
 * @param callback $function Comparison function
 */
function fn_sort(&$array, $key, $function)
{
    usort($array, $function);
    foreach ($array as $k => $v) {
        if (!empty($v[$key])) {
            fn_sort($array[$k][$key], $key, $function);
        }
    }
}

/**
 * Generate url-safe name for the object
 * Example:
 *  Hello, World! => hello-world
 *  Русский код => russky-kod
 *
 * @param string  $str           String to be checked and converted
 * @param string  $object_type   Extra string, object type (e.g.: 'products', 'categories'). Result: some-string-products
 * @param int     $object_id     Extra string, Object identifier. Result: some-string-products-34
 * @param boolean $is_multi_lang Support multi-language names
 * @return string Url-safe name
 */
function fn_generate_name($str, $object_type = '', $object_id = 0, $is_multi_lang = false)
{
    /**
     * Change parameters for generating file name
     *
     * @param string $str         Basic file name
     * @param string $object_type Object type
     * @param int    $object_id   Object identifier
     */
    fn_set_hook('generate_name_pre', $str, $object_type, $object_id);

    $delimiter = SEO_DELIMITER;
    $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8'); // convert html special chars back to original chars

    $result = '';

    if (!empty($str)) {
        if ($is_multi_lang) {
            $literals = "/[^a-z\p{Ll}\p{Lu}\p{Lt}\p{Lm}\p{Lo}\p{Nd}\p{Pc}\p{Mn}0-9-\.]/u";
            $convert_letters = fn_get_schema('literal_converter', 'general');
        } else {
            $literals = "/[^a-z0-9-\.]/";
            $convert_letters = fn_get_schema('literal_converter', 'schema');
        }
        $str = strtr($str, $convert_letters);

        if (!empty($object_type)) {
            $str .= $delimiter . $object_type . $object_id;
        }

        $str = fn_strtolower($str); // only lower letters
        $str = preg_replace($literals, '', $str); // URL can contain latin letters, numbers, dashes and points only
        $str = preg_replace("/($delimiter){2,}/", $delimiter, $str); // replace double (and more) dashes with one dash

        $result = trim($str, '-'); // remove trailing dash if exist
    }

    /**
     * Change generated file name
     *
     * @param string $result      Generated file name
     * @param string $str         Basic file name
     * @param string $object_type Object type
     * @param int    $object_id   Object identifier
     */
    fn_set_hook('generate_name_post', $result, $str, $object_type, $object_id);

    return $result;
}

function fn_sort_by_ids($items, $ids, $field = 'product_id')
{
    $tmp = array();

    foreach ($items as $k => $item) {
        foreach ($ids as $key => $item_id) {
            if ($item_id == $item[$field]) {
                $tmp[$key] = $item;
                break;
            }
        }
    }

    ksort($tmp);

    return $tmp;
}

/**
 * Gets a list by the given ids
 *
 * @param array<array|object> $items List of items
 * @param array<array|string> $ids   Searched ids list
 * @param string              $field Search field
 *
 * @return array List of found items
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_get_items_by_ids($items, $ids, $field = 'product_id')
{
    $tmp = [];

    foreach ($ids as $key => $id) {
        foreach ($items as $item) {
            if ($id === $item[$field]) {
                $tmp[$key] = $item;
                break;
            }
        }
    }

    return $tmp;
}

/**
 * Loads additional data related to given enities from corresponding tables.
 * Each specified table triggers single SQL-query.
 *
 * @param array $params Configuration of which fields should be loaded in the following format:
 * array(
 *      table_name => array(
 *          // Name of primary key used by items' table. You may specify SQL expression to use in "fields" array.
 *          'primary_key' => 'product_id',
 *          'fields' => array( // list of fields names from this table to be loaded
 *              'product_id' => 'table_name.object_id',
 *              'short_description',
 *              // you may specify SQL expression that would be used instead field name in the SQL query
 *              'full_description' => 'IF(table_name.full_description = '', table_name.short_description, '')
 *          ),
 *          // optional parameter, allows to specify additional conditions used by SQL query
 *          'condition' => db_query(' AND table_name.company_id = ?i', $company_id),
 *          // optional parameter, allows to specify additional joins used by SQL query
 *          'join' => '',
 *          // optional parameter, allows to specify grouping used by SQL query
 *          'group_by' => '',
 *      )
 * )
 * @param array $item_ids List of entity identifiers
 *
 * @return array List of extra data in format: array(table_name => array(item_id => extra_data, ...), ...)
 */
function fn_load_extra_data_by_entity_ids($params, $item_ids)
{
    $extra_data = [];
    foreach ($params as $table_name => $table_config) {
        if (empty($table_config) || empty($table_config['primary_key']) || empty($table_config['fields'])) {
            continue;
        }
        $primary_key_field = $table_config['primary_key'];
        if (isset($table_config['fields'][$primary_key_field])) {
            $primary_key_expr = $table_config['fields'][$primary_key_field];
        } else {
            $primary_key_expr = $table_name . '.' . $primary_key_field;
        }

        $select_fields = in_array('*', $table_config['fields'])
            ? []
            : [$primary_key_field => $primary_key_expr];

        foreach ($table_config['fields'] as $k => $v) {
            if (is_integer($k)) {
                $select_fields[$v] = $table_name . '.' . $v;
            } else {
                $select_fields[$k] = $v . ' AS ' . $k;
            }
        }
        $select_fields = implode(', ', $select_fields);

        $condition = empty($table_config['condition'])
            ? ''
            : $table_config['condition'];
        $join = empty($table_config['join'])
            ? ''
            : $table_config['join'];
        $group_by = empty($table_config['group_by'])
            ? ''
            : $table_config['group_by'];

        $extra_data[$table_name] = db_get_hash_array(
            "SELECT $select_fields FROM $table_name $join WHERE $primary_key_expr IN (?n) $condition $group_by",
            $primary_key_field,
            $item_ids
        );
    }

    return $extra_data;
}

/**
 * Merges extra data loaded by fn_load_extra_data to given entity list.
 *
 * @param array $extra_data Data in the following format: array(entity_id => additional_data, ...)
 * @param array $entities   Entity list passed by reference in format: array(entity_id => entity_data, ...)
 */
function fn_merge_extra_data_to_entity_list($extra_data, &$entities)
{
    foreach ($extra_data as $table_name => $table_extra_data) {
        foreach ($table_extra_data as $entity_id => $data) {
            if (isset($entities[$entity_id])) {
                $entities[$entity_id] = array_merge($entities[$entity_id], $data);
            }
        }
    }
}

function fn_convert_weight($weight)
{
    /**
     * Change weight before converting
     *
     * @param float $weight Weight for converting
     */
    fn_set_hook('convert_weight_pre', $weight);

    if (Registry::get('config.localization.weight_unit')) {
        $g = Registry::get('settings.General.weight_symbol_grams');
        $weight = $weight * Registry::get('config.localization.weight_unit') / $g;
    }

    $result = sprintf('%01.3f', $weight);

    /**
     * Change the converted weight
     *
     * @param float $result Converted weight
     * @param float $weight Weight for converting
     */
    fn_set_hook('convert_weight_post', $result, $weight);

    return $result;
}

/**
 * @param string[] $receivers
 *
 * @return array
 *
 * @internal
 */
function fn_get_notification_receiver_search_conditions(array $receivers)
{
    $recipients = [
        'groups' => array_fill_keys($receivers, []),
        'events' => array_fill_keys($receivers, []),
    ];

    $group_settings = db_get_array('SELECT * FROM ?:notification_group_receivers ORDER BY id ASC');
    $event_settings = db_get_array('SELECT * FROM ?:notification_event_receivers ORDER BY id ASC');

    foreach ($group_settings as $data) {
        $recipients['groups'][$data['group_id']][$data['receiver']][] = SearchCondition::makeOne($data);
    }

    foreach ($event_settings as $data) {
        $recipients['events'][$data['event_id']][$data['receiver']][] = SearchCondition::makeOne($data);
    }

    return $recipients;
}

/**
 * @param string                                          $object_type
 * @param string                                          $object_id
 * @param string                                          $receiver_type
 * @param \Tygh\Notifications\Receivers\SearchCondition[] $conditions
 *
 * @internal
 */
function fn_update_notification_receiver_search_conditions($object_type, $object_id, $receiver_type, array $conditions)
{
    $table = "notification_{$object_type}_receivers";
    $id_field = "{$object_type}_id";

    db_query(
        'DELETE FROM ?:?f WHERE ?w',
        $table,
        [
            $id_field    => $object_id,
            'receiver'   => $receiver_type,
        ]
    );

    if (!$conditions) {
        return;
    }

    $conditions_data = array_map(
        function ($condition) use ($id_field, $object_id, $receiver_type) {
            /** @var \Tygh\Notifications\Receivers\SearchCondition $condition */
            return [
                $id_field   => (string) $object_id,
                'method'    => $condition->getMethod(),
                'criterion' => $condition->getCriterion(),
                'receiver'  => (string) $receiver_type,
            ];
        },
        $conditions
    );

    db_query('INSERT INTO ?:?f ?m', $table, $conditions_data);
}

/**
 * Convert price from particular currency to base currency
 *
 * @param float  $price         Currency
 * @param string $currency_code Currency code
 *
 * @return float Converted currencty
 */
function fn_convert_price($price, $currency_code = CART_PRIMARY_CURRENCY)
{
    /**
     * Change price before converting
     *
     * @param float  $price         Price for converting
     * @param string $currency_code Price currency code
     */
    fn_set_hook('convert_price_pre', $price, $currency_code);

    $currencies = Registry::get('currencies');
    $result = $price * $currencies[$currency_code]['coefficient'];

    /**
     * Change the converted price
     *
     * @param float  $result        Converted price
     * @param float  $price         Price for converting
     * @param string $currency_code Price currency code
     */
    fn_set_hook('convert_price_post', $result, $price, $currency_code);

    return $result;
}

/**
 * Gets statuses list with specific param
 *
 * @param string $type   One-letter status type code
 * @param array  $params Array for searching param like [param_name => param_value] if status is matched at least one
 *                       of the condition it will be found
 *
 * @return array Status identifiers list with specific params
 */
function fn_get_status_by_type_and_param($type, array $params)
{
    if (empty($type) || empty($params)) {
        return [];
    }

    $conditions = [];

    foreach ($params as $param_name => $param_value) {
        $conditions[] = db_quote('(?s, ?s)', $param_name, $param_value);
    }

    $condition = implode(', ', $conditions);

    return db_get_fields(
        'SELECT ?:statuses.status'
        . ' FROM ?:statuses'
        . ' INNER JOIN ?:status_data'
            . ' ON ?:status_data.status_id = ?:statuses.status_id'
        . ' WHERE type = ?s'
            . ' AND (param, value) IN (?p)',
        $type,
        $condition
    );
}

/**
 * Checks if a user is an administrator.
 *
 * @return bool Returns true if area is "A" and ACCOUNT_TYPE is "admin" or false otherwise.
 */
function fn_is_admin_account_type()
{
    return AREA === 'A' && ACCOUNT_TYPE === 'admin';
}

/**
 * Validates controller via antibot rules schema.
 *
 * @param string $controller
 * @param string $mode
 * @param string $request_method
 * @param array  $request
 *
 * @return array|null
 */
function fn_validate_controller_with_antibot($controller, $mode, $request_method, array $request)
{
    /** @var \Tygh\Web\Antibot $antibot */
    $antibot = Tygh::$app['antibot'];

    if ($antibot->getDriver() instanceof NullDriver) {
        return null;
    }

    $antibot_rules = fn_get_schema('security', 'antibot_rules');

    if (!isset($antibot_rules[$controller][$mode])) {
        return null;
    }

    $rule = $antibot_rules[$controller][$mode];

    if (isset($rule['condition']) && is_callable($rule['condition'])) {
        $condition_result = call_user_func($rule['condition'], $request);

        if (!$condition_result) {
            return null;
        }
    }

    if (
        isset($rule['request_method'])
        && fn_strtolower($request_method) !== fn_strtolower($rule['request_method'])
    ) {
        return null;
    }

    if (empty($rule['verification_scenario'])) {
        throw new DeveloperException('Antibot validation rule must contain "verification_scenario" parameter.');
    }

    if (false !== fn_image_verification($rule['verification_scenario'], $request)) {
        return null;
    }

    if (isset($rule['save_post_data'])) {
        call_user_func_array('fn_save_post_data', (array) $rule['save_post_data']);
    }

    if (!empty($rule['terminate_process'])) {
        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    if (!isset($rule['rewrite_controller_status']) || !is_array($rule['rewrite_controller_status'])) {
        return null;
    }

    if (!empty($rule['rewrite_controller_status'][1])) {
        $location = array_pop($rule['rewrite_controller_status']);
        if (is_callable($location)) {
            $location = call_user_func($location);
        }
        $rule['rewrite_controller_status'][] = $location;
    } // Redirect was desired, but no redirect URL was passed
    elseif ($rule['rewrite_controller_status'][0] === CONTROLLER_STATUS_REDIRECT) {
        // Just exit here, because core will continue executing controller stack when no redirect URL is given
        if (empty($request['redirect_url'])) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $rule['rewrite_controller_status'][] = $request['redirect_url'];
    }

    return $rule['rewrite_controller_status'];
}

/**
 * Defines is admin panel will be shown or not
 *
 * @param array $auth Authentication user data
 *
 * @return bool True if bottom panel is available, false otherwise
 */
function fn_is_bottom_panel_available(array $auth)
{
    return defined('THEMES_PANEL')
        || (!empty($auth['user_type']) && in_array($auth['user_type'], [UserTypes::VENDOR, UserTypes::ADMIN]));
}

/**
 * Defines is setup wizard panel will be shown or not
 *
 * @param array $auth Authentication user data
 *
 * @return bool True if setup wizard panel is available, false otherwise
 */
function fn_is_setup_wizard_panel_available(array $auth)
{
    $schema = fn_get_schema('setup_wizard', 'settings');

    return !empty($schema)
        && !empty($auth['user_type'])
        && UserTypes::isAdmin($auth['user_type'])
        && YesNo::isTrue($auth['is_root']);
}

/**
 * Defines is theme editor can be activated by authorized user
 *
 * @param array $auth Authentication user data
 *
 * @return bool True if theme editor is available, false otherwise
 */
function fn_is_theme_editor_available_for_user(array $auth)
{
    if (
        empty($auth)
        || empty($auth['user_id'])
        || empty($auth['user_type'])
    ) {
        return false;
    }

    if ($auth['user_type'] !== UserTypes::VENDOR) {
        return fn_check_permissions('customization', 'update_mode', 'admin', '', ['type' => 'theme_editor'], AREA, $auth['user_id']);
    }

    return fn_check_company_permissions('customization', 'update_mode', '', ['type' => 'theme_editor'])
        && ((int) fn_get_styles_owner() === (int) $auth['company_id']);
}

/**
 *  Prepares some data for bottom panel
 *
 * @return array Prepared bottom panel data
 */
function fn_prepare_bottom_panel_data()
{
    $quick_menu = fn_get_quick_menu_data();

    $bottom_panel_data = [
        'quick_menu' => $quick_menu,
    ];

    /**
     * Allows to extend data provided to bottom panel
     *
     * @param array $bottom_panel_data Bottom panel data for this user
     */
    fn_set_hook('prepare_bottom_panel_data', $bottom_panel_data);

    return $bottom_panel_data;
}

/**
 * The "update_language_post" hook handler.
 *
 * Actions performed:
 *  - Updates data which is not directly relate to language, but depends on it.
 *
 * @see \Tygh\Languages\Languages::update()
 *
 * @internal
 */
function fn_core_update_language_post($language_data, $lang_id, $action, array $previous_language_data)
{
    if ($action === 'add') {
        fn_promotions_update_language_post($language_data);
    }

    if (
        $action !== 'update'
        || $previous_language_data['storefront_ids']
        || empty($language_data['storefront_ids'])
    ) {
        return;
    }

    Languages::saveStorefrontLanguageIntegrity();
}

/**
 * The "delete_languages_post" hook handler.
 *
 * Actions performed:
 *  - Deletes data which is not directly relate to language, but depends on it.
 *
 * @see \Tygh\Languages\Languages::deleteLanguages()
 */
function fn_core_delete_languages_post($lang_ids, $lang_codes, $deleted_lang_codes)
{
    fn_promotions_delete_languages_post($deleted_lang_codes);
}

/**
 * The "delete_image_pre" hook handler.
 *
 * Actions performed:
 *  - Deletes image data from related tables
 *
 * @see fn_delete_image()
 */
function fn_core_delete_image_pre($image_id, $pair_id, $object_type)
{
    if ($object_type == 'promotion') {
        fn_promotions_delete_image_pre($pair_id);
    }
}

/**
 * Gets menu items that declared in the specified menu schema fragment.
 *
 * @param array<string, string|array<string, string>> $schema          Menu schema fragment
 * @param array<string, string>|null                  $previous_parent Previous menu item parent
 *
 * @psalm-param array{
 *   id: string,
 *   title: string|null,
 *   parent: array{
 *     id: string,
 *     title: string|null,
 *     parent: null,
 *   }|null
 * } $previous_parent
 *
 * @return array<string, array<string|null|array<string>>>
 *
 * @psalm-return array<string, array{
 *   id: string,
 *   parent: array{
 *     id: string,
 *     parent: array{
 *       id: string,
 *       parent: array{
 *         id: string,
 *         parent: null,
 *         title: null|string
 *       }|null,
 *       title: null|string
 *     }|null,
 *     title: null|string
 *   }|null,
 *   title: null|string
 * }>
 */
function fn_get_admin_panel_menu_items(array $schema, array $previous_parent = null)
{
    $menu_items = [];

    foreach ($schema as $item_id => $item_data) {
        $subitems = [];
        $parent = null;

        if (in_array($item_id, ['top', 'central'], true)) {
            $subitems = $item_data;
        } elseif (isset($item_data['items'])) {
            $subitems = $item_data['items'];
            $parent = [
                'id' => $item_id,
                'title' => isset($item_data['title']) ? $item_data['title'] : null,
                'parent' => $previous_parent,
            ];
        } elseif (isset($item_data['subitems'])) {
            $subitems = $item_data['subitems'];
            $parent = [
                'id' => $item_id,
                'title' => isset($item_data['title']) ? $item_data['title'] : null,
                'parent' => $previous_parent,
            ];
        }

        /** @psalm-suppress PossiblyInvalidIterator */
        foreach ($subitems as $subitem_id => $subitem_data) {
            if (!isset($subitem_data['href'])) {
                continue;
            }

            $subitem_href = fn_substitute_vars((string) $subitem_data['href'], Registry::get('config'));
            $menu_items[$subitem_href] = [
                'id' => $subitem_id,
                'title' => isset($subitem_data['title']) ? (string) $subitem_data['title'] : null,
                'parent' => $parent,
            ];
        }

        if (!$subitems) {
            continue;
        }

        /**
         * @psalm-suppress PossiblyInvalidArgument
         * @psalm-suppress ArgumentTypeCoercion
         */
        $menu_items = array_merge($menu_items, fn_get_admin_panel_menu_items($subitems, $parent));
    }

    return $menu_items;
}

/**
 * Saves synchronization settings
 *
 * @param string $provider_id Provider identifier
 * @param int    $company_id  Company identifier
 * @param array  $data        Data to save
 */
function fn_save_sync_data_settings($provider_id, $company_id, array $data)
{
    $data = [
        'provider_id'   => $provider_id,
        'settings_data' => json_encode($data),
        'company_id'    => $company_id,
    ];

    db_replace_into('sync_data_settings', $data);
}

/**
 * Gets sync data settings by provider and company identifiers
 *
 * @param string $provider_id Provider identifier
 * @param int    $company_id  Company identifier
 *
 * @return array
 */
function fn_get_sync_data_settings($provider_id, $company_id)
{
    $provider_settings = [];

    $settings = db_get_row('SELECT * FROM ?:sync_data_settings WHERE provider_id = ?s AND company_id = ?i', $provider_id, $company_id);

    if (isset($settings['settings_data'])) {
        $provider_settings = json_decode($settings['settings_data'], true);
    }

    return $provider_settings;
}

/**
 * Checks the permissions to sync data page
 *
 * @return bool
 */
function fn_check_permission_sync_data()
{
    $schema = fn_get_schema('sync_data', 'sync_data');

    if (empty($schema)) {
        return false;
    }

    return true;
}

/**
 * Removes demo data
 *
 * @return bool
 */
function fn_remove_demo_data()
{
    set_time_limit(0);

    $items = fn_get_schema('setup_wizard', 'cleanup_demo_data');

    foreach ($items as $name => $item) {
        if (!is_callable($item)) {
            continue;
        }
        $item();
    }

    return true;
}

/**
 * Install addon
 *
 * @param string $addon Internal addon name
 *
 * @return bool True if success, false otherwise
 */
function fn_setup_wizard_install_addon($addon)
{
    fn_install_addon($addon);
    if (!fn_get_addon_version($addon)) {
        return false;
    }
    fn_update_addon_status($addon, ObjectStatuses::ACTIVE);

    return true;
}

/**
 * Modifies vendor settings data for displaying
 *
 * @param array<string, string|int|bool|array> $vendor_tab_data Received from settings schema tab data with additional
 *                                             info from db.
 *
 * @return array<string, string|int|bool|array> Tab data with changed setting types for another displaying
 */
function fn_setup_wizard_prepare_vendors_settings(array $vendor_tab_data)
{
    foreach ((array) $vendor_tab_data['sections'] as $key => $section) {
        if (empty($section['items'])) {
            continue;
        }

        foreach ($section['items'] as $item_key => $item) {
            /** @var array<int, array<string, array<int, array<string, array>>>> $vendor_tab_data['sections'] */
            $vendor_tab_data['sections'][(int) $key]['items'][(int) $item_key]['setting_data']['type'] = SettingTypes::CHECKBOX;

            if ($item['section'] !== 'vendor_data_premoderation') {
                continue;
            }

            $vendor_tab_data['sections'][$key]['items'][$item_key]['setting_data']['false_checkbox_value'] = ($item['setting_data']['value'] === 'all')
                ? 'none'
                : $item['setting_data']['value'];

            $vendor_tab_data['sections'][$key]['items'][$item_key]['setting_data']['true_checkbox_value'] = 'all';
        }
    }

    return $vendor_tab_data;
}

/**
 * Checks all business model for available and returns its
 *
 * @return array<string, array>
 */
function fn_setup_wizard_get_available_business_models()
{
    if (!fn_allowed_for('MULTIVENDOR')) {
        return [];
    }

    $available_models = [];

    $business_models = fn_get_schema('setup_wizard', 'business_model');

    foreach ($business_models as $type => $business_model_data) {
        $required_addons = [];
        foreach ($business_model_data['addons'] as $addons) {
            $business_model_required_addon = array_filter($addons, static function($addon_status) {
                return $addon_status === ObjectStatuses::ACTIVE;
            });

            $required_addons = array_merge($required_addons, $business_model_required_addon);
        }

        if (empty($required_addons)) {
            $available_models[(string) $type] = $business_model_data;
            continue;
        }

        $is_model_available = false;

        foreach (array_keys($required_addons) as $addon_name) {
            $is_addon_available = fn_check_addon_snapshot($addon_name);

            if (!$is_addon_available) {
                $is_model_available = false;
                break;
            }

            $is_model_available = true;
        }

        if (!$is_model_available) {
            continue;
        }

        $available_models[(string) $type] = $business_model_data;
    }

    return $available_models;
}

/**
 * Gets current business model
 *
 * @return string|false
 */
function fn_setup_wizard_get_current_business_model()
{
    $available_business_models = fn_setup_wizard_get_available_business_models();

    foreach ($available_business_models as $type => $bussines_model) {
        $addon_list = $bussines_model['addons'];

        $addons_available_for_current_model = false;

        foreach ($addon_list as $addons) {
            foreach ($addons as $addon => $required_status) {
                $status = Registry::get('addons.' . $addon . '.status');

                if (empty($status) && $required_status === ObjectStatuses::ACTIVE) {
                    $addons_available_for_current_model = false;
                    continue;
                }

                if ($status === $required_status || (empty($status) && $required_status === ObjectStatuses::DISABLED)) {
                    $addons_available_for_current_model = true;
                    break;
                }

                $addons_available_for_current_model = false;
            }

            if (!$addons_available_for_current_model) {
                break;
            }
        }

        $payments_available_for_current_model = true;

        if (isset($bussines_model['processors_scripts'])) {
            foreach ($bussines_model['processors_scripts'] as $processor_script) {
                if (fn_get_payments(['processor_script' => $processor_script])) {
                    continue;
                }
                $payments_available_for_current_model = false;
                break;
            }
        }

        if (!$addons_available_for_current_model || !$payments_available_for_current_model) {
            continue;
        }

        return $type;
    }

    return MoneyTransferTypes::TO_OWNER;
}

/**
 * Modifies addons statuses depends on selected money transfer type
 *
 * @param string $type Type of money transfer selected on setup wizard
 *
 * @return void
 */
function fn_setup_wizard_update_money_transfer_type($type)
{
    if (empty($type)) {
        return;
    }

    $business_models = fn_get_schema('setup_wizard', 'business_model');

    if (empty($business_models) || empty($business_models[$type])) {
        return;
    }

    foreach ($business_models[$type]['addons'] as $addons) {
        foreach ($addons as $addon_name => $required_status) {
            $status = Registry::get('addons.' . $addon_name . '.status');
            if (empty($status) && $required_status === ObjectStatuses::ACTIVE) {
                fn_setup_wizard_install_addon($addon_name);
            }

            if ($status !== $required_status) {
                fn_update_addon_status($addon_name, $required_status);
            }

            if (!isset($business_models[$type]['processors_scripts'][$addon_name])) {
                continue;
            }

            fn_setup_wizard_install_payment($business_models[$type]['processors_scripts'][$addon_name]);
        }
    }
}


/**
 * Modifies addons statuses and settings depends on selected vendor registration flow
 *
 * @param string $type Type of vendor registration flow selected on setup wizard
 *
 * @return void
 */
function fn_setup_wizard_update_vendor_registration_flow($type)
{
    if (empty($type)) {
        return;
    }

    $registration_flow_schema = fn_get_schema('setup_wizard', 'registration_flow');

    if (empty($registration_flow_schema) || empty($registration_flow_schema[$type])) {
        return;
    }

    $settings = Settings::instance();

    foreach ($registration_flow_schema[$type]['addons'] as $addon_name => $data) {
        $status = Registry::get('addons.' . $addon_name . '.status');
        if (
            empty($status) && $data['status'] === ObjectStatuses::ACTIVE
            && empty($data['is_optional'])
        ) {
            fn_setup_wizard_install_addon($addon_name);
            continue;
        }

        if (!empty($data['settings'])) {
            foreach ($data['settings'] as $setting_name => $setting_data) {
                $settings->updateValue($setting_name, $setting_data['value'], $addon_name);
            }
        }

        if (
            $status === $data['status']
            || !empty($data['is_optional'])
        ) {
            continue;
        }

        fn_update_addon_status($addon_name, $data['status']);
    }

    if (empty($registration_flow_schema[$type]['settings'])) {
        return;
    }

    foreach ($registration_flow_schema[$type]['settings'] as $setting_name => $setting_data) {
        $settings->updateValue($setting_name, $setting_data['value']);
    }
}

/**
 * Gets current registration flow type
 *
 * @return string|false
 */
function fn_setup_wizard_get_current_registration_flow_type()
{
    $registration_flow_schema = fn_get_schema('setup_wizard', 'registration_flow');

    $settings = Settings::instance();

    foreach ($registration_flow_schema as $registration_flow_type => $registration_flow) {
        foreach ($registration_flow['addons'] as $addon_name => $data) {
            $status = Registry::get('addons.' . $addon_name . '.status');
            if (
                empty($status) && $data['status'] === ObjectStatuses::ACTIVE
                && empty($data['is_optional'])
            ) {
                continue 2;
            }

            if (empty($data['settings'])) {
                continue;
            }

            foreach ($data['settings'] as $setting_name => $setting_data) {
                $value = $settings->getValue($setting_name, $addon_name);

                if ($value !== $setting_data['value']) {
                    continue 3;
                }
            }
        }

        if (!empty($registration_flow['settings'])) {
            foreach ($registration_flow['settings'] as $setting_name => $setting_data) {
                $value = $settings->getValue($setting_name, '');

                if ($value !== $setting_data['value']) {
                    continue 2;
                }
            }
        }

        return $registration_flow_type;
    }

    return RegistrationFlowTypes::NO_ACCESS;
}


/**
 * Updates vendor_data_premoderation add-on settings
 *
 * @param array<string, string> $change_settings Array of changed add-on settings
 *
 * @return void
 */
function fn_setup_wizard_update_vendor_data_premoderation(array $change_settings)
{
    $schema = fn_get_schema('setup_wizard', 'vendor_data_premoderation');

    $settings = Settings::instance();

    if (!empty($change_settings)) {
        fn_setup_wizard_install_addon('vendor_data_premoderation');
    }

    foreach ($schema['settings'] as $setting_name => $setting) {
        if (
            isset($change_settings[$setting_name])
            && $change_settings[$setting_name] === $setting['value']
        ) {
            $settings->updateValue($setting_name, $setting['value'], 'vendor_data_premoderation', true);
            Registry::set('addons.vendor_data_premoderation.' . $setting_name, $setting['value']);
        } else {
            $settings->updateValue($setting_name, $setting['default_value'], 'vendor_data_premoderation', true);
            Registry::set('addons.vendor_data_premoderation.' . $setting_name, $setting['default_value']);
        }
    }
}

/**
 * Gets setting value for settings item
 *
 * @param array<string, string|bool> $setting_item Setting item
 *
 * @return array<string, string|bool|array>|bool
 */
function fn_setup_wizard_get_setting_data(array $setting_item)
{
    $settings = Settings::instance();
    $section = !empty($setting_item['section'])
        ? (string) $setting_item['section']
        : '';
    $id = $settings->getId((string) $setting_item['name'], $section);

    if ($id === false) {
        return false;
    }

    /** @var array $setting_data */
    $setting_data = $settings->getData((int) $id);

    if (!empty($setting_data)) {
        $setting_data['description'] = __((string) $setting_item['description']);
        unset($setting_data['tooltip']);
    }

    return $setting_data;
}

/**
 * Builds Content Security Policy header
 *
 * @param array<string, array<string, array<string>>|bool> $directives Directives
 *
 * @return string
 */
function fn_build_content_security_policy_header($directives)
{
    $headers = [];

    foreach ($directives as $directive => $policies) {
        if (empty($policies)) {
            continue;
        }

        if (isset($policies['none']) && $policies['none']) {
            $headers[$directive] = "'none'";
            continue;
        }

        if ($directive === 'sandbox' && $policies === true) {
            $headers[$directive] = '';
            continue;
        }

        if ($directive === 'plugin-types') {
            // MIME types
            $headers[$directive] = implode(' ', $policies['allow']);
            continue;
        }

        $parts = [];

        if (!empty($policies['self'])) {
            $parts[] = "'self'";
        }

        if (!empty($policies['allow'])) {
            $urls = [];
            foreach ($policies['allow'] as $url) {
                $url = filter_var($url, FILTER_SANITIZE_URL);
                if ($directive === 'sandbox') {
                    $urls[] = $url;
                    continue;
                }

                if (strpos($url, '://') === false) {
                    $urls[] = 'https://' . $url;
                    $urls[] = 'http://' . $url;
                    continue;
                }

                $urls[] = $url;
            }
            array_push($parts, ...array_unique($urls));
        }

        if (!empty($policies['hashes'])) {
            /** @var array<array-key, array<string, string>> $hashes */
            $hashes = $policies['hashes'];

            /**
             * @var string $algo sha256, sha384 or sha512
             * @var string $hash_val hashed value
             */
            foreach ($hashes as $algo => $hash_val) {
                $parts[] = sprintf(
                    "'%s-%s'",
                    preg_replace('/[^A-Za-z0-9]/', '', $algo),
                    preg_replace('/[^A-Za-z0-9\+\/=]/', '', $hash_val)
                );
            }
        }

        if (!empty($policies['nonces'])) {
            /** @var array<array-key, string> $nonces */
            $nonces = $policies['nonces'];

            /** @var string $nonce */
            foreach ($nonces as $nonce) {
                $parts[] = sprintf(
                    "'nonce-%s'",
                    preg_replace('/[^A-Za-z0-9\+\/=]/', '', $nonce)
                );
            }
        }

        if (!empty($policies['types'])) {
            /** @var array<array-key, string> $types */
            $types = $policies['types'];

            /** @var string $type */
            foreach ($types as $type) {
                $parts[] = $type;
            }
        }

        if (!empty($policies['unsafe-inline'])) {
            $parts[] = "'unsafe-inline'";
        }

        if (!empty($policies['unsafe-eval'])) {
            $parts[] = "'unsafe-eval'";
        }

        if (!empty($policies['blob'])) {
            $parts[] = 'blob:';
        }

        if (!empty($policies['data'])) {
            $parts[] = 'data:';
        }

        if (!empty($policies['mediastream'])) {
            $parts[] = 'mediastream:';
        }

        if (!empty($policies['filesystem'])) {
            $parts[] = 'filesystem:';
        }

        if (!empty($policies['strict-dynamic'])) {
            $parts[] = "'strict-dynamic'";
        }

        if (!empty($policies['unsafe-hashed-attributes'])) {
            $parts[] = "'unsafe-hashed-attributes'";
        }

        if (empty($parts)) {
            continue;
        }

        $headers[$directive] = implode(' ', $parts);
    }


    $header = '';
    foreach ($headers as $directive => $value) {
        $format = $value ? '%s %s' : '%s';
        $header .= sprintf("$format; ", $directive, $value);
    }

    if ($header) {
        return 'Content-Security-Policy: ' . $header;
    }

    return '';
}

/**
 * Computes the difference of multidimensional arrays with additional index check
 *
 * @param array $array1
 * @param array $array2
 *
 * @return array
 */
function fn_array_diff_assoc_recursive(array $array1, array $array2)
{
    $difference = [];
    foreach ($array1 as $key => $value) {
        if (is_array($value)) {
            if (!isset($array2[$key]) || !is_array($array2[$key])) {
                $difference[$key] = $value;
            } else {
                $new_diff = fn_array_diff_assoc_recursive($value, $array2[$key]);
                if (!empty($new_diff))
                    $difference[$key] = $new_diff;
            }
        } else if (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
            $difference[$key] = $value;
        }
    }
    return $difference;
}

/**
 * Gets full hierarchy of add-on menu item parents.
 *
 * @param array<string, array<string>> $menu_items Add-on menu items
 *
 * @psalm-param array<string, array{
 *   id: string,
 *   parent: array{
 *     id: string,
 *     parent: array{
 *       id: string,
 *       parent: array{
 *         id: string,
 *         parent: null,
 *         title: null|string
 *       }|null,
 *       title: null|string
 *     }|null,
 *     title: null|string
 *   }|null,
 *   title: null|string
 * }> $menu_items
 *
 * @return array<string, array<string>>
 *
 * @psalm-return array<string, array{
 *   id: string,
 *   parents: array<array{
 *     id: string,
 *     parent: array{
 *       id: string,
 *       parent: array{
 *         id: string,
 *         parent: null,
 *         title: null|string
 *       }|null,
 *       title: null|string
 *     }|null,
 *     title: null|string
 *   }>,
 *   title: null|string
 * }>
 *
 * @see \fn_get_menu_schema_for_addon
 */
function fn_attach_addon_menu_item_parents(array $menu_items)
{
    foreach ($menu_items as &$item) {
        $item['parents'] = [];
        $parent = $item['parent'];
        while ($parent) {
            $item['parents'][] = $parent;
            $parent = $parent['parent'];
        }
        $item['parents'] = array_reverse(array_filter($item['parents']));
        unset($item['parent']);
    }
    unset($item);

    /**
     * @psalm-var array<string, array{
     *   id: string,
     *   parents: array<array{
     *     id: string,
     *     parent: array{
     *       id: string,
     *       parent: array{
     *         id: string,
     *         parent: null,
     *         title: null|string
     *       }|null,
     *       title: null|string
     *     }|null,
     *     title: null|string
     *   }>,
     *   title: null|string
     * }> $menu_items
     */
    return $menu_items;
}

/**
 *
 * The "settings_update_value_by_id_post" hook handler.
 *
 * Actions performed:
 * - Adds a storefront to the language settings, when changing the default language for it, if needs.
 *
 * @param \Tygh\Settings $instance          Settings instance
 * @param string         $object_id         Setting object ID
 * @param string|array   $value             New value that was passed to function
 * @param int            $company_id        Company ID
 * @param bool           $execute_functions Whether to execute action functions
 * @param array          $data              Data to be inserted/updated into settings objects table
 * @param array          $old_data          Previously existed data (if any) of settings object at settings objects table
 * @param string         $table             Table to save setting object value ("settings_objects" or "settings_vendor_values")
 * @param int            $storefront_id     Storefront identifier
 *
 * @return void
 */
function fn_core_settings_update_value_by_id_post($instance, $object_id, $value, $company_id, $execute_functions, $data, $old_data, $table, $storefront_id)
{
    if ($storefront_id) {
        return;
    }

    if ($old_data['name'] === 'frontend_default_language') {
        $lang_data = Languages::get(['lang_code' => $data['value']]);
        $lang_data = reset($lang_data);

        if (!empty($lang_data['storefront_ids'])) {
            $lang_id = $lang_data['lang_id'];
            $storefront_ids = explode(',', $lang_data['storefront_ids']);

            $repository = StorefrontProvider::getRepository();

            [$storefronts,] = $repository->find(['get_total' => false]);

            /** @var \Tygh\Storefront\Storefront $storefront */
            foreach ($storefronts as $storefront) {
                if (in_array((string) $storefront->storefront_id, $storefront_ids)) {
                    continue;
                }

                $repository->save($storefront->addLanguageIds($lang_id));
            }
        }
    }

    if ($old_data['name'] !== 'frontend_default_currency') {
        return;
    }
    $currency_data = fn_get_currencies_list(['currency_code' => $data['value']]);
    $currency_data = reset($currency_data);

    if (empty($currency_data['storefront_ids'])) {
        return;
    }

    $currency_id = $currency_data['currency_id'];
    $storefront_ids = explode(',', $currency_data['storefront_ids']);

    $repository = StorefrontProvider::getRepository();

    [$storefronts,] = $repository->find(['get_total' => false]);

    /** @var \Tygh\Storefront\Storefront $storefront */
    foreach ($storefronts as $storefront) {
        if (in_array((string) $storefront->storefront_id, $storefront_ids)) {
            continue;
        }

        $repository->save($storefront->addCurrencyIds($currency_id));
    }
}

/**
 * Gets vendor search conditions.
 *
 * @param $with_default_email_field bool Adds default e-mail field
 *
 * @return array<array-key, \Tygh\Notifications\Receivers\SearchCondition>
 */
function fn_get_default_vendor_notification_search_conditions($with_default_email_field = false)
{
    $search_conditions = [
        new SearchCondition(ReceiverSearchMethods::VENDOR_OWNER, ReceiverSearchMethods::VENDOR_OWNER),
    ];

    if (!$with_default_email_field) {
        return $search_conditions;
    }

    $default_email_field = fn_get_profile_fields('', [], CART_LANGUAGE, [
        'include_types' => (array) ProfileFieldTypes::EMAIL,
        'profile_type' => ProfileTypes::CODE_SELLER,
        'get_contact_email_field' => false,
        'is_default' => true,
    ]);

    if ($default_email_field) {
        $default_email_field = reset($default_email_field);
        $default_email_field = reset($default_email_field);
        $search_conditions[] = new SearchCondition(
            ReceiverSearchMethods::VENDOR_EMAIL, $default_email_field['field_id']
        );
    }

    return $search_conditions;
}

/**
 * Install payment and set it into hidden status
 *
 * @param string $processor_script Processor script name
 *
 * @return void
 */
function fn_setup_wizard_install_payment($processor_script)
{
    $processor_data = db_get_row(
        'SELECT processor_id, processor FROM ?:payment_processors WHERE processor_script = ?s',
        $processor_script
    );

    if (empty($processor_data)) {
        return;
    }

    if (!fn_get_payments(['processor_script' => $processor_script])) {
        fn_update_payment([
            'payment' => $processor_data['processor'],
            'processor_id' => $processor_data['processor_id'],
            'company_id' => 0,
            'status' => ObjectStatuses::DISABLED,
        ], 0);
    }
}

/**
 * Generates a page title from bread crumbs
 *
 * @param array<int, array<string, string>>|null $breadcrumbs        Breadcrumbs
 * @param string                                 $language_direction Language direction: ltr or rtl
 * @param string                                 $suffix             Suffix
 * @param bool                                   $skip_page_title    Flag that resets the addition of a suffix
 *
 * @return string
 */
function fn_generate_page_title(?array $breadcrumbs, $language_direction = 'ltr', $suffix = '', $skip_page_title = false)
{
    $title = '';

    if ($breadcrumbs) {
        array_shift($breadcrumbs);

        if ($language_direction === 'rtl') {
            $breadcrumbs = array_reverse($breadcrumbs);
        }

        $breadcrumbs = array_column($breadcrumbs, 'title');
        $breadcrumbs = array_map('strip_tags', $breadcrumbs);

        $title = implode(' :: ', $breadcrumbs);
    }

    if ($suffix && !$skip_page_title) {
        if ($title) {
            $title .= ' - ';
        }
        $title .= $suffix;
    }

    return $title;
}


/**
 * Fetches custom blocks
 *
 * @param array  $params    Parameters for SELECT query
 * @param string $lang_code Two-letters language code
 *
 * @return array
 */
function fn_get_custom_blocks(array $params, $lang_code = DESCR_SL)
{
    $fields = [
        'db.block_id',
        'db.status',
        'db.section',
        'db.area',
        'db.type',
        'db.position',
        'dbc.content',
        'dbc.lang_code'
    ];
    $joins = [
        db_quote(
            ' LEFT JOIN ?:custom_blocks_content AS dbc ON db.block_id = dbc.block_id AND dbc.lang_code = ?s',
            $lang_code
        )
    ];
    $condition = '';

    if (!empty($params['status'])) {
        $condition .= db_quote(' AND db.status = ?s', $params['status']);
    }

    if (!empty($params['section'])) {
        $condition .= db_quote(' AND db.section = ?s', $params['section']);
    }

    if (!empty($params['type'])) {
        $condition .= db_quote(' AND db.type = ?s', $params['type']);
    }

    if (!empty($params['area'])) {
        $condition .= db_quote(' AND db.area = ?s', $params['area']);
    }

    if (fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id')) {
        if (!empty($params['filter_dismissed'])) {
            $joins[] = db_quote(
                ' LEFT JOIN ?:custom_blocks_vendor_dismiss as vd ON db.block_id = vd.block_id AND vd.company_id = ?i',
                Registry::get('runtime.company_id')
            );
            $condition .= db_quote(' AND vd.company_id IS NULL');
        } else {
            $fields[] = db_quote('(vd.company_id IS NOT NULL) AS is_dismissed');
            $joins[] = db_quote(
                ' LEFT JOIN ?:custom_blocks_vendor_dismiss as vd ON db.block_id = vd.block_id AND vd.company_id = ?i',
                Registry::get('runtime.company_id')
            );
        }
    }

    return db_get_array(
        'SELECT ?p FROM ?:custom_blocks AS db'
        . '?p WHERE 1=1 ?p ORDER BY db.position',
        implode(',', $fields),
        implode(' ', $joins),
        $condition
    );
}

/**
 * Gets custom block data
 *
 * @param int    $block_id  Block identificator
 * @param string $lang_code Two-letters language code
 *
 * @return array
 */
function fn_get_custom_block_data($block_id, $lang_code = DESCR_SL)
{
    $fields = [
        'db.block_id',
        'db.status',
        'db.section',
        'db.area',
        'db.type',
        'db.position',
        'dbc.content',
        'dbc.lang_code'
    ];

    return db_get_row(
        'SELECT ?p FROM ?:custom_blocks AS db'
        . ' LEFT JOIN ?:custom_blocks_content AS dbc ON db.block_id = dbc.block_id AND dbc.lang_code = ?s'
        . ' WHERE db.block_id = ?s',
        implode(',', $fields),
        $lang_code,
        $block_id
    );
}

/**
 * Update custom block
 *
 * @param array  $data      Block data
 * @param string $lang_code Two-letters language code
 *
 * @return void
 */
function fn_update_custom_block(array $data, $lang_code = DESCR_SL)
{
    // Create block
    if (empty($data['block_id'])) {
        $block_id = db_query('INSERT INTO ?:custom_blocks ?e', $data);

        $_data = $data;
        $_data['block_id'] = $block_id;

        foreach (array_keys(Languages::getAll()) as $_data['lang_code']) {
            db_query('INSERT INTO ?:custom_blocks_content ?e', $_data);
        }

        // Update block
    } else {
        db_query('UPDATE ?:custom_blocks SET ?u WHERE block_id = ?i', $data, $data['block_id']);
        db_query(
            'UPDATE ?:custom_blocks_content SET ?u WHERE block_id = ?i AND lang_code = ?s',
            $data,
            $data['block_id'],
            $lang_code
        );
    }
}

/**
 * Delete custom block(s)
 *
 * @param array|int $block_ids Block identificators
 *
 * @return void
 */
function fn_delete_custom_block($block_ids)
{
    if (empty($block_ids)) {
        return;
    }

    db_query('DELETE FROM ?:custom_blocks WHERE block_id IN (?n)', $block_ids);
    db_query('DELETE FROM ?:custom_blocks_content WHERE block_id IN (?n)', $block_ids);

    if (!fn_allowed_for('MULTIVENDOR')) {
        return;
    }
    db_query('DELETE FROM ?:custom_blocks_vendor_dismiss WHERE block_id IN (?n)', $block_ids);
}

/**
 * Delete custom block(s)
 *
 * @param int $block_id Block identificator
 *
 * @return void
 */
function fn_dismiss_custom_block($block_id)
{
    if (!fn_allowed_for('MULTIVENDOR') || empty($block_id) || !Registry::get('runtime.company_id')) {
        return;
    }

    $data = [
        'block_id'   => $block_id,
        'company_id' => Registry::get('runtime.company_id')
    ];

    db_query('REPLACE INTO ?:custom_blocks_vendor_dismiss ?e', $data);
}

/**
 * Checks customizations permissions for vendor.
 *
 * @return bool
 */
function fn_vendor_customization_permissions()
{
    $session = Tygh::$app['session'];

    if (Registry::ifGet('config.demo_mode', false)) {
        return true;
    }

    if (
        empty($session['auth']['act_as_user']) || empty($session['auth']['act_as_area'])
        || !UserTypes::isVendor($session['auth']['user_type']) || !SiteArea::isVendor($session['auth']['act_as_area'])
    ) {
        return false;
    }

    return true;
}

/**
 * Get dashboard To-do list items
 *
 * @param array $auth Authorization data
 *
 * @return array
 */
function fn_get_todo_list_items(array $auth)
{
    $data = [];
    $todo_schema = fn_get_schema('dashboard', 'todo');

    foreach ($todo_schema as $item_name => $item_data) {
        if ($item_data['area'] !== $auth['user_type']) {
            continue;
        }

        if (isset($item_data['content_callback']) && is_callable($item_data['content_callback'])) {
            $result = call_user_func($item_data['content_callback'], $auth);
            unset($item_data['content_callback']);

            if (empty($result['text']) && empty($result['action_text'])) {
                continue;
            }

            if (!empty($result['text'])) {
                $item_data['text'] = $result['text'];
            }

            if (!empty($result['action_text'])) {
                $item_data['action_text'] = $result['action_text'];
            }

            if (!empty($result['action_url'])) {
                $item_data['action_url'] = $result['action_url'];
            }

            if (!empty($result['is_ajax_action'])) {
                $item_data['is_ajax_action'] = $result['is_ajax_action'];
            }
        }

        $data[$item_name] = $item_data;
    }

    return $data;
}

/**
 * Returns company ID of the styles owner.
 * Result of this function is often used as a boolean in various checks.
 *
 * @return int|false Company ID of the vendor if vendors can manage styles, false otherwise.
 */
function fn_get_menu_item_styles_owner()
{
    if (
        fn_allowed_for('MULTIVENDOR')
        && ($vendor_id = Registry::ifGet('runtime.vendor_id', Registry::get('runtime.company_id')))
        && Registry::get('settings.Vendors.can_edit_styles') === YesNo::YES
    ) {
        return $vendor_id;
    }

    return false;
}
