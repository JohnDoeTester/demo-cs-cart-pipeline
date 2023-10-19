<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier<br>
 * Name:     render_tag_attrs<br>
 * Purpose:  Renders tag attributes from array
 * Example:  {["data-ca-url" => "http://example.com", "data-ca-image" => "http://example.com/image.png"]|render_tag_attrs}
 * -------------------------------------------------------------
 *
 * @param array<string, int|string|bool|array<string>> $attributes     List of the attribute which was transformed to string
 * @param array<string, int|string|bool|array<string>> $default_attrs  List of the value which will be used if attribute is not passed
 * @param array<string, int|string|bool|array<string>> $extended_attrs List of the attribute values which was required include to the string
 *
 * @return string Prepared string with attributes for using in template
 */
function smarty_modifier_render_tag_attrs($attributes, $default_attrs = [], $extended_attrs = [])
{
    $attributes = (array) $attributes;
    $result = [];
    $attributes = array_merge($default_attrs, $attributes);

    foreach ($attributes as $name => $value) {
        if (is_bool($value)) {
            if ($value) {
                $result[] = $name;
            }
            continue;
        } elseif (is_array($value)) {
            if (isset($extended_attrs[$name])) {
                $value = array_merge($value, (array) $extended_attrs[$name]);
            }
            $value = json_encode($value);
        }
        if (!empty($extended_attrs[$name])) {
            $value .= ' ' . trim($extended_attrs[$name]);
        }

        $result[] = sprintf('%s="%s"', $name, htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE));
    }

    return implode(' ', $result);
}
