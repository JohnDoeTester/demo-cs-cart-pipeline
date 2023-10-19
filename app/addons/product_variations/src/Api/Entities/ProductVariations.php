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
use Tygh\Api\Response;
use Tygh\Common\OperationResult;

class ProductVariations extends Products
{
    const ENTITY_NAME = 'product_variations';

    /**
     * @inheritDoc
     */
    public function index($id = 0, $params = [])
    {
        if (!$id) {
            $params = array_merge($params, [
                'include_child_variations' => true,
                'group_child_variations'   => false,
                'has_variation_group'      => true
            ]);

            if ($this->isParentProductVariationsGroups()) {
                $group = $this->getParentData();
                $params['variation_group_id'] = $group['id'];
            }
        }

        return parent::index($id, $params);
    }

    /**
     * @inheritDoc
     */
    public function create($params)
    {
        if ($this->isParentProductVariationsGroups()) {
            $group = $this->getParentData();
            $params['variation_group_id'] = $group['id'];
        }

        $result = parent::create($params);

        if (empty($params['variation_group_id'])) {
            return $result;
        }

        if ($result['status'] === Response::STATUS_CREATED) {
            $operation_result = $this->updateProductVariationGroup(
                $result['data']['product_id'],
                $params['variation_group_id']
            );

            if ($operation_result->hasErrors()) {
                $result['data']['message'] = implode("\n", $operation_result->getErrors());
            } elseif ($operation_result->hasWarnings()) {
                $result['data']['message'] = implode("\n", $operation_result->getWarnings());
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function update($id, $params)
    {
        $result = parent::update($id, $params);

        if (empty($params['variation_group_id'])) {
            return $result;
        }

        if ($result['status'] === Response::STATUS_OK) {
            $operation_result = $this->updateProductVariationGroup(
                $result['data']['product_id'],
                $params['variation_group_id']
            );

            if ($operation_result->hasErrors()) {
                $result['data']['message'] = implode("\n", $operation_result->getErrors());
            } elseif ($operation_result->hasWarnings()) {
                $result['data']['message'] = implode("\n", $operation_result->getWarnings());
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        return parent::delete($id);
    }

    /**
     * @inheritDoc
     */
    public function isValidIdentifier($id)
    {
        return $this->validateProductId($id);
    }

    protected function isParentProductVariationsGroups()
    {
        return $this->getParentName() === ProductVariationsGroups::ENTITY_NAME;
    }

    protected function validateProductId($id)
    {
        if ($this->isParentProductVariationsGroups()) {
            $group = $this->getParentData();
            $product_ids = array_column($group['products'], 'product_id', 'product_id');

            if (!isset($product_ids[$id])) {
                return false;
            }
        }

        return true;
    }

    protected function updateProductVariationGroup($product_id, $group_id)
    {
        $service = ServiceProvider::getService();
        $group_repository = ServiceProvider::getGroupRepository();

        $current_group_id = $group_repository->findGroupIdByProductId($product_id);

        if ($current_group_id !== $group_id) {
            return $service->moveProductsToGroup($group_id, [$product_id]);
        }

        return new OperationResult(true);
    }
}
