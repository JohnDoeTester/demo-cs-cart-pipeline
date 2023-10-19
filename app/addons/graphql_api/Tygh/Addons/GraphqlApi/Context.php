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

namespace Tygh\Addons\GraphqlApi;

use Tygh\Application;

// phpcs:disable

class Context
{
    /**
     * @var \Tygh\Application
     */
    protected $app;

    /**
     * @var array
     */
    protected $auth;

    /**
     * @var string
     */
    protected $lang_code;

    /**
     * @var string
     */
    protected $currency;

    public function __construct(Application $app, array $auth, string $lang_code, string $currency)
    {
        $this->app = $app;
        $this->auth = $auth;
        $this->lang_code = $lang_code;
        $this->currency = $currency;
    }

    public function getApp(): Application
    {
        return $this->app;
    }

    public function getLanguageCode(): string
    {
        return $this->lang_code;
    }

    public function getCompanyId(): int
    {
        return (int) $this->auth['company_id'];
    }

    public function getUserId(): int
    {
        return (int) $this->auth['user_id'];
    }

    public function getUserType(): string
    {
        return $this->auth['user_type'];
    }

    public function getCurrencyCode(): string
    {
        return $this->currency;
    }

    public function getAuth(): array
    {
        return $this->auth;
    }
}

