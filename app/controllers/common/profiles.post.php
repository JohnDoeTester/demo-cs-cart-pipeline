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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode === 'get_custom_file') {
    if (
        !isset($_REQUEST['hash'])
        || !isset($_REQUEST['object_type'])
        || !isset($_REQUEST['object_id'])
        || !isset($_REQUEST['field_id'])
    ) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $object_type = $_REQUEST['object_type'];
    $object_id = $_REQUEST['object_id'];
    $field_id = $_REQUEST['field_id'];
    $hash = $_REQUEST['hash'];

    $field_data = fn_get_profile_field_data($object_type, $object_id, $field_id);

    if (empty($field_data['file_path']) || $hash !== $field_data['hash']) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    if (!Storage::instance('custom_files')->isExist($field_data['file_path'])) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    Storage::instance('custom_files')->get($field_data['file_path']);
}
