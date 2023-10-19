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

use Tygh\Enum\Addons\Discussion\DiscussionObjectTypes;
use Tygh\Enum\Addons\Discussion\DiscussionTypes;

$schema['discussion.update'] = [
    'from' => [
        'dispatch'        => 'discussion.update',
        'discussion_type' => DiscussionObjectTypes::TESTIMONIALS_AND_LAYOUT
    ],
    'to_customer' => function () {
        $thread_id = fn_get_discussion(0, DiscussionObjectTypes::TESTIMONIALS_AND_LAYOUT);

        if (!empty($thread_id['thread_id']) && ($thread_id['type'] != DiscussionTypes::TYPE_DISABLED)){
            return [
                'dispatch' => 'discussion.view',
                'thread_id' => $thread_id['thread_id']
            ];
        } else {
            return false;
        }
    }
];

return $schema;