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

use Tygh\Addons\TildaPages\ServiceProvider;
use Tygh\Common\OperationResult;
use Tygh\Enum\ContainerPositions;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;
use Tygh\Http;
use Tygh\Less;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Gets a list of Tilda pages
 *
 * @return array|bool Tilda page list
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_tilda_pages_get_page_list_from_tilda()
{
    $tilda_client = ServiceProvider::getTildaClient();

    $page_list = $tilda_client->getPagesList();

    if (!$page_list) {
        return false;
    }

    return $page_list;
}

/**
 * Gets a page data from Tilda
 *
 * @param int   $page_id         Tilda page id
 * @param array $additional_data Additional page data
 *
 * @return bool
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_get_page_from_tilda_by_id($page_id, array $additional_data = [])
{
    $tilda_client = ServiceProvider::getTildaClient();

    $page = $tilda_client->getFullExportedPageById($page_id);

    if (!$page) {
        return false;
    }

    $page = fn_tilda_pages_convert_data($page, $additional_data);

    if (isset($page['inner_page_id'])) {
        fn_tilda_pages_update_tilda_page($page, true, 'tilda_pages');
    } elseif (isset($page['inner_location_id'])) {
        fn_tilda_pages_update_tilda_page($page, true, 'tilda_locations');
    }

    return true;
}

/**
 * Transforms page data
 *
 * @param array $page            Tilda page data
 * @param array $additional_data Additional page data
 *
 * @return array Tilda page data
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_convert_data(array $page, array $additional_data = [])
{
    $file_types = ['images', 'js', 'css'];

    if (
        !empty($page['html'])
        && strpos($page['html'], '<!DOCTYPE html>') !== false
    ) {
        try {
            $dom = new DomDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($page['html'], LIBXML_HTML_NOIMPLIED | LIBXML_COMPACT | LIBXML_HTML_NODEFDTD);

            $link_tags = $dom->getElementsByTagName('link');

            /** @var DOMElement $link */
            foreach ($link_tags as $link) {
                $rel = $link->getAttribute('rel');
                $href = $link->getAttribute('href');

                // phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit
                if (
                    $rel === 'stylesheet'
                    && $href
                    && filter_var($href, FILTER_VALIDATE_URL)
                ) {
                    $css_exist = false;
                    foreach ($page['css'] as $css) {
                        if ($css['from'] === $href || $css['to'] === $href) {
                            $css_exist = true;
                            break;
                        }
                    }

                    if (!$css_exist) {
                        $page['css'][] = [
                            'from' => $href,
                            'to'   => $href,
                        ];
                    }
                }
            }
        } catch (Exception $e) {
        }

        //get allrecords(body) content
        $html = strstr($page['html'], '<!--allrecords-->');
        $html = strstr($html ? $html : $page['html'], '<!--/allrecords-->', true);
        $page['html'] = $html ? $html : $page['html'];
    }

    $result =  [
        'page_id'     => empty($page['id']) ? 0 : $page['id'],
        'project_id'  => empty($page['projectid']) ? 0 : $page['projectid'],
        'page_title'  => empty($page['title']) ? '' : $page['title'],
        'published'   => empty($page['published']) ? TIME : $page['published'],
        'css'         => [],
        'js'          => [],
        'images'      => [],
        'description' => empty($page['html']) ? '' : '<div class="tilda-pages-container">' . $page['html'] . '</div>',
        'status'      => ObjectStatuses::ACTIVE
    ];

    $result = array_merge($result, $additional_data);

    foreach ($file_types as $file_type) {
        if (empty($page[$file_type])) {
            continue;
        }

        foreach ($page[$file_type] as $file) {
            // phpcs:ignore
            if (!empty($file['to']) && !empty($file['from'])) {
                $result[$file_type][$file['from']] = $file['to'];
            }
        }
    }

    return $result;
}

/**
 * Updates page data or creates a new
 *
 * @param array  $page_data           Tilda page data
 * @param bool   $save_external_files Determines whether to save files
 * @param string $tilda_db_table      Specifies in which table to save the Tilda page data
 *
 * @return bool
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_update_tilda_page(array $page_data, $save_external_files = false, $tilda_db_table = 'tilda_pages')
{
    if (empty($page_data['page_id'])) {
        return false;
    }

    $old_page_data = fn_tilda_pages_get_tilda_page_data($page_data['page_id'], [], $tilda_db_table);

    if (
        empty($old_page_data)
        && empty($page_data['inner_page_id'])
        && empty($page_data['inner_location_id'])
    ) {
        return false;
    }

    if (!empty($page_data['inner_page_id'])) {
        $has_old_inner_page_data = db_get_field('SELECT COUNT(1) FROM ?:tilda_pages WHERE inner_page_id = ?i', $page_data['inner_page_id']) > 0;
        $inner_page_id_field = 'inner_page_id';
    } else {
        $has_old_inner_page_data = db_get_field('SELECT COUNT(1) FROM ?:tilda_locations WHERE inner_location_id = ?i', $page_data['inner_location_id']) > 0;
        $inner_page_id_field = 'inner_location_id';
    }

    if (isset($page_data['published'])) {
        $page_data['published'] = empty($page_data['published']) ? TIME : (int) $page_data['published'];
    }
    if (isset($page_data['project_id'])) {
        $page_data['project_id'] = empty($page_data['project_id']) ? 0 : (int) $page_data['project_id'];
    }

    if ($save_external_files) {
        $page_data = fn_tilda_pages_save_external_files($old_page_data, $page_data);
    }

    if (isset($page_data['description'])) {
        $page_data['description'] = empty($page_data['description']) ? '' : html_entity_decode($page_data['description']);
    }

    $page_data = array_merge($old_page_data, $page_data);

    if (isset($page_data['images'])) {
        $page_data['images'] = serialize($page_data['images']);
    }

    if (isset($page_data['errors'])) {
        $page_data['errors'] = serialize($page_data['errors']);
    }

    if (isset($page_data['js'])) {
        $page_data['js'] = serialize($page_data['js']);
    }

    if (isset($page_data['css'])) {
        $page_data['css'] = serialize($page_data['css']);
    }

    if (empty($old_page_data) && !$has_old_inner_page_data) {
        db_query('INSERT INTO ?:?p ?e', $tilda_db_table, $page_data);
    } elseif (!empty($old_page_data) && !$has_old_inner_page_data) {
        db_query('UPDATE ?:?p SET ?u WHERE page_id = ?i', $tilda_db_table, $page_data, $page_data['page_id']);
    } elseif ($has_old_inner_page_data) {
        db_query('UPDATE ?:?p SET ?u WHERE ?p = ?i', $tilda_db_table, $page_data, $inner_page_id_field, $page_data[$inner_page_id_field]);
    }

    return true;
}

/**
 * Saves files for the Tilda page
 *
 * @param array $old_data Old Tilda page data
 * @param array $new_data Updated Tilda page data
 *
 * @return array Page data
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_save_external_files(array $old_data, array $new_data)
{
    $page_id = $new_data['page_id'];
    $description = empty($new_data['description']) ? '' : $new_data['description'];
    $images = empty($new_data['images']) ? [] : (array) $new_data['images'];
    $old_images = empty($old_data['images']) ? [] : (array) $old_data['images'];

    $new_data['errors'] = [];

    $process_result = static function (OperationResult $result) use (&$new_data) {
        foreach ($result->getWarnings() as $code => $message) {
            $new_data['errors'][$code] = $message;
        }
        foreach ($result->getErrors() as $code => $message) {
            $new_data['errors'][$code] = $message;
        }

        return $result->isSuccess();
    };

    if (!empty($new_data['js'])) {
        $result = fn_tilda_pages_merge_js($page_id, $new_data['js'], $description);

        if ($process_result($result)) {
            $description = $result->getData();
            // phpcs:ignore
        } else {
            return $new_data;
        }
    }

    if (!empty($new_data['css'])) {
        $result = fn_tilda_pages_merge_css($page_id, $new_data['css']);

        if (!$process_result($result)) {
            return $new_data;
        }
    }

    $result = fn_tilda_pages_save_images($page_id, $images, $old_images, $description);

    if ($process_result($result)) {
        $description = $result->getData();
        // phpcs:ignore
    } else {
        return $new_data;
    }

    $new_data['description'] = $description;
    $new_data['status'] = ObjectStatuses::ACTIVE;

    return $new_data;
}

/**
 * Saves css files for the Tilda page
 *
 * @param int   $page_id  Tilda page id
 * @param array $css_list Css files list
 *
 * @return OperationResult
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_merge_css($page_id, array $css_list)
{
    $result = new OperationResult(false);

    $upload_setting = fn_tilda_pages_get_upload_settings($page_id, 'css');

    $upload_path = $upload_setting['upload_path'];
    $contents = [];

    fn_mkdir($upload_path);

    foreach ($css_list as $external_file => $file_name) {
        $content = fn_tilda_pages_file_get_contents($external_file);

        if (strpos((string) $content, '<!DOCTYPE html>') !== false) {
            continue;
        }

        if (!$content) {
            $result->addError($file_name, sprintf('Can not download file %s', $external_file));
            return $result;
        }

        $contents[] = $content;
    }

    try {
        $css_content = implode("\n\n", $contents);
        $revert_css = file_get_contents(Registry::get('config.dir.design_backend') . 'css/addons/tilda_pages/revert.css');
        $css_content = $revert_css . ' .tilda-pages-container {' . $css_content . '}';

        $less = new Less();
        $css_content = $less->compile($css_content);

        $file_path = sprintf('%s/%s', $upload_path, TILDA_PAGE_COMMON_STYLE_FILE_NAME);

        if (file_put_contents($file_path, $css_content)) {
            $result->setSuccess(true);
        }
    } catch (Exception $e) {
        $result->addError('less', sprintf('Can compile less: %s', $e->getMessage()));
    }

    return $result;
}

/**
 * Saves js files for the Tilda page
 *
 * @param int    $page_id     Tilda page id
 * @param array  $js_list     Js files list
 * @param string $description Page description
 *
 * @return OperationResult
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_merge_js($page_id, array $js_list, $description)
{
    $result = new OperationResult(false);

    $upload_setting = fn_tilda_pages_get_upload_settings($page_id, 'js');
    $upload_path = $upload_setting['upload_path'];

    fn_mkdir($upload_path);
    $js_contents = [];

    foreach ($js_list as $external_file => $file_name) {
        if (strpos($file_name, 'jquery-') !== false) {
            continue;
        }

        $content = fn_tilda_pages_file_get_contents($external_file);

        if (!$content) {
            $result->addError($file_name, sprintf('Can not download file %s', $external_file));
            return $result;
        }

        $js_contents[] = $content;
    }

    $pattern = '/\<script([^>]*)\>(.*?)\<\/script\>/s';

    if (preg_match_all($pattern, $description, $matches)) {
        foreach ($matches[0] as $index => $match) {
            $description = str_replace($match, '<!-- Inline script moved to common file -->', $description);
            $script = $matches[2][$index];
            $script = preg_replace('/\$\((?:\'|")([^\'"]+?)(?:\'|")\)/', '$("\1", $("#' . TILDA_PAGE_CONTAINER_ID . '"))', $script);

            $js_contents[] = $script;
        }
    }

    $js_content = implode("\n", $js_contents);

    $file_path = sprintf('%s/%s', $upload_path, TILDA_PAGE_COMMON_SCRIPT_FILE_NAME);

    if (file_put_contents($file_path, $js_content)) {
        $result->setSuccess(true);
        $result->setData($description);
    } else {
        $result->addError('script.js', sprintf('Can not save file %s', $file_path));
    }

    return $result;
}

/**
 * Saves images for the Tilda page
 *
 * @param int    $page_id     Tilda page id
 * @param array  $images      Images list
 * @param array  $old_images  Old images list
 * @param string $description Page description
 *
 * @return OperationResult
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_save_images($page_id, array $images, array $old_images, $description)
{
    $result = new OperationResult(true);

    $logging = Http::$logging;
    Http::$logging = false;

    $upload_setting = fn_tilda_pages_get_upload_settings($page_id, 'images');
    $upload_path = $upload_setting['upload_path'];
    $http_path = $upload_setting['http_path'];

    fn_mkdir($upload_path);

    foreach ($images as $external_file => $file_name) {
        $file_extension = strrpos($file_name, '.');
        $new_file_name = !$file_extension ? $file_name . '.png' : $file_name;

        $file_path = sprintf('%s/%s', $upload_path, $new_file_name);

        if (file_exists($file_path)) {
            fn_rm($file_path);
            $description = str_replace(sprintf('%s/%s', $http_path, $new_file_name), $file_name, $description);
        }

        Http::get($external_file, [], [
            'write_to_file' => $file_path
        ]);

        if (file_exists($file_path)) {
            $description = str_replace($file_name, sprintf('%s/%s', $http_path, $new_file_name), $description);
        } else {
            $result->addWarning($file_name, sprintf('Can not download file %s', $external_file));
        }
    }

    $deleted_files = array_diff($old_images, $images);

    foreach ($deleted_files as $deleted_file) {
        $file_path = sprintf('%s/%s', $upload_path, $deleted_file);
        fn_rm($file_path);
    }

    Http::$logging = $logging;

    $result->setData($description);

    return $result;
}

/**
 * Get file content
 *
 * @param string $file File data
 *
 * @return bool|string File content
 */
function fn_tilda_pages_file_get_contents($file)
{
    $attempts = 0;
    $max_attempts = 3;

    while ($attempts < $max_attempts) {
        $logging = Http::$logging;
        Http::$logging = false;

        $content = Http::get($file);

        Http::$logging = $logging;

        if ($content !== false) {
            return $content;
        }

        $attempts++;
    }

    return false;
}

/**
 * Get Tilda page data
 *
 * @param int    $page_id        Page id
 * @param array  $fields         Fields list
 * @param string $tilda_db_table Specifies in which table to save the Tilda page data
 *
 * @return array Page data
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_get_tilda_page_data($page_id, array $fields = [], $tilda_db_table = 'tilda_pages')
{
    $fields = !empty($fields) ? implode(',', $fields) : '*';

    $data = db_get_row('SELECT ?p FROM ?:?p as tilda WHERE tilda.page_id = ?i', $fields, $tilda_db_table, $page_id);

    if (isset($data['images'])) {
        $data['images'] = unserialize($data['images']);
    }

    if (isset($data['errors'])) {
        $data['errors'] = unserialize($data['errors']);
    }

    if (isset($data['js'])) {
        $data['js'] = unserialize($data['js']);
    }

    if (isset($data['css'])) {
        $data['css'] = unserialize($data['css']);
    }

    return $data;
}

/**
 * The "pre_get_page_data" hook handler.
 *
 * Actions performed:
 *  - Joins tilda page data from separate table
 *
 * @param string $field_list Field list
 * @param string $join       Join table data
 *
 * @see fn_get_page_data
 *
 * @return void
 */
function fn_tilda_pages_pre_get_page_data(&$field_list, &$join)
{
    $field_list .= ', ?:tilda_pages.description AS tilda_description'
        . ', ?:tilda_pages.page_id AS tilda_page_id'
        . ', ?:tilda_pages.is_only_content AS is_only_content'
        . ', ?:tilda_pages.project_id AS tilda_project_id'
        . ', ?:tilda_pages.published AS tilda_published';
    $join .= db_quote(' LEFT JOIN ?:tilda_pages ON ?:pages.page_id = ?:tilda_pages.inner_page_id');
}

/**
 * The "get_page_data" hook handler.
 *
 * Actions performed:
 *  - Changes description to tilda description for tilda pages
 *
 * @param array $page_data Page data
 *
 * @see fn_get_page_data
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_get_page_data(array &$page_data)
{
    if ($page_data['page_type'] !== PAGE_TYPE_TILDA_PAGE) {
        return;
    }

    $page_data['description'] = $page_data['tilda_description'];
}

/**
 * The "delete_page" hook handler.
 *
 * Actions performed:
 *  - Delete data for Tilda page
 *
 * @param int $page_id Page id
 *
 * @return void
 *
 * @see fn_delete_page
 */
function fn_tilda_pages_delete_page($page_id)
{
    $tilda_page_id = (int) db_get_field('SELECT page_id FROM ?:tilda_pages WHERE inner_page_id = ?i', $page_id);
    if (!$tilda_page_id) {
        return;
    }

    db_query('DELETE FROM ?:tilda_pages WHERE inner_page_id = ?i', $page_id);

    $tilda_page_users_count = (int) db_get_field('SELECT COUNT(1) FROM ?:tilda_pages WHERE page_id = ?i', $tilda_page_id);
    $tilda_location_users_count = (int) db_get_field('SELECT COUNT(1) FROM ?:tilda_locations WHERE page_id = ?i', $tilda_page_id);
    if (
        $tilda_page_users_count !== 0
        || $tilda_location_users_count !== 0
    ) {
        return;
    }

    fn_rm(fn_tilda_pages_get_upload_page_dir($tilda_page_id));
}

/**
 * The "get_pages" hook handler.
 *
 * Actions performed:
 *  - Joins tilda page data from separate table
 *
 * @param array  $params    Array with query params
 * @param string $join      Join table data
 * @param string $condition Condition data
 * @param string $fields    Field list
 *
 * @see fn_get_pages
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_get_pages(array $params, &$join, &$condition, &$fields)
{
    $fields[] = '?:tilda_pages.status as tilda_status';
    $fields[] = '?:tilda_pages.page_id as tilda_page_id';

    $join .= db_quote(' LEFT JOIN ?:tilda_pages ON ?:pages.page_id = ?:tilda_pages.inner_page_id');

    // phpcs:ignore
    if (!empty($params['tilda_status'])) {
        $params['tilda_status'] = is_array($params['tilda_status']) ? $params['tilda_status'] : [$params['tilda_status']];

        $condition .= db_quote(' AND ?:tilda_pages.status IN (?a)', $params['tilda_status']);
    }
}

/**
 * Get upload file settings
 *
 * @param int    $page_id Page id
 * @param string $type    File type
 *
 * @return array Upload settings
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_tilda_pages_get_upload_settings($page_id, $type = null)
{
    $default_lang_code = Registry::get('settings.Appearance.frontend_default_language');
    $file_dir = fn_tilda_pages_get_upload_page_dir($page_id);
    $domain = str_replace('/index.php', '/', fn_url('', 'C', 'http', $default_lang_code));
    $http_path = str_replace('http://', '//', sprintf('%s%s', $domain, fn_get_rel_dir($file_dir)));

    $settings = [
        'images' => [
            'upload_path' => sprintf('%s/images', $file_dir),
            'http_path' => sprintf('%s/images', $http_path),
        ],
        'js'     => [
            'upload_path' => sprintf('%s/js', $file_dir),
            'http_path' => sprintf('%s/js', $http_path),
        ],
        'css'     => [
            'upload_path' => sprintf('%s/css', $file_dir),
            'http_path' => sprintf('%s/css', $http_path),
        ],
    ];

    return $type === null ? $settings : $settings[$type];
}

/**
 * Get path for file directory
 *
 * @param int $page_id Page id
 *
 * @return string Directory path
 */
function fn_tilda_pages_get_upload_page_dir($page_id)
{
    return sprintf('%s/var/files/tilda/page_%d', DIR_ROOT, $page_id);
}

/**
 * The "page_object_by_type" hook handler.
 *
 * Actions performed:
 * - Allows administrators to create Tilda pages.
 *
 * @param array $types Page types list
 *
 * @see fn_get_page_object_by_type
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_page_object_by_type(array &$types)
{
    if (
        !SiteArea::isStorefront(AREA)
        && (
            empty(Tygh::$app['session']['auth']['user_type'])
            || UserTypes::isVendor(Tygh::$app['session']['auth']['user_type'])
        )
    ) {
        return;
    }

    $types[PAGE_TYPE_TILDA_PAGE] = [
        'single'    => 'tilda_pages.tilda_page',
        'name'      => 'tilda_pages.tilda_pages',
        'add_name'  => 'tilda_pages.add_tilda_page',
        'edit_name' => 'tilda_pages.editing_tilda_page',
        'new_name'  => 'tilda_pages.new_tilda_page',
    ];
}

/**
 * The "update_page_post" hook handler.
 *
 * Actions performed:
 *  - Save Tilda page data after creation
 *
 * @param array $page_data Page data
 * @param int   $page_id   Page identifier, if equals zero new page will be created
 *
 * @see fn_update_page
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_update_page_post(array $page_data, $page_id)
{
    if (
        empty($page_data['page_type'])
        || $page_data['page_type'] !== PAGE_TYPE_TILDA_PAGE
    ) {
        return;
    }

    $additional_data = [
        'inner_page_id'     => $page_id,
        'is_only_content'   => isset($page_data['is_only_content']) ? YesNo::YES : YesNo::NO
    ];
    fn_tilda_pages_get_page_from_tilda_by_id($page_data['tilda_page_id'], $additional_data);
}

/**
 * The "update_location_post" hook handler.
 *
 * Actions performed:
 *  - Save Tilda page data after creation
 *
 * @param array  $location_data Array of location data
 * @param string $lang_code     Language code
 * @param int    $location_id   Location identifier
 *
 * @see \Tygh\BlockManager\Location::update
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_update_location_post(array $location_data, $lang_code, $location_id)
{
    // phpcs:ignore
    if (isset($location_data['is_tilda_page'])) {
        $additional_data = [
            'inner_location_id' => $location_id,
            'is_only_content'   => isset($location_data['is_only_content']) ? YesNo::YES : YesNo::NO
        ];
        fn_tilda_pages_get_page_from_tilda_by_id($location_data['tilda_page_id'], $additional_data);
    } else {
        fn_tilda_pages_remove_location($location_id);
    }
}

/**
 * The "remove_location" hook handler.
 *
 * Actions performed:
 *  - Delete Tilda page data after deleting a location
 *
 * @param int $location_id Location identifier
 *
 * @see \Tygh\BlockManager\Location::remove
 *
 * @return void
 */
function fn_tilda_pages_remove_location($location_id)
{
    $tilda_page_id = (int) db_get_field('SELECT page_id FROM ?:tilda_locations WHERE inner_location_id = ?i', $location_id);
    if (!$tilda_page_id) {
        return;
    }

    db_query('DELETE FROM ?:tilda_locations WHERE inner_location_id = ?i', $location_id);

    $tilda_page_users_count = (int) db_get_field('SELECT COUNT(1) FROM ?:tilda_pages WHERE page_id = ?i', $tilda_page_id);
    $tilda_location_users_count = (int) db_get_field('SELECT COUNT(1) FROM ?:tilda_locations WHERE page_id = ?i', $tilda_page_id);
    if (
        $tilda_page_users_count !== 0
        || $tilda_location_users_count !== 0
    ) {
        return;
    }

    fn_rm(fn_tilda_pages_get_upload_page_dir($tilda_page_id));
}

/**
 * The "block_manager_location_get_list" hook handler.
 *
 * Actions performed:
 *  - Joins tilda page data from separate table
 *
 * @param array  $params    Input params
 * @param string $lang_code Two letter language code
 * @param string $join      Join tables data
 *
 * @see \Tygh\BlockManager\Location::getList()
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_block_manager_location_get_list(array $params, $lang_code, &$join)
{
    $join .= db_quote(' LEFT JOIN ?:tilda_locations ON l.location_id = ?:tilda_locations.inner_location_id ');
}

/**
 * The "get_location_post" hook handler.
 *
 * Actions performed:
 *  - Extends data for a page
 *
 * @param array $location Location data
 *
 * @see \Tygh\BlockManager\Location::get()
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_get_location_post(array &$location)
{
    // phpcs:ignore
    if (isset($location['page_id'])) {
        $location['tilda_page_upload_settings'] = fn_tilda_pages_get_upload_settings($location['page_id']);
    }
}

/**
 * The "block_manager_block_find_post" hook handler.
 *
 * Actions performed:
 *  - Extends data for a page
 *
 * @param array  $params         Block search params
 * @param int    $items_per_page Limit element on page
 * @param string $lang_code      Two-letter language code
 * @param array  $blocks         Block list
 *
 * @see \Tygh\BlockManager\Block::find()
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_tilda_pages_block_manager_block_find_post(array $params, $items_per_page, $lang_code, array &$blocks)
{
    if (empty($blocks)) {
        return;
    }

    $tilda_locations = db_get_array('SELECT * FROM ?:tilda_locations');

    $locations_content_info = db_get_array(
        'SELECT * FROM ?:bm_containers AS containers'
        . ' LEFT JOIN ?:bm_grids AS grids ON grids.container_id = containers.container_id'
        . ' LEFT JOIN ?:bm_snapping AS snapping ON snapping.grid_id = grids.grid_id'
        . ' WHERE containers.position = ?s',
        ContainerPositions::CONTENT
    );

    if (empty($tilda_locations)) {
        return;
    }

    foreach ($blocks as &$block) {
        foreach ($tilda_locations as $location) {
            $location_id = $location['inner_location_id'];

            if (!isset($block['locations'][$location_id])) {
                continue;
            }

            if (!YesNo::toBool($location['is_only_content'])) {
                unset($block['locations'][$location_id]);
                continue;
            }

            $blocks_in_content = array_filter(
                $locations_content_info,
                static function ($location_content) use ($block, $location_id) {
                    return $location_content['block_id'] === $block['block_id'] && $location_content['location_id'] === $location_id;
                }
            );

            // phpcs:ignore
            if (!empty($blocks_in_content)) {
                unset($block['locations'][$location_id]);
            }
        }
    }
}

/**
 * The "clone_page" hook handler.
 *
 * Actions performed:
 * - Duplicates Tilda page data for cloned page.
 *
 * @param int $page_id     Source page ID
 * @param int $new_page_id Created page ID
 *
 * @return void
 */
function fn_tilda_pages_clone_page($page_id, $new_page_id)
{
    $page_data = db_get_row('SELECT * FROM ?:tilda_pages WHERE inner_page_id = ?i', $page_id);

    if (empty($page_data)) {
        return;
    }

    $page_data['inner_page_id'] = $new_page_id;
    db_replace_into('tilda_pages', $page_data);
}

/**
 * The "location_copy" hook handler.
 *
 * Actions performed:
 * - Copy Tilda page data for copied location.
 *
 * @param int $location_id     Location identifier
 * @param int $new_location_id New location identifier
 *
 * @return void
 */
function fn_tilda_pages_location_copy($location_id, $new_location_id)
{
    $page_data = db_get_row('SELECT * FROM ?:tilda_locations WHERE inner_location_id = ?i', $location_id);

    if (empty($page_data)) {
        return;
    }

    $page_data['inner_location_id'] = $new_location_id;
    db_replace_into('tilda_locations', $page_data);
}
