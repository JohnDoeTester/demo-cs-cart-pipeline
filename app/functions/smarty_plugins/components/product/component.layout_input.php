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

use Tygh\Settings;
use Tygh\Tools\Url;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @param array<string, string>     $params   Block params
 * @param string                    $content  Block content
 * @param \Smarty_Internal_Template $template Smarty template
 *
 * @return string
 */
function smarty_component_product_layout_input(array $params, $content, Smarty_Internal_Template $template)
{
    $object_type = isset($params['object']) ? (string) $params['object'] : 'product';
    $object_id = isset($params['id']) ? (int) $params['id'] : 0;
    $company_id = isset($params['company_id']) && fn_allowed_for('ULTIMATE') ? (int) $params['company_id'] : null;
    $value = $params['value'];

    if (fn_allowed_for('ULTIMATE')) {
        $company_id = (fn_get_runtime_company_id()) ? fn_get_runtime_company_id() : $company_id;
    }

    $global_value = Settings::getSettingValue('Appearance.global_product_details_view', $company_id);

    if (isset($global_value)) {
        return null;
    }

    $default_value = Settings::getSettingValue('Appearance.default_product_details_view', $company_id);

    $list = fn_get_product_details_views($object_id, $company_id);
    $variants = [];

    if ($object_type === 'product') {
        $category_id = $params['category_id'] ?: fn_get_product_main_category_id($object_id);
        $parent_value = fn_get_product_details_view_by_category($category_id);
        $parent_value = empty($parent_value) ? 'default' : $parent_value;

        if ($category_id) {
            $parent_url = Url::buildUrn('categories.update', [
                'category_id'      => $category_id,
                'selected_section' => 'views'
            ]);

            if (fn_check_view_permissions($parent_url)) {
                $variants[] = [
                    'type'  => 'inheritance_edit',
                    'value' => null,
                    'name'  => __('default_custom.edit_parent', ['[name]' => ($parent_value === 'default') ? $list[$default_value] : $list[$parent_value]]),
                    'url'   => $parent_url
                ];
            }
        }

        if ($parent_value !== 'default') {
            $variants[] = [
                'type'  => 'inheritance',
                'value' => 'default',
                'name'  => __('default_custom.parent', ['[name]' => $list[$parent_value]]),
            ];
        }
    }

    if (!isset($parent_value) || $parent_value === 'default') {
        $name = $object_type === 'product' && !$object_id
            ? __('default_custom.parent', ['[name]' => __('category_template')])
            : __('default_custom.global', ['[name]' => $list[$default_value]]);
        $variants[] = [
            'type'  => 'inheritance',
            'value' => 'default',
            'name'  => $name
        ];
        if (fn_check_view_permissions('settings.manage?section_id=Appearance&highlight=default_product_details_view')) {
            $variants[] = [
                'type'  => 'inheritance_edit',
                'value' => null,
                'name'  => __('default_custom.edit_global', ['[name]' => $list[$default_value]]),
                'url'   => 'settings.manage?section_id=Appearance&highlight=default_product_details_view'
            ];
        }
    }

    foreach ($list as $key => $title) {
        if ($key === 'default') {
            continue;
        }

        $variants[] = [
            'type'  => 'variant',
            'value' => $key,
            'name'  => $title,
        ];
    }

    $template->assign([
        'component_id' => 'elm_details_layout',
        'name'         => isset($params['input_name']) ? $params['input_name'] : 'product_data[details_layout]',
        'variants'     => $variants,
        'value'        => $value,
        'show_custom'  => false
    ]);

    $input = $template->fetch('components/default_custom.tpl');

    if ($content && strpos($content, '#INPUT#') !== false) {
        return str_replace('#INPUT#', $input, $content);
    }

    return $input;
}
