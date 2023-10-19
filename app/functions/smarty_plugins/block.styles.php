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
use Tygh\Enum\FontSubset;
use Tygh\Enum\FontType;
use Tygh\Enum\FontWeight;
use Tygh\Enum\SiteArea;
use Tygh\Registry;
use Tygh\Storage;
use Tygh\Themes\Styles;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
function smarty_block_styles($params, $content, &$smarty, &$repeat)
{
    if ($repeat) {
        return '';
    }

    $prepend_prefix = Embedded::isEnabled() ? 'html#tygh_html body#tygh_body .tygh' : '';
    $current_location = Registry::get('config.current_location');

    $styles = [];
    $inline_styles = '';
    $external_styles = [];

    if (preg_match_all('/\<link(.*?href\s?=\s?(?:"|\')([^"]+)(?:"|\'))?[^\>]*\>/is', $content, $m)) {
        foreach ($m[2] as $k => $v) {
            $v = preg_replace('/\?.*?$/', '', $v);
            $media = '';
            if (strpos($m[1][$k], 'media=') !== false && preg_match('/media="(.*?)"/', $m[1][$k], $_m)) {
                $media = $_m[1];
            }

            if (strpos($v, $current_location) === false || strpos($m[1][$k], 'data-ca-external') !== false) {
                // Location is different OR style is skipped for minification
                $external_styles[] = str_replace(' data-ca-external="Y"', '', $m[0][$k]);
            } else {
                $styles[] = [
                    'file' => str_replace($current_location, Registry::get('config.dir.root'), $v),
                    'relative' => str_replace($current_location . '/', '', $v),
                    'media' => $media
                ];
            }
        }
    }

    if (preg_match_all('/\<style.*\>(.*)\<\/style\>/isU', $content, $m)) {
        $inline_styles = implode("\n\n", $m[1]);
    }

    if ($styles || $inline_styles) {
        fn_set_hook('styles_block_files', $styles);

        list($_area) = Tygh::$app['view']->getArea();
        $params['compressed'] = true;
        list($filename, $file_id_in_storage) = fn_merge_styles($styles, $inline_styles, $prepend_prefix, $params, $_area, true);

        $content = '';
        static $preloaded_resources = null;
        if (
            SiteArea::isStorefront($_area)
            && !Registry::get('config.tweaks.disable_resource_preloading')
            && $preloaded_resources === null
        ) {
            $preloaded_resources = Registry::getOrSetCache(
                'preloaded_font_resources',
                [],
                ['storefront', 'lang'],
                static function () use ($file_id_in_storage) {
                    $tmp_css_path = fn_create_temp_file();
                    Storage::instance('assets')->export($file_id_in_storage, $tmp_css_path);
                    $css = file_get_contents($tmp_css_path);
                    fn_rm($tmp_css_path);

                    return fn_get_preload_font_resources(
                        $css,
                        CART_LANGUAGE,
                        Registry::ifGet('config.tweaks.max_fonts_to_preload', 1)
                    );
                }
            );

            /**
             * Executes when creating styles link right before building a set of preload links,
             * allows you to add or remove resources to preload.
             *
             * @param array  $params              Styles block parameters
             * @param string $content             Content to output before preload links
             * @param array  $preloaded_resources Preloaded resources
             */
            fn_set_hook('block_styles_before_build_preload_links', $params, $content, $preloaded_resources);

            if ($preloaded_resources) {
                foreach ($preloaded_resources as $resource) {
                    $content .= '<link' .
                        ' rel="preload"' .
                        ' crossorigin="anonymous"' .
                        ' as="' . $resource['as'] . '"' .
                        ' href="' . $resource['href'] . '"' .
                        ($resource['type'] ? ' type="' . $resource['type'] . '"' : '') .
                        ' />' . PHP_EOL;
                }
            }
        }

        $content .= '<link type="text/css" rel="stylesheet" href="' .
            $filename .
            '?' . fn_get_storage_data('cache_id') .
            '" />';

        if ($external_styles) {
            $content .= PHP_EOL . implode(PHP_EOL, $external_styles);
        }
    }

    return $content;
}

/**
 * Gets fonts to preload on page load.
 *
 * @param string $css                  CSS content
 * @param string $language_code        Current language code
 * @param int    $max_fonts_to_preload Maximum amount of fonts to preload. Too much fonts will only slow down a page
 *
 * @return array<
 *   array{
 *     as: string,
 *     type: string,
 *     href: string,
 *     priority: int,
 *   }
 * >
 *
 * @internal
 */
function fn_get_preload_font_resources($css, $language_code, $max_fonts_to_preload)
{
    $font_resources = [];
    $design_path = fn_get_theme_path('[relative]/');

    preg_match_all(
        '~@font-face\s*{(?P<font_faces>.+?)}~is',
        $css,
        $css_parse_result
    );

    foreach ($css_parse_result['font_faces'] as $font_face_spec) {
        $properties = array_filter(explode(';', $font_face_spec));

        $font_face_properties = fn_block_styles_get_font_face_properties($properties);
        $family = $font_face_properties['font-family'];
        $weight = $font_face_properties['font-weight'];
        $subset = $font_face_properties['unicode-range'];
        $priority = $font_face_properties['--preload-priority'];

        foreach ($font_face_properties['src'] as $sources_string) {
            preg_match_all(
                '~url\(' .
                '["\']*' .
                '(?P<urls>(?:http|/|\.).+?)' .
                '["\']*' .
                '\)~i',
                $sources_string,
                $source_parse_result
            );

            $source_parse_result['urls'] = array_map(
                static function ($url) use ($design_path) {
                    if ($design_path_position = strpos($url, $design_path)) {
                        $url = Registry::get('config.current_location') . substr($url, $design_path_position - 1);
                    }

                    return explode('#', $url)[0];
                },
                $source_parse_result['urls']
            );

            foreach ($source_parse_result['urls'] as $url) {
                list($clean_url) = explode('?', $url);
                $type = FontType::getByExtension(pathinfo($clean_url, PATHINFO_EXTENSION));

                if (!isset($font_resources[$family])) {
                    $font_resources[$family] = [
                        'family'   => $family,
                        'priority' => $priority,
                        'weights'  => [],
                    ];
                }

                if (!isset($font_resources[$family]['weights'][$weight])) {
                    $font_resources[$family]['weights'][$weight] = [
                        'weight'   => $weight,
                        'priority' => $priority,
                        'subsets'  => [],
                    ];
                }

                if (!isset($font_resources[$family]['weights'][$weight]['subsets'][$subset])) {
                    $font_resources[$family]['weights'][$weight]['subsets'][$subset] = [
                        'subset' => $subset,
                        'types'  => [],
                    ];
                }

                $font_resources[$family]['weights'][$weight]['subsets'][$subset]['types'][$type] = [
                    'type' => $type,
                    'as'   => 'font',
                    'href' => $url,
                ];
            }
        }
    }

    $font_resources = array_unique($font_resources, SORT_REGULAR);

    // we can prioritize fonts properly using data from theme style
    $font_resource_priorities = fn_block_styles_get_fonts_from_theme_style(Registry::get('runtime.layout'));
    $font_resources = fn_array_merge($font_resources, $font_resource_priorities);

    $preload_font_resources  = [];
    $font_resources = fn_sort_array_by_key($font_resources, 'priority');
    foreach ($font_resources as $font_resource) {
        if (empty($font_resource['weights'])) {
            continue;
        }

        $font_resource['weights'] = fn_sort_array_by_key($font_resource['weights'], 'priority');
        foreach ($font_resource['weights'] as $weight) {
            if (empty($weight['subsets'])) {
                continue;
            }

            $weight['subsets'] = fn_sort_by_ids($weight['subsets'], FontSubset::getByLanguageUsage($language_code), 'subset');
            $preferred_subset = reset($weight['subsets']);
            $preferred_subset['types'] = fn_sort_by_ids($preferred_subset['types'], FontType::getAllBySupport(), 'type');
            $preferred_type = reset($preferred_subset['types']);
            $preferred_type['priority'] = $font_resource['priority'] * $weight['priority'];
            $preload_font_resources[] = $preferred_type;
        }
    }

    $preload_font_resources = fn_sort_array_by_key($preload_font_resources, 'priority');

    return array_slice($preload_font_resources, 0, $max_fonts_to_preload);
}

/**
 * Gets font-face properties from CSS.
 *
 * @param array<string> $properties Font-face properties
 *
 * @return array<string, int|string>
 *
 * @psalm-return array{
 *   font-family: string,
 *   font-weight: int,
 *   unicode-range: string,
 *   src: string,
 *   --preload-priority: int,
 * }
 *
 * @internal
 */
function fn_block_styles_get_font_face_properties(array $properties)
{
    $font_face_properties = [
        'font-family'        => null,
        'font-weight'        => FontWeight::NORMAL,
        'unicode-range'      => FontSubset::LATIN,
        'src'                => [],
        '--preload-priority' => 1000,
    ];

    foreach ($properties as $property_string) {
        list($property, $value) = explode(':', $property_string, 2);
        $property = trim($property);
        $value = trim($value);
        if ($property === 'font-family') {
            $value = trim($value, '"\'');
        }
        if ($property === 'font-weight') {
            $value = FontWeight::getByValue($value);
        }
        if ($property === 'src') {
            $value = array_merge($font_face_properties['src'], (array) $value);
        }
        if ($property === '--preload-priority') {
            $value = (int) $value;
        }

        $font_face_properties[$property] = $value;
    }

    return $font_face_properties;
}

/**
 * @param array<string, string> $layout Current layout data
 *
 * @psalm-param array{
 *   style_id: string,
 *   theme_name: string,
 * }
 *
 * @return array<
 *   string, array{
 *     family: string,
 *     priority: int,
 *     weights: array<
 *       int, array{
 *         weight: int,
 *         priority: int,
 *       }
 *     >
 *   }
 * >
 *
 * @internal
 */
function fn_block_styles_get_fonts_from_theme_style(array $layout)
{
    if (!$layout['style_id']) {
        return [];
    }

    $font_family_priorities = [];
    $style = Styles::factory($layout['theme_name'])->get($layout['style_id'], ['parse' => true]);
    $priority = 0;
    foreach (['body_font', 'headings_font', 'links_font'] as $font_id) {
        if (empty($style['data'][$font_id])) {
            continue;
        }

        list($font_family) = explode(',', $style['data'][$font_id]);
        $font_family = trim($font_family, '"\'');
        $font_weight = FontWeight::NORMAL;
        if (!empty($style['data']["{$font_id}_weight"])) {
            $font_weight = FontWeight::getByValue($style['data']["{$font_id}_weight"]);
        }
        if (!isset($font_family_priorities[$font_family])) {
            $font_family_priorities[$font_family] = [
                'family'   => $font_family,
                'priority' => ++$priority,
                'weights'  => [],
            ];
        }
        // phpcs:ignore
        if (!isset($font_family_priorities[$font_family]['weights'][$font_weight])) {
            $font_family_priorities[$font_family]['weights'][$font_weight] = [
                'weight'   => $font_weight,
                'priority' => ++$priority,
            ];
        }
    }

    return $font_family_priorities;
}
