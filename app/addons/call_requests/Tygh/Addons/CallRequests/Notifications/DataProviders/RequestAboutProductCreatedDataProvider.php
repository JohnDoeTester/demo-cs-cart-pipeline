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

namespace Tygh\Addons\CallRequests\Notifications\DataProviders;

use Tygh\Exceptions\DeveloperException;
use Tygh\Notifications\DataProviders\BaseDataProvider;

class RequestAboutProductCreatedDataProvider extends BaseDataProvider
{
    protected $call_request = [];

    public function __construct(array $data)
    {
        if (empty($data['call_request_data'])) {
            throw new DeveloperException('The call request must be defined.');
        }

        $this->call_request = $data['call_request_data'];

        $data['url'] = $this->getUrl();
        $data['customer'] = $this->call_request['name'];
        $data['phone_number'] = $this->call_request['phone'];
        $data['product_url'] = $this->getProductUrl();
        $data['product_name'] = $this->getProductName();

        parent::__construct($data);
    }

    protected function getUrl()
    {
        if (fn_allowed_for('MULTIVENDOR')) {
            return fn_url('call_requests.manage?id=' . $this->call_request['request_id'], 'V', 'current', $this->getLanguageCode());
        }

        return fn_url('call_requests.manage?id=' . $this->call_request['request_id'], 'A', 'current', $this->getLanguageCode());
    }

    protected function getProductUrl()
    {
        if (isset($this->call_request['product_id'])) {
            return fn_url('products.view?product_id=' . $this->call_request['product_id'], 'C');
        }

        return null;
    }

    protected function getProductName()
    {
        if (isset($this->call_request['product_id'])) {
            return fn_get_product_name($this->call_request['product_id'], $this->getLanguageCode());
        }

        return null;
    }

    protected function getLanguageCode()
    {
        $lang_code = fn_get_company_language($this->call_request['company_id']);
        if (empty($lang_code)) {
            $lang_code = CART_LANGUAGE;
        }

        return $lang_code;
    }
}