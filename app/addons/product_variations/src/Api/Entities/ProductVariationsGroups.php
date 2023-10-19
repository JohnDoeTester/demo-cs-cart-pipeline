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

use Tygh\Addons\ProductVariations\Product\Group\Group;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeature;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection;
use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Common\OperationResult;
use Tygh\Registry;

class ProductVariationsGroups extends AEntity
{
    const ENTITY_NAME = 'product_variations_groups';

    /**
     * @inheritDoc
     */
    public function index($id = '', $params = [])
    {
        if ($id) {
            $group = $this->findGroup($id);

            if ($group) {
                return [
                    'status' => Response::STATUS_OK,
                    'data'   => $this->convertGroupToArray($group)
                ];
            } else {
                return [
                    'status' => Response::STATUS_NOT_FOUND
                ];
            }
        } else {
            $items_per_page = (int) $this->safeGet(
                $params,
                'items_per_page',
                Registry::get('settings.Appearance.admin_elements_per_page')
            );
            $page = (int) $this->safeGet($params, 'page', 1);

            $repository = ServiceProvider::getGroupRepository();

            if (isset($params['product_ids'])) {
                $group_ids = $repository->findGroupIdsByProductIds((array) $params['product_ids']);
            } elseif (isset($params['feature_ids'])) {
                $group_ids = $repository->findGroupIdsByFeaturesIds((array) $params['feature_ids']);
            } else {
                $group_ids = $repository->findAllGroupIds();
            }

            $total_items_count = count($group_ids);

            if ($items_per_page) {
                $group_ids = array_slice($group_ids, ($page - 1) * $items_per_page, $items_per_page);
            }

            $groups = $repository->findGroupsByIds($group_ids);

            $data = [
                'groups' => $this->convertGroupsToArray($groups),
                'params' => [
                    'items_per_page' => $items_per_page,
                    'page'           => $page,
                    'total_items'    => $total_items_count,
                ],
            ];

            return [
                'status' => Response::STATUS_OK,
                'data'   => $data
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function create($params)
    {
        $service = ServiceProvider::getService();

        $product_ids = (array) $this->safeGet($params, 'product_ids', []);
        $group_code = $this->safeGet($params, 'code', null);
        $features = $this->safeGet($params, 'features', []);

        if ($features) {
            $group_feature_collection = self::convertFeaturesToFeatureCollection($features);
        } else {
            $group_feature_collection = null;
        }

        $result = $service->createGroup($product_ids, $group_code, $group_feature_collection);

        return self::convertSaveGroupResultToResponse($result, Response::STATUS_CREATED);
    }

    /**
     * @inheritDoc
     */
    public function update($id, $params)
    {
        $group = $this->findGroup($id);

        if (!$group) {
            return [
                'status' => Response::STATUS_NOT_FOUND
            ];
        }

        $service = ServiceProvider::getService();

        $code = $this->safeGet($params, 'code', null);

        $result = $service->updateGroupCode($group->getId(), $code);

        return self::convertSaveGroupResultToResponse($result, Response::STATUS_OK);
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        $group = $this->findGroup($id);

        if (!$group) {
            return [
                'status' => Response::STATUS_NOT_FOUND
            ];
        }

        $service = ServiceProvider::getService();

        $result = $service->removeGroup($group->getId());

        if ($result->isSuccess()) {
            return [
                'status' => Response::STATUS_NO_CONTENT
            ];
        } else {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data' => [
                    'message' => implode("\n", $result->getErrors())
                ]
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function privileges()
    {
        return [
            'create' => 'manage_catalog',
            'update' => 'manage_catalog',
            'delete' => 'manage_catalog',
            'index'  => 'view_catalog'
        ];
    }

    /**
     * @inheritDoc
     */
    public function childEntities()
    {
        return [
            'product_variations',
        ];
    }

    /**
     * @inheritDoc
     */
    public function isValidIdentifier($id)
    {
        return true;
    }

    protected function findGroup($id)
    {
        $repository = ServiceProvider::getGroupRepository();

        if (!is_numeric($id)) {
            $id = $repository->findGroupIdByCode((string) $id);
        }

        return $repository->findGroupById($id);
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\Group $group
     *
     * @return array
     */
    public static function convertGroupToArray(Group $group)
    {
        $data = $group->toArray(true);

        // Resets array keys
        $data['features'] = array_values($data['features']);
        $data['products'] = array_values(array_map(function (array $product) {
            $product['feature_values'] = array_values($product['feature_values']);
            return $product;
        }, $data['products']));

        return $data;
    }

    /**
     * @param array<int, Group> $groups
     *
     * @return array
     */
    public static function convertGroupsToArray(array $groups)
    {
        $result = [];

        foreach ($groups as $group) {
            $result[] = self::convertGroupToArray($group);
        }

        return $result;
    }

    public static function convertSaveGroupResultToResponse(
        OperationResult $result,
        $success_status = Response::STATUS_CREATED
    ) {
        if ($result->isSuccess()) {
            return [
                'status' => $success_status,
                'data'   => [
                    'message'         => implode("\n", $result->getWarnings()),
                    'group'           => self::convertGroupToArray($result->getData('group')),
                    'products_status' => $result->getData('products_status'),
                ],
            ];
        } else {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data' => [
                    'message' => implode("\n", $result->getErrors())
                ]
            ];
        }
    }

    public static function convertFeaturesToFeatureCollection(array $features)
    {
        $group_feature_collection = new GroupFeatureCollection();

        foreach ($features as $feature) {
            $group_feature = GroupFeature::createFromArray($feature);
            $group_feature_collection->addFeature($group_feature);
        }

        return $group_feature_collection;
    }
}