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

use Tygh\Addons\ProductReviews\ServiceProvider as ProductReviewsProvider;
use Tygh\Api\Entities\ProductReviews;
use Tygh\Api\Response;

class SraProductReviews extends ProductReviews
{

    /** @inheritdoc */
    public function index($id = '', $params = [])
    {
        return [
            'status' => Response::STATUS_FORBIDDEN,
        ];
    }

    /**
     * @param array<string|int> $params Params
     *
     * @return array<string, int|array<string, string|array<string>>>
     */
    public function create($params)
    {
        $service = ProductReviewsProvider::getService();

        /** @var \Tygh\Common\OperationResult $result */
        $result = $service->createProductReview($params, $this->auth);

        if ($result->isSuccess()) {
            return [
                'status' => Response::STATUS_OK,
                'data'   => [
                    'product_review_id' => $result->getData('product_review_id'),
                    'messages'          => $result->getMessages(),
                ],
            ];
        }

        return [
            'status' => Response::STATUS_BAD_REQUEST,
            'data'   => [
                'errors'   => $result->getErrors(),
                'messages' => $result->getMessages(),
            ],
        ];
    }

    /**
     * Forbids updating reviews via API.
     *
     * @param int                        $id     Product review ID
     * @param array<string, string>|null $params Request parameters
     *
     * @return array<string, int>
     */
    public function update($id, $params)
    {
        return [
            'status' => Response::STATUS_FORBIDDEN,
        ];
    }

    /**
     * Forbids removing reviews via API.
     *
     * @param int $id Product review ID
     *
     * @return array<string, int>
     */
    public function delete($id)
    {
        return [
            'status' => Response::STATUS_FORBIDDEN,
        ];
    }

    /** @inheritdoc */
    public function privileges()
    {
        return [];
    }

    /** @inheritdoc */
    public function privilegesCustomer()
    {
        if (!static::isAddonEnabled()) {
            return [];
        }

        return [
            'index'  => false,
            'create' => true,
            'update' => false,
            'delete' => false,
        ];
    }
}
