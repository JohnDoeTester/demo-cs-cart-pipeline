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


namespace Tygh\Mailer;


use Pimple\Container;
use Tygh\Settings;

/**
 * The class responsible for creating the sender object.
 *
 * @package Tygh\Mailer
 */
class TransportFactory implements ITransportFactory, ICompanyTransportFactory
{
    /**
     * @var array<string, array<string, \Tygh\Mailer\ITransport>> Internal cach
     */
    protected $instances = [];

    /**
     * @var array<int, mixed> Company settings
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     */
    protected $company_settings = [];

    /**
     * @var \Pimple\Container
     */
    protected $container;

    /**
     * TransportFactory constructor.
     *
     * @param \Pimple\Container $container Dependency Injection Container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function createTransport($type, $settings)
    {
        $setting_hash = md5(serialize($settings));
        if (isset($this->instances[$type][$setting_hash])) {
            return $this->instances[$type][$setting_hash];
        }

        $key = 'mailer.transport.' . $type;

        if (isset($this->container[$key])) {
            $factory = $this->container[$key];
        } else {
            $factory = $this->container['mailer.transport.default'];
        }

        return $this->instances[$type][$setting_hash] = $factory($settings);
    }

    /**
     * @inheritdoc
     */
    public function createTransportByCompanyId($company_id)
    {
        if (!isset($this->company_settings[$company_id])) {
            $this->company_settings[$company_id] = Settings::instance($company_id)->getValues('Emails');
        }

        return $this->createTransport(
            isset($this->company_settings[$company_id]['mailer_send_method']) ? $this->company_settings[$company_id]['mailer_send_method'] : null,
            $this->company_settings[$company_id]
        );
    }
}
