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

namespace Tygh\Addons\PaypalCommercePlatform\Webhook;

use stdClass;

abstract class Event
{
    /** @var string $id */
    protected $id;

    /** @var string $summary */
    protected $summary;

    /** @var \stdClass $resource */
    protected $resource;

    /**
     * Event constructor.
     *
     * @param \stdClass $payload Payload data
     */
    public function __construct(stdClass $payload)
    {
        foreach ($payload as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Provides webhook event ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Provides webhook event summary.
     *
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Provides webhook event associated resource.
     *
     * @return \stdClass
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Whether an event has been processed.
     *
     * @return bool
     */
    public function isProcessed()
    {
        return false;
    }
}