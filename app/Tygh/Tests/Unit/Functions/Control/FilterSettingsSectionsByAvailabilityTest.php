<?php

namespace Tygh\Tests\Unit\Functions\Control;

use Tygh\Tests\Unit\ATestCase;

class FilterSettingsSectionsByAvailabilityTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    public function setUp(): void
    {
        $this->requireCore('functions/fn.control.php');
    }

    /**
     * @dataProvider dpGeneral
     */
    public function testGeneral($sections, $accessible_sections, $expected)
    {
        $actual = fn_filter_settings_sections_by_accessibility($sections, $accessible_sections);
        $this->assertEquals($expected, $actual);
    }

    public function dpGeneral()
    {
        return [
            [
                [],
                [],
                [],
            ],
            [
                [
                    'General'                    => [
                        'href' => 'settings.manage?section_id=General',
                        'type' => 'setting',
                    ],
                    'Appearance'                 => [
                        'href' => 'settings.manage?section_id=Appearance',
                        'type' => 'setting',
                    ],
                    'Appearance_divider'         => [
                        'type' => 'divider',
                    ],
                    'Company'                    => [
                        'href' => 'settings.manage?section_id=Company',
                        'type' => 'setting',
                    ],
                    'Checkout'                   => [
                        'href' => 'settings.manage?section_id=Checkout',
                        'type' => 'setting',
                    ],
                    'Emails'                     => [
                        'href' => 'settings.manage?section_id=Emails',
                        'type' => 'setting',
                    ],
                    'Thumbnails'                 => [
                        'href' => 'settings.manage?section_id=Thumbnails',
                        'type' => 'setting',
                    ],
                    'Sitemap'                    => [
                        'href' => 'settings.manage?section_id=Sitemap',
                        'type' => 'setting',
                    ],
                    'Vendors'                    => [
                        'href' => 'settings.manage?section_id=Vendors',
                        'type' => 'setting',
                    ],
                    'Upgrade_center'             => [
                        'href' => 'settings.manage?section_id=Upgrade_center',
                        'type' => 'setting',
                    ],
                    'Upgrade_center_divider'     => [
                        'type' => 'divider',
                    ],
                    'Security'                   => [
                        'href' => 'settings.manage?section_id=Security',
                        'type' => 'setting',
                    ],
                    'Image_verification_divider' => [
                        'type' => 'divider',
                    ],
                    'Logging'                    => [
                        'href' => 'settings.manage?section_id=Logging',
                        'type' => 'setting',
                    ],
                    'Reports'                    => [
                        'href' => 'settings.manage?section_id=Reports',
                        'type' => 'setting',
                    ],
                    'Reports_divider'            => [
                        'type' => 'divider',
                    ],
                    'settings_wizard'            => [
                        'href'  => 'settings_wizard.view',
                        'title' => 'Settings wizard',
                    ],
                    'store_mode'                 => [
                        'type' => 'title',
                        'href' => 'settings.change_store_mode',
                    ],
                ],
                [
                    'Appearance'         => [],
                    'Checkout'           => [],
                    'General'            => [],
                    'Image_verification' => [],
                    'Security'           => [],
                    'Sitemap'            => [],
                    'Thumbnails'         => [],
                    'Vendors'            => [],
                ],
                [
                    'General'                => [
                        'href' => 'settings.manage?section_id=General',
                        'type' => 'setting',
                    ],
                    'Appearance'             => [
                        'href' => 'settings.manage?section_id=Appearance',
                        'type' => 'setting',
                    ],
                    'Appearance_divider'     => [
                        'type' => 'divider',
                    ],
                    'Checkout'               => [
                        'href' => 'settings.manage?section_id=Checkout',
                        'type' => 'setting',
                    ],
                    'Thumbnails'             => [
                        'href' => 'settings.manage?section_id=Thumbnails',
                        'type' => 'setting',
                    ],
                    'Sitemap'                => [
                        'href' => 'settings.manage?section_id=Sitemap',
                        'type' => 'setting',
                    ],
                    'Vendors'                => [
                        'href' => 'settings.manage?section_id=Vendors',
                        'type' => 'setting',
                    ],
                    'Upgrade_center_divider' => [
                        'type' => 'divider',
                    ],
                    'Security'               => [
                        'href' => 'settings.manage?section_id=Security',
                        'type' => 'setting',
                    ],
                ],
            ],

            [
                [
                    'General'            => [
                        'href' => 'settings.manage?section_id=General',
                        'type' => 'setting',
                    ],
                    'Appearance'         => [
                        'href' => 'settings.manage?section_id=Appearance',
                        'type' => 'setting',
                    ],
                    'Appearance_divider' => [
                        'type' => 'divider',
                    ],
                    'Emails'             => [
                        'href' => 'settings.manage?section_id=Emails',
                        'type' => 'setting',
                    ],
                    'Emails_divider'     => [
                        'type' => 'divider',
                    ],
                    'Company'            => [
                        'href' => 'settings.manage?section_id=Company',
                        'type' => 'setting',
                    ],
                    'Company_divider'    => [
                        'type' => 'divider',
                    ],
                    'Logging'            => [
                        'href' => 'settings.manage?section_id=Logging',
                        'type' => 'setting',
                    ],
                ],
                [
                    'Appearance'         => [],
                    'Checkout'           => [],
                    'General'            => [],
                    'Image_verification' => [],
                    'Security'           => [],
                    'Sitemap'            => [],
                    'Thumbnails'         => [],
                    'Vendors'            => [],
                ],
                [
                    'General'            => [
                        'href' => 'settings.manage?section_id=General',
                        'type' => 'setting',
                    ],
                    'Appearance'         => [
                        'href' => 'settings.manage?section_id=Appearance',
                        'type' => 'setting',
                    ],
                ],
            ],
        ];
    }
}