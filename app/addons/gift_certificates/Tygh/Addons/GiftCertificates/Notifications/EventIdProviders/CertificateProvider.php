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

namespace Tygh\Addons\GiftCertificates\Notifications\EventIdProviders;

use Tygh\Notifications\EventIdProviders\IProvider;

/**
 * Class CertificateProvider provides means to distinguish gift certificate-based notification event.
 *
 * @package Tygh\Addons\GiftCertificates\Notifications\EventIdProviders
 */
class CertificateProvider implements IProvider
{
    /**
     * @var string
     */
    protected $prefix = 'gift_certificate.';

    /**
     * @var string
     */
    protected $id;

    public function __construct(array $gift_cert_data)
    {
        $this->id = $this->prefix . $gift_cert_data['gift_cert_id'];
    }

    /** @inheritDoc */
    public function getId()
    {
        return $this->id;
    }
}
