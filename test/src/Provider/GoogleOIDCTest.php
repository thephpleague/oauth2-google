<?php

namespace League\OAuth2\Client\Test\Provider;

use Eloquent\Phony\Phpunit\Phony;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google as GoogleProvider;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;

class GoogleOIDCTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Google provider using the Google's OIDC user info endpoint for user details
     * @var GoogleProvider
     */
    protected $providerOidc;

    protected function setUp()
    {
        $this->providerOidc = new GoogleProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'hostedDomain' => 'mock_domain',
            'accessType' => 'mock_access_type',
            'useOidcMode' => true,
        ]);
    }

    public function testAuthorizationUrl()
    {
        $url = $this->providerOidc->getAuthorizationUrl();
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

        $this->assertAttributeNotEmpty('state', $this->providerOidc);
    }

    public function testBaseAccessTokenUrl()
    {
        $url = $this->providerOidc->getBaseAccessTokenUrl([]);
        $uri = parse_url($url);

        $this->assertEquals('/oauth2/v4/token', $uri['path']);
    }

    public function testResourceOwnerDetailsUrl()
    {
        $token = $this->mockAccessToken();

        $url = $this->providerOidc->getResourceOwnerDetailsUrl($token);
        // Per 'userinfo_endpoint' of https://accounts.google.com/.well-known/openid-configuration
        $this->assertEquals('https://www.googleapis.com/oauth2/v3/userinfo', $url);
    }


    public function testUserData()
    {
        // Mock
        $response = json_decode('{"email": "mock_email","sub": "12345","name": "mock_name", "family_name": "mock_last_name","given_name": "mock_first_name", "picture": "mock_image_url", "hd": "example.com"}', true);

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
