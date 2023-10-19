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

use Tygh\Tygh;

/**
 * Class Manager provides means to operate the customer location object.
 *
 * @see     \Tygh\Location\Location
 *
 * @package Tygh\Location
 */
class Manager
{
    const SESSION_STORAGE_KEY = 'checkout_customer_location';

    const SESSION_STORAGE_KEY_DESTINATION_ID = 'checkout_customer_destination_id';

    const EMPTY_STATE_CODE = "\xc2\xa0"; // '\u00a0'

    /** @var string[] $counties_cache Countries cache */
    protected $countries;

    /** @var string $lang_code Current language */
    protected $lang_code;

    /** @var array[] $states States cache */
    protected $states;

    /** @var \Tygh\Location\Location $location Customer location */
    protected $location;

    /** @var bool $is_detected Whether the location has been detected */
    protected $is_detected = false;

    /** @var string[] $checkout_settings Checkout settings */
    protected $checkout_settings;

    /** @var int $company_id */
    protected $company_id;

    /** @var \Tygh\Database\Connection $db */
    protected $db;

    /** @var \Tygh\Location\IUserDataStorage session.cart.user_data */
    protected $user_data_storage;

    /** @var int session.auth.user_id */
    protected $user_id;

    /**
     * Manager constructor.
     *
     * @param string                          $checkout_settings Checkout settings
     * @param \Tygh\Database\Connection       $db                Database connection
     * @param int                             $company_id        Company ID
     * @param string                          $lang_code         Two-letter language code
     * @param int                             $user_id
     * @param \Tygh\Location\IUserDataStorage $user_data_storage
     */
    public function __construct(
        $checkout_settings,
        $db,
        $company_id,
        $lang_code,
        $user_id,
        IUserDataStorage $user_data_storage
    ) {
        $this->checkout_settings = $checkout_settings;
        $this->company_id = $company_id;
        $this->db = $db;
        $this->lang_code = $lang_code;
        $this->user_id = $user_id;
        $this->user_data_storage = $user_data_storage;
    }

    /**
     * Provides customer location.
     *
     * @return \Tygh\Location\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Sets customer location.
     *
     * @param \Tygh\Location\Location $location
     *
     * @return \Tygh\Location\Manager
     */
    public function setLocation(Location $location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Checks if location was detected automatically.
     *
     * @return bool
     */
    public function getIsDetected()
    {
        return $this->is_detected;
    }

    /**
     * Sets that location was detected automatically.
     *
     * @param bool $is_detected
     *
     * @return \Tygh\Location\Manager
     */
    public function setIsDetected($is_detected = true)
    {
        $this->is_detected = $is_detected;

        return $this;
    }

    /**
     * Sets multiple location fields at once.
     *
     * @param string[] $location Location fields
     *
     * @return \Tygh\Location\Manager
     */
    public function setLocationFromArray(array $location)
    {
        $country = $this->location->getCountry();
        if (isset($location['country'])) {
            $country = $location['country'];
        }

        $state = $this->location->getState();
        if (isset($location['state'])) {
            $state = $location['state'];
        }

        $city = $this->location->getCity();
        if (isset($location['city'])) {
            $city = $location['city'];
        }

        $zipcode = $this->location->getZipcode();
        if (isset($location['zipcode'])) {
            $zipcode = $location['zipcode'];
        } else {
            $zipcode_auto = $this->detectZipcode(
                $country,
                $state,
                $city
            );
            if ($zipcode_auto !== null) {
                $zipcode = $zipcode_auto;
            }
        }

        $address = $this->location->getAddress();
        if (isset($location['address'])) {
            $address = $location['address'];
        }

        if (isset($location['is_detected'])) {
            $this->setIsDetected($location['is_detected']);
        }

        return $this->setLocation(new Location($country, $state, $city, $address, $zipcode, $this->lang_code));
    }

    /**
     * Stores customer location in session, registry cache and customer profile.
     *
     * @return array Customer location stored in session
     */
    public function storeLocation()
    {
        $saved_location = $this->location->toArray();
        $saved_location['is_detected'] = $this->getIsDetected();

        fn_set_session_data(self::SESSION_STORAGE_KEY, $saved_location);

        $address_fields_prefixes = ['', SHIPPING_ADDRESS_PREFIX . '_'];

        $ship_to_another = fn_check_shipping_billing($this->user_data_storage->getAll(), fn_get_profile_fields('O'));

        if (!$ship_to_another) {
            $address_fields_prefixes[] = BILLING_ADDRESS_PREFIX . '_';
        }

        foreach ($this->getAddressFields() as $field) {
            foreach ($address_fields_prefixes as $prefix) {
                if ($prefix) {
                    $this->user_data_storage->set(
                        $prefix . $field,
                        $saved_location[$field]
                    );
                }
            }
        }

        $location = $this->user_data_storage->getAll();

        fn_set_session_data(self::SESSION_STORAGE_KEY_DESTINATION_ID, fn_get_available_destination($location));

        return $location;
    }

    /**
     * Gets customer location field from assorted arrays.
     *
     * This function searches for the profile field value amongst the fields of the primary section.
     * If non-empty field value was not found, fields from another section will be searched.
     * If the profile field was not found in the secondary section, fields without prefix will be searched.
     *
     * @param array  $array           Array to extract field from
     * @param string $field           String field name
     * @param mixed  $default_value   Default value to return when required field is not found or empty
     * @param string $primary_section Primary section to initially search field value in:
     *                                SHIPPING_ADDRESS_PREFIX or BILLING_ADDRESS_PREFIX
     *
     * @return string|mixed Found field value or default value
     */
    public function getLocationField($array, $field, $default_value = null, $primary_section = SHIPPING_ADDRESS_PREFIX)
    {
        $secondary_section = $primary_section === SHIPPING_ADDRESS_PREFIX
            ? BILLING_ADDRESS_PREFIX
            : SHIPPING_ADDRESS_PREFIX;

        $address_fields_prefixes = [
            "{$primary_section}_",
            "{$secondary_section}_",
            '',
        ];

        foreach ($address_fields_prefixes as $prefix) {
            if (isset($array[$prefix . $field]) && $array[$prefix . $field] !== '') {
                return $array[$prefix . $field];
            }
        }

        return $default_value;
    }

    /**
     * Determines zipcode using stored reference table.
     *
     * @param string $country_code ISO 3166-1 country code
     * @param string $state_code   ISO 3166-2 state code
     * @param string $city         City name
     *
     * @return string|null Zipcode or null when not detected
     */
    protected function detectZipcode($country_code, $state_code, $city)
    {
        $zipcode = null;

        /**
         * Executes when automatically detecting a customer's zipcode after the zipcode is detected,
         * allows you to modify the detected zipcode.
         *
         * @param string $country_code ISO 3166-1 country code
         * @param string $state_code   ISO 3166-2 state code
         * @param string $city         City name
         * @param string $zipcode      Detected zipcode
         */
        fn_set_hook('location_manager_detect_zipcode_post', $country_code, $state_code, $city, $zipcode);

        return $zipcode;
    }

    /**
     * Provides list of fields that can be detected automatically in any way.
     *
     * @return string[]
     */
    protected function getAddressFields()
    {
        return ['country', 'state', 'city', 'zipcode'];
    }

    /**
     * Extracts location data from user data.
     *
     * @param array $user_data User data
     *
     * @return array
     */
    public function getLocationFromUserData(array $user_data)
    {
        $location = [
            'country' => $this->getLocationField($user_data, 'country'),
            'state'   => $this->getLocationField($user_data, 'state'),
            'city'    => $this->getLocationField($user_data, 'city'),
            'zipcode' => $this->getLocationField($user_data, 'zipcode'),
            'address' => $this->getLocationField($user_data, 'address'),
        ];

        return $location;
    }

    /**
     * Sets location from user data array.
     * Prefills empty location fields with the default ones.
     *
     * @param array $user_data      User data
     *
     * @return array
     */
    public function setLocationFromUserData(array $user_data)
    {
        $default_location = Tygh::$app['location.default_location'];

        $location = $this->getLocationFromUserData($user_data);

        $is_default_country = $location['country'] === $default_location->getCountry();
        $is_default_state = $location['state'] === ($default_location->getState() ?: $default_location->getStateName());
        $is_default_city = $location['city'] === $default_location->getCity();
        $is_location_changed = false;

        if (!$location['country']) {
            $location['country'] = $default_location->getCountry();
            $is_default_country = $is_location_changed = true;
        }

        if (!$location['state'] && $is_default_country) {
            $location['state'] = $default_location->getState() ?: $default_location->getStateName();
            $is_default_state = $is_location_changed = true;
        }

        if (!$location['city'] && $is_default_state) {
            $location['city'] = $default_location->getCity();
            $is_default_city = $is_location_changed = true;
        }

        if (!$location['zipcode'] && $is_default_city) {
            $location['zipcode'] = $default_location->getZipcode();
            $is_location_changed = true;
        }

        $updated_user_data = $this
            ->setLocationFromArray($location)
            ->storeLocation();

        $user_data = array_merge($user_data, $updated_user_data);

        if ($is_location_changed) {
            unset(
                $user_data['s_country_descr'],
                $user_data['b_country_descr'],
                $user_data['s_state_descr'],
                $user_data['b_state_descr']
            );
            fn_add_user_data_descriptions($user_data, $this->lang_code);
        }

        return [$user_data, $is_location_changed];
    }

    /**
     * Sets location from user profile by its id.
     *
     * @param int $profile_id Profile identifier
     *
     * @return array
     */
    public function setLocationFromUserProfile($profile_id)
    {
        $user_profile = fn_get_user_info($this->user_id, true, $profile_id);
        $raw_location = $this->getLocationFromUserData($user_profile);

        $location = array_map(function ($item) {
            return $item === null ? '' : $item;
        }, $raw_location);

        return $this
            ->setLocationFromArray($location)
            ->storeLocation();
    }

    /**
     * Fetches destination identifier by current location
     *
     * @return int
     */
    public function getDestinationId()
    {
        $destination_id = (int) fn_get_session_data(self::SESSION_STORAGE_KEY_DESTINATION_ID);

        if (!$destination_id) {
            $location = $this->location->toArray();
            $destination_id = (int) fn_get_available_destination($location);
        }

        return $destination_id;
    }

    /**
     * Fills empty location fields in the specified section by copying them from the secondary profile fields section.
     *
     * @param array  $array           Array with location data
     * @param string $primary_section Section to fill fields in
     *
     * @return array
     */
    public function fillEmptyLocationFields(array $array, $primary_section)
    {
        foreach ($array as $field_name => &$value) {
            if ($value !== '' || strpos($field_name, "{$primary_section}_") !== 0) {
                continue;
            }

            $unprefixed_name = substr_replace($field_name, '', 0, strlen("{$primary_section}_"));
            $value = $this->getLocationField($array, $unprefixed_name, '', $primary_section);
        }
        unset($value);

        return $array;
    }
}
