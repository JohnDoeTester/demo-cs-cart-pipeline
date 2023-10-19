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
 * Clears invalid values entered and sets the default return period
 *
 * @param string $new_value New return period
 * @param string $old_value Old return period
 *
 * @return void
 *
 * @param-out string|int $new_value
 */
function fn_settings_actions_addons_rma_return_period(&$new_value, $old_value)
{
    if (is_numeric($new_value)) {
        $new_value = (int) $new_value;
    } else {
        $new_value = $old_value;
    }
}
