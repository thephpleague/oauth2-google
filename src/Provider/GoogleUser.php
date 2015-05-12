<?php

namespace League\OAuth2\Client\Provider;

class GoogleUser implements UserInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $displayName;

    /**
     * @var string
     */
    protected $givenName;

    /**
     * @var string
     */
    protected $familyName;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $imageUrl;

    /**
     * @param  array $response
     */
    public function __construct(array $response)
    {
        $this->id          = $response['id'];
        $this->displayName = $response['displayName'];
        $this->givenName   = $response['name']['givenName'];
        $this->familyName  = $response['name']['familyName'];

        if (!empty($response['emails'])) {
            $this->email = $response['emails'][0]['value'];
        }

        if (!empty($response['image']['url'])) {
            $this->imageUrl = $response['image']['url'];
        }
    }

    public function getUserId()
    {
        return $this->id;
    }

    /**
     * Get perferred display name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->displayName;
    }

    /**
     * Get perferred first name.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->givenName;
    }

    /**
     * Get perferred last name.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->familyName;
    }

    /**
     * Get email address.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get avatar link.
     *
     * @return string
     */
    public function getAvatar()
    {
        return $this->imageUrl;
    }
}
