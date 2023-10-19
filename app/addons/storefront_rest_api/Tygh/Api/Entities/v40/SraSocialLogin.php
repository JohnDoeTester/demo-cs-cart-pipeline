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

namespace Tygh\Api\Entities\v40;

use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Api\Response;
use Tygh\Addons\StorefrontRestApi\ASraEntity;
use Tygh\Tools\Url;

class SraSocialLogin extends ASraEntity
{

    /** @inheritdoc */
    public function index($id = '', $params = [])
    {
        $providers_list = fn_hybrid_auth_get_providers_list(true);
        $data = array_reduce($providers_list, function ($provider_links, $provider) {
            $provider_links[$provider['provider']] = $this->getProviderAuthUrl($provider);
            return $provider_links;
        }, []);

        return [
            'status' => Response::STATUS_OK,
            'data'   => $data,
        ];
    }

    /**
     * @inheritDoc
     */
    public function create($params)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }

    /**
     * @inheritDoc
     */
    public function update($id, $params)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }

    /** @inheritdoc */
    public function privilegesCustomer()
    {
        if (!static::isAddonEnabled()) {
            return [];
        }

        return [
            'index'  => true,
            'create' => false,
            'update' => false,
            'delete' => false,
        ];
    }

    /**
     * Checks whether the add-on enabled.
     *
     * @return bool
     */
    public static function isAddonEnabled()
    {
        return Registry::ifGet('addons.hybrid_auth.status', ObjectStatuses::DISABLED) === ObjectStatuses::ACTIVE;
    }

    /**
     * Gets redirect url.
     *
     * @param array<string, string|int> $provider Provider data
     *
     * @return string
     */
    private function getProviderAuthUrl(array $provider)
    {
        $provider_id = $provider['provider_id'];
        $redirect_url = 'index.php?' . http_build_query(['dispatch' => 'index.index', 'mobile_auth' => YesNo::YES]);

        $url = Url::buildUrn(['auth', 'login_provider'], [
            'provider_id' => $provider_id,
            'redirect_url' => $redirect_url
        ]);

        return fn_url($url);
    }
}
