<?php

namespace Ecosys\OAuth\Model\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface
{
    use EntityTrait, ClientTrait;

    protected $secret;
    protected $scopes = []; // Added to hold the client's allowed scopes

    public function __construct($clientIdentifier)
    {
        $this->setIdentifier($clientIdentifier);
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setRedirectUri($uri)
    {
        $this->redirectUri = $uri;
    }

    public function setClientSecret($secret)
    {
        $this->secret = $secret;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Set the client's allowed scopes.
     * @param array $scopes
     */
    public function setScopes(array $scopes)
    {
        $this->scopes = $scopes;
    }

    /**
     * Get the client's allowed scopes.
     * @return array
     */
    public function getScopes()
    {
        return $this->scopes;
    }
    
    public function isConfidential()
    {
        return !empty($this->secret);
    }
}
