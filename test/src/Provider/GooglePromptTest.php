<?php

namespace League\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\Google as GoogleProvider;
use PHPUnit\Framework\TestCase;

class GooglePromptTest extends TestCase
{
    public function testDefaultParameters(): void
    {
        $provider = new GoogleProvider([
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
        ]);

        $params = $this->getQueryParams($provider->getAuthorizationUrl());

        self::assertArrayNotHasKey('approval_prompt', $params);
    }

    public function testPromptParameters(): void
    {
        $provider = new GoogleProvider([
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
            'prompt' => 'consent',
        ]);

        $params = $this->getQueryParams($provider->getAuthorizationUrl());

        self::assertArrayNotHasKey('approval_prompt', $params);
    }

    private function getQueryParams(string $url): array
    {
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        return $query;
    }
}
