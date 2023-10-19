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

namespace Tygh\Addons\PaypalCommercePlatform;

use Exception;
use Tygh\Addons\PaypalCommercePlatform\Api\Client;
use Tygh\Common\OperationResult;
use Tygh\Http;

class OAuthHelper
{
    const FALLBACK_COUNTRY_CODE = '__default';

    /** @var \Tygh\Addons\PaypalCommercePlatform\Api\Client $api_requestor */
    protected $api_requestor;

    /** @var array<string, string> $config */
    protected $config;

    /** @var string $redirect_uri */
    protected $redirect_uri;

    /** @var int $company_id */
    protected $company_id;

    /** @var int $user_id */
    protected $user_id;

    /** @var string $locale */
    protected $locale;

    /** @var string $currency */
    protected $currency;

    /** @var array<string, string> */
    protected $country_products;

    /**
     * OAuthHelper constructor.
     *
     * @param \Tygh\Addons\PaypalCommercePlatform\Api\Client $api_client       API client
     * @param array<string, string>                          $config           Configuration
     * @param string                                         $redirect_uri     Auth redirect URI
     * @param int                                            $company_id       Current company ID
     * @param int                                            $user_id          Current user ID
     * @param string                                         $locale           Locale to use for sign-up
     * @param string                                         $currency         Currency code
     * @param array<string, string>                          $country_products Country product support map
     *
     * @psalm-param array{
     *   payer_id: string,
     * } $config
     */
    public function __construct(
        Client $api_client,
        array $config,
        $redirect_uri,
        $company_id,
        $user_id,
        $locale,
        $currency,
        array $country_products = []
    ) {
        $this->api_requestor = $api_client;
        $this->config = $config;
        $this->redirect_uri = $redirect_uri;
        $this->company_id = (int) $company_id;
        $this->user_id = (int) $user_id;
        $this->locale = $locale;
        $this->currency = $currency;
        $this->country_products = $country_products;
    }

    /**
     * Provides URL to initiate OAuth flow to connect vendor to the store.
     *
     * @return OperationResult
     */
    public function getAuthorizeUrl()
    {
        $referral_request = $this->buildReferralRequest();

        $result = new OperationResult();

        try {
            /**
             * @psalm-var array{
             *   links: array<
             *     int, array{
             *       rel: string,
             *       href: string
             *     }
             *   >
             * } $response
             */
            list($response,) = $this->api_requestor->signedRequest(
                '/v2/customer/partner-referrals',
                json_encode($referral_request)
            );
            $result->setSuccess(true);
            foreach ($response['links'] as $link_data) {
                if ($link_data['rel'] !== 'action_url') {
                    continue;
                }
                $result->setData($link_data['href']);
            }
        } catch (Exception $e) {
            $result->setSuccess(false);
            $result->setErrors([$e->getMessage()]);
        }

        return $result;
    }

    /**
     * Build merchant onboarding request body.
     *
     * @return array<string, string|array<string, string>>
     *
     * @psalm-return array{
     *   business_entity: array{
     *     addresses?: array{
     *       array{
     *         address_line_1?: string,
     *         admin_area_1?: string,
     *         admin_area_2?: string,
     *         country_code?: string,
     *         postal_code?: string,
     *         type: string
     *       }
     *     },
     *     emails: array{
     *       array{
     *         email: string,
     *         type: string
     *       }
     *     },
     *     names: array{
     *       array{
     *         business_name: string,
     *         type: string
     *       }
     *     },
     *     website?: string
     *   },
     *   email: string,
     *   individual_owners: array{
     *     array{
     *       names: array{
     *         array{
     *           given_name: string,
     *           surname: string,
     *           type: string
     *         }
     *       },
     *       type: string
     *     }
     *   },
     *   legal_consents: array{
     *     array{
     *       granted: true,
     *       type: string
     *     }
     *   },
     *   operations: array{
     *     array{
     *       api_integration_preference: array{
     *         rest_api_integration: array{
     *           integration_method: string,
     *           integration_type: string,
     *           third_party_details: array{
     *             features: array<string>
     *           }
     *         }
     *       },
     *       operation: string
     *     },
     *   },
     *   partner_config_override: array{
     *     action_renewal_url: string,
     *     partner_logo_url: string,
     *     return_url: string
     *   },
     *   preferred_language_code: string,
     *   products: array<string>,
     *   tracking_id: string
     * }
     */
    protected function buildReferralRequest()
    {
        $user_info = $this->getUserInfo();
        $placement_info = $this->getCompanyPlacementInfo();
        $partner_logo_url = $this->getMarketplaceLogoUrl();

        $address = array_filter([
            'address_line_1' => $placement_info['company_address'],
            'postal_code'    => $placement_info['company_zipcode'],
            'country_code'   => $placement_info['company_country'],
            'admin_area_2'   => $placement_info['company_city'],
            'admin_area_1'   => $placement_info['company_state'],
        ]);

        $website = $placement_info['company_website'];

        $product = $this->getSupportedProduct($address);
        $request = [
            'individual_owners'       => [
                [
                    'names' => [
                        [
                            'given_name' => $user_info['firstname'],
                            'surname'    => $user_info['lastname'],
                            'type'       => 'LEGAL',
                        ],
                    ],
                    'citizenship' => $placement_info['company_country'],
                    'type'  => 'PRIMARY',
                ],
            ],
            'business_entity'         => [
                'names'     => [
                    [
                        'business_name' => $placement_info['company_name'],
                        'type'          => 'LEGAL_NAME',
                    ],
                ],
                'emails'    => [
                    [
                        'type'  => 'CUSTOMER_SERVICE',
                        'email' => $placement_info['company_support_department'],
                    ],
                ],
            ],
            'email'                   => $user_info['email'],
            'preferred_language_code' => $this->locale,
            'tracking_id'             => $this->company_id . '-', // FIXME: PayPal requires Tracking ID to be a string
            'partner_config_override' => [
                'partner_logo_url'   => $partner_logo_url,
                'return_url'         => $this->redirect_uri,
                'action_renewal_url' => $this->redirect_uri,
            ],
            'operations'              => [
                [
                    'operation'                  => 'API_INTEGRATION',
                    'api_integration_preference' => [
                        'rest_api_integration' => [
                            'integration_method'  => 'PAYPAL',
                            'integration_type'    => 'THIRD_PARTY',
                            'third_party_details' => [
                                'features' => [
                                    'PAYMENT',
                                    'REFUND',
                                    'PARTNER_FEE',
                                    'DELAY_FUNDS_DISBURSEMENT',
                                    'ADVANCED_TRANSACTIONS_SEARCH',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'products'                => [
                $product,
            ],
            'legal_consents'          => [
                [
                    'type'    => 'SHARE_DATA_CONSENT',
                    'granted' => true,
                ],
            ],
        ];

        if ($address) {
            $address['type'] = 'WORK';
            $request['business_entity']['addresses'] = [$address];
        }

        if ($website) {
            $request['business_entity']['website'] = $website;
        }

        return $request;
    }

    /**
     * Provides store's logo URL to display in registration form.
     *
     * @return string URL
     */
    protected function getMarketplaceLogoUrl()
    {
        $logos = fn_get_logos(0);

        return $logos['theme']['image']['image_path'];
    }

    /**
     * Provides company placement information.
     *
     * @return array<string, string>
     */
    protected function getCompanyPlacementInfo()
    {
        return fn_get_company_placement_info($this->company_id);
    }

    /**
     * Provides authenticated user info.
     *
     * @return array<string, string>
     */
    protected function getUserInfo()
    {
        return fn_get_user_info($this->user_id);
    }

    /**
     * Obtains merchant info from PayPal.
     *
     * @param string $account_id Merchant ID in PayPal
     *
     * @return OperationResult Contains merchant info as data on success
     */
    public function getAccountInfo($account_id)
    {
        $result = new OperationResult();

        $url = sprintf(
            '/v1/customer/partners/%s/merchant-integrations/%s',
            $this->config['payer_id'],
            $account_id
        );

        $vendor = null;

        try {
            $vendor = $this->api_requestor->signedRequest(
                $url,
                '',
                [],
                Http::GET
            );

            $result->setSuccess(true);
            foreach ($vendor as $info) {
                if (isset($info['merchant_id'])) {
                    $result->setData($info);
                    break;
                }
            }
        } catch (Exception $e) {
            $result->addError((string) $e->getCode(), $e->getMessage());
        }

        return $result;
    }

    /**
     * Gets connected product by vendor's country.
     * Is used to connect vendor to the correct product depending country availability.
     *
     * @param array<string, string> $address Vendor's address
     *
     * @return string
     */
    private function getSupportedProduct(array $address)
    {
        $country_code = isset($address['country_code'])
            ? $address['country_code']
            : self::FALLBACK_COUNTRY_CODE;

        return isset($this->country_products[$country_code])
            ? $this->country_products[$country_code]
            : $this->country_products[self::FALLBACK_COUNTRY_CODE];
    }
}
