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

class Order implements OperationInterface
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

    /**
     * @return mixed
     */
    public function run()
    {
        $order_id = $this->args['id'];
        $company_id = $this->context->getCompanyId();

        /** @var \Tygh\Addons\GraphqlApi\Validator\OwnershipValidator $ownership_validator */
        $ownership_validator = $this->context->getApp()['graphql_api.validator.ownership'];
        if (!$ownership_validator->validateOrder($order_id, $company_id)) {
            return false;
        }

        $currency = $this->context->getCurrencyCode();

        $order = fn_get_order_info(
            $order_id,
            $this->args['native_language'],
            true,
            true,
            false,
            $this->context->getLanguageCode()
        );


        $statuses = fn_get_statuses(STATUSES_ORDER, [], false, false, $this->context->getLanguageCode());

        if ($order && isset($statuses[$order['status']])) {
            $order['status_data'] = [
                'status'      => $statuses[$order['status']]['status'],
                'description' => $statuses[$order['status']]['description'],
                'color'       => $statuses[$order['status']]['params']['color'],
            ];
        }

        if ($order) {
            $order = fn_storefront_rest_api_format_order_prices($order, $currency);
        }

        return $order;
    }

    /**
     * @return string|bool
     */
    public function getPrivilege()
    {
        return 'view_orders';
    }

    /**
     * @return string|bool
     */
    public function getCustomerPrivilege()
    {
        return false;
    }
}
