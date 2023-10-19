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

class UpdateOrder implements OperationInterface
{
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
     * @return bool
     */
    public function run()
    {
        $order_id = $this->args['id'];
        $order_data = $this->args['order'];
        $company_id = $this->context->getCompanyId();

        /** @var \Tygh\Addons\GraphqlApi\Validator\OwnershipValidator $ownership_validator */
        $ownership_validator = $this->context->getApp()['graphql_api.validator.ownership'];
        if (!$ownership_validator->validateOrder($order_id, $company_id)) {
            return false;
        }

        if ($order_data['status'] !== null) {
            fn_change_order_status(
                $order_id,
                $order_data['status'],
                '',
                fn_get_notification_rules($this->args, false)
            );
        }

        if ($order_data['update_shipping'] !== null) {
            foreach ($order_data['update_shipping'] as $shipment_info) {
                $shipment_info['order_id'] = $order_id;
                fn_update_shipment(
                    $shipment_info,
                    $shipment_info['shipment_id'],
                    $shipment_info['group_id'],
                    true
                );
            }
        }

        $order_data['updated_at'] = isset($order_data['updated_at']) ? fn_parse_date($order_data['updated_at']) : TIME;

        /** @var \Tygh\Database\Connection $db */
        $db = $this->context->getApp()['db'];

        $db->query('UPDATE ?:orders SET ?u WHERE order_id = ?i', $order_data, $order_id);

        return true;
    }

    /**
     * @return string|bool
     */
    public function getPrivilege()
    {
        return 'edit_order';
    }

    /**
     * @return string|bool
     */
    public function getCustomerPrivilege()
    {
        return false;
    }
}
