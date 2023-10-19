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

namespace Tygh\Addons\AdvancedImport\Modifiers\Parsers;

use Tygh\Addons\AdvancedImport\Exceptions\InvalidModifierFormatException;
use Tygh\Addons\AdvancedImport\Exceptions\InvalidModifierParameterException;

/**
 * The interface of the parser class responsible for parsing modifiers.
 *
 * @package Tygh\Addons\AdvancedImport\Modifiers\Parsers
 */
interface IModifierParser
{
    /**
     * Parses modifier string that contains the operation
     *
     * @param string $modifier The modifier operator
     *
     * @return mixed
     * @throws InvalidModifierFormatException
     * @throws InvalidModifierParameterException
     */
    public function parse($modifier);
}
