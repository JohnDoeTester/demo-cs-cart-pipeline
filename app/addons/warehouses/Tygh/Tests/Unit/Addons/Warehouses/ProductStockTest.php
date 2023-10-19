<?php

namespace Tygh\Tests\Unit\Addons\Warehouses;

use Tygh\Addons\Warehouses\Destination;
use Tygh\Addons\Warehouses\ProductStock;
use Tygh\Addons\Warehouses\ProductWarehouse;
use Tygh\Tests\Unit\ATestCase;

class ProductStockTest extends ATestCase
{
    /**
     * @dataProvider dpGetWarehousesForDestination
     */
    public function testGetWarehousesForDestination($warehouses, $destination_id, $expected)
    {
        $stock = new ProductStock(1, $warehouses);
        $actual = $stock->getWarehousesForDestination($destination_id);

        $this->assertEquals($expected, $actual);
    }

    public function dpGetWarehousesForDestination()
    {
        return [
            [
                [],
                1,
                [],
            ],

            [
                [
                    [
                        'amount'                  => 1,
                        'position'                => 1,
                        'product_id'              => 1,
                        'store_type'              => 'S',
                        'warehouse_id'            => 1,
                        'main_destination_id'     => 1,
                        'pickup_destinations_ids' => '1,2',
                        'shipping_destinations_ids' => '2,3',
                        'destinations'            => [],
                        'status' => 'A',
                    ],
                    [
                        'amount'                  => 1,
                        'position'                => 1,
                        'product_id'              => 1,
                        'store_type'              => 'S',
                        'warehouse_id'            => 2,
                        'main_destination_id'     => 2,
                        'pickup_destinations_ids' => '2,3',
                        'shipping_destinations_ids' => '2,3',
                        'destinations'            => [],
                        'status' => 'A',
                    ],
                    [
                        'amount'                  => 1,
                        'position'                => 1,
                        'product_id'              => 1,
                        'store_type'              => 'S',
                        'warehouse_id'            => 3,
                        'main_destination_id'     => 1,
                        'pickup_destinations_ids' => '1,3',
                        'shipping_destinations_ids' => '',
                        'destinations'            => [],
                        'status' => 'A',
                    ],
                ],
                1,
                [
                    new ProductWarehouse([
                        'amount'          => 1,
                        'position'        => 1,
                        'product_id'      => 1,
                        'store_type'      => 'S',
                        'warehouse_id'    => 1,
                        'main_destination_id'     => 1,
                        'pickup_destination_ids' => '1,2',
                        'shipping_destination_ids' => '2,3',
                        'destinations'    => [],
                        'status' => 'A',
                    ]),
                    new ProductWarehouse([
                        'amount'          => 1,
                        'position'        => 1,
                        'product_id'      => 1,
                        'store_type'      => 'S',
                        'warehouse_id'    => 3,
                        'main_destination_id'     => 1,
                        'pickup_destination_ids' => '1,3',
                        'shipping_destination_ids' => '',
                        'destinations'    => [],
                        'status' => 'A',
                    ]),
                ],
            ],

            [
                [
                    [
                        'amount'                  => 1,
                        'position'                => 1,
                        'product_id'              => 1,
                        'store_type'              => 'S',
                        'warehouse_id'            => 1,
                        'main_destination_id'     => 1,
                        'pickup_destinations_ids' => '1,2,3',
                        'shipping_destinations_ids' => '1,2,3',
                        'destinations'            => [],
                        'status' => 'A',
                    ],
                    [
                        'amount'                  => 1,
                        'position'                => 1,
                        'product_id'              => 1,
                        'store_type'              => 'S',
                        'warehouse_id'            => 2,
                        'main_destination_id'     => 2,
                        'pickup_destinations_ids' => '2,3',
                        'shipping_destinations_ids' => '2,3',
                        'destinations'            => [],
                        'status' => 'A',
                    ],
                    [
                        'amount'                  => 1,
                        'position'                => 1,
                        'product_id'              => 1,
                        'store_type'              => 'S',
                        'warehouse_id'            => 3,
                        'main_destination_id'     => 1,
                        'pickup_destinations_ids' => '1,3',
                        'shipping_destinations_ids' => '1,3',
                        'destinations'            => [],
                        'status' => 'A',
                    ],
                ],
                4,
                [],
            ],

            [
                [
                    [
                        'amount'                  => 1,
                        'position'                => 1,
                        'product_id'              => 1,
                        'store_type'              => 'S',
                        'warehouse_id'            => 1,
                        'main_destination_id'     => 1,
                        'pickup_destinations_ids' => '1,2,3',
                        'shipping_destinations_ids' => '1,2,3',
                        'destinations'            => [
                            1 => [
                                'destination_id'   => 1,
                                'position'         => 30,
                                'shipping_delay'   => '',
                                'warn_about_delay' => false,
                            ],
                            2 => [
                                'destination_id'   => 2,
                                'position'         => 20,
                                'shipping_delay'   => '',
                                'warn_about_delay' => false,
                            ],
                            3 => [
                                'destination_id'   => 3,
                                'position'         => 10,
                                'shipping_delay'   => '',
                                'warn_about_delay' => false,
                            ],
                        ],
                        'status' => 'A',
                    ],
                    [
                        'amount'                  => 1,
                        'position'                => 1,
                        'product_id'              => 1,
                        'store_type'              => 'S',
                        'warehouse_id'            => 2,
                        'main_destination_id'     => 2,
                        'pickup_destinations_ids' => '2,3',
                        'shipping_destinations_ids' => '2,3',
                        'destinations'            => [
                            2 => [
                                'destination_id'   => 2,
                                'position'         => 20,
                                'shipping_delay'   => '',
                                'warn_about_delay' => false,
                            ],
                            3 => [
                                'destination_id'   => 3,
                                'position'         => 10,
                                'shipping_delay'   => '',
                                'warn_about_delay' => false,
                            ],
                        ],
                        'status' => 'A',
                    ],
                    [
                        'amount'                  => 1,
                        'position'                => 1,
                        'product_id'              => 1,
                        'store_type'              => 'S',
                        'warehouse_id'            => 3,
                        'main_destination_id'     => 1,
                        'pickup_destinations_ids' => '1,3',
                        'shipping_destinations_ids' => '1,3',
                        'destinations'            => [
                            1 => [
                                'destination_id'   => 1,
                                'position'         => 10,
                                'shipping_delay'   => '',
                                'warn_about_delay' => false,
                            ],
                            3 => [
                                'destination_id'   => 3,
                                'position'         => 30,
                                'shipping_delay'   => '',
                                'warn_about_delay' => false,
                            ],
                        ],
                        'status' => 'A',
                    ],
                ],
                1,
                [
                    new ProductWarehouse([
                        'amount'          => 1,
                        'position'        => 1,
                        'product_id'      => 1,
                        'store_type'      => 'S',
                        'warehouse_id'    => 3,
                        'main_destination_id'     => 1,
                        'pickup_destination_ids' => '1,3',
                        'shipping_destination_ids' => '1,3',
                        'destinations'    => [
                            1 => new Destination([
                                'destination_id'   => 1,
                                'position'         => 10,
                                'shipping_delay'   => '',
                                'warn_about_delay' => false,
                            ]),
                            3 => new Destination([
                                'destination_id'   => 3,
                                'position'         => 30,
                                'shipping_delay'   => '',
                                'warn_about_delay' => false,
                            ]),
                        ],
                        'status' => 'A',
                    ]),
                    new ProductWarehouse([
                        'amount'          => 1,
                        'position'        => 1,
                        'product_id'      => 1,
                        'store_type'      => 'S',
                        'warehouse_id'    => 1,
                        'main_destination_id'     => 1,
                        'pickup_destination_ids' => '1,2,3',
                        'shipping_destination_ids' => '1,2,3',
                        'destinations'    => [
                            1 => new Destination([
                                'destination_id'   => 1,
                                'position'         => 30,
                                'shipping_delay'   => '',
                                'warn_about_delay' => false,
                            ]),
                            2 => new Destination([
                                'destination_id'   => 2,
                                'position'         => 20,
                                'shipping_delay'   => '',
                                'warn_about_delay' => false,
                            ]),
                            3 => new Destination([
                                'destination_id'   => 3,
                                'position'         => 10,
                                'shipping_delay'   => '',
                                'warn_about_delay' => false,
                            ]),
                        ],
                        'status' => 'A',
                    ]),
                ],
            ],
        ];
    }

}
