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

$schema['export_fields']['Vendor plan'] = array(
    'db_field' => 'plan_id',
);

if (in_array('absolute_vendor_commission', fn_get_table_fields('companies'))) {
    $schema['export_fields']['Absolute vendor commission value'] = array(
        'db_field' => 'absolute_vendor_commission',
    );
}

return $schema;
