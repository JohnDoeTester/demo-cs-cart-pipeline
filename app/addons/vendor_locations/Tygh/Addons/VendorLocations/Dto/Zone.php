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
 * Class Zone
 * Describes geolocation of Zone type (place_id, lat, lng, radius fields)
 * This type is used in filters by distance to the vendor.
 *
 * @package Tygh\Addons\VendorLocations\Dto
 */
class Zone
{
    /**
     * @var string
     */
    protected $place_id;

    /**
     * @var float
     */
    protected $lat;

    /**
     * @var float
     */
    protected $lng;

    /**
     * @var int
     */
    protected $radius;

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
     * @return float
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @param float $lat
     */
    public function setLat($lat)
    {
        $this->lat = (float) $lat;
    }

    /**
     * @return float
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * @param float $lng
     */
    public function setLng($lng)
    {
        $this->lng = (float) $lng;
    }

    /**
     * @return int
     */
    public function getRadius()
    {
        return $this->radius;
    }

    /**
     * @param int $radius
     */
    public function setRadius($radius)
    {
        $this->radius = (int) $radius;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'place_id' => $this->place_id,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'radius' => $this->radius
        );
    }

    /**
     * @param string $hash
     *
     * @return \Tygh\Addons\VendorLocations\Dto\Zone
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
            $instance->setLat($params[1]);
        }

        if (!empty($params[2])) {
            $instance->setLng($params[2]);
        }

        if (!empty($params[3])) {
            $instance->setRadius($params[3]);
        }

        return $instance;
    }

    /**
     * @param array $location
     * @param bool  $sanitize
     *
     * @return \Tygh\Addons\VendorLocations\Dto\Zone
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

        if (isset($location['lat'])) {
            $self->setLat($location['lat']);
        }

        if (isset($location['lng'])) {
            $self->setLng($location['lng']);
        }

        if (isset($location['radius'])) {
            $self->setRadius($location['radius']);
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
