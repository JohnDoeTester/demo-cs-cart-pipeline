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

use Tygh\Enum\BackendMenuLocations;
use Tygh\Languages\Languages;

class BackendCustomMenu
{
    /**
     * Gets block manager custom block data
     *
     * @param string $id_path ID path
     * @param array  $params  Extra parameters
     *
     * @return array
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public static function getCustomMenuItem($id_path, array $params = [])
    {
        if (empty($params['location'])) {
            $params['location'] = BackendMenuLocations::CENTRAL;
        }

        [$menu_schema,] = BackendMenu::instance('index', 'index')->generate([]);

        $item = static::findCustomMenuItem($menu_schema[$params['location']], $id_path);

        $item['block_id'] = $item['id_path'];
        if (!isset($item['title'])) {
            $item['title'] = __($item['id']);
        }

        return $item;
    }

    /**
     * Convert multi-level array of backend menu to plain representation
     *
     * @param array  $data    Source array
     * @param string $id_path ID path
     *
     * @return array Structured data
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    private static function findCustomMenuItem(array $data, $id_path)
    {
        $res = [];
        foreach ($data as $item) {
            if ($res) {
                return $res;
            }
            if ($item['id_path'] === $id_path) {
                return $item;
            }
            if (!empty($item['items'])) {
                $res = static::findCustomMenuItem($item['items'], $id_path);
            } elseif (!empty($item['subitem'])) {
                $res = static::findCustomMenuItem($item['subitems'], $id_path);
            }
        }

        return $res;
    }

    /**
     * Get custom menu
     *
     * @param array<string|bool> $params    Custom menu params
     * @param string             $lang_code Language code
     *
     * @return array
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function getCustomMenu(array $params, $lang_code = DESCR_SL)
    {
        $fields = [
            'cm.item_id',
            'cm.href',
            'cm.new_window',
            'cm.status',
            'cm.location',
            'cm.position',
            'cm.parent_id',
            'cm.id_path',
            'cmd.title',
        ];

        $condition = '';

        if (!empty($params['area'])) {
            $condition .= db_quote(' AND cm.area = ?s', $params['area']);
        }

        if (!empty($params['status'])) {
            $condition .= db_quote(' AND cm.status = ?s', $params['status']);
        }

        if (!empty($params['location'])) {
            $condition .= db_quote(' AND cm.location = ?s', $params['location']);
        }

        $fields = implode(', ', $fields);
        $custom_menu = db_get_array(
            'SELECT ?p, "Y" as is_custom FROM ?:custom_menu AS cm'
            . ' LEFT JOIN ?:custom_menu_descriptions AS cmd ON cm.item_id = cmd.item_id AND cm.id_path = cmd.id_path'
            . ' AND cmd.lang_code = ?s WHERE 1=1 ?p ORDER BY cm.position',
            $fields,
            $lang_code,
            $condition
        );

        // Preparing items for merge (TODO?)
        foreach ($custom_menu as &$item) {
            $item['block_id'] = $item['id'] = $item['item_id'];
            unset($item['item_id']);
            if (empty($item['position'])) {
                unset($item['position']);
            }
            if (empty($item['href'])) {
                unset($item['href']);
            }
            if (!empty($item['title'])) {
                continue;
            }
            unset($item['title']);
        }

        if (!empty($params['multi_level'])) {
            $custom_menu = static::buildMultiLevelCustomMenu($custom_menu);
        }

        return $custom_menu;
    }

    /**
     * Update custom menu
     *
     * @param array  $data      Block data
     * @param string $lang_code Two-letters language code
     *
     * @return void
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public static function updateCustomMenu(array $data, $lang_code = DESCR_SL)
    {
        $item_id = $data['item_id'] = !empty($data['block_id']) ? $data['block_id'] : 0;

        static::addCustomMenuItemParents($item_id, $data['id_path']);

        if (!empty($item_id)) {
            if (!empty($data['id_path'])) {
                $id_path_array = explode('/', $data['id_path']);
                array_pop($id_path_array);
                if (!empty($id_path_array)) {
                    $data['parent_id'] = end($id_path_array);
                } else {
                    $data['parent_id'] = 0;
                }
            } else {
                $data['parent_id'] = 0;
                $data['id_path'] = $item_id;
            }

            $custom_item_exists = db_get_field(
                'SELECT COUNT(*) FROM ?:custom_menu AS cm WHERE cm.item_id = ?s AND cm.id_path = ?s',
                $item_id,
                $data['id_path']
            );

            if ($custom_item_exists) {
                db_query('UPDATE ?:custom_menu SET ?u WHERE item_id = ?s AND id_path = ?s', $data, $item_id, $data['id_path']);

                $item_descr_exists = db_get_field(
                    'SELECT COUNT(*) FROM ?:custom_menu_descriptions WHERE item_id = ?s AND id_path = ?s AND lang_code = ?s',
                    $item_id,
                    $data['id_path'],
                    $lang_code
                );

                if ($item_descr_exists) {
                    db_query(
                        'UPDATE ?:custom_menu_descriptions SET ?u WHERE item_id = ?s AND id_path = ?s AND lang_code = ?s',
                        $data,
                        $item_id,
                        $data['id_path'],
                        $lang_code
                    );
                } elseif (!empty($data['title'])) {
                    $data['lang_code'] = $lang_code;
                    db_query('REPLACE INTO ?:custom_menu_descriptions ?e', $data);
                }
            } else {
                db_query('INSERT INTO ?:custom_menu ?e', $data);
                $data['lang_code'] = $lang_code;
                db_query('REPLACE INTO ?:custom_menu_descriptions ?e', $data);
            }
        } else {
            $item_id = $data['item_id'] = uniqid('item_');

            $data['parent_id'] = !empty($data['id_path']) ? $data['id_path'] : 0;
            $data['id_path'] = !empty($data['id_path']) ? $data['id_path'] . '/' . $item_id : $item_id;

            db_query('INSERT INTO ?:custom_menu ?e', $data);
            foreach (array_keys(Languages::getAll()) as $data['lang_code']) {
                db_query('REPLACE INTO ?:custom_menu_descriptions ?e', $data);
            }
        }
    }

    /**
     * Adds parents for updated menu elements for further correct merge.
     *
     * @param int|string $item_id Item ID
     * @param string     $id_path ID path for item
     *
     * @return void
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public static function addCustomMenuItemParents($item_id, $id_path)
    {
        if (empty($id_path)) {
            return;
        }
        $id_path_array = explode('/', $id_path);
        $parents = [];

        if (empty($item_id) || count($id_path_array) > 1) {
            if (!empty($item_id)) {
                array_pop($id_path_array);
            }
            $_parent_id = 0;
            foreach ($id_path_array as $key => $elem) {
                if (!empty($parents)) {
                    $parents[$key]['id_path'] = $parents[$key - 1]['id_path'] . '/' . $elem;
                } else {
                    $parents[$key]['id_path'] = $elem;
                }
                $parents[$key]['item_id'] = $elem;
                $parents[$key]['parent_id'] = $_parent_id;
                $_parent_id = $elem;
            }
        }
        unset($_parent_id);

        foreach ($parents as $parent) {
            if (empty($parent['item_id'])) {
                continue;
            }
            $parent_exists = db_get_field(
                'SELECT COUNT(*) FROM ?:custom_menu AS cm WHERE cm.item_id = ?s AND cm.id_path = ?s',
                $parent['item_id'],
                $parent['id_path']
            );
            if ($parent_exists) {
                continue;
            }

            db_query('INSERT INTO ?:custom_menu ?e', $parent);
        }
    }

    /**
     * Convert custom menu to multilevel format
     *
     * @param array $menu_items Custom menu items
     *
     * @return array
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    private static function buildMultiLevelCustomMenu(array &$menu_items)
    {
        if (empty($menu_items)) {
            return [];
        }

        $menu_locations = BackendMenuLocations::getAll();

        $custom_menu = [];
        // Sort items by locations
        foreach ($menu_locations as $menu_location) {
            foreach ($menu_items as $item_key => $item) {
                if ($item['location'] !== $menu_location) {
                    continue;
                }
                $custom_menu[$menu_location][$item_key] = $item;
            }
        }

        // Convert array to multi level form
        foreach ($menu_locations as $menu_location) {
            if (empty($custom_menu[$menu_location])) {
                continue;
            }

            $custom_menu[$menu_location] = static::buildCustomMenuTree($custom_menu[$menu_location], 0, 'id_path', 'items');
        }

        return $custom_menu;
    }

    /**
     * Convert custom menu to multilevel tree
     *
     * @param array      $tree       Items
     * @param int|string $id_path    ID path
     * @param string     $key        Single item id
     * @param string     $parent_key Subitems array key
     *
     * @return array
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    private static function buildCustomMenuTree(array $tree, $id_path, $key, $parent_key)
    {
        $res = [];
        foreach ($tree as $row) {
            $id_path_array = explode('/', $row['id_path']);
            if ($row['id_path'] !== $id_path . '/' . $row['id'] && ($id_path !== 0 || count($id_path_array) > 1)) {
                continue;
            }
            $res[$row['id']] = $row;
            if ($id_path !== 0) {
                $parent_index = array_search($id_path, array_column($tree, 'id_path'));
                $res[$row['id']]['parent_title'] = $tree[$parent_index]['title'] ?? false;
            }
            if (count($id_path_array) > 1) {
                $res[$row['id']]['subitems'] = static::buildCustomMenuTree($tree, $row[$key], $key, 'subitems');
            } else {
                $res[$row['id']][$parent_key] = static::buildCustomMenuTree($tree, $row[$key], $key, $parent_key);
            }
        }

        return $res;
    }

    /**
     * Delete custom menu item(s)
     *
     * @param array<int|string> $id_paths ID paths of menu items
     *
     * @return int|bool
     */
    public static function deleteCustomMenu($id_paths)
    {
        if (empty($id_paths)) {
            return false;
        }

        if (!is_array($id_paths)) {
            $id_paths = (array) $id_paths;
        }

        // Add children items for deletion if they exist
        $child_ids = [];
        foreach ($id_paths as $id_path) {
            $id_path_array = explode('/', (string) $id_path);
            if (count($id_path_array) > 1) {
                continue;
            }
            $child_ids[] = db_get_array('SELECT id_path FROM ?:custom_menu WHERE parent_id IN (?a)', $id_path_array[0]);
        }

        if (!empty($child_ids)) {
            foreach ($child_ids as $_child_ids) {
                $id_paths = array_merge(array_column($_child_ids, 'id_path'), $id_paths);
            }
        }

        db_query('DELETE FROM ?:custom_menu_descriptions WHERE id_path IN (?a)', $id_paths);
        return db_query('DELETE FROM ?:custom_menu WHERE id_path IN (?a)', $id_paths);
    }
}
