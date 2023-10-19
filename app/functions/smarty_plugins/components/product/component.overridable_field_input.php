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

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Registry;
use Tygh\Settings;
use Tygh\Enum\SettingTypes;

/**
 * @param array<string, string>     $params   Block params
 * @param string                    $content  Block content
 * @param \Smarty_Internal_Template $template Smarty template
 *
 * @return string|null
 */
function smarty_component_product_overridable_field_input(array $params, $content, Smarty_Internal_Template $template)
{
    $allow_global_individual_settings = Registry::ifGet('config.tweaks.allow_global_individual_settings', false);
    $schema = fn_get_product_overridable_fields_schema();
    $field_name = $params['field_name'];
    $value = $params['value'];
    $current_value = $value === null || $value === '' ? '__default__' : $value;

    if (!isset($schema[$field_name])) {
        return null;
    }

    $field_schema = $schema[$field_name];
    $company_id = isset($params['company_id']) && fn_allowed_for('ULTIMATE') ? (int) $params['company_id'] : null;

    if (fn_allowed_for('ULTIMATE')) {
        $company_id = (fn_get_runtime_company_id()) ? fn_get_runtime_company_id() : $company_id;
    }

    $global_value = Settings::getSettingValue($field_schema['global_setting'], $company_id);

    if (isset($global_value)) {
        return null;
    }

    $default_value = Settings::getSettingValue($field_schema['default_setting'], $company_id);
    $default_variant_name = isset($params['variants']) ? $params['variants'][$default_value] : $default_value;
    list($setting_section, $setting_name) = explode('.', $field_schema['default_setting']);
    $edit_url = 'settings.manage?section_id=' . $setting_section . '&highlight=' . $setting_name;
    $variants = [];

    if ($allow_global_individual_settings) {
        $variants[] = [
            'type'  => 'inheritance',
            'value' => '__default__',
            'name'  => __('default_custom.global', ['[name]' => $default_variant_name])
        ];

        if (fn_check_view_permissions($edit_url)) {
            $variants[] = [
                'type'  => 'inheritance_edit',
                'value' => null,
                'name'  => __('default_custom.edit_global', ['[name]' => $default_variant_name]),
                'url'   => $edit_url
            ];
        }
    } elseif ($current_value === '__default__') {
        $current_value = $default_value;
    }

    if (isset($params['variants'])) {
        foreach ($params['variants'] as $key => $title) {
            if ($key === '__default__') {
                continue;
            }

            $variants[] = [
                'type'  => 'variant',
                'value' => $key,
                'name'  => $title,
            ];
        }
    }

    if ($params['type'] === SettingTypes::INPUT && $current_value !== '__default__') {
        $variants[] = [
            'type'  => 'variant',
            'value' => $current_value,
            'name'  => $current_value,
        ];
    }

    $template->assign(array_merge($params, [
        'component_id'            => 'elm_' . $params['field_name'],
        'name'                    => isset($params['input_name']) ? $params['input_name'] : 'product_data[' . $params['field_name'] . ']',
        'variants'                => $variants,
        'value'                   => $current_value,
        'show_custom'             => $params['type'] === SettingTypes::INPUT,
        'disable_inputs'          => isset($params['disable_inputs']) ? (bool) $params['disable_inputs'] : false,
    ]));

    $input = $template->fetch('components/default_custom.tpl');

    if ($content && strpos($content, '#INPUT#') !== false) {
        return str_replace('#INPUT#', $input, $content);
    }

    return $input;
}
