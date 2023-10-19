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

use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Api\AEntity;
use Tygh\Api\Response;

class DetachProductVariation extends AEntity
{
    /**
     * @inheritDoc
     */
    public function index($id = '', $params = [])
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }

    /**
     * @inheritDoc
     */
    public function create($params = [])
    {
        if ($this->getParentName() === ProductVariations::ENTITY_NAME) {
            $data = $this->getParentData();
            $params['product_id'] = $data['product_id'];
        }

        if (empty($params['product_id'])) {
            return [
                'status' => Response::STATUS_BAD_REQUEST
            ];
        }

        $service = ServiceProvider::getService();
        $group_repository = ServiceProvider::getGroupRepository();

        $group_id = $group_repository->findGroupIdByProductId($params['product_id']);

        $result = $service->detachProductFromGroup($group_id, $params['product_id']);

        if ($result->isSuccess()) {
            return [
                'status' => Response::STATUS_CREATED,
                'data'   => [
                    'message' => implode("\n", $result->getWarnings()),
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

    /**
     * @inheritDoc
     */
    public function update($id, $params)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
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
}