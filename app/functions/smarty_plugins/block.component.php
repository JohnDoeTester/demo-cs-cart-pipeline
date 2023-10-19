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

use Tygh\Exceptions\DeveloperException;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @param array<string, string>     $params  Block params
 * @param string                    $content Block content
 * @param \Smarty_Internal_Template $tempale Smarty template
 * @param bool                      $repeat  Repeat flag
 *
 * @return string
 */
function smarty_block_component(array $params, $content, Smarty_Internal_Template $tempale, &$repeat)
{
    if ($repeat === true) {
        return $content;
    }

    if (!isset($params['name'])) {
        throw new DeveloperException('Component must have name');
    }

    $funciton_name = smarty_block_include_component_file($params['name']);

    unset($params['name']);

    return $funciton_name($params, $content, $tempale);
}

/**
 * Includes file with component function
 *
 * @param string $component_name Component name
 *
 * @return string Function name
 */
function smarty_block_include_component_file($component_name)
{
    static $component_files;

    if ($component_files === null) {
        $smarty = Tygh::$app['view'];
        $func_read_dir = static function ($dir, $prefix = null) use (&$component_files, &$func_read_dir) {
            if (!is_dir($dir)) {
                return;
            }

            $dh = opendir($dir);

            if ($dh === false) {
                return;
            }

            while ($file = readdir($dh)) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filepath = $dir . '/' . $file;

                if (strpos($file, 'component.') !== false) {
                    $func = sprintf(
                        'smarty_component_%s%s',
                        $prefix ? $prefix . '_' : '',
                        str_replace('component.', '', basename($file, '.php'))
                    );
                    $component_files[$func] = $filepath;
                } elseif (is_dir($filepath)) {
                    $func_read_dir($filepath, $file);
                }
            }

            closedir($dh);
        };

        foreach ($smarty->getPluginsDir() as $dir) {
            $func_read_dir(rtrim($dir, '/') . '/components');
        }
    }

    $funciton_name = sprintf('smarty_component_%s', str_replace('.', '_', $component_name));

    if (!function_exists($funciton_name) && isset($component_files[$funciton_name])) {
        require_once $component_files[$funciton_name];
    }

    if (!function_exists($funciton_name)) {
        throw new DeveloperException(sprintf('Component %s function %s not found', $component_name, $funciton_name));
    }

    return $funciton_name;
}
