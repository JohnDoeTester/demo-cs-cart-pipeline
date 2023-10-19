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


namespace Tygh\Addons\Warehouses\CommerceML\Dto;


use Tygh\Addons\CommerceML\Dto\RepresentEntityDto;
use Tygh\Addons\CommerceML\Dto\RepresentEntitDtoTrait;
use Tygh\Addons\CommerceML\Dto\PropertyDtoCollection;

/**
 * Class WarehouseDto
 *
 * @package Tygh\Addons\Warehouses\CommerceML\Dto
 */
class WarehouseDto implements RepresentEntityDto
{
    use RepresentEntitDtoTrait;

    const REPRESENT_ENTITY_TYPE = 'warehouse';

    /**
     * @var \Tygh\Addons\CommerceML\Dto\IdDto
     */
    public $id;

    /**
     * @var \Tygh\Addons\CommerceML\Dto\TranslatableValueDto|null
     */
    public $name;

    /**
     * @var \Tygh\Addons\CommerceML\Dto\TranslatableValueDto|null
     */
    public $city;

    /**
     * @var \Tygh\Addons\CommerceML\Dto\TranslatableValueDto|null
     */
    public $address;

    /**
     * @var \Tygh\Addons\CommerceML\Dto\PropertyDtoCollection
     */
    public $properties;


    /**
     * ProductFeatureDto constructor.
     */
    public function __construct()
    {
        $this->properties = new PropertyDtoCollection();
    }
}
