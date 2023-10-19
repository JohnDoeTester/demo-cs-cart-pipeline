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

$schema['top']['administration']['items']['import_data']['subitems']['advanced_import.advanced_products_import'] = array(
    'attrs' => array(
        'class'=>'is-addon'
    ),
    'href' => 'import_presets.manage?object_type=products',
    'position' => 0,
    'alt' => 'import_presets.update,import_presets.manage',
);

return $schema;
