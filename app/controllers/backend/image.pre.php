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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'upload') {
        $rebuilt = fn_rebuild_files('file');
        $file = reset($rebuilt);

        if (empty($file)) {
            exit;
        }

        $file_extension = fn_get_file_ext($file['name']);

        if (!fn_is_file_extension_allowed($file_extension)) {
            exit;
        }

        $file = fn_move_uploaded_file($file);

        Tygh::$app['ajax']->assign('local_data', $file);
        exit;
    }

    return [CONTROLLER_STATUS_OK];
}

