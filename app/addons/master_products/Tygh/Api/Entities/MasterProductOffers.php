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

use Tygh\Addons\MasterProducts\ServiceProvider;
use Tygh\Api\Response;
use Tygh\Enum\UserTypes;

class MasterProductOffers extends Products
{
    /**
     * @inheritDoc
     */
    public function index($id = 0, $params = [])
    {
        $data = '';
        $status = Response::STATUS_OK;

        if ($this->isParentMasterProduct()) {
            $master_product_id = $this->parent['product_id'];
            $repository = ServiceProvider::getProductRepository();
            $offers_ids = $repository->findVendorProductIds($master_product_id);
            if (!empty($id) && in_array($id, $offers_ids)) {
                $result = parent::index($id, $params);
                $data = $result['data'];
            } elseif (!$id && !empty($offers_ids)) {
                list($data,) = fn_get_products(['pid' => $offers_ids]);
            }
        } else {
            $status = Response::STATUS_BAD_REQUEST;
            $data = __('master_products.api_master_product_must_be_specified');
        }

        if (empty($data)) {
            $status = Response::STATUS_NOT_FOUND;
        }

        return [
            'status' => $status,
            'data'   => $data,
        ];
    }

    /**
     * @inheritDoc
     */
    public function create($params)
    {
        $data = [];
        $valid_params = true;
        $status = Response::STATUS_BAD_REQUEST;

        if ($this->isParentMasterProduct()) {
            if (UserTypes::isVendor($this->auth['user_type'])) {
                $params['company_id'] = $this->auth['company_id'];
            }

            if (empty($params['company_id'])) {
                $data['message'] = __('api_required_field', [
                    '[field]' => 'company_id'
                ]);
                $valid_params = false;
            }

            if ($valid_params) {
                $master_product_id = $this->parent['product_id'];
                $service = ServiceProvider::getService();
                $result = $service->createVendorProduct($master_product_id, $params['company_id']);
                if ($result->isSuccess()) {
                    $result_data = $result->getData();
                    $status = Response::STATUS_CREATED;
                    $data = [
                        'vendor_product_id' => $result_data['vendor_product_id'],
                    ];
                }
            }
        } else {
            $status = Response::STATUS_BAD_REQUEST;
            $data = __('master_products.api_master_product_must_be_specified');
        }

        return [
            'status' => $status,
            'data' => $data
        ];
    }

    /**
     * @inheritDoc
     */
    public function update($id, $params)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = __('master_products.api_master_product_must_be_specified');

        if ($this->isParentMasterProduct()) {
            $master_product_id = $this->parent['product_id'];
            $repository = ServiceProvider::getProductRepository();
            $offers_ids = $repository->findVendorProductIds($master_product_id);
            if (in_array($id, $offers_ids)) {
                $product_id_map = ServiceProvider::getProductIdMap();
                if (!$product_id_map->isVendorProduct($id)) {
                    return [
                        'status' => Response::STATUS_BAD_REQUEST,
                        'data' => __('master_products.api_not_vendor_product')
                    ];
                }

                return parent::update($id, $params);
            }

            $status = Response::STATUS_NOT_FOUND;
            $data = '';
        }

        return [
            'status' => $status,
            'data' => $data
        ];
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = __('master_products.api_master_product_must_be_specified');

        if ($this->isParentMasterProduct()) {
            $master_product_id = $this->parent['product_id'];
            $repository = ServiceProvider::getProductRepository();
            $offers_ids = $repository->findVendorProductIds($master_product_id);
            if (in_array($id, $offers_ids)) {
                $product_id_map = ServiceProvider::getProductIdMap();
                if (!$product_id_map->isVendorProduct($id)) {
                    return [
                        'status' => Response::STATUS_BAD_REQUEST,
                        'data' => __('master_products.api_not_vendor_product')
                    ];
                }

                return parent::delete($id);
            }

            $status = Response::STATUS_NOT_FOUND;
            $data = '';
        }

        return [
            'status' => $status,
            'data' => $data
        ];
    }

    /**
     * Checks whether entity has parent and it is master_products.
     *
     * @return bool
     */
    protected function isParentMasterProduct()
    {
        return $this->getParentName() === MasterProducts::ENTITY_NAME;
    }
}
