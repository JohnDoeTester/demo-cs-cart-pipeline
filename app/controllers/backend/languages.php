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

use Tygh\Enum\ObjectStatuses;
use Tygh\Http;
use Tygh\Languages\Languages;
use Tygh\Languages\Values as LanguageValues;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    fn_trusted_vars("lang_data", "new_lang_data");
    $suffix = '.manage';

    //
    // Update language variables
    //
    if ($mode == 'm_update_variables') {
        if (is_array($_REQUEST['lang_data'])) {
            LanguageValues::updateLangVar($_REQUEST['lang_data']);
        }

        $suffix = '.translations';
    }

    //
    // Delete language variables
    //
    if ($mode == 'm_delete_variables') {
        if (!empty($_REQUEST['names'])) {
            LanguageValues::deleteVariables($_REQUEST['names']);
        }

        $suffix = '.translations';
    }

    //
    // Add new language variable
    //
    if ($mode == 'update_variables') {
        if (!empty($_REQUEST['new_lang_data'])) {
            $params = array('clear' => false);
            foreach (Languages::getAll() as $lc => $_v) {
                LanguageValues::updateLangVar($_REQUEST['new_lang_data'], $lc, $params);
            }
        }

        $suffix = '.translations';
    }

    if ($mode == 'update_translation') {
        $uploaded_data = fn_filter_uploaded_data('language_data', array('po', 'zip'));

        if (!empty($uploaded_data['po_file']['path'])) {
            $ext = fn_get_file_ext($uploaded_data['po_file']['name']);

            $params = array(
                'reinstall' => true,
                'validate_lang_code' => $_REQUEST['language_data']['lang_code'],
            );
            if ($ext == 'po') {
                $result = Languages::installLanguagePack($uploaded_data['po_file']['path'], $params);
            } else {
                $result = Languages::installZipPack($uploaded_data['po_file']['path'], $params);
            }

            if (!$result) {
                fn_delete_notification('changes_saved');
            }
        }
    }

    //
    // Update languages
    //
    if ($mode == 'm_update') {

        if (!Registry::get('runtime.company_id')) {
            if (!empty($_REQUEST['update_language'])) {
                foreach ($_REQUEST['update_language'] as $lang_id => $data) {
                    Languages::update($data, $lang_id);
                }
            }

            Languages::saveLanguagesIntegrity();
        }
    }

    //
    // Create/update language
    //
    if ($mode == 'update') {

        $lc = false;
        $errors = false;

        if (!Registry::get('runtime.company_id')) {
            $lang_data = $_REQUEST['language_data'];

            if (!$errors) {
                $lc = Languages::update($lang_data, $_REQUEST['lang_id']);
            }

            if ($lc !== false) {
                Languages::saveLanguagesIntegrity();
            }
        }

        if ($lc == false) {
            fn_delete_notification('changes_saved');
        }
    }

    if ($mode == 'install_from_po') {
        $uploaded_data = fn_filter_uploaded_data('language_data', array('po', 'zip'));

        if (!empty($uploaded_data['po_file']['path'])) {
            $ext = fn_get_file_ext($uploaded_data['po_file']['name']);

            if ($ext == 'po') {
                $result = Languages::installLanguagePack($uploaded_data['po_file']['path']);
            } else {
                $result = Languages::installZipPack($uploaded_data['po_file']['path']);
            }

            if (!$result) {
                fn_delete_notification('changes_saved');
            }
        }
    }

    if ($mode == 'install' && !empty($_REQUEST['pack'])) {
        $pack_path = Registry::get('config.dir.lang_packs') . fn_basename($_REQUEST['pack']);

        if (Languages::installCrowdinPack($pack_path, array())) {
            return array(CONTROLLER_STATUS_OK, 'languages.manage');
        } else {
            return array(CONTROLLER_STATUS_OK, 'languages.manage?selected_section=available_languages');
        }
    }

    if ($mode == 'delete_variable') {

        LanguageValues::deleteVariables($_REQUEST['name']);

        return array(CONTROLLER_STATUS_REDIRECT);
    }

    if ($mode === 'update_status') {
        $params = array_merge(
            [
                'lang_ids' => null,
                'id'       => null,
                'status'   => ObjectStatuses::ACTIVE,
            ],
            $_REQUEST
        );

        $language_ids = $params['lang_ids'] === null
            ? (array) $params['id']
            : (array) $params['lang_ids'];

        foreach (array_filter($language_ids) as $language_id) {
            fn_tools_update_status(
                [
                    'table'   => 'languages',
                    'id_name' => 'lang_id',
                    'id'      => (int) $language_id,
                    'status'  => $params['status'],
                ]
            );
        }

        Languages::saveLanguagesIntegrity();
    }

    if ($mode == 'clone_language') {
        $lang_id = $_REQUEST['lang_id'];
        $lang_data = Languages::get(array('lang_id' => $lang_id), 'lang_id');

        if (!empty($lang_data) && !empty($_REQUEST['lang_code'])) {
            $language = $lang_data[$lang_id];

            $new_language = array(
                'lang_code' => $_REQUEST['lang_code'],
                'name' => $language['name'] . '_clone',
                'country_code' => $language['country_code'],
                'from_lang_code' => $language['lang_code'],
                'status' => 'D', // Disable cloned language
            );

            $lc = Languages::update($new_language, 0);

            if ($lc !== false) {
                Languages::saveLanguagesIntegrity();
            }
        }
    }

    if ($mode == 'export_language') {
        $lang_id = $_REQUEST['lang_id'];
        $lang_data = Languages::get(array('lang_id' => $lang_id), 'lang_id');

        if (!empty($lang_data)) {
            Languages::export($lang_data[$lang_id]['lang_code']);
        }
    }

    if ($mode === 'delete_language' || $mode === 'm_delete') {
        $params = array_merge(
            [
                'lang_ids' => null,
                'lang_id'  => null,
            ],
            $_REQUEST
        );

        $language_ids = $params['lang_ids'] === null
            ? (array) $params['lang_id']
            : (array) $params['lang_ids'];

        Languages::deleteLanguages($language_ids);

        return [CONTROLLER_STATUS_REDIRECT, 'languages.manage?selected_section=languages'];
    }

    if (isset($_REQUEST['redirect_url'])) {
        $redirect_url = $_REQUEST['redirect_url'];
    } else {
        $q = empty($_REQUEST['q'])
            ? ''
            : $_REQUEST['q'];
        $redirect_url = 'languages' . $suffix . '?q=' . $q;
    }

    return [CONTROLLER_STATUS_OK, $redirect_url];
}

//
// Get language variables values
//
if ($mode == 'manage') {

    $sections = array(
        'translations' => array(
            'title' => __('translations'),
            'href' => fn_url('languages.translations'),
        ),
        'manage_languages' => array(
            'title' => __('manage_languages'),
            'href' => fn_url('languages.manage'),
        ),
    );

    Registry::set('navigation.dynamic.sections', $sections);
    Registry::set('navigation.dynamic.active_section', 'manage_languages');

    Registry::set('navigation.tabs', array (
        'languages' => array (
            'title' => __('installed_languages'),
            'js' => true
        ),
    ));

    if (!Registry::get('runtime.company_id')) {
        Registry::set('navigation.tabs.available_languages', array (
            'title' => __('available_languages'),
            'ajax' => true,
            'href' => 'languages.install_list',
        ));
    }

    $view = Tygh::$app['view'];

    $languages = Languages::getAll(true);
    $view->assign([
        'langs'                     => $languages,
        'countries'                 => fn_get_simple_countries(false, DESCR_SL),
        'is_allow_update_languages' => fn_check_permissions('languages', 'update', 'admin', Http::POST),
    ]);

} elseif ($mode == 'install_list') {
    $view = Tygh::$app['view'];
    $langs_meta = Languages::getLangPacksMeta();

    $languages = Languages::getAll(true);

    $view->assign('langs_meta', $langs_meta);
    $view->assign('countries', fn_get_simple_countries(false, DESCR_SL));

    $view->assign('langs', $languages);

    $view->display('views/languages/components/install_languages.tpl');
    exit(0);

} elseif ($mode == 'translations') {
    $sections = array(
        'translations' => array(
            'title' => __('translations'),
            'href' => fn_url('languages.translations'),
        ),
        'manage_languages' => array(
            'title' => __('manage_languages'),
            'href' => fn_url('languages.manage'),
        ),
    );
    Registry::set('navigation.dynamic.sections', $sections);
    Registry::set('navigation.dynamic.active_section', 'translations');

    list($lang_data, $search) = LanguageValues::getVariables($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'));

    Tygh::$app['view']->assign('lang_data', $lang_data);
    Tygh::$app['view']->assign('search', $search);

} elseif ($mode == 'update') {
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $lang_data = Languages::get(array('lang_id' => $_REQUEST['lang_id']), 'lang_id');

    if (empty($lang_data[$_REQUEST['lang_id']])) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    } else {
        $lang_data = $lang_data[$_REQUEST['lang_id']];

        if (fn_allowed_for('ULTIMATE')) {
            /** @var \Tygh\Storefront\Repository $repository */
            $repository = Tygh::$app['storefront.repository'];
            list($is_sharing_enabled, $is_shared) = $repository->getSharingDetails(['language_ids' => $lang_data['lang_id']]);

            $view->assign([
                'is_sharing_enabled' => $is_sharing_enabled,
                'is_shared'          => $is_shared,
            ]);
        }
    }

    $view->assign([
        'lang_data' => $lang_data,
        'countries' => fn_get_simple_countries(false, DESCR_SL)
    ]);

} elseif ($mode == 'update_translation') {
    $lang_data = Languages::get(array('lang_id' => $_REQUEST['lang_id']), 'lang_id');
    if (empty($lang_data[$_REQUEST['lang_id']])) {
        return array(CONTROLLER_STATUS_NO_PAGE);

    } else {
        $lang_data = $lang_data[$_REQUEST['lang_id']];
    }

    Tygh::$app['view']->assign('lang_data', $lang_data);
} elseif ($mode === 'selector') {
    list($objects, $total_objects) = fn_get_languages_for_picker($_REQUEST);

    /** @var \Tygh\Ajax $ajax */
    $ajax = Tygh::$app['ajax'];
    $ajax->assign('objects', $objects);
    $ajax->assign('total_objects', $total_objects);
    return [CONTROLLER_STATUS_NO_CONTENT];
}
