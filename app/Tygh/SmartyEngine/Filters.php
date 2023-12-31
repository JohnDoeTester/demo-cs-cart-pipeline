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

namespace Tygh\SmartyEngine;

use Smarty_Internal_Template;
use Smarty_Internal_TemplateCompilerBase;
use Tygh;
use Tygh\Embedded;
use Tygh\Registry;
use Tygh\Tools\Url;

class Filters
{
    /**
     * Inlines 'literal' script instances.
     *
     * @param string                    $content  Template content
     * @param \Smarty_Internal_Template $template Template instance
     *
     * @return string
     */
    public static function preScript($content, Smarty_Internal_Template $template)
    {
        $pattern = '/\<script([^>]*)\>.*?\<\/script\>/s';
        if (preg_match_all($pattern, $content, $matches)) {
            $m = $matches[0];
            $m_attrs = $matches[1];
            $has_literals = strpos($content, '{literal}');
            foreach ($m as $index => $match) {
                if (!strpos($m_attrs[$index], 'data-no-defer') ) {
                    $inline_wrapper_open = '{inline_script}';
                    $inline_wrapper_close = '{/inline_script}';
                    // Check if script was wrapped by the {literal} tag
                    if ($has_literals !== false) {
                        $end_pos = strpos($content, $match);
                        // Calculate literals count to detect if script is between {literal}
                        // If end_pos is equal to 0, {literal} tag inside <script>, so skip it
                        if ($end_pos != 0) {
                            $open_tags = substr_count($content, '{literal}', 0, $end_pos);
                            $close_tags = substr_count($content, '{/literal}', 0, $end_pos);
                            if ($open_tags != $close_tags) {
                                $inline_wrapper_open = '{/literal}' . $inline_wrapper_open . '{literal}';
                                $inline_wrapper_close = '{/literal}' . $inline_wrapper_close . '{literal}';
                            }
                        }
                    }
                    $content = str_replace($match, $inline_wrapper_open . $match . $inline_wrapper_close, $content);
                }
            }
        }

        return $content;
    }

    /**
     * Moves inline scripts to the bottom of the page.
     *
     * @param string                    $content  Template content
     * @param \Smarty_Internal_Template $template Template instance
     *
     * @return string
     */
    public static function outputScript($content, Smarty_Internal_Template $template)
    {
        if (defined('AJAX_REQUEST')) {
            return $content;
        }

        if ($template->smarty->getTemplateVars('block_rendering')) {
            if (!$template->smarty->getTemplateVars('block_parse_js')) {
                return $content;
            }
        }

        $pattern = '/\<script([^>]*)\>.*?\<\/script\>/s';
        if (preg_match_all($pattern, $content, $matches)) {
            if (Registry::get('runtime.inside_scripts')) {
                return $content;
            }

            $cache_name = $template->smarty->getTemplateVars('block_cache_name');

            $m = $matches[0];
            $m_attrs = $matches[1];

            $javascript = '';

            foreach ($m as $index => $match) {
                if (strpos($m_attrs[$index], 'data-no-defer') === false) {
                    $repeat = false;
                    $template->smarty->loadPlugin('smarty_block_inline_script');
                    smarty_block_inline_script(array(), $match, $template->smarty, $repeat);

                    $content = str_replace($match, '<!-- Inline script moved to the bottom of the page -->', $content);
                    $javascript .= $match;
                }
            }

            if (!empty($cache_name)) {
                $cached_content = Registry::get($cache_name);
                if (!isset($cached_content['javascript'])) {
                    $cached_content['javascript'] = '';
                }
                $cached_content['javascript'] .= $javascript;

                Registry::set($cache_name, $cached_content, true);
            }

        }

        return $content;
    }

    /**
     * Prefilter: template wrapper for design mode
     * @param  string                    $content  template content
     * @param  \Smarty_Internal_Template $template template instance
     * @return string                    template content
     */
    public static function preTemplateWrapper($content, Smarty_Internal_Template $template)
    {
        $cur_templ = fn_addon_template_overrides($template->template_resource, $template);
        $cur_templ = str_replace(Tygh::$app['view']->default_resource_type . ':', '', $cur_templ);

        $ignored_template = array(
            'index.tpl',
            'common/pagination.tpl',
            'views/categories/components/menu_items.tpl',
            'views/block_manager/render/location.tpl',
            'views/block_manager/render/container.tpl',
            'views/block_manager/render/grid.tpl',
            'views/block_manager/render/block.tpl',
            'backend:common/template_editor.tpl',
            'backend:common/theme_editor.tpl',
            'backend:views/debugger/debugger.tpl',
        );

        if (!in_array($cur_templ, $ignored_template) && fn_get_file_ext($cur_templ) == 'tpl') { // process only "real" templates (not eval'ed, etc.)
            $content =
                '{if $runtime.customization_mode.design == "Y" && $smarty.const.AREA == "C"}' .
                    '{capture name="template_content"}' . $content . '{/capture}' .
                    '{if $smarty.capture.template_content|trim}' .
                        '{if $auth.area == "A"}' .
                            '<span class="cm-template-box template-box" data-ca-te-template="' . $cur_templ . '" id="{set_id name="' . $cur_templ . '"}">' .
                            '<div class="cm-template-icon icon-edit ty-icon-edit hidden"></div>' .
                            '{$smarty.capture.template_content nofilter}<!--[/tpl_id]--></span>' .
                        '{else}{$smarty.capture.template_content nofilter}{/if}{/if}' .
                '{else}' . $content . '{/if}';
        }

        return $content;
    }

    /**
     * Postfilter: gets all available language variables in templates and puts their retrieving to the template start
     * @param  string                    $content  template content
     * @param  \Smarty_Internal_Template $template template instance
     * @return string                    template content
     */
    public static function postTranslation($content, Smarty_Internal_Template $template)
    {
        $content = str_replace('__(', '$_smarty_tpl->__(', $content);

        if (preg_match_all('/__\(\"([\w\.]*?)\"/i', $content, $matches)) {
            return "<?php\n\Tygh\Languages\Helper::preloadLangVars(array('" . implode("','", $matches[1]) . "'));\n?>\n" . $content;
        }

        return $content;
    }

    /**
     * Prefilter: escapes all variables except language variables
     *
     * @param string                                $content  Template content
     * @param \Smarty_Internal_TemplateCompilerBase $compiler Compiler
     *
     * @return string
     */
    public static function preTranslation(string $content, Smarty_Internal_TemplateCompilerBase $compiler)
    {
        if (!preg_match('/^__\(/', $content) && !preg_match('/^strtr\(\(string\)__\(/', $content)) {
            //phpcs:ignore
            $content = "htmlspecialchars((string) {$content}, ENT_QUOTES, '" . addslashes(Core::$_CHARSET) . "')";
        }

        return $content;
    }

    /**
     * Output filter: translation mode
     * @param  string                    $content  template content
     * @param  \Smarty_Internal_Template $template template instance
     * @return string                    template content
     */
    public static function outputLiveEditorWrapper($content, Smarty_Internal_Template $template)
    {
        $pattern = '/\<(input|img|div)[^>]*?(\[lang name\=([\w\-\.]+?)\](.*?)\[\/lang\])[^>]*?\>/';
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[0] as $k => $m) {
                $phrase_replaced = str_replace($matches[2][$k], $matches[5][$k], $matches[0][$k]);
                $langvar = $matches[3][$k];
                $langvar_value = addslashes(htmlentities(__($langvar, ['skip_live_editor' => true])));

                if (strpos($m, 'class="') !== false) {
                    $class_added = str_replace(
                        'class="',
                        'data-ca-live-edit="langvar::' . $langvar . '"' .
                        ' data-ca-live-edit-original-value="' . $langvar_value . '"' .
                        ' class="cm-live-editor-need-wrap ',
                        $phrase_replaced
                    );
                } else {
                    $class_added = str_replace(
                        $matches[1][$k],
                        $matches[1][$k] .
                        ' data-ca-live-edit="langvar::' . $langvar . '"' .
                        ' data-ca-live-edit-original-value="' . $langvar_value . '"' .
                        ' class="cm-live-editor-need-wrap"',
                        $phrase_replaced
                    );
                }

                if ($matches[1][$k] === 'div') {
                    $content = str_replace($matches[0][$k], $phrase_replaced, $content);
                } else {
                    $content = str_replace($matches[0][$k], $class_added, $content);
                }
            }
        }

        $pattern = '/(\<(textarea|option)[^<]*?)\>(\[lang name\=([\w\-\.]+?)\](.*?)\[\/lang\])[^>]*?\>/is';
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[0] as $k => $m) {
                $phrase_replaced = str_replace($matches[3][$k], $matches[6][$k], $matches[0][$k]);
                $langvar = $matches[4][$k];
                $langvar_value = addslashes(htmlentities(__($langvar, ['skip_live_editor' => true])));

                if (strpos($m, 'class="') !== false) {
                    $class_added = str_replace(
                        'class="',
                        'data-ca-live-edit="langvar::' . $langvar . '"' .
                        ' data-ca-live-edit-original-value="' . $langvar_value . '"' .
                        ' class="cm-live-editor-need-wrap ',
                        $phrase_replaced
                    );
                } else {
                    $class_added = str_replace(
                        '<' . $matches[2][$k],
                        '<' . $matches[2][$k] .
                        ' data-ca-live-edit="langvar::' . $langvar . '"' .
                        ' data-ca-live-edit-original-value="' . $langvar_value . '"' .
                        ' class="cm-live-editor-need-wrap"',
                        $phrase_replaced
                    );
                }
                $content = str_replace($matches[0][$k], $class_added, $content);
            }
        }

        $pattern = '/<title>(.*?)<\/title>/is';
        $pattern_inner = '/\[(lang) name\=([\w\-\.]+?)\](.*?)\[\/\1\]/is';
        preg_match($pattern, $content, $matches);
        $phrase_replaced = $matches[0];
        $phrase_replaced = preg_replace($pattern_inner, '$3', $phrase_replaced);
        $content = str_replace($matches[0], $phrase_replaced, $content);

        // remove translation tags from elements attributes
        $pattern = '/(\<[^<>]*\=[^<>]*)(\[lang name\=([\w\-\.]+?)\](.*?)\[\/lang\])[^<>]*?\>/is';
        while (preg_match($pattern, $content, $matches)) {
            $phrase_replaced = preg_replace($pattern_inner, '$3', $matches[0]);
            $content = str_replace($matches[0], $phrase_replaced, $content);
        }

        $pattern = '/(?<=>)[^<]*?\[(lang) name\=([\w\-\.]+?)\](.*?)\[\/\1\]/is';
        $pattern_inner = '/\[(lang) name\=([\w\-\.]+?)\]((?:(?>[^\[]+)|\[(?!\1[^\]]*\]))*?)\[\/\1\]/is';
        while (preg_match($pattern, $content, $matches)) {
            $phrase_replaced = $matches[0];
            while (preg_match($pattern_inner, $phrase_replaced)) {
                $phrase_replaced = preg_replace_callback($pattern_inner, static function ($matches) {
                    $langvar = $matches[2];
                    $langvar_value = addslashes(htmlentities(__($langvar, ['skip_live_editor' => true])));

                    return '<var class="live-edit-wrap">' .
                            '<span class="cm-icon-live-edit icon-live-edit ty-icon-live-edit"></span>' .
                                '<var data-ca-live-edit="langvar::' . $langvar . '"' .
                                ' data-ca-live-edit-original-value="' . $langvar_value . '"' .
                                ' class="cm-live-edit live-edit-item"' .
                            '>' . $matches[3] . '</var>' .
                        '</var>';
                }, $phrase_replaced);
            }
            $content = str_replace($matches[0], $phrase_replaced, $content);
        }

        $pattern = '/\[(lang) name\=([\w\-\.]+?)\](.*?)\[\/\1\]/';
        $replacement = '$3';
        $content = preg_replace($pattern, $replacement, $content);
        return $content;
    }

    /**
     * Output filter: design mode
     * @param  string                    $content  template content
     * @param  \Smarty_Internal_Template $template template instance
     * @return string                    template content
     */
    public static function outputTemplateIds($content, Smarty_Internal_Template $template)
    {
        $pattern = '/(\<head\>.*?)(\<span[^<>]*\>|\<\/span\>|\<img[^<>]*\>|\<!--[\w]*--\>)+?(.*?\<\/head\>)/is';
        while (preg_match($pattern, $content, $match)) {
            $content = str_replace($match[0], $match[1] . $match[3], $content);
        }
        $pattern = '/\<span[^<>]*\>|\<\/span\>|\<img[^<>]*\>|\<!--[\w]*--\>/is';
        $glob_pattern = '/\<script[^<>]*\>.*?\<\/script\>/is';
        if (preg_match_all($glob_pattern, $content, $matches)) {
            foreach ($matches[0] as $k => $m) {
                $replace_script = preg_replace($pattern, '', $matches[0][$k]);
                $content = str_replace($matches[0][$k], $replace_script, $content);
            }
        }

        static $template_ids;

        if (!isset($template_ids)) {
            $template_ids = array();
        }

        $pattern = '/\[(tpl_id) ([^ ]*)\]((?:(?>[^\[]+)|\[(?!\1[^\]]*\]))*?)\[\/\1\]/is';
        while (preg_match($pattern, $content, $matches)) {
            $id = 'te' . md5($matches[2]);
            if (empty($template_ids[$matches[2]])) {
                $template_ids[$matches[2]] = 1;
            } else {
                $template_ids[$matches[2]]++;
                $id .= '_' . $template_ids[$matches[2]];
            }
            $content = preg_replace($pattern, $id . '${3}' . $id, $content, 1);
        }

        return $content;
    }

    /**
     * Output filter: adds unique field to all forms to protect from CSRF attacks.
     *
     * @param string                    $content  Template content
     * @param \Smarty_Internal_Template $template Template instance
     *
     * @return string                    Template content
     */
    public static function outputSecurityHash($content, Smarty_Internal_Template $template)
    {
        $content = preg_replace('/<input type="hidden" name="security_hash".*?>/i', '', $content);
        $content = str_replace(
            '</form>',
            '<input type="hidden" name="security_hash" class="cm-no-hide-input" value="' . fn_generate_security_hash() . '" /></form>',
            $content
        );

        return $content;
    }

    /**
     * Output filter: adds ability to share objects per companies
     * @param  string                    $content  template content
     * @param  \Smarty_Internal_Template $template template instance
     * @return string                    template content
     */
    public static function outputSharing($content, Smarty_Internal_Template $template)
    {
        if (!fn_allowed_for('ULTIMATE')) {
            return $content;
        }

        if (Registry::get('runtime.simple_ultimate')) {
            return $content;
        }

        $sharing = Registry::get('sharing');
        $content_expr = '/<!--Content-->.*?<!--\/Content-->/is';

        if (defined('AJAX_REQUEST')) {
            $central_content = $content;
        } elseif (preg_match($content_expr, $content, $central_content)) {
            $central_content = $central_content[0];
        }

        if (!empty($central_content)) {
            if (!empty($sharing['tpl_tabs'])) {
                foreach ($sharing['tpl_tabs'] as $object => $data) {
                    // Add a new tab
                    $tab_expr = '/(<div[^>]+?class[^>]*?tabs.*?>.*?<ul.*?>)(.*?)(<\/ul>)/is';
                    if (preg_match($tab_expr, $central_content, $matches)) {
                        if (!empty($matches[2])) {
                            // Add a new tab
                            $tab_content = $matches[1] . $matches[2] . '<li id="tab_share_object' . $data['params']['object_id'] . '" class="cm-js cm-ajax"><a href="' . fn_url('companies.get_object_share?object=' . $data['params']['object'] . '&object_id=' . $data['params']['object_id']) . '">' . __('storefronts') . '</a></li>' . $matches[3];

                            $central_content = preg_replace($tab_expr, fn_preg_replacement_quote($tab_content), $central_content, 1);
                        }

                        // Get main form to add tab content inside.
                        $form_content_expr = '/<form.*?>.*?<\/form>/is';
                        if (preg_match($form_content_expr, $central_content, $matches)) {
                            $form = $matches[0];

                            // Add tab content
                            $tab_content_expr = '/<div[^>]+?id[^>]*?content_.*?>/is';
                            if (preg_match($tab_content_expr, $form, $tab_matches)) {
                                $tab_content = '<div class="cm-tabs-content hidden" id="content_tab_share_object' . $data['params']['object_id'] . '"></div>' . $tab_matches[0];

                                $form = preg_replace($tab_content_expr, fn_preg_replacement_quote($tab_content), $form, 1);
                                $central_content = preg_replace($form_content_expr, fn_preg_replacement_quote($form), $central_content, 1);
                            }
                        }
                    }
                }

                if (defined('AJAX_REQUEST')) {
                    $content = $central_content;
                } else {
                    $content = preg_replace($content_expr, fn_preg_replacement_quote($central_content), $content, 1);

                }
            }
        }

        return $content;
    }

    /**
     * Output filter: Transforms URLs to the appropriate format for the embedded mode
     * @param  string                    $content  template content
     * @param  \Smarty_Internal_Template $template template instance
     * @return string                    template content
     */
    public static function outputEmbeddedUrl($content, Smarty_Internal_Template $template)
    {
        $path = Registry::get('config.current_host') . Registry::get('config.current_path');

        // Transform 'href' attribute values of the 'a' elements, which:
        // - have 'href' attribute
        // - the 'href' value contains current path and host, or its a relative url
        // - do not have class attribute starting with 'cm-' prefix

        $pattern = '{'
            . '<(?:a)\s+'
            . '(?=[^>]*\bhref="([^"]*//' . $path . '[^"]*|(?!//)(?!https?)[^"]*)")'
            . '(?![^>]*\bclass="[^"]*cm-[^"]*")'
            . '[^>]*>'
            . '}Usi';

        $content = preg_replace_callback($pattern, function($matches) {
            return str_replace(
                $matches[1],
                Embedded::resolveUrl($matches[1]),
                $matches[0]
            );
        }, $content);

        // Transform relative 'src'attribute values

        $pattern = '{<[^>]+\bsrc="((?!//)(?!https?)[^"]+)"[^>]*>}Usi';

        $content = preg_replace_callback($pattern, function($matches) {
            return str_replace(
                $matches[1],
                Url::resolve($matches[1], Registry::get('config.current_location')),
                $matches[0]
            );
        }, $content);

        $area = \Tygh::$app['view']->getArea();

        if ($area[1] == 'mail') {

            // Transform URLs in the text

            $pattern = '{\bhttps?://' . $path . '[^\s<>"\']*(?=[^>]*<)}s';

            $content = preg_replace_callback($pattern, function($matches) {
                return Embedded::resolveUrl($matches[0]);
            }, $content);

        }

        return $content;
    }

    /**
     * Default Plugin Handler
     * called when Smarty encounters an undefined tag during compilation
     *
     * @param string $name name of the undefined tag
     * @param string $type tag type (e.g. Smarty::PLUGIN_FUNCTION, Smarty::PLUGIN_BLOCK,
     *                                          Smarty::PLUGIN_COMPILER, Smarty::PLUGIN_MODIFIER, Smarty::PLUGIN_MODIFIERCOMPILER)
     * @param object $template template object
     * @param string &$callback returned function name
     * @param string &$script optional returned script filepath if function is external
     * @param bool &$cacheable true by default, set to false if plugin is not cachable (Smarty >= 3.1.8)
     * @return bool true if successfull
     */
    public static function smartyDefaultHandler($name, $type, $template, &$callback, &$script, &$cacheable)
    {
        /* Process all MVE tags */
        if (fn_allowed_for('ULTIMATE')) {
            if (strpos($name, '_mve_') !== false) {
                $callback = array('Tygh\SmartyEngine\Filters', 'smartyHandlerProcessing');

                return true;
            }
        }

        /* Process all ULT tags */
        if (fn_allowed_for('MULTIVENDOR')) {
            if (strpos($name, '_ult_') !== false) {
                $callback = array('Tygh\SmartyEngine\Filters', 'smartyHandlerProcessing');

                return true;
            }
        }

        return false;
    }

    /**
     * Process value of undefined tag
     *
     * @param  mixed $variable_value
     * @return mixed Processed value
     */
    public static function smartyHandlerProcessing($variable_value)
    {
        return false;
    }

}
