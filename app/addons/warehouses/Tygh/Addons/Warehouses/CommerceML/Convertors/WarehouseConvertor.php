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


namespace Tygh\Addons\Warehouses\CommerceML\Convertors;

use Tygh\Addons\Warehouses\CommerceML\Dto\WarehouseDto;
use Tygh\Addons\CommerceML\Dto\IdDto;
use Tygh\Addons\CommerceML\Dto\TranslatableValueDto;
use Tygh\Addons\CommerceML\Storages\ImportStorage;
use Tygh\Addons\CommerceML\Xml\SimpleXmlElement;

/**
 * Class WarehouseConvertor
 *
 * @package Tygh\Addons\Warehouses\CommerceML\Convertors
 */
class WarehouseConvertor
{
    /**
     * Converts CommerceML element property to product feature DTO
     *
     * @param \Tygh\Addons\CommerceML\Xml\SimpleXmlElement   $element        Xml element
     * @param \Tygh\Addons\CommerceML\Storages\ImportStorage $import_storage Import storage instance
     */
    public function convert(SimpleXmlElement $element, ImportStorage $import_storage)
    {
        $entities = [];
        $product_warehouse = new WarehouseDto();

        $product_warehouse->id = IdDto::createByExternalId($element->getAsString('id'));
        $product_warehouse->name = TranslatableValueDto::create($element->getAsString('name', ''));
        $product_warehouse->address = TranslatableValueDto::create($element->getAsString('address/presentation', ''));

        /**
         * @psalm-suppress PossiblyNullIterator
         */
        foreach ($element->get('address/address_field', []) as $field) {
            if (trim($field->getAsString('type')) !== trim(SimpleXmlElement::findAlias('locality'))) {
                continue;
            }
            $product_warehouse->city = TranslatableValueDto::create($field->getAsString('value', ''));
        }

        array_unshift($entities, $product_warehouse);

        $import_storage->saveEntities($entities);
    }
}
