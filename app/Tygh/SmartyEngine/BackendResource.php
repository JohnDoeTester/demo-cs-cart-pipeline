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

namespace Tygh\SmartyEngine;

use Tygh\Registry;
use Smarty_Template_Source;
use Smarty_Internal_Template;

class BackendResource extends FileResource
{
    /**
     * build template filepath by traversing the template_dir array
     *
     * @param Smarty_Template_Source   $source    source object
     * @param Smarty_Internal_Template $_template template object
     *
     * @return string fully qualified filepath
     */
    protected function buildFilepath(Smarty_Template_Source $source, Smarty_Internal_Template $_template = null)
    {
        $file = $source->name;
        $_directory = Registry::get('config.dir.design_backend') . 'templates/';

        // resolve relative path
        if (!preg_match('/^([\/\\\\]|[a-zA-Z]:[\/\\\\])/', $file)) {
            // don't we all just love windows?
            $_path = DIRECTORY_SEPARATOR . trim($file, '/');
            $_was_relative = true;
        } else {
            // don't we all just love windows?
            $_path = str_replace('\\', '/', $file);
        }
        $_path = $this->normalizePath($_path, false);
        if (DIRECTORY_SEPARATOR !== '/') {
            // don't we all just love windows?
            $_path = str_replace('/', '\\', $_path);
        }
        // revert to relative
        if (isset($_was_relative)) {
            $_path = substr($_path, 1);
        }

        // this is only required for directories
        $file = rtrim($_path, '/\\');

        // relative file name?
        if (!preg_match('/^([\/\\\\]|[a-zA-Z]:[\/\\\\])/', $file)) {
            $_filepath = $_directory . $file;
            if ($this->fileExists($source, $_filepath)) {
                return $this->normalizePath($_filepath);
            }
        }

        // try absolute filepath
        if ($this->fileExists($source, $file)) {
            return $file;
        }

        // give up
        return false;
    }

    /**
     * Test is file exists and save timestamp
     *
     * @param Smarty_Template_Source $source Source object
     * @param string                 $file   File name
     *
     * @return bool                   true if file exists
     */
    protected function fileExists(Smarty_Template_Source $source, $file)
    {
        $source->timestamp = is_file($file) ? filemtime($file) : false;

        return $source->exists = !!$source->timestamp;
    }

    /**
     * Normalize Paths "foo/../bar" to "bar"
     *
     * @param string $_path Path to normalize
     * @param bool   $ds    Respect windows directory separator
     *
     * @return string  normalized path
     */
    protected function normalizePath($_path, $ds = true)
    {
        if ($ds) {
            // don't we all just love windows?
            $_path = str_replace('\\', '/', $_path);
        }

        $offset = 0;

        // resolve simples
        $_path = preg_replace('#/\./(\./)*#', '/', $_path);
        // resolve parents
        while (true) {
            $_parent = strpos($_path, '/../', $offset);
            if (!$_parent) {
                break;
            }
            if ($_path[$_parent - 1] === '.') {
                $offset = $_parent + 3;
                continue;
            }

            $_pos = strrpos($_path, '/', $_parent - strlen($_path) - 1);
            if ($_pos === false) {
                // don't we all just love windows?
                $_pos = $_parent;
            }

            $_path = substr_replace($_path, '', $_pos, $_parent + 3 - $_pos);
        }

        if ($ds && DIRECTORY_SEPARATOR !== '/') {
            // don't we all just love windows?
            $_path = str_replace('/', '\\', $_path);
        }

        return $_path;
    }
}
