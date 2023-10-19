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

class ProductFeatures implements OperationInterface
{
    /**
     * @var mixed $source
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
        $params = [
            'page'          => $this->args['page'],
            'exclude_group' => $this->args['exclude_group'],
            'feature_types' => $this->args['feature_types'],
            'parent_id'     => $this->args['parent_id'],
        ];

        if (!empty($this->args['product_id'])) {
            $auth = $this->context->getAuth();
            $product_data = fn_get_product_data($this->args['product_id'], $auth);
            $category_ids = !empty($product_data['category_ids']) ? fn_get_category_ids_with_parent($product_data['category_ids']) : null;
            if ($category_ids !== null) {
                $params['category_ids'] = $category_ids;
            }
        }

        list($features) = fn_get_product_features($params, $this->args['items_per_page'], $this->context->getLanguageCode());

        return $features;
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
