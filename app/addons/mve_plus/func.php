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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Enables the `can_edit_blocks` and `can_edit_styles` settings if they exist in provided array
 *
 * @param array $settings Settings
 *
 * @return array
 */
function fn_mve_plus_hide_theme_and_styles_editing_settings($settings)
{
    if (isset($settings['main'])) {

        foreach ($settings['main'] as &$setting) {
            $setting_type_hidden = 'D';

            if (in_array($setting['name'], array('can_edit_blocks', 'can_edit_styles'))) {
                $setting['type'] = $setting_type_hidden;
            }
        }
        unset($setting);
    }

    return $settings;
}
