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
use Tygh\Enum\ProductFeatures;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Languages\Languages;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Storefront\Storefront;
use Tygh\Tools\SecurityHelper;

defined('BOOTSTRAP') or die('Access denied');

function fn_get_google_sitemap_company_condition($field)
{
    if (fn_allowed_for('ULTIMATE')) {
        return fn_get_company_condition($field);
    }

    return '';
}

/**
 * Generates a set of links to the sitemap entry.
 *
 * @param string     $type          Entry type
 * @param int|string $id            Entry unique identifier
 * @param string[]   $languages     List of languages to generate the sitemap for
 * @param array      $extra         Additional link parameters
 * @param int        $storefront_id Storefront idenfitier to generate the sitemap for
 *
 * @return string[]
 */
function fn_google_sitemap_generate_link($type, $id, $languages, $extra = [], $storefront_id = null)
{
    switch ($type) {
        case 'product':
            $link = 'products.view?product_id=' . $id;
            break;
        case 'category':
            $link = 'categories.view?category_id=' . $id;
            break;
        case 'page':
            $link = 'pages.view?page_id=' . $id;
            break;
        case 'extended':
            $link = 'product_features.view?variant_id=' . $id;
            break;
        case 'companies':
            $link = 'companies.view?company_id=' . $id;
            break;
        case 'vendor_store':
            $link = 'companies.products?company_id=' . $id;
            break;
        case 'index':
            $link = '';
            break;
        default:
            /**
             * @deprecated since 4.11.1. Use google_sitemap_generate_link_get_object_link instead.
             */
            fn_set_hook('sitemap_link_object', $link, $type, $id);

            /**
             * Executes when generating a sitemap entry link. Allows you to generate link for custom sitemap entries
             *
             * @param string     $type          Entry type
             * @param int|string $id            Entry unique identifier
             * @param string[]   $languages     List of languages to generate the sitemap for
             * @param array      $extra         Additional link parameters
             * @param int        $storefront_id Storefront idenfitier to generate the sitemap for
             * @param string     $link          Entry link
             */
            fn_set_hook('google_sitemap_generate_link_get_object_link', $type, $id, $languages, $extra, $storefront_id, $link);
    }

    $links = [];
    $query_separator = strpos($link, '?') !== false ? '&' : '?';
    if (count($languages) === 1) {
        $links['main_link'] = fn_url(
            $link . $query_separator . 'storefront_id=' . $storefront_id,
            SiteArea::STOREFRONT,
            fn_get_storefront_protocol(),
            key($languages)
        );
    } else {
        $frontend_default_language = Settings::instance(['storefront_id' => (int) $storefront_id])->getValue('frontend_default_language', 'Appearance');
        foreach ($languages as $lang_code => $lang) {
            $links[$lang_code] = fn_url(
                $link . $query_separator . 'sl=' . $lang_code . '&storefront_id=' . $storefront_id,
                SiteArea::STOREFRONT,
                fn_get_storefront_protocol(),
                $lang_code
            );
            if ($lang_code !== $frontend_default_language) {
                continue;
            }
            $links['main_link'] = fn_url(
                $link . $query_separator . 'sl=' . $lang_code . '&storefront_id=' . $storefront_id,
                SiteArea::STOREFRONT,
                fn_get_storefront_protocol(),
                $lang_code
            );
        }
    }

    /**
     * @deprecated since 4.11.1. Use google_sitemap_generate_link_post instead
     */
    fn_set_hook('sitemap_link', $link, $type, $id, $languages, $links);

    /**
     * Executes when generating a sitemap entry links after a set of links is generated.
     * Allows you to modify the generated set of links
     *
     * @param string                $type          Entry type
     * @param int|string            $id            Entry unique identifier
     * @param string[]              $languages     List of languages to generate the sitemap for
     * @param array                 $extra         Additional link parameters
     * @param int                   $storefront_id Storefront idenfitier to generate the sitemap for
     * @param array<string, string> $links         Entry links
     */
    fn_set_hook('google_sitemap_generate_link_post', $type, $id, $languages, $extra, $storefront_id, $links);

    return $links;
}

function fn_google_sitemap_print_item_info($links, $last_modified_time, $frequency, $priority)
{

    $links['main_link'] = SecurityHelper::escapeHtml($links['main_link']);

    $item = <<<ITEM
    <url>
        <loc>{$links['main_link']}</loc>

ITEM;

    if (count($links) > 1) {
        foreach ($links as $lang_code => $link) {
            if ($lang_code === 'main_link') {
                continue;
            }
            $link = SecurityHelper::escapeHtml($link);
            $item .= <<<ITEM
        <xhtml:link rel="alternate" hreflang="{$lang_code}" href="{$link}"/>

ITEM;
        }
    }

    $additional_info = <<<ITEM
        <lastmod>{$last_modified_time}</lastmod>
        <changefreq>{$frequency}</changefreq>
        <priority>{$priority}</priority>
    </url>

ITEM;

    return $item . $additional_info;
}

function fn_google_sitemap_get_frequency()
{
    $frequency = array(
        'always' => __('always'),
        'hourly' => __('hourly'),
        'daily' => __('daily'),
        'weekly' => __('weekly'),
        'monthly' => __('monthly'),
        'yearly' => __('yearly'),
        'never' => __('never'),
    );

    return $frequency;
}

function fn_google_sitemap_get_priority()
{
    $priority = array();

    for ($i = 0.1; $i <= 1; $i += 0.1) {
        $priority[(string) $i] = (string) $i;
    }

    return $priority;
}

/**
 * Generates sitemaps.
 *
 * @param array<int>|null $storefront_ids Storefront to generate sitemap for
 */
function fn_google_sitemap_get_content(array $storefront_ids = null)
{
    $storefronts = fn_google_sitemap_get_storefronts($storefront_ids);

    $parts = 0;
    foreach ($storefronts as $storefront) {
        // homepage is always written into a sitemap, thus progress counter contains at least one record
        $parts++;
        $sitemap_settings = Settings::instance(['storefront_id' => $storefront->storefront_id])->getValues('google_sitemap', Settings::ADDON_SECTION, false);
        $parts += YesNo::toBool($sitemap_settings['include_categories']);
        $parts += YesNo::toBool($sitemap_settings['include_products']);
        $parts += YesNo::toBool($sitemap_settings['include_pages']);
        $parts += YesNo::toBool($sitemap_settings['include_extended']);
        $parts += fn_allowed_for('MULTIVENDOR') && YesNo::toBool($sitemap_settings['include_companies']);
    }

    fn_set_progress('parts', $parts);

    foreach ($storefronts as $storefront) {
        /** @var array<string, int|string> $sitemap_settings */
        $sitemap_settings = Settings::instance(['storefront_id' => $storefront->storefront_id])->getValues('google_sitemap', Settings::ADDON_SECTION, false);
        fn_google_sitemap_generate_sitemap_for_storefront($storefront, $sitemap_settings);
    }

    fn_set_notification('N', __('notice'), __('google_sitemap.map_generated'));
    exit();
}

function fn_google_sitemap_check_counter(&$file, &$link_counter, &$file_counter, $links, $header, $footer, $type, Storefront $storefront)
{
    $file_info = [
        'size' => 0,
    ];
    if ($file !== null) {
        $file_info = fstat($file);
    }

    if ($file === null
        || (count($links) + $link_counter) > MAX_URLS_IN_MAP
        || $file_info['size'] >= MAX_SIZE_IN_KBYTES * 1024
    ) {
        if ($file !== null) {
            fwrite($file, $footer);
            fclose($file);
        }

        $file_counter++;

        $file = fopen(fn_google_sitemap_get_sitemap_path($storefront->storefront_id, $file_counter), 'wb');
        $link_counter = count($links);
        fwrite($file, $header);
    } else {
        $link_counter += count($links);

        fn_set_progress(
            'echo',
            __('google_sitemap.export_progress_entry', [
                '[object]'     => __($type),
                '[storefront]' => $storefront->name,
            ])
        );
    }
}

/**
 * Generates a storefront sitemap.
 *
 * @param \Tygh\Storefront\Storefront $storefront Storefront to generate sitemap for
 * @param array                       $settings   Sitemap generation settings
 *
 * @return bool
 */
function fn_google_sitemap_generate_sitemap_for_storefront(Storefront $storefront, array $settings)
{
    $get_categories = YesNo::toBool($settings['include_categories']);
    $get_products = YesNo::toBool($settings['include_products']);
    $get_pages = YesNo::toBool($settings['include_pages']);
    $get_features = YesNo::toBool($settings['include_extended']);
    $get_companies = fn_allowed_for('MULTIVENDOR') && YesNo::toBool($settings['include_companies']);

    $last_modified_time = date('Y-m-d', TIME);

    $sitemap_header = <<<HEAD
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xmlns:xhtml="http://www.w3.org/1999/xhtml"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

HEAD;

    $sitemap_footer = <<<FOOT

</urlset>
FOOT;

    $file = null;
    $link_counter = 0;
    $file_counter = 0;

    fn_google_sitemap_recreate_sitemap_dir($storefront->storefront_id);

    $languages = fn_google_sitemap_get_sitemap_languages($storefront);

    list($file, $link_counter, $file_counter) = fn_google_sitemap_write_homepage_to_sitemap(
        $storefront,
        $last_modified_time,
        $settings['site_change'],
        $settings['site_priority'],
        $file,
        $link_counter,
        $file_counter,
        $sitemap_header,
        $sitemap_footer,
        $languages
    );

    if ($get_categories) {
        list($file, $link_counter, $file_counter) = fn_google_sitemap_write_categories_to_sitemap(
            $storefront,
            $last_modified_time,
            $settings['categories_change'],
            $settings['categories_priority'],
            $file,
            $link_counter,
            $file_counter,
            $sitemap_header,
            $sitemap_footer,
            $languages
        );
    }

    if ($get_products) {
        list($file, $link_counter, $file_counter) = fn_google_sitemap_write_products_to_sitemap(
            $storefront,
            $last_modified_time,
            $settings['products_change'],
            $settings['products_priority'],
            $file,
            $link_counter,
            $file_counter,
            $sitemap_header,
            $sitemap_footer,
            $languages
        );
    }

    if ($get_pages) {
        list($file, $link_counter, $file_counter) = fn_google_sitemap_write_pages_to_sitemap(
            $storefront,
            $last_modified_time,
            $settings['pages_change'],
            $settings['pages_priority'],
            $file,
            $link_counter,
            $file_counter,
            $sitemap_header,
            $sitemap_footer,
            $languages
        );
    }

    if ($get_features) {
        list($file, $link_counter, $file_counter) = fn_google_sitemap_write_extended_features_to_sitemap(
            $storefront,
            $last_modified_time,
            $settings['extended_change'],
            $settings['extended_priority'],
            $file,
            $link_counter,
            $file_counter,
            $sitemap_header,
            $sitemap_footer,
            $languages
        );
    }

    if ($get_companies) {
        list($file, $link_counter, $file_counter) = fn_google_sitemap_write_companies_to_sitemap(
            $storefront,
            $last_modified_time,
            $settings['companies_change'],
            $settings['companies_priority'],
            $file,
            $link_counter,
            $file_counter,
            $sitemap_header,
            $sitemap_footer,
            $languages
        );
    }

    /**
     * @deprecated since 4.11.1. Use google_sitemap_generate_sitemap_for_storefront_after_items
     */
    fn_set_hook('sitemap_item', $settings, $file, $last_modified_time, $link_counter, $file_counter);

    /**
     * Executes after a sitemap for the storefront is created. Allows you to write additional items into the sitemap.
     *
     * @param \Tygh\Storefront\Storefront $storefront         Storefront to generate sitemap for
     * @param array                       $settings           Sitemap generation settings
     * @param resource                    $file               File the sitemap is written into
     * @param string                      $last_modified_time Sitemap's last modified time in format YYYY-MM-DD
     * @param int                         $link_counter       Amount of links in the current sitemap file
     * @param int                         $file_counter       Amount of sitemap files
     */
    fn_set_hook('google_sitemap_generate_sitemap_for_storefront_after_items', $storefront, $settings, $file, $last_modified_time, $link_counter, $file_counter);

    fwrite($file, $sitemap_footer);
    fclose($file);

    if ($file_counter > 1) {
        return fn_google_sitemap_create_sitemap_index($storefront, $last_modified_time, $file_counter);
    }

    return fn_rename(
        fn_google_sitemap_get_sitemap_path($storefront->storefront_id, $file_counter),
        fn_google_sitemap_get_sitemap_path($storefront->storefront_id)
    );
}

/**
 * Gets a full path to the directory where the sitemap is stored.
 *
 * @param int $storefront_id Storefront identifier to generate sitemap for
 *
 * @return string
 */
function fn_google_sitemap_get_sitemap_dir($storefront_id)
{
    $suffix = '';
    // files are stored in the company directory in ULTIMATE
    if (fn_allowed_for('MULTIVENDOR')) {
        $suffix = $storefront_id . '/';
    }

    return fn_get_files_dir_path() . 'google_sitemap/' . $suffix;
}

/**
 * Gets a full path to the sitemap file.
 *
 * @param int $storefront_id Storefront identifier to generate sitemap for
 * @param int $index         Sitemap file numerical order
 *
 * @return string
 */
function fn_google_sitemap_get_sitemap_path($storefront_id, $index = null)
{
    if (!$index) {
        return fn_google_sitemap_get_sitemap_dir($storefront_id) . 'sitemap.xml';
    }

    return fn_google_sitemap_get_sitemap_dir($storefront_id) . 'sitemap' . $index . '.xml';
}

/**
 * Gets a list of languages to generate the sitemap for.
 *
 * @param \Tygh\Storefront\Storefront $storefront Storefront to generate sitemap for
 *
 * @return string[] Language names
 */
function fn_google_sitemap_get_sitemap_languages(Storefront $storefront)
{
    $languages_conditions = [
        'area'           => 'C',
        'include_hidden' => false,
        'storefront_id'  => empty($storefront->storefront_id) ? null : $storefront->storefront_id
    ];

    $languages = array_map(function($language) {
        return $language['name'];
    }, Languages::getAvailable($languages_conditions));

    return $languages;
}

/**
 * Writes storefront's categories into the sitemap.
 *
 * @param \Tygh\Storefront\Storefront $storefront         Storefront to generate sitemap for
 * @param string                      $last_modified_time Sitemap's last modified time in format YYYY-MM-DD
 * @param string                      $change_frequency   Sitemap item's update frequency
 * @param float                       $priority           Sitemap item's priority
 * @param resource                    $file               File the sitemap is written into
 * @param int                         $link_counter       Amount of links in the current sitemap file
 * @param int                         $file_counter       Amount of sitemap files
 * @param string                      $sitemap_header     Sitemap header
 * @param string                      $sitemap_footer     Sitemap footer
 * @param string[]                    $languages          List of languages to generate the sitemap for
 *
 * @return array
 *
 * @internal
 */
function fn_google_sitemap_write_categories_to_sitemap(
    Storefront $storefront,
    $last_modified_time,
    $change_frequency,
    $priority,
    $file,
    $link_counter,
    $file_counter,
    $sitemap_header,
    $sitemap_footer,
    array $languages
) {

    $conditions = [
        'usergroup_ids' => db_quote('FIND_IN_SET(?i, usergroup_ids)', USERGROUP_ALL),
        'status'        => db_quote('AND status = ?s', ObjectStatuses::ACTIVE),
        'company_id'    => fn_get_google_sitemap_company_condition('?:categories.company_id')
    ];

    $conditions['storefront_id'] = db_quote('AND ?:categories.storefront_id IN (?n)', [0, $storefront->storefront_id]);

    $categories = db_get_fields(
        'SELECT category_id FROM ?:categories WHERE ?p',
        implode(' ', array_filter($conditions))
    );

    fn_set_progress('step_scale', count($categories));

    //Add the all active categories
    foreach ($categories as $category) {
        $links = fn_google_sitemap_generate_link('category', $category, $languages, [], $storefront->storefront_id);
        $item = fn_google_sitemap_print_item_info($links, $last_modified_time, $change_frequency, $priority);

        fn_google_sitemap_check_counter($file, $link_counter, $file_counter, $links, $sitemap_header, $sitemap_footer, 'categories', $storefront);

        fwrite($file, $item);
    }

    return [$file, $link_counter, $file_counter];
}

/**
 * Writes storefront's products into the sitemap.
 *
 * @param \Tygh\Storefront\Storefront $storefront         Storefront to generate sitemap for
 * @param string                      $last_modified_time Sitemap's last modified time in format YYYY-MM-DD
 * @param string                      $change_frequency   Sitemap item's update frequency
 * @param float                       $priority           Sitemap item's priority
 * @param resource                    $file               File the sitemap is written into
 * @param int                         $link_counter       Amount of links in the current sitemap file
 * @param int                         $file_counter       Amount of sitemap files
 * @param string                      $sitemap_header     Sitemap header
 * @param string                      $sitemap_footer     Sitemap footer
 * @param string[]                    $languages          List of languages to generate the sitemap for
 *
 * @return array
 *
 * @internal
 */
function fn_google_sitemap_write_products_to_sitemap(
    Storefront $storefront,
    $last_modified_time,
    $change_frequency,
    $priority,
    $file,
    $link_counter,
    $file_counter,
    $sitemap_header,
    $sitemap_footer,
    array $languages
) {
    $products_per_page = ITEMS_PER_PAGE;
    $page = 0;

    $params = [
        'custom_extend'     => ['categories'],
        'sort_by'           => 'null',
        'only_short_fields' => true,
        'area'              => 'C',
        'storefront'        => $storefront
    ];

    $query_condition = [
        'status' => ObjectStatuses::ACTIVE
    ];

    $original_auth = Tygh::$app['session']['auth'];
    Tygh::$app['session']['auth'] = fn_fill_auth([], [], false, 'C');

    /**
     * Gets params before getting products.
     *
     * @param \Tygh\Storefront\Storefront                                $storefront         Storefront to generate sitemap for
     * @param string                                                     $last_modified_time Sitemap's last modified time in format YYYY-MM-DD
     * @param string                                                     $change_frequency   Sitemap item's update frequency
     * @param float                                                      $priority           Sitemap item's priority
     * @param resource                                                   $file               File the sitemap is written into
     * @param int                                                        $link_counter       Amount of links in the current sitemap file
     * @param int                                                        $file_counter       Amount of sitemap files
     * @param string                                                     $sitemap_header     Sitemap header
     * @param string                                                     $sitemap_footer     Sitemap footer
     * @param string[]                                                   $languages          List of languages to generate the sitemap for
     * @param array<int, array<string, string|int|null|array<int, int>>> $products           Products data
     * @param array<string, string|int|null|array<int, int>>             $product            Product data
     * @param int                                                        $page               Page number
     * @param array<string, string|bool|array<int, string>>              $params             Additional parameters
     * @param array<string, string>                                      $query_condition    Query condition
     */
    fn_set_hook(
        'google_sitemap_write_products_to_sitemap_before_getting_products',
        $storefront,
        $last_modified_time,
        $change_frequency,
        $priority,
        $file,
        $link_counter,
        $file_counter,
        $sitemap_header,
        $sitemap_footer,
        $languages,
        $products,
        $product,
        $page,
        $params,
        $query_condition
    );

    fn_set_progress('step_scale', db_get_field('SELECT COUNT(*) FROM ?:products WHERE ?w', $query_condition));

    while (
        $params['pid'] = db_get_fields(
            'SELECT product_id FROM ?:products WHERE ?w ORDER BY product_id ASC ?p',
            $query_condition,
            db_paginate($page, $products_per_page)
        )
    ) {
        $page++;

        list($products) = fn_get_products($params, $products_per_page);

        foreach ($products as $product) {
            $sitemap_items = [
                $product['product_id'] => $product
            ];

            /**
             * Gets product before getting product link.
             *
             * @param \Tygh\Storefront\Storefront                                $storefront         Storefront to generate sitemap for
             * @param string                                                     $last_modified_time Sitemap's last modified time in format YYYY-MM-DD
             * @param string                                                     $change_frequency   Sitemap item's update frequency
             * @param float                                                      $priority           Sitemap item's priority
             * @param resource                                                   $file               File the sitemap is written into
             * @param int                                                        $link_counter       Amount of links in the current sitemap file
             * @param int                                                        $file_counter       Amount of sitemap files
             * @param string                                                     $sitemap_header     Sitemap header
             * @param string                                                     $sitemap_footer     Sitemap footer
             * @param string[]                                                   $languages          List of languages to generate the sitemap for
             * @param array<int, array<string, string|int|null|array<int, int>>> $products           Products data
             * @param array<string, string|int|null|array<int, int>>             $product            Product data
             * @param int                                                        $page               Page number
             * @param array<string, string|bool|array<int, string>>              $params             Additional parameters
             * @param array<int, array<string, string|int|null|array<int, int>>> $sitemap_items      Additional products in sitemap
             */
            fn_set_hook(
                'google_sitemap_write_products_to_sitemap_before_getting_link',
                $storefront,
                $last_modified_time,
                $change_frequency,
                $priority,
                $file,
                $link_counter,
                $file_counter,
                $sitemap_header,
                $sitemap_footer,
                $languages,
                $products,
                $product,
                $page,
                $params,
                $sitemap_items
            );

            foreach ($sitemap_items as $sitemap_item) {
                $links = fn_google_sitemap_generate_link('product', $sitemap_item['product_id'], $languages, [], $storefront->storefront_id);

                if (empty($links)) {
                    continue;
                }

                $item = fn_google_sitemap_print_item_info($links, $last_modified_time, $change_frequency, $priority);

                fn_google_sitemap_check_counter($file, $link_counter, $file_counter, $links, $sitemap_header, $sitemap_footer, 'products', $storefront);

                fwrite($file, $item);
            }
        }
    }
    unset($products);

    Tygh::$app['session']['auth'] = $original_auth;

    return [$file, $link_counter, $file_counter];
}

/**
 * Writes storefront's pages into the sitemap.
 *
 * @param \Tygh\Storefront\Storefront $storefront         Storefront to generate sitemap for
 * @param string                      $last_modified_time Sitemap's last modified time in format YYYY-MM-DD
 * @param string                      $change_frequency   Sitemap item's update frequency
 * @param float                       $priority           Sitemap item's priority
 * @param resource                    $file               File the sitemap is written into
 * @param int                         $link_counter       Amount of links in the current sitemap file
 * @param int                         $file_counter       Amount of sitemap files
 * @param string                      $sitemap_header     Sitemap header
 * @param string                      $sitemap_footer     Sitemap footer
 * @param string[]                    $languages          List of languages to generate the sitemap for
 *
 * @return array
 *
 * @internal
 */
function fn_google_sitemap_write_pages_to_sitemap(
    Storefront $storefront,
    $last_modified_time,
    $change_frequency,
    $priority,
    $file,
    $link_counter,
    $file_counter,
    $sitemap_header,
    $sitemap_footer,
    array $languages
) {
    $page_types = fn_get_page_object_by_type();
    unset($page_types[PAGE_TYPE_LINK]);

    $pages_conditions = [
        'simple'    => true,
        'status'    => ObjectStatuses::ACTIVE,
        'page_type' => array_keys($page_types),
    ];

    if (fn_allowed_for('MULTIVENDOR') && $storefront->getCompanyIds()) {
        $pages_conditions['company_id'] = array_merge([0], $storefront->getCompanyIds());
    }

    list($pages) = fn_get_pages($pages_conditions);

    fn_set_progress('step_scale', count($pages));

    //Add the all active pages
    foreach ($pages as $page) {
        $links = fn_google_sitemap_generate_link('page', $page['page_id'], $languages, [], $storefront->storefront_id);
        $item = fn_google_sitemap_print_item_info($links, $last_modified_time, $change_frequency, $priority);

        fn_google_sitemap_check_counter($file, $link_counter, $file_counter, $links, $sitemap_header, $sitemap_footer, 'pages', $storefront);

        fwrite($file, $item);
    }

    return [$file, $link_counter, $file_counter];
}

/**
 * Writes storefront's homepage into the sitemap.
 *
 * @param \Tygh\Storefront\Storefront $storefront         Storefront to generate sitemap for
 * @param string                      $last_modified_time Sitemap's last modified time in format YYYY-MM-DD
 * @param string                      $change_frequency   Sitemap item's update frequency
 * @param float                       $priority           Sitemap item's priority
 * @param resource                    $file               File the sitemap is written into
 * @param int                         $link_counter       Amount of links in the current sitemap file
 * @param int                         $file_counter       Amount of sitemap files
 * @param string                      $sitemap_header     Sitemap header
 * @param string                      $sitemap_footer     Sitemap footer
 * @param string[]                    $languages          List of languages to generate the sitemap for
 *
 * @return array
 *
 * @internal
 */
function fn_google_sitemap_write_homepage_to_sitemap(
    Storefront $storefront,
    $last_modified_time,
    $change_frequency,
    $priority,
    $file,
    $link_counter,
    $file_counter,
    $sitemap_header,
    $sitemap_footer,
    array $languages = []
) {
    fn_set_progress('step_scale', 1);

    $links = fn_google_sitemap_generate_link('index', '', $languages, [], $storefront->storefront_id);
    $item = fn_google_sitemap_print_item_info($links, $last_modified_time, $change_frequency, $priority);

    fn_google_sitemap_check_counter($file, $link_counter, $file_counter, $links, $sitemap_header, $sitemap_footer, 'storefront', $storefront);

    fwrite($file, $item);

    return [$file, $link_counter, $file_counter];
}

/**
 * Writes storefront's extended features into the sitemap.
 *
 * @param \Tygh\Storefront\Storefront $storefront         Storefront to generate sitemap for
 * @param string                      $last_modified_time Sitemap's last modified time in format YYYY-MM-DD
 * @param string                      $change_frequency   Sitemap item's update frequency
 * @param float                       $priority           Sitemap item's priority
 * @param resource                    $file               File the sitemap is written into
 * @param int                         $link_counter       Amount of links in the current sitemap file
 * @param int                         $file_counter       Amount of sitemap files
 * @param string                      $sitemap_header     Sitemap header
 * @param string                      $sitemap_footer     Sitemap footer
 * @param string[]                    $languages          List of languages to generate the sitemap for
 *
 * @return array
 *
 * @internal
 */
function fn_google_sitemap_write_extended_features_to_sitemap(
    Storefront $storefront,
    $last_modified_time,
    $change_frequency,
    $priority,
    $file,
    $link_counter,
    $file_counter,
    $sitemap_header,
    $sitemap_footer,
    array $languages
) {

    $conditions = [
        'feature_type' => db_quote('?:product_features.feature_type = ?s', ProductFeatures::EXTENDED),
        'status'       => db_quote('?:product_features.status = ?s', ObjectStatuses::ACTIVE)
    ];

    if (fn_allowed_for('MULTIVENDOR') && $storefront->getCompanyIds()) {
        $conditions['company_id'] = db_quote('?:product_features.company_id IN (?n)', array_merge([0], $storefront->getCompanyIds()));
    }

    $extended_features = db_get_fields(
        'SELECT ?:product_feature_variants.variant_id, ?:product_features.feature_id FROM ?:product_features'
        . ' LEFT JOIN ?:product_feature_variants ON (?:product_features.feature_id = ?:product_feature_variants.feature_id)'
        . ' WHERE ?p',
        implode(' AND ', $conditions)
    );

    fn_set_progress('step_scale', count($extended_features));

    //Add the all active extended features
    foreach ($extended_features as $var) {
        $links = fn_google_sitemap_generate_link('extended', $var, $languages, [], $storefront->storefront_id);
        $item = fn_google_sitemap_print_item_info($links, $last_modified_time, $change_frequency, $priority);

        fn_google_sitemap_check_counter($file, $link_counter, $file_counter, $links, $sitemap_header, $sitemap_footer, 'features', $storefront);

        fwrite($file, $item);
    }

    return [$file, $link_counter, $file_counter];
}

/**
 * Writes storefront's companies into the sitemap.
 *
 * @param \Tygh\Storefront\Storefront $storefront         Storefront to generate sitemap for
 * @param string                      $last_modified_time Sitemap's last modified time in format YYYY-MM-DD
 * @param string                      $change_frequency   Sitemap item's update frequency
 * @param float                       $priority           Sitemap item's priority
 * @param resource                    $file               File the sitemap is written into
 * @param int                         $link_counter       Amount of links in the current sitemap file
 * @param int                         $file_counter       Amount of sitemap files
 * @param string                      $sitemap_header     Sitemap header
 * @param string                      $sitemap_footer     Sitemap footer
 * @param string[]                    $languages          List of languages to generate the sitemap for
 *
 * @return array
 *
 * @internal
 */
function fn_google_sitemap_write_companies_to_sitemap(
    Storefront $storefront,
    $last_modified_time,
    $change_frequency,
    $priority,
    $file,
    $link_counter,
    $file_counter,
    $sitemap_header,
    $sitemap_footer,
    array $languages
) {
    $companies_condition = [
        'status' => ObjectStatuses::ACTIVE,
    ];
    if ($storefront->getCompanyIds()) {
        $companies_condition['company_id'] = $storefront->getCompanyIds();
    }

    $companies = db_get_fields(
        'SELECT company_id FROM ?:companies'
        . ' WHERE ?w',
        $companies_condition
    );

    fn_set_progress('step_scale', count($companies));

    if (!empty($companies)) {
        foreach ($companies as $company_id) {
            $links = fn_google_sitemap_generate_link('companies', $company_id, $languages, [], $storefront->storefront_id);
            $item = fn_google_sitemap_print_item_info($links, $last_modified_time, $change_frequency, $priority);

            fn_google_sitemap_check_counter($file, $link_counter, $file_counter, $links, $sitemap_header, $sitemap_footer, 'companies', $storefront);

            fwrite($file, $item);
        }

        if (fn_allowed_for('MULTIVENDOR')) {
            $vendor_stores = $companies;

            /**
             * Gets companies before getting links to vendor stores.
             *
             * @param \Tygh\Storefront\Storefront $storefront         Storefront to generate sitemap for
             * @param string                      $last_modified_time Sitemap's last modified time in format YYYY-MM-DD
             * @param string                      $change_frequency   Sitemap item's update frequency
             * @param float                       $priority           Sitemap item's priority
             * @param resource                    $file               File the sitemap is written into
             * @param int                         $link_counter       Amount of links in the current sitemap file
             * @param int                         $file_counter       Amount of sitemap files
             * @param string                      $sitemap_header     Sitemap header
             * @param string                      $sitemap_footer     Sitemap footer
             * @param string[]                    $languages          List of languages to generate the sitemap for
             * @param array<int, int>             $vendor_stores      List of company IDs
             */
            fn_set_hook(
                'google_sitemap_write_companies_to_sitemap_before_vendor_stores',
                $storefront,
                $last_modified_time,
                $change_frequency,
                $priority,
                $file,
                $link_counter,
                $file_counter,
                $sitemap_header,
                $sitemap_footer,
                $languages,
                $vendor_stores
            );

            if (!empty($vendor_stores)) {
                foreach ($vendor_stores as $company_id) {
                    $links = fn_google_sitemap_generate_link('vendor_store', $company_id, $languages, [], $storefront->storefront_id);
                    $item = fn_google_sitemap_print_item_info($links, $last_modified_time, $change_frequency, $priority);

                    fn_google_sitemap_check_counter($file, $link_counter, $file_counter, $links, $sitemap_header, $sitemap_footer, 'companies', $storefront);

                    fwrite($file, $item);
                }
            }
        }
    }

    return [$file, $link_counter, $file_counter];
}

/**
 * Gets a list of storefronts to generate the sitemap for.
 *
 * @param array<int>|null $storefront_ids Storefront to generate sitemap for
 *
 * @return \Tygh\Storefront\Storefront[]
 */
function fn_google_sitemap_get_storefronts(array $storefront_ids = null)
{
    if ($storefront_ids) {
        list($storefronts,) = StorefrontProvider::getRepository()->find(['storefront_id' => $storefront_ids]);
        return $storefronts;
    }

    if (fn_allowed_for('ULTIMATE')) {
        return [Tygh::$app['storefront']];
    }

    list($storefronts,) = StorefrontProvider::getRepository()->find();

    return $storefronts;
}

/**
 * Recreates a directory to store sitemaps in.
 *
 * @param int $storefront_id Storefront identifier to generate sitemap for
 */
function fn_google_sitemap_recreate_sitemap_dir($storefront_id)
{
    $sitemap_path = fn_google_sitemap_get_sitemap_dir($storefront_id);
    fn_rm($sitemap_path);
    fn_mkdir($sitemap_path);
}

/**
 * Creates an index file for generated sitemaps.
 *
 * @param \Tygh\Storefront\Storefront $storefront         Storefront to generate sitemap for
 * @param string                      $last_modified_time Sitemap's last modified time in format YYYY-MM-DD
 * @param int                         $file_counter       Amount of sitemap files
 *
 * @return bool
 */
function fn_google_sitemap_create_sitemap_index(Storefront $storefront, $last_modified_time, $file_counter)
{
    $maps = '';

    $location = fn_get_storefront_protocol($storefront->storefront_id) . '://' . $storefront->url;
    for ($i = 1; $i <= $file_counter; $i++) {
        $sitemap_location_url = $location . '/sitemap' . $i . '.xml';
        $sitemap_location_url = htmlentities($sitemap_location_url);
        $maps .= <<<MAP
    <sitemap>
        <loc>{$sitemap_location_url}</loc>
        <lastmod>{$last_modified_time}</lastmod>
    </sitemap>

MAP;
    }

    $index_map = <<<HEAD
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

{$maps}
</sitemapindex>
HEAD;

    $file = fopen(fn_google_sitemap_get_sitemap_path($storefront->storefront_id), 'wb');
    fwrite($file, $index_map);

    return fclose($file);
}

/**
 * Provides action buttons for sitemap management.
 *
 * @return string
 *
 * @internal
 */
function fn_google_sitemap_clear_url_info()
{
    // FIXME: Bad style
    $storefront_id = isset($_REQUEST['storefront_id'])
        ? (int) $_REQUEST['storefront_id']
        : 0;

    $regenerate_url = fn_url('xmlsitemap.generate');
    $sitemap_url = fn_url('xmlsitemap.view', SiteArea::STOREFRONT, fn_get_storefront_protocol(null, $storefront_id));
    $storefront_url = fn_google_sitemap_get_storefront_url_for_sitemap_xml();

    if (fn_allowed_for('MULTIVENDOR')) {
        if (
            !$storefront_id
            && StorefrontProvider::getRepository()->getCount() === 1
        ) {
            $storefront = StorefrontProvider::getRepository()->findDefault();
            $storefront_id = $storefront ? $storefront->storefront_id : 0;
        }

        $sitemap_available_in_customer = '';
        if ($storefront_id) {
            $regenerate_url = fn_link_attach($regenerate_url, "storefront_id={$storefront_id}");
            $sitemap_url = fn_url('xmlsitemap.view?storefront_id=' . $storefront_id, SiteArea::STOREFRONT, fn_get_storefront_protocol(null, $storefront_id));
            $storefront_url = fn_google_sitemap_get_storefront_url_for_sitemap_xml($storefront_id);

            $sitemap_available_in_customer = __(
                'sitemap_available_in_customer',
                [
                    '[http_location]' => $storefront_url,
                    '[sitemap_url]'   => $sitemap_url,
                ]
            );
        }

        return __(
            'google_sitemap.text_regenerate',
            [
                '[regenerate_url]'                => $regenerate_url,
                '[sitemap_available_in_customer]' => $sitemap_available_in_customer,
            ]
        );
    }

    if (fn_get_runtime_company_id()) {
        $sitemap_available_in_customer = __(
            'sitemap_available_in_customer',
            [
                '[http_location]' => $storefront_url,
                '[sitemap_url]'   => $sitemap_url,
            ]
        );

        return __(
            'google_sitemap.text_regenerate',
            [
                '[regenerate_url]'                => $regenerate_url,
                '[sitemap_available_in_customer]' => $sitemap_available_in_customer,
            ]
        );
    }

    return __('google_sitemap.text_select_storefront');
}

/**
 * Gets storefront URL for the sitemap.xml file.
 *
 * @param int|null $storefront_id Storefront to get URL for
 *
 * @return string
 *
 * @internal
 */
function fn_google_sitemap_get_storefront_url_for_sitemap_xml($storefront_id = null)
{
    if ($storefront_id) {
        $storefront_url = str_replace(
            '/' . Registry::get('config.customer_index'),
            '',
            fn_url('?storefront_id=' . $storefront_id, SiteArea::STOREFRONT, fn_get_storefront_protocol(null, $storefront_id), null)
        );
    } else {
        $storefront_url = str_replace(
            '/' . Registry::get('config.customer_index'),
            '',
            fn_url('', SiteArea::STOREFRONT, fn_get_storefront_protocol(), null)
        );
    }

    return rtrim($storefront_url, '/');
}
