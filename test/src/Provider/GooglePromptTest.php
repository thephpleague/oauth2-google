<?php

namespace League\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\Google as GoogleProvider;
use PHPUnit\Framework\TestCase;

class GooglePromptTest extends TestCase
{
    public function testDefaultParameters()
    {
        $provider = new GoogleProvider([
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
        ]);

        $params = $this->getQueryParams($provider->getAuthorizationUrl());

        $this->assertArrayNotHasKey('approval_prompt', $params);
    }

    public function testPromptParameters()
    {
        $provider = new GoogleProvider([
            'clientId' => 'client-id',
            'clientSecret' => 'client-secret',
            'prompt' => 'consent',
        ]);

        $params = $this->getQueryParams($provider->getAuthorizationUrl());

        $this->assertArrayNotHasKey('approval_prompt', $params);
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
