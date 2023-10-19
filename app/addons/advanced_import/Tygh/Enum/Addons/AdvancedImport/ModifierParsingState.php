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

namespace Tygh\Enum\Addons\AdvancedImport;

/**
 * The class declares available parsing process statuses.
 *
 * @package Tygh\Enum\Addons\AdvancedImport
 */
class ModifierParsingState
{
    const STARTING_PARSING_MODIFIER = 1;
    const STARTING_PARSING_PARAMETER = 3;

    const EXPECTING_OPENING_BRACKET = 5;
    const EXPECTING_PARAMETER_WRAPPER = 7;
    const EXPECTING_PARAMETER_DELIMITER = 9;

    const PARAMETER_PARSING_FINISHED = 11;
    const PARSING_FINISHED = 13;
}
