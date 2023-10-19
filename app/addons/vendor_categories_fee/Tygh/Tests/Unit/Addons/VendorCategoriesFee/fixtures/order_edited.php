<?php

$order_total = 298.0;

$payout_data = array(
    'order_amount'      => -70.0,
    'payout_type'       => 'order_changed',
    'commission_amount' => -4.9,
    'plan_id'           => 3,
    'extra'             =>
        array(
            'percent_commission' => -4.9,
        ),
);

$products = array(
    285 =>
        array(
            'main_category' => 165,
            'subtotal'      => 70.0,
        ),
    286 =>
        array(
            'main_category' => 209,
            'subtotal'      => 200.0,
        ),
    287 =>
        array(
            'main_category' => 224,
            'subtotal'      => 100.0,
        ),
);

$main_categories_fee = array(
    209 =>
        array(
            3 =>
                array(
                    'percent_fee' => 0.00,
                ),
        ),
);

$parent_categories_fee = array(
    165 =>
        array(
            3 =>
                array(
                    'percent_fee' => 33.00,
                ),
        ),
    209 =>
        array(
            3 =>
                array(
                    'percent_fee' => 7.00,
                ),
        ),
    224 =>
        array(
            3 =>
                array(
                    'percent_fee' => 7.00,
                ),
        ),
);

$payouts_history = array(
    0 =>
        array(
            'extra' =>
                array(
                    'category_fee' =>
                        array(
                            'category_fee_amount' => 50.349090909090904,
                        ),
                ),
        ),
);

return [$order_total, $payout_data, $products, $main_categories_fee, $parent_categories_fee, $payouts_history];
