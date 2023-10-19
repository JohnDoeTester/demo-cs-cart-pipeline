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

use Tygh\BackendCustomMenu;
use Tygh\BlockManager\Block;
use Tygh\BlockManager\Container;
use Tygh\BlockManager\Exim;
use Tygh\BlockManager\Grid;
use Tygh\BlockManager\Layout;
use Tygh\BlockManager\Location;
use Tygh\BlockManager\SchemesManager;
use Tygh\Enum\BlockManagerActions;
use Tygh\Registry;
use Tygh\Themes\Styles;
use Tygh\Themes\Themes;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$navigation_sections = [
    'manage_layouts' => [
        'title' => __('layouts'),
        'href' => fn_url('block_manager.manage'),
    ],
    'manage_blocks' => [
        'title' => __('manage_blocks'),
        'href' => fn_url('block_manager.blocks'),
    ]
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $bm_actions = BlockManagerActions::getAll();
    $availability = [];

    if (defined('AJAX_REQUEST')) {
        $result = false;
        if ($mode === 'block') {
            if (
                $action === 'update'
                && isset($_REQUEST['block_data'])
                && SchemesManager::isTemplateAvailable($_REQUEST['block_data'])
                && SchemesManager::isWrapperAvailable($_REQUEST['block_data'])
            ) {
                $result = Block::instance()->update($_REQUEST['block_data']);
            } elseif ($action === 'delete' && isset($_REQUEST['block_id'])) {
                $result = Block::instance()->remove($_REQUEST['block_id']);
                $_REQUEST['return_url'] = isset($_REQUEST['return_url']) ? $_REQUEST['return_url'] : 'block_manager.blocks';
            }
        }

        if ($mode == 'location') {
            if ($action == 'update' && isset($_REQUEST['location_data'])) {
                $result = Location::instance()->update($_REQUEST['location_data']);
            } elseif ($action == 'delete' && isset($_REQUEST['location_id'])) {
                $result = Location::instance()->remove($_REQUEST['location_id']);
            }
        }

        if ($mode == 'grid' && isset($_REQUEST['snappings']) && is_array($_REQUEST['snappings'])) {
            foreach ($_REQUEST['snappings'] as $snapping_data) {
                if (!empty($snapping_data['action'])) {

                    if ($snapping_data['action'] == 'update' || $snapping_data['action'] == 'add') {
                        if (SchemesManager::isGridWrapperAvailable($snapping_data['grid_data'])) {
                            $result = Grid::update($snapping_data['grid_data']);
                        }


                        if (is_numeric($result)) {
                            Tygh::$app['ajax']->assign('id', (int) $result);
                        }

                        if (isset($snapping_data['grid_data']['user_class']) && $snapping_data['action'] === 'update') {
                            $grid = Grid::getById($snapping_data['grid_data']['grid_id']);
                            $availability[] = [
                                'type'         => 'grid',
                                'id'           => $snapping_data['grid_data']['grid_id'],
                                'availability' => Grid::getAvailabilityInstance()->getAvailability($grid),
                            ];
                        }
                    } elseif ($snapping_data['action'] == 'delete' && !empty($snapping_data['grid_data']['grid_id'])) {
                        $result = Grid::remove($snapping_data['grid_data']['grid_id']);
                    }
                }
            }
        }

        if ($mode == 'container') {
            if (
                $action == 'update'
                && isset($_REQUEST['container_data']['container_id'])
            ) {
                $result = Container::update($_REQUEST['container_data']);
                $container = Container::getById($_REQUEST['container_data']['container_id']);
                $availability[] = [
                    'type'         => 'container',
                    'id'           => [$_REQUEST['container_data']['container_id']],
                    'availability' => Container::getAvailabilityInstance()->getAvailability($container),
                ];
            }
        }

        if (!empty($_REQUEST['clears_data'])) {
            Grid::setClearDivs($_REQUEST['clears_data']);
        }
    }

    if ($mode == 'block') {
        if ($action == 'm_delete' && isset($_REQUEST['block_ids'])) {
            foreach ($_REQUEST['block_ids'] as $block_id) {
                $result = Block::instance()->remove($block_id);
            }
        }
    }

    fn_trusted_vars(
        'block',
        'block_items',
        'block_data'
    );

    $suffix = '';
    $return_url = isset($_REQUEST['return_url']) ? $_REQUEST['return_url'] : 'block_manager.manage';

    if ($mode == 'update_layout') {

        $layout_data = $_REQUEST['layout_data'];
        $from_default_layout = array();

        if (empty($_REQUEST['layout_id'])) {
            $layout_data['theme_name'] = fn_get_theme_path('[theme]', 'C');

            if (!empty($layout_data['from_layout_id']) && !is_numeric($layout_data['from_layout_id'])) {
                list($from_default_layout['theme_name'], $from_default_layout['filename']) = explode(
                    '|', $layout_data['from_layout_id'], 2
                );
                unset($layout_data['from_layout_id']);
            }

        }

        $layout_id = Layout::instance()->update($layout_data, $_REQUEST['layout_id']);

        if (!empty($from_default_layout) && !empty($layout_id)) {

            $repo_dest = fn_get_theme_path('[themes]/' . $from_default_layout['theme_name'], 'C');
            $layout_path = fn_normalize_path($repo_dest . '/layouts/' . $from_default_layout['filename']);

            $exim = Exim::instance(
                Registry::get('runtime.company_id'), $layout_id, fn_get_theme_path('[theme]', 'C')
            );

            $structure = $exim->getStructure($layout_path);
            if (!empty($structure)) {
                foreach ($layout_data as $key => $val) {
                    if (!empty($structure->layout->{$key})) {
                        $structure->layout->{$key} = $val;
                    }
                }

                if (!isset($layout_data['is_default'])) {
                    $structure->layout->is_default = 0;
                }

                $exim->import($structure, array(
                    'import_style' => 'update'
                ));

                fn_create_theme_logos_by_layout_id(
                    $layout_data['theme_name'],
                    $layout_id,
                    Registry::get('runtime.company_id'),
                    false,
                    Styles::factory($layout_data['theme_name'])->getDefault()
                );
            }

        }

        fn_clear_cache('assets', 'design/');

        return array(CONTROLLER_STATUS_OK, fn_url('block_manager.manage?s_layout=' . $layout_id));
    }

    if ($mode == 'update_block') {
        $description = [];
        $block = $_REQUEST['block_data'];

        if (!empty($block['description'])) {
            $block['description']['lang_code'] = DESCR_SL;
            $description = $block['description'];
        }

        if (!empty($block['content_data'])) {
            $block['content_data']['lang_code'] = DESCR_SL;
            if (isset($block['content'])) {
                $block['content_data']['content'] = $block['content'];
            }
        }

        if (!empty($_REQUEST['dynamic_object']['object_id']) && $_REQUEST['dynamic_object']['object_id'] > 0) {
            unset($block['properties']);
        }

        if (!empty($block['content_data']['content']['items']['item_ids'])) {
            $block['content_data']['content']['items']['item_ids'] = Block::instance()->processItemIds($block['content_data']['content']['items']['item_ids']);
        }

        $block_schema = empty($block['type']) ? [] : SchemesManager::getBlockScheme($block['type'], []);

        if (SchemesManager::isTemplateAvailable($block, $block_schema)) {
            $block_id = Block::instance()->update($block, $description);
        }

        if (!empty($_REQUEST['snapping_data'])) {
            // If block was newly created, and it must be snapped to grid, do it
            $snapping_data = $_REQUEST['snapping_data'];
            $snapping_data['block_id'] = $block_id;

            if (SchemesManager::isWrapperAvailable($snapping_data, $block_schema)) {
                Block::instance()->updateSnapping($snapping_data);
            }
        }

        if (defined('AJAX_REQUEST')) {
            if (!empty($_REQUEST['dynamic_object'])) {
                $dynamic_object = $_REQUEST['dynamic_object'];
            } else {
                $dynamic_object = array();
            }

            $block_data = Block::instance()->getById($block_id, 0, $dynamic_object, DESCR_SL);
            $block_data['unique_id'] = Block::getUniqueIdByData($block_data);

            Tygh::$app['view']->assign('block_data', $block_data);
            Tygh::$app['ajax']->assign('block_data', $block_data);

            if (!empty($_REQUEST['assign_to'])) {
                Tygh::$app['view']->assign('external_render', true);
                Tygh::$app['ajax']->assignHtml($_REQUEST['assign_to'], Tygh::$app['view']->fetch('views/block_manager/render/block.tpl'));
            }

            $result = $block_id;
        } else {
            if (!empty($_REQUEST['r_url'])) {
                return [CONTROLLER_STATUS_OK, $_REQUEST['r_url']];
            }

            // Redirect to dynamic object edit page
            if (!empty($_REQUEST['dynamic_object']['object_id']) && !empty($_REQUEST['dynamic_object']['object_type'])) {
                $scheme = SchemesManager::getDynamicObjectByType($_REQUEST['dynamic_object']['object_type']);
                $return_url = $scheme['admin_dispatch'] .
                    '?' . $scheme['key'] . '=' . $_REQUEST['dynamic_object']['object_id'];

                if (!empty($_REQUEST['tab_redirect'])) {
                    $return_url .= '&selected_section=product_tabs';
                } else {
                    $return_url .= '&selected_section=blocks';
                }

                return array(CONTROLLER_STATUS_OK, $return_url);
            }

            $selected_location = fn_get_selected_location($_REQUEST);
            $suffix .= "&selected_location=" . $selected_location['location_id'];
        }

        if (!empty($_REQUEST['r_url'])) {
            return [CONTROLLER_STATUS_OK, $_REQUEST['r_url']];
        }
    }

    if ($mode == 'update_location') {

        fn_trusted_vars('location_data');

        $_REQUEST['location_data']['lang_code'] = DESCR_SL;
        $location_id = Location::instance()->update($_REQUEST['location_data']);

        $suffix .= "&selected_location=" . $location_id;
    }

    if ($mode == 'export_layout') {
        $location_ids = isset($_REQUEST['location_ids']) ? $_REQUEST['location_ids'] : array();
        $layout_id = Registry::get('runtime.layout.layout_id');
        $result = Exim::instance()->exportToFile($layout_id, $location_ids, $_REQUEST['filename']);
        if ($result) {
            fn_set_notification('N', __('notice'), __('text_exim_data_exported'));

            // Direct download
            if ($_REQUEST['output'] == 'D') {
                return array(CONTROLLER_STATUS_REDIRECT, 'block_manager.manage?meta_redirect_url=block_manager.get_file%26filename=' . $result);

                // Output to screen
            } elseif ($_REQUEST['output'] == 'C') {
                return array(CONTROLLER_STATUS_REDIRECT, 'block_manager.get_file?to_screen=Y&filename=' . $result);
            }
        }

    }

    if ($mode == 'import_layout') {
        $data = fn_filter_uploaded_data('filename');

        if (!empty($data[0]['path'])) {
            $result = Exim::instance()->importFromFile($data[0]['path'], $_REQUEST);

            // create logos when imported layout is created
            $import_style = empty($_REQUEST['import_style']) ? 'update' : $_REQUEST['import_style'];
            if ($import_style == 'create') {
                $layout_data = Layout::instance()->get($result);
                fn_create_theme_logos_by_layout_id(
                    $layout_data['theme_name'],
                    $result,
                    isset($layout_data['company_id']) ? $layout_data['company_id'] : 0,
                    false,
                    $layout_data['style_id']
                );
            }

            if ($result) {
                fn_set_notification('N', __('notice'), __('text_exim_data_imported_clear'));
            }
        }
    }

    if ($mode == 'delete_location' && !empty($_REQUEST['location_id'])) {
        Location::instance()->remove($_REQUEST['location_id']);
    }

    if ($mode == 'delete_layout') {
        Layout::instance()->delete($_REQUEST['layout_id']);
    }

    if ($mode == 'set_default_layout') {
        if (!empty($_REQUEST['layout_id'])) {
            Layout::instance()->setDefault($_REQUEST['layout_id']);

            fn_set_notification('N', __('notice'), __('text_changes_saved'));
        }

        return array(CONTROLLER_STATUS_OK, 'block_manager.manage' . $suffix);
    }

    if ($mode == 'set_custom_container') {

        if (!empty($_REQUEST['container_id']) && !empty($_REQUEST['linked_to_default'])) {
            if (fn_get_blocks_owner()) {
                if ($_REQUEST['linked_to_default'] == 'N') {
                    $selected_location = isset($_REQUEST['selected_location']) ? $_REQUEST['selected_location'] : 0;
                    Container::copyFor($_REQUEST['container_id'], Registry::get('runtime.company_id'), $selected_location);
                } else {
                    Container::remove($_REQUEST['container_id']);
                }
            } else {
                Container::update($_REQUEST);

                if ($_REQUEST['linked_to_default'] == 'N') {
                    // Check if we need to clone grids and blocks from the default container
                    $grids = Grid::getList(array('container_ids' => array($_REQUEST['container_id'])));

                    if (empty($grids)) {
                        // Clone data from the default container
                        $default_location = Location::instance()->getDefault();
                        $default_containers = Container::getList(array('default_location' => $default_location['location_id']));
                        $current_container = Container::getById($_REQUEST['container_id']);

                        Grid::copy($default_containers[$current_container['position']]['container_id'], $current_container['container_id']);
                    }
                }
            }
        }

        if (empty($_REQUEST['is_dynamic'])) {
            $selected_location = empty($_REQUEST['selected_location']) ? 0 : $_REQUEST['selected_location'];

            $suffix = '?selected_location=' . $selected_location;
        }
    }

    if (defined('AJAX_REQUEST')) {
        /** @var \Tygh\Ajax $ajax */
        $ajax = Tygh::$app['ajax'];
        if (isset($ajax->getAssignedVars()['result'])) {
            $result = $ajax->getAssignedVars()['result'];
        }
        if ($result === true) {
            $ajax->assign('status', 'OK');
        } elseif (is_numeric($result)) {
            $ajax->assign('id', intval($result));
            $ajax->assign('status', 'OK');
        } else {
            $ajax->assign('status', 'FAIL');
        }

        $ajax->assign('mode', $mode);
        $ajax->assign('action', $action);
        $ajax->assign('bm_actions', $bm_actions);
        $ajax->assign('availability', $availability);

        fn_set_notification('N', __('notice'), __('text_changes_saved'));

        if ($action === 'delete') {
            $ajax->assign('force_redirection', fn_url($return_url . $suffix));
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
        exit;
    }

    return array(CONTROLLER_STATUS_OK, $return_url . $suffix);
}

if ($mode == 'manage' || $mode == 'manage_in_tab') {

    if ($mode == 'manage') {
        Registry::set('navigation.dynamic.sections', $navigation_sections);
        Registry::set('navigation.dynamic.active_section', 'manage_layouts');
    }

    /** @var \Tygh\Storefront\Repository $storefront_repository */
    $storefront_repository = Tygh::$app['storefront.repository'];
    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];
    list($storefronts, ) = $storefront_repository->find();

    if (isset($_REQUEST['dynamic_object']['object_type'])
        && (fn_get_blocks_owner()
            || fn_allowed_for('MULTIVENDOR') && !Registry::get('runtime.company_id')
            || fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id'))
    ) {
        $dynamic_object = SchemesManager::getDynamicObjectByType($_REQUEST['dynamic_object']['object_type'], $_REQUEST);
    } else {
        $dynamic_object = '';
    }

    $selected_location = fn_get_selected_location($_REQUEST);

    if (empty($selected_location) && !empty($_REQUEST['selected_location'])) {
        return [CONTROLLER_STATUS_REDIRECT, 'block_manager.manage'];
    }

    if (fn_get_blocks_owner() && !in_array($selected_location['dispatch'], fn_get_vendor_dispatches())) {
        if (!empty($_REQUEST['redirect_to_block_manager'])) {
            return [CONTROLLER_STATUS_REDIRECT, 'block_manager.manage'];
        }

        return [CONTROLLER_STATUS_DENIED];
    }

    if ($dynamic_object) {
        // If it is some dynamic object, such as product with some id
        $dynamic_object_data = array();
        if (!empty($_REQUEST['dynamic_object'])) {
            $dynamic_object_data = $_REQUEST['dynamic_object'];
        }

        $selected_location = Location::instance()->get($dynamic_object['customer_dispatch'], $dynamic_object_data, DESCR_SL);
        if (!empty($selected_location['is_default'])) {
            $selected_location = [];
            $location_by_dispatch = Location::instance()->get($dynamic_object['customer_dispatch'], [], DESCR_SL);
            Tygh::$app['view']->assign(['location_by_dispatch' => empty($location_by_dispatch['is_default']) ? $location_by_dispatch : []]);
        }
        Tygh::$app['view']->assign([
            'location'              => $selected_location,
            'dynamic_object'        => $dynamic_object_data,
            'dynamic_object_scheme' => $dynamic_object,
        ]);
    } else {
        // If it is all another block manager
        $params = array(
            'sort_by' => 'position',
            'sort_order' => 'asc',
        );
        if (fn_get_blocks_owner()) {
            $params['dispatch'] = fn_get_vendor_dispatches();
        }
        $locations = Location::instance()->getList($params, DESCR_SL);

        Tygh::$app['view']->assign('locations', $locations);

        // Set tabs content
        if (!empty($locations)) {
            foreach ($locations as $location => $_location) {
                Registry::set("navigation.tabs.location_". $_location['location_id'], array (
                    'title' => $_location['name'],
                    'href' => "block_manager.manage?selected_location=" . $_location['location_id']
                ));
            }
        }
    }

    Tygh::$app['view']->assign('location', $selected_location);

    if (!empty($_REQUEST['dynamic_object'])) {
        $dynamic_object_params = [];
        foreach ($_REQUEST['dynamic_object'] as $param => $value) {
            $dynamic_object_params['dynamic_object[' . $param . ']'] = $value;
        }

        Tygh::$app['view']->assign([
            'dynamic_object'        => $_REQUEST['dynamic_object'],
            'dynamic_object_params' => $dynamic_object_params,
        ]);
    }

    if (!empty($selected_location['dispatch'])) {
        Tygh::$app['view']->assign('dynamic_object_scheme', SchemesManager::getDynamicObject($selected_location['dispatch'], 'C'));
    }

    $theme = Themes::factory(fn_get_theme_path('[theme]', 'C', null, true, $storefront->storefront_id));

    $params = array(
        'theme_name' => $theme->getThemeName()
    );

    $edit_object_id = 0;
    if (!empty($_REQUEST['object_id'])) {
        $edit_object_id = (int) $_REQUEST['object_id'];
    }
    $edit_object_type = '';
    if (!empty($_REQUEST['type'])) {
        $edit_object_type = (string) $_REQUEST['type'];
    }

    Tygh::$app['view']->assign([
        'theme_manifest'          => $theme->getManifest(),
        'storefront'              => $storefront,
        'storefronts'             => $storefronts,
        'layouts'                 => Layout::instance()->getList($params),
        'all_layouts'             => Layout::instance()->getList(),
        'default_layouts_sources' => fn_get_default_layouts_sources('responsive'),
        'themes'                  => fn_get_available_themes($theme->getThemeName()),
        'widget_http_url'         => urlencode('http://' . $storefront->url),
        'widget_https_url'        => urlencode('https://' . $storefront->url),
        'is_filtered_by_device'   => !empty($_REQUEST['device']),
        'edit_object_id'          => $edit_object_id,
        'edit_object_type'        => $edit_object_type,
    ]);

    if ($mode == 'manage') {
        Tygh::$app['view']->assign('layout_data', Layout::instance()->get(Registry::get('runtime.layout.layout_id')));
    } else {
        Tygh::$app['view']->display('views/block_manager/manage_in_tab.tpl');
        exit;
    }

} elseif ($mode == 'update_block') {
    $snapping_data = array();

    $editable_content = true;
    $editable_template_name = true;
    $editable_wrapper = false;

    if (isset($_REQUEST['block_data']['content']['items']['item_ids'])) {
        $_REQUEST['block_data']['content']['items']['item_ids'] = Block::instance()->processItemIds($_REQUEST['block_data']['content']['items']['item_ids']);
    }

    if (!empty($_REQUEST['dynamic_object'])) {
        $dynamic_object = $_REQUEST['dynamic_object'];
        $editable_template_name = false;
    } else {
        $dynamic_object = array();
    }

    if (!empty($_REQUEST['snapping_data']['snapping_id'])) {
        $snapping_data = Block::instance()->getSnappingData(
            array('?:bm_blocks.type as type', '?:bm_blocks.block_id as block_id', '?:bm_snapping.*'),
            $_REQUEST['snapping_data']['snapping_id']
        );
        $type = isset($snapping_data['type']) ? $snapping_data['type'] : 'html_block';
        $block_id = isset($snapping_data['block_id']) ? $snapping_data['block_id'] : 0;
        $snapping_id = $_REQUEST['snapping_data']['snapping_id'];
    } else {
        $block_id = isset($_REQUEST['block_data']['block_id']) ? $_REQUEST['block_data']['block_id'] : 0;
        $type = isset($_REQUEST['block_data']['type']) ? $_REQUEST['block_data']['type'] : 'html_block';

        if (!empty($_REQUEST['snapping_data'])) {
            $snapping_data = $_REQUEST['snapping_data'];
        }
        $snapping_id = 0;
    }

    if (!SchemesManager::isManageable($type)) {
        if (defined('AJAX_REQUEST')) {
            fn_set_notification('W', __('warning'), __('access_denied'));
            exit;
        }
        return array(CONTROLLER_STATUS_DENIED);
    }

    $content = array();

    if (!empty($_REQUEST['block_data']['content_data'])) {
        $content = $_REQUEST['block_data']['content_data'];
    }

    // If edit block
    if ($block_id > 0) {
        if (!empty($_REQUEST['snapping_data']['snapping_id'])) {
            $editable_wrapper = true;
        }

        $block_data = Block::instance()->getById($block_id, $snapping_id, $dynamic_object, DESCR_SL);

        if (!empty($block_data['content']) && empty($content['content'])) {
            $content['content'] = $block_data['content'];
        }

        $type = $block_data['type'];
        Tygh::$app['view']->assign('changed_content_stat', Block::instance()->getChangedContentsCount($block_id, true));
    } else {
        $type = isset($_REQUEST['block_data']['type']) ? $_REQUEST['block_data']['type'] : 'html_block';

        $block_data = array(
            'type' => $type,
            'block_id' => 0
        );
    }

    if (!empty($_REQUEST['block_data']['description']['name'])) {
        $block_data['name'] = $_REQUEST['block_data']['description']['name'];
    }

    if (!empty($_REQUEST['block_data']['properties'])) {
        $block_data['properties'] = $_REQUEST['block_data']['properties'];
    }

    if (!empty($_REQUEST['block_data']['content'])) {
        $block_data['content'] = $_REQUEST['block_data']['content'];
    }

    $block_scheme = SchemesManager::getBlockScheme($type, isset($_REQUEST['block_data']) ? $_REQUEST['block_data'] : $block_data, true);

    // Set template as first default from scheme
    if (empty($block_data['properties']['template']) && isset($block_scheme['templates'])) {
        if (is_array($block_scheme['templates'])) {
            $block_data['properties']['template'] = current(array_keys($block_scheme['templates']));
        } else {
            $block_data['properties']['template'] = $block_scheme['templates'];
        }
        $block_scheme['content'] = SchemesManager::prepareContent($block_scheme, $block_data);
    }

    // Set content_type as first default from scheme
    if (empty($block_data['properties']['content_type']) && !empty($block_scheme['content'])) {
        $block_data['properties']['content_type'] = current(array_keys($block_scheme['content']));
    }

    // Set filing as first default from scheme
    if (isset($block_scheme['content']) && is_array($block_scheme['content'])) {
        foreach ($block_scheme['content'] as $name => $scheme) {
            if (isset($scheme['type']) && $scheme['type'] == 'enum') {
                $fillings = array_keys($scheme['fillings']);
                if ((!empty($block_data['content'][$name]['filling']) && array_search($block_data['content'][$name]['filling'], $fillings) === FALSE) || empty($block_data['content'][$name]['filling'])) {
                    $block_data['content'][$name]['filling'] = current($fillings);
                }
            }
        }
    }

    $selected_location = fn_get_selected_location($_REQUEST);
    Tygh::$app['view']->assign('dynamic_object_scheme', SchemesManager::getDynamicObject($selected_location['dispatch'], 'C'));

    if (!empty($_REQUEST['hide_status'])) {
        Tygh::$app['view']->assign('hide_status', 1);
    }

    Tygh::$app['view']->assign('location', $selected_location);
    Tygh::$app['view']->assign('editable_content', $editable_content);
    Tygh::$app['view']->assign('editable_template_name', $editable_template_name);
    Tygh::$app['view']->assign('editable_wrapper', $editable_wrapper);

    Tygh::$app['view']->assign('block', $block_data);
    Tygh::$app['view']->assign('snapping_data', $snapping_data);
    Tygh::$app['view']->assign('block_scheme', $block_scheme);
    Tygh::$app['view']->assign('block_availability_instance', Block::getAvailabilityInstance());

    if (defined('AJAX_REQUEST')) {
        Tygh::$app['view']->display('views/block_manager/update_block.tpl');
        exit;
    }
} elseif ($mode == 'update_grid') {
    $grid = [];
    if (!empty($_REQUEST['grid_data']['grid_id'])) {
        $grid = Grid::getById($_REQUEST['grid_data']['grid_id'], DESCR_SL);
    } else {
        $grid['availability'] = Grid::getAvailabilityInstance()->getAvailability([]);
    }

    $grids_schema = fn_get_schema('block_manager', 'grids');

    Tygh::$app['view']->assign([
        'grid'                       => $grid,
        'grid_params'                => $_REQUEST['grid_data'],
        'grids_schema'               => $grids_schema,
        'grid_availability_instance' => Grid::getAvailabilityInstance(),
    ]);

} elseif ($mode == 'update_container') {
    if (!empty($_REQUEST['container_id'])) {
        // Update existing container
        $container = Container::getById($_REQUEST['container_id']);

        Tygh::$app['view']->assign('container', $container);
    }

    Tygh::$app['view']->assign([
        'container_availability_instance' => Container::getAvailabilityInstance(),
    ]);

} elseif ($mode == 'update_location') {

    $layout_id = isset($_REQUEST['s_layout']) ? $_REQUEST['s_layout'] : null;

    $location_data = array(
        'dispatch' => '',
        'layout_id' => $layout_id
    );

    if (!empty($_REQUEST['location'])) {
        $location_data = Location::instance()->getById($_REQUEST['location'], DESCR_SL);
    }

    if (isset($_REQUEST['location_data']['dispatch'])) {
        $location_data['dispatch'] = $_REQUEST['location_data']['dispatch'];
        $location_data['object_ids'] = "";
    }

    Tygh::$app['view']->assign('location', $location_data);
    Tygh::$app['view']->assign('dynamic_object_scheme', SchemesManager::getDynamicObject($location_data['dispatch'], 'C'));
    Tygh::$app['view']->assign('dispatch_descriptions', SchemesManager::getDispatchDescriptions());

    if (defined('AJAX_REQUEST')) {
        Tygh::$app['view']->display('views/block_manager/update_location.tpl');
        exit;
    }

} elseif ($mode == 'block_selection') {
    $selected_location = fn_get_selected_location($_REQUEST);

    $purpose = isset($_REQUEST['purpose'])
        ? $_REQUEST['purpose']
        : 'block_manager';

    if (!empty($_REQUEST['on_product_tabs'])) {
        $selected_location['dispatch'] = 'product_tabs';
    }

    $unique_blocks = SchemesManager::filterByLocation(Block::instance()->getAllUnique(DESCR_SL), $selected_location);
    array_walk($unique_blocks, function (&$block) {
        $block['unique_id'] = Block::getUniqueIdByData($block);
    });
    $block_types = SchemesManager::filterByLocation(SchemesManager::getBlockTypes(DESCR_SL), $selected_location);
    unset($block_types['smarty_block']);

    if (!empty($_REQUEST['grid_id'])) {
        Tygh::$app['view']->assign('grid_id', $_REQUEST['grid_id']);
    }

    if (!empty($_REQUEST['extra_id'])) {
        Tygh::$app['view']->assign('extra_id', $_REQUEST['extra_id']);
    }

    $block_types = SchemesManager::getBlockDescriptions($block_types, DESCR_SL);

    Tygh::$app['view']->assign([
        'block_types'   => $block_types,
        'unique_blocks' => $unique_blocks,
        'purpose'       => $purpose,
    ]);

} elseif ($mode == 'block_type_list') {

    $block_types = SchemesManager::getBlockDescriptions(SchemesManager::getBlockTypes(DESCR_SL), DESCR_SL);
    unset($block_types['smarty_block']);
    array_walk($block_types, function (&$block_type) {
        $block_type['is_manageable'] = SchemesManager::isManageable($block_type['type']);
    });

    Tygh::$app['view']->assign([
        'block_types'      => $block_types,
    ]);

} elseif ($mode == 'get_file' && !empty($_REQUEST['filename'])) {
    $file = fn_basename($_REQUEST['filename']);
    if (Registry::get('runtime.company_id')) {
        $file = Registry::get('runtime.company_id') . '/' . $file;
    }

    if (!empty($_REQUEST['to_screen'])) {
        header("Content-type: text/xml");
        readfile(Registry::get('config.dir.layouts') . $file);
        exit;

    } else {
        fn_get_file(Registry::get('config.dir.layouts') . $file);
    }

} elseif ($mode == 'show_objects') {
    if (!empty($_REQUEST['object_type']) && !empty($_REQUEST['block_id'])) {
        Tygh::$app['view']->assign('object_type', $_REQUEST['object_type']);
        Tygh::$app['view']->assign('block_id', $_REQUEST['block_id']);
        Tygh::$app['view']->assign('object_ids', Block::instance()->getChangedContentsIds($_REQUEST['object_type'], $_REQUEST['block_id']));
        Tygh::$app['view']->assign('params', array('type' => 'links'));
        Tygh::$app['view']->assign('dynamic_object_scheme', SchemesManager::getDynamicObjectByType($_REQUEST['object_type'], $_REQUEST));
    }

}  elseif ($mode == 'blocks') {
    Registry::set('navigation.dynamic.sections', $navigation_sections);
    Registry::set('navigation.dynamic.active_section', 'manage_blocks');

    $params = $_REQUEST;
    $params['extend'] = ['get_quantity', 'get_locations', 'get_info'];
    $params['only_types_from_scheme'] = true;

    list($blocks, $search) = Block::instance(Registry::get('runtime.company_id'))->find($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);
    foreach ($blocks as &$block) {
        if (isset($block['locations'])) {
            $locations_map = [];
            foreach ($block['locations'] as $location) {
                if (!isset($locations_map[$location['layout_id']])) {
                    $locations_map[$location['layout_id']] = [
                        'layout_name' => $location['layout_name'],
                        'theme_name'  => $location['theme_name']
                    ];
                }
                $locations_map[$location['layout_id']]['locations'][$location['location_id']] =  $location['location_name'];
            }
            $block['locations'] = $locations_map;
        }
    }
    unset($block, $locations_map);

    $theme_names = [];
    $locations = [];
    $layouts = Layout::instance(Registry::get('runtime.company_id'))->getList();

    foreach ($layouts as $layout_id => &$layout) {
        if (!isset($theme_names[$layout['theme_name']])) {
            $theme = Themes::factory($layout['theme_name']);
            $manifest = $theme->getManifest();

            $theme_names[$layout['theme_name']] = isset($manifest['title']) ? $manifest['title'] : '';
        }

        $layout['theme'] = $theme_names[$layout['theme_name']];
        foreach (Location::instance($layout_id)->getList() as $location) {
            $locations[$location['location_id']] = $location;
            $locations[$location['location_id']]['layout_name'] = $layout['name'];
            $locations[$location['location_id']]['theme_name'] = $layout['theme'];
        }
    }
    unset($layout);

    Tygh::$app['view']->assign([
        'blocks'      => $blocks,
        'search'      => $search,
        'block_types' => SchemesManager::getBlockTypes(),
        'layouts'     => $layouts,
        'locations'   => $locations
    ]);

} elseif ($mode === 'update_custom_block') {

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $return_url = !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : 'index.index';
    $notifications = [];
    $custom_block = [
        'block_id' => 0
    ];
    $object_type = '';

    if (!empty($_REQUEST['object_type'])) {
        $object_type = $_REQUEST['object_type'];
    }

    if (!empty($_REQUEST['section'])) {
        $custom_block['section'] = $_REQUEST['section'];
    }

    if (!empty($_REQUEST['type'])) {
        $custom_block['type'] = $_REQUEST['type'];
    }

    if (isset($_REQUEST['id_path']) && $object_type === 'menu_item') {
        $custom_block['id_path'] = !empty($_REQUEST['id_path']) ? $_REQUEST['id_path'] : 0;
    }

    if (!empty($_REQUEST['block_id'])) {
        if ($object_type === 'menu_item') {
            $custom_block = BackendCustomMenu::getCustomMenuItem($_REQUEST['block_id']);

            if (!empty($custom_block['root_hidden'])) {
                $notifications = [__('custom_menu.section_has_one_visible_item')];
            }
            if (!empty($custom_block['hidden_by_permissions'])) {
                $notifications = [__('custom_menu.item_hidden_due_to_privileges')];
            }
        } else {
            $custom_block = fn_get_custom_block_data($_REQUEST['block_id']);
        }
    }

    if (!empty($_REQUEST['menu_level'])) {
        $custom_block['menu_level'] = $_REQUEST['menu_level'];
    }

    $view->assign('custom_block', $custom_block);
    $view->assign('object_type', $object_type);
    $view->assign('return_url', $return_url);
    $view->assign('notifications', $notifications);
}

function fn_get_selected_location($params)
{
    if (isset($params['selected_location']) && !empty($params['selected_location'])) {
        $selected_location = Location::instance()->getById($params['selected_location'], DESCR_SL);
    } elseif (fn_get_blocks_owner()) {
        $first_owned_location = Location::instance()->getList(array(
            'dispatch' => fn_get_vendor_dispatches(),
            'sort_by' => 'location_id',
            'sort_order' => 'asc',
            'limit' => 1
        ), DESCR_SL);
        $selected_location = reset($first_owned_location);
    } else {
        $selected_location = Location::instance()->getDefault(DESCR_SL);
    }

    return $selected_location;
}

function fn_get_default_layouts_sources($theme_name = '', $themes_path = '')
{
    $layouts_sources = array();

    if (empty($themes_path)) {
        $themes_path = fn_get_theme_path('[themes]', 'C');
    }

    if (empty($theme_name)) {

        $installed_themes = fn_get_dir_contents($themes_path, true);

        foreach ($installed_themes as $theme_name) {
            $layouts_sources = array_merge($layouts_sources, fn_get_default_layouts_sources($theme_name, $themes_path));
        }

    } else {

        $theme = Themes::areaFactory('C');
        $layouts = $theme->getDirContents(array(
            'dir' => 'layouts',
            'get_dirs' => false,
            'get_files' => true,
            'extension' => '.xml'
        ), Themes::STR_SINGLE);

        foreach ($layouts as $layout_name => $path_info) {

            $layout_path = $path_info[Themes::PATH_ABSOLUTE];

            if (file_exists($layout_path)) {

                $layout_data = Exim::instance(
                    Registry::get('runtime.company_id'), 0, $theme_name
                )->getLayoutData($layout_path, false);

                if (!empty($layout_data)) {
                    $layout_data['theme_name'] = $theme_name;
                    $layout_data['filename'] = $layout_name;
                    $layouts_sources[] = $layout_data;
                }
            }
        }
    }

    return $layouts_sources;

}
