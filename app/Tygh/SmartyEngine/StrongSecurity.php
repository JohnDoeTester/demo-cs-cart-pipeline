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
// phpcs:ignoreFile

namespace Tygh\SmartyEngine;

class StrongSecurity extends \Smarty_Security
{
    /**
     * @var null|array<string, bool>
     */
    public static $allowed_functions = null;

    /**
     * Check if PHP function is trusted.
     *
     * @param string $function_name Function name
     * @param object $compiler      Compiler object
     *
     * @return boolean                 true if function is trusted
     */
    public function isTrustedPhpFunction($function_name, $compiler)
    {
        if (static::isFunctionAllowed($function_name)) {
            return true;
        }

        $compiler->trigger_template_error("PHP function '{$function_name}' not allowed by security setting");
        return false;
    }


    /**
     * Check if static class is trusted.
     *
     * @param string $class_name Class name
     * @param object $compiler   Compiler object
     *
     * @return boolean                 true if class is trusted
     */
    public function isTrustedStaticClass($class_name, $compiler)
    {
        return true;
    }

    /**
     * Check if static class method/property is trusted.
     *
     * @param string $class_name Class name
     * @param string $params     Params
     * @param object $compiler   Compiler object
     *
     * @return bool True if class method is trusted
     */
    public function isTrustedStaticClassAccess($class_name, $params, $compiler)
    {
        return true;
    }

    /**
     * Check if PHP modifier is trusted.
     *
     * @param string $modifier_name Modifier name
     * @param object $compiler      Compiler object
     *
     * @return bool True if modifier is trusted
     */
    public function isTrustedPhpModifier($modifier_name, $compiler)
    {
        if (static::isFunctionAllowed($modifier_name)) {
            return true;
        }

        $compiler->trigger_template_error("modifier '{$modifier_name}' not allowed by security setting");
        return false;
    }

    /**
     * Check if tag is trusted.
     *
     * @param string $tag_name Tag name
     * @param object $compiler Compiler object
     *
     * @return bool True if tag is trusted
     */
    public function isTrustedTag($tag_name, $compiler)
    {
        return true;
    }

    /**
     * Check if special $smarty variable is trusted.
     *
     * @param string $var_name Variable name
     * @param object $compiler Compiler object
     *
     * @return bool True if special $smarty variable is trusted
     */
    public function isTrustedSpecialSmartyVar($var_name, $compiler)
    {
        return true;
    }

    /**
     * Check if modifier plugin is trusted.
     *
     * @param string $modifier_name Modifier name
     * @param object $compiler      Compiler object
     *
     * @return bool True if modifier plugin is trusted
     */
    public function isTrustedModifier($modifier_name, $compiler)
    {
        return true;
    }

    /**
     * Check if constants are enabled or trusted
     *
     * @param string $const    Constant name
     * @param object $compiler Compiler object
     *
     * @return bool
     */
    public function isTrustedConstant($const, $compiler)
    {
        return true;
    }

    /**
     * Check if stream is trusted.
     *
     * @param string $stream_name Stream name
     *
     * @return bool True if stream is trusted
     */
    public function isTrustedStream($stream_name)
    {
        return true;
    }

    /**
     * Check if directory of file resource is trusted.
     *
     * @param string    $filepath File path
     * @param null|bool $isConfig Is Config
     *
     * @return bool true if directory is trusted
     */
    public function isTrustedResourceDir($filepath, $isConfig = null)
    {
        return true;
    }

    /**
     * Check if URI (e.g. {fetch} or {html_image}) is trusted
     *
     * @param string $uri URI
     *
     * @return bool True if URI is trusted
     */
    public function isTrustedUri($uri)
    {
        return true;
    }

    /**
     * Check if directory of file resource is trusted.
     *
     * @param string $filepath File path
     *
     * @return bool True if directory is trusted
     */
    public function isTrustedPHPDir($filepath)
    {
        return true;
    }

    /**
     * @param string $func_name Function or modifier name
     *
     * @return bool
     */
    public static function isFunctionAllowed($func_name)
    {
        if (static::$allowed_functions === null) {
            static::$allowed_functions = fn_get_schema('block_manager', 'smarty_allowed_functions');
        }

        return isset(static::$allowed_functions[$func_name]) && static::$allowed_functions[$func_name];
    }
}
