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

use Tygh\Addons\Recaptcha\RecaptchaDriver;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */
if (defined('INSTALLER_INITED') || defined('INSTALLER_STARTED')) {
    return $schema;
}

/** @var \Tygh\Web\Antibot $antibot */
$antibot = Tygh::$app['antibot'];

if (!($antibot->getDriver() instanceof RecaptchaDriver)) {
    return $schema;
}

$schema['services']['recaptcha'] = [
    'purposes' => ['strictly_necessary'],
    'name' => 'recaptcha',
    'translations' => [
        'zz' => [
            'title' => 'recaptcha.recaptcha_cookie_title',
            'description' => 'recaptcha.recaptcha_cookie_description'
        ],
    ],
    'required' => true,
];

return $schema;
