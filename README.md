# Google Provider for OAuth 2.0 Client

[![Join the chat at https://gitter.im/thephpleague/oauth2-google](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/thephpleague/oauth2-google?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

[![Build Status](https://img.shields.io/travis/thephpleague/oauth2-google.svg)](https://travis-ci.org/thephpleague/oauth2-google)
[![Code Coverage](https://img.shields.io/coveralls/thephpleague/oauth2-google.svg)](https://coveralls.io/r/thephpleague/oauth2-google)
[![Code Quality](https://img.shields.io/scrutinizer/g/thephpleague/oauth2-google.svg)](https://scrutinizer-ci.com/g/thephpleague/oauth2-google/)
[![License](https://img.shields.io/packagist/l/league/oauth2-google.svg)](https://github.com/thephpleague/oauth2-google/blob/master/LICENSE)
[![Latest Stable Version](https://img.shields.io/packagist/v/league/oauth2-google.svg)](https://packagist.org/packages/league/oauth2-google)

This package provides Google OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

This package is compliant with [PSR-1][], [PSR-2][] and [PSR-4][]. If you notice compliance oversights, please send
a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md


## Requirements

The following versions of PHP are supported.

* PHP 5.4
* PHP 5.5
* PHP 5.6
* HHVM

## Installation

To install, use composer:

```
composer require league/oauth2-google
```

## Usage

### Authorization Code Flow

```php
$provider = new League\OAuth2\Client\Provider\Google([
    'clientId'     => '{google-app-id}',
    'clientSecret' => '{google-app-secret}',
    'redirectUri'  => 'https://example.com/callback-url',
    'scopes'       => ['profile', 'email', '...'],
    'hostedDomain' => 'example.com',
]);

if (!empty($_GET['error'])) {

    // Got an error, probably user denied access
    exit('Got error: ' . $_GET['error']);

} elseif (empty($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->state;
    header('Location: ' . $authUrl);
    exit;

} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    // State is invalid, possible CSRF attack in progress
    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $userDetails = $provider->getUserDetails($token);

        // Use these details to create a new profile
        printf('Hello %s!', $userDetails->firstName);

    } catch (Exception $e) {

        // Failed to get user details
        exit('Something went wrong: ' . $e->getMessage());

    }

    // Use this to interact with an API on the users behalf
    echo $token->accessToken;

    // Use this to get a new access token if the old one expires
    echo $token->refreshToken;

    // Number of seconds until the access token will expire, and need refreshing
    echo $token->expires;
}
```

### Refreshing a Token

```php
$provider = new League\OAuth2\Client\Provider\Google([
    'clientId'     => '{google-app-id}',
    'clientSecret' => '{google-app-secret}',
    'redirectUri'  => 'https://example.com/callback-url',
]);

$grant = new League\OAuth2\Client\Grant\RefreshToken();
$token = $provider->getAccessToken($grant, ['refresh_token' => $refreshToken]);
```

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/thephpleague/oauth2-google/blob/master/CONTRIBUTING.md) for details.


## Credits

- [Woody Gilk](https://github.com/shadowhand)
- [All Contributors](https://github.com/thephpleague/oauth2-google/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/thephpleague/oauth2-google/blob/master/LICENSE) for more information.
