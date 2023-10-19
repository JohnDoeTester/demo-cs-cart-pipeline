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
 *  ProfileFieldTypes contains possible values for `profile_fields`.`field_type` DB field.
 *
 * @package Tygh\Enum
 */
class ProfileFieldTypes
{
    const ADDRESS_TYPE = 'N';
    const CHECKBOX = 'C';
    const COUNTRY = 'O';
    const DATE = 'D';
    const EMAIL = 'E';
    const HEADER = 'H';
    const INPUT = 'I';
    const PASSWORD = 'W';
    const PHONE = 'P';
    const POSTAL_CODE = 'Z';
    const RADIO = 'R';
    const SELECT_BOX = 'S';
    const STATE = 'A';
    const TEXT_AREA = 'T';
    const USER_GROUP = 'U';
    const VENDOR_TERMS = 'B';
    const FILE = 'F';
}
