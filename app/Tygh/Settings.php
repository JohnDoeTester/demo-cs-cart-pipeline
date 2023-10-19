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

namespace Tygh;

use Tygh\Database\Connection;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\SettingTypes;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Providers\StorefrontProvider;

/**
 * Class Settings provides means to operate store settings.
 *
 * @package Tygh
 */
class Settings
{
    /**
     * Settings section types
     */
    const CORE_SECTION  = 'CORE';
    const ADDON_SECTION = 'ADDON';
    const TAB_SECTION   = 'TAB';
    const SEPARATE_TAB_SECTION = 'SEPARATE_TAB';

    /**
     * Setting/section edition types
     */
    const NONE   = 'NONE';
    const ROOT   = 'ROOT';
    const VENDOR = 'VENDOR';
    const VENDORONLY = 'VENDORONLY';
    const STOREFRONT = 'STOREFRONT';

    /**
     * Settings description types
     */
    const VARIANT_DESCRIPTION = 'V';
    const SETTING_DESCRIPTION = 'O';
    const SECTION_DESCRIPTION = 'S';

    const NULL_VALUE = '__NULL__';

    /**
     * Instances of a class.
     *
     * @var array<string, self>
     */
    private static $instance_cache = [];

    /**
     * Settgins values cache
     *
     * @var array<string, array<string, string>>
     */
    private static $settings_values_cache = [];

    /**
     * Setting sections cache.
     *
     * @var array
     */
    private $sections;

    /**
     * Name of current edition.
     *
     * @var string
     */
    private $current_edition;

    /**
     * Indicates, where setting values should be stored:
     * - when set to true, settings_objects table will be used,
     * - when set to false, settings_vendor_values table will be used instead.
     *
     * Root mode is used when settings of a store with a single storefront are edited.
     *
     * @var bool
     */
    private $is_root_mode = false;

    /**
     * Instance company ID.
     *
     * @var int|null
     */
    private $company_id = null;

    /**
     * Instance storefront ID.
     *
     * @var int|null
     */
    private $storefront_id = null;

    /**
     * Whether settings can be overridden by a storefront.
     *
     * @var bool
     */
    private $has_multiple_storefronts = true;

    /**
     * Database connection instance to interact with the database.
     *
     * @var \Tygh\Database\Connection
     */
    private $db;

    /**
     * Loads sections data and settings schema from DB.
     *
     * @param \Tygh\Database\Connection $db Database connection instance
     */
    private function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Reloads sections data into cache.
     *
     * @return bool Always true
     */
    public function reloadSections()
    {
        $this->sections = $this->getSections('', 0, true, false);

        return true;
    }

    /**
     * Provides settings service instance.
     *
     * @param array<int|string>|int|null $params Instance parameters
     *
     * @psalm-param array{
     *   company_id?: int|null,
     *   storefront_id?: int|null,
     *   has_multiple_storefronts?: bool,
     *   area?: string,
     *   db?: \Tygh\Database\Connection,
     * }|int|null $params
     *
     * @return self
     */
    public static function instance($params = [])
    {
        $params = self::normalizeInstanceParameters($params);
        $cache_key = "{$params['area']}_{$params['storefront_id']}_{$params['company_id']}_{$params['has_multiple_storefronts']}";

        if (!isset(self::$instance_cache[$cache_key])) {
            $instance = new Settings($params['db']);
            $instance->has_multiple_storefronts = $params['has_multiple_storefronts'];
            $instance->is_root_mode = $instance->hasSingleStorefront() || !$params['storefront_id'];
            $instance->company_id = $params['company_id'];
            $instance->storefront_id = $params['storefront_id'];
            $instance->reloadSections();

            self::$instance_cache[$cache_key] = $instance;
        }

        return self::$instance_cache[$cache_key];
    }

    /**
     * Normalizes settings instance creation parameters.
     *
     * @param array<int|string>|int|null $params Array of parameters or a company ID
     *
     * @psalm-param array{
     *   company_id?: int|null,
     *   storefront_id?: int|null,
     *   has_multiple_storefronts?: bool,
     *   area?: string,
     *   db?: \Tygh\Database\Connection,
     * }|int|null $params
     *
     * @return array<int|string> Normalized instance parameters
     *
     * @psalm-return array{
     *   company_id: int|null,
     *   storefront_id: int|null,
     *   has_multiple_storefronts: bool,
     *   area: string,
     *   db: \Tygh\Database\Connection,
     * }
     */
    private static function normalizeInstanceParameters($params)
    {
        if (!is_array($params)) {
            $params = [
                'company_id' => $params
            ];
        }

        $params = array_merge(
            [
                'company_id'               => null,
                'storefront_id'            => null,
                'has_multiple_storefronts' => null,
                'area'                     => null,
                'db'                       => null,
            ],
            $params
        );

        if (fn_allowed_for('ULTIMATE') && $params['company_id'] === null) {
            $params['company_id'] = fn_get_runtime_company_id();
        }

        if ($params['area'] === null) {
            $params['area'] = AREA;
        }

        if (fn_allowed_for('ULTIMATE') && $params['company_id'] && $params['storefront_id'] === null) {
            $storefront = StorefrontProvider::getRepository()->findByCompanyId($params['company_id']);
            if ($storefront) {
                $params['storefront_id'] = $storefront->storefront_id;
            }
        }

        if ($params['storefront_id'] === null && SiteArea::isStorefront($params['area'])) {
            $storefront = StorefrontProvider::getStorefront();
            $params['storefront_id'] = $storefront->storefront_id;
        }

        if ($params['has_multiple_storefronts'] === null) {
            $params['has_multiple_storefronts'] = StorefrontProvider::getRepository()->getCount(['cache' => true]) > 1;
        }

        if ($params['db'] === null) {
            /** @var Connection $db */
            $db = Tygh::$app['db'];
            $params['db'] = $db;
        }

        return $params;
    }

    /**
     * Replaces current settings instance product edition.
     *
     * @param string $edition Full edition name (new value of const PRODUCT_EDITION)
     */
    public function setNewEdition($edition)
    {
        $this->current_edition = strtoupper(fn_get_edition_acronym($edition));
    }

    /**
     * Gets current edition acronym.
     *
     * @return string
     */
    private function getCurrentEditionPrefix()
    {
        if (empty($this->current_edition)) {
            $this->current_edition = strtoupper(fn_get_edition_acronym(PRODUCT_EDITION));
        }

        return $this->current_edition . ':';
    }

    /**
     * Checks whether a section with the specified name exists in the specified list of sections.
     *
     * @param  array  $sections     List of sections
     * @param  string $section_name Section name to find in sections list
     *
     * @return bool
     */
    public function sectionExists($sections, $section_name)
    {
        foreach ($sections as $section) {
            if ($section['name'] == $section_name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether a specified settings section has visible settings.
     *
     * @param  string $section_name Section name
     * @param  string $section_type Section type
     *
     * @return bool   True if visible options exists, false otherwise
     */
    public function optionsExists($section_name, $section_type)
    {
        $section_data = $this->getSectionByName($section_name, $section_type);
        $options = $this->getPlainList(
            ['?:settings_objects.object_id'],
            $section_data['section_id'],
            '',
            false,
            $this->db->quote(' AND ?:settings_objects.type <> ?s', SettingTypes::HIDDEN)
        );

        return !empty($options);
    }

    /**
     * Gets all core setting sections.
     *
     * @param string $lang_code Two-letter language code
     *
     * @return array  List of setting sections
     */
    public function getCoreSections($lang_code = CART_LANGUAGE)
    {
        $_sections = $this->getSections(Settings::CORE_SECTION, 0, true, true, $lang_code);
        $sections = Array();

        foreach ($_sections as &$section) {
            $sections[$section['name']] = $section;
            if (isset($section['name'])) {
                $sections[$section['name']]['section_id'] = $section['name'];
            }
            $sections[$section['name']]['object_type'] = self::SECTION_DESCRIPTION;
            if (isset($section['description'])) {
                $sections[$section['name']]['title'] =  $section['description'];
            }
            unset ($sections[$section['name']]['name']);
        }

        ksort($sections);

        return $sections;
    }

    /**
     * Gets add-ons setting sections.
     *
     * @return array List of setting sections
     */
    public function getAddons()
    {
        return $this->getSections(self::ADDON_SECTION);
    }

    /**
     * Gets settings section tabs.
     *
     * @param  int    $parent_section_id Parent section identifier
     * @param  string $lang_code         2 letters language code
     *
     * @return array  List of tab sections
     */
    public function getSectionTabs($parent_section_id, $lang_code = CART_LANGUAGE)
    {
        fn_get_schema('settings', 'actions.functions', 'php', true);

        $_tabs = $this->getSections([Settings::TAB_SECTION, Settings::SEPARATE_TAB_SECTION], $parent_section_id, false, true, $lang_code);
        $tabs = [];

        foreach ($_tabs as $tab) {
            if (isset($this->sections[$parent_section_id]['name'])) {
                $func_name = 'fn_is_tab_' . fn_strtolower($this->sections[$parent_section_id]['name']) . '_' . $tab['name'] . '_available';

                if (function_exists($func_name) && $func_name() === false) {
                    continue;
                }
            }

            $tabs[$tab['name']] = $tab;
            $tabs[$tab['name']]['parent_id'] = $parent_section_id;
        }

        return $tabs;
    }

    /**
     * Gets settings section data by its name and type.
     *
     * @param  string $name             Section name
     * @param  string $type             Type of section. Use Settings class constant to set this value
     * @param  bool   $use_access_level Use or ignore edition and type access conditions (ROOT, VENDOR, etc...)
     *
     * @return array  Section data
     */
    public function getSectionByName($name, $type = Settings::CORE_SECTION, $use_access_level = true)
    {
        return $this->db->getRow(
            'SELECT * FROM ?:settings_sections'
            . ' WHERE name = ?s AND type = ?s ?p',
            $name,
            $type,
            $this->generateEditionCondition('?:settings_sections', $use_access_level)
        );
    }

    /**
     * Gets translated section name by its ID.
     *
     * @param int    $section_id Section identifier
     * @param string $lang_code  Two-letters language code
     *
     * @return string Section name
     */
    public function getSectionName($section_id, $lang_code = CART_LANGUAGE)
    {
        return $this->db->getField(
            'SELECT ?:settings_descriptions.value FROM ?:settings_sections'
            . ' LEFT JOIN ?:settings_descriptions'
            . ' ON ?:settings_descriptions.object_id = ?:settings_sections.section_id AND object_type = ?s'
            . ' WHERE section_id = ?i AND ?:settings_descriptions.lang_code = ?s',
            Settings::SECTION_DESCRIPTION,
            $section_id,
            $lang_code
        );
    }

    /**
     * Gets internal section name by its ID.
     *
     * @param  string $section_id Section identifier
     *
     * @return string Section name
     */
    public function getSectionTextId($section_id)
    {
        return $this->sections[$section_id]['name'];
    }

    /**
     * Gets settings sections.
     *
     * @param  mixed  $section_type     Section type (one or several sections can be passed as string or array). Use constants of Settings class to set this value.
     * @param  int    $parent_id        Id of parent section
     * @param  bool   $generate_href    Generate href to core section // FIXME: Bad style
     * @param  bool   $use_access_level Use or ignore edition and type access conditions (ROOT, MSE:VENDOR, etc...)
     * @param  string $lang_code        2 letters language code
     *
     * @return array  List of sections
     */
    private function getSections($section_type = '', $parent_id = 0, $generate_href = true, $use_access_level = true, $lang_code = '')
    {
        $condition = $this->generateEditionCondition('?:settings_sections', $use_access_level);
        $values = '';
        $join = '';

        if ($parent_id != 0) {
            $condition .= $this->db->quote(' AND ?:settings_sections.parent_id = ?i', $parent_id);
        }
        if (!empty($section_type)) {
            $section_type = is_array($section_type) ? $section_type : array($section_type);
            $condition .= $this->db->quote(' AND ?:settings_sections.type IN (?a)', $section_type);
        }

        if (!empty($lang_code)) {
            $join = $this->db->quote(
                ' LEFT JOIN ?:settings_descriptions'
                . ' ON ?:settings_descriptions.object_id = ?:settings_sections.section_id'
                . ' AND object_type = ?s AND ?:settings_descriptions.lang_code = ?s',
                Settings::SECTION_DESCRIPTION,
                $lang_code
            );
            $values .= ', ?:settings_descriptions.value as description, object_id, object_type';
        } else {
            $values .= ', ?:settings_sections.name as description';
        }

        //TODO: Fix generating link for core sections
        if ($generate_href) {
            $values .= ', CONCAT(\'settings.manage?section_id=\', ?:settings_sections.name) as href ';
        }

        return $this->db->getHash(
            'SELECT ?:settings_sections.name, ?:settings_sections.section_id,'
            . ' ?:settings_sections.position, ?:settings_sections.type ?p'
            . ' FROM ?:settings_sections ?p'
            . ' WHERE 1 ?p ORDER BY ?:settings_sections.position',
            'section_id',
            $values,
            $join,
            $condition
        );
    }

    /**
     * Updates settings section.
     *
     * Section data must be array in this format (example):
     * Array (
     *      'section_id'   => 1,
     *      'parent_id'    => 3,
     *      'edition_type' => 'ROOT,VENDOR',
     *      'name'         => 'Appearance',
     *      'position'     => 10,
     *      'type'         => 'CORE',
     * );
     *
     * If some parameter will be skipped and function not update it field.
     * If section_id skipped function adds new variant and retuns id of new record.
     *
     * @param  array   $section_data Array of section data
     *
     * @return bool|int Section identifier if section was created, true un success update, false otherwise
     */
    public function updateSection($section_data)
    {
        if (!$this->checkEdition($section_data)) {
            return false;
        }

        $section_id = $this->db->replaceInto('settings_sections', $section_data);
        $this->sections = $this->getSections();

        return $section_id;
    }

    /**
     * Removes setting section.
     *
     * @param int $section_id Section identifier
     *
     * @return bool True on success, false when $section_id is not specified
     */
    public function removeSection($section_id)
    {
        if (!$section_id) {
            $this->generateError(__('unable_to_delete_setting_description'), __('empty_key_value'));

            return false;
        }

        $sections = $this->db->getColumn('SELECT section_id FROM ?:settings_sections WHERE section_id = ?i OR parent_id = ?i', $section_id, $section_id);
        if (!empty($sections)) {
            $this->db->query('DELETE FROM ?:settings_sections WHERE section_id IN (?n)', $sections);
            $this->db->query('DELETE FROM ?:settings_descriptions WHERE object_id IN (?n) AND object_type = ?s', $sections, Settings::SECTION_DESCRIPTION);

            $setting_ids = $this->db->getColumn('SELECT object_id FROM ?:settings_objects WHERE section_id IN (?n)', $sections);
            if (!empty($setting_ids)) {
                $this->db->query(
                    'DELETE FROM ?:settings_descriptions WHERE object_id IN (?n) AND object_type = ?s',
                    $setting_ids,
                    Settings::SETTING_DESCRIPTION
                );

                $variant_ids = $this->db->getColumn(
                    'SELECT variant_id FROM ?:settings_variants WHERE object_id IN (?n)',
                    $setting_ids
                );
                if (!empty($variant_ids)) {
                    $this->db->query('DELETE FROM ?:settings_variants WHERE object_id IN (?n)', $setting_ids);
                    $this->db->query(
                        'DELETE FROM ?:settings_descriptions WHERE object_id IN (?n) AND object_type = ?s',
                        $variant_ids,
                        Settings::VARIANT_DESCRIPTION
                    );
                }

                $this->db->query('DELETE FROM ?:settings_vendor_values WHERE object_id IN (?n)', $setting_ids);
                $this->db->query('DELETE FROM ?:settings_objects WHERE object_id IN (?n)', $setting_ids);
            }

            $this->sections = $this->getSections();
        }

        return true;
    }

    /**
     * Gets settings data.
     *
     * @param int    $section_id     Section identifier
     * @param int    $section_tab_id Section tab identifier
     * @param bool   $plain_list     Get list without division into sections
     * @param int    $company_id     Company identifier
     * @param string $lang_code      Two-letter language code
     * @param int    $storefront_id  Storefront identifier
     *
     * @return array<string, int|string> Settings data
     */
    public function getList($section_id = 0, $section_tab_id = 0, $plain_list = false, $company_id = null, $lang_code = CART_LANGUAGE, $storefront_id = null)
    {
        $company_id = $this->getCompanyId($company_id);
        $storefront_id = $this->getStorefrontId($storefront_id, $company_id);

        $condition = $this->generateEditionCondition('?:settings_objects', true);
        $plain_settings = $this->getPlainList(
            [
                '?:settings_objects.object_id as object_id',
                '?:settings_objects.name as name',
                'section_id',
                'section_tab_id',
                'type',
                'edition_type',
                'position',
                'is_global',
                'handler',
                'parent_id',
            ],
            $section_id,
            $section_tab_id,
            false,
            $condition,
            false,
            $company_id,
            $lang_code,
            $storefront_id
        );

        $settings = [];
        if ($plain_list) {
            $settings = $plain_settings;
        } else {
            foreach ($plain_settings as $setting) {
                $setting = $this->processSettingData($setting, $lang_code);

                if (($section_tab_id != 0) && ($section_id != 0)) {
                    $settings[$setting['object_id']] = $setting;
                } elseif (($section_id != 0)) {
                    $settings[$setting['section_tab_name']][$setting['object_id']] = $setting;
                } else {
                    $settings[$setting['section_name']][$setting['section_tab_name']][$setting['object_id']] = $setting;
                }
            }
        }

        return $settings;
    }

    /**
     * Gets all information of requested setting by its name.
     *
     * @param string $setting_name  Name of setting (Example: 'orders_per_page')
     * @param int    $company_id    Company identifier
     * @param string $lang_code     Two-letter language code
     * @param int    $storefront_id Storefront identifier
     *
     * @return array<int|string>|bool  Setting information on success, false otherwise
     */
    public function getSettingDataByName($setting_name, $company_id = null, $lang_code = CART_LANGUAGE, $storefront_id = null)
    {
        $company_id = $this->getCompanyId($company_id);
        $storefront_id = $this->getStorefrontId($storefront_id, $company_id);

        if (!$setting_name) {
            return false;
        }

        $condition = $this->db->quote(' AND name = ?s', $setting_name);
        $setting = $this->getPlainList(
            [
                '?:settings_objects.object_id as object_id',
                '?:settings_objects.name as name',
                'section_id',
                'section_tab_id',
                'type',
                'edition_type',
                'position',
                'is_global',
                'handler',
                'parent_id',
            ],
            '',
            '',
            false,
            $condition,
            false,
            $company_id,
            $lang_code,
            $storefront_id
        );

        $setting = reset($setting);
        if (!$setting) {
            return false;
        }

        $setting = $this->processSettingData($setting, $lang_code);

        return $setting;
    }

    /**
     * Gets additional setting data, such as section name, setting variants, value in correct format, etc.
     * Execute handler functions for this setting
     *
     * @param array<string, int|string> $setting   Setting data.
     * @param string                    $lang_code Two-letter language code
     *
     * @return array Prepared setting data
     *
     * @psalm-suppress PossiblyInvalidArgument
     */
    private function processSettingData(array $setting, $lang_code)
    {
        $_sections = $this->sections;

        fn_get_schema('settings', 'handlers.functions', 'php', true);

        if (isset($_sections[$setting['section_id']])) {
            $setting['section_name'] = ($setting['section_id'] === 0 && !isset($_sections[$setting['section_id']]))
                ? 'General'
                : $_sections[$setting['section_id']]['name'];
            $setting['section_tab_name'] = ((int) $setting['section_tab_id'] === 0 && !isset($_sections[$setting['section_tab_id']]))
                ? 'main'
                : $_sections[$setting['section_tab_id']]['name'];
        } else {
            $setting['section_name'] = $setting['section_tab_name'] = '';
        }

        $result = '';
        // Execute custom function for generate info from handler if it exists
        if (!empty($setting['handler'])) {
            $args = explode(',', $setting['handler']);
            $func = array_shift($args);
            $result = 'Something goes wrong';
            if (function_exists($func)) {
                $result = call_user_func_array($func, $args);
            }
        } else {
            $setting_handler_func = (
                'fn_settings_handlers_'
                . fn_strtolower($setting['section_name']) . '_'
                . ($setting['section_tab_name'] !== 'main' ? fn_strtolower($setting['section_tab_name']) . '_' : '')
                . fn_strtolower($setting['name'])
            );
            if (function_exists($setting_handler_func)) {
                $result = $setting_handler_func();
            }
        }

        if (!empty($result)) {
            if (is_array($result)) {
                $setting['options'] = $result;
            } else {
                $setting['info'] = $result;
            }
        }

        // Check if this options may be updated for all vendors
        if ($this->areOverriddenValuesSupportedByEdition($setting['edition_type'])) {
            $setting['update_for_all'] = true;
        }

        $setting['variants'] = $this->getVariants($setting['section_name'], $setting['name'], $setting['section_tab_name'], $setting['object_id'], $lang_code);

        $force_parse = $setting['type'] == 'N' ? true : false;
        $setting['value'] = $this->unserializeValue($setting['value'], $force_parse);

        if ($setting['type'] === SettingTypes::MULTIPLE_CHECKBOXES) {
            $setting['value_ids'] = $this->setCheckedValueIds($setting);
        }

        return $setting;
    }

    /**
     * Gets settings values, applying all permission and edition filters.
     *
     * @param string $section_name  Section name
     * @param string $section_type  Section type. Use CSettings class constants
     * @param bool   $hierarchy     If it's false settings will be returned as plain list
     * @param int    $company_id    Company identifier
     * @param int    $storefront_id Storefront identifier
     *
     * @return array<int|string>|bool List of settings values on success, false otherwise
     */
    public function getValues($section_name = '', $section_type = Settings::CORE_SECTION, $hierarchy = true, $company_id = null, $storefront_id = null)
    {
        $settings = array();
        $company_id = $this->getCompanyId($company_id);
        $storefront_id = $this->getStorefrontId($storefront_id, $company_id);

        $section_id = '';
        $section_tab_id = '';

        if ($section_name) {
            $section = $this->getSectionByName($section_name, $section_type, false);

            if (!isset($section['section_id'])) {
                return false;
            }

            if ($section['parent_id'] != 0) {
                $section_id = $section['parent_id'];
                $section_tab_id = $section['section_id'];
            } else {
                $section_id = $section['section_id'];
            }
        }
        $result = $this->getPlainList(
            [
                '?:settings_objects.object_id as object_id',
                'name',
                'section_id',
                'section_tab_id',
                'type',
                'position',
                'is_global',
            ],
            $section_id,
            $section_tab_id,
            true,
            false,
            false,
            $company_id,
            '',
            $storefront_id
        );

        $_sections = $this->sections;

        if ($result) {
            foreach ($result as $_row) {
                $section_name = ($_row['section_id'] != 0 && isset($_sections[$_row['section_id']])) ? $_sections[$_row['section_id']]['name'] : '';
                $section_tab_name = ($_row['section_tab_id'] != 0 && isset($_sections[$_row['section_tab_id']])) ? $_sections[$_row['section_tab_id']]['name'] : '';

                $force_parse = $_row['type'] == 'N' ? true : false;
                if (!empty($_row['section_tab_id']) && $hierarchy) {
                    $settings[$section_name][$section_tab_name][$_row['name']] = $this->unserializeValue($_row['value'], $force_parse);
                } elseif (!empty($_row['section_id']) && $hierarchy) {
                    $settings[$section_name][$_row['name']] = $this->unserializeValue($_row['value'], $force_parse);
                } else {
                    $settings[$_row['name']] = $this->unserializeValue($_row['value'], $force_parse);
                }
            }

            if (empty($section_id) || !$hierarchy) {
                return $settings;
            } elseif (!empty($section_id) && empty($section_tab_id)) {
                return $settings[$section_name];
            } elseif (!empty($section_tab_id)) {
                return $settings[$section_id][$section_tab_id];
            }
        }

        return $settings;
    }

    /**
     * Gets setting value.
     *
     * @param string $setting_name  Setting name
     * @param string $section_name  Section name
     * @param int    $company_id    Company identifier
     * @param int    $storefront_id Storefront identifier
     *
     * @return mixed|bool Setting value on success, false otherwise
     */
    public function getValue($setting_name, $section_name, $company_id = null, $storefront_id = null)
    {
        $company_id = $this->getCompanyId($company_id);
        $storefront_id = $this->getStorefrontId($storefront_id, $company_id);

        if (!$setting_name) {
            return false;
        }

        $id = $this->getId($setting_name, $section_name);
        $condition = $this->db->quote(' AND ?:settings_objects.object_id = ?i', $id);
        $setting = $this->getPlainList(
            ['?:settings_objects.object_id as object_id', '?:settings_objects.type as object_type'],
            '',
            '',
            false,
            $condition,
            false,
            $company_id,
            '',
            $storefront_id
        );

        $setting = reset($setting);
        if (!$setting) {
            return false;
        }

        $force_parse = $setting['object_type'] === SettingTypes::MULTIPLE_CHECKBOXES;
        return $this->unserializeValue($setting['value'], $force_parse);
    }

    /**
     * Gets setting data but its ID.
     *
     * @param int    $object_id     Setting object identifier
     * @param int    $company_id    Company identifier
     * @param string $lang_code     Two-letter language code
     * @param int    $storefront_id Storefront identifier
     *
     * @return array|bool Setting data on success, false otherwise
     */
    public function getData($object_id, $company_id = null, $lang_code = CART_LANGUAGE, $storefront_id = null)
    {
        $company_id = $this->getCompanyId($company_id);
        $storefront_id = $this->getStorefrontId($storefront_id, $company_id);

        if (empty($object_id)) {
            return false;
        }

        $condition = $this->db->quote(' AND ?:settings_objects.object_id = ?i', $object_id);
        $setting = $this->getPlainList(
            [
                '?:settings_objects.object_id as object_id',
                '?:settings_objects.name as name',
                'section_id',
                'section_tab_id',
                'type',
                'edition_type',
                'position',
                'is_global',
                'handler',
                'parent_id',
            ],
            '',
            '',
            false,
            $condition,
            false,
            $company_id,
            $lang_code,
            $storefront_id
        );

        $setting = reset($setting);
        if (!$setting) {
            return false;
        }

        $setting = $this->processSettingData($setting, $lang_code);

        return $setting;
    }

    /**
     * Gets setting ID by its name and section name.
     *
     * @param  string   $section_name Setting name
     * @param  string   $setting_name Section name
     *
     * @return int|bool Setting ID on success, or false if $setting_name is not specified
     */
    public function getId($setting_name, $section_name = '')
    {
        if (!$setting_name) {
            return false;
        }

        if (!empty($section_name)) {
            $section_condition = $this->db->quote(' AND ?:settings_sections.name = ?s', $section_name);
        } else {
            $section_condition = '';
        }

        $object_id = $this->db->getField(
            'SELECT object_id FROM ?:settings_objects'
            . ' LEFT JOIN ?:settings_sections ON ?:settings_objects.section_id = ?:settings_sections.section_id'
            . ' WHERE ?:settings_objects.name = ?s ?p',
            $setting_name,
            $section_condition
        );

        return empty($object_id) ? false : (int) $object_id;
    }

    /**
     * Updates all setting parameters including descriptions and variants.
     *
     * @param array<string, int|string>    $setting_data        Setting data
     * @param array<string, int|string>    $variants            List of variants to update
     * @param array<array<string, string>> $descriptions        List of descriptions to update
     * @param bool                         $force_cache_cleanup Force registry cleanup after setting was updated
     *
     * @return int   Setting identifier if it was created, true un success update, false otherwise
     *
     * @see \Tygh\Settings::updateVariant()
     * @see \Tygh\Settings::updateDescription
     * @see \Tygh\Settings::updateSettingObject()
     */
    public function update($setting_data, $variants = null, $descriptions = null, $force_cache_cleanup = false)
    {
        $id = $this->updateSettingObject($setting_data);

        if (!empty($id)) {
            if (is_array($variants)) {
                foreach ($variants as $variant_data) {
                    $variant_data['object_id'] = $id;
                    $this->updateVariant($variant_data);
                }
            }

            if (is_array($descriptions)) {
                foreach ($descriptions as $description_data) {
                    $description_data['object_id'] = $id;

                    $this->updateDescription($description_data);
                }
            }
        }

        if ($force_cache_cleanup) {
            Registry::cleanup();
        }

        return $id;
    }

    /**
     * Updates setting.
     * Settings data must be array in this format (example):
     *
     * Array (
     *      'object_id' =>      22,
     *      'name' =>           'products_per_page',
     *      'section_id' =>     4,
     *      'section_tab_id' => 0,
     *      'type' =>           'U',
     *      'position' =>       100,
     *      'is_global' =>      'Y'
     * )
     *
     * If some parameter will be skipped and function not update it field.
     * If object_id skipped function adds new setting and retuns id of new record.
     *
     * For update setting value please use specific functions
     *
     * @param array<string, int|string> $setting_data Array of setting fields
     *
     * @return int   Setting identifier if setting was created, true un success update, false otherwise
     */
    private function updateSettingObject(array $setting_data)
    {
        if (!$this->checkEdition($setting_data)) {
            return false;
        }

        $data = $setting_data;

        // Delete value if exist
        if (!empty($data['value'])) {
            unset($data['value']);
        }

        return $this->db->replaceInto('settings_objects', $data);
    }

    /**
     * Updates setting value by its name and section name.
     *
     * @param string                   $setting_name        Setting name
     * @param string|array<string|int> $setting_value       Setting value
     * @param string                   $section_name        Section name
     * @param bool                     $force_cache_cleanup Force registry cleanup after setting was updated
     * @param int                      $company_id          Company identifier
     * @param bool                     $execute_functions   Whether update triggers should be executed
     * @param int                      $storefront_id       Storefront identifier
     *
     * @return bool   Always true
     */
    public function updateValue(
        $setting_name,
        $setting_value,
        $section_name = '',
        $force_cache_cleanup = false,
        $company_id = null,
        $execute_functions = true,
        $storefront_id = null
    ) {
        if (!empty($setting_name)) {
            $object_id = $this->getId($setting_name, $section_name);
            $this->updateValueById($object_id, $setting_value, $company_id, $execute_functions, $storefront_id);

            if ($force_cache_cleanup) {
                Registry::cleanup();
            }
        }

        return true;
    }

    /**
     * Updates setting value.
     *
     * @param int                      $object_id         Setting identifier
     * @param string|array<string|int> $value             New value
     * @param int                      $company_id        Company identifier
     * @param bool                     $execute_functions Flag needed execute functions
     * @param int                      $storefront_id     Storefront identifier
     *
     * @return bool   True on success, false if $object_id is not specified
     */
    public function updateValueById($object_id, $value, $company_id = null, $execute_functions = true, $storefront_id = null)
    {
        if (!$object_id) {
            return false;
        }

        $company_id = $this->getCompanyId($company_id);
        $storefront_id = $this->getStorefrontId($storefront_id, $company_id);

        fn_get_schema('settings', 'actions.functions', 'php', true);

        $value = $this->serializeValue($value);

        $edition_types = explode(
            ',',
            $this->db->getField('SELECT edition_type FROM ?:settings_objects WHERE object_id = ?i', $object_id)
        );

        $table = $this->getUpdateValueTable($edition_types, $storefront_id);
        $data = $this->getUpdateValueData($object_id, $value, $edition_types, $company_id, $storefront_id);

        if (!$table) {
            $message = __('unable_to_update_setting_value') . ' (' . $object_id . ')';
            $this->generateError($message, __('you_have_no_permissions'));

            return false;
        }

        $old_data = $this->getData($object_id, $company_id);

        // Value types should be converted to the same one to compare
        if ($old_data && !is_array($old_data['value']) && $old_data['value'] !== null) {
            $old_data['value'] = (string) $old_data['value'];
        }

        if (!is_array($value) && $value !== null) {
            $value = (string) $value;
        }

        // If option value was changed execute user function if it exists
        if ($old_data && $old_data['value'] !== $value && $execute_functions) {
            $core_func_name = 'fn_settings_actions_'
                . fn_strtolower($old_data['section_name'])
                . '_'
                . (!empty($old_data['section_tab_name']) && $old_data['section_tab_name'] !== 'main'
                    ? $old_data['section_tab_name'] . '_'
                    : ''
                ) . $old_data['name'];
            if (function_exists($core_func_name)) {
                $core_func_name($data['value'], $old_data['value'], $this);
            }

            $addon_func_name  = 'fn_settings_actions_addons_'  . fn_strtolower($old_data['section_name']) . '_' . fn_strtolower($old_data['name']);
            if (function_exists($addon_func_name)) {
                $addon_func_name($data['value'], $old_data['value'], $this);
            }
        }

        if (!is_array($old_data)) {
            $old_data = [];
        }

        /**
         * Hook is being executed before updating setting value by setting object ID.
         *
         * @param \Tygh\Settings $this              Settings instance
         * @param string         $object_id         Setting object ID
         * @param string|array   $value             New value that was passed to function
         * @param int            $company_id        Company ID
         * @param bool           $execute_functions Whether to execute action functions
         * @param array          $data              Data to be inserted/updated into settings table
         * @param array          $old_data          Previously existed data (if any) of settings object at settings table
         * @param string         $table             Table to save setting object value ("settings_objects" or "settings_vendor_values")
         * @param int            $storefront_id     Storefront identifier
         */
        fn_set_hook('settings_update_value_by_id_pre', $this, $object_id, $value, $company_id, $execute_functions, $data, $old_data, $table, $storefront_id);

        $this->db->replaceInto($table, $data);

        if ($this->isRootMode() && $storefront_id) {
            $this->db->query('DELETE FROM ?:settings_vendor_values WHERE object_id = ?i AND storefront_id = ?i', $object_id, $storefront_id);
        }

        /**
         * Hook is being executed after updating setting value by setting object ID.
         *
         * @param \Tygh\Settings $this              Settings instance
         * @param string         $object_id         Setting object ID
         * @param string|array   $value             New value that was passed to function
         * @param int            $company_id        Company ID
         * @param bool           $execute_functions Whether to execute action functions
         * @param array          $data              Data to be inserted/updated into settings objects table
         * @param array          $old_data          Previously existed data (if any) of settings object at settings objects table
         * @param string         $table             Table to save setting object value ("settings_objects" or "settings_vendor_values")
         * @param int            $storefront_id     Storefront identifier
         */
        fn_set_hook('settings_update_value_by_id_post', $this, $object_id, $value, $company_id, $execute_functions, $data, $old_data, $table, $storefront_id);

        return true;
    }

    /**
     * Check whether a setting exists.
     *
     * @param  string $section_name Setting name
     * @param  string $setting_name Section name
     *
     * @return bool
     */
    public function isExists($setting_name, $section_name = '')
    {
        return (bool) $this->getId($setting_name, $section_name);
    }

    /**
     * Removes setting and all related data by its name.
     *
     * @param  string $section_name Setting name
     * @param  string $setting_name Section name
     *
     * @return bool   Always true
     */
    public function remove($setting_name, $section_name = '')
    {
        return $this->removeById($this->getId($setting_name, $section_name));
    }

    /**
     * Removes setting and all related data by its ID.
     *
     * @param  int  $setting_id Setting identifier
     *
     * @return bool Always true
     */
    public function removeById($setting_id)
    {
        $this->db->query('DELETE FROM ?:settings_objects WHERE object_id = ?i', $setting_id);

        $this->removeDescription($setting_id, Settings::SETTING_DESCRIPTION);
        $this->removeSettingVariants($setting_id);
        $this->resetAllOverrides($setting_id);

        return true;
    }

    /**
     * Removes all settings values for a company.
     *
     * @param int $company_id Company identifier
     *
     * @return bool Always true
     */
    public function removeVendorSettings($company_id)
    {
        return $this->db->query('DELETE FROM ?:settings_vendor_values WHERE company_id = ?i', $company_id);
    }

    /**
     * Removes all settings values for a storefront.
     *
     * @param int $storefront_id Storefront identifier
     *
     * @return bool Always true
     */
    public function removeStorefrontSettings($storefront_id)
    {
        return $this->db->query('DELETE FROM ?:settings_vendor_values WHERE storefront_id = ?i', $storefront_id);
    }

    /**
     * @deprecated since 4.12.1. Use \Tygh\Settings::resetAllOverrides instead.
     */
    public function resetAllVendorsSettings($object_id)
    {
        return $this->resetAllOverrides($object_id);
    }

    /**
     * Removes all company- and storefront-specific values of a setting.
     *
     * @param int $object_id Setting object identifier
     *
     * @return bool Always true
     */
    public function resetAllOverrides($object_id)
    {
        return $this->db->query('DELETE FROM ?:settings_vendor_values WHERE object_id = ?i', $object_id);
    }

    /**
     * Gets setting value for all companies.
     *
     * @param string $setting_name Setting name
     * @param string $section_name Section name
     *
     * @return array|bool Array of setting values with company_ids as keys on success, false otherwise
     */
    public function getAllVendorsValues($setting_name, $section_name = '')
    {
        if (!fn_allowed_for('ULTIMATE') || !$setting_name) {
            return false;
        }

        $fields = [
            '?:companies.company_id',
            '?:settings_objects.object_id as object_id',
            '?:settings_objects.type as object_type',
            'IF(?:settings_vendor_values.value IS NULL, ?:settings_objects.value, ?:settings_vendor_values.value) as value',
        ];

        $id = $this->getId($setting_name, $section_name);

        $join = $this->db->quote('LEFT JOIN ?:settings_objects ON ?:settings_objects.object_id = ?i', $id);
        $join .= ' LEFT JOIN ?:settings_vendor_values ON ?:settings_vendor_values.object_id = ?:settings_objects.object_id'
            . ' AND ?:settings_vendor_values.company_id = ?:companies.company_id';

        $group = ' GROUP BY ?:companies.company_id';

        $fields = implode(', ', $fields);
        return $this->db->getSingleHash(
            'SELECT ?p FROM ?:companies ?p WHERE 1 ?p ORDER BY ?:companies.company',
            ['company_id', 'value'],
            $fields,
            $join,
            $group
        );
    }

    /**
     * Gets setting value for all storefronts.
     *
     * @param string $setting_name Setting name
     * @param string $section_name Section name
     *
     * @return array<int, string|int>|bool Array of setting values with storefront_ids as keys on success, false otherwise
     */
    public function getAllStorefrontValues($setting_name, $section_name = '')
    {
        if (!$setting_name) {
            return false;
        }

        if (fn_allowed_for('ULTIMATE')) {
            return $this->getAllVendorsValues($setting_name, $section_name);
        }

        $fields = [
            '?:storefronts.storefront_id',
            '?:settings_objects.object_id as object_id',
            '?:settings_objects.type as object_type',
            'IF(?:settings_vendor_values.value IS NULL, ?:settings_objects.value, ?:settings_vendor_values.value) as value',
        ];

        $id = $this->getId($setting_name, $section_name);

        $join = $this->db->quote('LEFT JOIN ?:settings_objects ON ?:settings_objects.object_id = ?i', $id);
        $join .= ' LEFT JOIN ?:settings_vendor_values ON ?:settings_vendor_values.object_id = ?:settings_objects.object_id'
            . ' AND ?:settings_vendor_values.storefront_id = ?:storefronts.storefront_id';

        $group = ' GROUP BY ?:storefronts.storefront_id';

        $fields = implode(', ', $fields);
        return $this->db->getSingleHash(
            'SELECT ?p FROM ?:storefronts ?p WHERE 1 ?p ORDER BY ?:storefronts.storefront_id',
            ['storefront_id', 'value'],
            $fields,
            $join,
            $group
        );
    }

    /**
     * Provides variants of a setting object.
     *
     * Usage (examples):
     *  // Addons
     *  Settings::instance->get_variants('affiliate', 'payment_period');
     *
     *  // Core same as addons but if $section_tab_name is empty it will be setted to 'main'
     *  Settings::instance->get_variants('general', 'feedback_type');
     *
     *  // Return variants only by setting id, but function not check custom variant functions
     *  Settings::instance->get_variants('', '', '', 40);
     *
     *  // Return variants only by setting id, and checks custom variant functions
     *  Settings::instance->get_variants('affiliate', 'payment_period', '', 40);
     *
     * @param string $section_name     Setting section name
     * @param string $setting_name     Setting name
     * @param string $section_tab_name Setting section tab name
     * @param int    $object_id        Id of setting in setting_objects table
     * @param string $lang_code        Two-letter language code
     *
     * @return array<int|string> Setting variants
     */
    public function getVariants($section_name, $setting_name, $section_tab_name = '', $object_id = null, $lang_code = CART_LANGUAGE)
    {
        fn_get_schema('settings', 'variants.functions', 'php', true);

        $variants = array();

        // Generate custom variants
        $addon_variant_func = 'fn_settings_variants_addons_'  . fn_strtolower($section_name) . '_' . fn_strtolower($setting_name);

        $core_variant_func = (
            'fn_settings_variants_'
            . fn_strtolower($section_name) . '_'
            . ($section_tab_name != 'main' ? fn_strtolower($section_tab_name) . '_' : '')
            . fn_strtolower($setting_name)
        );

        if (function_exists($addon_variant_func)) {
            $variants = $addon_variant_func();
        } elseif (function_exists($core_variant_func)) {
            $variants = $core_variant_func($this->storefront_id);
        } else {
            $addon_variant_post_func = $addon_variant_func . '_post';
            $core_variant_post_func = $core_variant_func . '_post';

            // If object id is 0 try to get it from section name and setting name
            if ($object_id === null || $object_id === 0) {
                $object_id = $this->getId($setting_name, $section_name);
            }

            if (($object_id !== null && $object_id !== 0) || $object_id == 'all') {
                if ($object_id == 'all') {
                    $object_condition = '';
                } else {
                    $object_condition = $this->db->quote('?:settings_variants.object_id = ?i AND', $object_id);
                }
                $_variants = $this->db->getArray(
                    'SELECT ?:settings_variants.*, ?:settings_descriptions.value, ?:settings_descriptions.object_type'
                    . ' FROM ?:settings_variants'
                    . ' INNER JOIN ?:settings_descriptions'
                    . ' ON ?:settings_descriptions.object_id = ?:settings_variants.variant_id AND object_type = ?s'
                    . ' WHERE ?p ?:settings_descriptions.lang_code = ?s ORDER BY ?:settings_variants.position',
                    Settings::VARIANT_DESCRIPTION,
                    $object_condition,
                    $lang_code
                );

                fn_update_lang_objects('variants', $_variants);

                foreach ($_variants as $variant) {
                    if ($object_id == 'all') {
                        $variants[$variant['name']] = array(
                            'value' => $variant['value'],
                        );
                    } else {
                        $variants[$variant['name']] = $variant['value'];
                    }
                }

                if (function_exists($addon_variant_post_func)) {
                    $variants = $addon_variant_post_func($variants);
                } elseif (function_exists($core_variant_post_func)) {
                    $variants = $core_variant_post_func($variants);
                }
            } else {
                if (Debugger::isActive() || fn_is_development()) {
                    $message = str_replace('[option_id]', $setting_name, __('setting_has_no_variants'));
                    fn_set_notification(NotificationSeverity::ERROR, __('error'), $message);
                }

                return $variants;
            }
        }

        return $variants;
    }

    /**
     * Gets variant by its name.
     *
     * @param string $section_name Setting section
     * @param string $setting_name Setting name
     * @param string $variant_name Setting variant name
     * @param string $lang_code    Two-letter language code
     *
     * @return array<string, int|string> Variant data
     */
    public function getVariant($section_name, $setting_name, $variant_name, $lang_code = CART_LANGUAGE)
    {
        $object_id = $this->getId($setting_name, $section_name);
        $object_condition = $this->db->quote('?:settings_variants.object_id = ?i AND', $object_id);

        return $this->db->getRow(
            'SELECT ?:settings_variants.*, ?:settings_descriptions.value, ?:settings_descriptions.object_type'
            . ' FROM ?:settings_variants'
            . ' LEFT JOIN ?:settings_descriptions'
            . ' ON ?:settings_descriptions.object_id = ?:settings_variants.variant_id AND object_type = ?s AND ?:settings_descriptions.lang_code = ?s'
            . ' WHERE ?p ?:settings_variants.name = BINARY ?s',
            Settings::VARIANT_DESCRIPTION,
            $lang_code,
            $object_condition,
            $variant_name
        );
    }

    /**
     * Updates variant of setting.
     *
     * Variant data must be array in this format (example):
     * Array (
     *      'variant_id' => 1
     *      'object_id'  => 3,
     *      'name'       => 'hide',
     *      'position'   => 10,
     * );
     *
     * If some parameter will be skipped and function not update it field.
     * If variant_id skipped function adds new variant and retuns id of new record.
     *
     * @param  array   $variant_data Array of variant data
     *
     * @return bool|int Variant identifier if variant was created, true on success update, false otherwise
     */
    public function updateVariant($variant_data)
    {
        return $this->db->replaceInto('settings_variants', $variant_data);
    }

    /**
     * Removes variant by its ID.
     *
     * @param int $variant_id Variant identifier
     *
     * @return bool True on succes, false when $variant_id is not specified
     */
    public function removeVariant($variant_id)
    {
        if (!$variant_id) {
            $this->generateError(__('unable_to_delete_setting_variant'), __('empty_key_value'));

            return false;
        }

        $this->db->query('DELETE FROM ?:settings_variants WHERE variant_id = ?i', $variant_id);
        $this->removeDescription($variant_id, Settings::VARIANT_DESCRIPTION);

        return true;
    }

    /**
     * Removes all setting variants.
     *
     * @param  string $setting_id Setting identifier
     *
     * @return bool   True on success, false when $setting_id was not specified
     */
    public function removeSettingVariants($setting_id)
    {
        if (!$setting_id) {
            $this->generateError(__('unable_to_delete_setting_variant'), __('empty_key_value'));

            return false;
        }

        $variants = $this->db->getColumn('SELECT variant_id FROM ?:settings_variants WHERE object_id = ?i', $setting_id);
        foreach ($variants as $variant_id) {
            $this->removeVariant($variant_id);
        }

        return true;
    }

    /**
     * Gets setting description.
     *
     * @param  int               $object_id   Identifier of object that has description
     * @param  string            $object_type Type of object (Use CSettings *_DESCRIPTION constants)
     * @param  string            $lang_code   @ letters language code
     *
     * @return string|false Setting description, or false when $object_id, $object_type or $lang_code is not specified
     */
    public function getDescription($object_id, $object_type, $lang_code = CART_LANGUAGE)
    {
        if (!$object_id || !$object_type || !$lang_code) {
            return false;
        }

        return $this->db->getField(
            'SELECT value FROM ?:settings_descriptions'
            . ' WHERE object_id = ?i AND object_type = ?s AND lang_code = ?s',
            $object_id,
            $object_type,
            $lang_code
        );
    }

    /**
     * Updates settings description.
     *
     * Description data must be array in this format (example):
     *  array(
     *      'value'     => 'General',
     *      'tooltip'   => 'General tab',
     *      'object_id' => '1',
     *      'object_type' => 'S',
     *      'lang_code' => 'en'
     *  )
     *
     * If some parameter will be skipped and function not update it field.
     * If name or lang_code skipped function adds new description and returns true.
     *
     * @param array<string, int|string> $data Description data
     *
     * @return bool  True on success, false when object_type, object_id or lang_code is not specified in $data
     */
    public function updateDescription($data)
    {
        if (!$data['object_type'] || !$data['object_id'] || !$data['lang_code']) {
            $this->generateError(__('unable_to_update_setting_description'), __('empty_key_value'));

            return false;
        }

        $this->db->replaceInto('settings_descriptions', $data);

        return true;
    }

    /**
     * Removes description of a setting object.
     *
     * @param string $object_id   Setting object id
     * @param string $object_type Type of object to remove variant
     * @param string $lang_code   Two-letter language code
     *
     * @return bool   True on success, false when $object_id or $object_type is not specified
     */
    public function removeDescription($object_id, $object_type, $lang_code = '')
    {
        if (!$object_id || !$object_type) {
            $this->generateError(__('unable_to_delete_setting_description'), __('empty_key_value'));

            return false;
        }

        $lang_condition = '';
        if (!empty($lang_code)) {
            $lang_condition = $this->db->quote('AND lang_code = ?s', $lang_code);
        }

        $this->db->query(
            'DELETE FROM ?:settings_descriptions WHERE object_id = ?i AND object_type = ?s ?p',
            $object_id,
            $object_type,
            $lang_condition
        );

        return true;
    }

    /**
     * Checks whether settings section can be accessed by a company.
     *
     * @param string $section_id Section identifier
     * @param int    $company_id Company ID
     *
     * @return bool
     */
    public function checkPermissionCompanyId($section_id, $company_id)
    {
        $allow = true;

        if (fn_allowed_for('ULTIMATE')) {
            $section = $this->getSectionByName($section_id, Settings::CORE_SECTION, false);

            if (!empty($section['edition_type'])) {
                $edition_prefix = $this->getCurrentEditionPrefix();
                $setting_editions = explode(',', $section['edition_type']);

                if (array_search(self::NONE, $setting_editions) === false) {
                    if ($company_id) {
                        $allow = (bool) array_intersect(
                            [self::VENDOR, self::STOREFRONT, $edition_prefix .  self::VENDOR, $edition_prefix . self::STOREFRONT],
                            $setting_editions
                        );
                    } else {
                        $allow = in_array(self::ROOT, $setting_editions);
                    }
                }
            }
        }

        return $allow;
    }

    /**
     * Generates error notification.
     *
     * @param string $action Performed action
     * @param string $reason Reason, why the error notification must be showed
     * @param string $table  Table name (optional)
     *
     * @return bool   Always true
     */
    private function generateError($action, $reason, $table = '')
    {
        $message = str_replace('[reason]', $reason, $action);
        if (!empty($table)) {
            $message = str_replace('[table]', $table, $message);
        }

        fn_log_event('settings', 'error', $message);

        if (Debugger::isActive() || fn_is_development()) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), $message);
        }

        return true;
    }

    /**
     * Returns plain list of settings.
     *
     * @param array<string> $fields          String in SQL format with fields to get from db
     * @param string        $section_id      If defined function returns list of option for this section
     * @param string        $section_tab_id  If defined function returns list of option for this tab of section
     * @param bool          $no_headers      If true function gets all settings that type is not 'H'
     * @param string        $extra_condition Extra SQL condition
     * @param bool          $is_global       If true return oly global options
     * @param int           $company_id      Company identifier
     * @param string        $lang_code       Two-letter language code
     * @param int           $storefront_id   Storefront identifier
     *
     * @return array<array<string, int|string>>|bool List of settings on success, false otherwise
     */
    private function getPlainList(
        array $fields,
        $section_id = '',
        $section_tab_id = '',
        $no_headers = false,
        $extra_condition = '',
        $is_global = true,
        $company_id = null,
        $lang_code = '',
        $storefront_id = null
    ) {
        $company_id = $this->getCompanyId($company_id);
        $storefront_id = $this->getStorefrontId($storefront_id, $company_id);

        $global_condition = $is_global
            ? $this->db->quote(' AND is_global = ?s', YesNo::YES)
            : '';
        $condition = !empty($section_id)
            ? $this->db->quote(' AND section_id = ?i', $section_id)
            : $global_condition;

        $condition .= !empty($section_tab_id)
            ? $this->db->quote(' AND section_tab_id = ?i', $section_tab_id)
            : '';
        $condition .= $this->generateEditionCondition('?:settings_objects', false);
        if ($no_headers) {
            $condition .= $this->db->quote(' AND ?:settings_objects.type <> ?s', SettingTypes::HEADER);
        }

        $join = $this->getValueSelectionJoinTables($company_id, $storefront_id);
        $value = $this->getValueSelectionCriteria($company_id, $storefront_id);

        if (!empty($lang_code)) {
            $join[] = $this->db->quote(
                ' LEFT JOIN ?:settings_descriptions'
                . ' ON ?:settings_descriptions.object_id = ?:settings_objects.object_id'
                . ' AND ?:settings_descriptions.object_type = ?s AND lang_code = ?s',
                self::SETTING_DESCRIPTION,
                $lang_code
            );
            $fields[] = $this->db->quote('?:settings_descriptions.value as description');
            $fields[] = $this->db->quote('?:settings_descriptions.tooltip as tooltip');
            $fields[] = $this->db->quote('?:settings_descriptions.object_type as object_type');
        } else {
            $fields[] = $this->db->quote('?:settings_objects.name as description');
        }

        $fields[] = $value;
        $fields = implode(', ', $fields);
        $join = implode(' ', $join);

        return $this->db->getArray(
            'SELECT ?p FROM ?:settings_objects ?p WHERE 1 ?p ORDER BY ?:settings_objects.position',
            $fields,
            $join,
            $condition . $extra_condition
        );
    }

    /**
     * Generates SQL condition for edition types.
     *
     * @param string $table            Name of table that condition generated. Must be in SQL notation with placeholder
     *                                 for place database prefix.
     * @param bool   $use_access_level Use or ignore edition and type access conditions (ROOT, MSE:VENDOR, etc...)
     *
     * @return string SQL condition
     */
    private function generateEditionCondition($table, $use_access_level = true)
    {
        $edition_conditions = $_edition_conditions = array();

        $_edition_conditions = $this->getEditableSettingsEditionTypes($use_access_level);

        foreach ($_edition_conditions as $edition_condition) {
            $edition_conditions[] = $this->db->quote('FIND_IN_SET(?s, ?p.edition_type)', $edition_condition, $table);
        }

        return ' AND (' . implode(' OR ', $edition_conditions) . ')';
    }

    /**
     * Normalizes the setting value. Forms an array ids of selected values
     *
     * @param array $setting Setting data
     *
     * @return array Normalize value
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    private function setCheckedValueIds(array $setting)
    {
        $value_ids = [];
        foreach ((array) $setting['value'] as $key => $value) {
            // phpcs:ignore
            if ($value === YesNo::YES) {
                $value_ids[] = $key;
            }
        }

        return $value_ids;
    }

    /**
     * Unpacks setting value.
     *
     * @param string|null $value       Setting or null value
     * @param bool        $force_parse Whether setting value should be parsed anyway
     *
     * @return array<string>|int|string|null Unpacked value
     */
    public function unserializeValue($value, $force_parse = false)
    {
        if ($value === null) {
            return $value;
        }

        if (strpos($value, '#M#') === 0) {
            parse_str(str_replace('#M#', '', $value), $value);
        } elseif ($force_parse) {
            parse_str($value, $value);
        }

        return $value;
    }

    /**
     * Packs setting value.
     *
     * @param array<string|int>|string $value Setting value
     *
     * @return int|string|null Packed value
     */
    public function serializeValue($value)
    {
        if (is_array($value)) {
            $value = '#M#' . implode('=Y&', $value) . '=Y';
        } elseif ($value === self::NULL_VALUE) {
            $value = null;
        }

        return $value;
    }

    /**
     * Checks whether a setting or section can be updated in current edition.
     *
     * @param array<string, int|string> $data Setting object data to check
     *
     * @return bool
     */
    private function checkEdition($data)
    {
        $allow = true;

        if (!empty($data['edition_type'])) {
            $edition_names = $this->getCurrentEditionPrefix();
            $setting_editions = explode(',', $data['edition_type']);

            if (
                array_search(self::ROOT, $setting_editions) === false
                && array_search(self::VENDOR, $setting_editions) === false
                && array_search(self::STOREFRONT, $setting_editions) === false
                && array_search($edition_names . self::ROOT, $setting_editions) === false
                && array_search($edition_names . self::VENDOR, $setting_editions) === false
                && array_search($edition_names . self::STOREFRONT, $setting_editions) === false
                && array_search(self::NONE, $setting_editions) === false
            ) {
                $allow = false;
            }
        }

        return $allow;
    }

    /**
     * Gets company ID to use when selecting or updating setting values.
     *
     * @param int $company_id Company identifier
     *
     * @return int|null
     */
    public function getCompanyId($company_id = null)
    {
        if ($company_id !== null && (!$this->company_id || (int) $company_id === (int) $this->company_id)) {
            return (int) $company_id;
        }

        if ($this->company_id !== null) {
            return $this->company_id;
        }

        return null;
    }

    /**
     * Checks whether settings service functions in the "All storefronts" mode.
     *
     * @return bool
     */
    protected function isRootMode()
    {
        return $this->is_root_mode;
    }

    /**
     * Provides human readable setting value.
     *
     * Example:
     * <code>
     *     $setting_data = Settings::instance()->getSettingDataByName('default_products_view');
     *     $description = Settings::instance()->getValueReadable($setting_data);
     * </code>
     *
     * $description will contain the value of the "Product list default view" setting
     * as displayed on Settings > Appearance page,
     * i.e. 'Grid', 'List without options' or 'Compact list'
     *
     * @param  array $setting_data Setting data from Settings::getSettingDataByName
     * @param  mixed $value Value of setting to get description for. When not specified, uses current value.
     *
     * @return string Human readable setting value
     */
    public static function getValueReadable($setting_data, $value = null)
    {
        if (is_null($value)) {
            $value = $setting_data['value'];
        }

        switch ($setting_data['type']) {
            case SettingTypes::PASSWORD:
                return str_repeat('*', 8);
            case SettingTypes::CHECKBOX:
                return YesNo::toBool($value)
                    ? __('yes')
                    : __('no');
            case SettingTypes::RADIOGROUP:
            case SettingTypes::SELECTBOX:
            case SettingTypes::SELECTBOX_WITH_SOURCE:
                return isset($setting_data['variants'][$value]) ? $setting_data['variants'][$value] : __('none');
            case SettingTypes::MULTIPLE_SELECT:
            case SettingTypes::MULTIPLE_CHECKBOXES:
            case SettingTypes::MULTIPLE_CHECKBOXES_FOR_SELECTBOX: // variants for SettingTypes::SELECTBOX_WITH_SOURCE
            case SettingTypes::SELECTABLE_BOX:
                $values = array();
                foreach($setting_data['variants'] as $key => $variant) {
                    if (
                        isset($value[$key]) && ($value[$key] == 'Y' || $value[$key] === true)
                        || in_array($key, $value)
                    ) {
                        $values[] = $variant;
                    }
                }
                return $values ? implode(', ', $values) : __('no_items');
            case SettingTypes::COUNTRY:
                foreach(fn_get_simple_countries() as $key => $variant) {
                    if ($key == $value) {
                        return $variant;
                    }
                }
                return $value;
            case SettingTypes::TEMPLATE:
            case SettingTypes::PERMANENT_TEMPLATE:
            case SettingTypes::HEADER:
                return '';
            case SettingTypes::INFO:
                return $setting_data['info'];
            case SettingTypes::STATE: // we are unable to determine the state without a country
            case SettingTypes::FILE:
            case SettingTypes::TEXTAREA:
            case SettingTypes::INPUT:
            case SettingTypes::NUMBER:
            case SettingTypes::HIDDEN:
            case SettingTypes::PHONE:
            default:
                return $value;
        }
    }

    /**
     * Provides editable settings' edition types.
     *
     * @param bool $use_access_level Whether to perform check as a company administrator
     *
     * @return array Edition types
     */
    public function getEditableSettingsEditionTypes($use_access_level = true)
    {
        $edition_types = [
            self::STOREFRONT,
        ];

        if ($this->company_id) {
            $edition_types[] = self::VENDOR;
        }

        if (
            !$use_access_level
            || $this->isRootMode()
            || !$this->getStorefrontId(null, $this->getCompanyId())
        ) {
            $edition_types[] = self::ROOT;
        }

        // product-specific types: ULT:ROOT, MVE:ROOT, ULT:VENDOR, MVE:VENDOR
        foreach ($edition_types as $type) {
            $edition_types[] = $this->getCurrentEditionPrefix() . $type;
        }

        return $edition_types;
    }

    /**
     * Checks whether setting can be overridden on a per storefront or a per company basis.
     *
     * @param string $edition_type Setting edition type
     *
     * @return bool
     *
     * @deprecated since 4.12.1. Use \Tygh\Settings::areOverriddenValuesSupportedByEdition instead.
     */
    public function isVendorValuesSupportedByEditionType($edition_type)
    {
        return $this->areOverriddenValuesSupportedByEdition($edition_type);
    }

    /**
     * Checks whether setting can be overridden on a per storefront or a per company basis.
     *
     * @param string $edition_type Setting edition type
     * @param bool   $is_root_mode_forced Whether to force root mode
     *
     * @return bool
     */
    public function areOverriddenValuesSupportedByEdition($edition_type, $is_root_mode_forced = false)
    {
        $edition_type = explode(',', $edition_type);

        $overriding_edition_types = $this->getEditionTypesThatOverrideRootValues();

        return ($this->isRootMode() || $is_root_mode_forced)
            && $this->hasMultipleStorefronts()
            && array_intersect($edition_type, $overriding_edition_types);
    }

    /**
     * Gets storefront ID to use when selecting or updating setting values.
     *
     * @param int|null $storefront_id Storefront ID
     * @param int|null $company_id    Company ID
     *
     * @return int|null
     */
    public function getStorefrontId($storefront_id, $company_id)
    {
        if ($storefront_id !== null && (!$this->storefront_id || $storefront_id = $this->storefront_id)) {
            return (int) $storefront_id;
        }

        $company_id = $this->getCompanyId($company_id);
        if (fn_allowed_for('ULTIMATE') && $company_id) {
            $storefront = StorefrontProvider::getRepository()->findByCompanyId($company_id);
            if ($storefront) {
                return $storefront->storefront_id;
            }
        }

        if ($this->storefront_id !== null) {
            return $this->storefront_id;
        }

        return null;
    }

    /**
     * Gets setting edition types that can override ROOT setting on a per storefront or a per company basis.
     *
     * @return array<string>
     */
    private function getEditionTypesThatOverrideRootValues()
    {
        return [
            self::VENDOR,
            self::VENDORONLY,
            self::STOREFRONT,
            $this->getCurrentEditionPrefix() . self::VENDOR,
            $this->getCurrentEditionPrefix() . self::VENDORONLY,
            $this->getCurrentEditionPrefix() . self::STOREFRONT,
        ];
    }

    /**
     * Checks whether multiple storefronts exist in the store.
     *
     * @return bool
     */
    private function hasMultipleStorefronts()
    {
        return $this->has_multiple_storefronts;
    }

    /**
     * Checks whether single storefront exists in the store.
     *
     * @return bool
     */
    private function hasSingleStorefront()
    {
        return !$this->has_multiple_storefronts;
    }

    /**
     * Gets JOIN conditions to use when selecting setting values.
     *
     * @param int $company_id    Company ID
     * @param int $storefront_id Storefront ID
     *
     * @return array<string, string>
     */
    private function getValueSelectionJoinTables($company_id, $storefront_id)
    {
        $join = [];

        if ($company_id) {
            $join['company_values'] = $this->db->quote(
                ' LEFT JOIN ?:settings_vendor_values AS company_values' .
                ' ON company_values.object_id = ?:settings_objects.object_id' .
                ' AND company_values.company_id = ?i AND company_values.storefront_id = ?i',
                $company_id,
                $storefront_id
            );
        }

        if ($storefront_id) {
            $join['storefront_values'] = $this->db->quote(
                ' LEFT JOIN ?:settings_vendor_values AS storefront_values' .
                ' ON storefront_values.object_id = ?:settings_objects.object_id' .
                ' AND storefront_values.storefront_id = ?i AND storefront_values.company_id = ?i',
                $storefront_id,
                0
            );
        }

        return $join;
    }

    /**
     * Gets SQL expression to select setting value field.
     *
     * @param int $company_id    Company ID
     * @param int $storefront_id Storefront ID
     *
     * @return string
     */
    private function getValueSelectionCriteria($company_id, $storefront_id)
    {
        $value_sources = [
            'main' => '?:settings_objects.value',
            'alt' => [],
        ];

        if ($company_id) {
            $value_sources['alt'][] = [
                'condition' => 'company_values.object_id',
                'value'     => 'company_values.value'
            ];
        }

        if ($storefront_id) {
            $value_sources['alt'][] = [
                'condition' => 'storefront_values.object_id',
                'value'     => 'storefront_values.value'
            ];
        }

        if (!$value_sources['alt']) {
            return "{$value_sources['main']} AS value";
        }

        $value = '(CASE';
        foreach ($value_sources['alt'] as $alt_field) {
            $value .= " WHEN {$alt_field['condition']} IS NOT NULL THEN {$alt_field['value']}";
        }
        $value .= " ELSE {$value_sources['main']} END) AS value";

        return $value;
    }

    /**
     * Gets table to update settings in.
     *
     * @param array<string> $edition_types Setting edition types
     * @param int           $storefront_id Storefront ID
     *
     * @return string
     */
    private function getUpdateValueTable(array $edition_types, $storefront_id)
    {
        $table = '';

        $overriding_edition_types = $this->getEditionTypesThatOverrideRootValues();
        if ($this->hasMultipleStorefronts() && $storefront_id) {
            if (array_intersect($overriding_edition_types, $edition_types)) {
                $table = 'settings_vendor_values';
            }
        } else {
            if (!in_array($this->getCurrentEditionPrefix() . Settings::NONE, $edition_types)) {
                $table = 'settings_objects';
            }
        }

        return $table;
    }

    /**
     * Gets data to store in a database when updating a setting value.
     *
     * @param int             $object_id     Setting ID
     * @param int|null|string $value         Setting value
     * @param array<string>   $edition_types Setting edition types
     * @param int             $company_id    Company ID
     * @param int             $storefront_id Storefront ID
     *
     * @return array<string, int|string|null>
     */
    private function getUpdateValueData($object_id, $value, array $edition_types, $company_id, $storefront_id)
    {
        $data = [
            'object_id' => $object_id,
            'value'     => $value,
        ];

        $overriding_edition_types = $this->getEditionTypesThatOverrideRootValues();
        if ($this->hasMultipleStorefronts() && $storefront_id && array_intersect($overriding_edition_types, $edition_types)) {
            $data['company_id'] = (int) $company_id;
            $data['storefront_id'] = (int) $storefront_id;
        }

        return $data;
    }

    /**
     * Gets settings values
     *
     * @param int|null $company_id    Company ID
     * @param int|null $storefront_id Storefront ID
     *
     * @return array<string, string>
     */
    public static function getSettingsValues($company_id = null, $storefront_id = null)
    {
        $key = sprintf('storefront_settings_values_%s_%s', $company_id ?: '_', $storefront_id ?: '_');

        if (isset(self::$settings_values_cache[$key])) {
            return self::$settings_values_cache[$key];
        }

        self::$settings_values_cache[$key] = Registry::getOrSetCache(
            ['storefront_settings_values', $key],
            ['settings_objects', 'settings_vendor_values', 'settings_sections', 'settings_variants'],
            ['static', 'storefront'],
            static function () use ($company_id, $storefront_id) {
                return Settings::instance(['company_id' => $company_id, 'storefront_id' => $storefront_id])->getValues();
            }
        );

        return self::$settings_values_cache[$key];
    }

    /**
     * Gets setting value
     *
     * @param string   $setting_path  Setting path (General.global_options_type, etc)
     * @param int|null $company_id    Company ID
     * @param int|null $storefront_id Storefront ID
     *
     * @return mixed
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     */
    public static function getSettingValue($setting_path, $company_id = null, $storefront_id = null)
    {
        $settings = self::getSettingsValues($company_id, $storefront_id);

        return fn_dot_syntax_get($setting_path, $settings);
    }
}
