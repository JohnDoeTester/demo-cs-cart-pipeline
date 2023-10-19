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

namespace Tygh\Location;

/**
 * Class Location provides customer location storage.
 *
 * @package Tygh\Location
 */
class Location
{
    /** @var string $address Default address */
    protected $address;

    /** @var string $zipcode Default zipcode */
    protected $zipcode;

    /** @var string $city Default city */
    protected $city;

    /** @var string $country Default country (code) */
    protected $country_code;

    /** @var string|null $country_name Default country (name) */
    protected $country_name = null;

    /** @var string $state_code Default state (code) */
    protected $state_code = null;

    /** @var string|null $state_name Default state (name) */
    protected $state_name = null;

    /** @var string $lang_code Two letter language code */
    protected $lang_code;

    /** @var array Country states cache */
    protected $states_cache = array();

    /**
     * Location constructor.
     *
     * @param string $country   ISO 3166-1 Country code
     * @param string $state     ISO 3166-2 State code
     * @param string $city      City name
     * @param string $address   Address
     * @param string $zipcode   Zipcode
     * @param string $lang_code Two-letter language code
     */
    public function __construct($country, $state, $city, $address, $zipcode, $lang_code)
    {
        $this->address = $address;
        $this->zipcode = $zipcode;
        $this->city = $city;
        $this->lang_code = $lang_code;

        $this->setCountry($country);
        $this->setState($state);
    }

    /**
     * Gets country.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country_code;
    }

    /**
     * Gets country name.
     *
     * @return string
     */
    public function getCountryName()
    {
        if ($this->country_name === null) {
            $this->country_name = fn_get_country_name($this->country_code, $this->lang_code);
        }

        return $this->country_name;
    }

    /**
     * Sets country.
     *
     * @param string $country ISO 3166-1 code
     */
    public function setCountry($country)
    {
        if ($this->country_code !== $country) {
            $this->country_code = $country;
            $this->country_name = null;
        }
    }

    /**
     * Gets state code.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state_code;
    }

    /**
     * Gets state name.
     *
     * @return string
     */
    public function getStateName()
    {
        if ($this->state_name === null && $this->state_code !== null) {
            $country_states = $this->getCountryStates();
            $this->state_name = isset($country_states[$this->state_code])
                ? $country_states[$this->state_code]
                : '';
        }

        return $this->state_name;
    }

    /**
     * Sets state.
     *
     * @param string $state ISO 3166-2 code
     */
    public function setState($state)
    {
        if ($this->isIsoCode($state)) {
            if ($this->state_code != $state) {
                $this->state_code = $state;
                $this->state_name = null;
            }
        } else {
            $this->state_code = $this->state_name = $state;
        }
    }

    /**
     * Gets city name.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Sets city name.
     *
     * @param string $city City name
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Gets address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Sets address.
     *
     * @param string $address Address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Gets zipcode.
     *
     * @return string
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * Sets zipcode.
     *
     * @param string $zipcode Zipcode
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;
    }

    /**
     * Provides location as array.
     *
     * @return string[]
     */
    public function toArray()
    {
        return [
            'country' => $this->getCountry(),
            'state'   => $this->getState(),
            'city'    => $this->getCity(),
            'zipcode' => $this->getZipcode(),
            'address' => $this->getAddress(),
        ];
    }

    /**
     * Checks if the state is a valid ISO 3166-2 state code.
     *
     * @param string      $state        State code or name
     * @param string|null $country_code Country code
     *
     * @return bool
     */
    protected function isIsoCode($state, $country_code = null)
    {
        if ($country_code === null) {
            $country_code = $this->country_code;
        }

        $country_states = $this->getCountryStates($country_code);

        return isset($country_states[$state]);
    }

    /**
     * Gets country states.
     *
     * @param string|null $country_code Country code
     *
     * @return string[]
     */
    protected function getCountryStates($country_code = null)
    {
        if ($country_code === null) {
            $country_code = $this->country_code;
        }

        if (!isset($this->states_cache[$country_code])) {
            $this->states_cache[$country_code] = fn_get_country_states($country_code, false, $this->lang_code);
        }

        return $this->states_cache[$country_code];
    }
}
