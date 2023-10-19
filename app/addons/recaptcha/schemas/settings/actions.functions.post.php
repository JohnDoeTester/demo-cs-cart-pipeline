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

/**
 * @return string Notification text displayed at the add-on settings.
 */
function fn_recaptcha_settings_notice_handler()
{
    return __('recaptcha.text_settings_notice');
}

/**
 * @return string Notification text displayed at the add-on settings.
 */
function fn_recaptcha_forbidden_countries_notice_handler()
{
    return __('recaptcha.text_forbidden_countries_notice');
}

/**
 * Provides variants for forbidden countries setting
 *
 * @return array
 */
function fn_settings_variants_addons_recaptcha_forbidden_countries()
{
    return fn_get_simple_countries();
}

