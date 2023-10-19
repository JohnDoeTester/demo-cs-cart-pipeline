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

use Tygh\Enum\ObjectStatuses;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */

$schema['central']['content']['items']['comments_and_reviews'] = [
    'attrs'    => [
        'class' => 'is-addon',
    ],
    'href'     => 'discussion_manager.manage',
    'position' => 110,
    'status'   => ObjectStatuses::DISABLED,
];

return $schema;
