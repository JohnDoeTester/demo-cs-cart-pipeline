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
 * Class Location
 * Describes geolocation shared type with vendor_locations fields full set
 *
 * @package Tygh\Addons\VendorLocations\Dto
 */
class Location
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
     * @var string
     */
    protected $country;

    /**
     * @var string
     */
    protected $country_text;

    /**
     * @var string $country_place_id Country place id
     */
    protected $country_place_id;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string
     */
    protected $state_text;

    /**
     * @var string
     */
    protected $locality;

    /**
     * @var string
     */
    protected $locality_text;

    /**
     * @var string
     */
    protected $route;

    /**
     * @var string
     */
    protected $route_text;

    /**
     * @var string
     */
    protected $postal_code;

    /**
     * @var string
     */
    protected $postal_code_text;

    /**
     * @var string
     */
    protected $street_number;

    /**
     * @var string
     */
    protected $street_number_text;

    /**
     * @var string
     */
    protected $formatted_address;

    /**
     * @var string $locality_place_id Locality place id
     */
    protected $locality_place_id;
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
        $this->lat = $lat;
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
        $this->lng = $lng;
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
    public function getCountryText()
    {
        return $this->country_text;
    }

    /**
     * @param string $country_text
     */
    public function setCountryText($country_text)
    {
        $this->country_text = $country_text;
    }

    /**
     * @param string $country_place_id
     */
    public function setCountryPlaceId($country_place_id)
    {
        $this->country_place_id = $country_place_id;
    }

    /**
     * @return string
     */
    public function getCountryPlaceId()
    {
        return $this->country_place_id;
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
    public function getStateText()
    {
        return $this->state_text;
    }

    /**
     * @param string $state_text
     */
    public function setStateText($state_text)
    {
        $this->state_text = $state_text;
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
     * @return string
     */
    public function getLocalityText()
    {
        return $this->locality_text;
    }

    /**
     * @param string $locality_text
     */
    public function setLocalityText($locality_text)
    {
        $this->locality_text = $locality_text;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param string $route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * @param string $locality_place_id
     */
    public function setLocalityPlaceId($locality_place_id)
    {
        $this->locality_place_id = $locality_place_id;
    }

    /**
     * @return string
     */
    public function getLocalityPlaceId()
    {
        return $this->locality_place_id;
    }

    /**
     * @return string
     */
    public function getRouteText()
    {
        return $this->route_text;
    }

    /**
     * @param string $route_text
     */
    public function setRouteText($route_text)
    {
        $this->route_text = $route_text;
    }

    /**
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postal_code;
    }

    /**
     * @param string $postal_code
     */
    public function setPostalCode($postal_code)
    {
        $this->postal_code = $postal_code;
    }

    /**
     * @return string
     */
    public function getPostalCodeText()
    {
        return $this->postal_code_text;
    }

    /**
     * @param string $postal_code_text
     */
    public function setPostalCodeText($postal_code_text)
    {
        $this->postal_code_text = $postal_code_text;
    }

    /**
     * @return string
     */
    public function getStreetNumber()
    {
        return $this->street_number;
    }

    /**
     * @param string $street_number
     */
    public function setStreetNumber($street_number)
    {
        $this->street_number = $street_number;
    }

    /**
     * @return string
     */
    public function getStreetNumberText()
    {
        return $this->street_number_text;
    }

    /**
     * @param string $street_number_text
     */
    public function setStreetNumberText($street_number_text)
    {
        $this->street_number_text = $street_number_text;
    }

    /**
     * @return string
     */
    public function getFormattedAddress()
    {
        return $this->formatted_address;
    }

    /**
     * @param string $formatted_address
     */
    public function setFormattedAddress($formatted_address)
    {
        $this->formatted_address = $formatted_address;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'place_id'           => $this->place_id,
            'formatted_address'  => $this->formatted_address,
            'lat'                => $this->lat,
            'lng'                => $this->lng,
            'country'            => $this->country,
            'country_text'       => $this->country_text,
            'country_place_id'   => $this->country_place_id,
            'state'              => $this->state,
            'state_text'         => $this->state_text,
            'locality'           => $this->locality,
            'locality_text'      => $this->locality_text,
            'locality_place_id'  => $this->locality_place_id,
            'route'              => $this->route,
            'route_text'         => $this->route_text,
            'street_number'      => $this->street_number,
            'street_number_text' => $this->street_number_text,
            'postal_code'        => $this->postal_code,
            'postal_code_text'   => $this->postal_code_text,
        );
    }

    /**
     * @param array $location
     * @param bool  $sanitize
     *
     * @return \Tygh\Addons\VendorLocations\Dto\Location
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

        if (isset($location['country'])) {
            $self->setCountry($location['country']);
        }

        if (isset($location['country_text'])) {
            $self->setCountryText($location['country_text']);
        }

        if (isset($location['country_place_id'])) {
            $self->setCountryPlaceId($location['country_place_id']);
        }

        if (isset($location['state'])) {
            $self->setState($location['state']);
        }

        if (isset($location['state_text'])) {
            $self->setStateText($location['state_text']);
        }

        if (isset($location['locality'])) {
            $self->setLocality($location['locality']);
        }

        if (isset($location['locality_text'])) {
            $self->setLocalityText($location['locality_text']);
        }

        if (isset($location['locality_place_id'])) {
            $self->setLocalityPlaceId($location['locality_place_id']);
        }

        if (isset($location['route'])) {
            $self->setRoute($location['route']);
        }

        if (isset($location['route_text'])) {
            $self->setRouteText($location['route_text']);
        }

        if (isset($location['street_number'])) {
            $self->setStreetNumber($location['street_number']);
        }

        if (isset($location['street_number_text'])) {
            $self->setStreetNumberText($location['street_number_text']);
        }

        if (isset($location['postal_code'])) {
            $self->setPostalCode($location['postal_code']);
        }

        if (isset($location['postal_code_text'])) {
            $self->setPostalCodeText($location['postal_code_text']);
        }

        if (isset($location['formatted_address'])) {
            $self->setFormattedAddress($location['formatted_address']);
        }

        return $self;
    }

    /**
     * @param string $json
     *
     * @return \Tygh\Addons\VendorLocations\Dto\Location
     */
    public static function createFromJsonString($json)
    {
        return self::createFromArray((array) @json_decode($json, true));
    }

    /**
     * Converts location object to its string representation
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->place_id;
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
