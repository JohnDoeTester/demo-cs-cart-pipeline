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
 * Includes template with ability to pass parameters as array.
 * Does not capture variables from global/parent scope unless passed explicitly.
 *
 * @param array<string, mixed>      $params   Include parameters
 * @param \Smarty_Internal_Template $template Including template
 *
 * @return string Compiled template
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
function smarty_function_include_ext(array $params, Smarty_Internal_Template $template)
{
    /** @see Smarty::createTemplate() $tpl */
    $tpl = $template->createTemplate($params['file'], null, null, null, false);
    $tpl->parent = null;
    unset($params['file']);

    $tpl->assign($params['params_array']);
    unset($params['params_array']);

    if (!empty($params)) {
        $tpl->assign($params);
    }

    $tpl->assign([
        'ldelim' => $template->smarty->left_delimiter,
        'rdelim' => $template->smarty->right_delimiter,
    ]);

    $content = $tpl->fetch();

    if (!empty($params['assign'])) {
        $template->assign($params['assign'], $content);

        return '';
    }

    return $content;
}
