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

namespace Tygh\Api\Entities;


use Tygh\Addons\StorefrontRestApi\ASraEntity;
use Tygh\Api\Response;
use Tygh\BlockManager\Block;
use Tygh\BlockManager\Grid;
use Tygh\BlockManager\RenderManager;
use Tygh\BlockManager\SchemesManager;
use Tygh\Enum\SiteArea;
use Tygh\Registry;
use Tygh\BlockManager\Container;

/**
 * Class SraBmBlocks
 *
 * @package Tygh\Api\Entities
 */
class SraBmBlocks extends ASraEntity
{
    protected $icon_size_small = [500, 500];
    protected $icon_size_big = [1000, 1000];

    /**
     * @inheritDoc
     */
    public function index($id = '', $params = array())
    {
        if ($this->getParentName() !== 'sra_bm_locations') {
            return array(
                'status' => Response::STATUS_BAD_REQUEST
            );
        }

        $params['icon_sizes'] = $this->safeGet($params, 'icon_sizes', [
            'main_pair'   => [$this->icon_size_big, $this->icon_size_small],
            'image_pairs' => [$this->icon_size_small],
        ]);
        // set general icon sizes
        if (!isset($params['icon_sizes']['main_pair'])) {
            $params['icon_sizes']['main_pair'] = [$this->icon_size_big, $this->icon_size_small];
        }
        if (!isset($params['icon_sizes']['image_pairs'])) {
            $params['icon_sizes']['image_pairs'] = [$this->icon_size_small];
        }

        if (fn_allowed_for('MULTIVENDOR')) {
            $vendor_id = $this->safeGet($params, 'company_id', null);
            if ($vendor_id) {
                Registry::set('runtime.vendor_id', $vendor_id);
            }
        }

        $location = $this->getParentData();

        $containers = $this->getContainersByLocation($location['location_id']);
        $grids = $this->getGridsByContainers($containers);
        $blocks = $this->getBlocksByGrids($grids, $params);

        $data = $this->prepareBlocks($blocks, $grids, $params);

        if ($id) {
            if (!isset($data[$id])) {
                return array('status' => Response::STATUS_NOT_FOUND);
            }

            $data = $data[$id];
        }

        return array(
            'status' => Response::STATUS_OK,
            'data' => $data
        );
    }

    /**
     * @inheritDoc
     */
    public function create($params)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        );
    }

    /**
     * @inheritDoc
     */
    public function update($id, $params)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        );
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        );
    }

    /**
     * @inheritDoc
     */
    public function privilegesCustomer()
    {
        return array(
            'index'  => true,
            'create' => false,
            'update' => false,
            'delete' => false,
        );
    }

    /**
     * @inheritDoc
     */
    public function privileges()
    {
        return array(
            'index'  => true,
            'create' => 'edit_blocks',
            'update' => 'edit_blocks',
            'delete' => 'edit_blocks',
        );
    }

    /**
     * Gets containers by location.
     *
     * @param int $location_id
     *
     * @return array
     */
    protected function getContainersByLocation($location_id)
    {
        return Container::getListByArea($location_id, 'C');
    }

    /**
     * Gets grids by containers.
     *
     * @param array $containers List of the containers
     *
     * @return array Returns grids grouped by container
     */
    protected function getGridsByContainers(array $containers)
    {
        return Grid::getList(array(
            'container_ids' => Container::getIds($containers)
        ));
    }

    /**
     * Gets block by grids.
     *
     * @param array $grids  List of the grids grouped by container
     * @param array $params Request params
     *
     * @return array Returns blocks grouped by grid
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    protected function getBlocksByGrids(array $grids, array $params)
    {
        $dynamic_object = [];
        if (isset($params['dynamic_object'])) {
            $dynamic_object = $params['dynamic_object'];
        }
        /** @var Block $block */
        $block = Block::instance($this->getCompanyId());

        return $block->getList(
            array('?:bm_snapping.*','?:bm_blocks.*', '?:bm_blocks_descriptions.*'),
            Grid::getIds($grids),
            $dynamic_object
        );
    }

    /**
     * Prepares blocks.
     * Sorts blocks by containers and grids, retrieves the blocks variables, filtrates blocks by status.
     *
     * @param array $blocks         List of the blocks grouped by grid
     * @param array $grids          List of the grids grouped by container
     *
     * @return array Returns list of the blocks.
     */
    protected function prepareBlocks($blocks, $grids, $params)
    {
        $result = array();

        foreach ($grids as $container_id => $items) {
            foreach ($items as $grid_id => $grid) {
                if (empty($blocks[$grid_id])) {
                    continue;
                }

                foreach ($blocks[$grid_id] as $block_id => $block) {
                    if (isset($params['location'])) {
                        $block['status'] = RenderManager::correctStatusForDynamicObject($block, SchemesManager::getDynamicObject($params['location'], SiteArea::STOREFRONT));
                    }

                    if ($block['status'] !== 'A') {
                        continue;
                    }

                    $block['container'] = $grid['position'];

                    $block_schema = $this->getBlockScheme($block['type']);

                    if (!empty($block_schema['content'])) {
                        $content_variables = [];

                        foreach ($block_schema['content'] as $variable => $field) {
                            $value = RenderManager::getValue($variable, $field, $block_schema, $block);

                            if (isset($field['post_function']) && is_callable($field['post_function'])) {
                                $value = call_user_func($field['post_function'], $value, $block_schema, $block, $params);
                            }
                            if (
                                !empty($params['index_keys']) && !empty($value)
                                && $variable === 'items' && !empty($field['contains_id_keys'])
                            ) {
                                $value = array_values($value);
                            }
                            $content_variables[$variable] = $value;
                        }

                        $block['content'] = $content_variables;
                    }

                    $result[$block_id] = $block;
                }
            }
        }

        return $result;
    }

    /**
     * Returns the scheme for the block of a certain type overriding the basic scheme parameters with the addon
     * scheme parameters. If the addon scheme doesn't exist, it returns the basic scheme as is.
     *
     * @param string $type Block type (products, categories, etc - app/schemas/block_manager/blocks.php)
     *
     * @return array
     */
    protected function getBlockScheme($type)
    {
        $main_scheme = SchemesManager::getBlockScheme($type, array());
        $addon_scheme = fn_get_schema('storefront_rest_api', 'blocks');

        return isset($addon_scheme[$type])
            ? fn_array_merge($main_scheme, $addon_scheme[$type])
            : $main_scheme;
    }
}
