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

use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\YesNo;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'import') {
        $file = fn_filter_uploaded_data('csv_file');

        if (!empty($file)) {
            $pattern = fn_exim_get_pattern_definition('products', 'import');
            $shopify_import_pattern = fn_get_schema('shopify_import', 'products');
            $pattern = array_replace_recursive($pattern, $shopify_import_pattern);

            $import_options = array_merge(
                [
                    'images_path'                => 'exim/backup/images/',
                    'price_dec_sign_delimiter'   => '.',
                    'delimiter'                  => 'C',
                    'features_delimiter'         => '///',
                    'category_delimiter'         => '///',
                    'validate_schema'            => false,
                    'skip_creating_new_products' => YesNo::NO,
                    'files_path'                 => 'exim/backup/downloads/',
                    'delete_files'               => YesNo::NO,
                    'images_delimiter'           => '///',
                    'remove_images'              => true
                ],
                isset($_REQUEST['sync_data_settings']) ? (array) $_REQUEST['sync_data_settings'] : []
            );

            $shopify_csv_data = fn_exim_get_csv($pattern, $file[0]['path'], $import_options);

            $filtering_result = fn_shopify_import_filter_data($shopify_csv_data, $import_options);
            $shopify_filtered_data = $filtering_result->getData('filtered_data');
            $import_success = false;

            if ($filtering_result->isSuccess()) {
                $import_success = fn_import($pattern, $shopify_filtered_data, $import_options);
            }
            fn_shopify_import_save_import_result($import_success, fn_get_runtime_company_id());

            $filtering_result->showNotifications();
        } else {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('error_exim_no_file_uploaded'));
        }

        return [CONTROLLER_STATUS_OK, 'sync_data.update?sync_provider_id=shopify_import'];
    }
}
