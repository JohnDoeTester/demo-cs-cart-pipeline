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

use Tygh\Addons\ProductReviews\ServiceProvider as ProductReviewsProvider;
use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Common\OperationResult;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\UserTypes;
use Tygh\Registry;

class ProductReviews extends AEntity
{
    /** @inheritdoc */
    public function index($id = '', $params = [])
    {
        $lang_code = $this->getLanguageCode($params);
        $product_reviews_repository = ProductReviewsProvider::getProductReviewRepository();
        $data = [];

        if (empty($id)) {
            $params['items_per_page'] = (int) $this->safeGet(
                $params,
                'items_per_page',
                Registry::get('settings.Appearance.admin_elements_per_page')
            );

            if (Registry::get('runtime.company_id')) {
                $params['company_id'] = (int) Registry::get('runtime.company_id');
            }
            $product_reviews = $product_reviews_repository->find($params, $lang_code);

            $data = [
                'product_reviews' => array_values($product_reviews),
                'params' => $params,
            ];
            $status = Response::STATUS_OK;
        } else {
            $product_reviews = $product_reviews_repository->findById($id, $lang_code);
            if (!empty($product_reviews)) {
                if (!$this->checkAccessToProduct($product_reviews['product']['product_id'])) {
                    return [
                        'status' => Response::STATUS_NOT_FOUND,
                        'data' => $data
                    ];
                }
                $data = $product_reviews;
                $status = Response::STATUS_OK;
            } else {
                $status = Response::STATUS_NOT_FOUND;
            }
        }

        return [
            'status' => $status,
            'data' => $data
        ];
    }

    /** @inheritdoc */
    public function create($params)
    {
        $service = ProductReviewsProvider::getService();

        $params['product_id']   = $this->safeGet($params, 'product_id', 0);
        $params['rating_value'] = (int) $this->safeGet($params, 'rating_value', 0);
        $params['comment']      = $this->safeGet($params, 'comment', 0);

        $checked = $this->getErrorChecksParams($params['product_id'], $params['rating_value'], $params['comment']);
        if ($checked->isFailure()) {
            return [
                'status' => $checked->getData(),
                'data'   => [
                    'errors'   => $checked->getErrors(),
                    'messages' => $checked->getMessages(),
                ],
            ];
        }

        $this->preparedImages($params);

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

    /** @inheritdoc */
    public function update($id, $params)
    {
        $product_reviews_repository = ProductReviewsProvider::getProductReviewRepository();
        $product_review = $product_reviews_repository->findOne(['product_review_id' => $id]);
        $service = ProductReviewsProvider::getService();
        $params['reply_user_id'] = $service->isAddReply($id, $params)
            ? $this->auth['user_id']
            : $service->getReplyUserId($id);
        $data = [];

        $is_allowed_update_reply = isset($params['reply']) && $service->isAllowUserUpdateReply($this->auth, $id);
        if (
            !isset($product_review['product_id'])
            || !$this->checkAccessToProduct($product_review['product_id'])
            || fn_allowed_for('MULTIVENDOR')
            && UserTypes::isVendor($this->auth['user_type'])
            && !$is_allowed_update_reply
        ) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data' => $data
            ];
        }

        if (
            $is_allowed_update_reply
            && fn_allowed_for('MULTIVENDOR')
            && UserTypes::isVendor($this->auth['user_type'])
        ) {
            $product_reviews_repository->updateReply($id, $params);
            return [
                'status' => Response::STATUS_OK,
                'data'   => ['product_review_id' => $id]
            ];
        }

        if (
            isset($params['comment'])
            && empty(trim($params['comment']))
        ) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data' => __('api_required_field', [
                    '[field]' => 'comment',
                ])
            ];
        }

        $product_reviews_repository->update($id, $params);
        if ($is_allowed_update_reply) {
            $product_reviews_repository->updateReply($id, $params);
        }

        return [
            'status' => Response::STATUS_OK,
            'data' => ['product_review_id' => $id]
        ];
    }

    /** @inheritdoc */
    public function delete($id)
    {
        $product_reviews_repository = ProductReviewsProvider::getProductReviewRepository();
        $data = [];

        if (
            fn_allowed_for('MULTIVENDOR')
            && UserTypes::isVendor($this->auth['user_type'])
        ) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data' => $data
            ];
        }

        $product_reviews = $product_reviews_repository->findById($id);
        if (!empty($product_reviews)) {
            if (!$this->checkAccessToProduct($product_reviews['product']['product_id'])) {
                return [
                    'status' => Response::STATUS_NOT_FOUND,
                    'data' => $data
                ];
            }

            $product_reviews_repository->delete($id);
            $status = Response::STATUS_NO_CONTENT;
        } else {
            $status = Response::STATUS_NOT_FOUND;
        }

        return [
            'status' => $status,
            'data' => $data
        ];
    }

    /** @inheritdoc */
    public function privileges()
    {
        if (!static::isAddonEnabled()) {
            return [];
        }

        return [
            'index'  => 'view_product_reviews',
            'create' => 'create_product_reviews',
            'update' => 'manage_product_reviews',
            'delete' => 'manage_product_reviews'
        ];
    }

    /** @inheritdoc */
    public function privilegesCustomer()
    {
        if (!static::isAddonEnabled()) {
            return [];
        }

        return [
            'index'  => true,
            'create' => false,
            'update' => false,
            'delete' => false,
        ];
    }

    /**
     * Checks whether the Product reviews add-on enabled.
     *
     * @return bool
     */
    public static function isAddonEnabled()
    {
        return Registry::ifGet('addons.product_reviews.status', ObjectStatuses::DISABLED) === ObjectStatuses::ACTIVE;
    }

    /**
     * Get the error code end message from parameters checks
     *
     * @param string $product_id   Product identifier
     * @param int    $rating_value Rating value
     * @param string $comment      Comment
     *
     * @return OperationResult
     */
    protected function getErrorChecksParams($product_id, $rating_value, $comment)
    {
        $result = new OperationResult(true, Response::STATUS_BAD_REQUEST);

        if (
            empty($product_id)
            || !$this->checkAccessToProduct($product_id)
        ) {
            $result->setSuccess(false);
            $result->addError(
                'invalid_value',
                __('api_invalid_value', [
                    '[field]' => 'product_id',
                    '[value]' => $product_id
                ])
            );
        }

        if (empty($rating_value)) {
            $result->setSuccess(false);
            $result->addError(
                'required_field',
                __('api_required_field', ['[field]' => 'rating_value'])
            );
        } elseif (!$this->checkRatingValue($rating_value)) {
            $result->setSuccess(false);
            $result->addError(
                'invalid_value',
                __('api_invalid_value', [
                    '[field]' => 'rating_value',
                    '[value]' => $rating_value
                ])
            );
        }

        if (empty(trim($comment))) {
            $result->setSuccess(false);
            $result->addError(
                'required_field',
                __('api_required_field', ['[field]' => 'comment'])
            );
        }

        return $result;
    }

    /**
     * Checks permission to add product review
     *
     * @param int|string $product_id Product identifier
     *
     * @return bool Permission availability
     */
    protected function checkAccessToProduct($product_id)
    {
        $product_company_id = fn_get_company_id('products', 'product_id', $product_id);

        if (
            Registry::get('runtime.company_id')
            && Registry::get('runtime.company_id') !== $product_company_id
        ) {
            return false;
        }

        return true;
    }

    /**
     * Checks the correctness of the selected rating value
     *
     * @param int $rating_value Rating value
     *
     * @return bool Selectable rating value
     */
    protected function checkRatingValue($rating_value)
    {
        if ($rating_value > 0 && $rating_value <= 5) {
            return true;
        }

        return false;
    }

    /**
     * Prepare of uploaded images for product reviews.
     *
     * @param array<string, string|int|array> $params Review data
     *
     * @return void
     */
    protected function preparedImages($params)
    {
        if (
            !isset($params['main_pair'])
            || empty($params['main_pair']['icon']['image_path'])
        ) {
            return;
        }

        $_REQUEST['file_product_review_data'] = [];
        $_REQUEST['type_product_review_data'] = [];

        if (is_array($params['main_pair']['icon']['image_path'])) {
            $_REQUEST['file_product_review_data'] = $params['main_pair']['icon']['image_path'];
            foreach ($params['main_pair']['icon']['image_path'] as $_id => $path) {
                if (strpos($path, '://') === false) {
                    $_REQUEST['type_product_review_data'][$_id] = 'server';
                } else {
                    $_REQUEST['type_product_review_data'][$_id] = 'url';
                }
            }
        } else {
            $_REQUEST['file_product_review_data'][] = $params['main_pair']['icon']['image_path'];
            $_REQUEST['type_product_review_data'][] = (strpos($params['main_pair']['icon']['image_path'], '://') === false)
                ? 'server'
                : 'url';
        }
    }
}
