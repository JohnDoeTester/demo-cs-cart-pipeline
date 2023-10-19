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

namespace Tygh\Addons\VendorPanelConfigurator;

use Tygh\Addons\VendorPanelConfigurator\Enum\ColorSchemas;
use Tygh\Enum\ImagePairTypes;
use Tygh\Settings;

class SettingsService
{
    /**
     * @var array<
     *   string, array{
     *     is_visible: bool,
     *     is_optional: bool,
     *     sections: array<
     *       string, array{
     *         fields: array<
     *           string, array{
     *             title: string,
     *             position: int,
     *             is_optional: bool,
     *             is_visible: bool,
     *           }
     *         >
     *       }
     *     >
     *   }
     * >
     */
    private $product_page_configuration;

    /**
     * @var array<string, array{
     *          color_schema: string,
     *          sidebar_color: string,
     *          element_color: string,
     *          sidebar_background_image: string,
     *      }>
     */
    private $vendor_panel_style;

    /** @var \Tygh\Settings */
    private $settings_manager;

    /**
     * SettingsService constructor.
     *
     * @param array<string, mixed> $product_page_configuration_schema Product page configuration schema
     * @param array<string, mixed> $settings                          Add-on settings
     * @param \Tygh\Settings       $settings_manager                  Settings manager instance
     *
     * @psalm-param array<
     *   string, array{
     *     is_visible: bool,
     *     is_optional: bool,
     *     sections: array<
     *       string, array{
     *         fields: array<
     *           string, array{
     *             title: string,
     *             position: int,
     *             is_optional: bool,
     *             is_visible: bool,
     *           }
     *         >
     *       }
     *     >
     *   }
     * > $product_page_configuration_schema
     *
     * @psalm-param array{
     *   product_fields_configuration: array<
     *     string, array<
     *       string, array<
     *         string, bool
     *       >
     *     >
     *   >,
     *   product_tabs_configuration: array<
     *     string, bool
     *   >
     * } $settings
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     */
    public function __construct(
        array $product_page_configuration_schema,
        array $settings,
        Settings $settings_manager
    ) {
        $this->settings_manager = $settings_manager;

        $this->product_page_configuration = $this->initProductPageConfiguration(
            $product_page_configuration_schema,
            $settings['product_fields_configuration'],
            $settings['product_tabs_configuration']
        );

        $color_schema = $this->settings_manager->getValue('color_schema', 'vendor_panel_configurator');
        $color_schemas = $this->getColorSchemas();

        if (
            $color_schema !== ColorSchemas::CUSTOM
            && isset($color_schemas[$color_schema])
        ) {
            $style = $color_schemas[$color_schema];
            $style['color_schema'] = $color_schema;
            $style['sidebar_background_image'] = $this->settings_manager->getValue('sidebar_background_image', 'vendor_panel_configurator');

            $this->vendor_panel_style = $style;
        } else {
            $this->vendor_panel_style = [
                'color_schema'             => $color_schema,
                'sidebar_color'            => $this->settings_manager->getValue('sidebar_color', 'vendor_panel_configurator'),
                'element_color'            => $this->settings_manager->getValue('element_color', 'vendor_panel_configurator'),
                'sidebar_background_image' => $this->settings_manager->getValue('sidebar_background_image', 'vendor_panel_configurator'),
            ];
        }

        $this->vendor_panel_style['main_pair'] = fn_get_image_pairs(
            0,
            'vendor_panel',
            ImagePairTypes::MAIN
        );
    }

    /**
     * Gets product page configuration.
     *
     * @param array<string, mixed>                              $schema Product page configuration schema
     * @param array<string, array<string, array<string, bool>>> $fields Product fields settings
     * @param array<string, bool>                               $tabs   Product tabs settings
     *
     * @return array<string, mixed>
     *
     * @psalm-return array<
     *   string, array{
     *     is_visible: bool,
     *     is_optional: bool,
     *     sections: array<
     *       string, array{
     *         fields: array<
     *           string, array{
     *             title: string,
     *             position: int,
     *             is_optional: bool,
     *             is_visible: bool,
     *           }
     *         >
     *       }
     *     >
     *   }
     * >
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     */
    private function initProductPageConfiguration(array $schema, array $fields, array $tabs)
    {
        foreach ($schema as $tab_id => &$tab) {
            $tab['is_visible'] = !isset($tabs[$tab_id]);
            if (!isset($tab['sections'])) {
                continue;
            }
            foreach ($tab['sections'] as $section_id => &$section) {
                if (!isset($section['fields'])) {
                    continue;
                }
                foreach ($section['fields'] as $field_id => &$field) {
                    $field['is_visible'] = !isset($fields[$tab_id][$section_id][$field_id]);
                }
                unset($field);

                $section['fields'] = fn_sort_array_by_key($section['fields'], 'position');
            }
            unset($section);

            $tab['sections'] = fn_sort_array_by_key($tab['sections'], 'position');
        }
        unset($tab);

        return fn_sort_array_by_key($schema, 'position');
    }

    /**
     * Updates product fields configuration in the add-on settings.
     *
     * @param array<string, array<string, array<string, bool>>> $product_fields_configuration Product fields configuration
     *
     * @return void
     */
    public function updateProductFieldsConfiguration(array $product_fields_configuration)
    {
        foreach ($product_fields_configuration as $tab_id => &$tab) {
            foreach ($tab as $section_id => &$section) {
                foreach ($section as $field_id => &$field) {
                    if ($field) {
                        unset($section[$field_id]);
                        continue;
                    }
                    $field = (int) $field;
                }
                unset($field);

                // phpcs:ignore
                if (!$section) {
                    unset($tab[$section_id]);
                }
            }
            unset($section);

            // phpcs:ignore
            if (!$tab) {
                unset($product_fields_configuration[$tab_id]);
            }
        }
        unset($tab);

        $this->settings_manager->updateValue(
            'product_fields_configuration',
            json_encode($product_fields_configuration),
            'vendor_panel_configurator'
        );
    }

    /**
     * Updates product tabs configuration in the add-on settings.
     *
     * @param array<string, bool> $product_tabs_configuration Product tabs configuration
     *
     * @return void
     */
    public function updateProductTabsConfiguration(array $product_tabs_configuration)
    {
        $product_tabs_configuration = array_filter(
            $product_tabs_configuration,
            static function ($is_visible) {
                return !$is_visible;
            }
        );

        $this->settings_manager->updateValue(
            'product_tabs_configuration',
            json_encode($product_tabs_configuration),
            'vendor_panel_configurator'
        );
    }

    /**
     * Updates vendor panel style configuration in the add-on settings.
     *
     * @param array<string, string> $vendor_panel_style_config Vendor panel style configuration.
     *
     * @return void
     */
    public function updateVendorPanelStyleConfiguration(array $vendor_panel_style_config)
    {
        if (isset($vendor_panel_style_config['color_schema'])) {
            $this->settings_manager->updateValue(
                'color_schema',
                $vendor_panel_style_config['color_schema'],
                'vendor_panel_configurator'
            );
        }

        if (
            !empty($vendor_panel_style_config['color_schema'])
            && $vendor_panel_style_config['color_schema'] === ColorSchemas::CUSTOM
        ) {
            if (isset($vendor_panel_style_config['sidebar_color'])) {
                $this->settings_manager->updateValue(
                    'sidebar_color',
                    $vendor_panel_style_config['sidebar_color'],
                    'vendor_panel_configurator'
                );
            }

            if (isset($vendor_panel_style_config['element_color'])) {
                $this->settings_manager->updateValue(
                    'element_color',
                    $vendor_panel_style_config['element_color'],
                    'vendor_panel_configurator'
                );
            }
        }

        if (isset($vendor_panel_style_config['sidebar_background_image'])) {
            $this->settings_manager->updateValue(
                'sidebar_background_image',
                $vendor_panel_style_config['sidebar_background_image'],
                'vendor_panel_configurator'
            );
        }
        fn_attach_image_pairs('vendor_panel_background', 'vendor_panel');
    }

    /**
     * Gets product page configuration.
     *
     * @return array<
     *   string, array{
     *     is_visible: bool,
     *     is_optional: bool,
     *     sections: array<
     *       string, array{
     *         fields: array<
     *           string, array{
     *             title: string,
     *             position: int,
     *             is_optional: bool,
     *             is_visible: bool,
     *           }
     *         >
     *       }
     *     >
     *   }
     * >
     */
    public function getProductPageConfiguration()
    {
        return $this->product_page_configuration;
    }

    /**
     * Gets vendor panel style configuration.
     *
     * @return array<string, array{
     *              sidebar_color: string,
     *              element_color: string,
     *              sidebar_background_image: string,
     *          }>
     */
    public function getVendorPanelStyle()
    {
        return $this->vendor_panel_style;
    }

    /**
     * Gets vendor panel actual style configuration from database.
     *
     * @return array<string, array{
     *              sidebar_color: string,
     *              element_color: string,
     *              sidebar_background_image: string,
     *          }>
     */
    public function getVendorPanelActualStyle()
    {
        return [
            'sidebar_color' => $this->settings_manager->getValue('sidebar_color', 'vendor_panel_configurator'),
            'element_color' => $this->settings_manager->getValue('element_color', 'vendor_panel_configurator'),
            'sidebar_background_image' => $this->settings_manager->getValue('sidebar_background_image', 'vendor_panel_configurator'),
        ];
    }

    /**
     * Gets vendor panel actual color schema from database.
     *
     * @return string
     */
    public function getVendorPanelActualColorSchema()
    {
        return $this->settings_manager->getValue('color_schema', 'vendor_panel_configurator');
    }

    /**
     * Gets color schemas.
     *
     * @return array<string, array<string, array{
     *          sidebar_color: string,
     *          element_color: string,
     *      }>>
     */
    public function getColorSchemas()
    {
        $color_schema = fn_get_schema('vendor_panel_configurator', 'color_schemas');

        $color_schema[ColorSchemas::CUSTOM] = [
            'sidebar_color' => $this->settings_manager->getValue('sidebar_color', 'vendor_panel_configurator'),
            'element_color' => $this->settings_manager->getValue('element_color', 'vendor_panel_configurator'),
        ];

        return $color_schema;
    }
}
