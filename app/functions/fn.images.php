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

use Imagine\Image\Metadata\ExifMetadataReader;
use Tygh\Enum\ImagePairTypes;
use Tygh\Enum\MultiQueryTypes;
use Tygh\Registry;
use Tygh\Storage;
use Tygh\Settings;
use Tygh\Tools\ImageHelper;
use Tygh\Languages\Languages;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

//
// Get image
//
function fn_get_image($image_id, $object_type, $lang_code = CART_LANGUAGE, $get_all_alts = false)
{
    $path = $object_type;

    if (!empty($image_id) && !empty($object_type)) {
        $image_data = db_get_row("SELECT ?:images.image_id, ?:images.image_path, ?:common_descriptions.description as alt, ?:images.image_x, ?:images.image_y FROM ?:images LEFT JOIN ?:common_descriptions ON ?:common_descriptions.object_id = ?:images.image_id AND ?:common_descriptions.object_holder = 'images' AND ?:common_descriptions.lang_code = ?s  WHERE ?:images.image_id = ?i", $lang_code, $image_id);
        if ($get_all_alts && count(Languages::getAll()) > 1) {
            $image_data['alt'] = db_get_hash_single_array('SELECT description, lang_code FROM ?:common_descriptions WHERE object_id = ?i AND object_holder = ?s', array('lang_code', 'description'), $image_data['image_id'], 'images');
        }
    }

    fn_attach_absolute_image_paths($image_data, $object_type);

    return (!empty($image_data) ? $image_data : false);
}

//
// Attach image paths
//
function fn_attach_absolute_image_paths(&$image_data, $object_type)
{
    $image_id = !empty($image_data['images_image_id'])? $image_data['images_image_id'] : $image_data['image_id'];
    $path = $object_type . '/' . floor($image_id / MAX_FILES_IN_DIR);

    $image_name = '';
    $image_data['relative_path'] = $image_data['http_image_path'] = $image_data['https_image_path'] = $image_data['absolute_path'] = '';

    if (!empty($image_data['image_path'])) {

        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = Tygh::$app['storefront'];
        $url = $storefront->url;

        $image_name = $image_data['image_path'];
        $image_data['relative_path'] = $path . '/' . $image_name;
        $image_data['http_image_path'] = Storage::instance('images')->getUrl($path . '/' . $image_name, 'http', $url);
        $image_data['https_image_path'] = Storage::instance('images')->getUrl($path . '/' . $image_name, 'https', $url);
        $image_data['absolute_path'] = Storage::instance('images')->getAbsolutePath($path . '/' . $image_name);
        $image_data['image_path'] = Storage::instance('images')->getUrl($path . '/' . $image_name, '', $url);
    }

    fn_set_hook('attach_absolute_image_paths', $image_data, $object_type, $path, $image_name);

    return $image_data;
}

/**
 * Function creates or updates image
 *
 * @param array{name: string, path: string, params?: array<string, string>, size: int} $image_data Array with image data
 * @param int                                                                          $image_id   Image ID
 * @param string                                                                       $image_type Type (object) of image (may be product, category, and so on)
 * @param string                                                                       $lang_code  Two letters language code
 * @param bool                                                                         $is_clone   True if image is copied from an existing image object
 *
 * @return int Updated or inserted image ID. False on failure.
 */
function fn_update_image(array $image_data, $image_id = 0, $image_type = 'product', $lang_code = CART_LANGUAGE, $is_clone = false)
{
    $images_path = $image_type . '/' . fn_get_image_subdir($image_id) . '/';
    $_data = [];

    list($_data['image_x'], $_data['image_y'], $mime_type) = fn_get_image_size($image_data['path']);

    // Get the real image type
    $ext = fn_get_image_extension($mime_type);
    if (strpos($image_data['name'], '.') === false) {
        $image_data['name'] .= '.' . $ext;
    }

    // Check if image path already set
    if ($image_id) {
        $image_path = db_get_field('SELECT image_path FROM ?:images WHERE image_id = ?i', $image_id);
    } else {
        $image_path = null;
    }

    // Delete existing image
    if (!empty($image_path)) {
        Storage::instance('images')->delete($images_path . $image_path);

        // Clear all existing thumbnails
        fn_delete_image_thumbnails($images_path . $image_path);
        
        $image_data['old_name'] = $image_path;
    }

    /**
     * Hook is executed before saving or updating an image.
     *
     * @param array   $image_data  Image data
     * @param int     $image_id    Image ID
     * @param string  $image_type  Type of an object image belongs to (product, category, etc.)
     * @param string  $images_path Path to directory image is located at
     * @param array   $_data       Data to be saved into "images" DB table
     * @param string  $mime_type   MIME type of an image file
     * @param bool    $is_clone    True if image is copied from an existing image object
     */
    fn_set_hook('update_image', $image_data, $image_id, $image_type, $images_path, $_data, $mime_type, $is_clone);

    $params = [
        'file' => $image_data['path'],
    ];

    if (!empty($image_data['params'])) {
        $params = fn_array_merge($params, $image_data['params']);
    }

    list($_data['image_size'], $_data['image_path']) = Storage::instance('images')->put($images_path . $image_data['name'], $params);

    $_data['image_path'] = fn_basename($_data['image_path']); // we need to store file name only

    if (!empty($image_id)) {
        db_query('UPDATE ?:images SET ?u WHERE image_id = ?i', $_data, $image_id);
    } else {
        $image_id = db_query('INSERT INTO ?:images ?e', $_data);
    }

    return $image_id;
}

function fn_add_image_link($pair_target_id, $pair_id)
{
    $pair_data = db_get_row("SELECT * FROM ?:images_links WHERE pair_id = ?i", $pair_id);
    unset($pair_data['pair_id']);
    $pair_data['object_id'] = $pair_target_id;

    return db_query("INSERT INTO ?:images_links ?e", $pair_data);
}

function fn_get_count_image_link($image_id)
{
    return db_get_field("SELECT COUNT(*) FROM ?:images_links WHERE image_id = ?i OR detailed_id = ?i", $image_id, $image_id);
}

/**
 * Removes image file and all its object links from db
 *
 * @param int    $image_id    Image identifier
 * @param int    $pair_id     Image link identifier
 * @param string $object_type Type of deleted image
 *
 * @return bool Always true
 */
function fn_delete_image($image_id, $pair_id, $object_type = 'product')
{
    if (AREA == 'A' && fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id') && $object_type == 'category') {
        return false;
    }

    $image_file = fn_get_image_file_by_id($image_id);
    if (empty($image_file)) {
        return false;
    }

    fn_set_hook('delete_image_pre', $image_id, $pair_id, $object_type);

    $type = ($object_type == 'detailed' ? 'detailed_id' : 'image_id');
    db_query('UPDATE ?:images_links SET ?f = ?s WHERE pair_id = ?i', $type, '0', $pair_id);
    $ids = db_get_row('SELECT image_id, detailed_id FROM ?:images_links WHERE pair_id = ?i', $pair_id);

    if (empty($ids['image_id']) && empty($ids['detailed_id'])) {
        db_query('DELETE FROM ?:images_links WHERE pair_id = ?i', $pair_id);
    }


    fn_delete_image_file($image_id, $object_type, $image_file);

    fn_set_hook('delete_image', $image_id, $pair_id, $object_type, $image_file);

    return true;
}

/**
 * Deletes all thumbnails of specified file
 *
 * @param string $filename file name
 * @param string $prefix path prefix
 * @return boolean always true
 */
function fn_delete_image_thumbnails($filename, $prefix = '')
{
    $filename = fn_substr($filename, 0, strrpos($filename, '.'));

    if (!empty($filename)) {
        Storage::instance('images')->deleteByPattern($prefix . 'thumbnails/*/*/' . $filename . '*');
    }

    return true;
}

/**
 * Gets image file name with extension by identifier
 *
 * @param int $image_id Image identifier
 *
 * @return string Image path if image identifier is not null; false otherwise
 */
function fn_get_image_file_by_id($image_id)
{
    return db_get_field('SELECT image_path FROM ?:images WHERE image_id = ?i', $image_id);
}

/**
 * Removes image file without active links with objects and info about it from db.
 *
 * @param int    $image_id    Image identifier
 * @param string $object_type Type of deleted image
 *
 * @return bool True if deleting was successful, false otherwise
 */
function fn_delete_image_file($image_id, $object_type)
{
    if (fn_get_count_image_link($image_id) != 0) {
        return false;
    }

    $image_file = fn_get_image_file_by_id($image_id);
    if (empty($image_file)) {
        return false;
    }

    $image_subdir = fn_get_image_subdir($image_id);
    $image_file = $object_type . '/' . $image_subdir . '/' . $image_file;

    Storage::instance('images')->delete($image_file);

    $result = db_query('DELETE FROM ?:images WHERE image_id = ?i', $image_id);
    db_query('DELETE FROM ?:common_descriptions WHERE object_id = ?i AND object_holder = ?s', $image_id, 'images');

    // Clear all existing thumbnails
    fn_delete_image_thumbnails($image_file);

    return $result;
}

/**
 * Gets image pairs (icon, detailed)
 *
 * @param int[]|int $object_ids   List of Object IDs or Object ID
 * @param string    $object_type  Type: product, category, banner, etc.
 * @param string    $pair_type    Main(M) or Additional(A)
 * @param bool      $get_icon     If need get icon
 * @param bool      $get_detailed If need get detailed
 * @param string    $lang_code    Two-letters code
 * @param bool      $get_all      Return all image pairs, only for Main pair type and single object_ids
 *
 * @return array Pair data
 */
function fn_get_image_pairs($object_ids, $object_type, $pair_type, $get_icon = true, $get_detailed = true, $lang_code = CART_LANGUAGE, $get_all = false)
{
    /**
     * Changes input params for fn_get_image_pairs function
     *
     * @param array/int $object_ids   List of Object IDs or Object ID
     * @param string    $object_type  Type: product, category, banner, etc.
     * @param string    $pair_type    (M)ain or (A)dditional
     * @param bool      $get_icon
     * @param bool      $get_detailed
     * @param string    $lang_code    2-letters code
     * @param bool      $get_all      Return all image pairs, only for Main pair type and single object_ids
     *
     * @return array     Pair data
     */
    fn_set_hook('get_image_pairs_pre', $object_ids, $object_type, $pair_type, $get_icon, $get_detailed, $lang_code, $get_all);

    $icon_pairs = $detailed_pairs = $pairs_data = array();

    if (is_array($object_ids)) {
        $cond = $object_ids
            ? db_quote('AND ?:images_links.object_id IN (?n)', $object_ids)
            : db_quote('AND ?:images_links.object_id IS NULL'); // backward compatibility: prevents SQL empty array deprecation notice
    } else {
        $cond = db_quote('AND ?:images_links.object_id = ?s', $object_ids);
    }

    if ($get_icon == true || $get_detailed == true) {
        $images_query_tpl = 'SELECT ?:images.*, ?:images_links.*, ?:common_descriptions.description AS alt, ?:images.image_id AS images_image_id'
            . ' FROM ?:images_links'
            . ' LEFT JOIN ?:images'
                . ' ON ?p'
            . ' LEFT JOIN ?:common_descriptions'
                . ' ON ?:common_descriptions.object_id = ?:images.image_id'
                . ' AND ?:common_descriptions.object_holder = ?s'
                . ' AND ?:common_descriptions.lang_code = ?s'
            . ' WHERE ?:images_links.object_type = ?s'
                . ' AND ?:images_links.type = ?s'
                . ' ?p'
            . ' ORDER BY ?:images_links.position, ?:images_links.pair_id';

        $query_stack = [];

        if ($get_icon == true) {
            $join_cond = db_quote('?:images_links.image_id = ?:images.image_id');
            $query_stack['icon_pairs'] = [
                MultiQueryTypes::ARR,
                $images_query_tpl,
                $join_cond,
                'images',
                $lang_code,
                $object_type,
                $pair_type,
                $cond
            ];
        }

        if ($get_detailed == true) {
            $join_cond = db_quote('?:images_links.detailed_id = ?:images.image_id');
            $query_stack['detailed_pairs'] = [
                MultiQueryTypes::ARR,
                $images_query_tpl,
                $join_cond,
                'images',
                $lang_code,
                $object_type,
                $pair_type,
                $cond
            ];
        }

        $m_pairs = db_multi_query($query_stack);

        if (!empty($m_pairs['icon_pairs'])) {
            $icon_pairs = $m_pairs['icon_pairs'];
        }

        if (!empty($m_pairs['detailed_pairs'])) {
            $detailed_pairs = $m_pairs['detailed_pairs'];
        }

        foreach ((array) $object_ids as $object_id) {
            $pairs_data[$object_id] = array();
        }

        // Convert the received data to the standard format in order to keep the backward compatibility
        foreach ($icon_pairs as $pair) {
            $_pair = [
                'pair_id' => $pair['pair_id'],
                'image_id' => $pair['image_id'],
                'detailed_id' => $pair['detailed_id'],
                'position' => $pair['position'],
                'object_id' => $pair['object_id'],
                'object_type' => $pair['object_type'],
            ];

            if (!empty($pair['images_image_id'])) { //get icon data if exist
                $icon = fn_attach_absolute_image_paths($pair, $object_type);

                $_pair['icon'] = array(
                    'image_path' => $icon['image_path'],
                    'alt' => $icon['alt'],
                    'image_x' => $icon['image_x'],
                    'image_y' => $icon['image_y'],
                    'http_image_path' => $icon['http_image_path'],
                    'https_image_path' => $icon['https_image_path'],
                    'absolute_path' => $icon['absolute_path'],
                    'relative_path' => $icon['relative_path']
                );
            }

            $pairs_data[$pair['object_id']][$pair['pair_id']] = $_pair;
        }// -foreach icon_pairs

        foreach ($detailed_pairs as $pair) {
            $pair_id = $pair['pair_id'];
            $object_id = $pair['object_id'];

            if (!empty($pairs_data[$object_id][$pair_id]['detailed_id'])) {
                $detailed = fn_attach_absolute_image_paths($pair, 'detailed');
                $pairs_data[$object_id][$pair_id]['detailed'] = array(
                    'object_id' => $pair['object_id'],
                    'object_type' => $pair['object_type'],
                    'type' => $pair['type'],
                    'image_path' => $detailed['image_path'],
                    'alt' => $detailed['alt'],
                    'image_x' => $detailed['image_x'],
                    'image_y' => $detailed['image_y'],
                    'http_image_path' => $detailed['http_image_path'],
                    'https_image_path' => $detailed['https_image_path'],
                    'absolute_path' => $detailed['absolute_path'],
                    'relative_path' => $detailed['relative_path']
                );
            } elseif (empty($pairs_data[$object_id][$pair_id]['pair_id'])) {
                $pairs_data[$object_id][$pair_id] = array(
                    'object_id' => $pair['object_id'],
                    'object_type' => $pair['object_type'],
                    'pair_id' => $pair['pair_id'],
                    'image_id' => $pair['image_id'],
                    'detailed_id' => $pair['detailed_id'],
                    'position' => $pair['position'],
                );

                if (!empty($pair['images_image_id'])) { //get detailed data if exist
                    $detailed = fn_attach_absolute_image_paths($pair, 'detailed');
                    $pairs_data[$object_id][$pair_id]['detailed'] = array(
                        'object_id' => $pair['object_id'],
                        'object_type' => $pair['object_type'],
                        'image_path' => $detailed['image_path'],
                        'alt' => $detailed['alt'],
                        'image_x' => $detailed['image_x'],
                        'image_y' => $detailed['image_y'],
                        'http_image_path' => $detailed['http_image_path'],
                        'https_image_path' => $detailed['https_image_path'],
                        'absolute_path' => $detailed['absolute_path'],
                        'relative_path' => $detailed['relative_path']
                    );
                }
            }
        }// -foreach detailed_pairs

    } else {
        $pairs_data = db_get_hash_multi_array(
            'SELECT pair_id, image_id, detailed_id, object_id, object_type'
            . ' FROM ?:images_links'
            . ' WHERE object_type = ?s'
                . ' AND type = ?s'
                . ' ?p',
            ['object_id', 'pair_id'],
            $object_type,
            $pair_type,
            $cond
        );
    }

    /**
     * Changes pair data informatin
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
     * @param bool      $get_all        Return all image pairs, only for Main pair type and single object_ids
     */
    fn_set_hook('get_image_pairs_post', $object_ids, $object_type, $pair_type, $get_icon, $get_detailed, $lang_code, $pairs_data, $detailed_pairs, $icon_pairs, $get_all);

    if (is_array($object_ids)) {
        return $pairs_data;
    } else {
        if ($pair_type === ImagePairTypes::ADDITIONAL || $get_all) {
            return $pairs_data[$object_ids];
        } else {
            return !empty($pairs_data[$object_ids]) ? reset($pairs_data[$object_ids]) : [];
        }
    }
}

/**
 * Create/Update image pairs (icon -> detailed image)
 *
 * @param array  $icons            Data of the object icon
 * @param array  $detailed         Data of the object detailed image
 * @param array  $pairs_data       Required image data for updating
 * @param int    $object_id        Object identifier
 * @param string $object_type      Object type
 * @param array  $object_ids       Used instead object identifier if there are several objects
 * @param bool   $update_alt_desc  True if image alt text should be updated
 * @param string $lang_code        Two-letter language code
 * @param bool   $from_exist_pairs True if image is copied from an existing image object
 *
 * @return array Identifiers updated images links
 */
function fn_update_image_pairs($icons, $detailed, $pairs_data, $object_id = 0, $object_type = 'product_lists', $object_ids = array(), $update_alt_desc = true, $lang_code = CART_LANGUAGE, $from_exist_pairs = false)
{
    $pair_ids = array();

    /**
     *  Adds additional actions before image pairs updating
     *
     * @param array  $icons            Data of the object icon
     * @param array  $detailed         Data of the object detailed image
     * @param array  $pairs_data       Required image data for updating
     * @param int    $object_id        Object identifier
     * @param string $object_type      Object type
     * @param array  $object_ids       Used instead object identifier if there are several objects
     * @param bool   $update_alt_desc  True if image alt text should be updated
     * @param string $lang_code        Two-letter language code
     * @param bool   $from_exist_pairs True if image is copied from an existing image object
     */
    fn_set_hook('update_image_pairs_pre', $icons, $detailed, $pairs_data, $object_id, $object_type, $object_ids, $update_alt_desc, $lang_code, $from_exist_pairs);

    if (!empty($pairs_data)) {
        foreach ($pairs_data as $k => $p_data) {
            $data = array();
            $pair_id = !empty($p_data['pair_id']) ? $p_data['pair_id'] : 0;
            $o_id = !empty($object_id) ? $object_id : ((!empty($p_data['object_id'])) ? $p_data['object_id'] : 0);

            if ($o_id == 0 && !empty($object_ids[$k])) {
                $o_id = $object_ids[$k];
            } elseif (!empty($object_ids) && empty($object_ids[$k])) {
                continue;
            }

            $is_main_pair = !empty($p_data['type']) && $p_data['type'] == 'M';
            $is_new_pair = !empty($p_data['is_new']) && $p_data['is_new'] == 'Y';

            // Check if main pair is exists
            if (empty($pair_id) && $is_main_pair && !$is_new_pair) {
                $pair_data = db_get_row(
                    'SELECT pair_id, image_id, detailed_id FROM ?:images_links WHERE object_id = ?i AND object_type = ?s AND type = ?s',
                    $o_id,
                    $object_type,
                    $p_data['type']
                );
                $pair_id = !empty($pair_data['pair_id']) ? $pair_data['pair_id'] : 0;
            } elseif ($pair_id) {
                $pair_data = db_get_row('SELECT image_id, detailed_id FROM ?:images_links WHERE pair_id = ?i', $pair_id);
                if (empty($pair_data)) {
                    $pair_id = 0;
                }
            } else {
                $pair_data = [];
            }

            // Update detailed image
            if (!empty($detailed[$k]) && !empty($detailed[$k]['size'])) {
                if (fn_get_image_size($detailed[$k]['path'])) {
                    $data['detailed_id'] = fn_update_image($detailed[$k], !empty($pair_data['detailed_id']) ? $pair_data['detailed_id'] : 0, 'detailed', $lang_code, $from_exist_pairs);
                }
            }

            // Update icon
            if (!empty($icons[$k]) && !empty($icons[$k]['size'])) {
                if (fn_get_image_size($icons[$k]['path'])) {
                    $data['image_id'] = fn_update_image($icons[$k], !empty($pair_data['image_id']) ? $pair_data['image_id'] : 0, $object_type, $lang_code, $from_exist_pairs);
                }
            }

            // Update alt descriptions
            if (((empty($data) && !empty($pair_id)) || !empty($data)) && $update_alt_desc == true) {
                $image_ids = array();
                if (!empty($pair_id)) {
                    $image_ids = db_get_row("SELECT image_id, detailed_id FROM ?:images_links WHERE pair_id = ?i", $pair_id);
                }

                $image_ids = $data = fn_array_merge($image_ids, $data);

                $fields = array('detailed', 'image');
                foreach ($fields as $field) {
                    if (!empty($image_ids[$field . '_id']) && isset($p_data[$field . '_alt'])) {
                        if (!is_array($p_data[$field . '_alt'])) {
                            $_data = array (
                                'description' => empty($p_data[$field . '_alt']) ? '' : trim($p_data[$field . '_alt']),
                                'object_holder' => 'images'
                            );

                            // check, if this is new record, create new descriptions for all languages
                            $is_exists = db_get_field('SELECT object_id FROM ?:common_descriptions WHERE object_id = ?i AND lang_code = ?s AND object_holder = ?s', $image_ids[$field . '_id'], $lang_code, 'images');
                            if (!$is_exists) {
                                fn_create_description('common_descriptions', 'object_id', $image_ids[$field . '_id'], $_data);
                            } else {
                                db_query('UPDATE ?:common_descriptions SET ?u WHERE object_id = ?i AND lang_code = ?s AND object_holder = ?s', $_data, $image_ids[$field . '_id'], $lang_code, 'images');
                            }
                        } else {
                            foreach ($p_data[$field . '_alt'] as $lc => $_v) {
                                $_data = array (
                                    'object_id' => $image_ids[$field . '_id'],
                                    'description' => empty($_v) ? '' : trim($_v),
                                    'lang_code' => $lc,
                                    'object_holder' => 'images'
                                );
                                db_query("REPLACE INTO ?:common_descriptions ?e", $_data);
                            }
                        }
                    }
                }
            }

            if (empty($data)) {
                continue;
            }

            // Pair exists
            $data['position'] = !empty($p_data['position']) ? $p_data['position'] : 0; // set data position

            if (!empty($p_data['type'])) {
                $data['type'] = $p_data['type']; // set link type
            }

            if (!empty($pair_id)) {
                db_query('UPDATE ?:images_links SET ?u WHERE pair_id = ?i', $data, $pair_id);
            } else {
                $data['object_id'] = $o_id; // assign pair to object
                $data['object_type'] = $object_type;
                $pair_id = db_query('INSERT INTO ?:images_links ?e', $data);
            }

            $pairs_data[$k]['pair_id'] = $pair_id;

            $pair_ids[] = $pair_id;
        }
    }

    /**
     *  Adds additional actions after image pairs updating
     *
     * @param array  $pair_ids        Identifiers updated images links
     * @param array  $icons           Data of the object icon
     * @param array  $detailed        Data of the object detailed image
     * @param array  $pairs_data      Required image data for updating
     * @param int    $object_id       Object identifier
     * @param string $object_type     Object type
     * @param array  $object_ids      Used instead object identifier if there are several objects
     * @param bool   $update_alt_desc True if image alt text should be updated
     * @param string $lang_code       Two-letter language code
     */
    fn_set_hook('update_image_pairs', $pair_ids, $icons, $detailed, $pairs_data, $object_id, $object_type, $object_ids, $update_alt_desc, $lang_code);

    return $pair_ids;
}

/**
 * Removes all (or specified) image pairs by object id
 *
 * @param integer $object_id   Object identifier
 * @param string  $object_type Object type
 * @param string  $pair_type   Pair type (main or additional)
 * @param array   $pair_ids    Array of pairs to delete
 *
 * @return bool
 */
function fn_delete_image_pairs($object_id, $object_type, $pair_type = '', $pair_ids = array())
{
    $cond = '';

    if ($pair_type  === 'A') {
        $cond .= db_quote('AND type = ?s', 'A');
    } elseif ($pair_type === 'M') {
        $cond .= db_quote('AND type = ?s', 'M');
    }

    // check if images belong to specified object
    if (!empty($pair_ids)) {
        $cond .= db_quote(' AND pair_id IN (?n)', $pair_ids);
    }

    $pair_ids = db_get_fields('SELECT pair_id FROM ?:images_links WHERE object_id = ?i AND object_type = ?s ?p', $object_id, $object_type, $cond);

    foreach ($pair_ids as $pair_id) {
        fn_delete_image_pair($pair_id, $object_type);
    }

    return true;
}

//
// Delete image pair
//
function fn_delete_image_pair($pair_id, $object_type = 'product')
{
    if (!empty($pair_id)) {
        $images = db_get_row("SELECT image_id, detailed_id, object_id, object_type FROM ?:images_links WHERE pair_id = ?i", $pair_id);
        if (!empty($images)) {
            fn_delete_image($images['image_id'], $pair_id, $object_type);
            fn_delete_image($images['detailed_id'], $pair_id, 'detailed');
        }

        fn_set_hook('delete_image_pair', $pair_id, $object_type, $images);

        return true;
    }

    return false;
}

/**
 * Delete all images pairs for object
 */
function fn_clean_image_pairs($object_id, $object_type)
{
    $pair_data = db_get_hash_array("SELECT pair_id, image_id, detailed_id, type FROM ?:images_links WHERE object_id = ?i AND object_type = ?s", 'pair_id', $object_id, $object_type);

    foreach ($pair_data as $pair_id => $p_data) {
        fn_delete_image_pair($pair_id, $object_type);
    }
}

//
// Clone image pairs
//
function fn_clone_image_pairs($target_object_id, $object_id, $object_type, $lang_code = CART_LANGUAGE)
{
    // Get all pairs
    $pair_data = db_get_hash_array(
        'SELECT pair_id, image_id, detailed_id, type, position FROM ?:images_links WHERE object_id = ?i AND object_type = ?s',
        'pair_id', $object_id, $object_type
    );

    if (empty($pair_data)) {
        return false;
    }

    $icons = $detailed = $pairs_data = array();

    foreach ($pair_data as $pair_id => $p_data) {
        if (!empty($p_data['image_id'])) {
            $icons[$pair_id] = fn_get_image($p_data['image_id'], $object_type, $lang_code, true);

            if (!empty($icons[$pair_id])) {
                $p_data['image_alt'] = empty($icons[$pair_id]['alt']) ? '' : $icons[$pair_id]['alt'];

                $tmp_name = fn_create_temp_file();
                Storage::instance('images')->export($icons[$pair_id]['relative_path'], $tmp_name);
                $name = fn_basename($icons[$pair_id]['image_path']);

                $icons[$pair_id] = array(
                    'path' => $tmp_name,
                    'size' => filesize($tmp_name),
                    'error' => 0,
                    'name' => $name,
                    'clone_from' => $p_data['image_id'],
                );
            }
        }
        if (!empty($p_data['detailed_id'])) {
            $detailed[$pair_id] = fn_get_image($p_data['detailed_id'], 'detailed', $lang_code, true);
            if (!empty($detailed[$pair_id])) {
                $p_data['detailed_alt'] = empty($detailed[$pair_id]['alt']) ? '' : $detailed[$pair_id]['alt'];

                $tmp_name = fn_create_temp_file();
                Storage::instance('images')->export($detailed[$pair_id]['relative_path'], $tmp_name);

                $name = fn_basename($detailed[$pair_id]['image_path']);

                $detailed[$pair_id] = array(
                    'path' => $tmp_name,
                    'size' => filesize($tmp_name),
                    'error' => 0,
                    'name' => $name,
                    'clone_from' => $p_data['detailed_id'],
                );
            }
        }

        $pairs_data = array(
            $pair_id => array(
                'type' => $p_data['type'],
                'image_alt' => (!empty($p_data['image_alt'])) ? $p_data['image_alt'] : '',
                'detailed_alt' => (!empty($p_data['detailed_alt'])) ? $p_data['detailed_alt'] : '',
                'position' => $p_data['position']
            )
        );

        fn_update_image_pairs($icons, $detailed, $pairs_data, $target_object_id, $object_type, array(), true, $lang_code, true);
    }
}

// ----------- Utility functions -----------------

/**
 * Resizes image
 * @param string $src source image path
 * @param integer $new_width new image width
 * @param integer $new_height new image height
 * @param string $bg_color new image background color
 * @param array $custom_settings custom convertion settings
 * @return array - new image contents and format
 */
function fn_resize_image($src, $new_width = 0, $new_height = 0, $bg_color = '#ffffff', $custom_settings = array())
{
    static $general_settings = array();
    if (empty($general_settings)) {
        $general_settings = Settings::instance()->getValues('Thumbnails');
    }

    gc_collect_cycles();

    $settings = empty($custom_settings) ? $general_settings : $custom_settings;

    /** @var \Imagine\Image\ImagineInterface|\Imagine\Image\AbstractImagine $imagine */
    $imagine = Tygh::$app['image'];

    $format = $settings['convert_to'];
    if ($format === 'original') {
        if ($original_file_type = fn_get_image_extension(fn_get_mime_content_type($src, true))) {
            $format = $original_file_type;
        } else {
            $format = 'png';
        }
    }

    $transparency = null;
    if (empty($bg_color)) {
        $bg_color = '#FFF';

        if ($format === 'png' || $format === 'gif' || $format === 'webp') {
            $transparency = 0;
        }
    } elseif (!preg_match('/^#([0-9a-f]{3}){1,2}$/i', $bg_color)) {
        $bg_color = '#FFF';
    }

    try {
        $imagine->setMetadataReader(new ExifMetadataReader());
        $exif_supported = true;
    } catch (\Imagine\Exception\NotSupportedException $e) {
        $exif_supported = false;
    }

    try {
        /** @var \Imagine\Image\AbstractImage $image */
        $image = $imagine->open($src);

        if ($exif_supported) {
            $metadata = $image->metadata()->toArray();

            if (isset($metadata['exif.Orientation'])) {
                $exif_orientation = (int) $metadata['exif.Orientation'];
            } elseif (isset($metadata['ifd0.Orientation'])) {
                $exif_orientation = (int) $metadata['ifd0.Orientation'];
            } else {
                $exif_orientation = null;
            }

            if (null !== $exif_orientation) {
                $rotation_angles = array(
                    3 => 180,
                    6 => 90,
                    8 => 270,
                );

                if (isset($rotation_angles[$exif_orientation])) {
                    $angle = $rotation_angles[$exif_orientation];

                    $image->rotate($angle);
                }
            }
        }

        list($new_width, $new_height) = ImageHelper::originalProportionsFallback(
            $image->getSize()->getWidth(), $image->getSize()->getHeight(), $new_width, $new_height
        );

        // This is a non-necessary operation
        // which can however trigger exceptions if isn't supported by a driver
        fn_catch_exception(function () use ($image) {
            $image->usePalette(new \Imagine\Image\Palette\RGB());
        });

        $filter = ($imagine instanceof \Imagine\Gd\Imagine)
            ? \Imagine\Image\ImageInterface::FILTER_UNDEFINED
            : \Imagine\Image\ImageInterface::FILTER_LANCZOS;

        $new_size = new \Imagine\Image\Box($new_width, $new_height);
        $thumbnail = $image->thumbnail(
            $new_size,
            \Imagine\Image\ImageInterface::THUMBNAIL_INSET,
            $filter
        );

        // In case that created thumbnail is smaller than required size, we create
        // an empty canvas of required size and center thumbnail on it
        $thumbnail_coordinates = new \Imagine\Image\Point(
            (int)(($new_size->getWidth() - $thumbnail->getSize()->getWidth()) / 2),
            (int)(($new_size->getHeight() - $thumbnail->getSize()->getHeight()) / 2)
        );

        if (!$image->palette()->supportsAlpha()) {
            $transparency = null;
        }
        $canvas_color = $image->palette()->color($bg_color, $transparency);

        $canvas = $imagine->create($new_size, $canvas_color);

        $canvas->paste($thumbnail, $thumbnail_coordinates);

        unset($thumbnail, $image);

        $thumbnail = $canvas;

        $options = array(
            'jpeg_quality' => isset($settings['jpeg_quality']) ? $settings['jpeg_quality'] : null,
            'png_compression_level' => 9,
            'filter' => $filter,
            'flatten' => true,
        );

        $return = array($thumbnail->get($format, $options), $format);

        unset($thumbnail);

        gc_collect_cycles();

        return $return;
    } catch (\Exception $e) {
        $error_message = __('error_unable_to_create_thumbnail', array(
            '[error]' => $e->getMessage(),
            '[file]' => $src
        ));

        if (AREA == 'A') {
            fn_set_notification('E', __('error'), $error_message);
        }

        gc_collect_cycles();

        return false;
    }
}

/**
 * @return array List of supported image formats to be used as setting variants
 */
function fn_get_supported_image_format_variants()
{
    $formats = array(
        'original' => __('same_as_source'),
    );

    $supported_formats = ImageHelper::getSupportedFormats();

    if (in_array('jpg', $supported_formats)) {
        $formats['jpg'] = 'JPEG';
    }
    if (in_array('png', $supported_formats)) {
        $formats['png'] = 'PNG';
    }
    if (in_array('gif', $supported_formats)) {
        $formats['gif'] = 'GIF';
    }
    if (in_array('webp', $supported_formats)) {
        $formats['webp'] = 'WEBP';
    }

    return $formats;
}

//
// Get $_dataimage extension by MIME type
//
function fn_get_image_extension($image_type)
{
    static $image_types = array (
        'image/gif' => 'gif',
        'image/pjpeg' => 'jpg',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/x-shockwave-flash' => 'swf',
        'image/psd' => 'psd',
        'image/bmp' => 'bmp',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
        'image/webp' => 'webp',
    );

    return isset($image_types[$image_type]) ? $image_types[$image_type] : false;
}

/**
 * Returns image width, height, mime type and local path to image
 *
 * @param string $file path to image
 * @return array array with width, height, mime type and path
 */
function fn_get_image_size($file)
{
    // File is url, get it and store in temporary directory
    if (strpos($file, '://') !== false) {
        $tmp = fn_create_temp_file();

        if (fn_put_contents($tmp, fn_get_contents($file)) == 0) {
            return false;
        }

        $file = $tmp;
    }

    $mime_type = fn_get_mime_content_type($file);

    if (in_array($mime_type, ['image/svg+xml', 'image/svg'])) {
        $xml_file = simplexml_load_file($file);
        $w = isset($xml_file['width']) ? (int) $xml_file['width'] : 0;
        $h = isset($xml_file['height']) ? (int) $xml_file['height'] : 0;
    } elseif (is_readable($file)) {
        $image_size_info = getimagesize($file);
        if ($image_size_info === false) {
            return false;
        }
        list($w, $h) = $image_size_info;
    } else {
        return false;
    }

    return [$w, $h, $mime_type, $file];
}

/**
 * Moves uploaded file from system tmp dir to sess_data directory (under custom_files)
 *
 * @param array $file File data
 *
 * @return array
 */
function fn_move_uploaded_file($file)
{
    if (!empty($file['path']) && is_uploaded_file($file['path'])) {
        $file_path = sprintf('sess_data/%s', \Tygh\Tools\SecurityHelper::sanitizeFileName(urldecode($file['name'])));
        list(, $file['path']) = Storage::instance('custom_files')->put($file_path, array(
            'file' => $file['path']
        ));

        if ($file['path'] === null) {
            $file = null;
        }
    }

    return $file;
}

/**
 * Returns image subdir
 * @param int $image_id Image id 
 * @return string
 */
function fn_get_image_subdir($image_id = 0)
{
    if (empty($image_id)) {
        $max_id = db_get_next_auto_increment_id('images');
        $img_id_subdir = floor($max_id / MAX_FILES_IN_DIR);
    } else {
        $img_id_subdir = floor($image_id / MAX_FILES_IN_DIR);
    }

    return $img_id_subdir;
}

/**
 * Attaches image pair to a specified object
 *
 * @param string $name        Name of image to search for inside global $_REQUEST array
 * @param string $object_type The type of object
 * @param int    $object_id   Object identifier
 * @param string $lang_code   Two-letters languag code
 * @param int[]  $object_ids  Array of object identifiers
 *
 * @return int[]
 */
function fn_attach_image_pairs($name, $object_type, $object_id = 0, $lang_code = CART_LANGUAGE, array $object_ids = [])
{
    // @TODO: get rid of direct $_REQUEST array usage inside this function and fn_filter_uploaded_data too
    $allowed_extensions = ImageHelper::getSupportedFormats($object_type);
    $icons = fn_filter_uploaded_data($name . '_image_icon', $allowed_extensions);
    $detailed = fn_filter_uploaded_data($name . '_image_detailed', $allowed_extensions);
    $pairs_data = !empty($_REQUEST[$name . '_image_data']) ? $_REQUEST[$name . '_image_data'] : [];

    return fn_update_image_pairs($icons, $detailed, $pairs_data, $object_id, $object_type, $object_ids, true, $lang_code);
}

/**
 * Generates thumbnail with given size from image
 *
 * @param string $image_path      Path to image
 * @param int    $width           Thumbnail width
 * @param int    $height          Thumbnail height
 * @param bool   $lazy            lazy generation - returns script URL that generates thumbnail
 * @param bool   $return_rel_path Return relative path
 * @param array  $image           An array of image object in the database, for which the thumbnail will be generated.
 * @param string $url             Input URL
 *
 * @return string path
 */
function fn_generate_thumbnail($image_path, $width, $height = 0, $lazy = false, $return_rel_path = false, array $image = [], $url = '')
{
    /**
     * Actions before thumbnail generate
     *
     * @param string $image_path Path to image
     * @param int    $width      Width of thumbnail
     * @param int    $height     Height of thumbnail
     * @param bool   $make_box   If true create rectangle border
     */
    fn_set_hook('generate_thumbnail_pre', $image_path, $width, $height, $make_box);

    if (empty($image_path)) {
        return '';
    }

    $filename = 'thumbnails/' . $width . (empty($height) ? '' : '/' . $height) . '/' . $image_path;

    if (
        Registry::get('settings.Thumbnails.convert_to') !== 'original'
        && Registry::get('config.tweaks.lazy_thumbnails') === $lazy
    ) {
        $filename .= '.' . Registry::get('settings.Thumbnails.convert_to');
    }

    $th_filename = '';

    if ($lazy || Storage::instance('images')->isExist($filename)) {
        $th_filename = $filename;

        if ($lazy) {
            // We should encode special characters that filename parts may contain,
            // because filename will be transmitted via HTTP GET parameter
            $th_filename = implode('/', array_map(
                'rawurlencode',
                explode('/', ltrim(
                    $th_filename, '/'
                ))
            ));
        }
    } else {

        // for lazy thumbnails: find original filename
        if (Registry::get('config.tweaks.lazy_thumbnails')
            && Registry::get('settings.Thumbnails.convert_to') != 'original'
            && !Storage::instance('images')->isExist($image_path)
        ){
            foreach (ImageHelper::getSupportedFormats() as $ext) {
                $image_path = preg_replace('/(\.' . $ext . ')$/', '', $image_path);
                if (Storage::instance('images')->isExist($image_path)) {
                    break;
                }
            }
        }

        /**
         * Actions before thumbnail generate, if thumbnail is not exists, after validations
         *
         * @param string $image_path Real path to image
         * @param string $lazy       Lazy generation - returns script URL that generates thumbnail
         * @param string $filename   Name of the image's file
         * @param int    $width      Width of thumbnail
         * @param int    $height     Height of thumbnail
         */
        fn_set_hook('generate_thumbnail_file_pre', $image_path, $lazy, $filename, $width, $height);

        list(, , ,$tmp_path) = fn_get_image_size(Storage::instance('images')->getAbsolutePath($image_path));

        if (!empty($tmp_path)) {
            list($cont, $format) = fn_resize_image($tmp_path, $width, $height, Registry::get('settings.Thumbnails.thumbnail_background_color'));

            if (!empty($cont)) {
                list(, $th_filename) = Storage::instance('images')->put($filename, array(
                    'contents' => $cont,
                    'caching' => true
                ));
            }
        }
    }

    /**
     * Actions after thumbnail generate
     *
     * @param string $th_filename Thumbnail path
     * @param string $lazy        Lazy generation - returns script URL that generates thumbnail
     * @param string $image_path  Path to image
     * @param int    $width       Width of thumbnail
     * @param int    $height      Height of thumbnail
     * @param array  $image       An array of image object in the database, for which the thumbnail was generated.
     */
    fn_set_hook('generate_thumbnail_post', $th_filename, $lazy, $image_path, $width, $height, $image);

    if (!$return_rel_path && $th_filename) {
        $th_filename = Storage::instance('images')->getUrl($th_filename, '', $url);
    }

    return !empty($th_filename) ? $th_filename : '';
}

/**
 * Generates thumbnail with given size from image
 *
 * @param array{image_x: int|float, image_y: int|float, image_path: string, alt: string, absolute_path: string, relative_path: string, icon: array{image_x: int|float, image_y: int|float, image_path: string, alt: string, absolute_path: string, relative_path: string}, detailed: array{image_x: int|float, image_y: int|float, image_path: string, alt: string, absolute_path: string, relative_path: string}} $images       Array with initial images
 * @param int|float                                                                                                                                                                                                                                                                                                                                                                                                $image_width  Result image width
 * @param int|float                                                                                                                                                                                                                                                                                                                                                                                                $image_height Result image height
 * @param string                                                                                                                                                                                                                                                                                                                                                                                                   $url          Input URL
 *
 * @return array<empty, empty>|array{image_path:string, detailed_image_path:string, alt:string, width:int, height:int, absolute_path:string, generate_image:bool, is_thumbnail:bool} Image data
 */
function fn_image_to_display($images, $image_width = 0, $image_height = 0, $url = '')
{
    if (empty($images)) {
        return [];
    }

    $image_data = [];

    // image pair passed
    if (!empty($images['icon']) || !empty($images['detailed'])) {
        if (!empty($images['icon'])) {
            $original_width = $images['icon']['image_x'];
            $original_height = $images['icon']['image_y'];
            $image_path = $images['icon']['image_path'];
            $absolute_path = $images['icon']['absolute_path'];
            $relative_path = $images['icon']['relative_path'];
        } else {
            $original_width = $images['detailed']['image_x'];
            $original_height = $images['detailed']['image_y'];
            $image_path = $images['detailed']['image_path'];
            $absolute_path = $images['detailed']['absolute_path'];
            $relative_path = $images['detailed']['relative_path'];
        }

        $detailed_image_path = !empty($images['detailed']['image_path']) ? $images['detailed']['image_path'] : '';
        $alt = !empty($images['icon']['alt']) ? $images['icon']['alt'] : (!empty($images['detailed']['alt']) ? $images['detailed']['alt'] : '');

    // single image passed only
    } else {
        $original_width = $images['image_x'];
        $original_height = $images['image_y'];
        $image_path = $images['image_path'];
        $alt = $images['alt'];
        $detailed_image_path = '';
        $absolute_path = $images['absolute_path'];
        $relative_path = $images['relative_path'];
    }

    $is_image_a_vector = in_array(fn_get_mime_content_type($absolute_path), ['image/svg+xml', 'image/svg']);

    list($image_width, $image_height) = ImageHelper::originalProportionsFallback(
        $original_width, $original_height, $image_width, $image_height
    );

    if (!empty($image_width) && !empty($relative_path) && !empty($absolute_path) && !$is_image_a_vector) {
        $image_path = fn_generate_thumbnail(
            $relative_path,
            $image_width,
            $image_height,
            Registry::get('config.tweaks.lazy_thumbnails'),
            false,
            $images,
            $url
        );
        $is_thumbnail = true;
    } else {
        $is_thumbnail = false;
        if (!$is_image_a_vector) {
            $image_width = $original_width;
            $image_height = $original_height;
        }
    }

    if (!empty($image_path)) {
        $image_data = [
            'image_path'          => $image_path,
            'detailed_image_path' => $detailed_image_path,
            'alt'                 => $alt,
            'width'               => $image_width,
            'height'              => $image_height,
            'absolute_path'       => $absolute_path,
            'generate_image'      => strpos($image_path, '&image_path=') !== false, // FIXME: dirty checking
            'is_thumbnail'        => $is_thumbnail
        ];
    }

    /**
     * Additionally processes image data
     *
     * @param array $image_data   Image data
     * @param array $images       Array with initial images
     * @param int   $image_width  Result image width
     * @param int   $image_height Result image height
     */
    fn_set_hook('image_to_display_post', $image_data, $images, $image_width, $image_height);

    return $image_data;
}

/**
 * Clears the request data of the image pairs.
 *
 * @param string $name Name of image to search for inside global $_REQUEST array.
 *
 * @return void
 */
function fn_clear_image_pairs_request_data($name = '')
{
    $names = [
        $name . '_image_data',
        'file_' . $name . '_image_icon',
        'type_' . $name . '_image_icon',
        'file_' . $name . '_image_detailed',
        'type_' . $name . '_image_detailed',
    ];

    foreach ($names as $item_name) {
        if (!isset($_REQUEST[$item_name])) {
            continue;
        }
        unset($_REQUEST[$item_name]);
    }
}
