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

use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Storage;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\FileUploadTypes;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Export product attachments
 *
 * @param int    $product_id      Product identifier
 *
 * @return string
 */
function fn_attachments_exim_export($product_id)
{
    $attachment_list = fn_get_attachments('product', $product_id, 'M');
    if (empty($attachment_list)) {
        return '';
    }

    $exim_path = ADVANCED_IMPORT_PRIVATE_ATTACHMENTS_RELATIVE_PATH . 'product/' . $product_id . '/';
    $exim_path = rtrim(fn_normalize_path($exim_path), '/');

    $attachments_path = fn_get_files_dir_path() . $exim_path;

    if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.simple_ultimate')) {
        $exim_path = Registry::get('runtime.forced_company_id') . '/' . $exim_path;
    }

    fn_mkdir($attachments_path);

    $export_data = [];

    foreach ($attachment_list as $attachment) {
        $description_list = db_get_hash_array(
            'SELECT * FROM ?:attachment_descriptions WHERE attachment_id = ?i',
            'lang_code',
            $attachment['attachment_id']
        );

        $set_delimiter = ';';

        $description_string = [];

        foreach ($description_list as $lang_code => $description) {
            $description_string[] = '[' . $lang_code . ']:' . $description['description'];
        }

        $description_string = implode($set_delimiter, fn_exim_wrap_value($description_string, "'", $set_delimiter));

        $attachment_export_path = $attachment['url'];

        if (empty($attachment['url'])) {
            $path = $attachments_path . '/' . fn_basename($attachment['filename']);

            Storage::instance('attachments')->export('product/' . $product_id . '/' . $attachment['filename'], $path);

            $attachment_export_path = $exim_path . '/' . fn_basename($attachment['filename']);
        }

        $export_data[] = $attachment_export_path . '#{' . $description_string . '}';
    }

    return implode(', ', $export_data);
}

/**
 * Updates product attachments when importing a product.
 *
 * @param int          $product_id            Product ID
 * @param array|string $attachments           Attachments from import file
 * @param string       $attachments_path      Default dir to search files on server
 * @param string       $attachments_delimiter Attachments delimiter
 * @param string       $remove_attachments    Whether to remove attachments
 * @param array        $preset                Import preset data
 * @param array        $parent_data           Data of parent object
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_attachments_import_product_attachments(
    $product_id,
    $attachments,
    $attachments_path,
    $attachments_delimiter,
    $remove_attachments,
    array $preset,
    array $parent_data = []
)
{
    static $attachments_list = [];

    if (is_string($attachments) && !fn_string_not_empty($attachments)
        || is_array($attachments) && !$attachments
    ) {
        return;
    }

    if (is_string($attachments)) {
        $attachments = explode($attachments_delimiter, $attachments);
    }

    foreach ($attachments as $i => $attachment) {
        $attachment_data = [];
        $attachment_description = '';

        $attachment_url = trim($attachment);
        if (!$attachment_url) {
            continue;
        }

        if (!empty($attachment_url) && strpos($attachment_url, '#') !== false) {
            list($attachment_url, $attachment_description) = explode('#', $attachment_url);
        }

        $attachment_files_type = (strpos($attachment_url, '://') === false)
            ? FileUploadTypes::SERVER
            : FileUploadTypes::URL;

        if (isset($attachments_list[$attachment_url])) {
            $attachment_data = $attachments_list[$attachment_url];
        }

        if (empty($attachment_data) || YesNo::toBool(Registry::get('addons.attachments.allow_save_attachments_to_server'))) {
            $file_url = '';

            $_REQUEST['type_attachment_files'] = [$attachment_files_type];
            $_REQUEST['file_attachment_files'] = [];

            $company_id = (fn_allowed_for('ULTIMATE') && Registry::get('runtime.simple_ultimate')) ? 0 : $preset['company_id'];
            $attachment_file = fn_find_file($attachments_path, $attachment_url, $company_id);

            $files_dir_path = (fn_allowed_for('ULTIMATE') && Registry::get('runtime.simple_ultimate'))
                ? fn_get_files_dir_path(0)
                : fn_get_files_dir_path();

            if ($attachment_file !== false) {
                if ($attachment_files_type === FileUploadTypes::URL) {
                    $file_url = $attachment_file;
                } elseif (strpos($attachment_file, Registry::get('config.dir.root')) === 0) {
                    $file_url = str_ireplace($files_dir_path, '', $attachment_file);
                } else {
                    fn_set_notification(NotificationSeverity::ERROR, __('error'), __('attachments.attachments_need_located_root_dir'));
                    continue;
                }
            }

            if (!empty($file_url)) {
                $_REQUEST['file_attachment_files'] = [$file_url];
                $attachment_description = empty($attachment_description) ? $file_url : $attachment_description;
            }

            if ($attachment_files_type === FileUploadTypes::URL) {
                $attachment_data = fn_filter_uploaded_data('attachment_files', [], false);
                $attachment_data = reset($attachment_data);

                if (!YesNo::toBool(Registry::get('addons.attachments.allow_save_attachments_to_server'))) {
                    $attachments_list[$attachment_url] = $attachment_data;
                }
            }
        }

        $options = [
            'remove_attachments'    => $remove_attachments,
            'attachment_company_id' => $preset['company_id'],
        ];

        if (empty($attachment_data) && $attachment_files_type === FileUploadTypes::URL) {
            $attach_errors = Registry::ifGet('exim.attacments_import_errors', []);
            $error_product = '';
            if (!empty($parent_data['product_code'])) {
                $error_product .= $parent_data['product_code'];
            } else {
                $error_product .= 'ID ' . $product_id;
            }
            if (isset($parent_data['product']) && !empty($parent_data['product'])) {
                $error_product .= ' - ' . $parent_data['product'];
            }
            $attach_errors[] = __('attachments.cant_upload_file', ['[url]' => $attachment_url, '[product]' => $error_product]);
            Registry::set('exim.attacments_import_errors', $attach_errors);
            continue;
        }

        fn_attachments_import_attachments(
            $attachment_data,
            $product_id,
            'product',
            $i * 10,
            $attachment_description,
            $options
        );
    }
}

/**
 * Imports attachments.
 *
 * @param array<string, string> $attachment_data               Array of the attachment file data
 * @param int                   $object_id                     ID of object to attach attachments to
 * @param string                $object                        Object type to attach attachments to
 * @param string                $position                      Attachment position
 * @param string                $attachment_description_string Description string of the attachment
 * @param array<string, string> $import_options                Import options
 *
 * @return int|bool Attachment identifier if attachment were imported, else false
 */
function fn_attachments_import_attachments(
    array $attachment_data,
    $object_id,
    $object,
    $position,
    $attachment_description_string = '',
    array $import_options = []
)
{
    static $updated_products = [];
    $perform_import = true;
    $result = false;


    /**
     * Allows to change attachment import params before import
     *
     * @param array  $attachment_data               Path prefix
     * @param string $attachment_description_string Attachment path or filename
     * @param string $position                      Attachment position
     * @param int    $object_id                     ID of object to attach attachment to
     * @param string $object                        Name of object to attach attachment to
     * @param array  $import_options                Import options
     * @param bool   $perform_import                Whether to import attachment
     */
    fn_set_hook(
        'exim_import_attachments_pre',
        $attachment_data,
        $attachment_description_string,
        $position,
        $object_id,
        $object,
        $import_options,
        $perform_import
    );

    if (!$perform_import || empty($object_id)) {
        return false;
    }

    // Process multilang requests
    $object_ids_list = [];
    if (!is_array($object_id)) {
        $object_ids_list = [$object_id];
    }

    foreach ($object_ids_list as $object_id) {
        if (
            empty($updated_products[$object_id])
            && !empty($import_options['remove_attachments'])
            && YesNo::toBool($import_options['remove_attachments'])
        ) {
            $updated_products[$object_id] = true;

            fn_attachments_delete_by_object_id($object, $object_id);
        }

        $current_attachment = $attachment_description = [];

        if (!empty($attachment_description_string)) {
            preg_match('/[\{](.*)[\}]/', $attachment_description_string, $matches);

            $attachment_description_list = [];
            if (!empty($matches[1])) {
                $attachment_description_list = str_getcsv($matches[1], ';', "'");
                array_walk($attachment_description_list, 'fn_trim_helper');
            }

            foreach ($attachment_description_list as $description) {
                preg_match('/\[([A-Za-z]+?)\]:(.*?)$/', $description, $matches);

                if (empty($matches[1]) || empty($matches[2])) {
                    continue;
                }

                $attachment_description[$matches[1]] = $matches[2];
            }
        }

        $import_data = [
            'description' => !empty($attachment_description) ? $attachment_description : fn_basename($attachment_description_string),
            'position'    => $position,
            'url'         => isset($attachment_data['url']) ? $attachment_data['url'] : '',
        ];

        if (!empty($attachment_data['url'])) {
            $current_attachment = fn_attachments_get_current_attachment_by_url($object, $object_id, $attachment_data['url']);
        }

        $attachment_id = empty($current_attachment)
            ? 0
            : $current_attachment['attachment_id'];

        if (empty($attachment_id)) {
            $result = fn_update_attachments(
                $import_data,
                $attachment_id,
                $object,
                $object_id,
                'M',
                $attachment_data
            );

            continue;
        }

        $result = $attachment_id;
    }

    if ($result) {
        return $result;
    }

    fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('attachments.error_exim_get_attachments_for_products'));

    return false;
}

/**
 * List import errors
 *
 * @return void
 */
function fn_attachments_exim_send_errors_notification()
{
    $import_errors = Registry::ifGet('exim.attacments_import_errors', []);
    if (empty($import_errors)) {
        return;
    }
    if (count($import_errors) === 1) {
        reset($import_errors);
        fn_set_notification(NotificationSeverity::WARNING, __('warning'), current($import_errors));
    } else {
        $smarty = Tygh::$app['view'];
        $smarty->assign('messages', $import_errors);
        $errors_view_type = count($import_errors) > 5 ? NotificationSeverity::INFO : NotificationSeverity::WARNING;
        /**
         * @psalm-suppress MissingThrowsDocblock
         */
        fn_set_notification(
            $errors_view_type,
            __('warning'),
            $smarty->fetch('addons/attachments/views/components/export_warnings.tpl')
        );
    }
}
