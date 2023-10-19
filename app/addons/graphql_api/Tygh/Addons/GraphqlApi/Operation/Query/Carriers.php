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
use Tygh\Shippings\Shippings;

class Carriers implements OperationInterface
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
        $carriers = Shippings::getCarriers();

        foreach ($carriers as $id => &$carrier) {
            $carrier['carrier'] = $id;
        }
        unset($carrier);

        return $carriers;
    }

    /**
     * @return string|bool
     */
    public function getPrivilege()
    {
        return true;
    }

    /**
     * @return string|bool
     */
    public function getCustomerPrivilege()
    {
        return false;
    }
}
