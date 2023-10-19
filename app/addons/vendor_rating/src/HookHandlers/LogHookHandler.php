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

namespace Tygh\Addons\VendorRating\HookHandlers;

use Tygh\Addons\VendorRating\Enum\Logging;

/**
 * Class LogHookHandler contains log-specific hook processors.
 *
 * @package Tygh\Addons\VendorRating\HookHandlers
 */
class LogHookHandler
{
    /**
     * The "save_log" hook handler.
     *
     * Actions performed:
     *     - Saves rating recalculation results.
     *
     * @see \fn_log_event()
     */
    public function onSave($type, $action, $data, $user_id, &$content, $event_type)
    {
        if ($type === Logging::LOG_TYPE_VENDOR_RATING) {
            /** @var \Tygh\Common\OperationResult $result */
            $result = $data['result'];

            $content = [
                'vendor_id'     => $result->getData('company_id'),
                'vendor' => $result->getData('company_name'),
            ];

            switch ($action) {
                case Logging::ACTION_SUCCESS:
                    $content['vendor_rating.previous_rating'] = $result->getData('previous_rating');
                    $content['vendor_rating.rating'] = $result->getData('rating');
                    break;
                case Logging::ACTION_FAILURE:
                    $errors = array_map('strip_tags', $result->getErrors());
                    $content['error'] = implode("\n", $errors);
                    break;
            }
        }

        return true;
    }
}
