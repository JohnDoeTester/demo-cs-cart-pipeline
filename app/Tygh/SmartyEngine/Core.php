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

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tygh\Enum\SiteArea;
use Tygh\Exceptions\PermissionsException;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Themes\Themes;
use Tygh\Tygh;

class Core extends \Smarty
{
    private $_area = AREA;
    private $_area_type = '';

    public $lang_code = CART_LANGUAGE;
    public $default_resource_type = 'tygh';
    public $escape_html = true;
    public $template_area = '';
    public $theme;
    public $theme_dirs = array();

    /**
     * @var bool Whether to use logos from runtime layout (true) or from default layout (false).
     */
    protected $use_runtime_layout_for_logos = true;

    /**
     * Wrapper for translate function
     *
     * @param  string $var    variable to translate
     * @param  array  $params placeholder replacements
     * @return string translated variable
     */
    public function __($var, $params = array())
    {
        return __($var, $params, $this->getLanguage());
    }

    /**
     * Smarty display method wrapper (adds template override, assigns navigation and checks for ajax request)
     * @param string $template   template name
     * @param string $cache_id   cache ID
     * @param string $compile_id compile ID
     * @param mixed  $parent     parent template
     */
    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        parent::display($this->_preFetch($template), $cache_id, $compile_id, $parent);
    }

    /**
     * Smarty fetch method wrapper (adds template override, assigns navigation and checks for ajax request)
     * @param  string  $template        template name
     * @param  string  $cache_id        cache ID
     * @param  string  $compile_id      compile ID
     * @param  mixed   $parent          parent template
     * @param  boolean $display         outputs template if true, returns if false
     * @param  boolean $merge_tpl_vars  merge template variables
     * @param  boolean $no_output_files skips output filters if tru
     * @return string  returns template contents
     */
    public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null, $display = false, $merge_tpl_vars = true, bool $no_output_filter = false)
    {
        return parent::fetch($this->_preFetch($template), $cache_id, $compile_id, $parent, $display, $merge_tpl_vars, $no_output_filter);
    }

    /**
     * Smarty loadPlugin method wrapper, allows to load smarty classes outside default directory
     * @param  string $plugin_name class plugin name to load
     * @param  bool   $check       check if already loaded
     * @return string |boolean filepath of loaded file or false
     */
    public function loadPlugin($plugin_name, $check = true)
    {
        if ($check && (is_callable($plugin_name) || class_exists($plugin_name, false))) {
            return true;
        }

        $_name_parts = explode('_', $plugin_name, 3);

        if (strtolower($_name_parts[1]) == 'internal') {
            $file = Registry::get('config.dir.functions') . 'smarty_plugins/' . strtolower($plugin_name) . '.php';
            if (file_exists($file)) {
                require_once($file);

                return $file;
            }
        }

        return parent::loadPlugin($plugin_name, $check);
    }

    public function getArea()
    {
        return array($this->_area, $this->_area_type);
    }

    /**
     * Sets area to display templates from
     *
     * @param string $area          area name (C,A)
     * @param string $area_type     area type (can be mail of empty)
     * @param int    $company_id    company ID
     * @param int    $storefront_id Storefront ID
     */
    public function setArea($area, $area_type = '', $company_id = null, $storefront_id = null)
    {
        if (fn_allowed_for('MULTIVENDOR') && is_null($company_id) && !Registry::get('runtime.company_id')) {
            $company_id = 0;
        }

        $area_type_suffix = $area_type == 'mail' ? '/mail': '';
        $path = fn_get_theme_path("[themes]/[theme]{$area_type_suffix}", $area, $company_id, true, $storefront_id);
        $path_rel = fn_get_theme_path("[relative]/[theme]{$area_type_suffix}", $area, $company_id, true, $storefront_id);
        if ($area == 'A') {
            $c_prefix = "backend{$area_type_suffix}";
        } else {
            if (!$storefront_id) {
                /** @var \Tygh\Storefront\Storefront $storefront */
                $storefront = Tygh::$app['storefront'];
                $storefront_id = $storefront->storefront_id;
            }
            $c_prefix = fn_get_theme_path("[theme]{$area_type_suffix}", $area, $company_id, true, $storefront_id);
        }

        $suffix = '/templates';
        $this->template_area = $area . (!empty($area_type) ? '_' . $area_type : '');
        $this->setTemplateDir($path . $suffix);
        $this->setConfigDir($path . $suffix);

        $this->_area = $area;
        $this->_area_type = $area_type;

        $this->theme = Themes::areaFactory($area, $company_id, $storefront_id);
        if ($area === SiteArea::STOREFRONT) {
            Registry::registerCache('theme_dirs', [], Registry::cacheLevel(['static', 'storefront']));
            $this->theme_dirs = Registry::ifGet('theme_dirs', []);
            if (!$this->theme_dirs) {
                // all theme directories have to be fetched to use add-on templates from base theme
                $this->theme_dirs = $this->theme->getThemeDirs(Themes::USE_BASE);
                Registry::set('theme_dirs', $this->theme_dirs);
            }

            // add template directories of the theme and the parent theme
            foreach ($this->theme_dirs as $theme_name => $path_info) {
                if ($theme_name !== $this->theme->getThemeName()) {
                    $this->addTemplateDir($path_info[Themes::PATH_ABSOLUTE] . ltrim($area_type_suffix, "/") . $suffix);
                }
            }
        }

        $compile_dir = Registry::get('config.dir.cache_templates') . $c_prefix;

        if (!is_dir($compile_dir)) {
            if (fn_mkdir($compile_dir) == false) {
                throw new PermissionsException("Can't create templates cache directory: <b>" . $compile_dir . '</b>.<br>Please check if it exists, and has writable permissions.');
            }
        }

        $this->setCompileDir($compile_dir);
        $this->setCacheDir($compile_dir);

        if ($parent = $this->theme->getParent()) {
            $this->assign('images_dir', Registry::get('config.current_location') . '/' . $parent->getThemeRelativePath() . "{$area_type_suffix}/media/images");
        } else {
            $this->assign('images_dir', Registry::get('config.current_location') . '/' . $this->theme->getThemeRelativePath() . "{$area_type_suffix}/media/images");
        }
        $this->assign('self_images_dir', Registry::get('config.current_location') . '/' . $path_rel . '/media/images');

        $this->initLogos($company_id);
    }

    /**
     * Displays templates from mail area
     *
     * @param  string  $template   template name
     * @param  boolean $to_screen  outputs if true, returns contents if false
     * @param  string  $area       template area
     * @param  integer $company_id company ID
     * @param  string  $lang_code  language code
     *
     * @return string template contents or true
     */
    public function displayMail($template, $to_screen, $area, $company_id = null, $lang_code = CART_LANGUAGE)
    {
        $original_lang_code = $this->getLanguage();
        $original_logos_source = $this->use_runtime_layout_for_logos;

        $this->use_runtime_layout_for_logos = false;
        $this->setArea($area, 'mail', $company_id);
        $this->setLanguage($lang_code);

        $result = true;

        /**
         * Actions before processing mail content via Smarty
         *
         * @param object $this       Smarty instance
         * @param string $template   Template name
         * @param bool   $to_screen  To screen flag
         * @param string $area       Area name
         * @param int    $company_id Company ID
         * @param string $lang_code  Language code
         */
        fn_set_hook('smarty_display_mail', $this, $template, $to_screen, $area, $company_id, $lang_code, $original_lang_code, $result);

        if ($to_screen == true) {
            $this->display($template);
        } else {
            $result = $this->fetch($template);
        }

        $this->use_runtime_layout_for_logos = $original_logos_source;
        $this->setArea(AREA);
        $this->setLanguage($original_lang_code);

        return $result;
    }

    /**
     * Prepares data before template fetch (adds template override, assigns navigation and checks for ajax request)
     * @param  string $template template name
     * @return string processed template name
     */
    private function _preFetch($template)
    {
        if (defined('AJAX_REQUEST') && (!Tygh::$app['ajax']->full_render || defined('THEME_EDITOR_MODE'))) {
            // Decrease amount of templates to parse if we're using ajax request
            if ($template === 'index.tpl' && $this->getTemplateVars('content_tpl') && !defined('THEME_EDITOR_MODE')) {
                $template = $this->getTemplateVars('content_tpl');
            }

            list($area, $area_type) = $this->getArea();
            if ($area == 'A' && empty($area_type)) {
                // Display required helper files
                parent::fetch('buttons/helpers.tpl');
            }
        }

        $this->_setCoreParams();

        return fn_addon_template_overrides($template, $this);
    }

    /**
     * Sets core templates parameteres
     */
    private function _setCoreParams()
    {
        $data = Registry::getAll();

        $this->assign(array(
            'demo_username' => isset($data['config']['demo_username']) ? $data['config']['demo_username'] : null,
            'demo_password' => isset($data['config']['demo_password']) ? $data['config']['demo_password'] : null,
            'user_info' => isset($data['user_info']) ? $data['user_info'] : null,
            'navigation' => isset($data['navigation']) ? $data['navigation'] : null, // Pass navigation to templates
            'settings' => $data['settings'],
            'addons' => $data['addons'],
            'config' => $data['config'],
            'runtime' => $data['runtime'],
            '_REQUEST' => $_REQUEST, // we need escape the request array too (access via $smarty.request in template)
            'auth' => \Tygh::$app['session']['auth'],
            'server_env' => \Tygh::$app['server.env'] // Pass server environment to templates
        ));
    }

    /**
     * Sets language code to get language variables
     * @param string $lang_code language code
     */
    public function setLanguage($lang_code)
    {
        $this->lang_code = $lang_code;
        // Set language direction
        $this->assign('language_direction', fn_is_rtl_language($lang_code)? 'rtl': 'ltr');
    }

    /**
     * Gets language code  for language variables
     * @return string language code
     */
    public function getLanguage()
    {
        return $this->lang_code;
    }

    /**
     * Checks if template file exists.
     * Tip: this realisation much faster and has less memory consumption than default, but does not support storages except file system.
     *
     * @param string $resource_name Relative template path
     *
     * @return bool true if exists, false - otherwise
     */
    public function templateExists($resource_name)
    {
        $exists = $this->templateExistsViaSnapshot($resource_name);

        if ($exists !== null) {
            return $exists;
        }

        /** @var array<string> $dirs */
        $dirs = $this->getTemplateDir();

        foreach ($dirs as $dir) {
            if (file_exists($dir . trim($resource_name, '/'))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if template dir exists.
     *
     * @param string $resource_name Relative template dir path
     *
     * @return bool
     */
    public function templateDirExists($resource_name)
    {
        /** @var array<string> $dirs */
        $dirs = $this->getTemplateDir();

        foreach ($dirs as $dir) {
            if (file_exists($dir . trim($resource_name, '/'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if template file exists via snapshot
     *
     * @param string $resource_name Relative template dir path
     *
     * @return bool|null
     */
    private function templateExistsViaSnapshot($resource_name)
    {
        static $snapshot = null;
        static $use_snapshot = null;

        if ($use_snapshot === null && SiteArea::isStorefront(AREA)) {
            $key = __FUNCTION__;
            Registry::registerCache($key, ['addons'], Registry::cacheLevel('static'));

            if (Registry::isExist($key)) {
                $use_snapshot = false;
            } else {
                Registry::set($key, 1);
                $use_snapshot = true;
            }
        }

        if (!$use_snapshot) {
            return null;
        }

        if ($snapshot === null) {
            $snapshot = [];
            $dirs = $this->getTemplateDir();

            foreach ($dirs as $dir) {
                $directory_iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $dir,
                        FilesystemIterator::SKIP_DOTS |
                        FilesystemIterator::CURRENT_AS_FILEINFO |
                        FilesystemIterator::FOLLOW_SYMLINKS
                    ),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($directory_iterator as $spl_file_info) {
                    /** @var \SplFileInfo $spl_file_info */
                    if (!$spl_file_info->isFile() && !$spl_file_info->isLink()) {
                        continue;
                    }
                    $snapshot[str_replace($dir, '', $spl_file_info->getPathname())] = true;
                }
            }
        }

        return
            isset($snapshot[$resource_name])
            || ('\\' === DIRECTORY_SEPARATOR && isset($snapshot[str_replace('/', '\\', $resource_name)]));
    }

    /**
     * Inits logos
     *
     * @param int|null $company_id Company ID
     */
    private function initLogos($company_id)
    {
        $key = sprintf(
            'init_logos_%s_%s_%s',
            $company_id === null ? '_' : (int) $company_id,
            Registry::get('runtime.layout.layout_id'),
            md5(Registry::get('runtime.layout.style_id'))
        );

        $logos = Registry::getOrSetCache(
            ['template_logos', $key],
            ['logos', 'images', 'images_links'],
            ['static', 'storefront'],
            function () use ($company_id) {
                $layout_id = $this->use_runtime_layout_for_logos ? (int) Registry::get('runtime.layout.layout_id') : null;
                $style_id = $this->use_runtime_layout_for_logos ? (string) Registry::get('runtime.layout.style_id') : null;
                $storefront_id = $company_id ? null : StorefrontProvider::getStorefront()->storefront_id;

                return fn_get_logos(
                    $company_id,
                    $layout_id,
                    $style_id,
                    $storefront_id
                );
            }
        );

        $this->assign('logos', $logos);
    }
}
