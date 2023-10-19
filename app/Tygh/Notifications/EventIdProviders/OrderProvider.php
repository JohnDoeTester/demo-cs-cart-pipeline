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
 * Class OrderProvider provides means to distinguish order-based notification event.
 *
 * @package Tygh\Notifications\EventIdProviders
 */
class OrderProvider implements IProvider
{
    /**
     * @var string
     */
    protected $prefix = 'order.';

    /**
     * @var string
     */
    protected $edp_suffix = '.edp';

    /**
     * @var string
     */
    protected $id;

    public function __construct(array $order, $edp_data = null)
    {
        $this->id = $this->prefix . $order['order_id'] . $order['status'];
        
        if ($edp_data) {
            $this->id .= $this->edp_suffix;
        }
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->id;
    }
}
