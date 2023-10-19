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

use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @param array<string, string>     $params  Block params
 * @param string                    $content Block content
 * @param \Smarty_Internal_Template $tempale Smarty template
 *
 * @throws Exception       Internal smarty rendering error.
 * @throws SmartyException If unable to load template.
 *
 * @return string
 */
function smarty_component_vendor_debt_payout_select_lowers_allowed_balance(array $params, $content, Smarty_Internal_Template $tempale)
{
    if (Registry::get('addons.vendor_debt_payout.global_lowers_allowed_balance') !== null) {
        return '';
    }
    $tempale->assign([
        'value' => Registry::ifGet('addons.vendor_debt_payout.default_lowers_allowed_balance', 0)
    ]);
    $variants = [];
    $global_value = trim(html_entity_decode(strip_tags($tempale->fetch('common/price.tpl'))));
    $value = (!isset($params['value']) || $params['value'] === 'default') ? 'default' : fn_format_price((float) $params['value']);
    $custom_input_styles = isset($params['custom_input_styles']) ? (string) $params['custom_input_styles'] : '';
    $custom_input_attributes = isset($params['custom_input_attributes']) ? (array) $params['custom_input_attributes'] : '';

    if ($value !== 'default') {
        $tempale->assign([
            'value' => $value
        ]);
        $value_name = trim(html_entity_decode(strip_tags($tempale->fetch('common/price.tpl'))));

        $variants[] = [
            'type'  => 'variant',
            'value' => $value,
            'name'  => $value_name,
        ];
    }

    $variants[] = [
        'type'  => 'inheritance',
        'value' => 'default',
        'name'  => __('default_custom.global', ['[name]' => $global_value])
    ];
    if (fn_check_view_permissions('addons.manage')) {
        $variants[] = [
            'type'  => 'inheritance_edit',
            'value' => null,
            'name'  => __('default_custom.edit_global', ['[name]' => $global_value]),
            'url'   => 'addons.update&addon=vendor_debt_payout'
        ];
    }

    $tempale->assign([
        'component_id'            => 'lowers_allowed_balance',
        'name'                    => isset($params['input_name']) ? $params['input_name'] : 'plan_data[lowers_allowed_balance]',
        'variants'                => $variants,
        'value'                   => $value,
        'show_custom'             => true,
        'custom_input_styles'     => $custom_input_styles,
        'custom_input_attributes' => $custom_input_attributes
    ]);

    return $tempale->fetch('components/default_custom.tpl');
}
