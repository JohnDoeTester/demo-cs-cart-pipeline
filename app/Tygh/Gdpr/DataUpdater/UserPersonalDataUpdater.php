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

namespace Tygh\Gdpr\DataUpdater;

use Tygh\Gdpr\SchemaManager;

/**
 * Updates user data according to schema.
 *
 * @package Tygh\Gdpr\DataModifier
 */
class UserPersonalDataUpdater implements IDataUpdater
{
    protected $schema_manager;

    public function __construct(SchemaManager $schema_manager)
    {
        $this->schema_manager = $schema_manager;
    }

    /**
     * @inheritdoc
     */
    public function update(array $user_data)
    {
        $user_data_schema = $this->schema_manager->getSchema('user_data');

        foreach ($user_data_schema as $data_item_name => $data_descriptor) {
            if (isset($data_descriptor['update_data_callback']) && isset($user_data[$data_item_name])) {
                call_user_func_array($data_descriptor['update_data_callback'], array($user_data[$data_item_name]));
            }
        }

        return true;
    }
}
