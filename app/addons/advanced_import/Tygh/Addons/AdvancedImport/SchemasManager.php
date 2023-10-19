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

namespace Tygh\Addons\AdvancedImport;

class SchemasManager
{
    protected $schemas = array();

    /**
     * Provides relations schema.
     *
     * @return array
     */
    public function getRelations()
    {
        return $this->get('relations');
    }

    /**
     * Provides modifiers schema.
     *
     * @return array
     */
    public function getModifiers()
    {
        return $this->get('modifiers');
    }

    /**
     * Provides products schema.
     *
     * @return array
     */
    public function getProducts()
    {
        return $this->get('products');
    }

    /**
     * Gets add-on schema.
     *
     * @param string $schema Schema name
     *
     * @return array
     */
    protected function get($schema)
    {
        if (!isset($this->schemas[$schema])) {
            $this->schemas[$schema] = fn_get_schema('advanced_import', $schema);
        }

        return $this->schemas[$schema];
    }
}