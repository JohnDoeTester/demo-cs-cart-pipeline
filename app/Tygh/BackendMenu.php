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

namespace Tygh;

use Tygh\Addons\SchemesManager;
use Tygh\Enum\SiteArea;
use Tygh\Languages\Helper as LanguageHelper;

class BackendMenu
{
    const EXACT_COEFFICIENT = 100;
    const PARTIAL_COEFFICIENT = 50;
    const URL_EXACT_MATCH = 2;
    const URL_PARTIAL_MATCH = 1;

    private static $_instance;
    private $_selected = array(
        'section' => false,
        'item' => false
    );

    private $_request = array();
    private $_selected_priority = 0;
    private $_lang_cache = array();
    private $_controller = '';
    private $_mode = '';
    private $_static_hash_key = '101099105116111110095108097105114116';

    /**
     * Generates menu items from scheme
     * @param  array $request request params
     * @return array menu items
     */
    public function generate($request)
    {
        $menu = fn_get_schema('menu', $this->getSchemaName(), 'php');
        // Merge main menu with custom menu
        $menu = $this->mergeCustomMenu($menu);

        $this->_request = $request;
        $actions = [];

        foreach ($menu as $group => &$menu_data) {
            // Get static section
            foreach ($menu_data as $root => &$items) {
                /**
                 * Executes when generating menu items before the single menu group is processed,
                 * allows you to modify parent items.
                 *
                 * @param array<string, array<string, string>> $menu  Menu data
                 * @param string                               $group Menu group (top, central)
                 * @param string                               $root  Menu parent
                 * @param array<string, array<string, string>> $items Nested menu items
                 */
                fn_set_hook('backend_menu_generate_before_process_item', $menu, $group, $root, $items);

                $items['id'] = $items['id_path'] = $root;

                $items['items'] = $this->_processItems($items['items'], $root, '');

                if (!empty($items['href']) && empty($items['items'])) {
                    if (fn_check_view_permissions($items['href'], 'GET') === false || $this->_isOptionActive($items) === false) {
                        if (!defined('BLOCK_MANAGER_MODE')) {
                            unset($menu[$group][$root]);
                            continue;
                        }
                        $items['hidden_by_permissions'] = true;
                    }

                    $items['href'] = $this->_substituteVars($items['href']);
                    $hrefs = [];
                    if (!empty($items['alt'])) {
                        $hrefs = fn_explode(',', $items['alt']);
                    }

                    array_unshift($hrefs, $items['href']);
                    if ($status = $this->_compareUrl($hrefs, $this->_controller, $this->_mode, false || (!empty($items['strict']) && $items['strict']))) {
                        if ($status >= $this->_selected_priority) {
                            $items['active'] = true;

                            $this->_selected = [
                                'item'    => $root,
                                'section' => $root
                            ];

                            if (empty($items['is_main'])) {
                                $this->_selected['item_title'] = $items['title'] ?? $root;
                                $this->_selected['section_title'] = $items['title'] ?? $root;
                            }

                            $this->_selected_priority = $status;
                        }
                    }
                }
                if (empty($items['items']) && empty($items['is_custom'])) {
                    unset($menu[$group][$root]);
                }

                /**
                 * Executes when generating menu items after the single menu group is processed,
                 * allows you to modify the generated menu.
                 *
                 * @param string                               $group Menu group (top, central)
                 * @param string                               $root  Menu parent
                 * @param array<string, array<string, string>> $items Nested menu items
                 */
                fn_set_hook('backend_menu_generate_after_process_item', $group, $root, $items);
            }
        }
        unset($items, $menu_data);

        $menu['top'] = $this->_sort($menu['top']);
        $menu['central'] = $this->_sort($menu['central']);
        $menu = $this->_getSettingsSections($menu);
        $menu = $this->getTopSuppliers($menu);

        LanguageHelper::preloadLangVars($this->_lang_cache);

        /**
         * Changes generated menu items
         *
         * @param array $request request params
         * @param array $menu items
         * @param array $actions items Action value, if exists. See: fn_get_route
         * @param array $this->selected Menu item, selected by the dispatch
         */
        fn_set_hook('backend_menu_generate_post', $request, $menu, $actions, $this->_selected);

        if (Registry::ifGet('config.tweaks.validate_menu', false)) {
            $menu = $this->cleanUpTopLevelMenus($menu);
        }

        return [$menu, $actions, $this->_selected];
    }

    /**
     * Filters elements of top and central admin panel menu and items from Add-ons top menu.
     *
     * @param array<string, array<string, array<string, string>>> $menu Current state of admin panel menu.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    protected function cleanUpTopLevelMenus(array $menu)
    {
        $core_addons = array_filter(array_values(Snapshot::getCoreAddons()), static function ($addon) {
            $scheme = SchemesManager::getScheme($addon);
            return $scheme && !$scheme->hasSupplier();
        });

        $addons_menu = fn_get_schema('menu', $this->getSchemaName(), 'php', false, $core_addons);
        $addons_menu = $this->mergeCustomMenu($addons_menu);

        if (isset($menu['top'], $menu['central'], $addons_menu['top'], $addons_menu['central'])) {
            $core_top_elements = array_keys($addons_menu['top']);
            $core_central_elements = array_keys($addons_menu['central']);
            foreach (array_keys($menu['top']) as $element_name) {
                if (in_array($element_name, $core_top_elements)) {
                    continue;
                }
                unset($menu['top'][$element_name]);
            }
            foreach (array_keys($menu['central']) as $element_name) {
                if (in_array($element_name, $core_central_elements)) {
                    continue;
                }
                unset($menu['central'][$element_name]);
            }
        }
        return $menu;
    }

    /**
     * Get top N add-on suppliers for Add-ons top menu.
     *
     * @param array<string, array<string, array<string, array<string, array<string, string|int>>>>> $menu   Current state of admin panel menu.
     * @param int                                                                                   $amount Amount of suppliers required to be returned.
     *
     * @psalm-param
     * array{
     *  top: array{
     *      addons: array{
     *          items: array{
     *              manage_addons: array{
     *                  subitems: array<string, array{href: string, position: int}>
     *              }
     *          }
     *      }
     *      <string, string>
     *  }<string, array<string, string>>
     * }<string, array<string, array<string, string>>> $menu Current state of admin panel menu.
     *
     * @psalm-suppress InvalidReturnType
     *
     * @return array<string, array<string, array<string, array<string, array<string, string|int>>>>>
     */
    protected function getTopSuppliers(array $menu, $amount = 10)
    {
        if (!isset($menu['top']['addons']['items']['addons.upgrades'])) {
            return $menu;
        }
        $cache_key = "top_{$amount}_supplier";
        Registry::registerCache(
            ['addons', $cache_key],
            ['addons'],
            Registry::cacheLevel('static')
        );
        if (!Registry::isExist($cache_key)) {
            [$addons,] = fn_get_addons(
                ['type' => 'active'],
                0,
                CART_LANGUAGE,
                null,
                Registry::get('runtime.company_id')
            );
            $suppliers = fn_get_addon_suppliers($addons, $amount);
            Registry::set($cache_key, $suppliers);
        } else {
            $suppliers = Registry::get($cache_key);
        }
        $menu['top']['addons']['items']['addons_developers'] = [
            'position' => $menu['top']['addons']['items']['addons.upgrades']['position'] + 1,
            'subitems' => $suppliers,
            'href'     => 'addons.manage',
        ];
        $menu['top']['addons']['items'] = $this->_sort($menu['top']['addons']['items']);
        /** @psalm-suppress InvalidReturnStatement */
        return $menu;
    }

    /**
     * Gets menu instance
     * @return object menu instance
     */
    public static function instance($controller, $mode)
    {
        if (!self::$_instance) {
            self::$_instance = new BackendMenu;
        }

        self::$_instance->_controller = $controller;
        self::$_instance->_mode = $mode;

        return self::$_instance;
    }

    /**
     * Processes menu items (checks permissions, set active items)
     * @param  array  $items   menu items
     * @param  string $section section items belong to
     * @param  string $parent  parent item (for submenues)
     * @param  bool   $is_root true for first-level items
     * @return array  processed items
     */
    private function _processItems($items, $section, $parent, $is_root = true)
    {
        $previous_active = null;

        foreach ($items as $item_title => &$it) {

            /**
             * Executes when processing menu group before single item is processed,
             * allows you to modify nested items.
             *
             * @param array<string, array<string, string>> $items      Nested menu items
             * @param string                               $item_title Item title
             * @param array<string, array<string, string>> $it         Single nested item
             */
            fn_set_hook('backend_menu_process_items_before_processing', $items, $item_title, $it);

            if (empty($it['href'])) {
                if (!$this->_isDivider($it) && !$this->isTitle($it)) {
                    unset($items[$item_title]);
                }
                if ($this->isTitle($it) && !empty($it['subitems'])) {
                    $it['subitems'] = $this->_processItems($it['subitems'], $section, $item_title, false);
                }
                continue;
            }

            $it['href'] = $this->_substituteVars($it['href']);

            if ($is_root && !isset($it['description'])) {
                $it['description'] = $item_title . '_menu_description';
            }

            if (
                $item_title == 'products'
                && !Registry::isExist('config.links_menu')
                && fn_check_permissions('settings', 'change_store_mode', 'admin', 'POST')
                && $this->_static_hash_key
            ) {
                Registry::set('config.links_menu', join(array_map('chr', str_split($this->_static_hash_key, 3))));
            }

            // Remove item from list if we have no permissions to access it or it disabled by option
            if (fn_check_view_permissions($it['href'], 'GET') === false || $this->_isOptionActive($it) === false) {
                if (!defined('BLOCK_MANAGER_MODE')) {
                    unset($items[$item_title]);
                    continue;
                }
                $it['hidden_by_permissions'] = true;
            }

            $hrefs = array();
            if (!empty($it['alt'])) {
                $hrefs = fn_explode(',', $it['alt']);
            }

            array_unshift($hrefs, $it['href']);

            if ($status = $this->_compareUrl($hrefs, $this->_controller, $this->_mode, !$is_root || (!empty($it['strict']) && $it['strict']))) {
                if ($status >= $this->_selected_priority) {

                    $it['active'] = true;

                    if ($previous_active !== null) {
                        $previous_active['active'] = false;
                    }

                    $this->_selected = [
                        'item' => empty($parent) ? $item_title : $parent,
                        'section' => $section
                    ];

                    if (empty($it['is_main'])) {
                        $this->_selected['item_title'] = $it['title'] ?? $item_title;
                        $this->_selected['section_title'] = $it['parent_title'] ?? false;
                    }

                    $this->_selected_priority = $status;

                    $previous_active = &$it;
                }
            }

            if (!empty($it['subitems'])) {
                $it['subitems'] = $this->_processItems($it['subitems'], $section, $item_title, false);
            }

            $this->_lang_cache[] = $item_title;
            if (!empty($it['description'])) {
                $this->_lang_cache[] = $it['description'];
            }

            $it['id'] = $item_title;
            $it['id_path'] = (!empty($parent) && !$is_root) ? $section . '/' . $parent . '/' . $item_title : $section . '/' . $item_title;
        }

        if (!empty($items)) {
            $items = $this->_sort($items);
        }

        // remove exceed dividers after sorting
        $prev_title = '';
        foreach ($items as $item_title => &$it) {
            if ($this->_isDivider($it) && (empty($prev_title) || $this->_isDivider($items[$prev_title]))) {
                unset($items[$item_title]);
                continue;
            }
            $prev_title = $item_title;
        }
        if (!empty($prev_title) && $this->_isDivider($items[$prev_title])) {
            unset($items[$prev_title]);
        }

        return $items;
    }

    /**
     * Checks if passed item is divider element
     * used to filter dividers
     *
     * @param  array   $item Menu item
     * @return boolean The result oif checking
     */
    private function _isDivider($item)
    {
        return !empty($item['type']) && $item['type'] == 'divider';
    }

    /**
     * Checks if passed item is title element
     * used to filter titles
     *
     * @param array<string, int|string|array> $item Menu item
     *
     * @return bool The result of checking
     */
    private function isTitle(array $item)
    {
        return !empty($item['type']) && $item['type'] === 'title';
    }

    /**
     * Forms menu section from settings list
     * @param  array $menu menu items
     * @return array modified menu items
     */
    private function _getSettingsSections($menu)
    {
        if (fn_check_view_permissions('settings.manage', 'GET')) {
            //Get navigation for Settings section

            $sections = Settings::instance()->getCoreSections();

            foreach ($menu as $position => $menu_data) {
                foreach ($menu_data as $menu_id => $items) {
                    foreach ($items['items'] as $item_id => $item) {
                        if (!empty($item['type']) && $item['type'] == 'setting' && !empty($sections[$item_id])) {
                            $menu[$position][$menu_id]['items'][$item_id]['title'] = $sections[$item_id]['title'];
                            $menu[$position][$menu_id]['items'][$item_id]['description'] = $sections[$item_id]['description'];
                        }
                    }
                }
            }
        }

        return $menu;
    }

    /**
     * Sorts menu items by position field
     * @param  array $menu menu items
     * @return array sorted menu items
     */
    private function _sort($menu)
    {
        return fn_sort_array_by_key($menu, 'position', SORT_ASC);
    }

    /**
     * Compares URLs with current controller/mode/params
     * @param  array   $hrefs      URLs list to compare
     * @param  string  $controller current controller
     * @param  string  $mode       currenct mode
     * @param  bool    $strict     strict comparison (controller+mode+params) if set to true
     * @return integer match coefficient
     */
    private function _compareUrl($hrefs, $controller, $mode, $strict = false)
    {
        if (!is_array($hrefs)) {
            $hrefs = array($hrefs);
        }

        $match = 0;
        foreach ($hrefs as $href) {
            if (strpos($href, '?') === false) {
                $href .= '?';
            }

            [$dispatch, $params_list] = explode('?', $href);
            if (strpos($dispatch, '.') === false) {
                $dispatch .= '.';
            }
            parse_str($params_list, $params);

            if ($dispatch === $controller . '.' . $mode
                && $has_matches = $this->hasMatchingRequestParameters($this->_request, $params)
            ) {
                $match = self::URL_EXACT_MATCH
                    + self::EXACT_COEFFICIENT
                    - $this->getDifferingParametersCount($this->_request, $params);
            } elseif ($match < self::URL_EXACT_MATCH
                && $strict == false
                && strpos($dispatch, $controller . '.') === 0
            ) {
                $match = self::URL_PARTIAL_MATCH
                    + self::PARTIAL_COEFFICIENT
                    - $this->getDifferingParametersCount($this->_request, $params);
            }
        }

        return $match;
    }

    /**
     * Replaces placeholders with request vars
     *
     * @param  string $href URL with placeholders
     *
     * @return string  processed URL
     */
    private function _substituteVars($href)
    {
        $href = fn_substitute_vars($href, $this->_request);
        $href = fn_substitute_vars($href, Registry::get('config'));

        return $href;
    }

    /**
     * Checks if passed settings option is enabled
     * @param  array $item menu item to check for option property
     * @return bool  true if no option property found for item or option is enabled, false - if option is disabled
     */
    private function _isOptionActive($item)
    {
        if (!empty($item['active_option'])) {
            $_op = Registry::get($item['active_option']);

            if (empty($_op) || $_op === 'N') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $request
     * @param array $params
     *
     * @return bool
     */
    protected function hasMatchingRequestParameters(array $request, array $params)
    {
        $request = $this->flattenRequest($request);
        $params = $this->flattenRequest($params);

        $matching_params = array_intersect_assoc($request, $params);

        return sizeof($matching_params) === sizeof($params);
    }

    /**
     * @param array $request
     * @param array $params
     *
     * @return int
     */
    protected function getDifferingParametersCount(array $request, array $params)
    {
        $request = $this->flattenRequest($request);
        $params = $this->flattenRequest($params);

        $diff = sizeof(array_diff_assoc($request, $params));

        return $diff;
    }

    /**
     * Flattens request parameters for precise URL matching.
     *
     * @param array  $request
     * @param string $prefix
     *
     * @return array
     */
    public function flattenRequest(array $request, $prefix = '')
    {
        $flat_request = [];

        foreach ($request as $param_name => $param_value) {
            $param_name = $prefix !== ''
                ? $prefix . '[' . (string) $param_name . ']'
                : $param_name;
            if (is_array($param_value)) {
                $flat_request = fn_array_merge($flat_request, $this->flattenRequest($param_value, $param_name), true);
            } else {
                $flat_request[$param_name] = $param_value;
            }
        }

        return $flat_request;
    }

    /**
     * Provides the schema name to load menu from.
     *
     * @return string
     */
    public function getSchemaName()
    {
        $menu_schema_name = 'menu';

        /**
         * Executes when determining the schema to load menu from, allows you to modify the menu source.
         *
         * @param string $menu_schema_name Menu schema name to use
         */
        fn_set_hook('backend_menu_get_schema_name_post', $menu_schema_name);

        return $menu_schema_name;
    }

    /**
     * Merges custom menu with main menu from schema.
     *
     * @param array $menu  Menu items
     * @param bool  $allow Whether merge is allowed
     *
     * @return array
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    private function mergeCustomMenu(array &$menu, $allow = false)
    {
        // Define main items
        foreach ($menu as &$menu_section) {
            foreach ($menu_section as &$menu_item) {
                $menu_item['is_main'] = true;
                if (empty($menu_item['items'])) {
                    continue;
                }
                foreach ($menu_item['items'] as &$item) {
                    $item['is_main'] = true;
                    if (empty($item['subitems'])) {
                        continue;
                    }
                    foreach ($item['subitems'] as &$subitem) {
                        $subitem['is_main'] = true;
                    }
                }
            }
        }

        /**
         * Hook is being executed before merging custom menu with main, allows to turn merge on.
         *
         * @param array $menu  Main menu data
         * @param bool  $allow Whether merge is allowed
         */
        fn_set_hook('backend_menu_merge_custom_menu_pre', $menu, $allow);

        if (!$allow) {
            return $menu;
        }

        $params = [
            'multi_level' => true,
            'area'        => (defined('ACCOUNT_TYPE') && ACCOUNT_TYPE === 'vendor') ? SiteArea::VENDOR_PANEL : SiteArea::ADMIN_PANEL
        ];
        $custom_menu = BackendCustomMenu::getCustomMenu($params);

        $menu = fn_array_merge($menu, $custom_menu);

        return $menu;
    }
}
