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

$schema = fn_get_schema('exim', 'products');

if (fn_allowed_for('ULTIMATE')) {
    $schema['export_fields']['Price'] = array(
        'table' => 'product_prices',
        'db_field' => 'price',
        'process_get' => array(
            'fn_data_feeds_export_price',
            '#key', '#this', '@company_id', '@price_dec_sign_delimiter'
        ),
    );

    $schema['export_fields']['Category'] = array(
        'process_get' => array('fn_data_feeds_get_product_categories', '#key', '@category_delimiter', '@company_id', '#lang_code'),
        'multilang' => true,
        'linked' => false,
    );
}

$schema['is_data_feeds'] = true;

return $schema;
