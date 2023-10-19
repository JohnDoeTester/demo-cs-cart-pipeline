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

namespace Tygh\Api\Entities\v40;

use Tygh\Addons\StorefrontRestApi\ASraEntity;
use Tygh\Api\Response;
use Tygh\Enum\SiteArea;
use Tygh\Enum\StorefrontStatuses;
use Tygh\Tygh;
use Tygh\Enum\YesNo;

/**
 * Class SraStorefront
 *
 * @package Tygh\Api\Entities
 */
class SraStorefront extends ASraEntity
{
    /** @inheritdoc */
    public function index($id = 0, $params = [])
    {
        $status = Response::STATUS_NOT_FOUND;

        if (empty($id)) {
            /** @var \Tygh\Storefront\Storefront $storefront */
            $storefront = Tygh::$app['storefront'];

            $id = $storefront->storefront_id;
        }

        $data = fn_storefront_rest_api_get_storefront($id);

        if ($data) {
            if (SiteArea::isStorefront($this->area) && $data['status'] === StorefrontStatuses::CLOSED) {
                $status = Response::STATUS_NOT_FOUND;
                $data = [];
            } else {
                $status = Response::STATUS_OK;
            }
        }

        return [
            'status' => $status,
            'data'   => $data
        ];
    }

    /** @inheritdoc */
    public function privileges()
    {
        return [
            'index' => true,
        ];
    }

    /** @inheritdoc */
    public function privilegesCustomer()
    {
        return [
            'index' => true,
        ];
    }

    /** @inheritdoc */
    public function create($params)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }

    /** @inheritdoc */
    public function update($id, $params)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }

    /** @inheritdoc */
    public function delete($id)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }
}
