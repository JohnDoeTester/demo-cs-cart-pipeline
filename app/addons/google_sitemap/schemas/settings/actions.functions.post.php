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

use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Warns admin about missing sitemap.
 *
 * @param string $new_value New add-on status
 * @param string $old_value Old add-on status
 *
 * @return bool
 */
function fn_settings_actions_addons_google_sitemap(&$new_value, $old_value)
{
    if ($new_value === ObjectStatuses::ACTIVE) {
        fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('google_sitemap.generate_map'));
    }

    return true;
}
