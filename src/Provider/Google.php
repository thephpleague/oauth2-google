<?php

namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Exception\HostedDomainException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Google extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var string If set, this will be sent to google as the "access_type" parameter.
     * @link https://developers.google.com/identity/protocols/OpenIDConnect#authenticationuriparameters
     */
    protected $accessType;

    /**
     * @var string If set, this will be sent to google as the "hd" parameter.
     *             Extra-feature: Also accept a comma-separated list of domains or domain regular expressions.
     *             In that case, Google connection screen is not bound to a specific hosted domain
      *            because no "hd" parameter is sent to Google, but domain matching is still done by this library.
     * @link https://developers.google.com/identity/protocols/OpenIDConnect#authenticationuriparameters
     */
    protected $hostedDomain;

    /**
     * @var string If set, this will be sent to google as the "prompt" parameter.
     * @link https://developers.google.com/identity/protocols/OpenIDConnect#authenticationuriparameters
     */
    protected $prompt;

    /**
     * @var array List of scopes that will be used for authentication.
     * @link https://developers.google.com/identity/protocols/googlescopes
     */
    protected $scopes = [];

    public function getBaseAuthorizationUrl()
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://www.googleapis.com/oauth2/v4/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://openidconnect.googleapis.com/v1/userinfo';
    }

    protected function getAuthorizationParameters(array $options)
    {
        if (empty($options['hd']) && $this->hostedDomain && !$this->isHostedDomainMultiple()) {
            $options['hd'] = $this->hostedDomain;
        }

        if (empty($options['access_type']) && $this->accessType) {
            $options['access_type'] = $this->accessType;
        }

        if (empty($options['prompt']) && $this->prompt) {
            $options['prompt'] = $this->prompt;
        }

        // The "approval_prompt" option MUST be removed to prevent conflicts with non-empty "prompt".
        if (!empty($options['prompt'])) {
            $options['approval_prompt'] = null;
        }

        // Default scopes MUST be included for OpenID Connect.
        // Additional scopes MAY be added by constructor or option.
        $scopes = array_merge($this->getDefaultScopes(), $this->scopes);

        if (!empty($options['scope'])) {
            $scopes = array_merge($scopes, $options['scope']);
        }

        $options['scope'] = array_unique($scopes);

        return parent::getAuthorizationParameters($options);
    }

    protected function getDefaultScopes()
    {
        // "openid" MUST be the first scope in the list.
        return [
            'openid',
            'email',
            'profile',
        ];
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        // @codeCoverageIgnoreStart
        if (empty($data['error'])) {
            return;
        }
        // @codeCoverageIgnoreEnd

        $code = 0;
        $error = $data['error'];

        if (is_array($error)) {
            $code = $error['code'];
            $error = $error['message'];
        }

        throw new IdentityProviderException($error, $code, $data);
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        $user = new GoogleUser($response);

        $this->assertMatchingDomains($user->getHostedDomain());

        return $user;
    }

    protected function isDomainExpression($str)
    {
        return preg_match('/[\(\|\*]/', $str) && !preg_match('!/!', $str);
    }

    protected function isHostedDomainMultiple()
    {
        return strpos($this->hostedDomain, ',') !== FALSE || $this->isDomainExpression($this->hostedDomain);
    }

    /**
     * @throws HostedDomainException If the domain does not match the configured domain.
     */
    protected function assertMatchingDomains($hostedDomain)
    {
        if ($this->hostedDomain === null) {
            // No hosted domain configured.
            return;
        }

        $domains = array_filter(explode(',', $this->hostedDomain));
        if (empty($domains)) {
            // No hosted domains configured.
            return;
        }

        foreach ($domains as $whiteListedDomain) {
            if ($this->assertMatchingDomain($whiteListedDomain, $hostedDomain)) {
                return;
            }
        }

        throw HostedDomainException::notMatchingDomain($this->hostedDomain);
    }

    /**
     * @return bool Whether user-originating domain equals or matches $reference.
     */
    protected function assertMatchingDomain($reference, $hostedDomain)
    {
        if ($reference === '*' && $hostedDomain) {
            // Any hosted domain is allowed.
            return true;
        }

        if ($reference === $hostedDomain) {
            // Hosted domain is correct.
            return true;
        }

        if ($this->isDomainExpression($reference) && @preg_match('/' . $reference . '/', $hostedDomain)) {
            // Hosted domain is correct.
            return true;
        }
    }
}
