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
use Tygh\Enum\UserTypes;

class SraOrderStatuses extends ASraEntity
{
    /**
     * @inheritDoc
     */
    public function index($id = '', $params = [])
    {
        return [
            'status' => Response::STATUS_OK,
            'data'   => fn_storefront_rest_api_get_formatted_orders_statuses(
                $this->getLanguageCode($params),
                $this->safeGet($params, 'additional_statuses', false)
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function create($params)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
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
            'index' => 'view_orders',
        ];
    }

    /**
     * @inheritDoc
     */
    public function privilegesCustomer()
    {
        return [
            'index' => $this->auth['user_type'] !== UserTypes::CUSTOMER ? 'view_orders' : false,
        ];
    }
}
