<?php
/****************************************************************************
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

use Tygh\Addons\ProductReviews\ServiceProvider as ProductReviewsProvider;
use Tygh\Enum\NotificationSeverity;
use Tygh\Registry;
use Tygh\Storage;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Initializes product reviews
 *
 * @param array<array-key, string|int>|null $alt_keys                   Keys that act as primary when adding/updating
 * @param bool                              $skip_get_primary_object_id Flag to leave primary object empty
 *
 * @return void
 */
function fn_import_skip_get_primary_object_id(&$alt_keys, &$skip_get_primary_object_id)
{
    if (!empty($alt_keys)) {
        return;
    }

    $skip_get_primary_object_id = true;
}

/**
 * Checks permission to add product review to the specified product.
 *
 * @param array<string, string|int|bool>                                       $object         Imported product review
 * @param array<array-key, int|array<array-key, array<array<array-key, int>>>> $processed_data Import stats
 * @param bool                                                                 $skip_record    Whether to skip record
 *
 * @psalm-suppress ReferenceConstraintViolation
 *
 * @psalm-param array{
 *  E: int,
 *  N: int,
 *  S: int,
 *  C: int
 * } $processed_data
 *
 * @return void
 */
function fn_import_access_to_product(&$object, &$processed_data, &$skip_record)
{
    if (empty(trim((string) $object['product_id']))) {
        $skip_record = true;
        $processed_data['S']++;
        fn_set_notification(
            NotificationSeverity::ERROR,
            __('error'),
            __('product_reviews.exim_error_empty_field', [
                '[field]' => 'Product ID',
                '[comment]' => $object['comment']
            ])
        );
    } else {
        $incorrect_product_id = false;
        $company_id = fn_get_company_id('products', 'product_id', $object['product_id']);

        if (fn_allowed_for('ULTIMATE')) {
            if (!empty($object['storefront_id'])) {
                $storefront_company_id = (int) db_get_field(
                    'SELECT company_id FROM ?:storefronts_companies WHERE storefront_id = ?i',
                    $object['storefront_id']
                );
            }

            if (
                !in_array(
                    !empty($storefront_company_id) ? $storefront_company_id : Registry::get('runtime.company_id'),
                    fn_ult_get_shared_product_companies((int) $object['product_id'])
                )
            ) {
                $incorrect_product_id = true;
            }
        }

        if (
            fn_allowed_for('MULTIVENDOR')
            && (
                $company_id === ''
                || Registry::get('runtime.company_id')
                && Registry::get('runtime.company_id') !== (int) $company_id
            )
        ) {
            $incorrect_product_id = true;
        }

        if ($incorrect_product_id) {
            $skip_record = true;
            $processed_data['S']++;
            fn_set_notification(
                NotificationSeverity::ERROR,
                __('error'),
                __('product_reviews.exim_error_incorrect_field_value', [
                    '[field]' => 'product ID',
                    '[value]' => $object['product_id'],
                    '[comment]' => $object['comment']
                ])
            );
        }
    }
}

/**
 * Processes of user information.
 *
 * @param array<string, string|int|bool>                                       $object         Imported product review
 * @param array<array-key, int|array<array-key, array<array<array-key, int>>>> $processed_data Import stats
 * @param bool                                                                 $skip_record    Whether to skip record
 *
 * @psalm-suppress ReferenceConstraintViolation
 * @psalm-suppress PossiblyInvalidScalarArgument
 *
 * @psalm-param array{
 *  E: int,
 *  N: int,
 *  S: int,
 *  C: int
 * } $processed_data
 *
 * @return void
 */
function fn_import_prepare_user_data(&$object, &$processed_data, &$skip_record)
{
    $auth = Tygh::$app['session']['auth'];

    if (!empty($object['user_id'])) {
        $object['user_id'] = fn_check_user_exists((int) $object['user_id'], (int) $auth['company_id']);
    }

    if (!empty($object['reply_user_id'])) {
        $object['reply_user_id'] = fn_check_user_exists((int) $object['reply_user_id'], (int) $auth['company_id']);
    }

    if (empty(trim((string) $object['name']))) {
        $object['user_id'] = '0';
    }

    if (empty($object['reply'])) {
        return;
    }

    $object['reply'] = trim((string) $object['reply']);
    if (!empty($object['reply'])) {
        $object['reply_user_id'] = !empty($object['reply_user_id']) ? $object['reply_user_id'] : $auth['user_id'];
        $object['reply_timestamp'] = !empty($object['reply_timestamp']) ? $object['reply_timestamp'] : TIME;
    } elseif (!$skip_record) {
        $skip_record = true;
        $processed_data['S']++;
        fn_set_notification(
            NotificationSeverity::ERROR,
            __('error'),
            __('product_reviews.exim_error_empty_field', [
                '[field]' => 'Reply',
                '[comment]' => $object['comment']
            ])
        );
    }
}

/**
 * Processes information about the storefront.
 *
 * @param array<string, string|int|bool>                                       $object         Imported product review
 * @param array<array-key, int|array<array-key, array<array<array-key, int>>>> $processed_data Import stats
 * @param bool                                                                 $skip_record    Whether to skip record
 *
 * @psalm-param array{
 *  E: int,
 *  N: int,
 *  S: int,
 *  C: int
 * } $processed_data
 *
 * @return void
 */
function fn_import_prepare_storefront(&$object, &$processed_data, &$skip_record)
{
    if (empty($object['storefront_id'])) {
        $object['storefront_id'] = 0;
    } else {
        $is_exists = db_get_field('SELECT storefront_id FROM ?:storefronts WHERE storefront_id = ?i', (int) $object['storefront_id']);
        if (!$is_exists && !$skip_record) {
            $skip_record = true;
            $processed_data['S']++;
            fn_set_notification(
                NotificationSeverity::ERROR,
                __('error'),
                __('product_reviews.exim_error_incorrect_field_value', [
                    '[field]' => 'store',
                    '[value]' => $object['storefront_id'],
                    '[comment]' => $object['comment']
                ])
            );
        }
    }
}

/**
 * Rounds unavailable rating values.
 *
 * @param string $object Imported rating value
 *
 * @return int
 */
function fn_import_round_rating_value($object)
{
    if ($object <= 0) {
        $object = 1;
    } elseif ($object > 5) {
        $object = 5;
    }

    return (int) $object;
}

/**
 * Converts the storefront name to its ID.
 *
 * @param string $object Imported storefront name
 *
 * @return int
 */
function fn_import_get_storefront_id($object)
{
    return (int) db_get_field('SELECT storefront_id FROM ?:storefronts WHERE name = ?s', $object);
}

/**
 * Converts the storefront ID name to its name.
 *
 * @param string $object Imported storefront ID
 *
 * @return string
 */
function fn_import_get_storefront_name($object)
{
    return db_get_field('SELECT name FROM ?:storefronts WHERE storefront_id = ?i', (int) $object);
}

/**
 * Updates information about product reviews after importing a new product review
 *
 * @param array<array-key, array<string, string>>                $primary_object_ids Primary objects ids
 * @param array<array-key, array<string, array<string, string>>> $import_data        Import data
 *
 * @return bool
 */
function fn_actualize_prepared_data(array $primary_object_ids, array $import_data)
{
    $service = ProductReviewsProvider::getService();
    $reviews = array_intersect_key($import_data, $primary_object_ids);
    foreach ($reviews as $review) {
        $review_data = array_shift($review);
        $service->actualizeProductPreparedData((int) $review_data['product_id']);
    }

    return true;
}

/**
 * Checks the existence of the specified user.
 *
 * @param int      $value_user_id Specified user id
 * @param int|null $company_id    Specified company id
 *
 * @return int
 */
function fn_check_user_exists($value_user_id, $company_id = 0)
{
    $condition = db_quote('user_id = ?i', $value_user_id);
    if (
        fn_allowed_for('ULTIMATE')
        && !empty($company_id)
    ) {
        $condition .= db_quote(' AND company_id = ?i', $company_id);
    }
    $user_id = !empty($user_id = db_get_field('SELECT user_id FROM ?:users WHERE ?p', $condition)) ? (int) $user_id : 0;

    return $user_id;
}

/**
 * Export product reviews images
 *
 * @param int    $object_id       Object ID
 * @param string $object          Object to export image for (product, category, etc...)
 * @param string $image_delimiter Path delimiter
 * @param string $backup_path     Path to export image
 *
 * @psalm-suppress PossiblyInvalidArgument
 *
 * @return string
 */
function fn_exim_get_product_review_images($object_id, $object, $image_delimiter, $backup_path = '')
{
    $image_delimiter = !empty($image_delimiter) ? $image_delimiter : '; ';

    if (empty($backup_path)) {
        $backup_path = 'exim/backup/images/' . $object . '/';
    }

    $backup_path = rtrim(fn_normalize_path($backup_path), '/');
    $files_dir_path = (fn_allowed_for('ULTIMATE') && Registry::get('runtime.simple_ultimate'))
        ? fn_get_files_dir_path(0)
        : fn_get_files_dir_path();
    $images_path = $files_dir_path . $backup_path;
    fn_mkdir($images_path);

    $array_images_ids = array_chunk(
        db_get_fields(
            'SELECT ?:images.image_id FROM ?:images '
            . 'JOIN ?:images_links ON ?:images_links.detailed_id = ?:images.image_id '
            . 'WHERE ?:images_links.object_type = ?s AND ?:images_links.object_id = ?i',
            $object,
            $object_id
        ),
        200
    );

    $product_review_images = [];
    foreach ($array_images_ids as $images_ids) {
        $image_data = db_get_hash_single_array(
            'SELECT image_id, image_path FROM ?:images WHERE image_id IN (?n)',
            ['image_id', 'image_path'],
            $images_ids
        );

        foreach ($image_data as $image_id => $image_path) {
            $path = $images_path . '/' . fn_basename($image_path);
            Storage::instance('images')->export('detailed/' . fn_get_image_subdir($image_id) . '/' . $image_path, $path);
            $full_path = ($backup_path . '/' . fn_basename($image_path));
            $product_review_images[] = $full_path;
        }
    }

    return implode($image_delimiter, fn_exim_wrap_value($product_review_images, '\'', $image_delimiter));
}

/**
 * Import product reviews images
 *
 * @param string $image_delimiter Path delimiter
 * @param string $prefix          Path prefix
 * @param string $image_file      Thumbnail path or filename
 * @param string $detailed_file   Detailed image path or filename
 * @param string $type            Pair type
 * @param int    $object_id       ID of object to attach images to
 * @param string $object          Name of object to attach images to
 *
 * @return array<array-key, array<array-key, int>>|bool True if images were imported
 */
function fn_exim_put_product_review_images($image_delimiter, $prefix, $image_file, $detailed_file, $type, $object_id, $object)
{
    if (empty(trim($detailed_file))) {
        return false;
    }

    $image_delimiter = !empty($image_delimiter) ? $image_delimiter : '; ';
    $images = explode($image_delimiter, $detailed_file);

    $old_image_pairs_ids = db_get_fields(
        'SELECT ?:images_links.pair_id FROM ?:images_links '
        . 'WHERE ?:images_links.object_type = ?s AND ?:images_links.object_id = ?i AND ?:images_links.type = ?s',
        $object,
        $object_id,
        $type
    );
    db_query('DELETE FROM ?:images_links WHERE pair_id IN (?n)', $old_image_pairs_ids);

    $product_review_images = [];
    $position = 0;
    foreach ($images as $image) {
        $result = fn_exim_import_images($prefix, $image_file, $image, (string) $position, $type, $object_id, $object);

        if (!$result) {
            return false;
        }

        $product_review_images[] = $result;
        $position++;
    }

    return $product_review_images;
}

/**
 * Merges product reviews with multiple images
 *
 * @param string $joins Join queries
 *
 * @return void
 */
function fn_product_reviews_pre_export_process_merge_product_reviews(&$joins)
{
    $joins[0] .= ' GROUP BY object_id, product_review_id';
}

/**
 * Replaces the line break with the <br> tag
 *
 * @param string $object Processed item product reviews
 *
 * @return string
 */
function fn_product_reviews_line_break_to_br($object)
{
    return str_replace(PHP_EOL, '<br>', trim($object, "\r\n"));
}

/**
 * Replaces the <br> tag with a line break
 *
 * @param string $object Processed item product reviews
 *
 * @return string
 */
function fn_product_reviews_br_to_line_break($object)
{
    return str_replace('<br>', PHP_EOL, $object);
}
