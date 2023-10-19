<?php

namespace Tygh\Addons\Warehouses;

class Destination
{
    /** @var int */
    protected $id;

    /** @var int */
    protected $position;

    /** @var string */
    protected $shipping_delay;

    /** @var bool */
    protected $warn_about_delay;

    public function __construct(array $data)
    {
        $this->id = (int) $data['destination_id'];
        $this->position = (int) $data['position'];
        $this->shipping_delay = $data['shipping_delay'];
        $this->warn_about_delay = (bool) $data['warn_about_delay'];
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function getShippingDelay()
    {
        return $this->shipping_delay;
    }

    /**
     * @return bool
     */
    public function isWarnAboutDelay()
    {
        return $this->warn_about_delay;
    }
}
