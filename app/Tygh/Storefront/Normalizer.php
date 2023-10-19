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

namespace Tygh\Storefront;

use Tygh\Tools\Url;

/**
 * Class Normalizer provides tools to normalize Storefront data for SQL queries and object creation.
 *
 * @package Tygh\Storefront
 */
class Normalizer
{
    const ENUMERATION_SEPARATOR = ',';

    /**
     * @var string[]
     */
    protected $enumerated_fields = ['country_codes', 'company_ids', 'currency_ids', 'language_ids'];

    /**
     * Normalizes storefront data.
     *
     * @param array $data Storefront data
     *
     * @return array
     */
    public function normalizeStorefrontData(array $data)
    {
        if (isset($data['url'])) {
            $data['url'] = Url::clean($data['url']);
        }

        if (isset($data['storefront_id'])) {
            $data['storefront_id'] = (int) $data['storefront_id'];
        }

        foreach ($this->enumerated_fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->getEnumeration($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Converts comma-separated list of IDs to an array.
     *
     * @param array|string $value List of IDs
     *
     * @return array
     */
    public function getEnumeration($value)
    {
        if (is_string($value)) {
            $value = explode(self::ENUMERATION_SEPARATOR, $value);
            $value = array_map('trim', $value);
        }

        $value = (array) $value;

        $value = array_filter($value);

        return array_values($value);
    }
}
