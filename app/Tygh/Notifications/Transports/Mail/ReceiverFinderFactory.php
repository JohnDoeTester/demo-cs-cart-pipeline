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

namespace Tygh\Notifications\Transports\Mail;

use Pimple\Container;
use Tygh\Exceptions\DeveloperException;

class ReceiverFinderFactory
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
     * @param string $method
     *
     * @return \Tygh\Notifications\Transports\Mail\ReceiverFinders\ReceiverFinderInterface
     *
     * @throws \Tygh\Exceptions\DeveloperException
     */
    public function get($method)
    {
        $finder_id = 'event.transports.mail.receiver_finders.' . $method;
        if (!$this->container->has($finder_id)) {
            throw new DeveloperException('Unknown receiver finder method ' . $method);
        }

        return $this->container[$finder_id];
    }
}
