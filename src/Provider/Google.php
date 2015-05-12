<?php

namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;

class Google extends AbstractProvider
{
    use BearerAuthorizationTrait;

    const SCOPE_SEPARATOR = ' ';
    const ACCESS_TOKEN_UID = 'id';

    /**
     * @var string If set, this will be sent to google as the "access_type" parameter.
     * @link https://developers.google.com/accounts/docs/OAuth2WebServer#offline
     */
    protected $accessType;

    /**
     * @var string If set, this will be sent to google as the "hd" parameter.
     * @link https://developers.google.com/accounts/docs/OAuth2Login#hd-param
     */
    protected $hostedDomain;

    public function urlAuthorize()
    {
        return 'https://accounts.google.com/o/oauth2/auth';
    }

    public function urlAccessToken()
    {
        return 'https://accounts.google.com/o/oauth2/token';
    }

    public function urlUserDetails(AccessToken $token)
    {
        return 'https://www.googleapis.com/plus/v1/people/me?' . $this->httpBuildQuery([
                'fields' => 'id,name(familyName,givenName),displayName,emails/value,image/url',
                'alt'    => 'json',
            ]);
    }

    public function getAuthorizationUrl(array $options = [])
    {
        $url = parent::getAuthorizationUrl($options);

        $params = array_filter([
            'hd'          => $this->hostedDomain,
            'access_type' => $this->accessType,
        ]);

        if ($params) {
            $url .= '&' . $this->httpBuildQuery($params);
        }

        return $url;
    }

    protected function getDefaultScopes()
    {
        return [
            'profile',
            'email',
        ];
    }

    protected function checkResponse(array $response)
    {
        if (!empty($response['error'])) {
            throw new IdentityProviderException($response['error'], 0, $response);
        }
    }

    protected function prepareUserDetails(array $response, AccessToken $token)
    {
        return new GoogleUser($response);
    }
}
