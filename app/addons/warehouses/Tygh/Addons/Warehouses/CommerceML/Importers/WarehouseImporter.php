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


namespace Tygh\Addons\Warehouses\CommerceML\Importers;


use Tygh\Addons\Warehouses\CommerceML\Storages\WarehouseStorage;
use Tygh\Addons\Warehouses\CommerceML\Dto\WarehouseDto;
use Tygh\Addons\CommerceML\Storages\ImportStorage;
use Tygh\Addons\Warehouses\Manager;
use Tygh\Common\OperationResult;

/**
 * Class WarehouseImporter
 *
 * @package Tygh\Addons\Warehouses\CommerceML\Importers
 */
class WarehouseImporter
{
    /**
     * @var \Tygh\Addons\Warehouses\CommerceML\Storages\WarehouseStorage
     */
    private $warehouse_storage;

    /**
     * WarehouseImporter constructor.
     *
     * @param \Tygh\Addons\Warehouses\CommerceML\Storages\WarehouseStorage $warehouse_storage Product storage instance
     */
    public function __construct(WarehouseStorage $warehouse_storage)
    {
        $this->warehouse_storage = $warehouse_storage;
    }

    /**
     * Imports warehouse
     *
     * @param \Tygh\Addons\Warehouses\CommerceML\Dto\WarehouseDto $warehouse      Warehouse DTO
     * @param \Tygh\Addons\CommerceML\Storages\ImportStorage      $import_storage Import storage instance
     *
     * @return \Tygh\Common\OperationResult
     */
    public function import(WarehouseDto $warehouse, ImportStorage $import_storage)
    {
        $warehouse_id = $import_storage->findEntityLocalId(WarehouseDto::REPRESENT_ENTITY_TYPE, $warehouse->id);
        $result = new OperationResult(true);

        if ($warehouse_id->hasValue()) {
            $result->setData($warehouse_id->asInt());
            $import_storage->removeEntity($warehouse);

            return $result;
        }

        $result = $this->importWarehouse($warehouse, $import_storage);

        if ($result->isFailure()) {
            return $result;
        }

        $result->addMessage('warehouse.created', __('warehouses.commerceml.import.message.warehouse.created', [
            '[id]'       => $warehouse->id->getId(),
            '[local_id]' => $warehouse->id->local_id,
        ]));

        $this->importWarehouseTranslations($warehouse, $import_storage);

        $import_storage->mapEntityId($warehouse);
        $import_storage->removeEntity($warehouse);

        $result->setData($warehouse->getEntityId()->local_id);
        $result->setSuccess(true);

        return $result;
    }

    /**
     * Imports warehouse data
     *
     * @param \Tygh\Addons\Warehouses\CommerceML\Dto\WarehouseDto $warehouse      Warehouse DTO
     * @param \Tygh\Addons\CommerceML\Storages\ImportStorage      $import_storage Import storage instance
     *
     * @return \Tygh\Common\OperationResult
     */
    private function importWarehouse(WarehouseDto $warehouse, ImportStorage $import_storage)
    {
        $warehouse_data = array_merge($warehouse->properties->getValueMap(), [
            'company_id'     => $import_storage->getImport()->company_id,
            'store_type'     => Manager::STORE_LOCATOR_TYPE_WAREHOUSE,
            'name'           => (string) $warehouse->name,
            'pickup_address' => (string) $warehouse->address,
            'city'           => (string) $warehouse->city
        ]);

        return $this->updateWarehouse($warehouse, $warehouse_data);
    }

    /**
     * Imports warehouse descriptions
     *
     * @param \Tygh\Addons\Warehouses\CommerceML\Dto\WarehouseDto $warehouse      Warehouse DTO
     * @param \Tygh\Addons\CommerceML\Storages\ImportStorage      $import_storage Import storage instance
     */
    private function importWarehouseTranslations(WarehouseDto $warehouse, ImportStorage $import_storage)
    {
        $lang_codes = (array) $import_storage->getSetting('lang_codes', []);

        foreach ($lang_codes as $lang_code) {
            $description_data = array_merge($warehouse->properties->getTranslatableValueMap($lang_code), [
                'name' => $warehouse->name && $warehouse->name->hasTraslate($lang_code) ? $warehouse->name->getTranslate($lang_code) : null,
            ]);
            $description_data = array_filter($description_data);

            if (!$description_data) {
                continue;
            }

            $this->updateWarehouse($warehouse, $description_data, $lang_code);
        }
    }

    /**
     * Executes update|create warehouse
     *
     * @param WarehouseDto                    $warehouse      Warehouse DTO
     * @param array<string, string|int|array> $warehouse_data Warehouse data
     * @param string|null                     $lang_code      Language code
     *
     * @return \Tygh\Common\OperationResult
     */
    private function updateWarehouse(WarehouseDto $warehouse, $warehouse_data, $lang_code = null)
    {
        $result = $this->warehouse_storage->updateWarehouse(
            $warehouse_data,
            (int) $warehouse->id->local_id,
            $lang_code,
            sprintf('Warehouse %s creating failed', $warehouse->id->getId())
        );

        if (!$warehouse->id->local_id && $result->isSuccess()) {
            $warehouse->id->local_id = (int) $result->getData();
        }

        return $result;
    }
}
