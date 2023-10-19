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

namespace Tygh\Addons\VendorRating\Criteria;

use Tygh\Application;
use Tygh\Enum\ObjectStatuses;

/**
 * Class ReviewsCriteria provides values of review-specific rating criteria.
 *
 * @package Tygh\Addons\VendorRating\Criteria
 */
class ReviewsCriteria extends AbstractCriteria
{
    /**
     * @var int
     */
    protected $products_iteration_step_size = 100;

    /**
     * @var \Tygh\Application
     */
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function getVendorRating()
    {
        list($companies_data,) = fn_get_companies(
            [
                'company_id'          => $this->company_id,
                'start_rating_period' => $this->start_rating_period,
            ],
            $this->application['session']['auth']
        );

        $company_data = reset($companies_data);

        return (float) $company_data['average_rating'];
    }

    public function getProductsRating()
    {
        $rating = 0;
        $page = 1;

        do {
            $products = $this->getProductsWithRating($page);
            if ($products) {
                $rating += $this->getAverageProductsRating($products);
                $page++;
            }
        } while ($products);

        return $page === 1 ? $rating : $rating / ($page - 1);
    }

    protected function getProductsWithRating($page)
    {
        $params = [
            'status'                   => ObjectStatuses::ACTIVE,
            'rating'                   => true,
            'company_id'               => $this->company_id,
            'load_products_extra_data' => false,
            'page'                     => $page,
            'start_rating_period'      => $this->start_rating_period,
        ];

        list($products,) = fn_get_products($params, $this->products_iteration_step_size);
        if (!$products) {
            return [];
        }

        $products = array_filter(
            $products,
            function ($product) {
                return isset($product['average_rating'])
                    && (float) $product['average_rating'];
            }
        );

        return $products;
    }

    protected function getAverageProductsRating(array $products)
    {
        $rating_values = array_column($products, 'average_rating');

        return array_sum($rating_values) / count($products);
    }
}
