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

use Tygh\Addons\VendorDataPremoderation\ServiceProvider;
use Tygh\Addons\VendorDataPremoderation\State;
use Tygh\Addons\VendorDataPremoderation\StateFactory;
use Tygh\Enum\Addons\VendorDataPremoderation\ProductStatuses;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Enum\VendorStatuses;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Tools\SecurityHelper;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Hook handler: adds "Requires approval" and "Disapproved" product statuses.
 */
function fn_vendor_data_premoderation_get_all_product_statuses_post($lang_code, &$statuses)
{
    $statuses[ProductStatuses::REQUIRES_APPROVAL] = __(
        'vendor_data_premoderation.product_status.requires_approval',
        [],
        $lang_code
    );

    $statuses[ProductStatuses::DISAPPROVED] = __(
        'vendor_data_premoderation.product_status.disapproved',
        [],
        $lang_code
    );
}

/**
 * Hook handler: updates product moderation results when changing a product status.
 */
function fn_vendor_data_premoderation_tools_update_status_before_query(
    $params,
    $old_status,
    &$status_data,
    $condition
) {
    if ($params['table'] !== 'products'
        || $old_status === $params['status']
    ) {
        return;
    }

    if (
        UserTypes::isVendor(Tygh::$app['session']['auth']['user_type'])
        && ($old_status === ProductStatuses::REQUIRES_APPROVAL
            || $old_status === ProductStatuses::DISAPPROVED
        )
    ) {
        $status_data['status'] = $old_status;

        return;
    }

    $product_id = $params['id'];

    switch ($params['status']) {
        case ProductStatuses::REQUIRES_APPROVAL:
            // "Requires approval" status can't be set manually
            $status_data['status'] = $old_status;
            break;
        case ProductStatuses::DISAPPROVED:
            fn_vendor_data_premoderation_disapprove_products([$product_id], false);
            break;
        default:
            if (($old_status === ProductStatuses::REQUIRES_APPROVAL || $old_status === ProductStatuses::DISAPPROVED)
                && (empty($params['is_status_updated_during_approval']))
            ) {
                fn_vendor_data_premoderation_approve_products([$product_id], false);
            }
            break;
    }
}

/**
 * Hook handler: adds premoderation reason for products.
 */
function fn_vendor_data_premoderation_gather_additional_products_data_post(
    $product_ids,
    $params,
    &$products,
    $auth,
    $lang_code
) {
    if (!SiteArea::isAdmin(AREA)) {
        return;
    }

    $premoderation = fn_vendor_data_premoderation_get_premoderation($product_ids);

    foreach ($products as &$product_data) {
        $product_data['premoderation_reason'] = isset($premoderation[$product_data['product_id']])
            ? $premoderation[$product_data['product_id']]['reason']
            : '';
    }
    unset($product_data);
}

/**
 * Hook handler: adds premoderation reason for a product.
 */
function fn_vendor_data_premoderation_get_product_data_post(&$product_data)
{
    if (!SiteArea::isAdmin(AREA) || !$product_data) {
        return;
    }

    $premoderation = fn_vendor_data_premoderation_get_premoderation($product_data['product_id']);

    $product_data['premoderation_reason'] = $premoderation
        ? $premoderation[$product_data['product_id']]['reason']
        : '';
}

/**
 * Hook handler: sets product approval status after product was cloned.
 */
function fn_vendor_data_premoderation_clone_product_post(
    $original_product_id,
    $cloned_product_id,
    $orig_name,
    $new_name
) {
    if (!$cloned_product_id || !fn_get_runtime_company_id()) {
        return;
    }

    if (fn_vendor_data_premoderation_product_requires_approval(Registry::get('runtime.company_data'), true)) {
        fn_vendor_data_premoderation_request_approval_for_products([$cloned_product_id], true);
    }
}

/**
 * The "update_product_pre" hook handler.
 *
 * Actions performed:
 *     - For vendors: stores originally passed product data when updating a product.
 *     - For admin: approves product when changing its status to something rather than Disapproved or Requires approval.
 *
 * @param array $product_data Product data
 * @param int   $product_id   Product identifier
 *
 * @see \fn_update_product()
 */
function fn_vendor_data_premoderation_update_product_pre(array &$product_data, $product_id)
{
    fn_vendor_data_premoderation_start_product_premoderation($product_data, $product_id);
}

/**
 * The "update_product_before_update" hook handler.
 *
 * Actions performed:
 *     - Disallow admin to change product owner if it's not approved
 *
 * @param array $product_data Product data
 * @param array $product_info Old product data
 *
 * @return void
 *
 * @see \fn_update_product()
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_vendor_data_premoderation_update_product_before_update(array &$product_data, array $product_info)
{
    $old_company_id = isset($product_info['company_id']) ? (int) $product_info['company_id'] : null;

    if (
        !isset($product_data['company_id']) || $old_company_id === (int) $product_data['company_id']
        || !empty($product_info['status']) && $product_info['status'] !== ProductStatuses::REQUIRES_APPROVAL
        || !empty($product_data['status']) && $product_data['status'] !== ProductStatuses::REQUIRES_APPROVAL
    ) {
        return;
    }

    $product_data['company_id'] = $old_company_id;
    fn_set_notification(
        NotificationSeverity::WARNING,
        __('warning'),
        __(
            'vendor_data_premoderation.approval_required_before_changing_owner',
            ['[url]' => fn_url('products.manage?status=' . ProductStatuses::REQUIRES_APPROVAL)]
        )
    );
}

/**
 * The "update_product_post" hook handler.
 *
 * Actions performed:
 *     - Sets product approval status after product was updated
 *
 * @see \fn_update_product()
 */
function fn_vendor_data_premoderation_update_product_post($product_data, $product_id, $lang_code, $is_created)
{
    fn_vendor_data_premoderation_end_product_premoderation($product_id, $is_created);
}

/**
 * The "update_product_file_pre" hook handler.
 *
 * Actions performed:
 *     - For vendors: stores originally passed product files data when updating a product files.
 *     - For admin: approves product when changing its status to something rather than Disapproved or Requires approval.
 *
 * @param array<array-key, string> $product_file File data
 * @param int                      $file_id      File identifier
 * @param string                   $lang_code    Language code to update file description
 *
 * @return void
 *
 * @see \fn_update_product_file()
 */
function fn_vendor_data_premoderation_update_product_file_pre(array $product_file, $file_id, $lang_code)
{
    $product_data = [];
    fn_vendor_data_premoderation_start_product_premoderation($product_data, (int) $product_file['product_id']);
}

/**
 * The "update_product_file_post" hook handler.
 *
 * Actions performed:
 *     - Sets product approval status after product files were updated
 *
 * @param array<array-key, string> $product_file File data
 * @param int                      $file_id      File identifier
 * @param string                   $lang_code    Language code to update file description
 *
 * @return void
 *
 * @see \fn_update_product_file()
 */
function fn_vendor_data_premoderation_update_product_file_post(array $product_file, $file_id, $lang_code)
{
    $is_created = false;
    fn_vendor_data_premoderation_end_product_premoderation((int) $product_file['product_id'], $is_created);
}

/**
 * The "update_product_file_folder_pre" hook handler.
 *
 * Actions performed:
 *     - For vendors: stores originally passed product file folders data when updating a product file folders.
 *     - For admin: approves product when changing its status to something rather than Disapproved or Requires approval.
 *
 * @param array<array-key, string> $product_file_folder File folder data
 * @param int                      $folder_id           File folder identifier
 * @param string                   $lang_code           Language code to update file folder description
 *
 * @return void
 *
 * @see \fn_update_product_file_folder()
 */
function fn_vendor_data_premoderation_update_product_file_folder_pre(array $product_file_folder, $folder_id, $lang_code)
{
    $product_data = [];
    fn_vendor_data_premoderation_start_product_premoderation($product_data, (int) $product_file_folder['product_id']);
}

/**
 * The "update_product_file_folder_post" hook handler.
 *
 * Actions performed:
 *     - Sets product approval status after product file folders were updated
 *
 * @param array<array-key, string> $product_file_folder File folder data
 * @param int                      $folder_id           File folder identifier
 * @param string                   $lang_code           Two-letter language code (e.g. 'en', 'ru', etc.)
 *
 * @return void
 *
 * @see \fn_update_product_file_folder()
 */
function fn_vendor_data_premoderation_update_product_file_folder_post(array $product_file_folder, $folder_id, $lang_code)
{
    $is_created = false;
    fn_vendor_data_premoderation_end_product_premoderation((int) $product_file_folder['product_id'], $is_created);
}

/**
 * The "delete_product_files_before_delete" hook handler.
 *
 * Actions performed:
 *     - For vendors: stores originally passed product files data when deleting a product files.
 *     - For admin: approves product when changing its status to something rather than Disapproved or Requires approval.
 *
 * @param int[] $file_ids   File identifiers
 * @param int   $product_id ID of the product that owns the file
 *
 * @return void
 *
 * @see \fn_delete_product_files()
 */
function fn_vendor_data_premoderation_delete_product_files_before_delete(array $file_ids, $product_id)
{
    $product_data = [];
    fn_vendor_data_premoderation_start_product_premoderation($product_data, $product_id);
}

/**
 * The "delete_product_file_folders_before_delete" hook handler.
 *
 * Actions performed:
 *     - For vendors: stores originally passed product files data when deleting a product file folders.
 *     - For admin: approves product when changing its status to something rather than Disapproved or Requires approval.
 *
 * @param int[] $folder_ids File folder identifiers
 * @param int[] $file_ids   File identifiers
 * @param int   $product_id Product identifier
 *
 * @return void
 *
 * @see \fn_delete_product_file_folders()
 */
function fn_vendor_data_premoderation_delete_product_file_folders_before_delete(array $folder_ids, array $file_ids, $product_id)
{
    $product_data = [];
    fn_vendor_data_premoderation_start_product_premoderation($product_data, $product_id);
}

/**
 * The "delete_product_files_post" hook handler.
 *
 * Actions performed:
 *     - Sets product approval status after product file was deleted
 *
 * @param int[] $file_ids   File identifiers
 * @param int   $product_id ID of the product that owns the file
 *
 * @return void
 *
 * @see \fn_delete_product_files()
 */
function fn_vendor_data_premoderation_delete_product_files_post(array $file_ids, $product_id)
{
    $is_created = false;
    fn_vendor_data_premoderation_end_product_premoderation($product_id, $is_created);
}

/**
 * The "delete_product_file_folders_post" hook handler.
 *
 * Actions performed:
 *     - Sets product approval status after product file folder was deleted
 *
 * @param int[] $folder_ids File folder identifiers
 * @param int   $product_id Product identifier
 * @param int[] $file_ids   File identifiers
 *
 * @return void
 *
 * @see \fn_delete_product_file_folders()
 */
function fn_vendor_data_premoderation_delete_product_file_folders_post(array $folder_ids, $product_id, array $file_ids)
{
    $is_created = false;
    fn_vendor_data_premoderation_end_product_premoderation($product_id, $is_created);
}

/**
 * The "update_product_categories_pre" hook handler.
 *
 * Actions performed:
 *     - Stores initial product data when updating product categories outside of fn_update_product function.
 *
 * @see \fn_update_product_categories()
 */
function fn_vendor_data_premoderation_update_product_categories_pre($product_id, $product_data, $rebuild, $company_id)
{
    // do not perform any checks when fn_update_product_categories() is called within fn_update_product()
    if (Registry::isExist('vendor_data_premoderation.is_updating_product')) {
        return;
    }

    if (!UserTypes::isVendor(Tygh::$app['session']['auth']['user_type'])) {
        return;
    }

    $company_data = fn_get_runtime_company_id() ? Registry::get('runtime.company_data') : fn_get_company_data(Tygh::$app['session']['auth']['company_id']);
    $current_status = fn_vendor_data_premoderation_get_current_product_statuses([$product_id])[$product_id];
    $requires_premoderation = fn_vendor_data_premoderation_product_requires_approval($company_data, false, $current_status);
    if (!$requires_premoderation) {
        return;
    }

    if ($current_status === ProductStatuses::REQUIRES_APPROVAL) {
        return;
    }

    $initial_product_state = fn_vendor_data_premoderation_get_product_state($product_id);
    Registry::set('vendor_data_premoderation.initial_product_state', $initial_product_state, true);
}

/**
 * The "update_product_categories_pre" hook handler.
 *
 * Actions performed:
 *     - Stores initial product data when updating product categories outside of fn_update_product function.
 *
 * @see \fn_update_product_categories()
 */
function fn_vendor_data_premoderation_update_product_categories_post($product_id, $product_data, $existing_categories, $rebuild, $company_id, $saved_category_ids)
{
    // do not perform any checks when fn_update_product_categories() is called within fn_update_product()
    if (Registry::isExist('vendor_data_premoderation.is_updating_product')) {
        return;
    }

    if (!UserTypes::isVendor(Tygh::$app['session']['auth']['user_type'])) {
        return;
    }

    $company_data = fn_get_runtime_company_id() ? Registry::get('runtime.company_data') : fn_get_company_data(Tygh::$app['session']['auth']['company_id']);
    $current_status = fn_vendor_data_premoderation_get_current_product_statuses([$product_id])[$product_id];
    $requires_premoderation = fn_vendor_data_premoderation_product_requires_approval($company_data, false, $current_status);
    if (!$requires_premoderation) {
        return;
    }

    if ($current_status === ProductStatuses::REQUIRES_APPROVAL) {
        return;
    }

    $initial_product_state = Registry::ifGet('vendor_data_premoderation.initial_product_state', null);
    $resulting_product_state = fn_vendor_data_premoderation_get_product_state($product_id);
    $has_changes_to_moderate = fn_vendor_data_premoderation_is_product_changed($initial_product_state, $resulting_product_state);

    if (!$has_changes_to_moderate) {
        return;
    }

    fn_vendor_data_premoderation_request_approval_for_products([$product_id], true);
}

/**
 * Hook handler: filters out disapproved products from exported ones.
 */
function fn_vendor_data_premoderation_data_feeds_export_before_get_products($datafeed_data, $pattern, &$params)
{
    if (isset($params['status'])) {
        $params['status'] = (array) $params['status'];
    } else {
        $params['status'] = array_keys(fn_get_all_product_statuses());
    }

    if (isset($datafeed_data['params']['exclude_disapproved_products'])
        && YesNo::toBool($datafeed_data['params']['exclude_disapproved_products'])
    ) {
        $params['status'] = array_diff($params['status'], [ProductStatuses::REQUIRES_APPROVAL, ProductStatuses::DISAPPROVED]);
    } else {
        $params['status'] = array_unique(array_merge($params['status'], [ProductStatuses::REQUIRES_APPROVAL, ProductStatuses::DISAPPROVED]));
    }

    if (isset($datafeed_data['params']['exclude_disabled_products'])
        && YesNo::toBool($datafeed_data['params']['exclude_disabled_products'])
    ) {
        $params['status'] = array_diff($params['status'], [ProductStatuses::DISABLED, ProductStatuses::HIDDEN, ProductStatuses::DISAPPROVED]);
    }
}

/**
 * Hook handler: sets company moderation status when creating/updating.
 */
function fn_vendor_data_premoderation_update_company_pre(&$company_data, &$company_id, &$lang_code)
{
    if (!fn_get_runtime_company_id()) {
        return;
    }

    $orig_company_data = fn_get_company_data($company_id, $lang_code);
    $vendors_premoderation = Registry::get('addons.vendor_data_premoderation.vendor_profile_updates_approval');

    if (
        isset($company_data['status'])
        && !fn_vendor_data_premoderation_is_vendor_status_can_be_changed($company_data['status'], $orig_company_data['status'])
    ) {
        unset($company_data['status']);
    }

    if ($orig_company_data['status'] === VendorStatuses::ACTIVE
        && ($vendors_premoderation == 'all'
            || ($vendors_premoderation == 'custom'
                && !empty($orig_company_data['pre_moderation_edit_vendors'])
                && YesNo::toBool($orig_company_data['pre_moderation_edit_vendors'])
            )
        )
    ) {
        $logotypes = fn_filter_uploaded_data('logotypes_image_icon'); // FIXME: dirty comparison

        SecurityHelper::sanitizeObjectData('company', $company_data);

        // check that some data is changed
        if (fn_vendor_data_premoderation_diff_company_data($company_data, $orig_company_data) || !empty($logotypes)) {
            $company_data['status'] = VendorStatuses::PENDING;
        }
    }
}

/**
 * Hook handler: Notifies admin about products that require approval.
 */
function fn_vendor_data_premoderation_set_admin_notification(&$auth)
{
    if (
        $auth['company_id']
        || !fn_check_permissions('premoderation', 'm_approve', 'admin')
        || !fn_vendor_data_premoderation_admin_is_receiver($auth, 'vendor_data_premoderation')
    ) {
        return;
    }

    fn_vendor_data_premoderation_send_product_premoderation_notification(['internal'], $auth['user_id']);
}

/**
 * Hook handler: adds "Requires approval" and "Disapproved" product statuses.
 */
function fn_vendor_data_premoderation_get_product_statuses_post($status, $add_hidden, $lang_code, &$statuses)
{
    static $company_id;
    if ($company_id === null) {
        $company_id = (int) Tygh::$app['session']['auth']['company_id'];
    }

    // Vendors can't change product status if the product was sent to moderation
    if ($company_id !== 0
        && ($status === ProductStatuses::REQUIRES_APPROVAL
            || $status === ProductStatuses::DISAPPROVED
        )
    ) {
        $statuses = [];
    }

    // "Requires approval" status can't be set manually
    if ($status === ProductStatuses::REQUIRES_APPROVAL) {
        $statuses[ProductStatuses::REQUIRES_APPROVAL] = __(
            'vendor_data_premoderation.product_status.requires_approval',
            [],
            $lang_code
        );
    }

    // Only administrators can set product status to "Disapproved"
    if ($company_id === 0 && $status !== '' || $status === ProductStatuses::DISAPPROVED) {
        $statuses[ProductStatuses::DISAPPROVED] = __(
            'vendor_data_premoderation.product_status.disapproved',
            [],
            $lang_code
        );
    }
}

/**
 * Hook handler: removes product premoderation when deleting a product.
 */
function fn_vendor_data_premoderation_delete_product_post($product_id)
{
    fn_vendor_data_premoderation_delete_premoderation($product_id);
}

/**
 * The "change_company_status_pre" hook handler.
 *
 * Actions performed:
 *     - Changes status_to, depending on the add-on settings.
 *
 * @param int    $company_id  Company ID
 * @param string $status_to   Status to letter
 * @param string $reason      Reason text
 * @param string $status_from Status from letter
 * @param bool   $skip_query  Skip query flag
 * @param bool   $notify      Notify flag
 *
 * @see fn_change_company_status()
 *
 * @return void
 */
function fn_vendor_data_premoderation_change_company_status_pre($company_id, &$status_to, $reason, $status_from, $skip_query, $notify)
{
    if (empty($status_from)) {
        $status_from = db_get_field('SELECT status FROM ?:companies WHERE company_id = ?i', $company_id);
    }

    if (
        $status_from !== VendorStatuses::NEW_ACCOUNT
        || $status_to === VendorStatuses::DISABLED
    ) {
        return;
    }

    $status_to = fn_vendor_data_premoderation_get_status_for_new_vendor();
}

/**
 * The "smarty_component_configurable_page_field_before_output" hook handler.
 *
 * Actions performed:
 *     - Adds display of old field values.
 *
 * @param string                                                                                  $entity       Page entity
 * @param string                                                                                  $tab          Tab of the field on the page
 * @param string                                                                                  $section      Section of the field in the tab
 * @param string                                                                                  $field        Field identifier
 * @param array<string, string|bool|int|array<string, string|callable|array<string, string|int>>> $field_config Field configuration
 * @param array<string, string>                                                                   $params       Component parameters
 * @param string                                                                                  $content      Output field content
 * @param \Smarty_Internal_Template                                                               $template     Template instance
 *
 * @throws Exception Exception.
 * @throws SmartyException Smarty exception.
 *
 * @see smarty_component_configurable_page_field()
 *
 * @return void
 */
function fn_vendor_data_premoderation_smarty_component_configurable_page_field_before_output(
    $entity,
    $tab,
    $section,
    $field,
    array $field_config,
    array $params,
    &$content,
    Smarty_Internal_Template $template
) {
    if ($entity !== 'products') {
        return;
    }

    static $initial_product_states;
    static $current_product_states;
    static $diffs;
    static $premoderation;

    /** @var array{product_id?: int, status?: string} $product_data */
    $product_data = $template->getTemplateVars('product_data');

    if (empty($product_data['product_id'])) {
        return;
    }

    $product_id = $product_data['product_id'];

    if (
        empty($product_data['status'])
        || $product_data['status'] !== ProductStatuses::REQUIRES_APPROVAL
        && $product_data['status'] !== ProductStatuses::DISAPPROVED
    ) {
        return;
    }

    if (
        !isset($initial_product_states[$product_id])
        || !isset($current_product_states[$product_id])
        || !isset($diffs[$product_id])
    ) {
        $premoderation = fn_vendor_data_premoderation_get_premoderation($product_id);

        $initial_product_state = $premoderation
            ? $premoderation[$product_id]['initial_product_state']
            : '';

        if (!$initial_product_state) {
            return;
        }

        $initial_product_states[$product_id] = new State((array) unserialize($initial_product_state));
        $current_product_states[$product_id] = fn_vendor_data_premoderation_get_product_state($product_id);
        $diffs[$product_id] = ServiceProvider::getProductComparator()
            ->compare($initial_product_states[$product_id], $current_product_states[$product_id], true);
    }

    $source_diff = $diffs[$product_id]->getSources();
    $fields_diff = $diffs[$product_id]->getFields();
    $original_content = $content;

    $template->assign([
        'product_data'       => $product_data,
        'original_content'   => $original_content,
        'old_value'          => false,
        'premoderation_data' => $premoderation,
    ]);

    $content = $template->fetch('addons/vendor_data_premoderation/components/product_page/field_content.tpl');

    if (
        !isset($fields_diff[$field_config['source']['table']][$field_config['source']['field']])
        && !isset($source_diff[$field_config['source']['table']])
    ) {
        return;
    }

    if (is_callable($field_config['source']['processing'])) {
        $old_value = call_user_func(
            $field_config['source']['processing'],
            $product_id,
            $field_config,
            $initial_product_states[$product_id],
            $current_product_states[$product_id],
            $template
        );
    } else {
        $old_value = false;
        $initial_data = $initial_product_states[$product_id]->getSourceData($field_config['source']['table']);
        $current_data = $current_product_states[$product_id]->getSourceData($field_config['source']['table']);

        $func = static function ($product_id, $data, $field_config) {
            $value = false;
            foreach ($data as $row) {
                $value = $row[$field_config['source']['field']];
                foreach ($field_config['source']['conditions'] as $search_name => $search_value) {
                    if ($search_value === StateFactory::OBJECT_ID_PLACEHOLDER) {
                        $search_value = (string) $product_id;
                    }

                    if ($row[$search_name] !== $search_value) {
                        $value = false;
                        break;
                    }
                }

                if ($value !== false) {
                    break;
                }
            }
            return $value;
        };

        $initial_value = $func($product_id, $initial_data, $field_config);
        $current_value = $func($product_id, $current_data, $field_config);

        if ($initial_value !== $current_value) {
            $old_value = $initial_value;
        }
    }

    if ($old_value === false) {
        return;
    }

    $template->assign([
        'product_data'       => $product_data,
        'original_content'   => $original_content,
        'old_value'          => $old_value,
        'premoderation_data' => $premoderation,
    ]);

    $content = $template->fetch('addons/vendor_data_premoderation/components/product_page/field_content.tpl');
}
