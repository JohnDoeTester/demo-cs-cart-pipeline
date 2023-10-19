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
 * 'copyright.txt' FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

use Tygh\Enum\YesNo;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

/**
 * Prepare RMA fields "is_returnable" and "return_period".
 *
 * @param string $object_id Product identifier
 * @param array  $object    Product data
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 *
 * @return void
 *
 * @param-out array $object
 */
function fn_import_prepare_rma_data($object_id, array &$object)
{
    $rma_settings = Registry::get('addons.rma');

    if (
        (
            empty($object['is_returnable'])
            || !in_array($object['is_returnable'], [YesNo::YES, YesNo::NO])
        )
        && empty($object_id)
    ) {
        $object['is_returnable'] = $rma_settings['returnable'];
    }

    if (
        !empty($object['return_period'])
        && is_numeric($object['return_period'])
        || !empty($object_id)
    ) {
        return;
    }

    $object['return_period'] = $rma_settings['return_period'];
}
