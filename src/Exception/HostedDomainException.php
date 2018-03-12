<?php

namespace League\OAuth2\Client\Exception;

/**
 * Exception thrown if the Google Provider is configured with a hosted domain that the user doesn't belong to
 */
class HostedDomainException extends \Exception
{
    private $hostedDomainConfigured;

    private $hostedDomainOfUser;

    /**
     * HostedDomainException constructor.
     * @param string $hostedDomainConfigured
     * @param string|null $hostedDomainOfUser
     */
    public function __construct($hostedDomainConfigured, $hostedDomainOfUser)
    {
        parent::__construct("Hosted domain mismatch '$hostedDomainOfUser' !== '$hostedDomainConfigured'");
        $this->hostedDomainConfigured = $hostedDomainConfigured;
        $this->hostedDomainOfUser = $hostedDomainOfUser;
    }

    /**
     * The hosted domain configured for this provider.
     * @return string
     */
    public function getHostedDomainConfigured()
    {
        return $this->hostedDomainConfigured;
    }

    /**
     * The hosted domain of the user. Non G-Suite users do not have hosted domains
     * @return string|null
     */
    public function getHostedDomainOfUser()
    {
        return $this->hostedDomainOfUser;
    }
}
