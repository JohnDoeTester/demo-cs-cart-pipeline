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

$schema['top']['administration']['items']['store_locator'] = [
    'attrs' => [
        'class' => 'is-addon'
    ],
    'href' => 'store_locator.manage',
    'position' => 400
];

$schema['top']['administration']['items']['import_data']['subitems']['pickup'] = [
    'href' => 'exim.import?section=pickup',
    'position' => 500,
];

$schema['top']['administration']['items']['export_data']['subitems']['pickup'] = [
    'href' => 'exim.export?section=pickup',
    'position' => 500,
];

return $schema;
