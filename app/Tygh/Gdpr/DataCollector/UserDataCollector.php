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

namespace Tygh\Gdpr\DataCollector;

use Tygh\Gdpr\SchemaManager;

/**
 * Collects user data.
 *
 * @package Tygh\Gdpr\DataCollector
 */
class UserDataCollector implements IDataCollector
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
    public function collect(array $params)
    {
        $data = [];
        $schema = $this->getDataSchema();

        foreach ($schema as $data_item_name => $data_descriptor) {
            $args = isset($data_descriptor['params']) ? $data_descriptor['params'] : array();
            $args = array_merge($args, $params);
            $result = array();

            if (isset($data_descriptor['collect_data_callback']) && is_callable($data_descriptor['collect_data_callback'])) {
                $result = call_user_func_array($data_descriptor['collect_data_callback'], array($args));
            }

            $data[$data_item_name] = $result;
        }

        return $data;
    }


    /**
     * Fetches user data schema
     *
     * @return array<string, string|array>
     */
    protected function getDataSchema()
    {
        return $this->schema_manager->getSchema('user_data');
    }
}
