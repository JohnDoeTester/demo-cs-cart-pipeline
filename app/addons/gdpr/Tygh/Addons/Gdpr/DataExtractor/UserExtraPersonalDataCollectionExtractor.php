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
 * Extracts extra user data specified in schema from collection.
 *
 * @package Tygh\Addons\Gdpr\DataExtractor
 */
class UserExtraPersonalDataCollectionExtractor extends UserPersonalDataCollectionExtractor implements IDataExtractor
{
    public function __construct(SchemaManager $schema_manager)
    {
        parent::__construct($schema_manager);
    }

    /**
     * @inheritdoc
     */
    public function extract(array $user_data)
    {
        return parent::extract($user_data);
    }

    /**
     * Extracts extra data
     *
     * @param array $data   Raw data
     * @param array $params Params
     *
     * @return array
     */
    protected function extractData(array $data, array $params)
    {
        $result = parent::extractData($data, $params);

        if (!empty($data['force_display'])) {
            $result = array_merge($result, $data['force_display']);
        }

        return $result;
    }
}