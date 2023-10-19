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

use Tygh\Enum\ContainerPositions;
use Tygh\Registry;

/**
 * Container class
 */
class Container
{
    use TDeviceAvailabiltiy;

    /**
     * Gets list of containers
     *
     * @param  array $params input params
     * @return array Array of containers data as position => data
     */
    public static function getList($params = array())
    {
        $default_params = array(
            'company_id' => 0,
            'container_id' => 0,
        );
        $params = array_merge($default_params, $params);

        $fields = array(
            'c.*'
        );

        $join = $condition = '';

        if (!empty($params['location_id'])) {
            $condition .= db_quote(" AND c.location_id = ?i", $params['location_id']);
        }

        if (!empty($params['container_id'])) {
            $condition .= db_quote(" AND c.container_id = ?i", $params['container_id']);
        }

        if (!empty($params['position'])) {
            $condition .= db_quote(" AND c.position IN (?a)", (array)$params['position']);
        }

        if (!empty($params['default_location'])) {
            $layout_id = db_get_field("SELECT layout_id FROM ?:bm_locations WHERE location_id = ?i", $params['default_location']);
            $join .= db_quote(" INNER JOIN ?:bm_locations as l ON c.location_id = l.location_id AND l.is_default = 1 AND l.layout_id = ?i", $layout_id);
            $condition .= db_quote(" AND c.position IN (?s, ?s, ?s)", ContainerPositions::TOP_PANEL, ContainerPositions::HEADER, ContainerPositions::FOOTER);

            $fields[] = db_quote('IF (c.location_id != ?i, 0, 1) as `default`', $params['default_location']);
        }

        if (fn_allowed_for('MULTIVENDOR') && !$params['container_id']) {
            $condition .= db_quote(' AND c.company_id = ?i', $params['company_id']);
        }

        $containers = db_get_hash_array(
            "SELECT " . implode(', ', $fields) . " FROM ?:bm_containers as c ?p WHERE 1 ?p",
            'position',
            $join,
            $condition
        );

        foreach ($containers as &$container) {
            $container['availability'] = static::getAvailabilityInstance()->getAvailability($container);
        }
        unset($container);

        return $containers;
    }

    /**
     * Gets list of containers from the location with <i>$location_id</i> for admin area,
     * or top, header and footer containers from the default location and center
     * container from location with <i>$location_id</i> for customer area.
     *
     * @param int          $location_id           Location identifier
     * @param string       $area                  Area ('A' for admin or 'C' for customer)
     * @param array|string $positions             Positions to get containers for
     * @param array        $dynamic_object        Viewed dynamic object data (if any)
     * @param array        $dynamic_object_scheme Viewed dynamic object scheme (if any)
     *
     * @return array  Array of containers data as position => data
     */
    public static function getListByArea($location_id, $area = AREA, $positions = array(), $dynamic_object = array(), $dynamic_object_scheme = array())
    {
        $location_containers_params = array(
            'location_id' => $location_id,
            'position' => $positions,
        );
        $default_containers_params = array(
            'default_location' => $location_id,
            'position' => $positions,
        );

        $containers = self::_overrideByDefault(
            self::getList($location_containers_params),
            self::getList($default_containers_params),
            $area
        );

        if ($location_containers_params['company_id'] = fn_get_blocks_owner()) {
            $containers = self::overrideByVendor($containers, $location_containers_params);
        }

        $containers = self::addBlockManagerProperties($containers, $dynamic_object, $dynamic_object_scheme);

        return $containers;
    }

    /**
     * Gets container data by id
     *
     * @param  int   $container_id Container identifier
     * @return array Array of container data
     */
    public static function getById($container_id)
    {
        $container = self::getList(array(
            'container_id' => $container_id
        ));

        return !empty($container) ? array_pop($container) : array();
    }

    /**
     * Gets identifiers of containers from array of containers data as position => data
     *
     * @param  array $containers Array of containers data as position => data
     * @return array Array of containers ids
     */
    public static function getIds($containers)
    {
        $container_ids = array();

        if (is_array($containers)) {
            $container_ids = array_column($containers, 'container_id');
        }

        return $container_ids;
    }

    /**
     * Creates or updates container.
     * <i>$container_data</i> must be array with this fields:
     * <pre>array (
     *  container_id,
     *  location_id,
     *  position (TOP_PANEL | HEADER | CONTENT | FOOTER),
     *  width (12 | 16)
     * )</pre>
     *
     * @param  array $container_data array of container data
     *
     * @return int|bool Container id if new grid was created, DB result otherwise
     */
    public static function update($container_data)
    {
        return db_replace_into('bm_containers', $container_data);
    }

    /**
     * Performs a cleanup: removes container related data
     *
     * @return bool Always true
     */
    public static function removeMissing()
    {
        // Remove missing blocks
        db_remove_missing_records('bm_containers', 'location_id', 'bm_locations');

        return true;
    }

    /**
     * Copies containers/grids/snappings from one location to another
     * @param int $location_id     source location ID
     * @param int $new_location_id target location ID
     */
    public static function copy($location_id, $new_location_id)
    {
        $containers = self::getList(array(
            'location_id' => $location_id
        ));

        foreach ($containers as $container) {
            $container_id = $container['container_id'];
            unset($container['container_id']);

            $container['location_id'] = $new_location_id;
            $new_container_id = db_query("INSERT INTO ?:bm_containers ?e", $container);

            Grid::copy($container_id, $new_container_id);
        }
    }

    /**
     * Override top, header and footer containers with the ones from the default location in customer area; only for the default location in the admin area
     *
     * @param  array  $containers     Array of containers data as position => data
     * @param  array  $def_containers Array of containers data from default location as position => data
     * @param  string $area           Area ('A' for admin or 'C' for customer)
     * @return array  Array of containers data as position => data
     */
    private static function _overrideByDefault($containers, $def_containers, $area)
    {
        $_containers = array();

        foreach ($containers as $position => $container) {
            $_containers[$position] = $container;
            if ($area == 'C') {
                // Always override by default containers
                if (!empty($def_containers[$position]) && $container['linked_to_default'] == 'Y') {
                    $_containers[$position] = $def_containers[$position];
                }
            } elseif ($area == 'A') {
                // Override by default containers only for default page
                if (isset($def_containers[$position]['default']) && $def_containers[$position]['default'] == 1) {
                    $_containers[$position] = $def_containers[$position];
                }
            }
        }

        return $_containers;
    }

    /**
     * Copies container from default layout to a vendor (used in Multi-Vendor).
     *
     * @param int  $container_id Container ID
     * @param int  $company_id   Vendor ID
     * @param int  $location_id  Location ID to copy container to
     * @param bool $follow_links Copy content of default container when the copied one
     *                           is not a CONTENT and is linked to default
     *
     * @return int|bool New container ID when copied, false otherwise
     */
    public static function copyFor($container_id = 0, $company_id = 0, $location_id = 0, $follow_links = true)
    {
        if (!$container_id || !$company_id || !fn_allowed_for('MULTIVENDOR')) {
            return false;
        }

        $containers = self::getList(array(
            'container_id' => $container_id,
        ));

        // clone container
        $new_container_id = false;

        if ($containers) {
            $container = reset($containers);

            if ($follow_links && $container['position'] != ContainerPositions::CONTENT && $container['linked_to_default'] == 'Y') {
                $default_location = Location::instance()->getDefault();
                $containers = self::getList(array(
                    'default_location' => $default_location['location_id'],
                    'position' => $container['position']
                ));
                $container = reset($containers);

                return self::copyFor($container['container_id'], $company_id, $location_id, false);
            }

            unset($container['container_id']);

            $container['company_id'] = $company_id;
            if ($location_id) {
                $container['location_id'] = $location_id;
            }

            $new_container_id = db_query("INSERT INTO ?:bm_containers ?e", $container);

            // clone grids, replace full block duplicates with blocks from another locations
            Grid::copy($container_id, $new_container_id, true);
        }

        return $new_container_id;
    }

    /**
     * Overrides layout containers by vendor ones  (used in Multi-Vendor).
     *
     * @param  array $containers Array of containers data as position => data
     * @param  array $params     Parameters to get vendor containers
     *
     * @return array Array of containers data as position => data
     */
    private static function overrideByVendor($containers, $params)
    {
        $vendor_containers = self::getList($params);

        foreach($containers as $position => $container) {
            if (isset($vendor_containers[$position])) {
                $containers[$position] = $vendor_containers[$position];
            }
        }

        return $containers;
    }

    /**
     * Removes container (used in Multi-Vendor).
     *
     * @param int $container_id Container ID
     * @param int $company_id   Vendor ID
     */
    public static function remove($container_id, $company_id = 0)
    {
        if (!$company_id) {
            $company_id = db_get_field('SELECT company_id FROM ?:bm_containers WHERE container_id = ?i', $container_id);
        }

        $grid_ids = Grid::getIds(Grid::getList(array(
            'simple' => true,
            'container_ids' => array($container_id)
        )));

        foreach ($grid_ids as $grid_id) {
            Grid::remove($grid_id);
        }

        db_query('DELETE FROM ?:bm_containers WHERE container_id = ?i', $container_id);

        // removes blocks that are not attached to any snapping
        Block::instance($company_id)->removeDetached(true);
    }

    /**
     * Prepares some variables used in condition checking.
     *
     * @param array $container Container data
     *
     * @return array Returns following variables:
     *               - container is linked to default,
     *               - container has CONTENT type
     *               - container's owner ID
     *               - container is located on the 'default' location
     */
    protected static function populateConditionVars($container)
    {
        $is_linked = $container['linked_to_default'] == 'Y';
        $is_content = $container['position'] == ContainerPositions::CONTENT;
        $owner_id = isset($container['company_id']) ? $container['company_id'] : 0;
        $is_default = isset($container['default']) && $container['default'] == 1;
        $is_in_container = in_array($container['position'], static::getOwned());

        return array($is_linked, $is_content, $owner_id, $is_default, $is_in_container);
    }

    /**
     * Checks if container uses default content.
     *
     * @param int   $company_id            Company ID to view block manager for
     * @param array $container             Container data
     * @param array $dynamic_object        Dynamic object data
     *
     * @return bool True if:
     *              [MVE] vendor-specific container is missing
     *              [MVE] store owner is viewing linked container on non-default location
     *              [ULT] linked container on non-default location
     */
    public static function usesDefaultContent($company_id, $container, $dynamic_object = array())
    {
        list($is_linked, $is_content, $owner_id, $is_default, $is_in_container) = static::populateConditionVars($container);

        return
            fn_allowed_for('MULTIVENDOR')
            && ($company_id && !$owner_id && $is_in_container
                || !$company_id && $is_linked && !$is_content && !$is_default && !$dynamic_object
            )
            || fn_allowed_for('ULTIMATE') && !($is_content || $dynamic_object || !$is_linked || $is_default);
    }

    /**
     * Checks if content of the container should be displayed.
     *
     * @param int   $company_id            Company ID to view block manager for
     * @param array $container             Container data
     * @param array $dynamic_object        Dynamic object data
     *
     * @return bool True if:
     *              'default' location is viewed
     *              [MVE] store owner is viewing custom container
     *              [MVE] store owner is viewing dynamic object
     *              [MVE] vendor-specific container exists
     *              [MVE] store owner is viewing CONTENT container
     *              [ULT] CONTENT block is viewed
     *              [ULT] dynamic object is viewed
     *              [ULT] custom container is viewed
     */
    public static function hasDisplayableContent($company_id, $container, $dynamic_object = array())
    {
        list($is_linked, $is_content, $owner_id, $is_default, $is_in_container) = static::populateConditionVars($container);

        return
            fn_allowed_for('MULTIVENDOR')
            && (!$company_id && ($dynamic_object || $is_content || !$is_linked || $is_default)
                || $company_id && $company_id == $owner_id && $is_in_container
            )
            || fn_allowed_for('ULTIMATE') && ($is_content || $dynamic_object || !$is_linked || $is_default);
    }

    /**
     * Checks if content of the container can be reset to default.
     *
     * @param int   $company_id            Company ID to view block manager for
     * @param array $container             Container data
     * @param array $dynamic_object        Dynamic object data
     *
     * @return bool True if:
     *              [MVE] vendor-specific container exists
     *              [MVE] store owner is viewing custom container
     *              [ULT] custom container is viewed
     */
    public static function canBeResetToDefault($company_id, $container, $dynamic_object = array())
    {
        list($is_linked, $is_content, $owner_id, $is_default, $is_in_container) = static::populateConditionVars($container);

        return
            fn_allowed_for('MULTIVENDOR')
            && (!$company_id && !$is_default && !$is_linked && !$is_content && !$dynamic_object
                || $company_id && $company_id == $owner_id && $is_in_container
            )
            || fn_allowed_for('ULTIMATE') && !$dynamic_object && !$is_linked && !$is_content;
    }

    /**
     * Returns message that should be displayed for the linked container.
     *
     * @param int   $company_id            Company ID to view block manager for
     * @param array $container             Container data
     * @param array $dynamic_object        Dynamic object data
     *
     * @return bool|string
     */
    public static function getLinkedMessage($company_id, $container, $dynamic_object = array())
    {
        list($is_linked, $is_content, $owner_id, $is_default, $is_in_container) = static::populateConditionVars($container);

        if (fn_allowed_for('MULTIVENDOR') && $company_id && !$owner_id && $is_in_container) {
            return __("mve.container_not_used");
        } elseif (!$is_content && !$is_default && !$dynamic_object) {
            $can_be_reset_to_default = isset($container['can_be_reset_to_default'])
                ? $container['can_be_reset_to_default']
                : static::canBeResetToDefault($company_id, $container, $dynamic_object);
            if (!$can_be_reset_to_default) {
                return __("container_not_used", array("[container]" => __($container['position'])));
            }
        }

        return false;
    }

    /**
     * Gets URL to set custom container configuration and reset to default.
     *
     * @param int   $company_id            Company ID to view block manager for
     * @param array $container             Container data
     * @param array $dynamic_object        Dynamic object data
     * @param array $dynamic_object_scheme Dynamic object scheme
     * @param bool  $use_default           If true, reset URL will be generated, set custom configuration URL otherwise
     * @return string URL
     */
    public static function getConfigurationUrl($company_id, $container, $dynamic_object, $dynamic_object_scheme, $use_default = false)
    {
        $use_default = $use_default ? 'Y' : 'N';

        list($is_linked, $is_content, $owner_id, $is_default, $is_in_container) = static::populateConditionVars($container);

        if (fn_allowed_for('MULTIVENDOR')
            && $dynamic_object
            && $is_in_container
            && $company_id && ($company_id == $owner_id || !$owner_id)) {

            $suffix = 'is_dynamic=Y&return_url='
                . urlencode("{$dynamic_object_scheme['admin_dispatch']}?{$dynamic_object_scheme['key']}={$dynamic_object['object_id']}&selected_section=blocks");
        } else {
            $suffix = 'selected_location=' . $container['location_id'];
        }

        return fn_url("block_manager.set_custom_container?container_id={$container['container_id']}&linked_to_default={$use_default}&{$suffix}");
    }

    /**
     * Adds properties used on in block manager.
     *
     * @param array $containers            Containers to render
     * @param array $dynamic_object        Dynamic object data
     * @param array $dynamic_object_scheme Dynamic object scheme
     *
     * @return array Containers to render with properties added
     */
    public static function addBlockManagerProperties(array $containers, $dynamic_object = array(), $dynamic_object_scheme = array())
    {
        $company_id = Registry::get('runtime.company_id');

        foreach ($containers as &$container) {
            $container['uses_default_content'] = self::usesDefaultContent($company_id, $container, $dynamic_object);
            $container['has_displayable_content'] = self::hasDisplayableContent($company_id, $container, $dynamic_object);
            $container['can_be_reset_to_default'] = self::canBeResetToDefault($company_id, $container, $dynamic_object);
            $container['linked_message'] = self::getLinkedMessage($company_id, $container, $dynamic_object);
            $container['set_custom_config_url'] = self::getConfigurationUrl($company_id, $container, $dynamic_object, $dynamic_object_scheme, false);
            $container['set_default_config_url'] = self::getConfigurationUrl($company_id, $container, $dynamic_object, $dynamic_object_scheme, true);
        }
        unset($container);

        return $containers;
    }

    /**
     * Returns container positions that can contain owned blocks.
     *
     * @return array
     */
    public static function getOwned()
    {
        if (fn_allowed_for('MULTIVENDOR')) {
            $schema = fn_get_schema('vendors', 'containers');

            return array_keys(array_filter($schema));
        }

        return array();
    }
}
