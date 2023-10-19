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

/**
 * Outputs configurable page field.
 *
 * @param array<string, string>     $params   Component parameters
 * @param string                    $content  Output field content
 * @param \Smarty_Internal_Template $template Template instance
 *
 * @return string
 */
function smarty_component_configurable_page_field(array $params, $content, Smarty_Internal_Template $template)
{
    if (
        !isset($params['entity'], $params['tab'], $params['section'], $params['field'])
        || !trim($content)
    ) {
        return $content;
    }

    list($entity, $tab, $section, $field) = [$params['entity'], $params['tab'], $params['section'], $params['field']];

    static $entity_fields_schema = [];
    if (!isset($entity_fields_schema[$entity])) {
        $entity_fields_schema[$entity] = fn_get_schema($entity, 'page_configuration') ?: [];
    }

    if (!isset($entity_fields_schema[$entity][$tab]['sections'][$section]['fields'][$field])) {
        return $content;
    }

    $field_config = array_merge(
        [
            'is_optional' => false,
            'is_visible'  => true,
            'is_removed'  => false,
        ],
        $entity_fields_schema[$entity][$tab]['sections'][$section]['fields'][$field]
    );

    /**
     * Executes before configurable page field output, allows you to modify the field to remove it from page or hide it.
     *
     * @param string                         $entity       Page entity
     * @param string                         $tab          Tab of the field on the page
     * @param string                         $section      Section of the field in the tab
     * @param string                         $field        Field identifier
     * @param array<string, string|bool|int> $field_config Field configuration
     * @param array<string, string>          $params       Component parameters
     * @param string                         $content      Output field content
     * @param \Smarty_Internal_Template      $template     Template instance
     */
    fn_set_hook(
        'smarty_component_configurable_page_field_before_output',
        $entity,
        $tab,
        $section,
        $field,
        $field_config,
        $params,
        $content,
        $template
    );

    if (!$field_config['is_optional']) {
        return $content;
    }

    if ($field_config['is_removed']) {
        return '';
    }

    if ($field_config['is_visible']) {
        return $content;
    }

    return '<div class="hidden">' . $content . '</div>';
}
