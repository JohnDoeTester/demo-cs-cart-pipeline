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

namespace Tygh\Enum;

/**
 *  ProfileTypes contains possible values for `profile_fields`.`profile_type` DB field.
 *
 * @package Tygh\Enum
 */
class ProfileTypes
{
    /** @var string Represents all current users (admins, customer, vendors) */
    const USER = 'user';
    const CODE_USER = 'U';

    /** @var string Represents seller (company in MVE edition) */
    const SELLER = 'seller';
    const CODE_SELLER = 'S';
}
