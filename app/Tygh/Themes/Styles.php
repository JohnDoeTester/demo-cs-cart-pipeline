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

namespace Tygh\Themes;

use Tygh\Http;
use Tygh\Less;
use Tygh\Registry;

class Styles
{
    private static $instances = array();

    public $params = array(); // addon can pass params here to use them in hooks later
    public $theme_name = '';
    public $theme;

    private $manifest = array();
    private $manifest_path_hash = '';

    /** @var string */
    private $styles_path = '';

    /** @var string */
    private $fonts_path = '';

    /** @var string */
    private $styles_dir = '';

    /** @var string */
    private $fonts_dir = 'media/fonts/';
    private $schema = array();
    private $gfonts_tag = 'GFONTS';
    private $google_fonts = array();

    private $company_id = 0;

    public function __construct($theme_name, $company_id = null)
    {
        $this->theme_name = $theme_name;
        $this->theme = Themes::factory($theme_name);

        // FIXME: Backward compatibility for themes usings presets
        if ($this->theme->getContentPath('presets', Themes::CONTENT_DIR)) {
            $this->styles_dir = 'presets/';
        } else {
            $this->styles_dir = 'styles/';
        }
        $this->styles_path = fn_get_theme_path('[themes]/' . $theme_name . '/' . $this->styles_dir, 'C');
        $this->fonts_path = fn_get_theme_path('[themes]/' . $theme_name . '/' . $this->fonts_dir, 'C');

        if (is_file($this->styles_path . 'schema.json')) {
            $schema = fn_get_contents($this->styles_path . 'schema.json');
            $this->schema = json_decode($schema, true);
        } else {
            $this->schema = array();
        }

        $this->company_id = is_null($company_id) ? Registry::get('runtime.company_id') : $company_id;
    }

    /**
     * Gets list of styles
     *
     * @param array $params Extra parameters
     *
     * @return array List of available styles
     */
    public function getList($params = array())
    {
        $styles = array();

        $style_files = array_keys($this->theme->getDirContents(array(
            'dir' => $this->getStylesDir(),
            'get_dirs' => false,
            'get_files' => true,
            'extension' => '.less',
        ), Themes::STR_EXCLUSIVE));

        /**
         * Modifies styles list
         *
         * @param object  $this Styles object
         * @param array   $style_files style files list
         * @param array   $params search params
         */
        fn_set_hook('styles_get_list', $this, $style_files, $params);

        if (!empty($style_files)) {
            foreach ($style_files as $id => $style_id) {
                $style_id = fn_basename($style_id, '.less');
                $styles[$style_id] = $this->get($style_id, $params);
            }
        }

        return $styles;
    }

    /**
     * Gets full style information
     *
     * @param string $style_id File name of the style schema (like: "satori")
     * @param array  $params   Extra parameters
     *      array(
     *          'parse' parse less to variables if true
     *      )
     * @return array Style information
     */
    public function get($style_id, $params = array())
    {
        $manifest = $this->getManifest();

        $style_id = fn_basename($style_id);
        $style = array();
        $data = array();
        $parsed = array();
        $custom_fonts = array();
        $less_content = fn_get_contents($this->getStyleFile($style_id));

        /**
         * Modifies style data
         *
         * @param object  $this Styles object
         * @param string  $less_content style LESS content
         * @param string  $style_id style ID
         */
        fn_set_hook('styles_get', $this, $less_content, $style_id);

        if (!empty($less_content)) {
            if (!empty($params['parse'])) {
                $less = new Less();
                $data = $less->extractVars($less_content);
                $parsed = $this->cssToUrl($data);
                $custom_fonts = $this->getCustomFonts($less_content);
            }

            $style = array(
                'style_id' => $style_id,
                // FIXME: Backward presets compatibility
                'preset_id' => $style_id,
                'data' => $data,
                'name' => $style_id,
                'is_default' => isset($manifest['default'][$style_id]),
                'parsed' => $parsed,
                'custom_fonts' => $custom_fonts,
                'image' => $this->getStyleImage($style_id),
            );

            if (empty($params['short_info'])) {
                $custom_css = $this->getCustomCss($style_id);

                $style['less'] = $less_content;
                $style['custom_css'] = $custom_css;

                $style['is_removable'] = $this->isRemovable($style);
            }
        }

        /**
         * Modifies style data (post-processing)
         *
         * @param object  $this Styles object
         * @param string  $style_id style ID
         * @param array   $params style retrieval params
         * @param array   $style style data
         */
        fn_set_hook('styles_get_post', $this, $style_id, $params, $style);

        return $style;
    }

    /**
     * Saves less data to style file
     *
     * @param string $style_id File name of the style schema (like: "satori")
     * @param array  $style    Style data
     *
     * @return boolean false on failure, true on success
     */
    public function update($style_id, $style)
    {
        $style_id = fn_basename($style_id);
        $style_path = $this->getStyleFile($style_id, 'less');

        $current_style = $this->get($style_id);
        $less = empty($current_style['less']) ? '' : $current_style['less'];

        $style['data'] = $this->processCopy($style_id, $style['data']);

        foreach ($style['data'] as $var_name => $value) {

            $less_var = Less::arrayToLessVars(array($var_name => $value));

            if (preg_match('/@' . $var_name . ':.*?;/m', $less)) {
                $less = preg_replace('/(*ANYCRLF)@' . $var_name . ':.*?;$/m', str_replace("\n", '', $less_var), $less);
            } else {
                $less .= $less_var;
            }
        }

        $less = $this->addGoogleFonts($style['data'], $less);

        $this->addCustomCss($style_id, $style['custom_css']);

        /**
         * Executes before saving style LESS content into a file, allows to modify style data and saving path.
         *
         * @param \Tygh\Themes\Styles $this       Styles instance
         * @param string              $style_id   File name of the style schema (like: "satori")
         * @param array               $style      Style data
         * @param string              $style_path Path to save style to
         * @param string              $less       LESS content of the style
         */
        fn_set_hook('styles_update', $this, $style_id, $style, $style_path, $less);

        return fn_put_contents($style_path, $less);
    }

    /**
     * Deletes style
     * @param  string  $style_id style ID
     * @return boolean true on succes, false otherwise
     */
    public function delete($style_id)
    {
        $style_id = fn_basename($style_id);

        $less = $this->getStyleFile($style_id);
        $css = $this->getStyleFile($style_id, 'css');
        $image = $this->getStyleFile($style_id, 'png');
        $patterns = Patterns::instance($this->params)->getPath($style_id);

        if (fn_rm($less)) {
            fn_rm($css); // remove custom css
            fn_rm($image); // remove style image
            fn_rm($patterns);

            $delete_logos = true;

            /**
             * Executes before deleting logos when deleting a style, allows to cancel logos removal.
             *
             * @param \Tygh\Themes\Styles $this         Styles instance
             * @param string              $style_id     Style name
             * @param bool                $delete_logos Indicates if logos for the style have to be deleted
             */
            fn_set_hook('styles_delete_before_logos', $this, $style_id, $delete_logos);

            if ($delete_logos) {
                fn_delete_logo('theme', null, $style_id);
                fn_delete_logo('mail', null, $style_id);
                fn_delete_logo('favicon', null, $style_id);

                fn_rm(fn_get_theme_path('[themes]/[theme]/media/images/logos/' . $style_id, 'C'));
            }

            fn_set_hook('delete_style_post', $style_id);

            return true;
        }

        return false;
    }

    /**
     * Gets default style name
     *
     * @return string Style name (like: satori)
     */
    public function getDefault()
    {
        $manifest = $this->getManifest();

        return !empty($manifest['default_style']) ? $manifest['default_style'] : '';
    }

    /**
     * Gets manifest information
     *
     * @return array Manifest data
     */
    public function getManifest()
    {
        $path = $this->styles_path;

        /**
         * Modifies the path to style manifest
         *
         * @param object  $this Styles object
         * @param string  $path current path
         */
        fn_set_hook('styles_get_manifest', $this, $path);

        if (empty($this->manifest) || $this->manifest_path_hash != md5($path)) {
            $manifest_path = $path . 'manifest.json';
            $this->manifest_path_hash = md5($path);

            if (is_file($manifest_path)) {
                $this->manifest = json_decode(fn_get_contents($manifest_path), true);
            }
        }

        return $this->manifest;
    }

    /**
     * Processes data copy according to schema
     *
     * @param  string $style_id style ID
     * @param  array  $data     style
     *
     * @return array return style
     */
    public function processCopy($style_id, $data)
    {
        if (!empty($data['copy'])) {
            foreach ($this->schema['backgrounds']['fields'] as $field_id => $field_data) {
                if (empty($field_data['copies'])) {
                    continue;
                }

                foreach ($field_data['copies'] as $property => $fields) {
                    foreach ($fields as $field) {
                        if (!empty($data['copy'][$property][$field_id])) {
                            if (!empty($field['inverse'])) {
                                $data[$field['match']] = $field['default'];
                            } elseif (isset($data[$field['source']])) {
                                $data[$field['match']] = $data[$field['source']];
                            }
                        } else {
                            if (empty($field['inverse'])) {
                                $data[$field['match']] = $field['default'];
                            }
                        }
                    }
                }
            }
        }

        unset($data['copy']);

        $data = $this->urlToCss($style_id, $data);

        return $data;
    }

    /**
     * Gets style schema
     * @return array style schema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Changes style ID of specified layout.
     *
     * @param int    $layout_id Layout ID
     * @param string $style_id  New style ID (e.g. "satori", "ocean", etc.)
     *
     * @return bool Whether style was changed successfully
     */
    public function setStyle($layout_id, $style_id)
    {
        $update_for_layout = true;
        $result = false;

        /**
         * Executes before updating layout style in the DB, allows to prevent this action.
         *
         * @param \Tygh\Themes\Styles $this              Styles instance
         * @param int                 $layout_id         Layout ID
         * @param string              $style_id          Style name
         * @param bool                $update_for_layout Whether update layout information
         * @param bool                $result            Return value
         */
        fn_set_hook('styles_set_style_pre', $this, $layout_id, $style_id, $update_for_layout, $result);

        if ($update_for_layout) {
            $result = db_query(
                'UPDATE ?:bm_layouts SET style_id = ?s WHERE layout_id = ?i AND theme_name = ?s',
                $style_id, $layout_id, $this->theme_name
            );

            $this->createMissedLogoTypesForLayout($layout_id, $style_id);
        }

        return $result;
    }

    /**
     * Creates logos of missing logo types for given layout and style.
     *
     * @param int    $layout_id Layout ID
     * @param string $style_id  Style ID
     */
    public function createMissedLogoTypesForLayout($layout_id, $style_id)
    {
        if ((fn_allowed_for('ULTIMATE') && !empty($this->company_id) || fn_allowed_for('MULTIVENDOR'))
            && !empty($layout_id)
            && !empty($style_id)
            && !empty($this->theme_name)
        ) {
            $logos = fn_get_logos($this->company_id, $layout_id, $style_id);
            $logo_types = fn_get_logo_types(false);

            $missed_logo_types = array_diff_key($logo_types, $logos);

            if (!empty($missed_logo_types)) {
                fn_create_theme_logos_by_layout_id(
                    $this->theme_name,
                    $layout_id,
                    $this->company_id,
                    false,
                    $style_id,
                    array_keys($missed_logo_types)
                );
            }
        }
    }

    /**
     * Gets style id for specified layout
     *
     * @param  int    $layout_id Layout ID
     * @return string Style ID (Example: satori, modern, etc.)
     */
    public function getStyle($layout_id)
    {
        $result = db_get_field('SELECT style_id FROM ?:bm_layouts WHERE layout_id = ?i', $layout_id);

        return $result;
    }

    /**
     * Copy style
     * @param  string  $from style ID to copy from
     * @param  string  $to   style ID to copy to
     * @return boolean true on success, false otherwise
     */
    public function copy($from, $to)
    {
        $from = fn_basename($from);
        $from = array(
            'style' => $from,
            'less' => $this->getStyleFile($from, 'less'),
            'css' => $this->getStyleFile($from, 'css')
        );

        $to = fn_basename($to);
        $to = array(
            'style' => $to,
            'less' => $this->getStyleFile($to, 'less'),
            'css' => $this->getStyleFile($to, 'css')
        );

        $clone_logos = true;

        /**
         * Executes before copying styles file, allows to modify source and destination files path.
         *
         * @param \Tygh\Themes\Styles $this        Styles instance
         * @param array               $from        Source style info: name, less path, css path
         * @param array               $to          Destination file info: name, less path, css path
         * @param bool                $clone_logos Indicates if logos have to be cloned for the new style
         */
        fn_set_hook('styles_copy', $this, $from, $to, $clone_logos);

        if (is_file($from['less'])) {

            fn_mkdir(dirname($to['less']));

            if (fn_copy($from['less'], $to['less'])) {

                if (file_exists($from['css'])) {
                    fn_copy($from['css'], $to['css']);
                }

                $patterns_instance = Patterns::instance($this->params);

                fn_copy($patterns_instance->getPath($from['style']), $patterns_instance->getPath($to['style']));
                $content = str_replace(
                    $patterns_instance->getRelPath($from['style']),
                    $patterns_instance->getRelPath($to['style']),
                    fn_get_contents($to['less'])
                );
                fn_put_contents($to['less'], $content);

                if ($clone_logos) {
                    // Clone logos for new style
                    $logos = db_get_array('SELECT * FROM ?:logos WHERE style_id = ?s AND company_id = ?i', $from['style'], $this->company_id);

                    foreach ($logos as $logo) {
                        $object_id = fn_update_logo(array(
                            'type' => $logo['type'],
                            'layout_id' => $logo['layout_id'],
                            'style_id' => $to['style'],
                        ), $logo['company_id'], $logo['storefront_id']);

                        fn_clone_image_pairs($object_id, $logo['logo_id'], 'logos');
                    }
                }

                return true;
            }

        }

        return false;
    }

    /**
     * Gets style LESS code.
     *
     * @param  array $current_style Style data to override current style data
     *
     * @return string Style LESS code
     */
    public function getLess($current_style = array())
    {
        $custom_less = '';

        $style = $this->get(Registry::get('runtime.layout.style_id'));

        if (!empty($style['less'])) {
            $custom_less = $style['less'];
            $custom_less .= "\n" . $style['custom_css'];
        }

        if (!empty($current_style)) {
            $custom_less .= Less::arrayToLessVars($current_style);
        }

        return $custom_less;
    }

    /**
     * Gets style file path
     * @param  string $style_id style ID
     * @param  string $type     file type (less/css/png)
     * @return string style file path
     */
    public function getStyleFile($style_id, $type = 'less')
    {
        $path = $this->getStylesPath();

        /**
         * Modifies the path to style file
         *
         * @param object  $this Styles object
         * @param string  $path current path
         * @param string  $style_id style ID
         * @param string  $type file type
         */
        fn_set_hook('styles_get_style_file', $this, $path, $style_id, $type);

        return $path . '/' . $style_id . '.' . $type;
    }

    /**
     * Gets styles path
     * @return string styles path
     */
    public function getStylesPath()
    {
        return $this->styles_path . 'data';
    }

    public function getStylesDir()
    {
        return $this->styles_dir . 'data';
    }

    /**
     * Gets fonts path
     *
     * @return string Fonts path
     */
    public function getFontsPath()
    {
        return $this->fonts_path;
    }

    /**
     * Gets fonts dir
     *
     * @return string Fonts dir
     */
    public function getFontsDir()
    {
        return $this->fonts_dir;
    }

    /**
     * Gets custom CSS code from LESS code
     * @param  string $style_id style ID
     * @return string custom CSS code
     */
    private function getCustomCss($style_id)
    {
        $file = $this->getStyleFile($style_id, 'css');
        if (file_exists($file)) {
            return fn_get_contents($file);
        }

        return '';
    }

    /**
     * Adds custom css to style LESS
     * @param  string  $style_id   style ID
     * @param  string  $custom_css CSS code
     * @return integer custom CSS length, written to file, boolean false on error
     */
    private function addCustomCss($style_id, $custom_css)
    {
        $style_path = $this->getStyleFile($style_id, 'css');

        /**
         * Executes before saving style custom CSS content into a file, allows to modify style data and saving path.
         *
         * @param \Tygh\Themes\Styles $this       Styles instance
         * @param string              $style_id   File name of the style schema (like: "satori")
         * @param string              $style_path Path to save style to
         * @param string              $custom_css Custom CSS content of the style
         */
        fn_set_hook('styles_add_custom_css', $this, $style_id, $style_path, $custom_css);

        return fn_put_contents($style_path, $custom_css);
    }

    /**
     * Adds Google Font initialization to style LESS
     * @param  array  $style_data style data
     * @param  string $less       style LESS code
     * @return string style LESS code
     */
    private function addGoogleFonts($style_data, $less)
    {
        $content = array();

        $less = preg_replace("#/\*{$this->gfonts_tag}\*/(.*?)/\*/{$this->gfonts_tag}\*/#s", '', $less);

        foreach ($this->schema['fonts']['fields'] as $field => $data) {
            $font_name = trim($style_data[$field]);
            if (empty($this->schema['fonts']['families'][$font_name])) {
                // Google font!
                if (empty($content[$font_name])) {
                    list($family) = explode(',', $font_name);
                    $font_data = $this->getGoogleFontData($family);

                    // Set user agent manually to get woff2 fonts
                    $url = 'https://fonts.googleapis.com/css?display=swap&family=' . $family;
                    if (!empty($font_data['weight'])) {
                        $url .= ':' . $font_data['weight'];
                    }
                    $css = Http::get($url, [], [
                        'headers' => [
                            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36'
                        ],
                    ]);

                    if (Http::getStatus() == Http::STATUS_OK && !empty($css)) {
                        preg_match_all('/(?<=src: url\().+?(?=\))/', $css, $matches);
                        $font_urls = reset($matches);

                        foreach ($font_urls as $font_url) {
                            $file_name = basename($font_url);
                            $file_path = $this->fonts_path . $file_name;

                            if (!file_exists($file_path)) {
                                fn_put_contents($file_path, fn_get_contents($font_url));
                            }

                            $css = str_replace($font_url, '../media/fonts/' . $file_name, $css);
                        }

                        $content[$font_name] = str_replace('http://', '//', $css);
                    }
                }
            }
        }

        if (!empty($content)) {
            $less .= "\n/*{$this->gfonts_tag}*/" . "\n" . implode("\n", $content) . "\n/*/{$this->gfonts_tag}*/";
        }

        return $less;
    }

    /**
     * Get custom fonts from LESS file
     * @param  string $less LESS content
     * @return array  custom fonts
     */
    public function getCustomFonts($less)
    {
        $families = array();
        if (preg_match("#/\*{$this->gfonts_tag}\*/(.*?)/\*/{$this->gfonts_tag}\*/#s", $less, $matches)) {
            if (preg_match_all('/font-family: \'([\w\-\_ ]+)\';/', $matches[1], $fonts)) {
                $families = $fonts[1];
            }
        }

        return $families;
    }

    /**
     * Converts CSS property ( url("../a.png") ) to URL (http://e.com/a.png)
     * @param  array $style_data style data
     * @return array modified parsed style data vars
     */
    private function cssToUrl($style_data)
    {
        $url = Registry::get('config.current_location') . '/' . fn_get_theme_path('[relative]/[theme]/');
        $parsed = array();
        if (!empty($this->schema['backgrounds']['fields'])) {
            foreach ($this->schema['backgrounds']['fields'] as $field) {
                if (!empty($field['properties']['pattern'])) {
                    $var_name = $field['properties']['pattern'];

                    if (!empty($style_data[$var_name]) && strpos($style_data[$var_name], 'url(') !== false) {
                        $parsed[$var_name] = fn_normalize_path(preg_replace('/url\([\'"]?\.\.\/(.*?)[\'"]?\)/', $url . '$1', $style_data[$var_name]));
                        if (strpos($parsed[$var_name], '?') === false) {
                            $parsed[$var_name] .= '?' . TIME;
                        }
                    }
                }
            }
        }

        return $parsed;
    }

    /**
     * Converts URL (http://e.com/a.png) to CSS property ( url("../a.png") )
     * @param  string $style_id   style ID
     * @param  array  $style_data style data (fields)
     * @return array  modified style data
     */
    private function urlToCss($style_id, $style_data)
    {
        $patterns_url = Patterns::instance($this->params)->getUrl($style_id, true);

        if (!empty($this->schema['backgrounds']['fields'])) {
            foreach ($this->schema['backgrounds']['fields'] as $field) {
                if (!empty($field['properties']['pattern'])) {
                    $var_name = $field['properties']['pattern'];

                    if (!empty($style_data[$var_name]) && strpos($style_data[$var_name], '//') !== false) {

                        $url = preg_replace('/url\([\'"]?(.*?)[\'"]?\)/', '$1', $style_data[$var_name]);
                        if (strpos($url, '//') === 0) {
                            $url = 'http:' . $url;
                        }

                        $url = fn_normalize_path($url);

                        if (strpos($url, $patterns_url) !== false) {
                            $url = str_replace($patterns_url, '..', $url);
                            if (strpos($url, '?') !== false) { // URL is parsed by Less::parseUrls method, so remove everything after ?
                                list($url) = explode('?', $url);
                            }

                        } elseif ($style_id) { // external url

                            $tmp_file = fn_create_temp_file();
                            fn_put_contents($tmp_file, fn_get_contents($url));

                            $_style = Patterns::instance($this->params)->save($style_id, array('data' => $style_data), array(
                                $var_name => array(
                                    'name' => fn_basename($url),
                                    'path' => $tmp_file,
                                )
                            ));

                            $style_data = $_style['data'];

                            continue; // assignment done in save method
                        }

                        $style_data[$var_name] = 'url(' . $url . ')';
                    }
                }
            }
        }

        return $style_data;
    }

    /**
     * Gets google font properties
     * @param  string $font font name
     * @return array  font properties
     */
    private function getGoogleFontData($font)
    {
        if (empty($this->google_fonts)) {
            $fonts = fn_get_contents(Registry::get('config.dir.root') . '/js/tygh/google_fonts_list.js');
            $this->google_fonts = json_decode($fonts, true);
        }

        foreach ($this->google_fonts as $sections => $fonts) {
            foreach ($fonts as $gfont) {
                if ($gfont['name'] == $font) {
                    return $gfont;
                }
            }
        }

        return array();
    }

    /**
     * Gets a style preview image
     * @param  string $style_id style ID
     * @return string preview image URL
     */
    public function getStyleImage($style_id)
    {
        $url = '';
        $path = $this->getStyleFile($style_id, 'png');

        if (file_exists($path)) {
            $url = Registry::get('config.current_location') . '/' . fn_get_rel_dir($path);
        }

        return $url;
    }

    public static function factory($theme_name, $params = array())
    {
        if (empty(self::$instances[$theme_name])) {
            self::$instances[$theme_name] = new self($theme_name);
        }

        self::$instances[$theme_name]->params = $params;

        return self::$instances[$theme_name];
    }

    /**
     * Sets styles manifest data
     *
     * @param array $manifest_data
     */
    public function setManifest($manifest_data)
    {
        $this->manifest = $manifest_data;
    }

    /**
     * Saves styles manifest file
     *
     * @return bool
     */
    public function saveManifest()
    {
        if (empty($this->manifest)) {
            return false;
        }

        return fn_put_contents($this->styles_path . 'manifest.json', json_encode($this->manifest));
    }

    /**
     * Sets styles schema data
     *
     * @param array $schema_data
     */
    public function setSchema($schema_data)
    {
        $this->schema = $schema_data;
    }

    /**
     * Saves styles schema file
     *
     * @return bool
     */
    public function saveSchema()
    {
        if (empty($this->schema)) {
            return false;
        }

        return fn_put_contents($this->styles_path . 'schema.json', json_encode($this->schema));
    }

    /**
     * Checks if style can be removed.
     * A style can be removed when it's not used in the current layout and is not the default one.
     *
     * @param array $style Style data (obtained from \Tygh\Themes\Styles::get())
     *
     * @return bool Whether style can be removed
     */
    public function isRemovable($style)
    {
        $is_removable = true;

        if (!empty($style['is_default']) || Registry::get('runtime.layout.style_id') == $style['style_id']) {
            $is_removable = false;
        }

        /**
         * Executes after removability of style is checked, allows to modify the check results.
         *
         * @param \Tygh\Themes\Styles $this         Styles instance
         * @param array               $style        Style data
         * @param bool                $is_removable Whether style can be removed
         */
        fn_set_hook('styles_is_removable_post', $this, $style, $is_removable);

        return $is_removable;
    }

    /**
     * Load Google Font initialization to style LESS for style list
     *
     * @param array<array-key, string|bool|array<array-key, string>> $style_list List of available styles
     *
     * @return void
     */
    public function loadGoogleFontsForStyles(array $style_list = [])
    {
        foreach ($style_list as $style_id => $style) {
            if (
                empty($style['data'])
                || empty($style['less'])
                || $style_id === 'Bright_theme'
            ) {
                continue;
            }

            /** @var array<array-key, string> $style['data'] */
            $less = $this->addGoogleFonts($style['data'], $style['less']);

            $style_path = $this->getStyleFile($style_id);
            fn_put_contents($style_path, $less);
        }
    }
}
