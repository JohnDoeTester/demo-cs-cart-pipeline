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

use Tygh\Mailer\Mailer;
use Tygh\Database\Connection;
use Tygh\Common\OperationResult;
use Illuminate\Support\Collection;
use Tygh\Exceptions\DatabaseException;
use Tygh\Exceptions\DeveloperException;

/**
 * Provides methods to send invitations to potential vendors.
 *
 * @package Tygh\Vendors\Invitations
 */
class Sender
{
    /** @var \Tygh\Database\Connection $db */
    protected $db;

    /** @var \Tygh\Mailer\Mailer $mailer */
    protected $mailer;

    /** @var \Tygh\Vendors\Invitations\Repository $repository */
    protected $repository;

    public function __construct(Connection $db, Repository $repository, Mailer $mailer)
    {
        $this->db = $db;
        $this->mailer = $mailer;
        $this->repository = $repository;
    }

    /**
     * Sends invitations
     *
     * @param array $invitations Invitations
     *
     * @return \Tygh\Common\OperationResult
     */
    public function send(array $invitations)
    {
        try {
            list($valid_emails, $invalid_emails) = $this->extractEmails($invitations);

            list($new_invitations, $existing_invitations) = $this->getNewAndExistingInvitations($valid_emails);
            list($sent_invitations, $failed_invitations) = $this->sendVendorInvitations($new_invitations);

            return $this->prepareResult(
                $sent_invitations,
                $failed_invitations->merge($existing_invitations->merge($this->prepareRawInvitations($invalid_emails)))
            );
        } catch (DatabaseException $e) {
            return new OperationResult(false);
        } catch (DeveloperException $e) {
            return new OperationResult(false);
        }
    }

    /**
     * Extracts emails list from invitations array
     *
     * @param array $invitations Invitations
     *
     * @return \Illuminate\Support\Collection[]
     */
    protected function extractEmails(array $invitations)
    {
        $raw_emails = (new Collection(explode("\n", $invitations['vendor_emails'])))
            ->map(function ($email) {
                return trim($email, "\r\t ");
            })->filter(function ($email) {
                return $email !== '';
            });

        $valid_emails = $raw_emails
            ->filter(function ($email) {
                return $this->isValidEmail($email);
            });

        $invalid_emails = $raw_emails->diff($valid_emails);

        return [$valid_emails, $invalid_emails];
    }

    /**
     * Checks if email is valid
     *
     * @param string $email Email
     *
     * @return bool
     */
    protected function isValidEmail($email)
    {
        return strpos($email, ',') === false && fn_validate_email($email);
    }

    /**
     * Splits raw invitations into new and existing
     *
     * @param \Illuminate\Support\Collection $emails Invitation emails
     *
     * @return \Illuminate\Support\Collection[]
     */
    protected function getNewAndExistingInvitations($emails)
    {
        $raw_invitations = $this->prepareRawInvitations($emails);

        $existing_invitations = $this->getExistingInvitations($raw_invitations);
        $existing_emails = $this->getExistingEmails($emails, $raw_invitations);
        $existing_invitations = $existing_invitations->merge($existing_emails);

        $new_invitations = $raw_invitations->diffKeys($existing_invitations);

        return [$new_invitations, $existing_invitations];
    }

    /**
     * Prepares raw invitations data
     *
     * @param \Illuminate\Support\Collection $emails Invitation emails
     *
     * @return \Illuminate\Support\Collection
     */
    protected function prepareRawInvitations(Collection $emails)
    {
        $raw_invitations = $emails->mapWithKeys(function ($email) {
            $invitation_key = $this->generateInvitationKey($email);

            return [
                $invitation_key => [
                    'invitation_key' => $invitation_key,
                    'email'          => $email,
                    'invited_at'     => TIME,
                ],
            ];
        });

        return $raw_invitations;
    }

    /**
     * Generates unique key from email
     *
     * @param string $email Vendor email
     *
     * @return string
     */
    protected function generateInvitationKey($email)
    {
        return md5($email);
    }

    /**
     * Fetches existing invitations
     *
     * @param \Illuminate\Support\Collection $raw_invitations Invitations
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getExistingInvitations(Collection $raw_invitations)
    {
        $existing_invitation_keys = array_keys(
            $this->repository->findInvitationsByKeys(
                array_column($raw_invitations->toArray(), 'invitation_key')
            )
        );
        $existing_invitations = $raw_invitations->filter(function ($invitation) use ($existing_invitation_keys) {
            return in_array($invitation['invitation_key'], $existing_invitation_keys);
        });

        return $existing_invitations;
    }

    /**
     * Fetches invitations that intersect with existing users or companies
     *
     * @param \Illuminate\Support\Collection $emails          Invitation emails
     * @param \Illuminate\Support\Collection $raw_invitations Invitations
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getExistingEmails(Collection $emails, Collection $raw_invitations)
    {
        if ($emails->isEmpty()) {
            return new Collection();
        }

        $companies_emails = $this->db->getHash('SELECT email FROM ?:companies WHERE email IN (?a)', 'email', $emails->toArray());
        $user_emails = $this->db->getHash('SELECT email FROM ?:users WHERE email IN (?a)', 'email', $emails->toArray());
        $existing_companies = $raw_invitations->filter(function ($invitation) use ($companies_emails, $user_emails) {
            $email = $invitation['email'];
            return isset($user_emails[$email]) || isset($companies_emails[$email]);
        });

        return $existing_companies;
    }

    /**
     * Sends vendor invitations
     *
     * @param \Illuminate\Support\Collection $invitations Invitations
     *
     * @return \Illuminate\Support\Collection[]
     * @throws \Tygh\Exceptions\DatabaseException
     * @throws \Tygh\Exceptions\DeveloperException
     */
    protected function sendVendorInvitations($invitations)
    {
        $sent_invitations = new Collection();
        $failed_invitations = new Collection();

        if ($invitations->count()) {
            foreach ($invitations as $key => $invitation) {
                $sent = $this->sendInvitation($invitation);
                if ($sent) {
                    $sent_invitations->offsetSet($key, $invitation);
                } else {
                    $failed_invitations->offsetSet($key, $invitation);
                }
            }
        }

        return [$sent_invitations, $failed_invitations];
    }

    /**
     * Sends invitation to vendor's email and saves to the database
     *
     * @param array $invitation Invitation data
     *
     * @return bool
     * @throws \Tygh\Exceptions\DatabaseException
     * @throws \Tygh\Exceptions\DeveloperException
     */
    protected function sendInvitation(array $invitation)
    {
        $added = $this->repository->add($invitation);
        if ($added) {
            $sent = $this->mailer->send([
                'to'            => $invitation['email'],
                'from'          => 'default_company_users_department',
                'data'          => [
                    'create_account_url' => fn_url('companies.apply_for_vendor?invitation_key=' . $invitation['invitation_key'], 'C'),
                ],
                'template_code' => 'vendor_invitation',
            ], 'A');
        }

        if (empty($sent)) {
            $this->repository->deleteByKey($invitation['invitation_key']);

            return false;
        }

        return true;
    }

    /**
     * Prepares sending invitations result
     *
     * @param \Illuminate\Support\Collection $sent_invitations   Sent invitations
     * @param \Illuminate\Support\Collection $failed_invitations Failed invitations
     *
     * @return \Tygh\Common\OperationResult
     */
    protected function prepareResult($sent_invitations, $failed_invitations)
    {
        $result = new OperationResult(true);

        if ($sent_invitations->count()) {
            $result->addMessage(
                'vendor_invitations_sent_notification',
                __('vendor_invitations_sent_notification', ['[sent_quantity]' => $sent_invitations->count()])
            );
        }

        if ($failed_invitations->count()) {
            $failed_emails = array_column($failed_invitations->toArray(), 'email');
            $result->addWarning(
                'vendor_invitations_fail_notification',
                __('vendor_invitations_fail_notification', [
                    '[emails_list]'     => implode('<br/>', $failed_emails),
                    '[failed_quantity]' => $failed_invitations->count(),
                    '[product]'         => PRODUCT_NAME,
                ])
            );
        }

        return $result;
    }
}
