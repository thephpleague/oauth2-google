<?php

namespace League\OAuth2\Client\Test\Provider;

use Eloquent\Phony\Phpunit\Phony;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google as GoogleProvider;
use League\OAuth2\Client\Token\AccessToken;

class GoogleTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;

    protected function setUp()
    {
        $this->provider = new GoogleProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'hostedDomain' => 'mock_domain',
            'accessType' => 'mock_access_type'
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

        $this->assertEquals('mock_access_type', $query['access_type']);
        $this->assertEquals('mock_domain', $query['hd']);

        $this->assertContains('email', $query['scope']);
        $this->assertContains('profile', $query['scope']);
        $this->assertContains('openid', $query['scope']);

        $this->assertAttributeNotEmpty('state', $this->provider);
    }

    public function testBaseAccessTokenUrl()
    {
        $url = $this->provider->getBaseAccessTokenUrl([]);
        $uri = parse_url($url);

        $this->assertEquals('/o/oauth2/token', $uri['path']);
    }

    public function testResourceOwnerDetailsUrl()
    {
        $token = $this->mockAccessToken();

        $url = $this->provider->getResourceOwnerDetailsUrl($token);
        $uri = parse_url($url);

        $this->assertEquals('/plus/v1/people/me', $uri['path']);
        $this->assertNotContains('mock_access_token', $url);
    }

    public function testResourceOwnerDetailsUrlCustomFields()
    {
        $provider = new GoogleProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'userFields' => [
                'domain',
                'gender',
                'verified',
            ],
        ]);

        $token = $this->mockAccessToken();

        $url = $provider->getResourceOwnerDetailsUrl($token);
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('fields', $query);
        $this->assertArrayHasKey('alt', $query);

        // Always JSON for consistency
        $this->assertEquals('json', $query['alt']);

        $fields = explode(',', $query['fields']);

        // Default values
        $this->assertContains('displayName', $fields);
        $this->assertContains('emails/value', $fields);
        $this->assertContains('image/url', $fields);

        // Configured values
        $this->assertContains('domain', $fields);
        $this->assertContains('gender', $fields);
        $this->assertContains('verified', $fields);
    }

    public function testUserData()
    {
        // Mock
        $response = json_decode('{"emails": [{"value": "mock_email"}],"id": "12345","displayName": "mock_name","name": {"familyName": "mock_last_name","givenName": "mock_first_name"},"image": {"url": "mock_image_url"}}', true);

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
        $this->assertEquals('mock_name', $user->getName());
        $this->assertEquals('mock_first_name', $user->getFirstName());
        $this->assertEquals('mock_last_name', $user->getLastName());
        $this->assertEquals('mock_email', $user->getEmail());
        $this->assertEquals('mock_image_url', $user->getAvatar());

        $user = $user->toArray();

        $this->assertArrayHasKey('id', $user);
        $this->assertArrayHasKey('displayName', $user);
        $this->assertArrayHasKey('emails', $user);
        $this->assertArrayHasKey('image', $user);
        $this->assertArrayHasKey('name', $user);
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
