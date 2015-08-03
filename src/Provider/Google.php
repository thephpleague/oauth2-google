<?php

namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Google extends AbstractProvider
{
    use BearerAuthorizationTrait;

    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'id';

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

    public function getBaseAuthorizationUrl()
    {
        return 'https://accounts.google.com/o/oauth2/auth';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://accounts.google.com/o/oauth2/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://www.googleapis.com/plus/v1/people/me?' . http_build_query([
                'fields' => 'id,name(familyName,givenName),displayName,emails/value,image/url',
                'alt'    => 'json',
            ]);
    }

    protected function getAuthorizationParameters(array $options)
    {
        $params = array_merge(
            parent::getAuthorizationParameters($options),
            array_filter([
                'hd'          => $this->hostedDomain,
                'access_type' => $this->accessType,
            ])
        );

        return $params;
    }

    protected function getDefaultScopes()
    {
        return [
            'profile',
            'email',
        ];
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            throw new IdentityProviderException($data['error'], 0, $response);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GoogleResourceOwner($response);
    }
}
