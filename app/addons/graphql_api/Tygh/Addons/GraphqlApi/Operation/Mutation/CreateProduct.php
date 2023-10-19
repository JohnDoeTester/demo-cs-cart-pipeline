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

class CreateProduct extends UpdateProduct
{
    public function run()
    {
        $product_id = 0;
        $company_id = $this->context->getCompanyId();
        if ($company_id) {
            $this->args['product']['company_id'] = $company_id;
        }

        $this->prepareFeatures($this->args['product']);
        $this->prepareImages($this->args['product'], $product_id);
        $this->prepareShippingParameters($this->args['product']);

        $result = fn_update_product($this->args['product'], $product_id, $this->context->getLanguageCode());

        return $result;
    }
}
