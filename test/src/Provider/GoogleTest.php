<?php

namespace League\OAuth2\Client\Test\Provider;

use Eloquent\Phony\Phpunit\Phony;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google as GoogleProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;

class GoogleTest extends TestCase
{
    /** @var GoogleProvider */
    protected $provider;

    protected function setUp(): void
    {
        $this->provider = new GoogleProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'hostedDomain' => 'mock_domain',
            'accessType' => 'mock_access_type',
            'prompt' => 'select_account',
        ]);
    }

    public function testAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        self::assertArrayHasKey('client_id', $query);
        self::assertArrayHasKey('redirect_uri', $query);
        self::assertArrayHasKey('state', $query);
        self::assertArrayHasKey('scope', $query);
        self::assertArrayHasKey('response_type', $query);
        self::assertArrayHasKey('hd', $query);
        self::assertArrayHasKey('access_type', $query);
        self::assertArrayHasKey('prompt', $query);

        self::assertEquals('mock_access_type', $query['access_type']);
        self::assertEquals('mock_domain', $query['hd']);
        self::assertEquals('select_account', $query['prompt']);

        self::assertStringContainsString('email', $query['scope']);
        self::assertStringContainsString('profile', $query['scope']);
        self::assertStringContainsString('openid', $query['scope']);

        self::assertNotEmpty($this->provider->getState());
    }

    public function testBaseAccessTokenUrl(): void
    {
        $url = $this->provider->getBaseAccessTokenUrl([]);
        $uri = parse_url($url);

        self::assertEquals('/token', $uri['path']);
    }

    /**
     * @link https://accounts.google.com/.well-known/openid-configuration
     */
    public function testResourceOwnerDetailsUrl(): void
    {
        $token = $this->mockAccessToken();

        $url = $this->provider->getResourceOwnerDetailsUrl($token);

        self::assertEquals('https://openidconnect.googleapis.com/v1/userinfo', $url);
    }

    public function testUserData(): void
    {
        // Mock
        $response = [
            'sub' => '12345',
            'email' => 'mock.name@example.com',
            'name' => 'mock name',
            'given_name' => 'mock',
            'family_name' => 'name',
            'picture' => 'mock_image_url',
            'hd' => 'example.com',
        ];

        $token = $this->mockAccessToken();

        $provider = Phony::partialMock(GoogleProvider::class);
        $provider->fetchResourceOwnerDetails->returns($response);
        $google = $provider->get();

        // Execute
        $user = $google->getResourceOwner($token);

        // Verify
        Phony::inOrder(
            $provider->fetchResourceOwnerDetails->called()
        );

        self::assertInstanceOf(ResourceOwnerInterface::class, $user);

        self::assertEquals(12345, $user->getId());
        self::assertEquals('mock name', $user->getName());
        self::assertEquals('mock', $user->getFirstName());
        self::assertEquals('name', $user->getLastName());
        self::assertEquals('mock.name@example.com', $user->getEmail());
        self::assertEquals('example.com', $user->getHostedDomain());
        self::assertEquals('mock_image_url', $user->getAvatar());

        $user = $user->toArray();

        self::assertArrayHasKey('sub', $user);
        self::assertArrayHasKey('name', $user);
        self::assertArrayHasKey('email', $user);
        self::assertArrayHasKey('hd', $user);
        self::assertArrayHasKey('picture', $user);
        self::assertArrayHasKey('family_name', $user);
    }

    public function testErrorResponse(): void
    {
        // Mock
        $error_json = '{"error": {"code": 400, "message": "I am an error"}}';

        $stream = Phony::mock('GuzzleHttp\Psr7\Stream');
        $stream->__toString->returns($error_json);

        $response = Phony::mock('GuzzleHttp\Psr7\Response');
        $response->getHeader->returns(['application/json']);
        $response->getBody->returns($stream);

        $provider = Phony::partialMock(GoogleProvider::class);
        $provider->getResponse->returns($response);

        $google = $provider->get();

        $token = $this->mockAccessToken();

        // Expect
        $this->expectException(IdentityProviderException::class);

        // Execute
        $user = $google->getResourceOwner($token);

        // Verify
        Phony::inOrder(
            $provider->getResponse->calledWith($this->instanceOf('GuzzleHttp\Psr7\Request')),
            $response->getHeader->called(),
            $response->getBody->called()
        );
    }

    private function mockAccessToken(): AccessToken
    {
        return new AccessToken([
            'access_token' => 'mock_access_token',
        ]);
    }
}
