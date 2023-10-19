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

namespace Tygh\Addons\GraphqlApi\Operation\Mutation;

use Tygh\Addons\GraphqlApi\Context;
use Tygh\Addons\GraphqlApi\Operation\OperationInterface;
use Tygh\Enum\ProductFeatures;

class UpdateProduct implements OperationInterface
{
    /**
     * @var mixed
     */
    protected $source;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var \Tygh\Addons\GraphqlApi\Context
     */
    protected $context;

    public function __construct($source, array $args, Context $context)
    {
        $this->source = $source;
        $this->args = $args;
        $this->context = $context;
    }

    public function run()
    {
        $product_id = $this->args['id'];
        $company_id = $this->context->getCompanyId();

        /** @var \Tygh\Addons\GraphqlApi\Validator\OwnershipValidator $ownership_validator */
        $ownership_validator = $this->context->getApp()['graphql_api.validator.ownership'];
        if (!$ownership_validator->validateProduct($product_id, $company_id)) {
            return false;
        }

        $this->prepareFeatures($this->args['product']);
        $this->prepareImages($this->args['product'], $product_id);
        $this->prepareShippingParameters($this->args['product']);

        $result = (bool) fn_update_product($this->args['product'], $product_id, $this->context->getLanguageCode());

        return $result;
    }

    /**
     * Populates superglobals with images data.
     * FIXME: Refactor file uploads completely to prevent superglobals dependencies and remove this method.
     *
     * @param array $params
     * @param int   $product_id
     */
    public function prepareImages(array $params, $product_id = 0)
    {
        if (isset($params['main_pair'])) {
            $main_img_id = 'product_main_image_detailed';
            $main_img_icon_id = 'product_main_image_icon';

            $_REQUEST["file_{$main_img_id}"] = [];
            $_REQUEST["type_{$main_img_id}"] = [];
            $_REQUEST["file_{$main_img_icon_id}"] = [];
            $_REQUEST["type_{$main_img_icon_id}"] = [];
            $_REQUEST['product_main_image_data'] = [];

            if ($product_id) {
                $products_images = fn_get_image_pairs($product_id, 'product', 'M');
                if (!empty($products_images)) {
                    fn_delete_image_pair($products_images['pair_id']);
                }
            }

            $main_img = $params['main_pair']['detailed'] ?? [];
            $main_img_icon = $params['main_pair']['icon'] ?? [];

            if (!empty($main_img['image_path'])) {
                $_REQUEST["file_{$main_img_id}"][] = $main_img['image_path'];
                $_REQUEST["type_{$main_img_id}"][] = strpos($main_img['image_path'], '://') === false
                    ? 'server'
                    : 'url';
            } elseif (!empty($main_img['upload'])) {
                $_FILES["file_{$main_img_id}"] = $main_img['upload'];
                $_REQUEST["type_{$main_img_id}"][] = 'local';
            }

            if (!empty($main_img_icon['image_path'])) {
                $_REQUEST["file_{$main_img_icon_id}"][] = $main_img_icon['image_path'];
                $_REQUEST["type_{$main_img_icon_id}"][] = strpos($main_img_icon['image_path'], '://') === false
                    ? 'server'
                    : 'url';
            } elseif (!empty($main_img_icon['upload'])) {
                $_FILES["file_{$main_img_icon_id}"] = $main_img_icon['upload'];
                $_REQUEST["type_{$main_img_icon_id}"][] = 'local';
            }

            $_REQUEST['product_main_image_data'][] = [
                'pair_id'      => 0,
                'type'         => 'M',
                'object_id'    => 0,
                'detailed_alt' => $main_img['alt'] ?? '',
                'image_alt'    => $main_img_icon['alt'] ?? '',
            ];
        }

        if (isset($params['image_pairs'])) {
            $pair_id = 'product_add_additional_image_detailed';
            $pair_icon_id = 'product_add_additional_image_icon';

            $_REQUEST["file_{$pair_id}"] = [];
            $_REQUEST["type_{$pair_id}"] = [];
            $_REQUEST["file_{$pair_icon_id}"] = [];
            $_REQUEST["type_{$pair_icon_id}"] = [];
            $_REQUEST['product_add_additional_image_data'] = [];

            if ($product_id) {
                $additional_images = fn_get_image_pairs($product_id, 'product', 'A');
                foreach ($additional_images as $additional_image) {
                    fn_delete_image_pair($additional_image['pair_id']);
                }
            }

            foreach ($params['image_pairs'] as $additional_image) {
                $pair = $additional_image['detailed'] ?? [];
                $pair_icon = $additional_image['icon'] ?? [];

                if (!empty($pair['image_path'])) {
                    $_REQUEST["file_{$pair_id}"][] = $pair['image_path'];
                    $_REQUEST["type_{$pair_id}"][] = strpos($pair['image_path'], '://') === false
                        ? 'server'
                        : 'url';
                } elseif (!empty($pair['upload'])) {
                    foreach ($pair['upload'] as $field => $value) {
                        $_FILES["file_{$pair_id}"][$field] = $_FILES["file_{$pair_id}"][$field] ?? [];
                        $_FILES["file_{$pair_id}"][$field][] = reset($value);
                    }
                    $_REQUEST["type_{$pair_id}"][] = 'local';
                }

                if (!empty($pair_icon['image_path'])) {
                    $_REQUEST["file_{$pair_icon_id}"][] = $pair_icon['image_path'];
                    $_REQUEST["type_{$pair_icon_id}"][] = strpos($pair_icon['image_path'], '://') === false
                        ? 'server'
                        : 'url';
                } elseif (!empty($pair_icon['upload'])) {
                    foreach ($pair_icon['upload'] as $field => $value) {
                        $_FILES["file_{$pair_icon_id}"][$field] = $_FILES["file_{$pair_icon_id}"][$field] ?? [];
                        $_FILES["file_{$pair_icon_id}"][$field][] = reset($value);
                    }
                    $_REQUEST["type_{$pair_icon_id}"][] = 'local';
                }

                $_REQUEST['product_add_additional_image_data'][] = [
                    'position'     => $additional_image['position'] ?? 0,
                    'pair_id'      => 0,
                    'type'         => 'A',
                    'object_id'    => 0,
                    'detailed_alt' => $pair['alt'] ?? '',
                    'image_alt'    => $pair_icon['alt'] ?? '',
                ];
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getPrivilege()
    {
        return 'manage_catalog';
    }

    /**
     * @inheritDoc
     */
    public function getCustomerPrivilege()
    {
        return false;
    }

    protected function prepareShippingParameters($product)
    {
        if (empty($product['shipping_params'])) {
            return;
        }

        foreach ($product['shipping_params'] as $key => $value) {
            $this->args['product'][$key] = $value;
        }

        unset($this->args['shipping_params']);
    }

    protected function prepareFeatures($product)
    {
        if (empty($product['product_features'])) {
            return;
        }

        $rebuilt_features = [];

        foreach ($product['product_features'] as $i => $feature_data) {
            $feature_id = $feature_data['feature_id'];
            $feature_type = fn_get_product_feature_type_by_feature_id($feature_id);

            $value = $feature_data['value'];
            if ($feature_data['variants']) {
                $value = [];
                foreach ($feature_data['variants'] as $variant_data) {
                    if ($variant_data['variant_id'] === null) {
                        $this->args['product']['add_new_variant'][$feature_id]['variant'] = $variant_data['variant'];
                        continue;
                    }

                    if ($variant_data['selected']) {
                        $value[] = $variant_data['variant_id'];
                    }
                }
            }

            $rebuilt_features[$feature_id] = $value;
            if ($feature_type === ProductFeatures::MULTIPLE_CHECKBOX || $feature_type === ProductFeatures::DATE || empty($value) || !is_array($value)) {
                continue;
            }
            $rebuilt_features[$feature_id] = reset($value);
        }

        $this->args['product']['product_features'] = $rebuilt_features;
    }
}
