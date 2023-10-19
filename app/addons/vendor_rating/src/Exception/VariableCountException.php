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
 * Class VariableCountException represents an exception when too much variables are registered in the product.
 *
 * @package Tygh\Addons\VendorRating\Exception
 */
class VariableCountException extends Exception
{
    /** @var int */
    protected $allowed_variables_count;

    /** @var int */
    protected $passed_variables_count;

    /**
     * @return int
     */
    public function getAllowedVariablesCount()
    {
        return $this->allowed_variables_count;
    }

    /**
     * @param int $allowed_variables_count
     */
    public function setAllowedVariablesCount($allowed_variables_count)
    {
        $this->allowed_variables_count = $allowed_variables_count;
    }

    /**
     * @return int
     */
    public function getPassedVariablesCount()
    {
        return $this->passed_variables_count;
    }

    /**
     * @param int $passed_variables_count
     */
    public function setPassedVariablesCount($passed_variables_count)
    {
        $this->passed_variables_count = $passed_variables_count;
    }
}
