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

use Tygh\Enum\ObjectStatuses;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 * @phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
 *
 * @param array<string, mixed>      $params  Hook params
 * @param string                    $content Content
 * @param \Smarty_Internal_Template $smarty  Smarty instance
 *
 * @return string
 */
function smarty_block_hook(array $params, $content, Smarty_Internal_Template $smarty)
{
    static $overrides = [];
    static $addons = null;

    /** @var \Tygh\SmartyEngine\Core $global_smarty */
    $global_smarty = $smarty->smarty ? $smarty->smarty : $smarty;

    $hook_content = '';
    $hook_name = 'thooks_' . $global_smarty->template_area;

    Registry::registerCache($hook_name, ['addons'], Registry::cacheLevel(['static', 'storefront']));
    $hooks = Registry::ifGet($hook_name, []);

    if (!isset($hooks[$params['name']])) {
        list($dir, $name) = explode(':', $params['name']);

        if ($addons === null) {
            $addons = Registry::get('addons');
        }

        $hook_exists = false;
        $hooks_list = [
            'pre'      => [],
            'post'     => [],
            'override' => []
        ];

        foreach ($addons as $addon => $data) {
            if ($data['status'] === ObjectStatuses::DISABLED) {
                continue;
            }

            $files = [];

            if (!isset($addons[$addon]['has_extended_hooks'])) {
                $addons[$addon]['has_extended_hooks'] = $global_smarty->templateDirExists('addons/' . $addon . '/addons/');
            }

            if ($addons[$addon]['has_extended_hooks']) {
                foreach ($addons as $_addon => $_data) {
                    if ($_data['status'] === ObjectStatuses::DISABLED || $_addon === $addon) {
                        continue;
                    }

                    $files[] = 'addons/' . $addon . '/addons/' . $_addon . '/hooks/' . $dir . '/' . $name;
                }
            }

            $files[] = 'addons/' . $addon . '/hooks/' . $dir . '/' . $name;

            foreach ($files as $file) {
                $pre_file = $file . '.pre.tpl';
                $post_file = $file . '.post.tpl';
                $override_file = $file . '.override.tpl';

                if ($global_smarty->templateExists($pre_file)) {
                    $hooks_list['pre'][] = $pre_file;
                    $hook_exists = true;
                }
                if ($global_smarty->templateExists($post_file)) {
                    $hooks_list['post'][] = $post_file;
                    $hook_exists = true;
                }
                if ($global_smarty->templateExists($override_file)) {
                    $hooks_list['override'][$override_file] = $override_file;
                    $hook_exists = true;
                }
            }
        }

        if (!$hook_exists) {
            $hooks[$params['name']] = [];
        } else {
            $hooks[$params['name']] = $hooks_list;
        }

        Registry::set($hook_name, $hooks);
    }

    if ($content === null) {
        // reset override for current hook
        $overrides[$params['name']] = false;

        // override hook should be call for opened tag to prevent pre/post hook execution
        if (!empty($hooks[$params['name']]['override']) && !isset($hooks[$params['name']]['override'][$smarty->template_resource])) {
            foreach ($hooks[$params['name']]['override'] as $tpl) {
                $_hook_content = $smarty->fetch($tpl);

                if (trim($_hook_content)) {
                    $overrides[$params['name']] = true;
                    $hook_content = $_hook_content;
                }
            }
        }

        // prehook should be called for the opening {hook} tag to allow variables passed from hook to body
        if (empty($overrides[$params['name']]) && !empty($hooks[$params['name']]['pre'])) {
            foreach ($hooks[$params['name']]['pre'] as $tpl) {
                $hook_content .= $smarty->fetch($tpl);
            }
        }
    } elseif (empty($overrides[$params['name']])) {
        // post hook should be called only if override hook was no executed
        if (!empty($hooks[$params['name']]['post'])) {
            foreach ($hooks[$params['name']]['post'] as $tpl) {
                $hook_content .= $smarty->fetch($tpl);
            }
        }

        $hook_content =  $content . "\n" . $hook_content;
    }

    fn_set_hook('smarty_block_hook_post', $params, $content, $overrides, $smarty, $hook_content);

    return $hook_content;
}
