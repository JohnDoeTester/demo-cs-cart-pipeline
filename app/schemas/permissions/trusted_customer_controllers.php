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

/**
 * The schema describes the controllers that will be available
 * when the strorefront setting "Access for authorized users only" is enabled.
 *
 * @see \Tygh\Storefront\Storefront::$is_accessible_for_authorized_customers_only
 */

return [
    'auth'     => [
        'login_form'       => true,
        'recover_password' => true,
    ],
    'profiles' => [
        'add'         => true,
        'success_add' => true,
    ]
];