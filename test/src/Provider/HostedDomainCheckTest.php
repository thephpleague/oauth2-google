<?php

namespace League\OAuth2\Client\Test\Provider;

use Eloquent\Phony\Phpunit\Phony;
use League\OAuth2\Client\Exception\HostedDomainException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google as GoogleProvider;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;

class HostedDomainCheckTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test combinations of hosted domain and user data that are valid
     *
     * @dataProvider validHostedDomainProvider
     * @param $providerConfig
     * @param $json
     * @param $expectedHostedDomain
     */
    public function testValidHostedDomains(array $providerConfig, $json, $expectedHostedDomain)
    {
        // Mock
        $response = json_decode($json, true);

        $token = $this->mockAccessToken();

        $provider = Phony::partialMock(GoogleProvider::class, $providerConfig);
        $provider->fetchResourceOwnerDetails->returns($response);
        $google = $provider->get();

        // Execute
        /** @var GoogleUser $user */
        $user = $google->getResourceOwner($token);

        // Verify
        Phony::inOrder(
            $provider->fetchResourceOwnerDetails->called()
        );

        $this->assertInstanceOf('League\OAuth2\Client\Provider\ResourceOwnerInterface', $user);
        $this->assertEquals($expectedHostedDomain, $user->getHostedDomain());

    }

    public function validHostedDomainProvider() {
        // Any domain or no domain is allowed if not specified
        $noHostedDomainConfig = [];
        // Any domain is allowed if set to * (but it must be set.)
        $wildCardHostedDomain = [['hostedDomain' => '*']];
        // Matching domain is allowed
        $hostedDomainConfig = [['hostedDomain' => 'example.com']];
        return [
            [ $noHostedDomainConfig, '{"email": "mock_email"}', null],
            [ $noHostedDomainConfig, '{"email": "mock_email", "hd": "anything.example"}', "anything.example"],
            [ $noHostedDomainConfig, '{"email": "mock_email", "domain": "anything.example"}', "anything.example"],
            [ $wildCardHostedDomain, '{"email": "mock_email", "hd": "anything.example"}', "anything.example"],
            [ $wildCardHostedDomain, '{"email": "mock_email", "domain": "anything.example"}', "anything.example"],
            [ $hostedDomainConfig, '{"email": "mock_email", "hd": "example.com"}', "example.com"],
            [ $hostedDomainConfig, '{"email": "mock_email", "domain": "example.com"}', "example.com"],

        ];
    }

    /**
     * Test combinations of hosted domain and user data that are invalid
     *
     * @dataProvider invalidHostedDomainProvider
     * @param $json
     */
    public function testInvalidHostedDomains(array $providerConfig, $json)
    {
        $this->expectException(HostedDomainException::class);
        // Mock
        $response = json_decode($json, true);

        $token = $this->mockAccessToken();

        $provider = Phony::partialMock(GoogleProvider::class, $providerConfig);
        $provider->fetchResourceOwnerDetails->returns($response);
        $google = $provider->get();

        // Execute
        $google->getResourceOwner($token);
    }

    public function invalidHostedDomainProvider() {
        // Wildcard requires a domain. No domain implies gmail
        $wildCardHostedDomain = [['hostedDomain' => '*']];
        // Matching domain is allowed
        $hostedDomainConfig = [['hostedDomain' => 'example.com']];
        return [
            // A domain is required for wild cards
            [ $wildCardHostedDomain, '{"email": "mock_email"}', null],
            // A domain is required for specific domains
            [ $hostedDomainConfig, '{"email": "mock_email"}', null],
            [ $hostedDomainConfig, '{"email": "mock_email", "hd": "wrong.example.com"}'],
            [ $hostedDomainConfig, '{"email": "mock_email", "domain": "example.co"}'],

        ];
    }

    /**
     * @return AccessToken
     */
    private function mockAccessToken()
    {
        return new AccessToken([
            'access_token' => 'mock_access_token',
        ]);
    }
}