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

namespace Tygh\Addons\StripeConnect;

use Exception;

class StripeException extends Exception
{
    // phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint

    /** @var array<array-key, mixed> */
    private $context;

    /**
     * StripeException constructor.
     *
     * @param string                  $message Exception message
     * @param array<array-key, mixed> $context Additional context data
     */
    public function __construct($message, array $context = [])
    {
        parent::__construct($message);
        $this->context = $context;
    }

    /**
     * @return array<string, string>
     */
    public function getContext()
    {
        return $this->context;
    }
}
