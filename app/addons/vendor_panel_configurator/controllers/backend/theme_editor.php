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

use Tygh\Addons\VendorPanelConfigurator\Enum\ColorSchemas;
use Tygh\Addons\VendorPanelConfigurator\ServiceProvider;
use Tygh\Enum\SiteArea;
use Tygh\Less;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if (AREA === SiteArea::STOREFRONT) {
    exit;
}

if (!Registry::get('runtime.customization_mode.theme_editor') && !Registry::get('runtime.customization_mode.design')) {
    fn_set_notification('E', __('error'), __('access_denied'));

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'save') {
        fn_trusted_vars('style.custom_css');

        if (!empty($_REQUEST['style']['data'])) {
            $style = $_REQUEST['style']['data'];

            if (!empty($_REQUEST['style_id'])) {
                $style['color_schema'] = fn_vendor_panel_configurator_check_color_differences($_REQUEST['style_id'], $style);
            }

            ServiceProvider::getSettingsService()->updateVendorPanelStyleConfiguration($style);
            fn_set_notification('N', __('notice'), __('text_changes_saved'));
        }
        fn_clear_cache('assets', 'design/');
    }

    return [CONTROLLER_STATUS_OK, 'theme_editor.view'];
}

if ($mode === 'view') {
    fn_vendor_panel_configurator_panel_editor();
    exit;
} elseif ($mode === 'get_css') {
    $css_filename = $content = '';
    $old_css_filename = !empty($_REQUEST['css_filename']) ? fn_basename($_REQUEST['css_filename']) : '';
    $cached_css_path = sprintf('%s/%s', rtrim(fn_get_cache_path(false), '/'), rtrim('assets/design/backend/css', '/'));
    $old_cached_css_full_path = sprintf('%s/%s', $cached_css_path, $old_css_filename);
    if (file_exists($old_cached_css_full_path)) {
        $content = fn_get_contents($old_cached_css_full_path);
    }

    fn_clear_cache('assets', 'design/');

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $settings_service = ServiceProvider::getSettingsService();

    if (!empty($_REQUEST['style']['data'])) {
        $style = $_REQUEST['style']['data'];
    } elseif (!empty($_REQUEST['style_id'])) {
        $all_color_presets = ServiceProvider::getSettingsService()->getColorSchemas();
        $style = $all_color_presets[$_REQUEST['style_id']];
    } else {
        $style = $settings_service->getVendorPanelStyle();
    }

    $less = new Less();
    /** @var array<array-key, string> $style */
    $main_color_saturation = $less->getColorSaturation(['raw_color', $style['element_color']]);
    $view->assign('is_gray_main_color', $main_color_saturation < 10);

    $view->assign('dashboard_element_color', $style['element_color']);
    $view->assign('dashboard_sidebar_color', $style['sidebar_color']);
    $view->fetch('common/styles.tpl');

    $files = scandir($cached_css_path);
    foreach ($files as $v) {
        if ($v !== '.' && $v !== '..') {
            $css_filename = $v;
        }
    }

    $cached_css_full_path = sprintf('%s/%s', $cached_css_path, $css_filename);
    if (file_exists($cached_css_full_path)) {
        $content = fn_get_contents($cached_css_full_path);
    }

    $content = fn_vendor_panel_configurator_parse_urls($content);

    header('content-type: text/css');
    fn_echo($content);
    exit;
}

/**
 * Method for initialize vendor panel editor.
 *
 * @return void
 *
 * @psalm-suppress PossiblyUndefinedArrayOffset
 */
function fn_vendor_panel_configurator_panel_editor()
{
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $data = [];
    $settings_service = ServiceProvider::getSettingsService();
    $all_color_presets = $settings_service->getColorSchemas();

    $current_style_id = !empty($_REQUEST['style_id'])
        ? $_REQUEST['style_id']
        : $settings_service->getVendorPanelActualColorSchema();
    $current_style_name = __('vendor_panel_configurator.color_schema.' . $current_style_id);
    $selected_color_preset = $all_color_presets[$current_style_id];

    $props_schema = [
        'backgrounds' => [
            'fields' => [
                'element_color' => [
                    'description' => 'vendor_panel_configurator.theme_editor.element',
                    'properties'  => [
                        'color' => [
                            'description' => 'theme_editor.color',
                            'enable'      => '1',
                            'match'       => 'element_color',
                            'value'       => $selected_color_preset['element_color']
                        ]
                    ]
                ],
                'sidebar_color' => [
                    'description' => 'vendor_panel_configurator.theme_editor.sidebar',
                    'properties'  => [
                        'color' => [
                            'description' => 'theme_editor.background_color',
                            'enable'      => '1',
                            'match'       => 'sidebar_color',
                            'value'       => $selected_color_preset['sidebar_color']
                        ]
                    ]
                ]
            ]
        ]
    ];

    foreach ($props_schema['backgrounds']['fields'] as $field) {
        $data[$field['properties']['color']['match']] = $field['properties']['color']['value'];
    }

    $current_style = [
        'style_id'                  => $current_style_id,
        'preset_id'                 => $current_style_id,
        'name'                      => $current_style_name,
        'data'                      => $data,
        'is_default'                => true,
        'is_removable'              => false,
        'is_can_change_style_name'  => false
    ];

    $view->assign([
        'content_container'       => 'main_column',
        'is_theme_editor_allowed' => true,
        'show_layouts'            => false,
        'show_converted_to_css'   => false,
        'show_reset_button'       => false,
        'show_duplicate_style'    => false,
        'show_image_loading'      => false,
        'te_sections'             => [
            'te_backgrounds' => 'theme_editor.backgrounds'
        ],
        'selected_section'        => 'te_backgrounds',
        'props_schema'            => $props_schema,
        'styles_list'             => fn_vendor_panel_configurator_get_styles_list($all_color_presets),
        'current_style_name'      => $current_style_name,
        'current_style'           => $current_style
    ]);

    /** @psalm-suppress MissingThrowsDocblock */
    $view->display('backend:views/theme_editor/view.tpl');
}

/**
 * Gets styles list for select in dashboard vendor panel.
 *
 * @param array<string, array<string, array{sidebar_color: string, element_color: string}>> $all_color_presets Color preset list
 *
 * @return array<string, array<string, string|bool>>
 */
function fn_vendor_panel_configurator_get_styles_list(array $all_color_presets = [])
{
    $styles_list = [];

    foreach (array_keys($all_color_presets) as $preset_abbr) {
        $styles_list[$preset_abbr] = [
            'style_id'     => $preset_abbr,
            'name'         => __('vendor_panel_configurator.color_schema.' . $preset_abbr),
            'is_removable' => false
        ];
    }

    return $styles_list;
}

/**
 * Checks color differences in the standard and modifiable color preset.
 *
 * @param string                                      $style_id Color preset abbreviation
 * @param array<string, string|array<string, string>> $colors   Color preset data
 *
 * @return string
 */
function fn_vendor_panel_configurator_check_color_differences($style_id, $colors)
{
    if ($style_id === ColorSchemas::CUSTOM) {
        return $style_id;
    }

    $all_color_presets = ServiceProvider::getSettingsService()->getColorSchemas();
    $current_default_preset = $all_color_presets[$style_id];

    foreach ($colors as $color_name => $color) {
        if ($current_default_preset[$color_name] !== $color) {
            return ColorSchemas::CUSTOM;
        }
    }

    return $style_id;
}

/**
 * Parses CSS code to make correct relative URLs in case of CSS files compiled and placed to another directory
 *
 * @param string $content CSS code
 *
 * @return string parsed CSS content
 */
function fn_vendor_panel_configurator_parse_urls($content)
{
    if (preg_match_all("/url\((?![\"']?data\:).*?\)/", $content, $m)) {
        foreach ($m[0] as $match) {
            $url = trim(str_replace('url(', '', $match), "'()\"");
            $url = preg_replace('/(\.\.\/)+(design\/backend\/media\/)*(\.\.\/)+/', '', $url);
            $content = str_replace($match, "url('{$url}')", $content);
        }
    }

    return $content;
}
