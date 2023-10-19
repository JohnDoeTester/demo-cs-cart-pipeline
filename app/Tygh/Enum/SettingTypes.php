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
 * Class SettingTypes contains all possible setting object types.
 *
 * @package Tygh\Enum
 */
class SettingTypes
{
    const PASSWORD = 'P';
    const CHECKBOX = 'C';
    const RADIOGROUP = 'R';
    const SELECTBOX = 'S';
    const SELECTBOX_WITH_SOURCE = 'K';
    const MULTIPLE_SELECT = 'M';
    const MULTIPLE_CHECKBOXES = 'N';
    const MULTIPLE_CHECKBOXES_FOR_SELECTBOX = 'G';
    const SELECTABLE_BOX = 'B';
    const COUNTRY = 'X';
    const STATE = 'W';
    const TEMPLATE = 'E';
    const PERMANENT_TEMPLATE = 'Z';
    const HEADER = 'H';
    const INFO = 'O';
    const FILE = 'F';
    const TEXTAREA = 'T';
    const INPUT = 'I';
    const NUMBER = 'U';
    const HIDDEN = 'D';
    const PHONE = 'L';
    const UNEDITABLE = 'A';
}
