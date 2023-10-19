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

use Tygh\CompanySingleton;
use Tygh\Themes\Styles;
use Tygh\Tygh;

class Layout extends CompanySingleton
{
    /**
     * @var array Internal cache for layouts list
     */
    protected $cache_layouts = [];

    /**
     * @var array Internal cache for default layout
     */
    protected $cache_defaults = [];

    /**
     * @var int|null
     */
    protected $storefront_id;

    /**
     * @var int
     */
    protected $instance_company_id;

    /**
     * Gets layout by ID
     *
     * @param int $layout_id layout ID
     *
     * @return array layout data
     */
    public function get($layout_id = 0)
    {
        if (!isset($this->cache_layouts[$layout_id])) {
            $condition = db_quote(' AND storefront_id = ?i', $this->storefront_id);

            if (!empty($layout_id)) {
                $condition .= db_quote(" AND layout_id = ?i", $layout_id);
            } else {
                $condition .= db_quote(" AND is_default = 1");
            }

            $this->cache_layouts[$layout_id] = db_get_row("SELECT * FROM ?:bm_layouts WHERE 1 ?p", $condition);
        }

        return $this->cache_layouts[$layout_id];
    }

    public function getDefault($theme_name = '')
    {
        $condition = '';

        if (!$theme_name) {
            $theme_name = fn_get_theme_path('[theme]', 'C', $this->_company_id, true, $this->storefront_id);
        }

        if (isset($this->cache_defaults[$theme_name])) {
            return $this->cache_defaults[$theme_name];
        }

        $condition .= db_quote(" AND is_default = 1 AND theme_name = ?s AND storefront_id = ?i", $theme_name, $this->storefront_id);
        $fields = array('?:bm_layouts.*');
        $join = '';

        /**
         * Modifies the way to get default layout
         *
         * @param \Tygh\BlockManager\Layout $this       Layout object
         * @param string                    $theme_name Theme name
         * @param string                    $condition  Conditions part of SQL query
         * @param array                     $fields     Fields to select with SQL query
         * @param string                    $join       Join part of SQL condition
         */
        fn_set_hook('layout_get_default', $this, $theme_name, $condition, $fields, $join);

        $layout =  db_get_row(
            'SELECT ?p FROM ?:bm_layouts'
            . ' ?p'
            . ' WHERE 1 ?p',
            implode(',', $fields),
            $join,
            $condition
        );

        return $this->cache_defaults[$theme_name] = $layout;
    }

    /**
     * Changes default layout for the theme
     *
     * @param int $layout_id Layout identifier
     *
     * @return bool true
     */
    public function setDefault($layout_id)
    {
        $condition = db_quote(' AND storefront_id = ?i', $this->storefront_id);

        /**
         * Changes the way how layout is set as default
         *
         * @param object  $this Layout object
         * @param integer $layout_id layout ID
         * @param string  $condition part of SQL condition
         */
        fn_set_hook('layout_set_default', $this, $layout_id, $condition);

        $theme_name = db_get_field('SELECT theme_name FROM ?:bm_layouts WHERE layout_id = ?i', $layout_id);
        db_query('UPDATE ?:bm_layouts SET is_default = IF(layout_id = ?i, 1, 0) WHERE theme_name = ?s ?p', $layout_id, $theme_name, $condition);

        return true;
    }

    /**
     * Gets layouts list
     *
     * @param array $params input params
     *
     * @return array layouts list
     */
    public function getList($params = array())
    {
        $condition = db_quote(' AND storefront_id = ?i', $this->storefront_id);

        if (!empty($params['theme_name'])) {
            $condition .= db_quote(" AND theme_name = ?s", $params['theme_name']);
        }

        if (!empty($params['style_id'])) {
            $condition .= db_quote(" AND style_id = ?s", $params['style_id']);
        }

        $join = '';
        $fields = array('?:bm_layouts.*');

        /**
         * Modifies layouts list
         *
         * @param \Tygh\BlockManager\Layout $this      Layout object
         * @param array                     $params    Search params
         * @param string                    $condition Conditions part of SQL condition
         * @param array                     $fields    Fields to select with SQL query
         * @param string                    $join      Join part of SQL condition
         */
        fn_set_hook('layout_get_list', $this, $params, $condition, $fields, $join);

        return db_get_hash_array(
            'SELECT ?p FROM ?:bm_layouts'
            . ' ?p'
            . ' WHERE 1 ?p',
            'layout_id',
            implode(',', $fields),
            $join,
            $condition
        );
    }

    /**
     * Updates or creates layout
     *
     * @param array $layout_data layout data
     * @param int   $layout_id   layout ID to update, zero to create
     *
     * @return int   ID of updated/created layout
     */
    public function update($layout_data, $layout_id = 0)
    {
        $create = empty($layout_id);

        $layout_data['storefront_id'] = $this->storefront_id;

        $theme_name = empty($layout_data['theme_name'])
            ? fn_get_theme_path('[theme]', 'C', $this->_company_id, false, $this->storefront_id)
            : $layout_data['theme_name'];

        $available_styles = Styles::factory($theme_name)->getList(array(
            'short_info' => true
        ));

        /**
         * Performs actions before updating layout
         *
         * @param object  $this        Layout object
         * @param integer $layout_id   layout ID
         * @param array   $layout_data layout data
         * @param boolean $create      create/update flag
         */
        fn_set_hook('layout_update_pre', $this, $layout_id, $layout_data, $create);

        if (empty($layout_id)) {
            // Create layout
            if (!empty($layout_data['from_layout_id'])) {
                $layout_data['style_id'] = Styles::factory($theme_name)->getStyle($layout_data['from_layout_id']);
            }

            if (!empty($layout_data['style_id']) && !isset($available_styles[$layout_data['style_id']])) {
                unset($layout_data['style_id']);
            }

            if (empty($layout_data['style_id'])) {
                $layout_data['style_id'] = Styles::factory($theme_name)->getDefault();
            }

            if (empty($layout_data['theme_name'])) {
                $layout_data['theme_name'] = $theme_name;
            }

            $layout_id = db_query("INSERT INTO ?:bm_layouts ?e", $layout_data);
        } else {
            // Update existing layout
            if (isset($layout_data['style_id']) && !isset($available_styles[$layout_data['style_id']])) {
                $layout_data['style_id'] = Styles::factory($theme_name)->getDefault();
            }

            $old_layout_data = $this->get($layout_id);
            if ($old_layout_data['is_default'] == 1 && empty($layout_data['is_default'])) {
                $layout_data['is_default'] = 1;
            }

            db_query('UPDATE ?:bm_layouts SET ?u WHERE layout_id = ?i', $layout_data, $layout_id);
        }

        if (!empty($layout_data['is_default'])) {
            $this->setDefault($layout_id);
        }

        if (!empty($layout_data['from_layout_id'])) {
            $this->copyById($layout_data['from_layout_id'], $layout_id);
        }

        if (!empty($layout_id) && !empty($layout_data['width'])) {
            $layout_width = (int) $layout_data['width'];

            $this->setLayoutElementsWidth($layout_id, $layout_width);
        }

        $this->clearInnerCache($layout_id);

        return $layout_id;
    }

    /**
     * Deletes layout and assigned data (logos)
     *
     * @param int $layout_id layout ID
     *
     * @return boolean always true
     */
    public function delete($layout_id)
    {
        // Delete locations, containers, grids and snappings
        $location_ids = db_get_fields("SELECT location_id FROM ?:bm_locations WHERE layout_id = ?i", $layout_id);
        if (!empty($location_ids)) {
            foreach ($location_ids as $location_id) {
                Location::instance($layout_id)->remove($location_id, true);
            }
        }

        db_query("DELETE FROM ?:bm_layouts WHERE layout_id = ?i", $layout_id);

        // Delete logos
        $logo_ids = db_get_fields("SELECT logo_id FROM ?:logos WHERE layout_id = ?i", $layout_id);
        if (!empty($logo_ids)) {
            foreach ($logo_ids as $logo_id) {
                fn_delete_image_pairs($logo_id, 'logos');
            }

            db_query("DELETE FROM ?:logos WHERE logo_id IN (?n)", $logo_ids);
        }

        $this->clearInnerCache($layout_id);

        return true;
    }

    /**
     * Copies all layouts from one storefront to another.
     *
     * @param int $to_company_id    Target company ID.
     *                              This parameter is deprecated and will be removed in v5.0.0.
     *                              Use $to_storefront_id instead.
     * @param int $to_storefront_id Storefront to copy layout to
     *
     * @return bool true on success, false - otherwise
     */
    public function copy($to_company_id, $to_storefront_id = null)
    {
        $source_layouts = $this->getList();
        if (empty($source_layouts)) {
            return false;
        }

        foreach ($source_layouts as $layout) {
            $original_layout_id = $layout['layout_id'];
            $layout['name'] .= ' (' . __('clone') . ')';
            $layout['from_layout_id'] = $original_layout_id;

            unset($layout['layout_id'], $layout['company_id'], $layout['storefront_id']);

            $new_layout_id = static::instance($to_company_id, [], $to_storefront_id)->update($layout, 0);

            $this->copyById($original_layout_id, $new_layout_id);
        }

        return true;
    }

    /**
     * Copies all layout data from one layout to another by their IDs.
     *
     * @param integer $source_layout_id Source layout ID
     * @param integer $target_layout_id Target layout ID
     *
     * @return boolean True on success, false - otherwise
     */
    public function copyById($source_layout_id, $target_layout_id)
    {
        $source_layout = $this->get($source_layout_id);
        if (empty($source_layout)) {
            return false;
        }

        // Copy locations, their containers, grids and blocks to the target layout
        Location::instance($source_layout_id)->copy($target_layout_id);

        $source_layout_company_id = 0;
        $target_layout_company_id = 0;
        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        /** @var \Tygh\Storefront\Storefront[] $storefronts */
        $storefronts = $repository->findByLayoutId([$source_layout_id, $target_layout_id], false);

        if (fn_allowed_for('ULTIMATE')) {
            list($source_layout_company_id,) = $storefronts[$source_layout_id]->getCompanyIds();
            list($target_layout_company_id,) = $storefronts[$target_layout_id]->getCompanyIds();
        }

        // Copy logos

        /**
         * Get the list of logos, bounded to source layout and given company.
         * List has the following format:
         *
         * [
         *   logo_type => [
         *      style_id => logo_id,
         *      ...
         *   ],
         *   ...
         * ]
         */
        $source_layout_logos = db_get_hash_multi_array(
            'SELECT `type`, `style_id`, `logo_id` FROM ?:logos WHERE `layout_id` = ?i AND `company_id` = ?i',
            array('type', 'style_id', 'logo_id'),
            $source_layout_id, $source_layout_company_id
        );

        $logo_types = fn_get_logo_types();

        foreach ($logo_types as $logo_type => $logo_type_metadata) {

            if (empty($logo_type_metadata['for_layout']) || empty($source_layout_logos[$logo_type])) {
                continue;
            }

            foreach ($source_layout_logos[$logo_type] as $source_layout_style_id => $source_layout_logo_id) {

                $created_target_layout_logo_id = fn_update_logo([
                    'type'      => $logo_type,
                    'layout_id' => $target_layout_id,
                    'style_id'  => $source_layout_style_id,
                ], $target_layout_company_id);

                fn_clone_image_pairs($created_target_layout_logo_id, $source_layout_logo_id, 'logos');
            }
        }

        return true;
    }

    /**
     * Replaces the widths of containers and grids of the given layout with the width of this layout.
     *
     * The widths of grids are changed only if the grids are wider than the layout itself.
     * Mainly, this function is used to change the default widths of the layout elements when creating a layout
     *
     * @param int $layout_id    The identifier of layout
     * @param int $layout_width The width of layout
     *
     * @return void
     */
    public function setLayoutElementsWidth($layout_id, $layout_width)
    {
        $locations = db_get_hash_array("SELECT * FROM ?:bm_locations WHERE layout_id = ?i", 'location_id', $layout_id);

        foreach ($locations as $location_id => $location) {
            $containers = Container::getList(array('location_id' => $location_id));

            foreach ($containers as $container) {
                if (!empty($container['width'])) {
                    db_query("UPDATE ?:bm_containers SET width = ?i WHERE container_id = ?i ", $layout_width, $container['container_id']);
                }

                $grids = db_get_hash_array("SELECT * FROM ?:bm_grids WHERE container_id = ?i ORDER BY grid_id", 'grid_id', $container['container_id']);
                foreach ($grids as $grid_id => $grid) {
                    if (!empty($grid['width'])) {
                        $grid_width = (int) $grid['width'];

                        if ($grid_width > $layout_width) {
                            db_query("UPDATE ?:bm_grids SET width = ?i WHERE grid_id = ?i", $layout_width, $grid_id);
                        }
                    }
                }
            }
        }
    }

    /**
     * Clears inner cache
     *
     * @param int|null $layout_id
     */
    protected function clearInnerCache($layout_id = null)
    {
        if (isset($layout_id)) {
            unset($this->cache_layouts[$layout_id]);
        } else {
            $this->cache_layouts = [];
        }

        $this->cache_defaults = [];
    }

    /**
     * {@inheritdoc}
     * Creates layout manager instance.
     *
     * @param int      $company_id    Company identifier.
     *                                This parameter is deprecated and will be removed in v5.0.0.
     *                                Use $storefront_id instead.
     * @param array    $params        Instance parameters
     * @param int|null $storefront_id Storefront ID
     *
     * @return \Tygh\BlockManager\Layout
     */
    public static function instance($company_id = 0, $params = [], $storefront_id = null)
    {
        /**
         * Executes before getting an instance of a layout manager,
         * allows you to modify the parameters passed to the function.
         *
         * @param int      $company_id    Company identifier.
         *                                This parameter is deprecated and will be removed in v5.0.0.
         *                                Use $storefront_id instead.
         * @param array    $params        Instance parameters
         * @param int|null $storefront_id Storefront ID
         */
        fn_set_hook('layout_instance_pre', $company_id, $params, $storefront_id);

        $params['instance_key_extra'] = $storefront_id;

        /** @var \Tygh\BlockManager\Layout $instance */
        $instance = parent::instance($company_id, $params);

        if (!$storefront_id) {
            /** @var \Tygh\Storefront\Storefront $storefront */
            $storefront = Tygh::$app['storefront'];
            $storefront_id = $storefront->storefront_id;
        }

        $instance->storefront_id = $storefront_id;

        return $instance;
    }
}
