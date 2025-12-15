<?php

namespace League\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\GoogleUser;
use PHPUnit\Framework\TestCase;

class GoogleUserTest extends TestCase
{
    public function testUserDefaults(): void
    {
        // Mock
        $user = new GoogleUser([
            'sub' => '12345',
            'email' => 'mock.name@example.com',
            'email_verified' => true,
            'name' => 'mock name',
            'given_name' => 'mock',
            'family_name' => 'name',
            'picture' => 'mock_image_url',
            'hd' => 'example.com',
            'locale' => 'en',
        ]);

        self::assertEquals(12345, $user->getId());
        self::assertEquals('mock name', $user->getName());
        self::assertEquals('mock', $user->getFirstName());
        self::assertEquals('name', $user->getLastName());
        self::assertEquals('en', $user->getLocale());
        self::assertEquals('mock.name@example.com', $user->getEmail());
        self::assertEquals('example.com', $user->getHostedDomain());
        self::assertEquals('mock_image_url', $user->getAvatar());
        self::assertTrue($user->getEmailVerified());
        self::assertTrue($user->isEmailTrustworthy());
    }

    public function testUserPartialData(): void
    {
        $user = new GoogleUser([
            'sub' => '12345',
            'name' => 'mock name',
            'given_name' => 'mock',
            'family_name' => 'name',
        ]);

        self::assertEquals(null, $user->getEmail());
        self::assertEquals(null, $user->getHostedDomain());
        self::assertEquals(null, $user->getAvatar());
        self::assertEquals(null, $user->getLocale());
        self::assertNull($user->getEmailVerified());
        self::assertFalse($user->isEmailTrustworthy());
    }

    public function testUserMinimalData(): void
    {
        $user = new GoogleUser([
            'sub' => '12345',
            'name' => 'mock name',
        ]);

        self::assertEquals(null, $user->getEmail());
        self::assertEquals(null, $user->getHostedDomain());
        self::assertEquals(null, $user->getAvatar());
        self::assertEquals(null, $user->getLocale());
        self::assertEquals(null, $user->getFirstName());
        self::assertEquals(null, $user->getLastName());
        self::assertNull($user->getEmailVerified());
        self::assertFalse($user->isEmailTrustworthy());
    }

    public function testGmailTrustworthy(): void
    {
        $user = new GoogleUser([
            'sub' => '12345',
            'name' => 'mock name',
            'email' => 'mock.email@gmail.com',
        ]);

        self::assertNull($user->getEmailVerified());
        self::assertTrue($user->isEmailTrustworthy());
    }

    public function testAlmostGmailNotTrustworthy(): void
    {
        $user = new GoogleUser([
            'sub' => '12345',
            'name' => 'mock name',
            'email' => 'mock.email@agmail.com',
        ]);

        self::assertNull($user->getEmailVerified());
        self::assertFalse($user->isEmailTrustworthy());
    }

    public function testNonGmailWithHostedDomainTrustworthy(): void
    {
        $user = new GoogleUser([
            'sub' => '12345',
            'name' => 'mock name',
            'email' => 'mock.email@example.com',
            'email_verified' => true,
            'hd' => 'example.com',
        ]);

        self::assertTrue($user->getEmailVerified());
        self::assertTrue($user->isEmailTrustworthy());
    }

    public function testNonGmailNonVerifiedWithHostedDomainNotTrustworthy(): void
    {
        $user = new GoogleUser([
            'sub' => '12345',
            'name' => 'mock name',
            'email' => 'mock.email@example.com',
            'email_verified' => false,
            'hd' => 'example.com',
        ]);

        self::assertFalse($user->getEmailVerified());
        self::assertFalse($user->isEmailTrustworthy());
    }

    public function testNonGmailVerifiedWithoutHostedDomainNotTrustworthy(): void
    {
        $user = new GoogleUser([
            'sub' => '12345',
            'name' => 'mock name',
            'email' => 'mock.email@example.com',
            'email_verified' => true,
        ]);

        self::assertTrue($user->getEmailVerified());
        self::assertFalse($user->isEmailTrustworthy());
    }
}
