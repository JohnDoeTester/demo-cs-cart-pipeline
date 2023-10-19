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

namespace Tygh\Gdpr\DataModifier;

use Tygh\Gdpr\SchemaManager;
use Faker\Generator;

/**
 * Modifies user data specified in schema using Faker library.
 *
 * @package Tygh\Gdpr\DataModifier
 */
class UserPersonalDataAnonymizer implements IDataModifier
{
    /** @var SchemaManager $schema_manager Schema manager */
    protected $schema_manager;

    /** @var Generator $faker Fake data generator */
    protected $faker;

    /** @var array $fakes Fake data array */
    protected $fakes = [];

    /** @var array $fields_list Fields names list of values to be modified */
    protected $fields_list;

    public function __construct(SchemaManager $schema_manager, Generator $faker)
    {
        $this->schema_manager = $schema_manager;
        $this->faker = $faker;
    }

    /**
     * @inheritdoc
     */
    public function modify(array $user_data)
    {
        $user_data_schema = $this->schema_manager->getSchema('user_data');
        $result = [];

        foreach ($user_data_schema as $data_item_name => $data_descriptor) {
            $result[$data_item_name] = $this->applyModifier(
                (array) isset($user_data[$data_item_name]) ? (array) $user_data[$data_item_name] : array(),
                isset($data_descriptor['params']) ? (array) $data_descriptor['params'] : array()
            );
        }

        return $result;
    }

    /**
     * Applies modifier to element inside user_data array that specified in parameters
     *
     * @param array $user_data Raw user data
     * @param array $params    Parameters
     *
     * @return mixed
     */
    protected function applyModifier($user_data, $params)
    {
        $fields_list = isset($params['fields_list']) ? (array) $params['fields_list'] : array();
        $ignore_subarray = isset($params['ignore_subarray_list']) ? (array) $params['ignore_subarray_list'] : array();

        if (empty($fields_list)) {
            return $user_data;
        }

        foreach ($user_data as $field => &$value) {
            if (is_array($value)) {
                if (!in_array($field, $ignore_subarray, true)) {
                    $value = $this->applyModifier($value, $params);
                }
            } elseif (in_array($field, $fields_list, true)) {
                $value = $this->modifyValue($field, $value);
            }
        }

        unset($value);

        return $user_data;
    }

    /**
     * Modifies provided value according to provided pattern
     *
     * @param string $pattern Pattern to modify value by
     * @param mixed  $value   Value to modify
     *
     * @return mixed|string
     */
    protected function modifyValue($pattern, $value)
    {
        $schema = $this->getAnonymizerSchema();

        if (isset($schema[$pattern])) {

            if ($schema[$pattern] === '') {
                $value = '';
            } elseif (isset($this->fakes[$schema[$pattern]])) {
                $value = $this->fakes[$schema[$pattern]];
            } else {
                $this->fakes[$schema[$pattern]] = $value = str_replace(array("\n","\r"), '', $this->faker->{$schema[$pattern]});
            }
        }

        return $value;
    }

    /**
     * Fetches anonymizer schema
     *
     * @return array
     */
    protected function getAnonymizerSchema()
    {
        return $this->schema_manager->getSchema('anonymizer');
    }
}
