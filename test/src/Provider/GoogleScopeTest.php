<?php

namespace League\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\Google as GoogleProvider;
use PHPUnit\Framework\TestCase;

class GoogleScopeTest extends TestCase
{
    public function testDefaultScopes(): void
    {
        $provider = new GoogleProvider([
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
        ]);

        $params = $this->getQueryParams($provider->getAuthorizationUrl());

        self::assertSame('openid email profile', $params['scope']);
    }

    public function testProviderScopes(): void
    {
        $provider = new GoogleProvider([
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
            'scopes' => [
                $yt = 'https://www.googleapis.com/auth/youtube.readonly',
            ],
        ]);

        $params = $this->getQueryParams($provider->getAuthorizationUrl());

        self::assertStringContainsString($yt, $params['scope']);
    }

    public function testOptionScopes(): void
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

        self::assertStringContainsString($yt, $params['scope']);
    }

    private function getQueryParams($url): array
    {
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        return $query;
    }
}
