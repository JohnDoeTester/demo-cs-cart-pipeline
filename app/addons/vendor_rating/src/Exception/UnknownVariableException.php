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

namespace Tygh\Addons\VendorRating\Exception;

use Exception;

/**
 * Class UnknownVariableException represents an exception that is thrown when an unknown variable is used in formula.
 *
 * @package Tygh\Addons\VendorRating\Exception
 */
class UnknownVariableException extends Exception
{
    /** @var string */
    protected $variable;

    /**
     * @return string
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * @param string $variable
     */
    public function setVariable($variable)
    {
        $this->variable = $variable;
    }
}
