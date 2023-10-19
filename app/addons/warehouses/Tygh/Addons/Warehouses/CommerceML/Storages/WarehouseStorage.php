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


namespace Tygh\Addons\Warehouses\CommerceML\Storages;


use Tygh\Common\OperationResult;

/**
 * Class WarehouseStorage
 *
 * @package Tygh\Addons\Warehouses\CommerceML\Storages
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
 */
class WarehouseStorage
{
    /**
     * @var string
     */
    private $default_language_code;

    /**
     * WarehouseStorage constructor.
     *
     * @param string $default_language_code Default language code
     */
    public function __construct($default_language_code)
    {
        $this->default_language_code = $default_language_code;
    }

    /**
     * Creates/updates warehouse
     *
     * @param array<string, int|string|float|null|bool|array> $warehouse_data Warehouse data
     * @param int                                             $warehouse_id   Warehouse ID
     * @param string|null                                     $lang_code      Language code
     * @param string|null                                     $error_message  Error message
     *
     * @return \Tygh\Common\OperationResult
     */
    public function updateWarehouse($warehouse_data, $warehouse_id, $lang_code = null, $error_message = null)
    {
        $lang_code = $this->getLangCode($lang_code);

        return OperationResult::wrap(static function () use ($warehouse_data, $warehouse_id, $lang_code) {
            return fn_update_store_location($warehouse_data, $warehouse_id, $lang_code);
        }, $error_message);
    }

    /**
     * Gets language code
     *
     * @param string|null $lang_code Languge code
     *
     * @return string
     */
    private function getLangCode($lang_code = null)
    {
        return $lang_code === null ? $this->default_language_code : $lang_code;
    }
}
