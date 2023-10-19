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
 * Interface PremoderationSettingsInterface describes a structure of the premoderation settings storage.
 *
 * @package Tygh\Addons\VendorDataPremoderation
 */
interface PremoderationSettingsInterface
{
    const SOURCE_FIELD_SEPARATOR = ':';

    const ALL_FIELDS_SELECTOR = '*';

    /**
     * Checks whether modified data source must be premoderated.
     *
     * @param string $source_name
     *
     * @return bool
     */
    public function getSourcePremoderation($source_name);

    /**
     * Checks whether modified source data field must be premoderated.
     *
     * @param string $source_name
     * @param string $field_name
     *
     * @return bool
     */
    public function getFieldPremoderation($source_name, $field_name);
}
