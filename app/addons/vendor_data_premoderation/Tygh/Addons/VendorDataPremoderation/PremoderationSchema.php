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

namespace Tygh\Addons\VendorDataPremoderation;

/**
 * Class PremoderationSchema represents a set of object premoderation rules.
 *
 * @package Tygh\Addons\VendorDataPremoderation
 */
class PremoderationSchema
{
    /**
     * @var array
     */
    protected $schema;

    public function __construct(array $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Gets data source premoderation rules.
     *
     * @param string $source_name
     *
     * @return bool|string[]
     */
    public function getSourcePremoderation($source_name)
    {
        if (!isset($this->schema[$source_name]['requires_premoderation'])) {
            return true;
        }

        return $this->schema[$source_name]['requires_premoderation'];
    }

    /**
     * Gets source data field premoderation rules.
     *
     * @param string $source_name
     * @param string $field_name
     *
     * @return bool|string[]
     */
    public function getFieldPremoderation($source_name, $field_name)
    {
        if ($this->getSourcePremoderation($source_name) === false) {
            return false;
        }

        if (!isset($this->schema[$source_name]['fields'][$field_name]['requires_premoderation'])) {
            return true;
        }

        return $this->schema[$source_name]['fields'][$field_name]['requires_premoderation'];
    }
}

