<?php

namespace League\OAuth2\Client\Test\Provider;

use Eloquent\Phony\Phpunit\Phony;
use Exception;
use League\OAuth2\Client\Exception\HostedDomainException;
use League\OAuth2\Client\Provider\Google as GoogleProvider;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;

class HostedDomainCheckTest extends TestCase
{
    /**
     * Test combinations of hosted domain and user data that are valid
     *
     * @dataProvider validHostedDomainProvider
     *
     * @param array       $providerConfig
     * @param string      $json
     * @param string|null $expectedHostedDomain
     *
     * @throws Exception
     */
    public function testValidHostedDomains(
        array $providerConfig,
        string $json,
        ?string $expectedHostedDomain
    ): void {
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

        self::assertInstanceOf(ResourceOwnerInterface::class, $user);
        self::assertEquals($expectedHostedDomain, $user->getHostedDomain());
    }

    public function validHostedDomainProvider(): array
    {
        // Any domain or no domain is allowed if not specified
        $noHostedDomainConfig = [];
        // Any domain is allowed if set to * (but it must be set.)
        $wildCardHostedDomain = [['hostedDomain' => '*']];
        // Matching domain is allowed
        $hostedDomainConfig = [['hostedDomain' => 'example.com']];
        return [
            [ $noHostedDomainConfig, '{"email": "mock_email"}', null],
            [ $noHostedDomainConfig, '{"email": "mock_email", "hd": "anything.example"}', "anything.example"],
            [ $wildCardHostedDomain, '{"email": "mock_email", "hd": "anything.example"}', "anything.example"],
            [ $hostedDomainConfig, '{"email": "mock_email", "hd": "example.com"}', "example.com"],
        ];
    }

    /**
     * Test combinations of hosted domain and user data that are invalid
     *
     * @dataProvider invalidHostedDomainProvider
     *
     * @param array  $providerConfig
     * @param string $json
     */
    public function testInvalidHostedDomains(array $providerConfig, string $json): void
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

    public function invalidHostedDomainProvider(): array
    {
        // Wildcard requires a domain. No domain implies gmail
        $wildCardHostedDomain = [['hostedDomain' => '*']];
        // Matching domain is allowed
        $hostedDomainConfig = [['hostedDomain' => 'example.com']];
        return [
            // A domain is required for wild cards
            [ $wildCardHostedDomain, '{"email": "mock_email"}'],
            // A domain is required for specific domains
            [ $hostedDomainConfig, '{"email": "mock_email"}'],
            [ $hostedDomainConfig, '{"email": "mock_email", "hd": "wrong.example.com"}'],
        ];
    }

    private function mockAccessToken(): AccessToken
    {
        return new AccessToken([
            'access_token' => 'mock_access_token',
        ]);
    }
}
