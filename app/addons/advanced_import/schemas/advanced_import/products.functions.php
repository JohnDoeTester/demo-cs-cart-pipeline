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

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Removes null values from import data
 *
 * @param array $row Import data
 *
 * @return bool
 */
function fn_advanced_import_filter_out_null_values(&$row)
{
    $global_settings_schema = fn_get_schema('products', 'globals');

    foreach ($row as $key => $value) {
        if ($value !== null || isset($global_settings_schema[$key])) {
            continue;
        }

        unset($row[$key]);
    }

    return true;
}

/**
 * Wraps default main image import handler in order to inject additional import options
 *
 * @param string     $prefix            Path prefix
 * @param string     $image_file        Thumbnail path or filename
 * @param string     $detailed_file     Detailed image path or filename
 * @param string     $position          Image position
 * @param string     $type              Pair type
 * @param int        $object_id         ID of object to attach images to
 * @param string     $object            Name of object to attach images to
 * @param array|null $preset            Import preset data
 *
 * @return array|bool True if images were imported
 */
function fn_advanced_import_import_detailed_image($prefix, $image_file, $detailed_file, $position, $type, $object_id, $object, $preset)
{
    $import_options = [
        'images_company_id' => isset($preset['company_id']) ? $preset['company_id'] : null,
    ];

    if (
        fn_allowed_for('ULTIMATE')
        && Registry::get('runtime.simple_ultimate')
    ) {
        $prefix = Registry::get('runtime.forced_company_id') . '/' . $prefix;
    }

    return fn_exim_import_images($prefix, $image_file, $detailed_file, $position, $type, $object_id, $object, $import_options);
}
