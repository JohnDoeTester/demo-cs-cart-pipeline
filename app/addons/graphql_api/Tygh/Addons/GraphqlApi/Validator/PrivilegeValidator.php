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

use Tygh\Addons\GraphqlApi\Operation\OperationInterface;
use Tygh\Enum\UserTypes;

class PrivilegeValidator
{
    public function validate(int $user_id, string $user_type, OperationInterface $handler): bool
    {
        if ($user_type === UserTypes::CUSTOMER) {
            $privilege = $handler->getCustomerPrivilege();
        } else {
            $privilege = $handler->getPrivilege();
        }

        if (is_bool($privilege)) {
            return $privilege;
        }

        $has_privilege = fn_check_user_access($user_id, $privilege);

        return $has_privilege;
    }
}
