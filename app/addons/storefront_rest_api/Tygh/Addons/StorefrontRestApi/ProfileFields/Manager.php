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

namespace Tygh\Addons\StorefrontRestApi\ProfileFields;

use Tygh\Enum\ProfileFieldAreas;
use Tygh\Enum\ProfileFieldSections;
use Tygh\Enum\ProfileFieldTypes;
use Tygh\Enum\ProfileFieldLocations;
use Tygh\Enum\ProfileTypes;

/**
 * Class Manager provides list of profile fields that could and/or must be specified when creating and/or
 * updating user profiles and means to validate fields population.
 *
 * @package Tygh\Addons\StorefrontRestApi\ProfileFields
 */
class Manager
{
    const ACTION_ADD = 'add';
    const ACTION_UPDATE = 'update';

    /** @var string[] $sections */
    protected $sections;

    /** @var bool $quick_registration */
    protected $quick_registration;

    /** @var array $states_cache */
    protected $states_cache;

    /** @var string[] $countries_cache */
    protected $countries_cache;

    /** @var string $profile_type */
    protected $profile_type = 'U';

    /** @var callable $field_filter_function */
    protected $field_filter_function;

    /** @var string[] $sections_cache */
    protected $sections_cache;

    /** @var \Tygh\Addons\StorefrontRestApi\ProfileFields\Validator $validator */
    protected $validator;

    /** @var \Tygh\Addons\StorefrontRestApi\ProfileFields\Hydrator $hydrator */
    protected $hydrator;

    /**
     * Manager constructor.
     *
     * @param bool                                                   $quick_registration    Whether quick registration
     *                                                                                      is enabled
     * @param string                                                 $first_address_section First address section on
     *                                                                                      checkout
     * @param \Tygh\Addons\StorefrontRestApi\ProfileFields\Validator $validator             Fields validator
     * @param \Tygh\Addons\StorefrontRestApi\ProfileFields\Hydrator  $hydrator              Fields value hydrator
     * @param string                                                 $profile_type          Profile type
     */
    public function __construct(
        $quick_registration,
        $first_address_section,
        Validator $validator,
        Hydrator $hydrator,
        $profile_type = ProfileTypes::CODE_USER
    ) {
        $this->quick_registration = $quick_registration;

        $this->sections = [
            ProfileFieldSections::ESSENTIALS          => ProfileFieldSections::ESSENTIALS,
            ProfileFieldSections::CONTACT_INFORMATION => ProfileFieldSections::CONTACT_INFORMATION,
        ];

        if ($first_address_section === 'billing_first') {
            $this->sections[ProfileFieldSections::BILLING_ADDRESS] = ProfileFieldSections::BILLING_ADDRESS;
            $this->sections[ProfileFieldSections::SHIPPING_ADDRESS] = ProfileFieldSections::SHIPPING_ADDRESS;
        } else {
            $this->sections[ProfileFieldSections::SHIPPING_ADDRESS] = ProfileFieldSections::SHIPPING_ADDRESS;
            $this->sections[ProfileFieldSections::BILLING_ADDRESS] = ProfileFieldSections::BILLING_ADDRESS;
        }

        $this->validator = $validator;
        $this->hydrator = $hydrator;
        $this->profile_type = $profile_type;
    }

    /**
     * Gets list of fields for specified location and action.
     *
     * @param string $location  Location to get fields for
     * @param string $action    Intended action ('add' or 'update')
     * @param array  $auth      Current user authentication data
     * @param string $lang_code Two-letter language code
     *
     * @return array
     */
    public function get($location, $action, array $auth, $lang_code)
    {
        $fields = $this->fetchFields(
            $this->getLocationId($location),
            $auth,
            $lang_code
        );

        if ($this->quick_registration
            && $location === ProfileFieldAreas::PROFILE
            && $action === self::ACTION_ADD
        ) {
            unset(
                $this->sections[ProfileFieldSections::SHIPPING_ADDRESS],
                $this->sections[ProfileFieldSections::BILLING_ADDRESS]
            );
        }

        if ($location === ProfileFieldAreas::PROFILE) {
            $fields[ProfileFieldSections::ESSENTIALS][] = [
                'field_type'  => ProfileFieldTypes::PASSWORD,
                'field_name'  => 'password1',
                'description' => $this->tr('password', $lang_code),
                'required'    => $action === self::ACTION_ADD,
                'is_default'  => true,
            ];

            $fields[ProfileFieldSections::ESSENTIALS][] = [
                'field_type'  => ProfileFieldTypes::PASSWORD,
                'field_name'  => 'password2',
                'description' => $this->tr('confirm_password', $lang_code),
                'required'    => $action === self::ACTION_ADD,
                'is_default'  => true,
            ];
        }

        $schema = [];
        foreach ($this->sections as $id) {
            if (isset($fields[$id])) {
                $schema[$id] = $this->formatSection($id, $fields[$id], $lang_code);
            }
        }

        return $schema;
    }

    /**
     * Obtains fields from the database.
     *
     * @param string $location_id Fields location ID
     * @param array  $auth        Authentication data
     * @param string $lang_code   Two-letter language code
     *
     * @return array Fields groupped by section
     */
    protected function fetchFields($location_id, array $auth, $lang_code)
    {
        return fn_get_profile_fields($location_id, $auth, $lang_code, [
            'profile_type'    => $this->profile_type,
            'storefront_show' => true,
        ]);
    }

    /**
     * Gets location ID for profile fields request
     *
     * @param string $location Location to display fields for
     *
     * @see ProfileFieldAreas
     * @see fn_get_profile_fields
     *
     * @return string Location ID
     */
    protected function getLocationId($location)
    {
        switch ($location) {
            case ProfileFieldAreas::CHECKOUT:
                return ProfileFieldLocations::CHECKOUT_FIELDS;
            default:
                return ProfileFieldLocations::CUSTOMER_FIELDS;
        }
    }

    /**
     * Translates language variable.
     *
     * @param string $source    Language variable
     * @param string $lang_code Two-letter language code
     *
     * @return string
     */
    protected function tr($source, $lang_code)
    {
        return __($source, [], $lang_code);
    }

    /**
     * Reformats obtained from the database fields list.
     *
     * @param string $id        Section ID
     * @param array  $section   Section data
     * @param string $lang_code Two-letter language code
     *
     * @see \Tygh\Enum\ProfileFieldSections
     *
     * @return array
     */
    protected function formatSection($id, array $section, $lang_code)
    {
        if ($this->sections_cache === null) {
            $this->sections_cache = ProfileFieldSections::getAll($lang_code);
        }

        $formatted = [
            'description' => $this->sections_cache[$id],
            'fields'      => [],
        ];

        foreach ($section as $field_id => $field) {
            $field = $this->formatField($field, $lang_code);
            $formatted['fields'][] = $field;
        }

        return $formatted;
    }

    /**
     * Reformats obtained from the database field data.
     *
     * @param array  $field
     * @param string $lang_code Two-letter language code
     *
     * @return array
     */
    protected function formatField(array $field, $lang_code)
    {
        $field['required'] = $field['required'] === true || $field['required'] === 'Y';
        $field['is_default'] = $field['is_default'] === true || $field['is_default'] === 'Y';
        $field['field_id'] = $this->getFieldId($field);

        if ($this->fieldHasSelectableValues($field['field_type'])) {
            $field['values'] = $this->getFieldValues($field, $lang_code);
        }

        if ($this->field_filter_function !== null) {
            $field = call_user_func($this->field_filter_function, $field);
        }

        return $field;
    }

    /**
     * Checks whether field has a predefined selectable list of values.
     *
     * @param string $field_type Field type
     *
     * @return bool
     */
    protected function fieldHasSelectableValues($field_type)
    {
        return $field_type === ProfileFieldTypes::RADIO
            || $field_type === ProfileFieldTypes::SELECT_BOX
            || $field_type === ProfileFieldTypes::COUNTRY
            || $field_type === ProfileFieldTypes::ADDRESS_TYPE
            || $field_type === ProfileFieldTypes::STATE;
    }

    /**
     * Gets predefined field values.
     *
     * @param array  $field     Field
     * @param string $lang_code Two-letter langudate code
     *
     * @return array Field values
     */
    protected function getFieldValues(array $field, $lang_code)
    {
        switch ($field['field_type']) {
            case ProfileFieldTypes::COUNTRY:
                return $this->getCountries($lang_code);

            case ProfileFieldTypes::STATE:
                return $this->getStates($lang_code);

            case ProfileFieldTypes::ADDRESS_TYPE:
                return $this->getAddressTypes($lang_code);

            case ProfileFieldTypes::RADIO:
            case ProfileFieldTypes::SELECT_BOX:
                return $field['values'];

            default:
                return [];
        }
    }

    /**
     * Gets predefined list of countries for country-typed profile fields.
     *
     * @param string $lang_code Two-letter language code
     *
     * @return string[]
     */
    protected function getCountries($lang_code)
    {
        if ($this->countries_cache === null) {
            $this->countries_cache = fn_get_simple_countries(true, $lang_code);
        }

        return $this->countries_cache;
    }

    /**
     * Gets predefined list of states for state-typed profile fields.
     *
     * @param string $lang_code Two-letter language code
     *
     * @return array
     */
    protected function getStates($lang_code)
    {
        if ($this->states_cache === null) {
            $state_names_by_countries = [];
            $states = fn_get_all_states(true, $lang_code);
            foreach ($states as $country_id => $country_states) {
                foreach ($country_states as $state) {
                    $state_names_by_countries[$country_id][$state['code']] = $state['state'];
                }
            }

            $this->states_cache = $state_names_by_countries;
        }

        return $this->states_cache;
    }

    /**
     * Gets field ID from object.
     *
     * @param array $field
     *
     * @return string
     */
    protected function getFieldId(array $field)
    {
        if ($field['is_default']) {
            return $field['field_name'];
        }

        if (isset($field['field_id'])) {
            return $field['field_id'];
        }

        return $field['field_name'];
    }

    /**
     * Sets function that filters fields' fields.
     *
     * @param callable $func Filtering function
     */
    public function setFieldFilter($func)
    {
        $this->field_filter_function = $func;
    }

    /**
     * Removes function that filters fields' fields.
     */
    public function removeFieldFilter()
    {
        $this->field_filter_function = null;
    }

    /**
     * @see \Tygh\Addons\StorefrontRestApi\ProfileFields\Validator::validate
     *
     * @param array $schema
     * @param array $data
     *
     * @return \Tygh\Common\OperationResult
     */
    public function validate(array $schema, array $data)
    {
        return $this->validator->validate($schema, $data);
    }

    /**
     * @see \Tygh\Addons\StorefrontRestApi\ProfileFields\Hydrator::hydrate
     *
     * @param array $schema
     * @param array $data
     *
     * @return array
     */
    public function hydrate(array $schema, array $data)
    {
        return $this->hydrator->hydrate($schema, $data);
    }

    /**
     * Splits custom fields from the flat object and places them in the separate `fields` section.
     *
     * @param array $schema Profile fields schema
     * @param array $data   Object to split
     *
     * @return array
     */
    public function split(array $schema, array $data)
    {
        if (!isset($data['fields'])) {
            $data['fields'] = [];
        }

        foreach ($schema as $section_id => $section) {
            foreach ($section['fields'] as $field) {
                $field_id = $field['field_id'];

                // data is missing or already in the fields section
                if (!isset($data[$field_id])) {
                    continue;
                }

                $data[$field_id] = $this->unformatField($field, $data[$field_id]);

                if (!$field['is_default']) {
                    $data['fields'][$field_id] = $data[$field_id];
                    unset($data[$field_id]);
                }
            }
        }

        return $data;
    }

    /**
     * Converts formatted field value to the one that is stored in the database.
     *
     * @param array $field Field spec
     * @param mixed $value Value
     *
     * @return mixed
     */
    protected function unformatField(array $field, $value)
    {
        if ($field['field_type'] === ProfileFieldTypes::CHECKBOX) {
            if ($value === true || $value === 'true' || $value === 'Y') {
                $value = 'Y';
            } elseif ($value === false || $value === 'false' || $value === 'N') {
                $value = 'N';
            }
        }

        return $value;
    }

    /**
     * Gets predefined list of variants for address-typed profile fields.
     *
     * @param string $lang_code Two-letter language code
     *
     * @return string[]
     */
    protected function getAddressTypes($lang_code)
    {
        return [
            'residential' => $this->tr('address_residential', $lang_code),
            'commercial'  => $this->tr('address_commercial', $lang_code),
        ];
    }
}
