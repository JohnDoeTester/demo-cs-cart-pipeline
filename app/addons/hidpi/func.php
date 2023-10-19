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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Hook handler: marks image as high resolution image
 *
 * @param array  $image_data  image data
 * @param int    $image_id    image ID
 * @param string $image_type  type of an object image belongs to (product, category, etc.)
 * @param string $images_path path to directory image is located at
 * @param array  $_data       data to be saved into "images" DB table
 * @param string $mime_type   MIME type of an image file
 * @param bool   $is_clone    true if image is copied from an existing image object
 */
function fn_hidpi_update_image($image_data, $image_id, $image_type, $images_path, &$_data, $mime_type, $is_clone)
{
    if (!empty($image_data['clone_from'])) {
        $original_is_high_res = db_get_field('SELECT is_high_res FROM ?:images WHERE image_id = ?i', $image_data['clone_from']);
        $image_data['is_high_res'] = $original_is_high_res == HIDPI_IS_HIGH_RES_TRUE;
    }

    if (isset($image_data['is_high_res']) && $image_data['is_high_res']) {
        $_data['is_high_res'] = HIDPI_IS_HIGH_RES_TRUE;
        $_data['image_x'] = (int) $_data['image_x'] / 2;
        $_data['image_y'] = (int) $_data['image_y'] / 2;
    } else {
        $_data['is_high_res'] = HIDPI_IS_HIGH_RES_FALSE;
    }
}

/**
 * Hook handler: retrieves high resolution marks
 *
 * @param array/int $object_ids   List of Object IDs or Object ID
 * @param string    $object_type  Type: product, category, banner, etc.
 * @param string    $pair_type    (M)ain or (A)dditional
 * @param bool      $get_icon
 * @param bool      $get_detailed
 * @param string    $lang_code      2-letters code
 * @param array     $pairs_data     Pairs data
 * @param array     $detailed_pairs Pairs data for detailed
 * @param array     $icon_pairs     Pairs data for icon
 */
function fn_hidpi_get_image_pairs_post($object_ids, $object_type, $pair_type, $get_icon, $get_detailed, $lang_code, &$pairs_data, $detailed_pairs, $icon_pairs)
{
    $map_icon = $map_detailed = [];

    foreach ($detailed_pairs as $item) {
        $map_detailed[$item['pair_id']] = $item;
    }

    foreach ($icon_pairs as $item) {
        $map_icon[$item['pair_id']] = $item;
    }

    foreach ($pairs_data as $object_id => &$pairs) {
        foreach ($pairs as $pair_id => &$item) {
            if (isset($map_detailed[$pair_id]) && isset($item['detailed'])) {
                $item['detailed']['is_high_res'] = $map_detailed[$pair_id]['is_high_res'] == HIDPI_IS_HIGH_RES_TRUE;
            }

            if (isset($map_icon[$pair_id]) && isset($item['icon'])) {
                $item['icon']['is_high_res'] = $map_icon[$pair_id]['is_high_res'] == HIDPI_IS_HIGH_RES_TRUE;;
            }
        }
        unset($item);
    }
    unset($pairs);
}

/**
 * Hook handler: creates thumbnail for high resolution logo
 *
 * @param int    $company_id company ID
 * @param int    $layout_id  layout ID
 * @param string $style_id   Style ID
 * @param array  $logos      Selected logos
 */
function fn_hidpi_get_logos_post($company_id, $layout_id, $style_id, &$logos)
{
    foreach ($logos as &$logo) {
        if (empty($logo['image']['is_high_res'])) {
            continue;
        }

        $image = fn_image_to_display($logo['image'], $logo['image']['image_x'], $logo['image']['image_y']);

        if (!empty($image['image_path'])) {
            $logo['image']['original_image_path'] = $logo['image']['image_path'];
            $logo['image']['image_path'] = $image['image_path'];
        }
    }
    unset($logo);
}

/**
 * Hook handler: retrieves high resolution marks from request
 *
 * @param string $name          name of uploaded data
 * @param array  $filter_by_ext allow file extensions
 * @param array  $filtered      filtered file data
 * @param array  $udata_local   List of uploaded files
 * @param array  $udata_other   List of files object types
 * @param array  $utype         List of files sources
 */
function fn_hidpi_filter_uploaded_data_post($name, $filter_by_ext, &$filtered, $udata_local, $udata_other, $utype)
{
    $marks = empty($_REQUEST['is_high_res_' . $name]) ? [] : (array) $_REQUEST['is_high_res_' . $name];

    foreach ($marks as $id => $mark) {
        if (!isset($filtered[$id])) {
            continue;
        }

        $filtered[$id]['is_high_res'] = $mark == HIDPI_IS_HIGH_RES_TRUE;
    }
}