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

/**
 * Describes a way to describe grids
 *
 * Structure:
 *
 * 'wrappers' => array(
 *     'wrapper_name' => 'template_name', // the list of "name -> template" pairs that will wrap blocks content inside grid
 * )
 */

/** @var array<string, array> $schema */
$schema = [
    'wrappers' => [
        __('block_manager.wrappers.lite_checkout') => 'blocks/grid_wrappers/lite_checkout.tpl',
    ],
];

return $schema;
