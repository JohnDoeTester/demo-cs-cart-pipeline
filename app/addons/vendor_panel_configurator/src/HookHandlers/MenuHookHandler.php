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

namespace Tygh\Addons\VendorPanelConfigurator\HookHandlers;

use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\UserTypes;
use Tygh\Tygh;

class MenuHookHandler
{
    /** @var array<string, string> */
    protected $menu_schema_names = [
        UserTypes::ADMIN         => 'menu',
        UserTypes::VENDOR        => 'menu_vendor',
        self::FALLBACK_USER_TYPE => 'menu',
    ];

    const FALLBACK_USER_TYPE = '__default';

    /** @var string */
    protected $user_type = UserTypes::ADMIN;

    /**
     * MenuHookHandler constructor.
     *
     * @param string $user_type Current user type
     */
    public function __construct($user_type)
    {
        $this->user_type = $user_type;
    }

    /**
     * The "backend_menu_get_schema_name_post" hook handler.
     *
     * Actions performed:
     * - Replaces schema to load menu from.
     *
     * @param string $menu_schema_name Menu schema name to use
     *
     * @return void
     *
     * @see \Tygh\BackendMenu::getSchemaName()
     */
    public function onAfterGetSchemaName(&$menu_schema_name)
    {
        $menu_schema_name = isset($this->menu_schema_names[$this->user_type])
            ? $this->menu_schema_names[$this->user_type]
            : $this->menu_schema_names[self::FALLBACK_USER_TYPE];
    }

    /**
     * The "backend_menu_generate_before_process_item" hook handler.
     *
     * Actions performed:
     * - Removes root item from menu data if it is disabled.
     *
     * @param array<string, array<string, string>>        $menu  Menu data
     * @param string                                      $group Menu group (top, central)
     * @param string                                      $root  Menu parent
     * @param array<string, string|array<string, string>> $items Nested menu items
     *
     * @return void
     *
     * @see \Tygh\BackendMenu::generate()
     */
    public function onBeforeGenerateItem(array &$menu, $group, $root, array $items)
    {
        if (
            !UserTypes::isVendor($this->user_type)
            || defined('BLOCK_MANAGER_MODE')
            || empty($items['status'])
            || $items['status'] !== ObjectStatuses::DISABLED
        ) {
            return;
        }
        unset($menu[$group][$root]);
    }

    /**
     * The "backend_menu_generate_after_process_item" hook handler.
     *
     * Actions performed:
     * - Moves nested items from the menu to the top level menu when menu has only one nested item.
     * - Removes subitems from menu items that would not be moved to the upper level.
     *
     * @param string $group Menu group (top, central)
     * @param string $root  Menu parent
     * @param array  $items Nested menu items
     *
     * @return void
     *
     * @see \Tygh\BackendMenu::generate()
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function onAfterGenerateItem($group, $root, array &$items)
    {
        if (
            !UserTypes::isVendor($this->user_type)
            || $group !== 'central'
            || !isset($items['items'])
        ) {
            return;
        }
        if (!defined('BLOCK_MANAGER_MODE')) {
            if (count($items['items']) === 1) {
                // Menus with single nested item are useless. Unwrap them into top-level menu
                $items = $this->movesMenuItemToUpperLevel($items);
            } else {
                $items = $this->removesMenuSubitems($items);
            }
        } else {
            $active_count = 0;
            foreach ($items['items'] as $item) {
                if (!empty($item['status']) && $item['status'] !== ObjectStatuses::ACTIVE) {
                    continue;
                }
                $active_count++;
            }
            if ($active_count !== 1) {
                $items['root_hidden'] = false;
            } else {
                $items['root_hidden'] = true;
            }

            if (defined('AJAX_REQUEST')) {
                $ajax_data = Tygh::$app['ajax']->getAssignedVar('data');

                if (!empty($ajax_data['id_path'])) {
                    $current_id_path = explode('/', $ajax_data['id_path']);
                    $current_root_id = reset($current_id_path);

                    if ($root === $current_root_id) {
                        Tygh::$app['ajax']->assign('data', $items);
                    }
                }
            }
        }
    }


    /**
     * The "backend_menu_process_items_before_processing" hook handler.
     *
     * Actions performed:
     * - Removes nested item from menu data if it is disabled.
     *
     * @param array<string, array<string, string>> $items      Nested menu items
     * @param string                               $item_title Item title
     * @param array<string, array<string, string>> $it         Single nested item
     *
     * @return void
     *
     * @see \Tygh\BackendMenu::_processItems()
     */
    public function onBeforeProcessNestedItem(array &$items, $item_title, array $it)
    {
        if (
            !UserTypes::isVendor($this->user_type)
            || defined('BLOCK_MANAGER_MODE')
            || empty($it['status'])
            || $it['status'] !== ObjectStatuses::DISABLED
        ) {
            return;
        }
        unset($items[$item_title]);
    }

    /**
     * The "backend_menu_merge_custom_menu_pre" hook handler.
     *
     * Actions performed:
     * - Executed before merging custom menu with main, allows to turn merge on.
     *
     * @param array<string, string|array<string, string>> $menu  Main menu data
     * @param bool                                        $allow Whether merge is allowed
     *
     * @return void
     *
     * @see \Tygh\BackendMenu::mergeCustomMenu()
     */
    public function onBeforeMergeCustomMenu($menu, &$allow)
    {
        if (!defined('ACCOUNT_TYPE') || ACCOUNT_TYPE !== 'vendor') {
            return;
        }

        $allow = true;
    }


    /**
     * Moves nested items from the menu to the top level menu when menu has only one nested item.
     *
     * @param array<string, string|array<string, string>> $items Nested menu items
     *
     * @return array<string, string|array<string, string>>
     */
    private function movesMenuItemToUpperLevel(array $items)
    {
        $root_item = reset($items['items']);
        $possible_title = key($items['items']);
        $root_item['items'] = [];
        if (isset($items['position'])) {
            $root_item['position'] = $items['position'];
        }
        if (isset($root_item['root_title'])) {
            $root_item['title'] = $root_item['root_title'];
        } elseif (!isset($root_item['title'])) {
            $root_item['title'] = __($possible_title);
        }

        return $root_item;
    }

    /**
     * Moves nested items from the menu to the top level menu when menu has only one nested item.
     *
     * @param array<string, string|array<string, string>> $items Nested menu items
     *
     * @return array<string, string|array<string, string>>
     */
    private function removesMenuSubitems(array $items)
    {
        if (!is_array($items['items'])) {
            return $items;
        }
        foreach ($items['items'] as &$second_level_item) {
            unset($second_level_item['subitems']);
        }
        unset($second_level_item);

        return $items;
    }
}
