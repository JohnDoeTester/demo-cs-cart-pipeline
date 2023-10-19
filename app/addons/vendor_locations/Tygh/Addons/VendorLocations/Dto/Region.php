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

namespace Tygh\Addons\VendorLocations\Dto;

/**
 * Class Region
 * Describes geolocation of Region type (place_id, country, state, locality fields).
 * This type is used in filters by address components.
 *
 * @package Tygh\Addons\VendorLocations\Dto
 */
class Region
{
    /**
     * @var string
     */
    protected $place_id;

    /**
     * @var string
     */
    protected $country;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string
     */
    protected $locality;

    /**
     * @return string
     */
    public function getPlaceId()
    {
        return $this->place_id;
    }

    /**
     * @param string $place_id
     */
    public function setPlaceId($place_id)
    {
        $this->place_id = $place_id;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getLocality()
    {
        return $this->locality;
    }

    /**
     * @param string $locality
     */
    public function setLocality($locality)
    {
        $this->locality = $locality;
    }

    /**
     * @param string $hash
     *
     * @return \Tygh\Addons\VendorLocations\Dto\Region
     */
    public static function createFromHash($hash)
    {
        $decoded_hash = base64_decode($hash);
        $params = explode('|', $decoded_hash);

        $instance = new self();

        if (!empty($params[0])) {
            $instance->setPlaceId($params[0]);
        }

        if (!empty($params[1])) {
            $instance->setCountry($params[1]);
        }

        if (!empty($params[2])) {
            $instance->setState($params[2]);
        }

        if (!empty($params[3])) {
            $instance->setLocality($params[3]);
        }

        return $instance;
    }

    /**
     * @param array $location
     * @param bool  $sanitize
     *
     * @return \Tygh\Addons\VendorLocations\Dto\Region
     */
    public static function createFromArray(array $location, $sanitize = true)
    {
        $self = new self();

        if ($sanitize) {
            $location = self::sanitizeArray($location);
        }

        if (isset($location['place_id'])) {
            $self->setPlaceId($location['place_id']);
        }

        if (isset($location['country'])) {
            $self->setCountry($location['country']);
        }

        if (isset($location['state'])) {
            $self->setState($location['state']);
        }

        if (isset($location['locality'])) {
            $self->setLocality($location['locality']);
        }

        return $self;
    }

    /**
     * @param array $location
     *
     * @return array
     */
    protected static function sanitizeArray(array $location)
    {
        foreach ($location as $key => &$value) {
            if (is_array($value)) {
                $value = self::sanitizeArray($value);
            } else {
                $value = trim(strip_tags($value));
            }
        }
        unset($value);

        return $location;
    }
}
