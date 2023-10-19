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
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Shows warning notification after add-on status changed
 *
 * @param string $new_value New values of pdf_documents setting
 *
 * @return void
 */
function fn_settings_actions_addons_pdf_documents($new_value)
{
    if ($new_value === ObjectStatuses::ACTIVE) {
        fn_set_notification(
            NotificationSeverity::WARNING,
            __('warning'),
            __(
                'pdf_documents.activate_notification',
                [
                    '[service_url]' => Registry::get('addons.pdf_documents.service_url'),
                ]
            ),
            '',
            'pdf_documents_activated'
        );
    } else {
        fn_set_notification(
            NotificationSeverity::WARNING,
            __('warning'),
            __(
                'pdf_documents.disable_notification',
                [
                    '[service_url]'  => Registry::get('addons.pdf_documents.service_url'),
                    '[helpdesk_url]' => Registry::get('config.resources.helpdesk_url'),
                ]
            ),
            '',
            'pdf_documents_disabled'
        );
    }
}
