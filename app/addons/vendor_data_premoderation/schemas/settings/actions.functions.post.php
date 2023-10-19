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
 * Shows warning notification after add-on status changed
 *
 * @param string $new_value New values of vendor_data_premoderation setting
 * @param string $old_value Old values of vendor_data_premoderation setting
 */
function fn_settings_actions_addons_vendor_data_premoderation($new_value, $old_value)
{
    if ($new_value == 'D') {
        fn_vendor_data_premoderation_display_notification_for_deleted_statuses();
    }
}