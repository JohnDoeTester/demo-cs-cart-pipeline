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
 *  ProfileDataTypes contains possible values for `profile_fields_data`.`object_type` DB field.
 *
 * @package Tygh\Enum
 */
class ProfileDataTypes
{
    const USER = 'U';
    const PROFILE = 'P';
    const USER_PROFILE = 'UP';

    const ORDER = 'O';
    const SELLER = 'S';
}
