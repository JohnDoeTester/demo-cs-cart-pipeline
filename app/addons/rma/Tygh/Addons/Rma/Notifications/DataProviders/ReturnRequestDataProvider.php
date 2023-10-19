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

namespace Tygh\Addons\Rma\Notifications\DataProviders;

use Tygh\Enum\UserTypes;
use Tygh\Exceptions\DeveloperException;
use Tygh\Notifications\Data;
use Tygh\Notifications\DataProviders\BaseDataProvider;
use Tygh\Registry;

class ReturnRequestDataProvider extends BaseDataProvider
{
    protected $order_info = [];

    protected $return_info = [];

    public function __construct(array $data)
    {
        if (empty($data['order_info'])) {
            throw new DeveloperException('The order_info must be defined.');
        }

        if (empty($data['return_info'])) {
            throw new DeveloperException('The return_info must be defined.');
        }

        $this->order_info = $data['order_info'];
        $this->return_info = $data['return_info'];

        parent::__construct($data);
    }

    public function get($recipient_type)
    {
        switch ($recipient_type) {
            case UserTypes::CUSTOMER:
                $data = $this->getForCustomer();
                break;
            case UserTypes::ADMIN:
                $data = $this->getForAdmin();
                break;
            case UserTypes::VENDOR:
                $data = $this->getForVendor();
                break;
            default:
                return parent::get($recipient_type);
        }

        return new Data(array_merge($this->data, $data));
    }

    protected function getForCustomer()
    {
        $lang_code = empty($this->order_info['lang_code'])
            ? CART_LANGUAGE
            : $this->order_info['lang_code'];

        $rma_reasons = fn_get_rma_properties(RMA_REASON, $lang_code);
        $rma_actions = fn_get_rma_properties(RMA_ACTION, $lang_code);
        $return_status = fn_get_status_data(
            $this->return_info['status'],
            STATUSES_RETURN,
            $this->return_info['return_id'],
            $lang_code
        );

        return [
            'lang_code'     => $lang_code,
            'reasons'       => $rma_reasons,
            'actions'       => $rma_actions,
            'return_status' => $return_status,
        ];
    }

    protected function getForAdmin()
    {
        $lang_code = Registry::get('settings.Appearance.backend_default_language');

        $rma_reasons = fn_get_rma_properties(RMA_REASON, $lang_code);
        $rma_actions = fn_get_rma_properties(RMA_ACTION, $lang_code);
        $return_status = fn_get_status_data(
            $this->return_info['status'],
            STATUSES_RETURN,
            $this->return_info['return_id'],
            $lang_code
        );

        return [
            'lang_code'     => $lang_code,
            'reasons'       => $rma_reasons,
            'actions'       => $rma_actions,
            'return_status' => $return_status,
        ];
    }

    protected function getForVendor()
    {
        $lang_code = fn_get_company_language($this->order_info['company_id']);

        $rma_reasons = fn_get_rma_properties(RMA_REASON, $lang_code);
        $rma_actions = fn_get_rma_properties(RMA_ACTION, $lang_code);
        $return_status = fn_get_status_data(
            $this->return_info['status'],
            STATUSES_RETURN,
            $this->return_info['return_id'],
            $lang_code
        );

        return [
            'lang_code'     => $lang_code,
            'reasons'       => $rma_reasons,
            'actions'       => $rma_actions,
            'return_status' => $return_status,
        ];
    }
}