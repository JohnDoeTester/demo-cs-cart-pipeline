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
use Tygh\Languages\Languages;
use Tygh\Storage;

defined('BOOTSTRAP') or die('Access denied');

//
// Copy product files
//
function fn_copy_product_files($file_id, $file, $product_id, $var_prefix = 'file')
{
    /**
     * Changes params before copying product files
     *
     * @param int    $file_id    File identifier
     * @param array  $file       File data
     * @param int    $product_id Product identifier
     * @param string $var_prefix Prefix of file variables
     */
    fn_set_hook('copy_product_files_pre', $file_id, $file, $product_id, $var_prefix);

    $filename = $product_id . '/' . $file['name'];

    $_data = array();

    list($_data[$var_prefix . '_size'], $_data[$var_prefix . '_path']) = Storage::instance('downloads')->put($filename, array(
        'file' => $file['path'],
        'overwrite' => true
    ));

    $_data[$var_prefix . '_path'] = fn_basename($_data[$var_prefix . '_path']);
    db_query('UPDATE ?:product_files SET ?u WHERE file_id = ?i', $_data, $file_id);

    /**
     * Adds additional actions after product files were copied
     *
     * @param int    $file_id    File identifier
     * @param array  $file       File data
     * @param int    $product_id Product identifier
     * @param string $var_prefix Prefix of file variables
     */
    fn_set_hook('copy_product_files_post', $file_id, $file, $product_id, $var_prefix);

    return true;
}

/**
 * Physically deletes product files on disk
 *
 * @param int $file_id file ID to delete
 * @return boolean true on success, false - otherwise
 */
function fn_delete_product_files_path($file_ids)
{
    if (!empty($file_ids) && is_array($file_ids)) {
        $files_data = db_get_array("SELECT file_path, preview_path, product_id FROM ?:product_files WHERE file_id IN (?n)", $file_ids);

        foreach ($files_data as $file_data) {
            if (!empty($file_data['file_path'])) {
                Storage::instance('downloads')->delete($file_data['product_id'] . '/' . $file_data['file_path']);
            }
            if (!empty($file_data['preview_path'])) {
                Storage::instance('downloads')->delete($file_data['product_id'] . '/' . $file_data['preview_path']);
            }

            // delete empty directory
            $files = Storage::instance('downloads')->getList($file_data['product_id']);
            if (empty($files)) {
                Storage::instance('downloads')->deleteDir($file_data['product_id']);
            }

        }

        return true;
    }

    return false;
}

/**
 * Delete product files in folder
 *
 * @param int $folder_id folder ID to delete
 * @param int $product_id product ID to delete all files from it. Ignored if $folder_id is passed
 * @return boolean true on success, false - otherwise
 */
function fn_delete_product_file_folders($folder_id, $product_id = 0)
{
    if (empty($product_id) && !empty($folder_id)) {
        $product_id = db_get_field('SELECT product_id FROM ?:product_file_folders WHERE folder_id = ?i', $folder_id);
    } elseif (empty($folder_id) && empty($product_id)) {
        return false;
    }

    if (!fn_company_products_check($product_id, true)) {
        return false;
    }

    if (!empty($folder_id)) {
        $folder_ids = [$folder_id];
        $file_ids = db_get_fields('SELECT file_id FROM ?:product_files WHERE product_id = ?i AND folder_id = ?i', $product_id, $folder_id);
    } else {
        $folder_ids = db_get_fields('SELECT folder_id FROM ?:product_file_folders WHERE product_id = ?i', $product_id);
        $file_ids = db_get_fields('SELECT file_id FROM ?:product_files WHERE product_id = ?i AND folder_id IN (?n)', $product_id, $folder_ids);
    }

    /**
     * Executes before product file folders are deleted, allows to check product folders and files before deletion
     *
     * @param array $folder_ids File folder identifiers
     * @param array $file_ids   File identifiers
     * @param int   $product_id Product identifier
     */
    fn_set_hook('delete_product_file_folders_before_delete', $folder_ids, $file_ids, $product_id);

    if (!empty($file_ids) && fn_delete_product_files_path($file_ids) == false) {
        return false;
    }

    db_query('DELETE FROM ?:product_file_folders WHERE folder_id IN (?n)', $folder_ids);
    db_query('DELETE FROM ?:product_file_folder_descriptions WHERE folder_id IN (?n)', $folder_ids);

    db_query('DELETE FROM ?:product_files WHERE file_id IN (?n)', $file_ids);
    db_query('DELETE FROM ?:product_file_descriptions WHERE file_id IN (?n)', $file_ids);

    /**
     * Executed after a file of a downloadable product file folder is deleted. The hook allows to perform additional actions.
     *
     * @param array $folder_ids File folder identifiers
     * @param int   $product_id Product identifier
     * @param array $file_ids   File identifiers
     */
    fn_set_hook('delete_product_file_folders_post', $folder_ids, $product_id, $file_ids);

    return true;
}

/**
 * Delete product files
 *
 * @param int $file_id    File ID to delete
 * @param int $product_id Product ID to delete all files from it. Ignored if $file_id is passed
 *
 * @return boolean true on success, false - otherwise
 */
function fn_delete_product_files($file_id, $product_id = 0)
{
    if (empty($product_id) && !empty($file_id)) {
        $product_id = db_get_field('SELECT product_id FROM ?:product_files WHERE file_id = ?i', $file_id);
    } elseif (empty($folder_id) && empty($product_id)) {
        return false;
    }

    if (!fn_company_products_check($product_id, true)) {
        return false;
    }

    if (!empty($file_id)) {
        $file_ids = [$file_id];
    } else {
        $file_ids = db_get_fields('SELECT file_id FROM ?:product_files WHERE product_id = ?i', $product_id);
    }

    /**
     * Executes before product files are deleted, allows to check product files before deletion
     *
     * @param array $file_ids   File identifiers
     * @param int   $product_id Product identifier
     */
    fn_set_hook('delete_product_files_before_delete', $file_ids, $product_id);

    if (fn_delete_product_files_path($file_ids) == false) {
        return false;
    }

    db_query('DELETE FROM ?:product_files WHERE file_id IN (?n)', $file_ids);
    db_query('DELETE FROM ?:product_file_descriptions WHERE file_id IN (?n)', $file_ids);

    /**
     * Executed after a file of a downloadable product is deleted. The hook allows to perform additional actions.
     *
     * @param array $file_ids   File identifiers
     * @param int   $product_id Product identifier
     */
    fn_set_hook('delete_product_files_post', $file_ids, $product_id);

    return true;
}

/**
 * Update product folder
 *
 * @param array $product_file_fodler folder data
 * @param int $folder_id folder ID for update, if empty - new folder will be created
 * @param string $lang_code language code to update folder description
 * @return int folder ID
 */

function fn_update_product_file_folder($product_file_folder, $folder_id, $lang_code = DESCR_SL)
{
    if (!fn_company_products_check($product_file_folder['product_id'], true)) {
        return false;
    }

    if ($folder_id && !empty($product_file_folder['product_id'])) {
        list($previous_folder,) = fn_get_product_file_folders([
            'folder_ids' => $folder_id,
            'product_id' => $product_file_folder['product_id']
        ]);
        if (!$previous_folder) {
            return false;
        }
    }

    /**
     * Executes before product file folder is updated, allows to change product file folder data
     *
     * @param array   $product_file_folder File folder data
     * @param int     $folder_id           File folder identifier
     * @param string  $lang_code           Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('update_product_file_folder_pre', $product_file_folder, $folder_id, $lang_code);

    if (empty($folder_id)) {

        $product_file_folder['folder_id'] = $folder_id = db_query('INSERT INTO ?:product_file_folders ?e', $product_file_folder);

        foreach (Languages::getAll() as $product_file_folder['lang_code'] => $v) {
            db_query('INSERT INTO ?:product_file_folder_descriptions ?e', $product_file_folder);
        }

    } else {
        db_query('UPDATE ?:product_file_folders SET ?u WHERE folder_id = ?i', $product_file_folder, $folder_id);
        db_query('UPDATE ?:product_file_folder_descriptions SET ?u WHERE folder_id = ?i AND lang_code = ?s', $product_file_folder, $folder_id, $lang_code);
    }

    /**
     * Executed after a file folder of a downloadable product is updated.
     * The hook allows to perform additional actions.
     *
     * @param array     $product_file_folder   File folder data
     * @param int       $folder_id             File folder identifier
     * @param string    $lang_code             Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('update_product_file_folder_post', $product_file_folder, $folder_id, $lang_code);

    return $folder_id;
}

/**
 * Update product file
 *
 * @param array     $product_file   File data
 * @param int       $file_id        File identifier for update, if empty - new file will be created
 * @param string    $lang_code      Language code to update file description
 *
 * @return boolean|int File identifier on success, otherwise false
 */
function fn_update_product_file($product_file, $file_id, $lang_code = DESCR_SL)
{
    /**
     * Executes before product file is updated, allows to change product file data
     *
     * @param array   $product_data File data
     * @param int     $product_id   File identifier
     * @param string  $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('update_product_file_pre', $product_file, $file_id, $lang_code);

    if (!fn_company_products_check($product_file['product_id'], true)) {
        return false;
    }

    if ($file_id && !empty($product_file['product_id'])) {
        list($previous_file,) = fn_get_product_files([
            'file_ids'   => $file_id,
            'product_id' => $product_file['product_id'],
        ]);
        if (!$previous_file) {
            return false;
        }
    }

    $uploaded_data = fn_filter_uploaded_data('base_file');
    $uploaded_preview_data = fn_filter_uploaded_data('file_preview');

    $delete_preview = (isset($product_file['delete_preview']) && $product_file['delete_preview'] == 'Y');

    if (!empty($file_id) || !empty($uploaded_data[$file_id])) {

        db_query("UPDATE ?:products SET is_edp = 'Y' WHERE product_id = ?i", $product_file['product_id']);

        if (!empty($uploaded_data[$file_id])) {
            $product_file['file_name'] = empty($product_file['file_name'])
                ? $uploaded_data[$file_id]['name']
                : $product_file['file_name'];
        }

        // Remove old file before uploading a new one
        if (!empty($file_id)) {
            $dir = $product_file['product_id'];
            $old_file = db_get_row(
                'SELECT file_path, preview_path FROM ?:product_files WHERE product_id = ?i AND file_id = ?i',
                $product_file['product_id'], $file_id
            );

            if (!empty($uploaded_data) && !empty($old_file['file_path'])) {
                Storage::instance('downloads')->delete($dir . '/' . $old_file['file_path']);
            }

            // Delete preview file if deletion is forced or new preview is uploaded
            if ($delete_preview
                ||
                (!empty($uploaded_preview_data) && !empty($old_file['preview_path']))
            ) {
                Storage::instance('downloads')->delete($dir . '/' . $old_file['preview_path']);
            }
        }

        if ($delete_preview) {
            $product_file['preview_path'] = '';
        }

        // Update file data
        if (empty($file_id)) {
            $product_file['file_id'] = $file_id = db_query('INSERT INTO ?:product_files ?e', $product_file);

            foreach (Languages::getAll() as $product_file['lang_code'] => $v) {
                db_query('INSERT INTO ?:product_file_descriptions ?e', $product_file);
            }

            $uploaded_id = 0;
        } else {

            db_query('UPDATE ?:product_files SET ?u WHERE file_id = ?i', $product_file, $file_id);
            db_query('UPDATE ?:product_file_descriptions SET ?u WHERE file_id = ?i AND lang_code = ?s', $product_file, $file_id, $lang_code);

            $uploaded_id = $file_id;
        }

        // Copy base file
        if (!empty($uploaded_data[$uploaded_id])) {
            fn_copy_product_files($file_id, $uploaded_data[$uploaded_id], $product_file['product_id']);
        }

        // Copy preview file
        if (!$delete_preview && !empty($uploaded_preview_data[$uploaded_id])) {
            fn_copy_product_files($file_id, $uploaded_preview_data[$uploaded_id], $product_file['product_id'], 'preview');
        }
    }

    /**
     * Executed after a file of a downloadable product is added or updated.
     * The hook allows to perform additional actions.
     *
     * @param array     $product_file   File data
     * @param int       $file_id        File identifier
     * @param string    $lang_code      Language code to update file description
     *
     */
    fn_set_hook('update_product_file_post', $product_file, $file_id, $lang_code);

    return $file_id;
}

/**
 * Clone product folders
 *
 * @param int $source_id source product ID
 * @param int $target_id target product ID
 *
 * @return array Associative array with the old folder IDs as keys and the new folder IDs as values
 */
function fn_clone_product_file_folders($source_id, $target_id)
{
    $data = db_get_array("SELECT * FROM ?:product_file_folders WHERE product_id = ?i", $source_id);
    $new_folder_ids = array();
    if (!empty($data)) {
        foreach ($data as $v) {
            $folder_descr = db_get_array("SELECT * FROM ?:product_file_folder_descriptions WHERE folder_id = ?i", $v['folder_id']);

            $v['product_id'] = $target_id;
            $old_folder_id = $v['folder_id'];
            unset($v['folder_id']);

            $new_folder_ids[$old_folder_id] = $new_folder_id = db_query("INSERT INTO ?:product_file_folders ?e", $v);

            foreach ($folder_descr as $key => $descr) {
                $descr['folder_id'] = $new_folder_id;
                db_query("INSERT INTO ?:product_file_folder_descriptions ?e", $descr);
            }
        }
    }

    return $new_folder_ids;
}

/**
 * Clone product files
 *
 * @param int $source_id source product ID
 * @param int $target_id target product ID
 *
 * @return boolean true on success, false - otherwise
 */
function fn_clone_product_files($source_id, $target_id)
{
    $data = db_get_array("SELECT * FROM ?:product_files WHERE product_id = ?i", $source_id);

    $new_folder_ids = fn_clone_product_file_folders($source_id, $target_id);

    if (!empty($data)) {
        foreach ($data as $v) {
            $file_descr = db_get_array("SELECT * FROM ?:product_file_descriptions WHERE file_id = ?i", $v['file_id']);

            $v['product_id'] = $target_id;
            unset($v['file_id']);

            // set new folder id
            if (!empty($v['folder_id'])) {
                $v['folder_id'] = $new_folder_ids[$v['folder_id']];
            }

            $new_file_id = db_query("INSERT INTO ?:product_files ?e", $v);

            foreach ($file_descr as $key => $descr) {
                $descr['file_id'] = $new_file_id;
                db_query("INSERT INTO ?:product_file_descriptions ?e", $descr);
            }

        }

        Storage::instance('downloads')->copy($source_id, $target_id);

        return true;
    }

    return false;
}

/**
 * Download product file
 *
 * @param int     $file_id    file ID
 * @param boolean $is_preview flag indicates that we download file itself or just preview
 * @param string  $ekey       temporary key to download file from customer area
 * @param string  $area       current working area
 *
 * @return bool file starts to download on success, boolean false in case of fail
 */
function fn_get_product_file($file_id, $is_preview = false, $ekey = '', $area = AREA)
{
    if (!empty($file_id)) {
        $column = $is_preview ? 'preview_path' : 'file_path';
        $file_data = db_get_row("SELECT $column, product_id FROM ?:product_files WHERE file_id = ?i", $file_id);

        if (fn_allowed_for('MULTIVENDOR') && $area == 'A' && !fn_company_products_check($file_data['product_id'], true)) {
            return false;
        }

        if (!empty($ekey)) {

            $ekey_info = fn_get_product_edp_info($file_data['product_id'], $ekey);

            if (empty($ekey_info) || $ekey_info['file_id'] != $file_id) {
                return false;
            }

            // Increase downloads for this file
            $max_downloads = (int) db_get_field("SELECT max_downloads FROM ?:product_files WHERE file_id = ?i", $file_id);
            $file_downloads = (int) db_get_field("SELECT downloads FROM ?:product_file_ekeys WHERE ekey = ?s AND file_id = ?i", $ekey, $file_id);

            if (!empty($max_downloads)) {
                if ($file_downloads >= $max_downloads) {
                    return false;
                }
            }

            db_query('UPDATE ?:product_file_ekeys SET ?u WHERE file_id = ?i AND product_id = ?i AND order_id = ?i', array('downloads' => $file_downloads + 1), $file_id, $file_data['product_id'], $ekey_info['order_id']);
        }

        Storage::instance('downloads')->get($file_data['product_id'] . '/' . $file_data[$column]);
    }

    return false;
}

/**
 * Returns product folders
 *
 * @param array  $params
 *        int product_id     - ID of product
 *        string folder_ids  - get folders by ids
 *        string order_by
 * @param string $lang_code
 *
 * @return array folders, params
 */
function fn_get_product_file_folders($params, $lang_code = DESCR_SL)
{
    $params['product_id'] = !empty($params['product_id'])? $params['product_id'] : 0;
    $fields = [
        'SUM(?:product_files.file_size) as folder_size',
        '?:product_file_folders.*',
        '?:product_file_folder_descriptions.folder_name',
    ];
    $default_params = [
        'product_id' => 0,
        'folder_ids' => '',
        'order_by'   => 'position, folder_name',
    ];
    $params = array_merge($default_params, $params);

    $join = db_quote(
        ' LEFT JOIN ?:product_files ON ?:product_file_folders.folder_id = ?:product_files.folder_id'
        . ' LEFT JOIN ?:product_file_folder_descriptions ON ?:product_file_folder_descriptions.folder_id = ?:product_file_folders.folder_id AND ?:product_file_folder_descriptions.lang_code = ?s',
        $lang_code
    );
    $order = $params['order_by'];

    $condition = '';
    if (!empty($params['folder_ids'])) {
        $condition .= db_quote(' AND ?:product_file_folders.folder_id IN (?n)', $params['folder_ids']);
    }

    if (!empty($params['product_id'])) {
        $condition .= db_quote(' AND ?:product_file_folders.product_id = ?i', $params['product_id']);
    }

    if (AREA == 'C') {
        $condition .= db_quote(' AND ?:product_file_folders.status = ?s', ObjectStatuses::ACTIVE);
    }

    $folders = db_get_array(
        'SELECT ?p FROM ?:product_file_folders ?p WHERE 1 = 1 ?p GROUP BY folder_id ORDER BY ?p',
        implode(', ', $fields),
        $join,
        $condition,
        $order
    );

    return array($folders, $params);
}

/**
 * Returns product files
 *
 * @param array $params
 *        int product_id     - ID of product
 *        bool preview_check - get files only with preview
 *        int order_id       - get order ekeys for the files
 *        string file_ids    - get files by ids
 *
 * @return array files, params
 */
function fn_get_product_files($params, $lang_code = DESCR_SL)
{
    $default_params = [
        'product_id'    => 0,
        'preview_check' => false,
        'order_id'      => 0,
        'file_ids'      => '',
    ];
    $params = array_merge($default_params, $params);

    /**
     * Change parameters for getting product files
     *
     * @param array  $params
     * @param string $lang_code 2-letters language code
     */
    fn_set_hook('get_product_files_pre', $params, $lang_code);

    $sortings = [
        'position' => [
            '?:product_files.position',
            '?:product_file_descriptions.file_name',
        ]
    ];

    $fields = [
        '?:product_files.*',
        '?:product_file_descriptions.file_name',
        '?:product_file_descriptions.license',
        '?:product_file_descriptions.readme'
    ];

    $join = db_quote(' LEFT JOIN ?:product_file_descriptions ON ?:product_file_descriptions.file_id = ?:product_files.file_id AND ?:product_file_descriptions.lang_code = ?s', $lang_code);

    if (!empty($params['order_id'])) {
        $fields[] = '?:product_file_ekeys.active';
        $fields[] = '?:product_file_ekeys.downloads';
        $fields[] = '?:product_file_ekeys.ekey';

        $join .= db_quote(' LEFT JOIN ?:product_file_ekeys ON ?:product_file_ekeys.file_id = ?:product_files.file_id AND ?:product_file_ekeys.order_id = ?i', $params['order_id']);
        $join .= (AREA == 'C') ? " AND ?:product_file_ekeys.active = 'Y'" : '';
    }

    $condition = '';
    if (!empty($params['file_ids'])) {
        $condition .= db_quote(' AND ?:product_files.file_id IN (?n)', $params['file_ids']);
    }

    if (!empty($params['product_id'])) {
        $condition .= db_quote(' AND ?:product_files.product_id = ?i', $params['product_id']);
    }

    if ($params['preview_check'] == true) {
        $condition .= " AND preview_path != ''";
    }

    if (AREA == 'C') {
        $condition .= db_quote(' AND ?:product_files.status = ?s', ObjectStatuses::ACTIVE);
    }

    /**
     * Change SQL parameters for product files selection
     *
     * @param array  $params
     * @param array  $fields    List of fields for retrieving
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param array  $sortings  Sorting fields
     * @param string $lang_code Language code
     */
    fn_set_hook('get_product_files_before_select', $params, $fields, $join, $condition, $sortings, $lang_code);

    $sorting = db_sort($params, $sortings, 'position', 'asc');

    $files = db_get_array(
        'SELECT ?p FROM ?:product_files ?p'
        . ' WHERE 1=1 ?p ?p',
        implode(', ', $fields),
        $join,
        $condition,
        $sorting
    );

    if (!empty($files)) {
        foreach ($files as $k => $file) {
            if (!empty($file['license']) && $file['agreement'] == 'Y') {
                $files[$k]['agreements'] = [$file];
            }
            if (!empty($file['product_id']) && !empty($file['ekey'])) {
                $files[$k]['edp_info'] = fn_get_product_edp_info($file['product_id'], $file['ekey']);
            }
        }
    }

    /**
     * Change product files
     *
     * @param array $params
     * @param array $files  Product files
     */
    fn_set_hook('get_product_files_post', $params, $files);

    return [$files, $params];
}

/**
 * Returns product folders and files merged and presented as a tree
 *
 * @param array  $folders Product folders
 * @param array  $files Product files
 * @return array tree
 */
function fn_build_files_tree($folders, $files)
{
    $tree = array();
    $folders = !empty($folders)? $folders : array();
    $files = !empty($files)? $files : array();

    if (is_array($folders) && is_array($files)) {

        foreach ($folders as $v_folder) {
            $subfiles = array();
            foreach ($files as $v_file) {
                if ($v_file['folder_id'] == $v_folder['folder_id']) {
                    $subfiles[] = $v_file;
                }
            }

            $v_folder['files'] = $subfiles;
            $tree['folders'][] = $v_folder;
        }

        foreach ($files as $v_file) {
            if (empty($v_file['folder_id'])) {
                $tree['files'][] = $v_file;
            }
        }

    }

    return $tree;
}

/**
 * Returns EDP ekey info
 *
 * @param int $product_id Product identifier
 * @param string $ekey Download key
 * @return array Download key info
 */
function fn_get_product_edp_info($product_id, $ekey)
{
    /**
     * Prepare params before getting EDP information
     *
     * @param int    $product_id Product identifier
     * @param string $ekey       Download key
     */
    fn_set_hook('get_product_edp_info_pre', $product_id, $ekey);

    $unlimited = db_get_field("SELECT unlimited_download FROM ?:products WHERE product_id = ?i", $product_id);
    $ttl_condition = ($unlimited == 'Y') ? '' :  db_quote(" AND ttl > ?i", TIME);

    $edp_info = db_get_row(
        "SELECT product_id, order_id, file_id "
        . "FROM ?:product_file_ekeys "
        . "WHERE product_id = ?i AND active = 'Y' AND ekey = ?s ?p",
        $product_id, $ekey, $ttl_condition
    );

    /**
     * Change product edp info
     *
     * @param array  $edp_info   EDP information
     * @param int    $product_id Product identifier
     * @param string $ekey       Download key
     */
    fn_set_hook('get_product_edp_info_post', $product_id, $ekey, $edp_info);

    return $edp_info;
}

/**
 * Gets EDP agreemetns
 *
 * @param int $product_id Product identifier
 * @param bool $file_name If true get file name in info, false otherwise
 * @return array EDP agreements data
 */
function fn_get_edp_agreements($product_id, $file_name = false)
{
    /**
     * Actions before getting edp agreements
     *
     * @param int  $product_id Product identifier
     * @param bool $file_name  Get file name
     */
    fn_set_hook('get_edp_agreements_pre', $product_id, $file_name);

    $join = '';
    $fields = array(
        '?:product_files.file_id',
        '?:product_files.agreement',
        '?:product_file_descriptions.license'
    );

    if ($file_name == true) {
        $join .= db_quote(" LEFT JOIN ?:product_file_descriptions ON ?:product_file_descriptions.file_id = ?:product_files.file_id AND product_file_descriptions.lang_code = ?s", CART_LANGUAGE);
        $fields[] = '?:product_file_descriptions.file_name';
    }

    /**
     * Prepare params before getting edp agreements
     *
     * @param int    $product_id Product identifier
     * @param string $join       Query join; it is treated as a JOIN clause
     * @param array  $fields     Array of table column names to be returned
     */
    fn_set_hook('get_edp_agreements_before_get_agriments', $product_id, $fields, $join);

    $edp_agreements = db_get_array("SELECT " . implode(', ', $fields) . " FROM ?:product_files INNER JOIN ?:product_file_descriptions ON ?:product_file_descriptions.file_id = ?:product_files.file_id AND ?:product_file_descriptions.lang_code = ?s WHERE ?:product_files.product_id = ?i AND ?:product_file_descriptions.license != '' AND ?:product_files.agreement = 'Y'", CART_LANGUAGE, $product_id);

    /**
     * Actions after getting edp agreements
     *
     * @param int   $product_id     Product identifier
     * @param bool  $file_name      If true get file name in info, false otherwise
     * @param array $edp_agreements EDP agreements data
     */
    fn_set_hook('get_edp_agreements_post', $product_id, $file_name, $edp_agreements);

    return $edp_agreements;
}
