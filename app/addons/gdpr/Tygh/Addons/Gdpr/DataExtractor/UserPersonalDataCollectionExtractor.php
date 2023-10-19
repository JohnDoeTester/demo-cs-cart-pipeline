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

namespace Tygh\Addons\Gdpr\DataExtractor;

use Tygh\Gdpr\SchemaManager;

/**
 * Extracts user data specified in schema from collection.
 *
 * @package Tygh\Addons\Gdpr\DataExtractor
 */
class UserPersonalDataCollectionExtractor implements IDataExtractor
{
    /** @var SchemaManager $schema_manager Schema manager */
    protected $schema_manager;

    public function __construct(SchemaManager $schema_manager)
    {
        $this->schema_manager = $schema_manager;
    }

    /**
     * @inheritdoc
     */
    public function extract(array $user_data)
    {
        $result = array();
        $user_data_schema = $this->schema_manager->getSchema('user_data');

        foreach ($user_data_schema as $data_item_name => $data_descriptor) {

            if (!isset($user_data_schema[$data_item_name])) {
                continue;
            }

            $result[$data_item_name] = $this->extractData(
                (array) isset($user_data[$data_item_name]) ? (array) $user_data[$data_item_name] : array(),
                isset($data_descriptor['params']) ? (array) $data_descriptor['params'] : array()
            );
        }

        return $result;
    }

    /**
     * Extracts data according to field_list in params
     *
     * @param array $data   Raw data
     * @param array $params Params
     *
     * @return array
     */
    protected function extractData(array $data, array $params)
    {
        $fields_list = isset($params['fields_list']) ? (array) $params['fields_list'] : array();
        $ignore_subarray = isset($params['ignore_subarray_list']) ? (array) $params['ignore_subarray_list'] : array();

        if (empty($fields_list)) {
            return array();
        }

        $result = array_fill_keys($fields_list, array());

        foreach ($data as $field => $value) {

            if (!is_array($value)) {

                if ($value !== '' && in_array($field, $fields_list, true)) {
                    $result[$field][] = $value;
                }
            } elseif (!in_array($field, $ignore_subarray, true)) {
                $result = array_merge_recursive(
                    $result,
                    $this->extractData($value, $params)
                );
            }
        }

        $result = array_map(function ($value) {
            if (is_array($value)) {
                $value = array_unique($value);
            }
            return $value;
        }, $result);

        return $result;
    }
}