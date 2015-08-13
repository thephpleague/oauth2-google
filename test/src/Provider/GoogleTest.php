<?php

namespace League\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\Google as GoogleProvider;

use Mockery as m;

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

    public function tearDown()
    {
        m::close();
        parent::tearDown();
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
        $token = m::mock('League\OAuth2\Client\Token\AccessToken', [['access_token' => 'mock_access_token']]);

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

        $token = m::mock('League\OAuth2\Client\Token\AccessToken', [['access_token' => 'mock_access_token']]);

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
        $response = json_decode('{"emails": [{"value": "mock_email"}],"id": "12345","displayName": "mock_name","name": {"familyName": "mock_last_name","givenName": "mock_first_name"},"image": {"url": "mock_image_url"}}', true);

        $provider = m::mock('League\OAuth2\Client\Provider\Google[fetchResourceOwnerDetails]')
            ->shouldAllowMockingProtectedMethods();

        $provider->shouldReceive('fetchResourceOwnerDetails')
            ->times(1)
            ->andReturn($response);

        $token = m::mock('League\OAuth2\Client\Token\AccessToken');
        $user = $provider->getResourceOwner($token);

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

    /**
     * @expectedException League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function testErrorResponse()
    {
        $response = m::mock('GuzzleHttp\Psr7\Response');

        $response->shouldReceive('getHeader')
            ->with('content-type')
            ->andReturn(['application/json']);

        $response->shouldReceive('getBody')
            ->andReturn('{"error": {"code": 400, "message": "I am an error"}}');

        $provider = m::mock('League\OAuth2\Client\Provider\Google[sendRequest]')
            ->shouldAllowMockingProtectedMethods();

        $provider->shouldReceive('sendRequest')
            ->times(1)
            ->andReturn($response);

        $token = m::mock('League\OAuth2\Client\Token\AccessToken');
        $user = $provider->getResourceOwner($token);
    }
}
