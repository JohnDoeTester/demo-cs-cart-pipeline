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

use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Common\OperationResult;
use Tygh\Enum\ProductOptionTypes;
use Tygh\Enum\YesNo;
use Tygh\Registry;

class Options extends AEntity
{
    public function index($id = 0, $params = array())
    {
        $lang_code = $this->getLanguageCode($params);

        if (empty($id) && empty($params['product_id'])) {

            $items_per_page = $this->safeGet($params, 'items_per_page', Registry::get('settings.Appearance.admin_elements_per_page'));

            list($product_options, $params) = fn_get_product_global_options($params, $items_per_page, $lang_code);

            $data = [
                'product_options' => array_values($product_options),
                'params' => $params,
            ];
            $status = Response::STATUS_OK;
        } else {
            $params['get_variants'] = YesNo::YES;
            if (!isset($params['product_id'])) {
                $params['product_id'] = db_get_field('SELECT product_id FROM ?:product_options WHERE option_id = ?i', $id);
            }

            if ((int) $params['product_id'] === 0) {
                $params['option_id'] = $id;
                list($product_options) = fn_get_product_global_options($params, 1, $lang_code);

                if (!empty($product_options)) {
                    $data = reset($product_options);
                    $status = Response::STATUS_OK;
                } else {
                    $data = [];
                    $status = Response::STATUS_NOT_FOUND;
                }
            } else {
                $product_data = fn_get_product_data(
                    (int) $params['product_id'],
                    $this->auth,
                    $lang_code,
                    '',
                    false,
                    false,
                    false,
                    false,
                    false,
                    false,
                    false
                );
                if (empty($product_data)) {
                    $status = Response::STATUS_NOT_FOUND;
                    $data = [];
                } else {
                    $status = Response::STATUS_OK;
                    if (!empty($id)) {
                        $data = fn_get_product_option_data($id, $params['product_id'], $lang_code);
                    } else {
                        $data = fn_get_product_options((int) $params['product_id'], $lang_code);
                    }

                    if (empty($data)) {
                        $status = Response::STATUS_NOT_FOUND;
                    }
                }
            }
        }

        return [
            'status' => $status,
            'data' => $data
        ];
    }

    public function create($params)
    {
        $data = [];
        $params['product_id'] = $this->safeGet($params, 'product_id', 0);
        $params['company_id'] = (isset($params['company_id']))
            ? $this->getCompanyId($params['company_id'])
            : $this->getCompanyId();

        if (
            fn_allowed_for('ULTIMATE')
            && !$this->checkUltimateCompanyId($params['company_id'])
        ) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data' => __('api_required_field', [
                    '[field]' => 'company_id',
                ])
            ];
        }

        $params['variants'] = $this->safeGet($params, 'variants', []);
        $params['option_type'] = $this->safeGet($params, 'option_type', '');

        $checked = $this->getErrorChecksParams($params['company_id'], $params['product_id'], $params['variants'], $params['option_type']);
        if ($checked->isFailure()) {
            return [
                'status' => $checked->getData(),
                'data'   => [
                    'errors'   => $checked->getErrors(),
                    'messages' => $checked->getMessages(),
                ],
            ];
        }

        if (empty($params['option_type'])) {
            unset($params['option_type']);
        }

        $lang_code = $this->getLanguageCode($params);
        $this->prepareImages($params, 0, 'variant_image', 'V');
        unset($params['option_id']);

        $option_id = fn_update_product_option($params, 0, $lang_code);
        if ($option_id) {
            $status = Response::STATUS_CREATED;
            $data = ['option_id' => $option_id];
        } else {
            $status = Response::STATUS_BAD_REQUEST;
        }

        return [
            'status' => $status,
            'data' => $data
        ];
    }

    public function update($id, $params)
    {
        $status = Response::STATUS_OK;
        $data = [];

        if (
            !$this->checkOptionId($id)
            || !$this->checkAccessToCompany($id)
        ) {
            return [
                'status' => Response::STATUS_NOT_FOUND,
                'data' => $data
            ];
        }

        unset($params['option_id']);
        if (!isset($params['product_id'])) {
            $params['product_id'] = db_get_field('SELECT product_id FROM ?:product_options WHERE option_id = ?i', $id);
        }
        if (!isset($params['company_id'])) {
            $params['company_id'] = (int) db_get_field('SELECT company_id FROM ?:product_options WHERE option_id = ?i', $id);
        }

        $params['company_id'] = $this->getCompanyId($params['company_id']);
        $params['variants'] = $this->safeGet($params, 'variants', []);
        $params['option_type'] = $this->safeGet($params, 'option_type', '');

        $checked = $this->getErrorChecksParams($params['company_id'], $params['product_id'], $params['variants'], $params['option_type']);
        if ($checked->isFailure()) {
            return [
                'status' => $checked->getData(),
                'data'   => [
                    'errors'   => $checked->getErrors(),
                    'messages' => $checked->getMessages(),
                ],
            ];
        }

        if (empty($params['option_type'])) {
            unset($params['option_type']);
        }

        $lang_code = $this->getLanguageCode($params);
        $this->prepareImages($params, 0, 'variant_image', 'V');

        $option_id = fn_update_product_option($params, $id, $lang_code);
        if ($option_id) {
            $data = ['option_id' => $option_id];
        } else {
            $status = Response::STATUS_BAD_REQUEST;
        }

        return [
            'status' => $status,
            'data' => $data
        ];
    }

    public function delete($id)
    {
        $data = [];

        if (fn_delete_product_option($id)) {
            $status = Response::STATUS_NO_CONTENT;
        } else {
            $status = Response::STATUS_NOT_FOUND;
        }

        return [
            'status' => $status,
            'data' => $data
        ];
    }

    public function privileges()
    {
        return [
            'create' => 'manage_catalog',
            'update' => 'manage_catalog',
            'delete' => 'manage_catalog',
            'index'  => 'view_catalog'
        ];
    }

    public function privilegesCustomer()
    {
        return [
            'index' => true
        ];
    }

    /**
     * Get the error code end message from parameters checks
     *
     * @param string                          $company_id  Company identifier
     * @param string                          $product_id  Product identifier
     * @param array<string, string|int|array> $variants    Variants option
     * @param string                          $option_type Option type
     *
     * @return OperationResult
     */
    protected function getErrorChecksParams($company_id, $product_id, $variants, $option_type)
    {
        $result = new OperationResult(true, Response::STATUS_BAD_REQUEST);

        if (!$this->checkExistCompany($company_id)) {
            $result->setSuccess(false);
            $result->addError(
                'options.need_correct_company_id',
                __('api_need_correct_company_id')
            );
        }

        if (
            !empty($product_id)
            && !$this->checkAccessToProduct($product_id)
        ) {
            $result->setSuccess(false);
            $result->addError(
                'options.invalid_value',
                __('api_invalid_value', [
                    '[field]' => 'product_id',
                    '[value]' => $product_id
                ])
            );
        }

        if (!empty($variants)) {
            if (empty($option_type)) {
                $result->setSuccess(false);
                $result->addError(
                    'options.required_field',
                    __('api_required_field', ['[field]' => 'option_type'])
                );
            } elseif (!$this->checkSelectableOptionType($option_type)) {
                $result->setSuccess(false);
                $result->addError(
                    'options.invalid_value',
                    __('api_invalid_value', [
                        '[field]' => 'option_type',
                        '[value]' => $option_type
                    ])
                );
            }
        }

        if (!empty($option_type)) {
            if (!$this->checkOptionType($option_type)) {
                $result->setSuccess(false);
                $result->addError(
                    'options.invalid_value',
                    __('api_invalid_value', [
                        '[field]' => 'option_type',
                        '[value]' => $option_type
                    ])
                );
            }
        }

        return $result;
    }

    /**
     * Get company identifier after verification
     *
     * @param int $company_id Sent company identifier
     *
     * @return string Verified company identifier
     */
    protected function getCompanyId($company_id = 0)
    {
        if (Registry::get('runtime.simple_ultimate')) {
            $company_id = Registry::get('runtime.forced_company_id');
        } else {
            $data = ['company_id' => $company_id];
            fn_set_company_id($data);
            $company_id = $data['company_id'];
        }

        return $company_id;
    }

    /**
     * Checks the existence of the option
     *
     * @param int $option_id Option identifier
     *
     * @return bool The option exists
     */
    protected function checkOptionId($option_id)
    {
        return (bool) db_get_field(
            'SELECT COUNT(*) FROM ?:product_options WHERE option_id = ?i',
            $option_id
        );
    }

    /**
     * Checks the company identifier if there are multiple storefronts
     *
     * @param string $company_id Company identifier
     *
     * @return bool The company ID sent
     */
    protected function checkUltimateCompanyId($company_id)
    {
        if (
            (int) $company_id === 0
            && Registry::get('runtime.is_multiple_storefronts')
        ) {
            return false;
        }
        return true;
    }

    /**
     * Checks permission to change company options
     *
     * @param int $option_id Option identifier
     *
     * @return bool Permission availability
     */
    protected function checkAccessToCompany($option_id)
    {
        if ((int) $this->auth['company_id'] === 0) {
            return true;
        }

        $company_id = db_get_field('SELECT company_id FROM ?:product_options WHERE option_id = ?i', $option_id);
        return $this->auth['company_id'] === $company_id;
    }

    /**
     * Checks the existence of the selectable option type if there are variants
     *
     * @param string $option_type Option type
     *
     * @return bool Selectable option type
     */
    protected function checkSelectableOptionType($option_type)
    {
        if (!empty($option_type)) {
            return ProductOptionTypes::isSelectable($option_type);
        }
        return false;
    }

    /**
     * Checks the existence of the option type
     *
     * @param string $option_type Option type
     *
     * @return bool Option type exists
     */
    protected function checkOptionType($option_type)
    {
        if (
            in_array($option_type, [
                ProductOptionTypes::SELECTBOX,
                ProductOptionTypes::RADIO_GROUP,
                ProductOptionTypes::CHECKBOX,
                ProductOptionTypes::INPUT,
                ProductOptionTypes::TEXT,
                ProductOptionTypes::FILE
            ], true)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Checks the existence of the company
     *
     * @param string $company_id Company identifier
     *
     * @return bool The company exists
     */
    protected function checkExistCompany($company_id)
    {
        if (!empty($company_id)) {
            return (bool) db_get_field(
                'SELECT COUNT(*) FROM ?:companies WHERE company_id = ?i',
                $company_id
            );
        }
        if (fn_allowed_for('ULTIMATE')) {
            return false;
        }

        return true;
    }

    /**
     * Checks permission to change product options
     *
     * @param string $product_id Product identifier
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
}
