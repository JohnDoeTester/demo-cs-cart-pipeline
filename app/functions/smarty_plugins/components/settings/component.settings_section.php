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
 * @param array<string, string>     $params   Block params
 * @param string                    $content  Block content
 * @param \Smarty_Internal_Template $template Smarty template
 *
 * @return string
 */
function smarty_component_settings_settings_section(array $params, $content, Smarty_Internal_Template $template)
{
    $allow_global_individual_settings = Registry::ifGet('config.tweaks.allow_global_individual_settings', false) || !empty($params['allow_global_individual_settings']);
    $items = isset($params['subsection']) ? array_filter((array) $params['subsection']) : [];

    if (!$items) {
        return '';
    }

    $settings_names = array_column($items, 'object_id', 'name');

    foreach ($items as $key => &$item) {
        if ($allow_global_individual_settings && $item['name'] === 'inventory_tracking') {
            unset($items[$key]);
            continue;
        }

        if (strpos($item['name'], 'default_') !== 0) {
            continue;
        }

        $global_setting_name = preg_replace('/^default_/', 'global_', $item['name'], 1);

        if (!isset($settings_names[$global_setting_name])) {
            continue;
        }

        $global_setting_id = $settings_names[$global_setting_name];
        $global_setting_item = $items[$global_setting_id];

        if ($global_setting_item['type'] !== $item['type']) {
            continue;
        }

        if (!$allow_global_individual_settings) {
            if ($item['name'] === 'default_product_details_view') {
                unset($items[$global_setting_id]);
                continue;
            }

            unset($items[$global_setting_id], $items[$key]);
            continue;
        }

        $item['global_setting'] = $global_setting_item;

        if (isset($global_setting_item['value'])) {
            $item['value'] = $global_setting_item['value'];
            $item['has_global_value'] = true;
        } else {
            $item['has_global_value'] = false;
        }

        unset($items[$global_setting_id]);
    }
    unset($item);

    $template->assign(array_merge($params, [
        'items' => $items
    ]));

    return $template->fetch('common/settings_section.tpl');
}
