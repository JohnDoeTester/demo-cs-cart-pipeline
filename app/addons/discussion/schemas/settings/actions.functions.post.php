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

if (Registry::get('addons.discussion.status') !== 'A') {
    /** workaround (see 1-22725 1-13030) */
    /** @var \Composer\Autoload\ClassLoader $class_loader */
    $class_loader = Tygh::$app['class_loader'];
    $class_loader->add('', Registry::get('config.dir.addons') . 'discussion');
}

/**
 * Check if mod_rewrite is active and clean up templates cache
 */
function fn_settings_actions_addons_discussion_home_page_testimonials(&$new_value, $old_value)
{
    if (function_exists('fn_create_empty_thread')) {
        fn_create_empty_thread($new_value);
    }

    return true;
}

function fn_settings_actions_addons_discussion_company_discussion_type(&$new_value, $old_value)
{
    db_query('UPDATE ?:discussion SET type = ?s WHERE object_type = ?s', $new_value, 'M');
}

function fn_settings_variants_addons_discussion_product_discussion_type()
{
    return fn_discussion_get_discussion_types();
}

function fn_settings_variants_addons_discussion_category_discussion_type()
{
    return fn_discussion_get_discussion_types();
}

function fn_settings_variants_addons_discussion_page_discussion_type()
{
    return fn_discussion_get_discussion_types();
}

function fn_settings_variants_addons_discussion_home_page_testimonials()
{
    return fn_discussion_get_discussion_types();
}

function fn_settings_variants_addons_discussion_company_discussion_type()
{
    return fn_discussion_get_discussion_types();
}