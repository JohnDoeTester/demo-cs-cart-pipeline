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

namespace Tygh\Addons\VendorRating;

use Tygh\Addons\InstallerInterface;
use Tygh\Addons\VendorRating\Enum\Logging;
use Tygh\Core\ApplicationInterface;
use Tygh\Languages\Languages;
use Tygh\Settings;

/**
 * Class Installer provides instructions to install and uninstall the vendor_rating add-on.
 *
 * @package Tygh\Addons\VendorRating
 */
class Installer implements InstallerInterface
{

    /**
     * @var \Tygh\Core\ApplicationInterface
     */
    protected $app;

    public function __construct(ApplicationInterface $app)
    {
        $this->app = $app;
    }

    /**
     * @inheritDoc
     */
    public static function factory(ApplicationInterface $app)
    {
        return new self($app);
    }

    /**
     * @inheritDoc
     */
    public function onInstall()
    {
        $this->addLoggingSetting();
    }

    /**
     * Adds logging setting on add-on installation.
     */
    protected function addLoggingSetting()
    {
        $setting_name = 'log_type_' . Logging::LOG_TYPE_VENDOR_RATING;
        $setting = Settings::instance()->getSettingDataByName($setting_name);
        $logging_section = Settings::instance()->getSectionByName('Logging');
        $lang_codes = array_keys(Languages::getAll());

        if ($setting) {
            return;
        }

        $setting = [
            'name'           => $setting_name,
            'section_id'     => $logging_section['section_id'],
            'section_tab_id' => 0,
            'type'           => 'N',
            'position'       => 10,
            'is_global'      => 'N',
            'edition_type'   => 'ROOT',
        ];

        $descriptions = [];
        foreach ($lang_codes as $lang_code) {
            $descriptions[] = [
                'object_type' => Settings::SETTING_DESCRIPTION,
                'lang_code'   => $lang_code,
                'value'       => __('log_type_vendor_rating'),
            ];
        }

        $setting_id = Settings::instance()->update($setting, null, $descriptions, true);
        foreach (Logging::getActions() as $position => $variant) {
            $variant_id = Settings::instance()->updateVariant(
                [
                    'object_id' => $setting_id,
                    'name'      => $variant,
                    'position'  => $position,
                ]
            );

            foreach ($lang_codes as $lang_code) {
                $description = [
                    'object_id'   => $variant_id,
                    'object_type' => Settings::VARIANT_DESCRIPTION,
                    'lang_code'   => $lang_code,
                    'value'       => __('log_action_' . $variant),
                ];
                Settings::instance()->updateDescription($description);
            }
        }

        Settings::instance()->updateValue($setting_name, '#M#' . Logging::ACTION_FAILURE . '=Y', 'Logging');
    }

    /**
     * @inheritDoc
     */
    public function onUninstall()
    {
        $setting = Settings::instance()->getSettingDataByName('log_type_' . Logging::LOG_TYPE_VENDOR_RATING);
        if (!$setting) {
            return;
        }

        Settings::instance()->removeById($setting['object_id']);
    }

    /**
     * @inheritDoc
     */
    public function onBeforeInstall()
    {
    }
}
