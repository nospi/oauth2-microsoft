<?php namespace nospi\OAuth2\Client\Provider;

use GuzzleHttp\Psr7\Uri;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Microsoft extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * No access token type
     *
     * @var string
     */
    const ACCESS_TOKEN_TYPE_NONE = '';

    /**
     * Access token type 'Bearer'
     *
     * @var string
     */
    const ACCESS_TOKEN_TYPE_BEARER = 'Bearer';

    /**
     * Default scopes
     *
     * @var array
     */
    public $defaultScopes = ['User.Read'];

    /**
     * Base url for authorization.
     *
     * @var string
     */
    protected $urlAuthorize = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';

    /**
     * Base url for access token.
     *
     * @var string
     */
    protected $urlAccessToken = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

    /**
     * Base url for resource owner.
     *
     * @var string
     */
    protected $urlResourceOwnerDetails = 'https://graph.microsoft.com/v1.0/me';

    /**
     * The access token type to use. Defaults to none.
     *
     * @var string
     */
    protected $accessTokenType = self::ACCESS_TOKEN_TYPE_NONE;

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->urlAuthorize;
    }

    /**
     * Get access token url to retrieve token
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->urlAccessToken;
    }

    /**
     * Sets the access token type used for authorization.
     *
     * @param string The access token type to use.
     */
    public function setAccessTokenType($accessTokenType)
    {
        $this->accessTokenType = $accessTokenType;
    }

    /**
     * Get default scopes
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return $this->defaultScopes;
    }

    /**
     * Check a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['error'])) {
            throw new IdentityProviderException(
                (isset($data['error']['message']) ? $data['error']['message'] : $response->getReasonPhrase()),
                $response->getStatusCode(),
                $response
            );
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response
     * @param AccessToken $token
     * @return MicrosoftResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new MicrosoftResourceOwner($response);
    }

    /**
     * Get provider url to fetch user details
     *
     * @param  AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        $uri = new Uri($this->urlResourceOwnerDetails);

        return (string) $uri;
    }

    /**
     * Returns the string that should be used to separate scopes when building
     * the URL for requesting an access token.
     *
     * @return string Scope separator, defaults to ' '
     */
    public function getScopeSeparator()
    {
        return ' ';
    }

    /**
     * Returns the authorization headers used by this provider.
     *
     * @param  mixed|null $token Either a string or an access token instance
     * @return array
     */
    protected function getAuthorizationHeaders($token = null)
    {
        switch ($this->accessTokenType) {
            case self::ACCESS_TOKEN_TYPE_BEARER:
                return ['Authorization' => 'Bearer ' .  $token];
            case self::ACCESS_TOKEN_TYPE_NONE:
            default:
                return [];
        }
    }

    /**
     * Requests resource owner details.
     *
     * @param  AccessToken $token
     * @return mixed
     */
    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        $url = $this->getResourceOwnerDetailsUrl($token);

        $this->setAccessTokenType(self::ACCESS_TOKEN_TYPE_BEARER);

        $request = $this->getAuthenticatedRequest(self::METHOD_GET, $url, $token);

        $response = $this->getParsedResponse($request);

        if (false === is_array($response)) {
          throw new UnexpectedValueException(
            'Invalid response received from Authorization Server. Expected JSON.'
          );
        }

        return $response;
    }
}