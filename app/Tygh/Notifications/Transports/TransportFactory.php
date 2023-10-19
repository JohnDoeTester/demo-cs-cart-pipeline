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

namespace Tygh\Notifications\Transports;

use Pimple\Container;

/**
 * Class TransportFactory implements a transport factory that loads transports from the application container.
 *
 * @package Tygh\Notifications\Transports
 */
class TransportFactory implements ITransportFactory
{
    /**
     * @var \Pimple\Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $transport_id
     *
     * @return \Tygh\Notifications\Transports\ITransport
     */
    public function create($transport_id)
    {
        return $this->container['event.transports.' . $transport_id];
    }
}
