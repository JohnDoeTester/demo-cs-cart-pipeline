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

namespace Tygh\Payments\Addons\Pingpp\Channels;

use Tygh\Enum\Addons\Pingpp\Channels;

class CpB2b implements IFormPayment
{
    /**
     * @var array $settings
     */
    protected $settings = array();

    /**
     * @var string $notification_url
     */
    protected $notification_url;

    /**
     * @var string $cancel_url
     */
    protected $cancel_url;

    /**
     * @var string $fail_url
     */
    protected $fail_url;

    /**
     * @var string $order_number
     */
    protected $order_number;

    /**
     * @var bool $new_version
     */
    protected $new_version = true;

    /**
     * @var array $extra
     */
    protected $extra = array();

    /** @inheritdoc */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /** @inheritdoc */
    public function getExtra()
    {
        return array();
    }

    /** @inheritdoc */
    public function setExtra($key, $value)
    {
        $this->extra[$key] = $value;
    }

    /** @inheritdoc */
    public function setNotificationUrl($url)
    {
        $this->notification_url = $url;
    }

    /** @inheritdoc */
    public function setFailUrl($url)
    {
        $this->fail_url = $url;
    }

    /** @inheritdoc */
    public function setCancelUrl($url)
    {
        $this->cancel_url = $url;
    }

    /** @inheritdoc */
    public function setOrderNumber($number)
    {
        $this->order_number = $number;
    }

    /** @inheritdoc */
    public function getFormUrl(array $charge)
    {
        return 'https://payment.chinapay.com/CTITS/service/rest/page/nref/000000000017/0/0/0/0/0';
    }

    /** @inheritdoc */
    public function getFormData(array $charge)
    {
        return $charge['credential'][Channels::CP_B2B];
    }

    /** @inheritdoc */
    public function getFormMethod(array $charge)
    {
        return 'get';
    }
}