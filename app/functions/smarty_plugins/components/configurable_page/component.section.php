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
 * Outputs configurable page section.
 *
 * @param array<string, string>     $params   Component parameters
 * @param string                    $content  Output field content
 * @param \Smarty_Internal_Template $template Template instance
 *
 * @return string
 */
function smarty_component_configurable_page_section(array $params, $content, Smarty_Internal_Template $template)
{
    if (
        !isset($params['entity'], $params['tab'], $params['section'])
        || !trim($content)
    ) {
        return $content;
    }

    list($entity, $tab, $section) = [$params['entity'], $params['tab'], $params['section']];

    static $entity_fields_schema = [];
    if (!isset($entity_fields_schema[$entity])) {
        $entity_fields_schema[$entity] = fn_get_schema($entity, 'page_configuration') ?: [];
    }

    if (!isset($entity_fields_schema[$entity][$tab]['sections'][$section])) {
        return $content;
    }

    $section_config = array_merge(
        [
            'is_optional' => false,
            'is_visible'  => true,
            'is_removed'  => false,
        ],
        $entity_fields_schema[$entity][$tab]['sections'][$section]
    );

    /**
     * Executes before configurable page section output, allows you to modify the section to remove it from page or hide it.
     *
     * @param string                         $entity         Page entity
     * @param string                         $tab            Tab on the page
     * @param string                         $section        Section in the tab
     * @param array<string, string|bool|int> $section_config Section configuration
     * @param array<string, string>          $params         Component parameters
     * @param string                         $content        Output section content
     * @param \Smarty_Internal_Template      $template       Template instance
     */
    fn_set_hook(
        'smarty_component_configurable_page_section_before_output',
        $entity,
        $tab,
        $section,
        $section_config,
        $params,
        $content,
        $template
    );

    if (!$section_config['is_optional']) {
        return $content;
    }

    if ($section_config['is_removed']) {
        return '';
    }

    if ($section_config['is_visible']) {
        return $content;
    }

    return '<div class="hidden">' . $content . '</div>';
}
