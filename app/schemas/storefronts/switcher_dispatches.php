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

$schema = [
    'usergroups'             => false,
    'companies.manage'       => false,
    'companies.update'       => false,
    'companies.add'          => false,
    'companies.merge'        => false,
    'storefronts'            => false,
    'taxes'                  => false,
    'countries'              => false,
    'states'                 => false,
    'datakeeper'             => false,
    'destinations'           => false,
    'statuses'               => false,
    'profile_fields'         => false,
    'currencies'             => false,
    'languages.manage'       => false,
    'languages.install_list' => false,
    'upgrade_center'         => false,
    'tools.view_changes'     => false,
    'settings_wizard.view'   => false,
    'email_templates'        => false,
    'documents'              => false,
    'storage.cdn'            => false,
    'notification_settings'  => false,

    'settings.manage' => [
        'allow'    => true,
        'variants' => [
            'store'          => [
                'allow'  => false,
                'params' => ['section_id' => 'Stores']
            ],
            'upgrade_center' => [
                'allow'  => false,
                'params' => ['section_id' => 'Upgrade_center']
            ]
        ]
    ],

    'exim' => [
        'allow'    => true,
        'variants' => [
            'states' => [
                'allow'  => false,
                'params' => ['section' => 'states']
            ]
        ]
    ]
];

if (Registry::ifGet('settings.Appearance.email_templates', 'new') === 'old') {
    $schema['statuses'] = true;
}

return $schema;
