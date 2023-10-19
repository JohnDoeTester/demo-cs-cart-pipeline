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

namespace Tygh\Vendors\Invitations;

use Tygh\Database\Connection;
use Tygh\Navigation\LastView;

/**
 * Provides methods to access storage of the vendor invitations.
 *
 * @package Tygh\Vendors\Invitations
 */
class Repository
{
    /** @var \Tygh\Database\Connection $db */
    protected $db;

    /**
     * Repository constructor.
     *
     * @param \Tygh\Database\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Add invitation
     *
     * @param array $invitation Invitation data
     *
     * @return bool|int
     * @throws \Tygh\Exceptions\DatabaseException
     * @throws \Tygh\Exceptions\DeveloperException
     */
    public function add(array $invitation)
    {
        return $this->db->query('INSERT INTO ?:vendor_invitations ?e', $invitation);
    }

    /**
     * Deletes vendor invitations by key(s) provided
     *
     * @param string[]|string $keys
     *
     * @return bool|int
     * @throws \Tygh\Exceptions\DatabaseException
     * @throws \Tygh\Exceptions\DeveloperException
     */
    public function deleteByKey($keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        if (empty($keys)) {
            return false;
        }

        return $this->db->query('DELETE FROM ?:vendor_invitations WHERE invitation_key IN (?a)', $keys);
    }

    /**
     * Deletes vendor invitations by email(s) provided
     *
     * @param string[]|string $emails
     *
     * @return bool|int
     * @throws \Tygh\Exceptions\DatabaseException
     * @throws \Tygh\Exceptions\DeveloperException
     */
    public function deleteByEmail($emails)
    {
        if (!is_array($emails)) {
            $emails = [$emails];
        }

        if (empty($emails)) {
            return false;
        }

        return $this->db->query('DELETE FROM ?:vendor_invitations WHERE email IN (?a)', $emails);
    }

    /**
     * Finds invitation by key
     *
     * @param string $key
     *
     * @return array
     */
    public function findInvitationByKey($key)
    {
        $result = $this->findInvitationsByKeys([$key]);
        return $result ? reset($result) : null;
    }

    /**
     * Finds invitations by keys
     *
     * @param string[] $keys
     *
     * @return array
     */
    public function findInvitationsByKeys(array $keys)
    {
        $keys = array_filter($keys);
        if (!$keys) {
            return [];
        }

        $invitations = $this->db->getHash(
            'SELECT * FROM ?:vendor_invitations WHERE invitation_key IN (?a)',
            'invitation_key',
            $keys
        );

        return $invitations;
    }

    /**
     * Fetches invitations list with pagination
     *
     * @param array $params
     * @param int   $items_per_page
     *
     * @return array
     */
    public function getListWithPagination($params, $items_per_page = 0)
    {
        $default_params = [
            'page'           => 1,
            'items_per_page' => $items_per_page,
        ];
        $params = array_merge($default_params, $params);

        $sortings = [
            'email'      => 'email',
            'invited_at' => 'invited_at',
        ];

        $sorting = db_sort($params, $sortings, 'invited_at', 'desc');
        $limit = '';

        if (!empty($params['items_per_page'])) {
            $params['total_items'] = $this->db->getField('SELECT COUNT(invitation_key) FROM ?:vendor_invitations');
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }

        $invitations = $this->db->getArray('SELECT * FROM ?:vendor_invitations ?p ?p', $sorting, $limit);
        return [$invitations, $params];
    }
}
