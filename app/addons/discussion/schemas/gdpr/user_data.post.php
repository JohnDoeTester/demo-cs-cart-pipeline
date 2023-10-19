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

$schema['discussions_data'] = array(
    'collect_data_callback' => function ($params) {
        $discussions = array();

        if (isset($params['user_id'])) {
            list($discussions) = fn_get_discussions(array('user_id' => $params['user_id']));
        }

        return $discussions;
    },
    'update_data_callback' => function ($discussions) {
        if (is_array($discussions)) {
            $posts = array();

            foreach ($discussions as $discussion) {

                if (!empty($discussion['post_id'])) {
                    $posts[$discussion['post_id']] = $discussion;
                }
            }

            if ($posts) {
                fn_update_discussion_posts($posts);
            }
        }
    },
    'params'        => array(
        'fields_list' => array('name', 'ip_address'),
    ),
);

return $schema;
