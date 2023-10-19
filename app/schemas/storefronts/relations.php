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

defined('BOOTSTRAP') or die('Access denied');

return [
    'country_codes' => [
        'table'               => '?:storefronts_countries',
        'table_alias'         => 'countries',
        'type'                => 'value',
        'id_field'            => 'country_code',
        'storefront_id_field' => 'storefront_id',
    ],
    'company_ids'   => [
        'table'               => '?:storefronts_companies',
        'table_alias'         => 'companies',
        'type'                => 'value',
        'id_field'            => 'company_id',
        'storefront_id_field' => 'storefront_id',
    ],
    'currency_ids'  => [
        'table'               => '?:storefronts_currencies',
        'table_alias'         => 'currencies',
        'type'                => 'value',
        'id_field'            => 'currency_id',
        'storefront_id_field' => 'storefront_id',
        'related_table'       => '?:currencies',
        'related_id_field'    => 'currency_id',
    ],
    'language_ids'  => [
        'table'               => '?:storefronts_languages',
        'table_alias'         => 'languages',
        'type'                => 'value',
        'id_field'            => 'language_id',
        'storefront_id_field' => 'storefront_id',
        'related_table'       => '?:languages',
        'related_id_field'    => 'lang_id',
    ],
    'payment_ids'   => [
        'table'               => '?:storefronts_payments',
        'table_alias'         => 'payments',
        'type'                => 'value',
        'id_field'            => 'payment_id',
        'storefront_id_field' => 'storefront_id',
    ],
    'shipping_ids'   => [
        'table'               => '?:storefronts_shippings',
        'table_alias'         => 'shippings',
        'type'                => 'value',
        'id_field'            => 'shipping_id',
        'storefront_id_field' => 'storefront_id',
    ],
    'promotion_ids'   => [
        'table'               => '?:storefronts_promotions',
        'table_alias'         => 'promotions',
        'type'                => 'value',
        'id_field'            => 'promotion_id',
        'storefront_id_field' => 'storefront_id',
    ],
];
