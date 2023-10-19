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

namespace Tygh\Languages;

use Tygh\Addons\SchemesManager;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Languages\Helper as LanguageHelper;
use Tygh\Languages\Values as LanguageValues;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Themes\Themes;
use Tygh\Tygh;

class Languages
{
    const DIRECTION_RTL = 'rtl';
    const DIRECTION_LTR = 'ltr';

    /** @var array Internal cache for available languages list */
    protected static $cache_available_languages = array();

    /**
     * Defines default translator language code
     *
     * @const TRANSLATION_LANGUAGE 2-letters language code
     */
    const TRANSLATION_LANGUAGE = 'en';

    /**
     * Gets list of languages by specified params
     *
     * @param array $params Extra condition for languages
     *      lang_code    - 2-letters language identifier
     *      lang_id      - integer language number (key in DB)
     *      name         - Name of language
     *      status       - 1-letter status code (A - active, H - hidden, D - disabled)
     *      country_code - Linked country code
     * @param string $hash_key Keys of returned array with languages.
     *  Example:
     *   hash_key - lang_code
     *      [en] => array(data)
     *      [bg] => array(data)
     *
     *   hash_key - lang_id
     *      [1] => array(data)
     *      [7] => array(data)
     * @return array $langs_data Languages list
     */
    public static function get($params, $hash_key = 'lang_code')
    {
        $field_list = db_quote("?:languages.*");
        $join = $group_by = $order_by = $limit = $condition = "";

        if (!empty($params['lang_code'])) {
            $condition .= db_quote(' AND lang_code = ?s', $params['lang_code']);
        }

        if (!empty($params['lang_id'])) {
            $condition .= db_quote(' AND lang_id IN (?n)', (array) $params['lang_id']);
        }

        if (!empty($params['name'])) {
            $condition .= db_quote(' AND name = ?s', $params['name']);
        }

        if (!empty($params['status'])) {
            $condition .= db_quote(' AND status IN (?a)', (array) $params['status']);
        }

        if (!empty($params['country_code'])) {
            $condition .= db_quote(' AND country_code = ?s', $params['country_code']);
        }

        /**
         * Modify get languages list by specified parameters SQL query parameters
         *
         * @param array   $params         Extra condition for languages
         * @param string  $hash_key       Keys of returned array with languages
         * @param string  $field_list     String of comma-separated SQL fields to be selected in an SQL-query
         * @param string  $join           String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
         * @param string  $condition      String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
         * @param string  $group_by       String containing the SQL-query GROUP BY field
         * @param string  $order_by       String containing the SQL-query ORDER BY field
         * @oaram string  $limit          String containing the SQL-query LIMIT field
         */
        fn_set_hook('get_languages', $params, $hash_key, $field_list, $join, $condition, $group_by, $order_by, $limit);

        $langs_data = db_get_hash_array('SELECT ?p FROM ?:languages ?p WHERE 1 ?p ?p ?p ?p', $hash_key, $field_list, $join, $condition, $group_by, $order_by, $limit);

        $langs_data = self::afterFind($langs_data);

        return $langs_data;
    }

    /**
     * Gets list of all languages defined in store
     * used for adding desciptions and etc.
     *
     * @param  boolean $edit Flag that determines if language list is used to be edited
     * @return array   $languages Languages list
     */
    public static function getAll($edit = false)
    {
        $field_list = db_quote("?:languages.*");
        $join = $group_by = $order_by = $limit = $condition = "";

        /**
         * Modify all languages list SQL query parameters
         *
         * @param boolean $edit           Flag that determines if language list is used to be edited
         * @param string  $field_list     String of comma-separated SQL fields to be selected in an SQL-query
         * @param string  $join           String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
         * @param string  $condition      String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
         * @param string  $group_by       String containing the SQL-query GROUP BY field
         * @param string  $order_by       String containing the SQL-query ORDER BY field
         * @oaram string  $limit          String containing the SQL-query LIMIT field
         */
        fn_set_hook('get_all_languages', $edit, $field_list, $join, $condition, $group_by, $order_by, $limit);

        $languages = db_get_hash_array("SELECT ?p FROM ?:languages ?p WHERE 1 ?p ?p ?p ?p", 'lang_code', $field_list, $join, $condition, $group_by, $order_by, $limit);
        $languages = self::afterFind($languages);

        /**
         * Adds additional languages to all language list
         *
         * @param array   $languages Languages list
         * @param boolean $edit      Flag that determines if language list is used to be edited
         */
        fn_set_hook('get_translation_languages', $languages, $edit);

        return $languages;
    }

    /**
     * Updates language
     *
     * @param  array  $language_data Language data
     * @param  string $lang_id       language id
     * @return string language id
     */
    public static function update($language_data, $lang_id)
    {
        if (!$language_data || empty($language_data['lang_code'])) {
            return false;
        }

        /**
         * Changes language data before update
         *
         * @param array  $language_data Language data
         * @param string $lang_id       language id
         */
        fn_set_hook('update_language_pre', $language_data, $lang_id);

        $language_data['lang_code'] = trim($language_data['lang_code']);
        $language_data['lang_code'] = substr($language_data['lang_code'], 0, 2);

        $action = false;

        $is_exists = db_get_field("SELECT COUNT(*) FROM ?:languages WHERE lang_code = ?s AND lang_id <> ?i", $language_data['lang_code'], $lang_id);

        $language_id = $lang_id;
        $previous_storefronts = [];
        $previous_language_data = [];
        if (!empty($is_exists)) {
            fn_set_notification('E', __('error'), __('error_lang_code_exists', array(
                '[code]' => $language_data['lang_code']
            )));

            $lang_id = false;

        } elseif (empty($lang_id)) {
            if (!empty($language_data['lang_code']) && !empty($language_data['name'])) {
                $lang_id = db_query("INSERT INTO ?:languages ?e", $language_data);
                $clone_from =  !empty($language_data['from_lang_code']) ? $language_data['from_lang_code'] : CART_LANGUAGE;

                LanguageHelper::cloneLanguage($language_data['lang_code'], $clone_from);

                $action = 'add';
            }

        } else {
            $previous_language_data = static::get(['lang_id' => $lang_id]);
            $previous_language_data = reset($previous_language_data);
            $res = db_query('UPDATE ?:languages SET ?u WHERE lang_id = ?i', $language_data, $lang_id);
            if (!$res) {
                $lang_id = null;
            }

            $repository = StorefrontProvider::getRepository();
            list($previous_storefronts,) = $repository->find(['language_ids' => $language_id, 'get_total' => false]);

            $action = 'update';
        }

        if (isset($language_data['storefront_ids'])) {
            $repository = StorefrontProvider::getRepository();
            list($new_storefronts,) = $repository->find(['storefront_id' => $language_data['storefront_ids'], 'get_total' => false]);
            list($all_storefronts,) = $repository->find(['get_total' => false]);

            $default_languages_storefronts = [];

            /** @var \Tygh\Storefront\Storefront $storefront */
            foreach ($all_storefronts as $storefront) {
                $params = [
                    'storefront_id' => $storefront->storefront_id
                ];

                if (fn_allowed_for('ULTIMATE')) {
                    $company_id = $storefront->getCompanyIds();
                    $params['company_id'] = reset($company_id);
                }

                $default_language = Settings::instance($params)->getValue(
                    'frontend_default_language',
                    'Appearance'
                );
                $default_languages_storefronts[$default_language][] = $storefront->storefront_id;
            }

            $storefront_ids = explode(',', $language_data['storefront_ids']);

            if (!empty($language_data['storefront_ids']) && isset($default_languages_storefronts[$language_data['lang_code']])) {
                $affected_storefronts = array_diff($default_languages_storefronts[$language_data['lang_code']], $storefront_ids);
            }

            $added_storefronts = array_diff_key($new_storefronts, $previous_storefronts);
            foreach ($added_storefronts as $storefront) {
                if (!empty($affected_storefronts) && !in_array($storefront->storefront_id, $affected_storefronts)) {
                    continue;
                }
                $repository->save($storefront->addLanguageIds($language_id));
            }
            $removed_storefronts = array_diff_key($previous_storefronts, $new_storefronts);
            foreach ($removed_storefronts as $storefront) {
                if (!empty($affected_storefronts) && in_array($storefront->storefront_id, $affected_storefronts)) {
                    continue;
                }
                $repository->save($storefront->removeLanguageIds($language_id));
            }

            if (!empty($affected_storefronts)) {
                $storefronts_names = array_map(static function ($storefront_id) use ($all_storefronts) {
                    return $all_storefronts[$storefront_id]->name;
                }, $affected_storefronts);

                fn_set_notification(
                    NotificationSeverity::ERROR,
                    __('error'),
                    __(
                        'removing_default_language_from_storefront',
                        [
                            count($affected_storefronts),
                            '[language_name]'   => $language_data['name'],
                            '[storefront_name]' => count($storefronts_names) > 1
                                ? implode(', ', $storefronts_names)
                                : reset($storefronts_names),
                        ]
                    )
                );
            }
        }

        self::$cache_available_languages = [];

        /**
         * Adds additional actions after language update
         *
         * @param array  $language_data          Language data
         * @param string $lang_id                language id
         * @param string $action                 Current action ('add', 'update' or bool false if failed to update language)
         * @param array  $previous_language_data Previous language state
         */
        fn_set_hook('update_language_post', $language_data, $lang_id, $action, $previous_language_data);

        return $lang_id;
    }

    /**
     * Removes languages.
     *
     * @param int|array<int> $lang_ids     List of language ids
     * @param string         $default_lang Default language code
     *
     * @return array<string>  Deleted language codes
     */
    public static function deleteLanguages($lang_ids, $default_lang = DEFAULT_LANGUAGE)
    {
        $lang_ids = array_filter((array) $lang_ids);
        if (!$lang_ids) {
            return [];
        }

        /**
         * Adds additional actions before languages deleting
         *
         * @param array $lang_ids List of language ids
         */
        fn_set_hook('delete_languages_pre', $lang_ids);

        $db_descr_tables = fn_get_description_tables();

        $removed_languages = static::get(['lang_id' => $lang_ids]);
        $deleted_lang_codes = [];

        foreach ($removed_languages as $lang_code => $lang_data) {
            if ($lang_code === $default_lang) {
                fn_set_notification(
                    NotificationSeverity::WARNING,
                    __('warning'),
                    __(
                        'warning_not_deleted_default_language',
                        [
                            '[lang_name]' => $lang_data['name'],
                        ]
                    ),
                    '',
                    'language_is_default'
                );
                continue;
            }

            $res = db_query('DELETE FROM ?:languages WHERE lang_code = ?s', $lang_code);
            if ($res) {
                $deleted_lang_codes[] = $lang_code;
            }

            foreach ($db_descr_tables as $table) {
                db_query('DELETE FROM ?:?f WHERE lang_code = ?s', $table, $lang_code);
            }
        }

        self::$cache_available_languages = [];

        $storefronts_repository = StorefrontProvider::getRepository();
        /** @var \Tygh\Storefront\Storefront[] $storefronts */
        list($storefronts,) = $storefronts_repository->find(['language_ids' => $lang_ids, 'get_total' => false]);
        foreach ($storefronts as $storefront) {
            $storefronts_repository->save($storefront->removeLanguageIds($lang_ids));
        }

        self::saveLanguagesIntegrity();

        $lang_codes = array_column($removed_languages, 'lang_code', 'lang_id');

        /**
         * Executes after languages are deleted, allows you to perform additional actions.
         *
         * @param array<int>         $lang_ids           List of language ids
         * @param array<int, string> $lang_codes         List of language codes
         * @param array<string>      $deleted_lang_codes List of deleted language codes
         */
        fn_set_hook('delete_languages_post', $lang_ids, $lang_codes, $deleted_lang_codes);

        return $deleted_lang_codes;
    }

    /**
     * Prevents usage of deleted and disabled languages.
     *
     * @param string $default_lang Two-letter language code
     *
     * @return bool   Always true
     */
    public static function saveLanguagesIntegrity($default_lang = CART_LANGUAGE)
    {
        $avail = db_get_field('SELECT COUNT(*) FROM ?:languages WHERE status = ?s', ObjectStatuses::ACTIVE);
        if (!$avail) {
            $default_lang_exists = db_get_field('SELECT COUNT(1) FROM ?:languages WHERE lang_code = ?s', $default_lang);
            if (!$default_lang_exists) {
                $default_lang = db_get_field('SELECT lang_code FROM ?:languages WHERE status = ?s LIMIT 1', ObjectStatuses::HIDDEN);
                if (!$default_lang) {
                    $default_lang = db_get_field('SELECT lang_code FROM ?:languages LIMIT 1');
                }
            }
            db_query('UPDATE ?:languages SET status = ?s WHERE lang_code = ?s', ObjectStatuses::ACTIVE, $default_lang);
        }

        $settings_checks = [
            'frontend' => ObjectStatuses::ACTIVE,
            'backend'  => [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN],
        ];

        $default_langs = [
            'frontend' => Registry::get('settings.Appearance.frontend_default_language') ?? $default_lang,
            'backend'  => Registry::get('settings.Appearance.backend_default_language') ?? $default_lang,
        ];
        $settings_changed = false;

        foreach ($settings_checks as $zone => $statuses) {
            $available = db_get_field('SELECT COUNT(1) FROM ?:languages WHERE lang_code = ?s AND status IN (?a)', $default_langs[$zone], $statuses);
            if ($available) {
                continue;
            }
            $first_avail_code = db_get_field('SELECT lang_code FROM ?:languages WHERE status IN (?a) LIMIT 1', $statuses);
            Settings::instance()->updateValue($zone . '_default_language', $first_avail_code, 'Appearance');
            $default_langs[$zone] = $first_avail_code;
            $settings_changed = true;
        }

        $available_codes = db_get_fields("SELECT lang_code FROM ?:languages WHERE status = 'A'");

        if (fn_allowed_for('MULTIVENDOR')) {
            db_query('UPDATE ?:companies SET lang_code = ?s WHERE lang_code NOT IN (?a)', $default_langs['backend'], $available_codes);
        }

        db_query('UPDATE ?:users SET lang_code = ?s WHERE lang_code NOT IN (?a)', $default_langs['frontend'], $available_codes);
        db_query('UPDATE ?:orders SET lang_code = ?s WHERE lang_code NOT IN (?a)', $default_langs['frontend'], $available_codes);

        if ($settings_changed) {
            fn_set_notification(
                NotificationSeverity::WARNING,
                __('warning'),
                __(
                    'warning_default_language_disabled',
                    [
                        '[link]' => fn_url('settings.manage?section_id=Appearance'),
                    ]
                )
            );
        }

        static::saveStorefrontLanguageIntegrity();

        /**
         * Executes after removing usages of deleted and disabled languages.
         *
         * @param string $default_lang Two-letter language code
         * @param bool $settings_changed True if language settings were changed
         */
        fn_set_hook('save_languages_integrity_post', $default_lang, $settings_changed);

        return true;
    }

    /**
     * Returns only active languages list (as lang_code => array(name, lang_code, status, country_code)
     *
     * @param string $default_value  Default value defined in Block scheme
     * @param array  $block          filled block data
     * @param array  $block_scheme   Scheme of current block
     * @param bool   $include_hidden if true get hidden languages too
     * @param array  $params         extra params
     *      area - get languages for specified area. Default: "C"
     * @return array Languages list
     */
    public static function getActive($default_value = '', $block = array(), $block_scheme = array(), $include_hidden = false, $params = array())
    {
        $language_condition = $include_hidden ? "WHERE status <> 'D'" : "WHERE status = 'A'";

        $area = isset($params['area']) ? $params['area'] : AREA;

        $languages = db_get_hash_array("SELECT lang_code, name, status, country_code FROM ?:languages ?p", 'lang_code', $language_condition);
        $languages = self::afterFind($languages);

        return $languages;
    }

    /**
     * Gets only active languages list (as lang_code => name)
     *
     * @param bool $include_hidden If true get hidden languages too
     * @param int  $storefront_id  Storefront ID
     *
     * @return array<string, string> Languages list
     */
    public static function getSimpleLanguages($include_hidden = false, $storefront_id = null)
    {
        $field_list = db_quote("?:languages.lang_code, ?:languages.name");
        $join = $order_by = $group_by = $limit = "";
        $condition = $include_hidden ? db_quote("AND ?:languages.status <> 'D'") : db_quote("AND ?:languages.status = 'A'");

        if (fn_allowed_for('ULTIMATE') && !$storefront_id && fn_get_runtime_company_id()) {
            $storefront_id = StorefrontProvider::getStorefront()->storefront_id;
        }

        if ($storefront_id) {
            $join .= db_quote(
                ' LEFT JOIN ?:storefronts_languages AS storefronts_languages'
                . ' ON storefronts_languages.language_id = ?:languages.lang_id'
            );
            $condition .= db_quote(
                ' AND (storefronts_languages.storefront_id = ?i OR storefronts_languages.storefront_id IS NULL)',
                $storefront_id
            );
        }

        /**
         * Modify simple languages list SQL query parameters
         *
         * @param boolean $include_hidden Get not-disabled languages
         * @param string  $field_list     String of comma-separated SQL fields to be selected in an SQL-query
         * @param string  $join           String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
         * @param string  $condition      String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
         * @param string  $group_by       String containing the SQL-query GROUP BY field
         * @param string  $order_by       String containing the SQL-query ORDER BY field
         * @oaram string  $limit          String containing the SQL-query LIMIT field
         */
        fn_set_hook('get_simple_languages', $include_hidden, $field_list, $join, $condition, $group_by, $order_by, $limit);

        $languages = db_get_hash_single_array("SELECT ?p FROM ?:languages ?p WHERE 1 ?p ?p ?p ?p", array('lang_code', 'name'), $field_list, $join, $condition, $group_by, $order_by, $limit);
        $languages = self::afterFind($languages, true);

        return $languages;
    }

    /**
     * Returns active and hidden languages list (as lang_code => array(name, lang_code, status, country_code)
     *
     * @param array $params
     *
     * @return array  Languages list
     */
    public static function getAvailable($params = [])
    {
        // FIXME: #STOREFRONTS: Backward compatibility
        if (!is_array($params) || $params === []) {
            $legacy_params = $params === []
                ? []
                : func_get_args();
            $params = static::convertLegacyGetAvailableParams($legacy_params);
        }

        $params = array_merge([
            'area'           => null,
            'include_hidden' => false,
            'language_ids'   => null,
            'storefront_id'  => null,
        ], $params);

        if ($params['area'] === 'C' && $params['storefront_id'] === null) {
            /** @var \Tygh\Storefront\Storefront $storefront */
            $storefront = Tygh::$app['storefront'];
            $params['storefront_id'] = $storefront->storefront_id;
        }

        /**
         * Executes when getting available languages in the very beginning of the function,
         * allows you to modify the parameters passed to the function.
         *
         * @param array $params Language search parameters
         */
        fn_set_hook('languages_get_available_pre', $params);

        $area = (string) $params['area'];
        $include_hidden = $params['include_hidden'];

        $cache_key = serialize($params);

        if (!isset(self::$cache_available_languages[$cache_key])) {
            $field_list = db_quote("?:languages.*");
            $join = $order_by = $group_by = $limit = "";
            $condition = $include_hidden ? db_quote("AND ?:languages.status <> 'D'") : db_quote("AND ?:languages.status = 'A'");

            if ($area == 'C' && defined('CART_LOCALIZATION')) {
                $join .= db_quote(" LEFT JOIN ?:localization_elements ON ?:localization_elements.element = ?:languages.lang_code AND ?:localization_elements.element_type = 'L'");
                $condition .= db_quote(' AND ?:localization_elements.localization_id = ?i', CART_LOCALIZATION);
                $order_by .= db_quote(" ORDER BY ?:localization_elements.position ASC");
            }

            if ($params['language_ids'] !== null) {
                $condition .= db_quote(' AND ?:languages.lang_id IN (?n)', (array) $params['language_ids']);
            }

            if ($params['storefront_id'] !== null) {
                $join .= db_quote(
                    ' LEFT JOIN ?:storefronts_languages AS storefronts_languages'
                    . ' ON storefronts_languages.language_id = ?:languages.lang_id'
                );
                $condition .= db_quote(
                    'AND (storefronts_languages.storefront_id = ?i OR storefronts_languages.storefront_id IS NULL)',
                    $params['storefront_id']
                );
            }

            /**
             * Modify available languages list SQL query parameters
             *
             * @param string  $area           One-letter site area code
             * @param boolean $include_hidden Include not-disabled languages
             * @param string  $field_list     String of comma-separated SQL fields to be selected in an SQL-query
             * @param string  $join           String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
             * @param string  $condition      String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
             * @param string  $group_by       String containing the SQL-query GROUP BY field
             * @param string  $order_by       String containing the SQL-query ORDER BY field
             * @oaram string  $limit          String containing the SQL-query LIMIT field
             */
            fn_set_hook('get_available_languages', $area, $include_hidden, $field_list, $join, $condition, $group_by, $order_by, $limit, $params);

            $languages = db_get_hash_array("SELECT ?p FROM ?:languages ?p WHERE 1 ?p ?p ?p ?p", 'lang_code', $field_list, $join, $condition, $group_by, $order_by, $limit);

            self::$cache_available_languages[$cache_key] = self::afterFind($languages, true);
        }

        return self::$cache_available_languages[$cache_key];
    }

    /**
     * Gets meta information from the PO file
     *
     * @param  string $base_path Root dir
     * @param  string $pack_file PO file name (without .po extension)
     * @param  bool   $reinstall Use this flag, if pack was alread installed before to get META information
     * @return array  List of lang pack meta information
     */
    public static function getLangPacksMeta($base_path = '', $pack_file = '', $reinstall = false, $check_installed = true)
    {
        $installed_languages = $check_installed ? self::getAll(true) : array();

        $path = empty($base_path) ? Registry::get('config.dir.lang_packs') : $base_path;
        $return = array();

        if (empty($pack_file)) {
            $po_file_list = array();
            foreach (fn_get_dir_contents($path, true, false) as $pack_directory) {
                if (is_dir($path . $pack_directory)) {
                    $po_file_list[] = $pack_directory . '/core.po';
                }
            }
        } else {
            $po_file_list = array($pack_file);
        }

        foreach ($po_file_list as $po_file_name) {
            $po_file_path = $path . $po_file_name;

            if (!file_exists($po_file_path)) {
                fn_set_notification(
                    NotificationSeverity::ERROR,
                    __('error'),
                    __('incorrect_po_pack_structure_file_not_exist', [
                        '[pack_path]' => fn_get_rel_dir(dirname($po_file_path)),
                        '[po_file]' => fn_get_rel_dir($po_file_path)
                    ])
                );

                continue;
            }

            $metadata = Po::getMeta($po_file_path);

            if (is_array($metadata)) {
                if (!self::isValidMeta($metadata)) {
                    fn_set_notification('E', __('error'), __('po_file_is_incorrect', array('[file]' => fn_get_rel_dir($po_file_path))));

                    continue;
                }
            } else {
                fn_set_notification('E', __('error'), $metadata);

                continue;
            }

            if (isset($installed_languages[$metadata['lang_code']]) && !$reinstall) {
                continue;
            }

            $return[] = $metadata;
        }

        if (!empty($pack_file) && !empty($return)) {
            return reset($return);
        }

        return $return;
    }

    /**
     * Explodes meta data by variables
     * Example:
     *  array(
     *      'Pack-Name: English',
     *      'Lang-Code: EN',
     *      'Country-Code: US'
     *  )
     *
     * @param  array $msg list of meta data
     * @return array Exploded properties
     *  Example:
     *   array(
     *      'name' => 'english',
     *      'lang_code' => 'en',
     *      'country_code' => 'us',
     *   )
     */
    public static function readMetaProperties($msg)
    {
        $properties = array();

        foreach ($msg as $_prop) {
            if (!empty($_prop)) {
                list($name, $value) = explode(':', $_prop);
                $name = strtolower(str_replace('-', '_', trim($name)));

                $properties[$name] = trim($value);
            }
        }

        return $properties;
    }

    /**
     * Checks if PO Meta information is valid
     *
     * @param  array $meta PO Meta-data
     * @return bool  true if meta information is valid
     */
    public static function isValidMeta($meta)
    {
        if (empty($meta)) {
            return false;
        }

        $required_fields = array(
            'lang_code',
            'name',
            'country_code',
        );

        foreach ($required_fields as $field) {
            if (empty($meta[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Installs new language from PO pack
     *
     * @param string $po_file_path Path to .po file
     * @param array  $params
     *                              reinstall: Skip adding new language
     *                              validate_lang_code:Check meta information (lang_code) with updated language data (lang_code) and forbid to update if does not match
     *                              force_lang_code: Skip meta lang code and use this one in all laguage variables
     *                              check_integrity: If language integrity check is required
     *
     * @return bool|int (bool) false on failure, (bool) true on success
     *                  or (int) ID of newly created language (if it was created successfully).
     */
    public static function installLanguagePack($po_file_path, array $params = [])
    {
        $default_params = [
            // Skip adding new language
            'reinstall'            => false,
            // Check meta information (lang_code) with updated language data (lang_code) and forbid to update if does not match
            'validate_lang_code'   => '',
            // Skip meta lang code and use this one in all laguage variables
            'force_lang_code'      => '',
            // Install only newly added language variables (except Addons translations and Settings)
            'install_newly_added'  => false,
            // If calling the saveLanguagesIntegrity method is required
            'check_integrity' => true,
        ];

        $params = array_merge($default_params, $params);

        $lang_meta = self::getLangPacksMeta(
            dirname($po_file_path) . '/',
            basename($po_file_path),
            $params['reinstall'],
            false
        );

        if (empty($lang_meta)) {
            return false;
        }

        if (!empty($params['validate_lang_code']) && $lang_meta['lang_code'] != $params['validate_lang_code']) {
            fn_set_notification('E', __('error'), __('po_meta_error_validating_lang_code'));

            return false;
        }

        $result = false;

        if (!Registry::get('runtime.company_id')) {

            if ($params['reinstall']) {
                $result = true;
            } else {
                $language_data = [
                    'lang_code' => $lang_meta['lang_code'],
                    'name' => $lang_meta['name'],
                    'country_code' => $lang_meta['country_code'],
                ];
                $result = self::update($language_data, 0);
            }

            if ($result !== false) {
                if ($params['check_integrity'] === true) {
                    self::saveLanguagesIntegrity();
                }

                $query = [];
                $original_values_query = [];
                $iteration = 0;
                $max_vars_in_query = 500;

                if (!empty($params['force_lang_code'])) {
                    $lang_meta['lang_code'] = $params['force_lang_code'];
                }

                $lang_data = Po::getValues($po_file_path, 'Languages');

                if (!is_array($lang_data)) {
                    fn_set_notification('E', __('error'), $lang_data);

                    return false;
                }

                $install_newly_added = !empty($params['install_newly_added']);
                if ($install_newly_added) {
                    list($values) = LanguageValues::getVariables([], 0, $lang_meta['lang_code']);
                    $exists_variables = [];
                    foreach ($values as $value) {
                        $exists_variables[$value['name']] = $value['value'];
                    }

                    unset($values);
                }

                foreach ($lang_data as $var_name => $var_data) {
                    if (!empty($var_name)) {
                        $id = $var_data['id'];
                        $value = implode('', $var_data['msgstr']);
                        $original_value = $var_data['msgid'];
                        $value = empty($value) ? $original_value : $value;

                        if ($install_newly_added && isset($exists_variables[$id])) {
                            continue;
                        }

                        $query[] = db_quote('(?s, ?s, ?s)', $lang_meta['lang_code'], trim($id), trim($value));
                        $original_values_query[] = db_quote('(?s, ?s)', $var_name, trim($original_value));
                    }

                    if ($iteration > $max_vars_in_query) {
                        self::executeLangQueries('language_values', ['lang_code', 'name', 'value'], $query);
                        self::executeLangQueries('original_values', ['msgctxt', 'msgid'], $original_values_query);

                        $query = [];
                        $original_values_query = [];

                        $iteration = 0;
                    }

                    $iteration++;
                }

                unset($exists_variables);

                self::executeLangQueries('language_values', ['lang_code', 'name', 'value'], $query);
                self::executeLangQueries('original_values', ['msgctxt', 'msgid'], $original_values_query);

                $settings_sections = Po::getValues($po_file_path, 'SettingsSections');

                $query = [];
                $original_values_query = [];
                $iteration = 0;

                foreach ($settings_sections as $var_name => $var_data) {
                    if (!empty($var_name)) {
                        $value = implode('', $var_data['msgstr']);
                        $original_value = $var_data['msgid'];
                        $value = empty($value) ? $original_value : $value;

                        if (!empty($var_data['parent'])) {
                            $parent_id = db_get_field('SELECT section_id FROM ?:settings_sections WHERE name = ?s AND type = ?s', $var_data['parent'], Settings::ADDON_SECTION);
                            $section_id = db_get_field('SELECT section_id FROM ?:settings_sections WHERE name = ?s AND parent_id = ?i', $var_data['id'], $parent_id);
                        } else {
                            $section_id = db_get_field('SELECT section_id FROM ?:settings_sections WHERE name = ?s', $var_data['id']);
                        }

                        if (empty($section_id)) {
                            continue;
                        }

                        $query[] = db_quote('(?i, ?s, ?s, ?s)', $section_id, 'S', $lang_meta['lang_code'], trim($value), trim($original_value));
                        $original_values_query[] = db_quote('(?s, ?s)', trim($var_name), trim($original_value));
                    }

                    if ($iteration > $max_vars_in_query) {
                        self::executeLangQueries('settings_descriptions', ['object_id', 'object_type', 'lang_code', 'value'], $query);
                        self::executeLangQueries('original_values', ['msgctxt', 'msgid'], $original_values_query);

                        $query = [];
                        $iteration = 0;
                    }

                    $iteration++;
                }

                self::executeLangQueries('settings_descriptions', ['object_id', 'object_type', 'lang_code', 'value'], $query);
                self::executeLangQueries('original_values', ['msgctxt', 'msgid'], $original_values_query);

                $original_values_query = [];
                $setting_options = Po::getValues($po_file_path, 'SettingsOptions');

                foreach ($setting_options as $var_name => $var_data) {
                    if (!empty($var_name)) {
                        $value = implode('', $var_data['msgstr']);
                        $original_value = $var_data['msgid'];
                        $value = empty($value) ? $original_value : $value;
                        $object = Settings::instance()->getId($var_data['id'], $var_data['parent']);

                        if (empty($object)) {
                            continue;
                        }

                        $query = [
                            'object_id' => $object,
                            'object_type' => 'O',
                            'lang_code' => $lang_meta['lang_code'],
                            'value' => trim($value)
                        ];

                        $update = [
                            'value' => trim($value)
                        ];

                        $original_values_query[] = db_quote('(?s, ?s)', trim($var_name), trim($original_value));

                        db_query('INSERT INTO ?:settings_descriptions ?e ON DUPLICATE KEY UPDATE ?u', $query, $update);
                    }
                }

                if (!empty($original_values_query)) {
                    self::executeLangQueries('original_values', ['msgctxt', 'msgid'], $original_values_query);
                }

                $original_values_query = [];
                $settings_tooltips = Po::getValues($po_file_path, 'SettingsTooltips');

                foreach ($settings_tooltips as $var_name => $var_data) {
                    if (!empty($var_name)) {
                        $value = implode('', $var_data['msgstr']);
                        $original_value = $var_data['msgid'];
                        $value = empty($value) ? $original_value : $value;
                        $object = Settings::instance()->getId($var_data['id'], $var_data['parent']);

                        if (empty($object)) {
                            continue;
                        }

                        $query = [
                            'object_id' => $object,
                            'object_type' => 'O',
                            'lang_code' => $lang_meta['lang_code'],
                            'tooltip' => trim($value)
                        ];

                        $update = [
                            'tooltip' => trim($value)
                        ];

                        $original_values_query[] = db_quote('(?s, ?s)', trim($var_name), trim($original_value));

                        db_query('INSERT INTO ?:settings_descriptions ?e ON DUPLICATE KEY UPDATE ?u', $query, $update);
                    }
                }

                if (!empty($original_values_query)) {
                    self::executeLangQueries('original_values', ['msgctxt', 'msgid'], $original_values_query);
                }

                $setting_variants = Po::getValues($po_file_path, 'SettingsVariants');
                $query = [];
                $original_values_query = [];
                $iteration = 0;

                foreach ($setting_variants as $var_name => $var_data) {
                    if (!empty($var_name)) {
                        $value = implode('', $var_data['msgstr']);
                        $original_value = $var_data['msgid'];
                        $value = empty($value) ? $original_value : $value;

                        $object = Settings::instance()->getVariant($var_data['section'], $var_data['parent'], $var_data['id']);

                        if (empty($object)) {
                            continue;
                        }

                        $query[] = db_quote('(?i, ?s, ?s, ?s)', $object['variant_id'], 'V', $lang_meta['lang_code'], trim($value));
                        $original_values_query[] = db_quote('(?s, ?s)', trim($var_name), trim($original_value));
                    }

                    if ($iteration > $max_vars_in_query) {
                        self::executeLangQueries('settings_descriptions', ['variant_id', 'object_type', 'lang_code', 'value'], $query);
                        self::executeLangQueries('original_values', ['msgctxt', 'msgid'], $original_values_query);

                        $query = [];
                        $iteration = 0;
                    }

                    $iteration++;
                }

                self::executeLangQueries('settings_descriptions', ['object_id', 'object_type', 'lang_code', 'value'], $query);
                self::executeLangQueries('original_values', ['msgctxt', 'msgid'], $original_values_query);

                $addons = Po::getValues($po_file_path, 'Addons');
                $query = [];
                $original_values_query = [];

                if (!empty($addons)) {
                    $exists_variables = db_get_hash_array('SELECT addon, name, description FROM ?:addon_descriptions WHERE lang_code = ?s', 'addon', $lang_meta['lang_code']);

                    foreach ($addons as $var_name => $var_data) {
                        if (!empty($var_name)) {
                            $value = implode('', $var_data['msgstr']);
                            $original_value = $var_data['msgid'];
                            $value = empty($value) ? $original_value : $value;

                            if ($var_data['parent'] == 'name') {
                                db_query('UPDATE ?:addon_descriptions SET name = ?s WHERE addon = ?s AND lang_code = ?s', trim($value), $var_data['id'], $lang_meta['lang_code']);
                            } else {
                                db_query('UPDATE ?:addon_descriptions SET description = ?s WHERE addon = ?s AND lang_code = ?s', trim($value), $var_data['id'], $lang_meta['lang_code']);
                            }

                            $original_values_query[] = db_quote('(?s, ?s)', trim($var_name), trim($original_value));
                        }
                    }

                    self::executeLangQueries('original_values', ['msgctxt', 'msgid'], $original_values_query);
                }

                $profile_fields = Po::getValues($po_file_path, 'ProfileFields');
                $query = [];
                $original_values_query = [];

                foreach ($profile_fields as $var_name => $var_data) {
                    if (!empty($var_name)) {
                        $value = implode('', $var_data['msgstr']);
                        $original_value = $var_data['msgid'];
                        $value = empty($value) ? $original_value : $value;

                        $field_ids = db_get_fields('SELECT field_id FROM ?:profile_fields WHERE field_name = ?s', $var_data['id']);

                        if (empty($field_ids)) {
                            continue;
                        }

                        foreach ($field_ids as $field_id) {
                            if ($install_newly_added) {
                                $exists = db_get_field('SELECT COUNT(*) FROM ?:profile_field_descriptions WHERE object_id = ?i AND object_type = ?s AND lang_code = ?s', $field_id, 'F', $lang_meta['lang_code']);

                                if (!empty($exists)) {
                                    continue;
                                }
                            }

                            $query[] = db_quote('(?i, ?s, ?s, ?s)', $field_id, trim($value), 'F', $lang_meta['lang_code']);
                            $original_values_query[] = db_quote('(?s, ?s)', trim($var_name), trim($original_value));
                        }
                    }
                }

                self::executeLangQueries('profile_field_descriptions', ['object_id', 'description', 'object_type', 'lang_code'], $query);
                self::executeLangQueries('original_values', ['msgctxt', 'msgid'], $original_values_query);

                if (!$params['reinstall']) {
                    fn_set_notification('N', __('notice'), __('text_changes_saved'));
                }
                $_suffix = '';
            }
        }

        return $result;
    }

    /**
     * Inserts new data to the description tables.
     *
     * @param  string $table  Table name without prefix
     * @param  array  $fields List of table fields to be updated
     * @param  array  $data   New data
     * @return bool   db_query result
     */
    public static function executeLangQueries($table, $fields, $query)
    {
        if (empty($query)) {
            return false;
        }

        return db_query('REPLACE INTO ?:' . $table . ' (' . implode(',', $fields) . ') VALUES ' . implode(', ', $query));
    }

    public static function getOriginalValues($context)
    {
        return db_get_hash_array('SELECT * FROM ?:original_values WHERE msgctxt LIKE ?l', 'msgctxt', $context . ':%');
    }

    /**
     * Creates PO file for specified Language
     *
     * @param string $lang_code 2-letters language code (Example: "en", "ru")
     * @param string $output    Output destination
     *      screen - Output countent direct to browser page
     *      download - Force file downloading
     *      server - upload file to the config.dir.lang_packs directory
     */
    public static function export($lang_code, $output = 'download')
    {
        $mve_lang_variables = array();

        // Translation packs should not include "Not translated" language data
        $allow_overlap = $lang_code == 'en' ? true : false;
        $default_lang = Registry::get('settings.Appearance.backend_default_language');

        $langs = self::get(array('lang_code' => $lang_code));
        $lang = $langs[$lang_code];

        $pack_path = fn_get_cache_path(false) . 'lang_pack/';
        $core_pack_path = $pack_path . 'core.po';
        $mve_pack_path = $pack_path . 'editions/mve.po';

        fn_rm($pack_path);
        fn_mkdir($pack_path);

        if (fn_allowed_for('MULTIVENDOR') && is_file(Registry::get('config.dir.lang_packs') . $default_lang . '/editions/mve.po')) {
            $mve_lang_variables = Po::getValues(Registry::get('config.dir.lang_packs') . $default_lang . '/editions/mve.po');
            Po::createHeader($mve_pack_path, $lang);
        }

        Po::createHeader($core_pack_path, $lang);

        // Export Language values
        list($values) = LanguageValues::getVariables(array(), 0, $lang_code);

        foreach ($values as $_id => $value) {
            $values[$_id]['msgctxt'] = 'Languages' . \I18n_Pofile::DELIMITER . $value['name'];
        }

        $values = fn_array_value_to_key($values, 'msgctxt');

        $addons_lang_vars = array();
        list($addons) = fn_get_addons(array('type' => 'active'), 0, $lang_code);
        foreach ($addons as $addon_id => $addon) {
            $addons_lang_vars = array_merge($addons_lang_vars, self::exportAddonsPo($addon_id, $pack_path . 'addons/' . $addon_id . '.po', $lang_code, $values));
        }

        $original_values = self::getOriginalValues('Languages');
        $values = array_diff_key($values, $addons_lang_vars);

        foreach ($values as $_id => $value) {
            $values[$_id]['original_value'] = isset($original_values['Languages' . \I18n_Pofile::DELIMITER . $value['name']]) ? $original_values['Languages::' . $value['name']]['msgid'] : '';
        }

        $values = Po::convert($values, array(), $allow_overlap);
        list($values, $excluded) = self::excludeEditionVariables('Languages', $values, $mve_lang_variables);

        Po::putValues('Languages', $values, $core_pack_path);
        if (!empty($excluded)) {
            Po::putValues('Languages', $excluded, $mve_pack_path);
        }

        // Export Settings Sections
        $sections = Settings::instance()->getCoreSections($lang_code);
        $original_values = self::getOriginalValues('SettingsSections');

        foreach ($sections as $_id => $value) {
            $sections[$_id]['original_value'] = isset($original_values['SettingsSections::' . $value['section_id']]) ? $original_values['SettingsSections::' . $value['section_id']]['msgid'] : '';
        }
        $_sections = Po::convert($sections, array(
            'id' => 'section_id',
            'value' => 'description'
        ), $allow_overlap);
        list($_sections, $excluded) = self::excludeEditionVariables('SettingsSections', $_sections, $mve_lang_variables);

        Po::putValues('SettingsSections', $_sections, $core_pack_path);
        if (!empty($excluded)) {
            Po::putValues('SettingsSections', $excluded, $mve_pack_path);
        }
        unset($_sections);

        $original_options = self::getOriginalValues('SettingsOptions');
        $original_variants = self::getOriginalValues('SettingsVariants');
        $original_tooltips = self::getOriginalValues('SettingsTooltips');

        foreach ($sections as $section) {
            $options = Settings::instance()->getList($section['object_id'], 0, false, null, $lang_code);
            $_options = array();
            $_tooltips = array();

            foreach ($options['main'] as $option) {
                $_options[] = array(
                    'name' => $option['name'],
                    'value' => $option['description'],
                    'original_value' => isset($original_options['SettingsOptions' . \I18n_Pofile::DELIMITER . $option['name']]) ? $original_options['SettingsOptions' . \I18n_Pofile::DELIMITER . $option['name']]['msgid'] : '',
                );

                if (!empty($option['variants'])) {
                    $_variants = array();
                    foreach ($option['variants'] as $variant_id => $variant) {
                        $_variants[] = array(
                            'name' => $variant_id,
                            'value' => $variant,
                            'original_value' => isset($original_variants['SettingsVariants' . \I18n_Pofile::DELIMITER . $option['name'] . \I18n_Pofile::DELIMITER . $variant_id]) ? $original_variants['SettingsVariants' . \I18n_Pofile::DELIMITER . $option['name'] . \I18n_Pofile::DELIMITER . $variant_id]['msgid'] : '',
                        );
                    }

                    $_variants = Po::convert($_variants, array(), $allow_overlap);
                    list($_variants, $excluded) = self::excludeEditionVariables('SettingsVariants', $_variants, $mve_lang_variables);

                    Po::putValues('SettingsVariants' . \I18n_Pofile::DELIMITER . $option['name'], $_variants, $core_pack_path);
                    if (!empty($excluded)) {
                        Po::putValues('SettingsVariants', $excluded, $mve_pack_path);
                    }

                    unset($_variants);
                }

                if (!empty($option['tooltip'])) {
                    $_tooltips[] = array(
                        'name' => $option['name'],
                        'value' => $option['tooltip'],
                        'original_value' => isset($original_tooltips['SettingsTooltips' . \I18n_Pofile::DELIMITER . $option['name']]) ? $original_tooltips['SettingsTooltips' . \I18n_Pofile::DELIMITER . $option['name']]['msgid'] : '',
                    );
                }
            }

            $_options = Po::convert($_options, array(), $allow_overlap);
            list($_options, $excluded) = self::excludeEditionVariables('SettingsOptions', $_options, $mve_lang_variables);

            Po::putValues('SettingsOptions', $_options, $core_pack_path);
            if (!empty($excluded)) {
                Po::putValues('SettingsOptions', $excluded, $mve_pack_path);
            }

            $_tooltips = Po::convert($_tooltips, array(), $allow_overlap);
            list($_tooltips, $excluded) = self::excludeEditionVariables('SettingsTooltips', $_tooltips, $mve_lang_variables);

            Po::putValues('SettingsTooltips', $_tooltips, $core_pack_path);
            if (!empty($excluded)) {
                Po::putValues('SettingsTooltips', $excluded, $mve_pack_path);
            }
        }

        // Export Profile fields
        $profile_fields = fn_get_profile_fields('ALL', array(), $lang_code);
        $original_values = self::getOriginalValues('ProfileFields');
        $values = array();

        foreach ($profile_fields as $zone => $fields) {
            foreach ($fields as $field_id => $field) {
                $values[] = array(
                    'name' => $field['field_name'],
                    'value' => $field['description'],
                    'original_value' => isset($original_values['ProfileFields::' . $field['field_name']]) ? $original_values['ProfileFields::' . $field['field_name']]['msgid'] : '',
                );
            }
        }

        $values = Po::convert($values, array(), $allow_overlap);
        list($values, $excluded) = self::excludeEditionVariables('ProfileFields', $values, $mve_lang_variables);

        Po::putValues('ProfileFields', $values, $core_pack_path);
        if (!empty($excluded)) {
            Po::putValues('ProfileFields', $excluded, $mve_pack_path);
        }

        fn_compress_files($lang_code . '.zip', './', $pack_path);

        $filename = $pack_path . $lang_code . '.zip';
        switch ($output) {
            case 'server':
                fn_copy($filename, Registry::get('config.dir.lang_packs') . $lang_code . '.zip');
                break;

            case 'download':
                fn_get_file($filename, $lang_code . '.zip');
                break;
        }

        return true;
    }

    protected static function excludeEditionVariables($context, $values, $originals)
    {
        $excluded = array();
        if (fn_allowed_for('MULTIVENDOR')) {
            $_values = array();

            foreach ($values as $value) {
                if (isset($originals[$context . \I18n_Pofile::DELIMITER . $value['msgctxt']])) {
                    $excluded[] = $value;
                } else {
                    $_values[] = $value;
                }
            }

            $values = $_values;
        }

        return array($values, $excluded);
    }

    /**
     * Exports only specified add-on language data
     *
     * @param  string $addon_id       Addon ID (like: gift_certificates, buy_together)
     * @param  string $pack_path      Path to exported PO-file
     * @param  string $lang_code      2-letters language code
     * @param  array  $current_values Current lang values from DB
     * @return array  Exported data
     */
    private static function exportAddonsPo($addon_id, $pack_path, $lang_code, $current_values = array())
    {
        // Translation packs should not include "Not translated" language data
        $allow_overlap = $lang_code == 'en' ? true : false;

        $langs = self::get(array('lang_code' => $lang_code));
        $lang = $langs[$lang_code];

        fn_rm($pack_path);

        Po::createHeader($pack_path, $lang);

        $addon_scheme = SchemesManager::getScheme($addon_id);
        $values = array();

        $originals = $addon_scheme->getOriginals();

        $_addon[] = array(
            'name' => $addon_id,
            'value' => $addon_scheme->getName($lang_code),
            'original_value' => $originals['name'],
        );
        $_addon = Po::convert($_addon, array(), $allow_overlap);
        $values = array_merge($values, Po::putValues('Addons' . \I18n_Pofile::DELIMITER . 'name', $_addon, $pack_path));

        $_description[] = array(
            'name' => $addon_id,
            'value' => $addon_scheme->getDescription($lang_code),
            'original_value' => $originals['description'],
        );
        $_description = Po::convert($_description, array(), $allow_overlap);

        $values = array_merge($values, Po::putValues('Addons' . \I18n_Pofile::DELIMITER . 'description', $_description, $pack_path));

        unset($_addon, $_description, $originals);

        $language_variables = $addon_scheme->getLanguageValues(false);
        $original_variables = $addon_scheme->getLanguageValues(true);

        $_originals = array();
        foreach ($original_variables as $id => $val) {
            $_originals[$val['msgctxt']] = $val;
        }

        $original_variables = $_originals;
        unset($_originals);

        $_values = array();

        foreach ($language_variables as $variable) {
            if ($lang_code != $variable['lang_code']) {
                continue;
            }

            $key = 'Languages' . \I18n_Pofile::DELIMITER . $variable['name'];

            $_values[] = array(
                'name' => $variable['name'],
                'value' => isset($current_values[$key]) ? $current_values[$key]['value'] : $variable['value'],
                'original_value' => isset($original_variables[$key]) ? $original_variables[$key]['msgid'] : '',
            );
        }

        $_values = Po::convert($_values, array(), $allow_overlap);
        $values = array_merge($values, Po::putValues('Languages', $_values, $pack_path));

        unset($original_variables, $language_variables);

        $_tooltips = array();
        $original_tooltips = self::getOriginalValues('SettingsTooltips');

        $section = Settings::instance()->getSectionByName($addon_id, Settings::ADDON_SECTION);

        if (!empty($section)) {
            $subsections = Settings::instance()->getSectionTabs($section['section_id'], $lang_code);

            if (!empty($subsections)) {
                $_sub_sections = array();
                $original_values = self::getOriginalValues('SettingsSections');

                foreach ($subsections as $subsection) {
                    $_sub_sections[] = array(
                        'name' => $subsection['name'],
                        'value' => $subsection['description'],
                        'original_value' => isset($original_values['SettingsSections' . \I18n_Pofile::DELIMITER . $addon_id . \I18n_Pofile::DELIMITER . $subsection['name']]) ? $original_values['SettingsSections' . \I18n_Pofile::DELIMITER . $addon_id . \I18n_Pofile::DELIMITER . $subsection['name']]['msgid'] : '',
                    );
                }

                $_sub_sections = Po::convert($_sub_sections, array(), $allow_overlap);
                $values = array_merge($values, Po::putValues('SettingsSections' . \I18n_Pofile::DELIMITER . $addon_id, $_sub_sections, $pack_path));
                unset($_sub_sections, $original_values);
            }

            unset($subsections);

            $section_options = Settings::instance()->getList($section['section_id'], 0, false, null, $lang_code);
            $original_options = self::getOriginalValues('SettingsOptions');
            $original_variants = self::getOriginalValues('SettingsVariants');

            foreach ($section_options as $section_id => $options) {
                $_options = array();

                foreach ($options as $option) {
                    $_options[] = array(
                        'name' => $option['name'],
                        'value' => $option['description'],
                        'original_value' => isset($original_options['SettingsOptions' . \I18n_Pofile::DELIMITER . $addon_id . \I18n_Pofile::DELIMITER . $option['name']]) ? $original_options['SettingsOptions' . \I18n_Pofile::DELIMITER . $addon_id . \I18n_Pofile::DELIMITER . $option['name']]['msgid'] : '',
                    );

                    if (!empty($option['variants'])) {
                        $_variants = array();
                        foreach ($option['variants'] as $variant_id => $variant) {
                            $_variants[] = array(
                                'name' => $variant_id,
                                'value' => $variant,
                                'original_value' => isset($original_variants['SettingsVariants' . \I18n_Pofile::DELIMITER . $addon_id . \I18n_Pofile::DELIMITER . $option['name'] . \I18n_Pofile::DELIMITER . $variant_id]) ? $original_variants['SettingsVariants' . \I18n_Pofile::DELIMITER . $addon_id . \I18n_Pofile::DELIMITER . $option['name'] . \I18n_Pofile::DELIMITER . $variant_id]['msgid'] : '',
                            );
                        }

                        $_variants = Po::convert($_variants, array(), $allow_overlap);
                        $values = array_merge($values, Po::putValues('SettingsVariants' . \I18n_Pofile::DELIMITER . $addon_id . \I18n_Pofile::DELIMITER . $option['name'], $_variants, $pack_path));

                        unset($_variants);
                    }

                    if (!empty($option['tooltip'])) {
                        $_tooltips[] = array(
                            'name' => $option['name'],
                            'value' => $option['tooltip'],
                            'original_value' => isset($original_tooltips['SettingsTooltips' . \I18n_Pofile::DELIMITER . $addon_id . \I18n_Pofile::DELIMITER . $option['name']]) ? $original_tooltips['SettingsTooltips' . \I18n_Pofile::DELIMITER . $addon_id . \I18n_Pofile::DELIMITER . $option['name']]['msgid'] : '',
                        );
                    }
                }

                $_options = Po::convert($_options, array(), $allow_overlap);
                $values = array_merge($values, Po::putValues('SettingsOptions' . \I18n_Pofile::DELIMITER . $addon_id, $_options, $pack_path));

                unset($_options);
            }
        }

        $_tooltips = Po::convert($_tooltips, array(), $allow_overlap);
        $values = array_merge($values, Po::putValues('SettingsTooltips' . \I18n_Pofile::DELIMITER . $addon_id, $_tooltips, $pack_path));

        $values = fn_array_value_to_key($values, 'msgctxt');

        return $values;
    }

    /**
     * Sets new default language for backend/frontend
     *
     * @param  string $lang_code 2-letters language code (en, ru, etc)
     * @return bool   Always true
     */
    public static function changeDefaultLanguage($lang_code)
    {
        Settings::instance()->updateValue('backend_default_language', $lang_code);
        Settings::instance()->updateValue('frontend_default_language', $lang_code);

        Registry::set('settings.Appearance.backend_default_language', $lang_code);
        Registry::set('settings.Appearance.frontend_default_language', $lang_code);

        return true;
    }

    /**
     * This method installs language values stored at so-named "Crowdin-pack directory".
     * The directory should have the following structure:
     *
     * /
     *  /core.po - Core language values. Required.
     *  /upgrade.po - Upgrade language values. Optional.
     *  /editions - Stores edition-specific *.po files. Optional.
     *    /mve.po - Multivendor-specific file.
     *  /addons - Stores add-ons' *.po files. Optional.
     *    /banners.po
     *    /bestsellers.po
     *    /{addon name}.po
     *
     * This methods collects relevant *.po files from given directory and executes {@see Tygh\Languages\Languages::installLanguagePack()} method for each one.
     * "Relevant" means that only installed addons' po-files would be installed as well as mve.po will be installed only for Multivendor edition.
     *
     * @see \Tygh\Languages::installLanguagePack()
     *
     * @param string $path   Path to directory that contains .po files (i.e. PO-pack directory)
     * @param array  $params Parameters passed to {@see Tygh\Languages\Languages::installLanguagePack()} function.
     *
     * @return bool|int (bool) false on failure, (bool) true on success or (int) ID of newly created language (if it was created successfully).
     */
    public static function installCrowdinPack($path, $params)
    {
        $path = rtrim($path, '\\/') . '/';

        $lang_meta = self::getLangPacksMeta($path, 'core.po', true);

        if (empty($lang_meta)) {
            $result = false;
        } else {
            fn_copy($path, Registry::get('config.dir.lang_packs') . $lang_meta['lang_code'] . '/');
            $path = Registry::get('config.dir.lang_packs') . $lang_meta['lang_code'] . '/';

            $result = self::installLanguagePack($path . 'core.po', $params);
        }

        if ($result) {
            $po_files_list = array();

            if (fn_allowed_for('MULTIVENDOR') && file_exists($path . 'editions/mve.po')) {
                $po_files_list[] = $path . 'editions/mve.po';
            }

            if (file_exists($path . 'upgrade.po')) {
                $po_files_list[] = $path . 'upgrade.po';
            }

            list($addons) = fn_get_addons(['type' => 'installed', 'get_unmanaged' => true]);

            foreach ($addons as $addon_id => $addon) {
                if (file_exists($path . 'addons/' . $addon_id . '.po')) {
                    $po_files_list[] = $path . 'addons/' . $addon_id . '.po';
                }
            }

            // install theme translations
            foreach (fn_get_installed_themes() as $theme_name) {
                if ($theme_po_path = Themes::factory($theme_name)->getPoPath($lang_meta['lang_code'])) {
                    $po_files_list[] = $theme_po_path;
                }
            }

            $params['reinstall'] = true;
            foreach ($po_files_list as $po_file) {
                $minor_result = self::installLanguagePack($po_file, $params);

                if (!$minor_result) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Installs new language from ZIP pack
     *
     * @param string $path   Path to ZIP file
     * @param array  $params
     *  reinstall: Skip adding new language
     *  validate_lang_code:Check meta information (lang_code) with updated language data (lang_code) and forbid to update if does not match
     *  force_lang_code: Skip meta lang code and use this one in all laguage variables
     * @return int Language ID
     */
    public static function installZipPack($path, $params = array())
    {
        $result = false;

        // Extract language pack and check the permissions
        $extract_path = fn_get_cache_path(false) . 'tmp/language_pack/';

        // Re-create source folder
        fn_rm($extract_path);
        fn_mkdir($extract_path);

        fn_copy($path, $extract_path . 'pack.zip');

        if (fn_decompress_files($extract_path . 'pack.zip', $extract_path)) {
            fn_rm($extract_path . 'pack.zip');
            $result = self::installCrowdinPack($extract_path, $params);
        } else {
            fn_set_notification('E', __('error'), __('broken_po_pack'));
        }

        return $result;
    }

    /**
     * Obtains locale code by a language code.
     *
     * @param string $lang_code Two-letter language code
     *
     * @return string|null Locale for the language (xx_XX) or null if no locale is found
     */
    public static function getLocaleByLanguageCode($lang_code)
    {
        $languages = static::get(array('lang_code' => $lang_code));
        if (!isset($languages[$lang_code])) {
            return null;
        }

        return sprintf('%s_%s', $languages[$lang_code]['lang_code'], $languages[$lang_code]['country_code']);
    }

    /**
     * Assigns languages for storefronts that have to languages configured.
     *
     * @return void
     */
    public static function saveStorefrontLanguageIntegrity()
    {
        // All storefronts should be checked whether they have configured languages
        $storefront_repository = StorefrontProvider::getRepository();
        list($all_storefronts,) = $storefront_repository->find(['get_total' => false]);
        $languages = static::get(['status' => ObjectStatuses::ACTIVE]);
        foreach ($all_storefronts as $storefront) {
            $storefront_has_languages = false;
            foreach ($languages as $language) {
                if (!$language['storefront_ids']) {
                    $storefront_has_languages = true;
                    break;
                }
                if (in_array($storefront->storefront_id, $language['storefront_ids_raw'])) {
                    $storefront_has_languages = true;
                    break;
                }
            }

            if ($storefront_has_languages) {
                continue;
            }

            // if no languages are assigned to the storefront, assign all languages to it
            $language_ids = array_column($languages, 'lang_id');
            $storefront_repository->save($storefront->addLanguageIds($language_ids));

            fn_set_notification(
                NotificationSeverity::WARNING,
                __('warning'),
                __(
                    'storefront_languages_assigned',
                    [
                        '[url]'  => fn_url('storefronts.update?storefront_id=' . $storefront->storefront_id),
                        '[name]' => $storefront->name,
                    ]
                )
            );
        }
    }

    /**
     * Removes new lines symbols and escapes quotes
     *
     * @param  string $value String to be escaped
     * @return string Escaped string
     */
    private static function _processPoValues($value)
    {
        $value = addslashes($value);
        $value = str_replace(array("\r\n", "\n", "\r"), '', $value);

        return trim($value);
    }

    private static function afterFind($languages, $remove_disabled = false)
    {
        $languages = self::detectLanguageDirection($languages);
        $languages = self::attachStorefrontIds($languages);

        return $languages;
    }

    /**
     * Fills the "direction" field for each given language.
     *
     * @param array $languages List of languages data
     *
     * @return array Language list with "direction" field filled.
     */
    private static function detectLanguageDirection($languages)
    {
        foreach ($languages as $index => $language) {
            if (is_array($language) && isset($language['lang_code'])) {
                $languages[$index]['direction'] = fn_is_rtl_language($language['lang_code'])
                    ? self::DIRECTION_RTL
                    : self::DIRECTION_LTR;
            }
        }

        return $languages;
    }

    /**
     * Converts legacy params for the \Tygh\Languages\Languages::getAvailable function.
     *
     * @param array $legacy_params
     *
     * @internal
     *
     * @return array
     */
    protected static function convertLegacyGetAvailableParams(array $legacy_params)
    {
        $params = [
            'area'           => null,
            'include_hidden' => false,
            'language_ids'   => null,
        ];

        $params['area'] = array_shift($legacy_params);
        if ($params['area'] === null) {
            $params['area'] = AREA;
        }

        if ($legacy_params) {
            $params['include_hidden'] = array_shift($legacy_params);
        }

        return $params;
    }

    protected static function attachStorefrontIds(array $languages)
    {
        array_walk($languages, static function (&$language) {
            if (!is_array($language) || !isset($language['lang_id'])) {
                return;
            }

            list($storefronts,) = StorefrontProvider::getRepository()->find(['language_ids' => $language['lang_id'], 'get_total' => false]);
            $language['storefront_ids'] = implode(',', array_keys($storefronts));
            $language['storefront_ids_raw'] = array_keys($storefronts);
        });

        return $languages;
    }
}
