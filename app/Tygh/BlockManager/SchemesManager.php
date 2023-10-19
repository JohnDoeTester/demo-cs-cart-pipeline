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

namespace Tygh\BlockManager;

use Tygh\Languages\Helper as LanguageHelper;
use Tygh\Registry;
use Tygh\Themes\Themes;

class SchemesManager
{
    /**
     * Static storage for already read schemes
     *
     * @var array{block: array<string, array<string, string>>, templates: string, content: array<string, string|bool|array>} $schemes Static storage for already read schemes
     */
    private static $schemes;

    /**
     * Static storage for already read schemes for grids
     *
     * @var array{wrappers: array<string, string>} $grid_schemes Static storage for already read schemes for grids
     */
    private static $grid_schemes;

    /**
     * Returns list of dispatches and it's descriptions
     * @static
     * @param  string $lang_code 2 letter language code
     * @return array  List of dispatch descriptions as dispatch => description
     */
    public static function getDispatchDescriptions($lang_code = DESCR_SL)
    {
        $descriptions = self::_getScheme('dispatch_descriptions');

        LanguageHelper::preloadLangVars(array_values($descriptions), $lang_code);

        foreach ($descriptions as $dispatch => $lang_var) {
            $descriptions[$dispatch] = __($lang_var, array(), $lang_code);
        }

        return $descriptions;
    }

    /**
     * Returns dynamic object data
     * @static
     * @param  string     $dispatch URL dispatch (controller.mode.action)
     * @param  string     $area     Area ('A' for admin or 'C' for customer)
     * @param  array      $request  requrest data
     * @return array|bool Array of dynamic object data, false otherwise
     */
    public static function getDynamicObject($dispatch, $area = 'A', $request = array())
    {
        $area = self::_normalizeArea($area);

        $objects = self::_getScheme('dynamic_objects');

        if (strpos($dispatch, '?') !== false) {
            list($dispatch, $q) = explode('?', $dispatch);
            parse_str($q, $request);
        }

        foreach ($objects as $object_type => $properties) {
            if (isset($properties[$area]) && $properties[$area] == $dispatch) {

                if (!empty($properties['check_params']) && !empty($request)) {
                    $properties['customer_dispatch'] = $properties['check_params']($request);
                }

                $properties['object_type'] = $object_type;

                return $properties;
            }
        }

        return false;
    }

    /**
     * Returns dynamic object data
     * @static
     * @param  string     $type    Type of dinamic object
     * @param  array      $request requrest data
     * @return array|bool Array of dynamic object data, false otherwise
     */
    public static function getDynamicObjectByType($type, $request = array())
    {
        $objects = self::_getScheme('dynamic_objects');
        if (isset($objects[$type])) {

            if (!empty($objects[$type]['check_params']) && !empty($request)) {
                $objects[$type]['customer_dispatch'] = $objects[$type]['check_params']($request);
            }

            return $objects[$type];
        }

        return array();
    }

    /**
     * Checks existing block with $block_type in block manager scheme
     * @static
     * @param  string $block_type Block type. Thirst key of scheme array
     * @return bool
     */
    public static function isBlockExist($block_type)
    {
        $scheme = self::_getScheme('blocks');

        return isset($scheme[$block_type]);
    }

    /**
     * Gets scheme for some block type
     * @static
     * @param  string $block_type Block type. Thirst key of scheme array
     * @param  array  $params     Request params
     * @param  bool   $no_cache   Do not get scheme from cache
     * @return array  Array of block scheme data
     */
    public static function getBlockScheme($block_type, $params = [], $no_cache = false)
    {
        $scheme = self::_getScheme('blocks');

        $cache_name = 'scheme_block_' . $block_type;

        Registry::registerCache(['scheme_block', $cache_name], ['addons'], Registry::cacheLevel(['static', 'storefront']));

        if (Registry::isExist($cache_name) == true && $no_cache == false) {
            return Registry::get($cache_name);
        } else {
            if (isset($scheme[$block_type])) {
                // Get all data for this block type
                $_block_scheme = $scheme[$block_type];

                $_block_scheme['type'] = $block_type;

                // Update templates data
                $_block_scheme['templates'] = self::_prepareTemplates($_block_scheme);
                $_block_scheme['wrappers'] = self::_prepareWrappers($_block_scheme);
                $_block_scheme['content'] = self::prepareContent($_block_scheme, $params);
                $_block_scheme['is_manageable'] = self::isManageable($block_type, $_block_scheme);
                $_block_scheme = self::_prepareSettings($_block_scheme);

                Registry::set($cache_name, $_block_scheme);

                return $_block_scheme;
            }
        }

        return array();
    }

    /**
     * Generates content section of block scheme
     * @static
     * @param  array $block_scheme   Scheme of block
     * @param  array $request_params Request params
     * @return array Content section of block scheme
     */
    public static function prepareContent($block_scheme, $request_params)
    {
        $content = array();

        if (isset($block_scheme['content']) && is_array($block_scheme['content'])) {
            foreach ($block_scheme['content'] as $name => $params) {
                $content[$name] = self::_getValue($params);

                if (is_array($content[$name]) && isset($content[$name]['fillings'])) {
                    $content[$name]['fillings'] = self::mergeFillingsSettings($name, $block_scheme, $request_params);
                }
            }
        }

        return $content;
    }

    /**
     * Merges the settings of all fillings available for the block
     * @static
     * @param  array $name           Content name
     * @param  array $block_scheme   Scheme of block
     * @param  array $request_params Request params
     *
     * @return array $fillings       The array with the settings of the block's fillings
     */
    public static function mergeFillingsSettings($name, $block_scheme, $request_params)
    {
        $fillings_scheme = self::_getScheme('fillings');
        $fillings = $block_scheme['content'][$name]['fillings'];

        foreach ($fillings as $filling_name => &$filling_param) {
            if (isset($fillings_scheme[$filling_name])) {
                $filling_param['settings'] = $fillings_scheme[$filling_name];
            }

            if (isset($filling_param['params']['ignore_settings'])
                && is_array($filling_param['params']['ignore_settings'])
            ) {
                $settings_ignore = array_flip($filling_param['params']['ignore_settings']);
                $filling_param['settings'] = array_diff_key($filling_param['settings'], $settings_ignore);
            }

            $filling_param = self::_prepareSettings($filling_param);

            if (!self::isFillingAvailable($request_params, $block_scheme, $filling_name)) {
                unset($fillings[$filling_name]);
            }
        }

        return $fillings;
    }

    /**
     * Returns available filling for this template or no
     * @static
     * @param  array  $params       Request params
     * @param  array  $block_scheme Scheme of block
     * @param  string $filling_name name of filling
     * @return bool   True if filling is available for this template, false otherwise
     */
    public static function isFillingAvailable($params, $block_scheme, $filling_name)
    {
        if (isset($params['properties']['template'])) {
            $template = $params['properties']['template'];
            if (isset($block_scheme['templates'][$template]['fillings'])) {
                return in_array($filling_name, $block_scheme['templates'][$template]['fillings']);
            }
        }

        return true;
    }

    /**
     * Generates templates section of block scheme
     * @static
     * @param  array $block_scheme Scheme of block
     * @return array Templates section of block scheme
     */
    private static function _prepareTemplates($block_scheme)
    {
        $templates = array();

        if (isset($block_scheme['templates'])) {
            $_all_templates = self::_getScheme('templates');
            $block_scheme['templates'] = self::_getValue($block_scheme['templates']);

            $theme = Themes::areaFactory('C');
            $theme_paths = array_reverse($theme->getThemeDirs());

            if (is_array($block_scheme['templates'])) {
                foreach ($block_scheme['templates'] as $path => $template) {
                    foreach ($theme_paths as $path_info) {
                        if (isset($_all_templates[$path])) {
                            $template = array_merge($template, $_all_templates[$path]);
                        }

                        if (empty($template['name'])) {
                            $template['name'] = self::generateTemplateName($path, $path_info[Themes::PATH_ABSOLUTE] . 'templates/');
                        }

                        $templates[$path] = $template;
                    }
                }
            }
        }

        return self::_prepareSettings($templates);
    }

    /**
     * Generates additional params for settings array
     * @param  array $scheme
     * @return array
     */
    private static function _prepareSettings($scheme)
    {
        if (!empty($scheme['settings']) && is_array($scheme['settings'])) {
            foreach ($scheme['settings'] as $name => $value) {
                $scheme['settings'][$name] = self::_getValue($value);
            }
        }

        return $scheme;
    }

    /**
     * Generates wrappers section of block scheme
     * @static
     * @param  array $block_scheme Scheme of block
     * @return array Wrappers section of block scheme
     */
    public static function _prepareWrappers($block_scheme)
    {
        $wrappers = array();

        if (isset($block_scheme['wrappers'])) {
            return self::_getValue($block_scheme['wrappers']);
        }

        return $wrappers;
    }

    /**
     * Returns all block types
     * @static
     * @param  string $lang_code 2 letter language code
     * @return array  List of block types with name, icon and type
     */
    public static function getBlockTypes($lang_code = CART_LANGUAGE)
    {
        $scheme = self::_getScheme('blocks');
        $types = array();

        foreach ($scheme as $type => $params) {
            $types[$type] = array(
                'type' => $type,
                'name' => __('block_' . $type, '', $lang_code),
                'icon' => '/media/images/block_manager/block_icons/default.png'
            );

            if (!empty($params['icon'])) {
                $types[$type]['icon'] = $params['icon'];
            }
        }

        $types = fn_sort_array_by_key($types, 'name');

        return $types;
    }

    /**
     * Removes blocks that cannot be on $location or can be only singular for this $location and already exist on it
     * for $location and allready exists on it
     *
     * To define that kind of block use hide_on_locations and single_for_location keys in blocks scheme
     *
     * @param  array $blocks   List of blocks
     * @param  array $location Array with location data
     * @return array Filtered list of blocks
     */
    public static function filterByLocation($blocks, $location)
    {
        $scheme = self::_getScheme('blocks');

        foreach ($blocks as $block_key => $block) {
            if (!empty($block['type'])) {
                $type = $block['type'];
                $hide_block_and_skip = false;

                if (!empty($scheme[$type]['hide_on_locations'])) {
                    if (array_search($location['dispatch'], $scheme[$type]['hide_on_locations']) !== false) {
                        $hide_block_and_skip = true;
                    }
                } elseif (isset($scheme[$type]['show_on_locations'])) {
                    if (!in_array($location['dispatch'], $scheme[$type]['show_on_locations'], true)) {
                        $hide_block_and_skip = true;
                    }
                }

                if ($hide_block_and_skip) {
                    unset($blocks[$block_key]);
                    continue;
                }

                $blocks[$block_key]['is_manageable'] = self::isManageable($type);

                if (!empty($block['type']) && !empty($scheme[$type]['single_for_location'])) {
                    $blocks[$block_key]['single_for_location'] = true;
                    $block_exists = Block::instance()->getBlocksByTypeForLocation($type, $location['location_id']);
                    if (!empty($block_exists)) {
                        unset($blocks[$block_key]);
                    }
                }
            }
        }

        return $blocks;
    }

    /**
     * Gets scheme and place it in private storage
     * @static
     * @param $target
     * @param $name
     * @return mixed
     */
    private static function _getScheme($name, $target = 'block_manager')
    {
        if (empty(self::$schemes[$name])) {
            self::$schemes[$name] = fn_get_schema($target, $name);
        }

        return self::$schemes[$name];
    }

    /**
     * Returns 'customer' or 'admin' for 'C' or 'A'
     * @param  string $area Area ('A' for admin or 'C' for customer)
     * @return string
     */
    private static function _normalizeArea($area)
    {
        if ($area == 'A') {
            $area = 'admin_dispatch';
        } else {
            $area = 'customer_dispatch';
        }

        return $area;
    }

    /**
     * Generates scheme data
     * @static
     * @param  mixed       $item Item from scheme
     * @return array|mixed
     */
    private static function _getValue($item)
    {
        // check, are there any function
        if (is_array($item)) {
            if (!empty($item[0]) && is_callable($item[0])) {
                // If it's a function execute it and return it result
                $callable = array_shift($item);

                return call_user_func_array($callable, $item);
            } elseif (!empty($item['data_function'][0]) && is_callable($item['data_function'][0])) {
                // If it's a data function, get the values
                $callable = array_shift($item['data_function']);
                $item['values'] = call_user_func_array($callable, $item['data_function']);
            }

            return $item;
        }

        // check for custom folder with templates
        $_dir = Registry::get('config.dir.root') . '/' . $item;
        if (is_dir($_dir)) {
            // If it's dir with templates return list of templates
            return fn_get_dir_contents($_dir, false, true);
        }

        // check for templates in the theme dirs
        $theme = Themes::areaFactory('C');
        $theme_paths = $theme->getThemeDirs();

        // check for single template
        $single_template = $theme->getContentPath("templates/{$item}");
        if ($single_template) {
            return array(
                strval($item) => array(
                    'name' => self::generateTemplateName($single_template[Themes::PATH_ABSOLUTE], '')
                )
            );
        }

        // check for templates in given folder and addons too
        $result = array();
        $dir_params = array(
            'dir' => 'templates/' . $item,
            'get_dirs' => false,
            'get_files' => true,
            'extension' => '.tpl',
            'prefix' => "{$item}/"
        );
        $tpl_files = $theme->getDirContents($dir_params, Themes::STR_MERGE);
        foreach (Registry::ifGet('addons', []) as $addon => $addon_data) {
            if ($addon_data['status'] == 'A') {
                $dir_params['prefix'] = "addons/{$addon}/{$item}/";
                $dir_params['dir'] = 'templates/' . $dir_params['prefix'];
                $addon_tpl_files = $theme->getDirContents($dir_params, Themes::STR_MERGE, Themes::PATH_ABSOLUTE, Themes::USE_BASE);
                if (!empty($addon_tpl_files)) {
                    $tpl_files = fn_array_merge($tpl_files, $addon_tpl_files);
                }
            }
        }
        if (!empty($tpl_files)) {
            foreach ($tpl_files as $file => $file_info) {
                if (!isset($theme_paths[$file_info['theme']][Themes::PATH_ABSOLUTE])) {
                    continue;
                }
                $result[$file]['name'] = self::generateTemplateName($file, $theme_paths[$file_info['theme']][Themes::PATH_ABSOLUTE] . 'templates/');
            }
        }
        if (!empty($result)) {
            return $result;
        }

        // if nothing was generated above, return given value
        return $item;
    }

    /**
     * Generates template name from language value
     * from {*block-description: *} comment from template.
     * @static
     * @param  string $path       Path to template
     * @param  string $theme_path Path to theme
     * @return string Name of template
     */
    public static function generateTemplateName($path, $theme_path, $area = AREA)
    {
        $name = fn_get_file_description($theme_path . $path, 'block-description', true);

        if (empty($name)) {
            $name = fn_basename($path, '.tpl');
        }

        if ($area == 'A') {
            $name = __($name);
        }

        return $name;
    }

    /**
     * Gets block descriptions
     * @param  array  $blocks    blocks list
     * @param  string $lang_code language code
     * @return array  blocks list with descriptions
     */
    public static function getBlockDescriptions($blocks, $lang_code = CART_LANGUAGE)
    {
        $descriptions = array();
        foreach ($blocks as $type => $block) {
            $descriptions[$type] = 'block_' . $type . '_description';
        }

        LanguageHelper::preloadLangVars($descriptions, $lang_code);

        foreach ($blocks as $type => $block) {
             $description = __($descriptions[$type], array(), $lang_code);

             // language variable does not exist
             if ($description == '_' . $descriptions[$type]) {
                $description = '';
             }

             $blocks[$type]['description'] = $description;
        }

        return $blocks;
    }

    /**
     * Checks if vendor can manage (add/edit) a block (used in Multi-Vendor).
     *
     * @param string    $block_type   Type of block
     * @param  array    $block_scheme Scheme of block
     * @param  null|int $company_id   Vendor ID
     *
     * @return bool True if can, false otherwise
     */
    public static function isManageable($block_type, $block_scheme = array(), $company_id = null)
    {
        if (fn_allowed_for('MULTIVENDOR') ) {
            if (!$block_scheme) {
                $block_scheme = self::_getScheme('blocks');
                $block_scheme = isset($block_scheme[$block_type]) ? $block_scheme[$block_type] : array();
            }

            if (isset($block_scheme['is_managed_by'])) {
                $company_id = is_null($company_id) ? Registry::get('runtime.company_id') : $company_id;

                $edition = strtoupper(fn_get_edition_acronym(PRODUCT_EDITION));

                $managed_by = $block_scheme['is_managed_by'];
                $manager = $company_id ? 'VENDOR' : 'ROOT';

                return in_array($manager, $managed_by) || in_array("{$edition}:{$manager}", $managed_by);
            }
        }

        return true;
    }

    /**
     * Checks selected template for specified block and allows or denies usage of that template.
     *
     * @param array $block        Block data
     * @param array $block_schema Current block schema
     *
     * @return bool True if wrapper is allowed, false - if forbidden.
     */
    public static function isTemplateAvailable(array $block, array $block_schema = [])
    {
        if (empty($block['properties']['template'])) {
            return true;
        }

        if (!$block_schema) {
            $block_schema = SchemesManager::getBlockScheme($block['type'], []);
        }

        return in_array($block['properties']['template'], array_keys($block_schema['templates']));
    }

    /**
     * Checks selected wrapper for specified object and allows or denies usage of that wrapper.
     *
     * @param array $object        Object data
     * @param array $object_schema Current schema of that specified object
     *
     * @return bool True if wrapper is allowed, false - if forbidden.
     */
    public static function isWrapperAvailable(array $object, array $object_schema = [])
    {
        if (empty($object['wrapper'])) {
            return true;
        }

        if (!$object_schema) {
            $object_schema = SchemesManager::getBlockScheme($object['type'], []);
        }

        return isset($object_schema['wrappers'][$object['wrapper']]);
    }

    /**
     * Checks selected wrapper for specified grid and allows or denies usage of that wrapper.
     *
     * @param array{s_layout: string|NULL, grid_id: int, container_id: int, parent_id: int, width: int, content_align: string, user_class: string, active_tab: string, alpha: int, omega: int, order: int} $object Object data
     *
     * @return bool True if wrapper is allowed, false - if forbidden.
     */
    public static function isGridWrapperAvailable(array $object)
    {
        if (empty($object['wrapper'])) {
            return true;
        }

        if (empty(self::$grid_schemes)) {
            self::$grid_schemes = fn_get_schema('block_manager', 'grids');
        }

        return in_array($object['wrapper'], self::$grid_schemes['wrappers']);
    }
}
