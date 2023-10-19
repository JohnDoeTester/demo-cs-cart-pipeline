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

namespace Tygh\Helpdesk;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;

class AuthProvider extends GenericProvider
{
    const STATE_STORAGE_KEY = 'helpdesk_oauth_state';

    /**
     * @var array<string, string>|\ArrayAccess
     */
    private $state_storage;

    /**
     * @var array<string>
     */
    private $scopes = ['openid', 'userinfo:user_id'];

    /**
     * @var string
     */
    private $scope_separator = ' ';

    /**
     * AuthProvider constructor.
     *
     * @param string                             $client_id          OAuth client ID
     * @param string                             $client_secret      OAuth client secret
     * @param string                             $authorize_url      Authorize URL
     * @param string                             $access_token_url   Token URL
     * @param string                             $resource_owner_url Resource owner URL
     * @param array<string, string>|\ArrayAccess $state_storage      Authentication state storage
     * @param string                             $redirect_uri       OAuth redirect URL
     */
    public function __construct(
        $client_id,
        $client_secret,
        $authorize_url,
        $access_token_url,
        $resource_owner_url,
        $state_storage,
        $redirect_uri
    ) {
        parent::__construct(
            [
                'clientId'                => $client_id,
                'clientSecret'            => $client_secret,
                'redirectUri'             => $redirect_uri,
                'urlAuthorize'            => $authorize_url,
                'urlAccessToken'          => $access_token_url,
                'urlResourceOwnerDetails' => $resource_owner_url,
                'scopes'                  => $this->scopes,
                'scopeSeparator'          => $this->scope_separator,
            ]
        );

        $this->state_storage = $state_storage;
    }

    /**
     * Checks whether authentication process state is valid for the current process.
     *
     * @param string $state Process state
     *
     * @return bool
     */
    public function isValidAuthState($state)
    {
        return $this->getAuthState() === $state;
    }

    /**
     * Resets authentication process state.
     *
     * @return void
     */
    public function resetAuthState()
    {
        unset($this->state_storage[self::STATE_STORAGE_KEY]);
    }

    /**
     * Requests an access token.
     *
     * @param string $code Authorization code
     *
     * @return \League\OAuth2\Client\Token\AccessToken
     *
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException When things go wrong.
     *
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getAccessTokenByAuthorizationCode($code)
    {
        return $this->getAccessToken(
            'authorization_code',
            [
                'code' => $code,
            ]
        );
    }

    /**
     * Saves authentication process state.
     *
     * @return void
     */
    public function rememberAuthState()
    {
        $this->setAuthState($this->getState());
    }

    /** @inheritdoc */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GenericResourceOwner($response['userinfo'], 'user_id');
    }

    /**
     * Sets authentication process state.
     *
     * @param string $state Process state
     *
     * @return void
     */
    private function setAuthState($state)
    {
        $this->state_storage[self::STATE_STORAGE_KEY] = $state;
    }

    /**
     * Gets authentication process state.
     *
     * @return string
     */
    private function getAuthState()
    {
        return $this->state_storage[self::STATE_STORAGE_KEY];
    }
}
