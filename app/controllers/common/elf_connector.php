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

use Tygh\Registry;
use Tygh\Storage;
use Tygh\Bootstrap;
use Tygh\ElFinder\Core;
use Tygh\ElFinder\Connector;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (Registry::get('config.demo_mode')) {
    // ElFinder should not work in demo mode
    $message = json_encode(array('error' => __('error_demo_mode')));
    exit($message);
}

if (AREA == 'C') {
    if (!Registry::get('runtime.customization_mode.live_editor')) {
        die('Access denied');
    }
}

// This code is needed for parameters sanitization
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_GET = $_REQUEST;
}

$command = null;
if (isset($_REQUEST['cmd'])) {
    $command = $_REQUEST['cmd'];
}

// only list and view commands can be executed with GET requests
$safe_commands = ['open', 'file', 'get', 'search', 'parents', 'subdirs', 'ls', 'zipdl'];
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && !in_array($command, $safe_commands)
) {
    $message = json_encode(['error' => __('access_denied')]);
    exit($message);
}

$company_id = null;
if (fn_allowed_for('ULTIMATE')) {
    $company_id = Registry::get('runtime.company_id');
}

$private_files_path = fn_get_files_dir_path($company_id);
$public_files_path = fn_get_public_files_path($company_id);

fn_mkdir($private_files_path);
fn_mkdir($public_files_path);

$start_path = '';

if (!empty($_REQUEST['init']) && !empty($_REQUEST['start_path'])) {
    unset($_GET['target'], $_POST['target']);
    $start_path = fn_normalize_path($private_files_path . $_REQUEST['start_path']);
    if (strpos($start_path, $private_files_path) !== 0) {
        $start_path = '';
    }
}

$extra_path = str_replace(Storage::instance('images')->getAbsolutePath(''), '', $public_files_path);

$forbidden_file_extensions_pattern = '/\.(' . implode('|', Registry::get('config.forbidden_file_extensions')) . ')$/i';

$opts = [
    'roots' => [
        [
            'driver'        => 'Tygh\ElFinder\Volume',
            'uploadDeny'    => Registry::get('config.forbidden_mime_types'),
            'fileMode'      => DEFAULT_FILE_PERMISSIONS,
            'dirMode'       => DEFAULT_DIR_PERMISSIONS,
            'uploadMaxSize' => Bootstrap::getIniParam('upload_max_filesize', true),
            'alias'         => __('private_files'),
            'tmbPath'       => '',
            'path'          => $private_files_path,
            'startPath'     => $start_path,
            'mimeDetect'    => 'internal',
            'archiveMimes'  => [
                'application/zip'
            ],
            'icon'          => Registry::get('config.current_location') . '/js/lib/elfinder/img/volume_icon_local.png',
            'attributes'    => [
                [
                    'pattern' => $forbidden_file_extensions_pattern,
                    'read'    => false,
                    'write'   => false,
                    'locked'  => true,
                    'hidden'  => true
                ]
            ]
        ],
        [
            'driver'        => 'Tygh\ElFinder\Volume',
            'uploadDeny'    => Registry::get('config.forbidden_mime_types'),
            'fileMode'      => DEFAULT_FILE_PERMISSIONS,
            'dirMode'       => DEFAULT_DIR_PERMISSIONS,
            'uploadMaxSize' => Bootstrap::getIniParam('upload_max_filesize', true),
            'alias'         => __('public_files'),
            'tmbPath'       => '',
            'path'          => $public_files_path,
            'URL'           => Storage::instance('images')->getUrl($extra_path),
            'mimeDetect'    => 'internal',
            'archiveMimes'  => [
                'application/zip'
            ],
            'icon'          => Registry::get('config.current_location') . '/js/lib/elfinder/img/volume_icon_local.png',
            'attributes'    => [
                [
                    'pattern' => $forbidden_file_extensions_pattern,
                    'read'    => false,
                    'write'   => false,
                    'locked'  => true,
                    'hidden'  => true
                ]
            ]
        ],
    ]
];

if ($mode === 'images') {
    unset($opts['roots'][0]);
}

$connector = new Connector(new Core($opts));
$connector->run();
exit;
