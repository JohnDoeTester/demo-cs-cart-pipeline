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
 * @psalm-var array $schema
 */
$schema['agreements'] = [
    'params'                => [
        'fields_list' => ['email'],
    ],
    'collect_data_callback' => static function ($params) {
        if (empty($params['user_id'])) {
            return [];
        }

        $conditions = db_quote('user_id = ?i', $params['user_id']);

        if (!empty($params['email'])) {
            $conditions .= db_quote(' OR email = ?s', $params['email']);
        }

        return db_get_array(
            'SELECT agreement_id, email FROM ?:gdpr_user_agreements WHERE ?p',
            $conditions
        );
    },
    'update_data_callback'  => static function ($agreements) {
        if (empty($agreements)) {
            return;
        }

        $agreement_ids = array_column($agreements, 'agreement_id');
        $first_agreement = reset($agreements);
        $email = isset($first_agreement['email'])
            ? $first_agreement['email']
            : '';

        if (!$email || !$agreement_ids) {
            return;
        }

        db_query(
            'UPDATE ?:gdpr_user_agreements SET ?u WHERE agreement_id IN (?n)',
            ['email' => $email],
            $agreement_ids
        );
    },
];

return $schema;
