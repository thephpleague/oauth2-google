<?php

namespace League\OAuth2\Client\Test\Provider;

use Eloquent\Phony\Phpunit\Phony;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google as GoogleProvider;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;

class GoogleTest extends TestCase
{
    /** @var GoogleProvider */
    protected $provider;

    protected function setUp()
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

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertArrayHasKey('hd', $query);
        $this->assertArrayHasKey('access_type', $query);
        $this->assertArrayHasKey('prompt', $query);

        $this->assertEquals('mock_access_type', $query['access_type']);
        $this->assertEquals('mock_domain', $query['hd']);
        $this->assertEquals('select_account', $query['prompt']);

        $this->assertContains('email', $query['scope']);
        $this->assertContains('profile', $query['scope']);
        $this->assertContains('openid', $query['scope']);

        $this->assertAttributeNotEmpty('state', $this->provider);
    }

    public function testBaseAccessTokenUrl()
    {
        $url = $this->provider->getBaseAccessTokenUrl([]);
        $uri = parse_url($url);

        $this->assertEquals('/oauth2/v4/token', $uri['path']);
    }

    /**
     * @link https://accounts.google.com/.well-known/openid-configuration
     */
    public function testResourceOwnerDetailsUrl()
    {
        $token = $this->mockAccessToken();

        $url = $this->provider->getResourceOwnerDetailsUrl($token);

        $this->assertEquals('https://openidconnect.googleapis.com/v1/userinfo', $url);
    }

    public function testUserData()
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

        $this->assertInstanceOf('League\OAuth2\Client\Provider\ResourceOwnerInterface', $user);

        $this->assertEquals(12345, $user->getId());
        $this->assertEquals('mock name', $user->getName());
        $this->assertEquals('mock', $user->getFirstName());
        $this->assertEquals('name', $user->getLastName());
        $this->assertEquals('mock.name@example.com', $user->getEmail());
        $this->assertEquals('example.com', $user->getHostedDomain());
        $this->assertEquals('mock_image_url', $user->getAvatar());

        $user = $user->toArray();

        $this->assertArrayHasKey('sub', $user);
        $this->assertArrayHasKey('name', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertArrayHasKey('hd', $user);
        $this->assertArrayHasKey('picture', $user);
        $this->assertArrayHasKey('family_name', $user);
    }

    public function testErrorResponse()
    {
        // Mock
        $error_json = '{"error": {"code": 400, "message": "I am an error"}}';

        $response = Phony::mock('GuzzleHttp\Psr7\Response');
        $response->getHeader->returns(['application/json']);
        $response->getBody->returns($error_json);

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
