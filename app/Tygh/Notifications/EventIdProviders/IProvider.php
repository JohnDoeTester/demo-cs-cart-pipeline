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

namespace Tygh\Notifications\EventIdProviders;

/**
 * Interface IProvider describes an unique event ID provider.
 * Event ID providers are used to prevent duplicate event processing when dispatching the same event with the same data
 * multiple times in the runtime.
 *
 * @package Tygh\Notifications\EventIdProviders
 */
interface IProvider
{
    /**
     * @return string
     */
    public function getId();
}
