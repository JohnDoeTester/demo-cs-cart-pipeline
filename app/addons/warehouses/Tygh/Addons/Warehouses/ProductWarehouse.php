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

namespace Tygh\Addons\Warehouses;

use Tygh\Enum\ObjectStatuses;

class ProductWarehouse
{
    /** @var int Product amount */
    protected $amount;

    /** @var int Warehouse position */
    protected $position;

    /** @var int Product identifier */
    protected $product_id;

    /** @var string Warehouse type */
    protected $warehouse_type;

    /** @var int Warehouse identifier */
    protected $warehouse_id;

    /** @var int Original product amount */
    protected $original_amount;

    /** @var int[] Destination identifiers that warehouse is available for pickup */
    protected $pickup_destination_ids;

    /** @var int[] Destination identifiers that warehouse is available for shipping */
    protected $shipping_destination_ids;

    /** @var \Tygh\Addons\Warehouses\Destination[] Rate areas that warehouse ships or shows to */
    protected $destinations;

    /** @var int Rate area that warehouse is located in */
    protected $main_destination_id;

    /** @var string Warehouse status */
    protected $status;

    /** @var bool Warehouse amount marked to remove */
    protected $marked_to_remove = false;

    public function __construct(array $data)
    {
        $this->warehouse_type = $data['store_type'];
        $this->product_id = $data['product_id'];
        $this->position = (int) $data['position'];
        $this->warehouse_id = (int) $data['warehouse_id'];
        $this->amount = $this->original_amount = (int) $data['amount'];
        $this->main_destination_id = (int) $data['main_destination_id'];
        $this->pickup_destination_ids = $this->initializeDestinationIds($data['pickup_destination_ids']);
        $this->shipping_destination_ids = $this->initializeDestinationIds($data['shipping_destination_ids']);
        $this->destinations = $data['destinations'];
        $this->status = $data['status'];
        $this->marked_to_remove = !empty($data['marked_to_remove']) || !is_numeric($data['amount']);
    }

    /**
     * Determines if warehouse for provided destination identifier
     *
     * @param int $destination_id Destination identifier
     *
     * @return bool
     */
    public function isAvailForPickupInDestination($destination_id)
    {
        return in_array($destination_id, $this->pickup_destination_ids);
    }

    /**
     * Determines if warehouse for provided destination identifier
     *
     * @param int $destination_id Destination identifier
     *
     * @return bool
     */
    public function isAvailForShippingInDestination($destination_id)
    {
        return in_array($destination_id, $this->shipping_destination_ids);
    }

    /**
     * Fetches warehouse identifier
     *
     * @return int
     */
    public function getWarehouseId()
    {
        return $this->warehouse_id;
    }

    /**
     * Fetches warehouse shipping destination ids
     *
     * @return array<int>
     */
    public function getShippingDestinationIds()
    {
        return $this->shipping_destination_ids;
    }

    /**
     * Fetches product amount
     *
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Sets product amount
     *
     * @param int $amount Product amount
     *
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Converts warehouse data to array representation
     *
     * @return array
     */
    public function getStockAsArray()
    {
        return [
            'amount'           => $this->getAmount(),
            'product_id'       => $this->product_id,
            'warehouse_id'     => $this->getWarehouseId()
        ];
    }

    /**
     * Gets warehouse position.
     *
     * @param int|null $destination_id
     *
     * @return int
     */
    public function getPosition($destination_id = null)
    {
        $destination_id = (int) $destination_id;
        if (isset($this->destinations[$destination_id])) {
            return $this->destinations[$destination_id]->getPosition();
        }

        return $this->position;
    }

    /**
     * Gets warehouse shipping delay.
     *
     * @param int|null $destination_id
     *
     * @return string
     */
    public function getShippingDelay($destination_id = null)
    {
        $destination_id = (int) $destination_id;
        if (isset($this->destinations[$destination_id])) {
            return $this->destinations[$destination_id]->getShippingDelay();
        }

        return '';
    }

    /**
     * Gets whether a customer must be warned about shipping delay.
     *
     * @param int|null $destination_id
     *
     * @return bool
     */
    public function isWarnAboutDelay($destination_id = null)
    {
        $destination_id = (int) $destination_id;
        if (isset($this->destinations[$destination_id])) {
            return $this->destinations[$destination_id]->isWarnAboutDelay();
        }

        return false;
    }

    /**
     * Prepares destination IDs for a warehouse.
     *
     * @param string $destination_ids
     *
     * @return array
     */
    protected function initializeDestinationIds($destination_ids)
    {
        $destination_ids = array_filter(explode(',', $destination_ids));

        array_walk($destination_ids, 'intval');

        return $destination_ids;
    }

    /**
     * @return string
     */
    public function getWarehouseType()
    {
        return $this->warehouse_type;
    }

    /**
     * @return int
     */
    public function getMainDestinationId()
    {
        return $this->main_destination_id;
    }

    public function isActive()
    {
        return $this->status === ObjectStatuses::ACTIVE;
    }

    public function isMarkedToRemove()
    {
        return $this->marked_to_remove;
    }
}
