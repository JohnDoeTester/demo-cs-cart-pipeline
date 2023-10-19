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

namespace Tygh\Api\Entities\v20;

use Tygh\Api\Entities\Users as BaseUsers;

/**
 * Represents api v2.0 of the users resource
 *
 * @package Tygh\Api\Entities\v20
 */
class Users extends BaseUsers
{
    /**
     * @inheritdoc
     */
    protected function filterUserData($user_data)
    {
        unset($user_data['user_id']);

        return $user_data;
    }
}
