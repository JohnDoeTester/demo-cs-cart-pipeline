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

namespace Tygh\Addons\GraphqlApi\Validator;

use Tygh\Database\Connection;

class OwnershipValidator
{
    /**
     * @var \Tygh\Database\Connection
     */
    protected $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function validateProduct(int $product_id, int $company_id): bool
    {
        if ($company_id === 0) {
            return true;
        }

        $product_company_id = (int) $this->db->getField(
            'SELECT company_id FROM ?:products WHERE product_id = ?i',
            $product_id
        );

        return $product_company_id === $company_id;
    }

    public function validateOrder(int $order_id, int $company_id): bool
    {
        if ($company_id === 0) {
            return true;
        }

        $order_company_id = (int) $this->db->getField(
            'SELECT company_id FROM ?:orders WHERE order_id = ?i',
            $order_id
        );

        return $order_company_id === $company_id;
    }
}
