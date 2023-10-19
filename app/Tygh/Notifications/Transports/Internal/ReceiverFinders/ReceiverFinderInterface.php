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

namespace Tygh\Notifications\Transports\Internal\ReceiverFinders;

use Tygh\Notifications\Transports\Internal\InternalMessageSchema;

/**
 * Interface ReceiverFinderInterface describes class that is used to find receivers for internal notifications.
 *
 * @package Tygh\Notifications\Transports\Internal\ReceiverFinders
 */
interface ReceiverFinderInterface
{
    /**
     * @param int|string                                                    $criterion      Searching criterion
     * @param \Tygh\Notifications\Transports\Internal\InternalMessageSchema $message_schema Schema that describes message
     *
     * @return array<int, string>
     */
    public function find($criterion, InternalMessageSchema $message_schema);
}
