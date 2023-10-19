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

namespace Tygh\Addons\PaypalCommercePlatform\Exception;

use Tygh\Exceptions\AException;

class ApiException extends AException
{
    /**
     * @var array<array<string, string>>
     */
    protected $details;

    /**
     * Sets exception details.
     *
     * @param array<array<string, string>> $details Exception details
     */
    public function setDetails(array $details)
    {
        $this->details = $details;
    }

    /**
     * Gets exception details.
     *
     * @return array<array<string, string>>
     */
    public function getDetails()
    {
        return $this->details;
    }
}
