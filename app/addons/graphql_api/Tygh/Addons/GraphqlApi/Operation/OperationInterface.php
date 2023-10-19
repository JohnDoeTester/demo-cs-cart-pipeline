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

namespace Tygh\Addons\GraphqlApi\Operation;

use Tygh\Addons\GraphqlApi\Context;

interface OperationInterface
{
    public function __construct($source, array $args, Context $context);

    /**
     * @return mixed
     */
    public function run();

    /**
     * @return string|bool
     */
    public function getPrivilege();

    /**
     * @return string|bool
     */
    public function getCustomerPrivilege();
}
