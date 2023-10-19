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

defined('BOOTSTRAP') or die('Access denied');

$schema = array(
    'checkout_place_order'     => array(
        'description_langvar'     => 'gdpr.checkout.place_order.personal_data_processing',
        'full_agreement_langvar'  => 'gdpr.checkout.place_order.agreement_text_full_personal_data_processing',
        'short_agreement_langvar' => 'gdpr.checkout.place_order.agreement_text_short_personal_data_processing',
    ),
    'checkout_profiles_update' => array(
        'description_langvar'     => 'gdpr.checkout.profiles_update.personal_data_processing',
        'full_agreement_langvar'  => 'gdpr.checkout.profiles_update.agreement_text_full_personal_data_processing',
        'short_agreement_langvar' => 'gdpr.checkout.profiles_update.agreement_text_short_personal_data_processing',
    ),
    'user_registration' => array(
        'description_langvar'     => 'gdpr.user.registration.personal_data_processing',
        'full_agreement_langvar'  => 'gdpr.user.registration.agreement_text_full_personal_data_processing',
        'short_agreement_langvar' => 'gdpr.user.registration.agreement_text_short_personal_data_processing',
    ),
    'profiles_update'          => array(
        'description_langvar'     => 'gdpr.profiles.update.personal_data_processing',
        'full_agreement_langvar'  => 'gdpr.profiles.update.agreement_text_full_personal_data_processing',
        'short_agreement_langvar' => 'gdpr.profiles.update.agreement_text_short_personal_data_processing',
    ),
    'product_subscription'     => array(
        'description_langvar'     => 'gdpr.products.subscribe.personal_data_processing',
        'full_agreement_langvar'  => 'gdpr.products.subscribe.agreement_text_full_personal_data_processing',
        'short_agreement_langvar' => 'gdpr.products.subscribe.agreement_text_short_personal_data_processing',
    ),
);

if (fn_allowed_for('MULTIVENDOR')) {
    $schema['apply_for_vendor'] = array(
        'description_langvar'      => 'gdpr.vendor.apply.personal_data_processing',
        'full_agreement_langvar'   => 'gdpr.vendor.apply.agreement_text_full_personal_data_processing',
        'short_agreement_langvar'  => 'gdpr.vendor.apply.agreement_text_short_personal_data_processing',
    );
}

return $schema;
