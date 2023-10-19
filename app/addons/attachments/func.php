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

use Tygh\Storage;
use Tygh\Languages\Languages;
use Tygh\Registry;
use Tygh\Enum\YesNo;
use Tygh\Enum\ObjectStatuses;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_get_attachments($object_type, $object_id, $type = 'M', $lang_code = CART_LANGUAGE)
{
    /**
     *  Executes at the beginning of the function, allowing you to modify the arguments passed to the function.
     *
     * @param string $object_type Object type
     * @param string $object_id   Object identifier
     * @param string $type        Attachment type
     * @param string $lang_code   Language code
     */
    fn_set_hook('get_attachments_pre', $object_type, $object_id, $type, $lang_code);

    $condition = '';
    if (AREA != 'A') {
        $auth = Tygh::$app['session']['auth'];
        $condition = db_quote(
            ' AND (?p) AND status = ?s',
            fn_find_array_in_set($auth['usergroup_ids'], 'usergroup_ids', true),
            ObjectStatuses::ACTIVE
        );
    }

    return db_get_array(
        'SELECT ?:attachments.*, ?:attachment_descriptions.description FROM ?:attachments '
        . 'LEFT JOIN ?:attachment_descriptions'
            . ' ON ?:attachments.attachment_id = ?:attachment_descriptions.attachment_id AND lang_code = ?s'
        . ' WHERE object_type = ?s AND object_id = ?i AND type = ?s ?p'
        . ' ORDER BY position',
        $lang_code,
        $object_type,
        $object_id,
        $type,
        $condition
    );
}

/**
 * Updates or create attachment to object
 *
 * @param array<string, array<string|int>> $attachment_data Attachment data
 * @param int                              $attachment_id   Attachment identifier
 * @param string                           $object_type     Object type
 * @param int                              $object_id       Object identifier
 * @param string                           $type            Attachment type
 * @param array<string, string>            $files           Files
 * @param string                           $lang_code       Two-letter language code
 *
 * @return int
 */
function fn_update_attachments(array $attachment_data, $attachment_id, $object_type, $object_id, $type = 'M', array $files = [], $lang_code = DESCR_SL)
{
    $uploaded_files = [];
    $object_id = intval($object_id);
    $directory = $object_type . '/' . $object_id;

    if (!empty($files)) {
        $uploaded_data = $files;
    } else {
        $uploaded_data = fn_filter_uploaded_data('attachment_files');
        $uploaded_data = reset($uploaded_data);
    }

    if (!empty($attachment_id)) {
        $data = [
            /** @var array{usergroup_ids: array<int>} $attachment_data */
            'usergroup_ids' => empty($attachment_data['usergroup_ids']) ? '0' : implode(',', $attachment_data['usergroup_ids']),
            'position'      => $attachment_data['position']
        ];

        db_query('UPDATE ?:attachment_descriptions SET description = ?s WHERE attachment_id = ?i AND lang_code = ?s', $attachment_data['description'], $attachment_id, $lang_code);
        db_query('UPDATE ?:attachments SET ?u WHERE attachment_id = ?i AND object_type = ?s AND object_id = ?i AND type = ?s', $data, $attachment_id, $object_type, $object_id, $type);

        /**
         * Executes after attachment file was updated. Allows to do additional actions.
         *
         * @param array  $attachment_data Data of the attachment
         * @param int    $attachment_id   Attachment identifier
         * @param string $object_type     Object type
         * @param int    $object_id       Object identifier
         * @param string $type            Attachment type
         * @param array  $files           Attachment files
         * @param string $lang_code       2 letter language code
         * @param array  $uploaded_data   Uploaded data
         */
        fn_set_hook('attachment_update_file', $attachment_data, $attachment_id, $object_type, $object_id, $type, $files, $lang_code, $uploaded_data);
    } elseif (!empty($uploaded_data)) {
        $attachment_data['type'] = $type;

        /** @var array{type: string, usergroup_ids: array<int>, position:int, description:string} $attachment_data */
        $data = [
            'object_type'   => $object_type,
            'object_id'     => $object_id,
            'usergroup_ids' => empty($attachment_data['usergroup_ids']) ? '0' : implode(',', $attachment_data['usergroup_ids']),
            'position'      => $attachment_data['position']
        ];

        $data = array_merge($data, $attachment_data);

        $attachment_id = db_query('INSERT INTO ?:attachments ?e', $data);

        if ($attachment_id) {
            $all_languages = Languages::getAll();
            foreach ($all_languages as $lang_code => $v) {
                if (is_array($attachment_data['description'])) {
                    $description = isset($attachment_data['description'][$lang_code]) ? $attachment_data['description'][$lang_code] : reset($attachment_data['description']);
                } else {
                    $description = $attachment_data['description'];
                }

                $description_data = [
                    'attachment_id' => $attachment_id,
                    'lang_code' => $lang_code,
                    'description' => $description,
                ];

                db_query('INSERT INTO ?:attachment_descriptions ?e', $description_data);
            }
        }

        /**
         * Executes after new file was added. Allows to do additional actions.
         *
         * @param array  $attachment_data Data of the atttachment
         * @param string $object_type     Object type
         * @param int    $object_id       Object identifier
         * @param string $type            Attachment type
         * @param array  $files           Attachment files
         * @param int    $attachment_id   Attachment identifier
         * @param array  $uploaded_data   Uploaded data
         */
        fn_set_hook('attachment_add_file', $attachment_data, $object_type, $object_id, $type, $files, $attachment_id, $uploaded_data);
    }

    if ($attachment_id) {
        $uploaded_files[$attachment_id] = $uploaded_data;
    }

    if (
        empty($attachment_id)
        || empty($uploaded_files[$attachment_id])
        || empty($uploaded_files[$attachment_id]['size'])
    ) {
        return $attachment_id;
    }

    $old_filename = db_get_row('SELECT filename, on_server FROM ?:attachments WHERE attachment_id = ?i', $attachment_id);

    if (YesNo::toBool($old_filename['on_server']) && !empty($old_filename['filename'])) {
        Storage::instance('attachments')->delete($directory . '/' . $old_filename['filename']);
    }

    $filename = $uploaded_files[$attachment_id]['name'];
    $filepath = $directory . '/' . $filename;

    if (empty($uploaded_files[$attachment_id]['url']) || YesNo::toBool(Registry::get('addons.attachments.allow_save_attachments_to_server'))) {
        list($filesize, $new_filename) = Storage::instance('attachments')->put($filepath, [
            'file' => $uploaded_files[$attachment_id]['path']
        ]);
    } else {
        $filesize = $uploaded_files[$attachment_id]['size'];
        $new_filename = $filename;
    }

    $update_data = [
        'filesize'  => $filesize,
        'on_server' => YesNo::YES,
        'filename'  => fn_basename($new_filename),
        'url'       => '',
    ];

    if (!empty($uploaded_files[$attachment_id]['url']) && !YesNo::toBool(Registry::get('addons.attachments.allow_save_attachments_to_server'))) {
        $update_data['on_server'] = YesNo::NO;
        $update_data['url'] = $uploaded_files[$attachment_id]['url'];
    }

    if (!empty($update_data['filesize'])) {
        db_query('UPDATE ?:attachments SET ?u WHERE attachment_id = ?i', $update_data, $attachment_id);
    }

    return $attachment_id;
}

function fn_delete_attachments($attachment_ids, $object_type, $object_id)
{
    fn_set_hook('attachment_delete_file', $attachment_ids, $object_type, $object_id);

    $data = db_get_array("SELECT * FROM ?:attachments WHERE attachment_id IN (?n) AND object_type = ?s AND object_id = ?i", $attachment_ids, $object_type, $object_id);

    foreach ($data as $entry) {
        Storage::instance('attachments')->delete($entry['object_type'] . '/' . $object_id . '/' . $entry['filename']);
    }

    db_query("DELETE FROM ?:attachments WHERE attachment_id IN (?n) AND object_type = ?s AND object_id = ?i", $attachment_ids, $object_type, $object_id);
    db_query("DELETE FROM ?:attachment_descriptions WHERE attachment_id IN (?n)", $attachment_ids);

    return true;
}

/**
 * Deletes file attachment by object ID
 *
 * @param string $object_type Object type
 * @param string $object_id   Object identifier
 *
 * @return void
 */
function fn_attachments_delete_by_object_id($object_type, $object_id)
{
    /**
     *  Allows to perform additional actions before deleting attachments files.
     *
     * @param string $object_type Object type
     * @param string $object_id   Object identifier
     */
    fn_set_hook('attachment_delete_file_by_object_id_pre', $object_type, $object_id);

    $attachment_list = db_get_hash_array(
        'SELECT * FROM ?:attachments WHERE object_type = ?s AND object_id = ?i',
        'attachment_id',
        $object_type,
        $object_id
    );

    foreach ($attachment_list as $attachment_id => $attachment) {
        Storage::instance('attachments')->delete($attachment['object_type'] . '/' . $object_id . '/' . $attachment['filename']);
    }

    db_query('DELETE FROM ?:attachments WHERE object_type = ?s AND object_id = ?i', $object_type, $object_id);
    db_query('DELETE FROM ?:attachment_descriptions WHERE attachment_id IN (?n)', array_keys($attachment_list));
}

/**
 * Get file attachment and send it to the output stream
 *
 * @param int $attachment_id ID of attachment file
 *
 * @return boolean false if attachment could not be obtained
 */
function fn_get_attachment($attachment_id)
{
    $auth = Tygh::$app['session']['auth'];

    $condition = '';
    if (AREA != 'A') {
        $condition = ' AND (' . fn_find_array_in_set($auth['usergroup_ids'], 'usergroup_ids', true) . ") AND status = 'A'";
    }

    $data = db_get_row('SELECT * FROM ?:attachments WHERE attachment_id = ?i ?p', $attachment_id, $condition);

    fn_set_hook('attachments_get_attachment', $data, $attachment_id);

    if (empty($data)) {
        return false;
    }

    if (YesNo::toBool($data['on_server'])) {
        $attachment_storage = Storage::instance('attachments');
        $attachment_filename = $data['object_type'] . '/' . $data['object_id'] . '/' . $data['filename'];

        if (!$attachment_storage->isExist($attachment_filename)) {
            return false;
        }

        $attachment_storage->get($attachment_filename);
        exit;
    }

    if (empty($data['url'])) {
        return false;
    }

    fn_redirect($data['url'], true);
    exit;
}

/**
 * Function clone product's attachments
 *
 * @param int $product_id old product id
 * @param int $pid new product id
 */
function fn_attachments_clone_product(&$product_id, &$pid)
{
    $add_data = array();
    $attachments = db_get_array("SELECT * FROM ?:attachments WHERE object_type = 'product' AND object_id = ?i", $product_id);

    foreach ($attachments as &$attachment) {
        $attachment_descriptions = db_get_array("SELECT * FROM ?:attachment_descriptions WHERE attachment_id = ?i", $attachment['attachment_id']);

        $attachment['attachment_id'] = 0;
        $attachment['object_id'] = $pid;

        $attachment_id = db_query("INSERT INTO ?:attachments ?e", $attachment);

        Storage::instance('attachments')->copy('product/' . $product_id, 'product/' . $pid);

        foreach ($attachment_descriptions as $descr) {
            $descr['attachment_id'] = $attachment_id;
            db_query("INSERT INTO ?:attachment_descriptions ?e", $descr);
        }
    }
}

/**
 * Function delete product's attachments
 *
 * @param int $product_id product id
 */
function fn_attachments_delete_product_post(&$product_id)
{
    $attachments = db_get_fields("SELECT attachment_id FROM ?:attachments WHERE object_type = 'product' AND object_id = ?i", $product_id);

    Storage::instance('attachments')->deleteDir('product/' . $product_id);

    foreach ($attachments as $attachment_id) {
        db_query("DELETE FROM ?:attachments WHERE attachment_id = ?i", $attachment_id);
        db_query("DELETE FROM ?:attachment_descriptions WHERE attachment_id = ?i", $attachment_id);
    }
}

/**
 * Checks permission to work with the attachment
 *
 * @param array $request    Array of query parameters
 *
 * @return bool Permission to work with attachment
 */
function fn_attachments_check_permission($request)
{
    /**
     * Changes input parameters for attachment permission check
     *
     * @param array $request Array of query parameters
     */
    fn_set_hook('attachments_check_permission_pre', $request);

    $permission = false;

    if (!empty($request['object_type']) && !empty($request['object_id'])) {
        $table = "products";
        $field = "product_id";

        $condition = db_quote("AND ?f = ?i?p", $field, $request['object_id'], fn_get_company_condition("?:{$table}.company_id"));

        /**
         * Checks permission to work with the attachment
         *
         * @param array     $request    Array of query parameters
         * @param string    $table      Table to perform check
         * @param string    $field      SQL field to be selected in an SQL-query
         * @param string    $condition  String containing SQL-query condition prepended with a logical operator (AND or OR)
         */
        fn_set_hook('attachments_check_permission', $request, $table, $field, $condition);

        $object_id = db_get_field(
            "SELECT ?f FROM ?:?f WHERE 1 ?p",
            $field,
            $table,
            $condition
        );
        if (!empty($object_id)) {
            $permission = true;
        }
    }

    /**
     * Changes result of attachment permission check
     *
     * @param array $request Array of query parameters
     */
    fn_set_hook('attachments_check_permission_post', $request, $permission);

    return $permission;
}

/**
 * Fetches array of paths to attachments directory for each existing company
 *
 * @param string $path user specified path
 *
 * @return array
 */
function fn_attachments_get_companies_import_attachments_directory($path = '')
{
    $result = [];
    $company_ids = fn_get_all_companies_ids();

    foreach ($company_ids as $company_id) {
        $result[$company_id] = fn_attachments_get_import_attachments_directory($company_id, $path);
    }

    return $result;
}

/**
 * Fetches array of paths to import attachments directory
 *
 * @param integer $company_id Company id
 * @param string  $path       User specified path
 *
 * @return array
 */
function fn_attachments_get_import_attachments_directory($company_id, $path = '')
{
    if ($path) {
        $path = fn_advanced_import_filter_user_path($path);
    }

    $files_dir = Registry::get('config.dir.files');

    $result = [
        'absolute_path' => sprintf('%s%s/%s%s', $files_dir, $company_id, ADVANCED_IMPORT_PRIVATE_ATTACHMENTS_RELATIVE_PATH, $path),
        'relative_path' => sprintf('%s%s/%s%s', ltrim(fn_get_rel_dir($files_dir), '/'), $company_id, ADVANCED_IMPORT_PRIVATE_ATTACHMENTS_RELATIVE_PATH, $path),
        'exim_path' => sprintf('%s%s', ADVANCED_IMPORT_PRIVATE_ATTACHMENTS_RELATIVE_PATH, $path),
        'filemanager_path' => sprintf('%s%s', ADVANCED_IMPORT_PRIVATE_ATTACHMENTS_RELATIVE_PATH, $path),
    ];

    if (!Registry::get('runtime.company_id')) {
        $result['filemanager_path'] = sprintf('%s/%s', $company_id, $result['filemanager_path']);
    }

    return $result;
}

/**
 * Hook handler after initializing product tabs
 * Sets product chains data to render in a tab
 */
function fn_attachments_init_product_tabs_post($product, $tabs)
{
    if (!empty($product['product_id'])) {
        // Assign attachments files for products
        $attachments = fn_get_attachments('product', $product['product_id']);

        if (!empty($attachments)) {
            Tygh::$app['view']->assign('attachments_data', $attachments);
        }
    }
}

/**
 * Gets current attachment of the object by the url
 *
 * @param string $object    Object type
 * @param int    $object_id Object identifier
 * @param string $url       URL of the
 *
 * @return array{
 *              attachment_id: int,
 *              object_type: string,
 *              object_id: int,
 *              type: string,
 *              position: int,
 *              filename: string,
 *              filesize: int,
 *              usergroup_ids: string,
 *              status: string,
 *              on_server: string,
 *              url:string,
 *              url_status: int
 *          }
 */
function fn_attachments_get_current_attachment_by_url($object, $object_id, $url)
{
    return db_get_row(
        'SELECT * FROM ?:attachments'
        . ' WHERE url = ?s AND object_id = ?i AND object_type = ?s',
        $url,
        $object_id,
        $object
    );
}
