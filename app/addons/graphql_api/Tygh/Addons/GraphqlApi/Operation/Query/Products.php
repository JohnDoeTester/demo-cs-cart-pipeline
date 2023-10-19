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

namespace Tygh\Addons\GraphqlApi\Operation\Query;

use Tygh\Addons\GraphqlApi\Context;
use Tygh\Addons\GraphqlApi\Operation\OperationInterface;
use Tygh\Enum\UserTypes;

class Products implements OperationInterface
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
        $currency = $this->context->getCurrencyCode();

        list($products) = fn_get_products([
            'page'       => $this->args['page'],
            'extend'     => ['full_description', 'description', 'companies'],
            'area'       => $this->context->getUserType(),
            'company_id' => $this->context->getUserType() === UserTypes::CUSTOMER
                ? $this->args['company_id']
                : $this->context->getCompanyId(),
        ], $this->args['items_per_page'], $this->context->getLanguageCode());

        fn_gather_additional_products_data($products, $this->args, $this->context->getLanguageCode());

        foreach ($products as &$product) {
            $product = fn_storefront_rest_api_format_product_prices($product, $currency);
        }

        return $products;
    }

    public function getPrivilege()
    {
        return 'view_catalog';
    }

    public function getCustomerPrivilege()
    {
        return false;
    }
}
