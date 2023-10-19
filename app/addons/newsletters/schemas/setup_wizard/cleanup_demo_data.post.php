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
 * @var array<string, array> $schema
 */
$schema['newsletters'] = static function () {
    $ids = db_get_fields('SELECT newsletter_id FROM ?:newsletters');
    foreach ($ids as $id) {
        fn_delete_newsletter($id);
    }

    $ids = db_get_fields('SELECT list_id FROM ?:mailing_lists');
    if (empty($ids)) {
        return;
    }
    fn_newsletters_delete_mailing_lists($ids);
};

return $schema;
