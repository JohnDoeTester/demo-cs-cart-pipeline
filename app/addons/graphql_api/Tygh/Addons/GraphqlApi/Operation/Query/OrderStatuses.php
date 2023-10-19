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

class OrderStatuses implements OperationInterface
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
        $statues = fn_get_statuses(
            STATUSES_ORDER,
            [],
            false,
            false,
            $this->context->getLanguageCode(),
            $this->context->getCompanyId()
        );

        return array_map(
            static function ($status) {
                $status['color'] = $status['params']['color'];
                return $status;
            },
            $statues
        );
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
