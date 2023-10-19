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

namespace Tygh\Exceptions;

use Tygh\Tools\ErrorHandler;

abstract class AException extends \Exception
{
    /**
     * Outputs exception information
     *
     * @deprecated
     * @see \Tygh\Tools\ErrorHandler::showErrorMessage
     */
    public function output()
    {
        ErrorHandler::showErrorMessage($this);
    }

    /**
     * Returns debug information
     *
     * @param boolean $plain_text output as plain text
     *
     * @return string Formatted debug info
     * @deprecated
     * @see \Tygh\Tools\ErrorHandler::getDebugInfo
     */
    protected function printDebug($plain_text = false)
    {
        return ErrorHandler::getDebugInfo($this, $plain_text);
    }

    public function getErrorTitle()
    {
        return __CLASS__;
    }
}
