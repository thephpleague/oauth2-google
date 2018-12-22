<?php

namespace League\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\Google as GoogleProvider;
use PHPUnit\Framework\TestCase;

class GoogleScopeTest extends TestCase
{
    public function testDefaultScopes()
    {
        $provider = new GoogleProvider([
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
        ]);

        $params = $this->getQueryParams($provider->getAuthorizationUrl());

        $this->assertSame('openid email profile', $params['scope']);
    }

    public function testProviderScopes()
    {
        $provider = new GoogleProvider([
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
            'scopes' => [
                $yt = 'https://www.googleapis.com/auth/youtube.readonly',
            ],
        ]);

        $params = $this->getQueryParams($provider->getAuthorizationUrl());

        $this->assertContains($yt, $params['scope']);
    }

    public function testOptionScopes()
    {
        $provider = new GoogleProvider([
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
        ]);

        $params = $this->getQueryParams($provider->getAuthorizationUrl([
            'scope' => [
                $yt = 'https://www.googleapis.com/auth/youtube.readonly',
            ],
        ]));

        $this->assertContains($yt, $params['scope']);
    }

    /**
     * @param string $url
     * @return array
     */
    private function getQueryParams($url)
    {
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        return $query;
    }
}
