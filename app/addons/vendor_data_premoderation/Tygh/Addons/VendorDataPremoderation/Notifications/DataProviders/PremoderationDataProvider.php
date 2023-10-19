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

namespace Tygh\Addons\VendorDataPremoderation\Notifications\DataProviders;


use Tygh\Notifications\DataProviders\BaseDataProvider;
use Tygh\Tools\Url;

class PremoderationDataProvider extends BaseDataProvider
{
    protected $products = null;
    protected $lang_code = null;
    protected $admin_user_id = null;
    protected $product_ids = [];
    protected $company_id = 0;

    public function __construct(array $data)
    {
        $this->product_ids = isset($data['product_ids']) ? $data['product_ids'] : [];
        $this->company_id = isset($data['company_id']) ? $data['company_id'] : 0;

        $data['lang_code'] = $this->getLangCode();
        $data['products'] = $this->getProducts();
        $data['product_name'] = $this->getProductName();
        $data['products_count'] = count($this->getProducts());
        $data['admin_user_id'] = $this->getAdminUserId();
        $data['manage_urn'] = $this->getManageUrn();
        $data['manage_url'] = $this->getManageUrl();

        parent::__construct($data);
    }

    protected function getLangCode()
    {
        if ($this->lang_code !== null) {
            return $this->lang_code;
        }

        return $this->lang_code = fn_get_company_language($this->company_id);
    }

    protected function getProducts()
    {
        if ($this->products !== null) {
            return $this->products;
        }

        $products = fn_get_product_name($this->product_ids, $this->getLangCode(), true);

        foreach ($products as $product_id => &$product) {
            $product = [
                'product' => $product,
                'urn'     => Url::buildUrn(['products', 'update'], ['product_id' => $product_id]),
                'url'     => fn_url(Url::buildUrn(['products', 'update'], ['product_id' => $product_id]), 'V'),
            ];
        }
        unset($product);

        return $this->products = $products;
    }

    protected function getAdminUserId()
    {
        if ($this->admin_user_id !== null) {
            return $this->admin_user_id;
        }

        return $this->admin_user_id = fn_get_company_admin_user_id($this->company_id);
    }

    protected function getManageUrn()
    {
        return Url::buildUrn(['products', 'manage'], ['pid' => implode(',', $this->product_ids)]);
    }

    protected function getManageUrl()
    {
        return fn_url($this->getManageUrn(), 'V');
    }

    protected function getProductName()
    {
        $product_name = '';

        if (count($this->product_ids) === 1) {
            $products = $this->getProducts();
            $product = reset($products);

            $product_name = $product['product'];
        }

        return $product_name;
    }
}