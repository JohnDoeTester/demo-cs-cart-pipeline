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

namespace Tygh\Notifications\Transports\Mail;

use Tygh\Exceptions\DeveloperException;
use Tygh\Mailer\Mailer;
use Tygh\Notifications\Transports\BaseMessageSchema;
use Tygh\Notifications\Transports\ITransport;

/**
 * Class MailTransport implements a transport that send emails based on an event message.
 *
 * @package Tygh\Events\Transports
 */
class MailTransport implements ITransport
{
    /**
     * @var \Tygh\Mailer\Mailer
     */
    protected $mailer;

    /**
     * @var \Tygh\Notifications\Transports\Mail\ReceiverFinderFactory
     */
    protected $receiver_finder_factory;

    /**
     * @var int|null
     */
    protected $runtime_company_id;

    /**
     * MailTransport constructor.
     *
     * @param \Tygh\Mailer\Mailer                                       $mailer                  Mailer instance
     * @param \Tygh\Notifications\Transports\Mail\ReceiverFinderFactory $receiver_finder_factory Factory to build receiver finders
     * @param int|null                                                  $runtime_company_id      Runtime company ID
     */
    public function __construct(Mailer $mailer, ReceiverFinderFactory $receiver_finder_factory, $runtime_company_id = null)
    {
        $this->mailer = $mailer;
        $this->receiver_finder_factory = $receiver_finder_factory;
        $this->runtime_company_id = $runtime_company_id;
    }

    /**
     * @inheritDoc
     */
    public static function getId()
    {
        return 'mail';
    }

    /**
     * @inheritDoc
     */
    public function process(BaseMessageSchema $schema, array $receiver_search_conditions)
    {
        if (!$schema instanceof MailMessageSchema) {
            throw new DeveloperException('Input data should be instance of MailMessageSchema');
        }

        $receivers = $this->getReceivers($receiver_search_conditions, $schema);
        $message_context_company_id = $schema->company_id === null
            ? $this->runtime_company_id
            : $schema->company_id;

        return fn_execute_as_company(
            function () use ($schema, $receivers) {
                return $this->mailer->send(
                    [
                        'to'            => $receivers,
                        'from'          => $schema->from,
                        'reply_to'      => $schema->reply_to,
                        'data'          => $schema->data,
                        'template_code' => $schema->template_code,
                        'tpl'           => $schema->legacy_template,
                        'company_id'    => $schema->company_id,
                        'attachments'   => $schema->attachments,
                        'storefront_id' => $schema->storefront_id,
                    ],
                    $schema->area,
                    $schema->language_code
                );
            },
            $message_context_company_id
        );
    }

    /**
     * Gets message receivers.
     *
     * @param \Tygh\Notifications\Receivers\SearchCondition[]       $receiver_search_conditions Receiver search conditions
     * @param \Tygh\Notifications\Transports\Mail\MailMessageSchema $schema                     Event message schema
     *
     * @return string[]
     */
    protected function getReceivers(array $receiver_search_conditions, MailMessageSchema $schema)
    {
        $emails = [];

        foreach ($receiver_search_conditions as $condition) {
            $finder = $this->receiver_finder_factory->get($condition->getMethod());
            $emails = array_merge($emails, $finder->find($condition->getCriterion(), $schema));
        }

        $emails = array_unique($emails);

        if (!$emails) {
            $emails = (array) $schema->to;
        }

        return array_filter($emails, static function ($email) {
            return !fn_checkout_is_email_address_fake($email);
        });
    }
}
