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

use Tygh\Common\OperationResult;
use Tygh\Enum\ProfileFieldTypes;

/**
 * Class Validator validate data against profile fields schema.
 *
 * @package Tygh\Addons\StorefrontRestApi\ProfileFields
 */
class Validator
{
    /**
     * Validates schema fields population.
     *
     * @param array $schema
     * @param array $data
     *
     * @return \Tygh\Common\OperationResult
     */
    public function validate(array $schema, array $data)
    {
        $result = new OperationResult(true);

        $error_fields = [
            'required' => [],
            'invalid'  => [],
        ];

        if (!isset($data['fields'])) {
            $data['fields'] = [];
        }

        foreach ($schema as $section_id => $section) {
            foreach ($section['fields'] as $field) {
                $field_id = $field['field_id'];
                if ($field['is_default']) {
                    $value_container = $data;
                } else {
                    $value_container = $data['fields'];
                }

                if ($field['required'] && (!isset($value_container[$field_id]))) {
                    $result->setSuccess(false);
                    $error_fields['required'][$field_id] = [
                        'is_default' => $field['is_default'],
                        'field_id'   => $field_id,
                    ];
                } elseif ($field['required'] && empty(trim($value_container[$field_id]))) {
                    $result->setSuccess(false);
                    $error_fields['empty'][] = [
                        'is_default' => $field['is_default'],
                        'field_id'   => $field_id,
                    ];
                }


                if (!isset($value_container[$field_id])) {
                    continue;
                }

                if ($field['field_type'] === ProfileFieldTypes::CHECKBOX && !$this->isBool($value_container[$field_id])) {
                    $result->setSuccess(false);
                    $error_fields['invalid'][] = [
                        'is_default' => $field['is_default'],
                        'field_id'   => $field_id,
                        'value'      => $value_container[$field_id],
                        'values'     => ['true' => 'Y', 'false' => 'N'],
                    ];
                } elseif ($field['field_type'] === ProfileFieldTypes::STATE) {
                    $country_code_field_id = $this->getCountryCodeFieldId($section['fields'], $field['is_default'], $field_id);
                    if ($country_code_field_id !== null && !isset($value_container[$country_code_field_id])) {
                        $result->setSuccess(false);
                        $error_fields['required'][$country_code_field_id] = [
                            'is_default' => $field['is_default'],
                            'field_id'   => $country_code_field_id,
                        ];
                        continue;
                    }
                    list($is_valid, $values) = $this->validateState($field, $value_container[$field_id], $value_container[$country_code_field_id]);
                    if (!$is_valid) {
                        $result->setSuccess(false);
                        $error_fields['invalid'][] = [
                            'is_default' => $field['is_default'],
                            'field_id'   => $field_id,
                            'value'      => $value_container[$field_id],
                            'values'     => $values,
                        ];
                    }
                } elseif (isset($field['values']) && !isset($field['values'][$value_container[$field_id]])) {
                    $result->setSuccess(false);
                    $error_fields['invalid'][] = [
                        'is_default' => $field['is_default'],
                        'field_id'   => $field_id,
                        'value'      => $value_container[$field_id],
                        'values'     => $field['values'],
                    ];
                } elseif (
                    $field['field_type'] === ProfileFieldTypes::EMAIL
                    || ($field['field_type'] === ProfileFieldTypes::INPUT && $field['field_name'] === 'email')
                ) {
                    if (!fn_validate_email($value_container[$field_id])) {
                        $result->setSuccess(false);
                        $values = ['customer@example.com' => 'email'];
                        $error_fields['invalid'][] = [
                            'is_default' => $field['is_default'],
                            'field_id' => $field_id,
                            'value' => $value_container[$field_id],
                            'values' => $values,
                        ];
                    }
                }
            }
        }

        $result->setData($error_fields);

        return $result;
    }

    /**
     * Validates state value.
     *
     * @param array  $field
     * @param string $state_code
     * @param string $country_code
     *
     * @return array Validation result and the list of available states
     */
    protected function validateState(array $field, $state_code, $country_code)
    {
        if (!$field['values']) {
            return [true, []];
        }

        if (!isset($field['values'][$country_code])) {
            return [true, []];
        }

        $is_valid = isset($field['values'][$country_code][$state_code]);
        $valid_values = array_keys($field['values'][$country_code]);

        return [$is_valid, $valid_values];
    }

    protected function isBool($value)
    {
        return in_array(
            $value,
            ['N', 'Y', false, true],
            true
        );
    }

    /**
     * @param array  $schema_section_fields
     * @param bool   $is_default
     * @param string $state_code_field_id
     *
     * @return int|string|null
     */
    protected function getCountryCodeFieldId(array $schema_section_fields, $is_default, $state_code_field_id)
    {
        if ($is_default) {
            list($prefix, ) = explode('_', $state_code_field_id);
            return $prefix . '_country';
        } else {
            foreach ($schema_section_fields as $field_id => $field) {
                if ($field['field_type'] === ProfileFieldTypes::COUNTRY) {
                    return $field_id;
                }
            }
        }

        return null;
    }
}
