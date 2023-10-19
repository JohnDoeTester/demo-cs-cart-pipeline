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

$schema['central']['marketing']['items']['gift_certificates'] = [
    'href'     => 'gift_certificates.manage',
    'position' => 500,
    'attrs'    => [
        'class' => 'is-addon',
    ],
];
$schema['top']['administration']['items']['statuses_management']['subitems']['gift_certificate_statuses'] = [
    'href'     => 'statuses.manage?type=' . STATUSES_GIFT_CERTIFICATE,
    'position' => 300,
    'attrs'    => [
        'class' => 'is-addon',
    ],
];

return $schema;
