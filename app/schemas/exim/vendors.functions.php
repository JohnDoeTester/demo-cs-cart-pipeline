<?php

use Tygh\Languages\Languages;
use Tygh\Shippings\Shippings;

/**
 * Exports logo to file.
 *
 * @param int $vendor_id Vendor identifier
 * @param string $logo_type Logo type ('theme' or 'mail')
 * @param string $backup_path Directory to store image
 *
 * @return string Path to the exported logo
 */
function fn_exim_vendors_export_logo($vendor_id, $logo_type, $backup_path)
{
    $logo_id = db_get_field(
        "SELECT image_id FROM ?:logos l"
        . " LEFT JOIN ?:images_links i"
            . " ON i.object_type = ?s"
            . " AND i.object_id = l.logo_id"
        . " WHERE l.company_id = ?i"
        . " AND l.type = ?s",
        'logos',
        $vendor_id,
        $logo_type
    );

    return fn_exim_export_image($logo_id, 'logos', $backup_path, false);
}

/**
 * Imports logo from file.
 *
 * @param string $vendor_email Vendor email
 * @param string $logo_type    Logo type ('theme' or 'mail')
 * @param string $logo_path    Image path
 * @param string $backup_path  Path to the image
 *
 * @return bool Always true
 */
function fn_exim_vendors_import_logo($vendor_email, $logo_type, $logo_path, $backup_path)
{
    $vendor_id = fn_get_company_id('companies', 'email', $vendor_email);

    $image_path = fn_find_file($backup_path, $logo_path);

    if (!$image_path) {
        return true;
    }

    fn_update_logo(array(
        'image_path' => $image_path,
        'type'       => $logo_type,
    ), $vendor_id);

    return true;
}

/**
 * Gets language of the vendor.
 *
 * @param int $vendor_id Vendor identifier
 *
 * @return string Language code
 */
function fn_exim_vendors_export_language($vendor_id)
{
    static $company_languages;
    if (!$company_languages) {
        $company_languages = db_get_hash_single_array(
            "SELECT company_id, lang_code FROM ?:companies",
            array('company_id', 'lang_code')
        );
    }

    return $company_languages[$vendor_id];
}

/**
 * Sets language of the vendor.
 *
 * @param string $vendor_email Vendor email
 * @param string $lang_code Language code
 *
 * @return bool Always true
 */
function fn_exim_vendors_import_language($vendor_email, $lang_code)
{
    static $languages;
    if (!$languages) {
        $languages = Languages::getAvailable();
    }
    if (!isset($languages[$lang_code])) {
        $lang_code = CART_LANGUAGE;
    }

    $vendor_id = fn_get_company_id('companies', 'email', $vendor_email);

    db_query(
        "UPDATE ?:companies"
        . " SET lang_code = ?s"
        . " WHERE company_id = ?s",
        $lang_code,
        $vendor_id
    );

    return true;
}

/**
 * Sets status for imported vendors.
 *
 * @param array $vendor Vendor data
 *
 * @return bool Always true
 */
function fn_exim_vendors_import_status(&$vendor)
{
    $vendor_id = fn_get_company_id('companies', 'email', $vendor['email']);

    // set to 'New'
    if (!$vendor_id && (empty($vendor['status']) || $vendor['status'] == 'N')) {
        $vendor['status'] = 'N';
        $vendor['request_account_data'] = serialize(array('fields' => ''));
    }

    return true;
}

/**
 * Sets shippings for imported vendors.
 *
 * @param array<string,string> $vendor Vendor data
 *
 * @return bool Always true
 */
function fn_exim_vendors_set_shippings(array &$vendor)
{
    $vendor_id = fn_get_company_id('companies', 'email', $vendor['email']);

    if (!$vendor_id && !isset($vendor['shippings'])) {
        $shippings = Shippings::getShippingIdsAvailableForNewVendors(true);
        if (!empty($shippings)) {
            $vendor['shippings'] = implode(',', $shippings);
        } else {
            $vendor['shippings']  = '';
        }
    }

    return true;
}

/**
 * Initializes vendor logotypes (for generating "object_id" sake)
 *
 * @param array $primary_object_id Imported company id
 * @param array $company           Imported data
 * @param bool  $object_exists     Existence flag
 */
function fn_exim_vendor_init_logos($primary_object_id, $company, $object_exists)
{
    if (!$object_exists
        && isset($primary_object_id['company_id'])
    ) {
        $theme_name = fn_get_theme_path('[theme]', 'C');
        fn_create_theme_logos_by_layout_id($theme_name, 0, $primary_object_id['company_id'], true);
    }
}
