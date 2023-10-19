<?php

namespace Tygh\Tests\Unit\Functions\Catalog;

use Tygh\Enum\OutOfStockActions;
use Tygh\Enum\ProductTracking;
use Tygh\Enum\YesNo;
use Tygh\Tests\Unit\ATestCase;

class FnGetProductQtyContentTest extends ATestCase
{
    public $runTestInSeparateProcess = true;

    public $backupGlobals = false;

    public $preserveGlobalState = false;

    public function genP($qty_step = 0, $min_qty = 0, $max_qty = 0, $list_qty_count = 0, $in_stock = 0, $inventory_amount = 0, $amount = 0, $tracking = '', $out_of_stock_actions = OutOfStockActions::NONE)
    {
        return [
            'qty_step'             => $qty_step,
            'min_qty'              => $min_qty,
            'max_qty'              => $max_qty,
            'list_qty_count'       => $list_qty_count,
            'in_stock'             => $in_stock,
            'inventory_amount'     => $inventory_amount,
            'amount'               => $amount,
            'tracking'             => $tracking,
            'out_of_stock_actions' => $out_of_stock_actions
        ];
    }

    protected function setUp(): void
    {
        $this->requireCore('functions/fn.products.php');
        $this->requireCore('functions/fn.common.php');
    }

    /**
     * @param array  $product               Product data
     * @param string $allow_negative_amount Flag: allow or disallow negative product quantity(Y - allow, N - disallow)
     * @param string $inventory_tracking    Flag: track product qiantity or not (Y - track, N - do not track)
     *
     * @param array  $expected              Expected qty_content
     *
     * @dataProvider dpGeneral
     */
    public function testGeneral($product, $allow_negative_amount, $inventory_tracking, $expected)
    {
        $qty_content = fn_get_product_qty_content($product, $allow_negative_amount, $inventory_tracking);
        $this->assertEquals($expected, $qty_content);
    }

    public function dpGeneral()
    {
        $qc3 = [];
        for ($i = 1; $i<=120; $i++) {
            $qc3[] = $i*3;
        }

        return [
            [$this->genP(3, 0,  0,  0,  0,   0,  0,  ProductTracking::TRACK_WITHOUT_OPTIONS), YesNo::NO, YesNo::YES, []],                          // #2  => empty

            [$this->genP(3, 9,  14, 0,  10,  10, 10, ProductTracking::DO_NOT_TRACK), YesNo::YES, YesNo::YES, array_slice($qc3, 2, 2  )],           // #4  => 9  - 12
            [$this->genP(3, 0,  31, 0,  10,  10, 10, ProductTracking::DO_NOT_TRACK), YesNo::YES, YesNo::YES, array_slice($qc3, 0, 10 )],           // #5  => 3  - 30
            [$this->genP(3, 15, 15, 0,  10,  10, 10, ProductTracking::DO_NOT_TRACK), YesNo::YES, YesNo::YES, array_slice($qc3, 4, 1  )],           // #6  => 15

            [$this->genP(3, 0, 0, 5, 10, 10, 10, ProductTracking::DO_NOT_TRACK), YesNo::YES, YesNo::YES, array_slice($qc3, 0, 5)],                 // #7  => 3  - 15
            [$this->genP(3, 4,  0,  9,  10,  10, 10, ProductTracking::DO_NOT_TRACK), YesNo::YES, YesNo::YES, array_slice($qc3, 1, 9  )],           // #8  => 6  - 27
            [$this->genP(3, 2,  30, 5,  10,  10, 10, ProductTracking::DO_NOT_TRACK), YesNo::YES, YesNo::YES, array_slice($qc3, 0, 5  )],           // #9  => 3  - 15
            [$this->genP(3, 6,  17, 10, 10,  10, 10, ProductTracking::DO_NOT_TRACK), YesNo::YES, YesNo::YES, array_slice($qc3, 1, 4  )],           // #10 => 6  - 15
            [$this->genP(3, 15, 15, 2,  10,  10, 10, ProductTracking::DO_NOT_TRACK), YesNo::YES, YesNo::YES, array_slice($qc3, 4, 1  )],           // #11 => 15
            [$this->genP(3, 18, 15, 2,  10,  10, 10, ProductTracking::DO_NOT_TRACK), YesNo::YES, YesNo::YES, []],                                  // #12 => empty

            [$this->genP(3, 1,  60, 0,  333, 10, 10, ProductTracking::TRACK_WITHOUT_OPTIONS), YesNo::NO, YesNo::YES, array_slice($qc3, 0, 20 )],   // #14 => 3  - 60
            [$this->genP(3, 3,  0,  0,  10,  10, 10, ProductTracking::TRACK_WITHOUT_OPTIONS), YesNo::NO, YesNo::YES, array_slice($qc3, 0, 3  )],   // #15 => 3  - 9
            [$this->genP(3, 6,  15, 0,  10,  10, 10, ProductTracking::TRACK_WITHOUT_OPTIONS), YesNo::NO, YesNo::YES, array_slice($qc3, 1, 2  )],   // #16 => 6  - 9
            [$this->genP(3, 0,  15, 0,  10,  10, 10, ProductTracking::TRACK_WITHOUT_OPTIONS), YesNo::NO, YesNo::YES, array_slice($qc3, 0, 3  )],   // #17 => 3  - 9
            [$this->genP(3, 0,  7,  0,  10,  10, 10, ProductTracking::TRACK_WITHOUT_OPTIONS), YesNo::NO, YesNo::YES, array_slice($qc3, 0, 2  )],   // #18 => 3  - 6
            [$this->genP(3, 6,  6,  0,  10,  10, 10, ProductTracking::TRACK_WITHOUT_OPTIONS), YesNo::NO, YesNo::YES, array_slice($qc3, 1, 1  )],   // #19 => 6
            [$this->genP(3, 15, 15, 0,  10,  10, 10, ProductTracking::TRACK_WITHOUT_OPTIONS), YesNo::NO, YesNo::YES, []],                          // #20 => empty

            [$this->genP(3, 3,  0,  2,  10,  10, 10, ProductTracking::TRACK_WITHOUT_OPTIONS), YesNo::NO, YesNo::YES, array_slice($qc3, 0, 2  )],   // #21 => 3  - 6
            [$this->genP(3, 3,  0,  20, 10,  10, 10, ProductTracking::TRACK_WITHOUT_OPTIONS), YesNo::NO, YesNo::YES, array_slice($qc3, 0, 3  )],   // #22 => 3  - 9
            [$this->genP(3, 1,  0,  20, 333, 10, 10, ProductTracking::TRACK_WITHOUT_OPTIONS), YesNo::NO, YesNo::YES, array_slice($qc3, 0, 20 )],   // #23 => 3  - 60
            [$this->genP(3, 3,  3,  2,  10,  10, 10, ProductTracking::TRACK_WITHOUT_OPTIONS), YesNo::NO, YesNo::YES, array_slice($qc3, 0, 1  )],   // #24 => 3
            [$this->genP(3, 15, 15, 2,  10,  10, 10, ProductTracking::TRACK_WITHOUT_OPTIONS), YesNo::NO, YesNo::YES, []],                          // #25 => empty

            [[], YesNo::YES, YesNo::YES, []],
            [$this->genP(0, 0, 0, 0, 0, 0, 0, ''), YesNo::YES, YesNo::YES, []],

            [$this->genP(100, 100, 600, 0, 100, 0, 100, ProductTracking::TRACK_WITHOUT_OPTIONS, OutOfStockActions::BUY_IN_ADVANCE), YesNo::NO, YesNo::YES, ['100']],
            [$this->genP(100, 100, 600, 0, 0, 0, 100, ProductTracking::TRACK_WITHOUT_OPTIONS, OutOfStockActions::BUY_IN_ADVANCE), YesNo::NO, YesNo::YES, []],
            [$this->genP(30, 20, 0, 0, 0, 0, 100, ProductTracking::DO_NOT_TRACK, OutOfStockActions::NONE), YesNo::NO, YesNo::YES, []],
            [$this->genP(10, 0, 0, 0, null, null, 50, ProductTracking::TRACK_WITHOUT_OPTIONS, OutOfStockActions::NONE), YesNo::NO, YesNo::YES, [10 ,20, 30, 40, 50]]
        ];
    }
}