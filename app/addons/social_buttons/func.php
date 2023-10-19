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

use Tygh\Embedded;
use Tygh\Enum\YesNo;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_get_sb_provider_settings($params)
{
    $view = Tygh::$app['view'];
    $addon_settings = Registry::get('settings.social_buttons');

    $provider_settings = [];
    if (!empty($addon_settings)) {
        foreach ($addon_settings as $provider_name => $provider_data) {
            if (empty($provider_data[$provider_name . '_enable']) || !YesNo::toBool($provider_data[$provider_name . '_enable'])) {
                continue;
            }

            $func_name = 'fn_' . $provider_name . '_prepare_settings';

            if (is_callable($func_name)) {
                $provider_settings[$provider_name]['data'] = call_user_func($func_name, $provider_data, $params);
            }

            $provider_settings[$provider_name]['template'] = $provider_name . '.tpl';

            $provider_settings[$provider_name]['meta_template'] = $view->templateExists('addons/social_buttons/meta_templates/' . $provider_name . '.tpl')
                ? $provider_name . '.tpl'
                : null;
        }
    }

    return $provider_settings;
}

function fn_pinterest_prepare_settings($pinterest_settings, $params)
{
    if (empty($pinterest_settings['pinterest_display_on'][$params['object']]) || !YesNo::toBool($pinterest_settings['pinterest_display_on'][$params['object']])) {
        return '';
    }

    $pinterest_fields = [
        'data-pin-do'     => 'buttonPin',
        'data-pin-shape'  => $pinterest_settings['pinterest_shape'],
        'data-pin-height' => $pinterest_settings['pinterest_size'],
    ];

    $pinterest_params = [
        'url'         => urlencode(fn_sb_get_url()),
        'media'       => urlencode(fn_get_sb_image_url($params)),
        'description' => rawurlencode(fn_truncate_chars(htmlspecialchars(fn_get_sb_description($params), ENT_COMPAT), 200)),
        'params'      => '',
    ];

    foreach ($pinterest_fields as $field_name => $value) {
        if (!empty($value)) {
            $pinterest_params['params'] .= $field_name . '="' . $value . '" ';
        }
    }

    return $pinterest_params;
}

function fn_twitter_prepare_settings($twitter_settings, $params)
{
    if (empty($twitter_settings['twitter_display_on'][$params['object']]) || !YesNo::toBool($twitter_settings['twitter_display_on'][$params['object']])) {
        return '';
    }

    $default_twitter_fields = [
        'data-lang'  => $params['lang_code'],
        'data-size'  => $twitter_settings['twitter_size'],
        'data-via'   => $twitter_settings['twitter_via'],
        'data-count' => $twitter_settings['twitter_display_count'],
        'data-url'   => fn_sb_get_url()
    ];

    $twitter_params = '';
    foreach ($default_twitter_fields as $field_name => $value) {
        if (!empty($value)) {
            $twitter_params .= $field_name . '="' . $value . '" ';
        }
    }

    return $twitter_params;
}

function fn_facebook_prepare_settings($facebook_settings, $params)
{
    if (empty($facebook_settings['facebook_display_on'][$params['object']]) || !YesNo::toBool($facebook_settings['facebook_display_on'][$params['object']])) {
        return '';
    }

    $facebook_fields = [
        'data-lang'        => $params['lang_code'],
        'data-layout'      => $facebook_settings['facebook_layout'],
        'data-href'        => !empty($facebook_settings['facebook_href']) ? $facebook_settings['facebook_href'] : fn_sb_get_url(),
        'data-send'        => YesNo::toBool($facebook_settings['facebook_send']) ? 'true' : 'false',
        'data-show-faces'  => YesNo::toBool($facebook_settings['facebook_show_faces']) ? 'true' : 'false',
        'data-action'      => $facebook_settings['facebook_action'],
        'data-font'        => $facebook_settings['facebook_action_font'],
        'data-colorscheme' => $facebook_settings['facebook_colorscheme'],
        'data-width'       => $facebook_settings['facebook_width'],
    ];

    $facebook_params = '';
    foreach ($facebook_fields as $field_name => $value) {
        if (!empty($value)) {
            $facebook_params .= $field_name . '="' . $value . '" ';
        }
    }

    return $facebook_params;
}

function fn_vkontakte_prepare_settings($vkontakte_settings, $params)
{
    if (empty($vkontakte_settings['vkontakte_display_on'][$params['object']]) || !YesNo::toBool($vkontakte_settings['vkontakte_display_on'][$params['object']])) {
        return '';
    }

    $vkontakte_fields = [
        'type'            => $vkontakte_settings['vkontakte_button_style'],
        'width'           => $vkontakte_settings['vkontakte_width'],
        'height'          => $vkontakte_settings['vkontakte_height'],
        'pageImage'       => fn_get_sb_image_url($params),
        'pageTitle'       => fn_get_vkontakte_title($params),
        'pageDescription' => fn_js_escape(fn_truncate_chars(fn_get_sb_description($params), 200)),
        'pageUrl'         => fn_sb_get_url(),
        'verb'            => $vkontakte_settings['vkontakte_buttons_name'] === 'like' ? 0 : 1,
    ];

    $default_values = [
        'pageTitle' => fn_sb_format_page_title(),
    ];
    //By default VK caches all data. We need to recalculate hash in order for VK to change the data in their cache
    $page_id = md5(implode(',', $vkontakte_fields));

    $vk_settings = '{';
    foreach ($vkontakte_fields as $field_name => $value) {
        if (empty($value) && !empty($default_values[$field_name])) {
            $value = $default_values[$field_name];
        }
        if (!empty($value)) {
            $vk_settings .= $field_name . ": '" . $value . "', ";
        }
    }
    $vk_settings .= "}, '" . $page_id . "'";

    return $vk_settings;
}

function fn_yandex_prepare_settings($yandex_settings, $params)
{
    if (empty($yandex_settings['yandex_display_on'][$params['object']]) || !YesNo::toBool($yandex_settings['yandex_display_on'][$params['object']])) {
        return '';
    }

    return $yandex_settings['yandex_share_code'];
}

function fn_get_sb_description($params)
{
    $description = '';

    if ($params['object'] === 'products') {
        $product = Tygh::$app['view']->getTemplateVars('product');
        $description = htmlspecialchars(strip_tags($product['full_description']), ENT_COMPAT);
    } elseif ($params['object'] === 'pages') {
        $page = Tygh::$app['view']->getTemplateVars('page');
        $description = htmlspecialchars(strip_tags($page['description']), ENT_COMPAT);
    }

    return $description;
}

function fn_get_vkontakte_title($params)
{
    $title = '';

    if ($params['object'] === 'products') {
        $product = Tygh::$app['view']->getTemplateVars('product');
        $title = htmlspecialchars(strip_tags($product['product']), ENT_COMPAT);
    } elseif ($params['object'] === 'pages') {
        $page = Tygh::$app['view']->getTemplateVars('page');
        $title = htmlspecialchars(strip_tags($page['page']), ENT_COMPAT);
    }

    return $title;
}

function fn_get_sb_image_url($params)
{
    $image_url = '';

    if ($params['object'] === 'products') {
        $product = Tygh::$app['view']->getTemplateVars('product');
        $image_url = isset($product['main_pair']['detailed']['image_path']) ? $product['main_pair']['detailed']['image_path'] : Registry::get('config.current_location') . '/images/no_image.png';

    } elseif ($params['object'] === 'pages') {
        $logos = fn_get_logos();
        if (fn_allowed_for('ULTIMATE')) {
            $company_id = Registry::ifGet('runtime.company_id', fn_get_default_company_id());
            $logos = fn_get_logos($company_id);
        }
        if (!empty($logos['theme']['image']['image_path'])) {
            $image_url = $logos['theme']['image']['image_path'];
        }
    }

    return $image_url;
}

function fn_get_sb_providers_meta_data($params)
{
    $addon_settings = Registry::get('settings.social_buttons');
    $providers_meta_data = [];

    if (fn_allowed_for('ULTIMATE')) {
        $company_id = Registry::ifGet('runtime.company_id', fn_get_default_company_id());
        $site_name = fn_get_company_name($company_id);
    }

    if ($params['object'] === 'products') {
        $product = Tygh::$app['view']->getTemplateVars('product');
        $providers_meta_data['all'] = [
            'title'        => fn_sb_format_page_title(),
            'url'          => fn_url('products.view?product_id=' . $params['object_id']),
            'image'        => !empty($product['main_pair']['detailed']['image_path']) ? $product['main_pair']['detailed']['image_path'] : '',
            'image:width'  => !empty($product['main_pair']['detailed']['image_x']) ? $product['main_pair']['detailed']['image_x'] : '',
            'image:height' => !empty($product['main_pair']['detailed']['image_y']) ? $product['main_pair']['detailed']['image_y'] : '',
            'site_name'    => !empty($site_name) ? $site_name : Registry::get('settings.Company.company_name'),
            'type'         => 'product',
        ];

    } elseif ($params['object'] === 'pages') {
        $page = Tygh::$app['view']->getTemplateVars('page');
        $logos = fn_get_logos();

        if (fn_allowed_for('ULTIMATE')) {
            $logos = fn_get_logos($company_id);
        }

        $providers_meta_data['all'] = [
            'title'        => $page['page'],
            'url'          => !empty($page['link']) ? $page['link'] : fn_url('pages.view?page_id=' . $params['object_id']),
            'image'        => !empty($logos['theme']['image']['image_path']) ? $logos['theme']['image']['image_path'] : '',
            'image:width'  => !empty($logos['theme']['image']['image_x']) ? $logos['theme']['image']['image_x'] : '',
            'image:height' => !empty($logos['theme']['image']['image_y']) ? $logos['theme']['image']['image_y'] : '',
            'site_name'    => !empty($site_name) ? $site_name : Registry::get('settings.Company.company_name'),
            'type'         => 'article',
        ];
    }

    if (!empty($addon_settings)) {
        foreach ($addon_settings as $provider_name => $provider_data) {
            $func_name = 'fn_' . $provider_name . '_prepare_meta_data';
            if (is_callable($func_name)) {
                $providers_meta_data[$provider_name] = call_user_func($func_name, $provider_data, $params);
            }

            if (!empty($providers_meta_data[$provider_name]['type'])) {
                $providers_meta_data['all']['type'] = $providers_meta_data[$provider_name]['type'];
            }
        }
    }

    return $providers_meta_data;
}

function fn_facebook_prepare_meta_data($provider_data, $params)
{
    $addon_settings = Registry::get('settings.social_buttons');
    $return = [];

    if ($params['object'] === 'products') {
        $product = Tygh::$app['view']->getTemplateVars('product');
        $return = [
            'type'   => !empty($product['facebook_obj_type']) ? $product['facebook_obj_type'] : '',
            'app_id' => !empty($addon_settings['facebook']['facebook_app_id']) ? $addon_settings['facebook']['facebook_app_id'] : '',
        ];
    } elseif ($params['object'] === 'pages') {
        $page = Tygh::$app['view']->getTemplateVars('page');

        $return = [
            'type'   => !empty($page['facebook_obj_type']) ? $page['facebook_obj_type'] : '',
            'app_id' => !empty($addon_settings['facebook']['facebook_app_id']) ? $addon_settings['facebook']['facebook_app_id'] : '',
        ];
    }

    return $return;
}

function fn_sb_format_page_title()
{
    $page_title = Tygh::$app['view']->getTemplateVars('page_title');
    if (empty($page_title)) {
        $breadcrumbs = Tygh::$app['view']->getTemplateVars('breadcrumbs');
        $breadcrumb_titles = array_column($breadcrumbs, 'title');
        if (fn_is_rtl_language()) {
            $page_title = implode(' :: ', array_reverse($breadcrumb_titles));
        } else {
            $page_title = implode(' :: ', $breadcrumb_titles);
        }
    }

    return $page_title;
}

function fn_social_buttons_before_dispatch()
{
    //For the stores works as widget. We need to redirect the customer to thee site where the Like button was clicked.
    if (isset($_REQUEST['_escaped_fragment_'])) {
        fn_redirect($_REQUEST['_escaped_fragment_'], true, true);
    }
}

/**
 * Gets current URL taking into account embedded mode
 * @return string current URL
 */
function fn_sb_get_url()
{
    $url = fn_url(Registry::get('config.current_url'));

    if (Embedded::isEnabled()) {
        // If SEO addon is enabled, the relative url will contain the current path,
        // which is not what expected and therefore removed here
        $_url = fn_url(Registry::get('config.current_url'), AREA, 'rel');
        $_path = Registry::get('config.current_path');
        if (!empty($_path) && strpos($_url, $_path) === 0) {
            $_url = substr($_url, strlen($_path));
        }
        $url = Embedded::getUrl() . '#!/' . $_url;
    }

    return $url;
}

/**
 * @param array<string, array<string|array<string>>> $provider_settings Provider settings
 *
 * @return bool
 */
function fn_sb_display_block(array $provider_settings = [])
{
    $result = false;
    $settings = Registry::get('addons.social_buttons');

    if (!empty($settings)) {
        foreach ($settings as $setting_name => $setting_value) {
            $pos = strpos($setting_name, '_display_on');
            if ($pos && is_array($setting_value)) {
                $provider = substr($setting_name, 0, $pos);
                foreach ($setting_value as $value) {
                    if (YesNo::toBool($value) && !empty($provider_settings[$provider]['data'])) {
                        $result = true;
                        break;
                    }
                }
            }
        }
    }

    return $result;
}

/**
 * The "settings_update_value_by_id_pre" hook handler.
 *
 * Actions performed:
 * - .
 *
 * @param \Tygh\Settings                   $instance          Settings instance
 * @param string                           $object_id         Setting object ID
 * @param string|array<string, string|int> $value             New value that was passed to function
 * @param int                              $company_id        Company ID
 * @param bool                             $execute_functions Whether to execute action functions
 * @param array<string, string|int>        $data              Data to be inserted/updated into settings objects table
 * @param array<string, string|int>        $old_data          Previously existed data (if any) of settings object at
 *                                         settings objects table
 * @param string                           $table             Table to save setting object value ("settings_objects" or
 *                                                            "settings_vendor_values")
 * @param int                              $storefront_id     Storefront identifier
 *
 * @return void
 */
function fn_social_buttons_settings_update_value_by_id_pre(
    Tygh\Settings $instance,
    $object_id,
    $value,
    $company_id,
    $execute_functions,
    array &$data,
    array $old_data,
    $table,
    $storefront_id
) {
    if ($old_data['name'] !== 'yandex_share_code' || !is_string($value)) {
        return;
    }

    $data['value'] = strip_tags($value, '<div>');
}
