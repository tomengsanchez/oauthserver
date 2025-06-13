<?php

namespace Ecosys\OAuth\Model\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface
{
    use EntityTrait, ClientTrait;

    /**
     * The client's secret.
     * This is declared to prevent the dynamic property creation deprecation notice.
     * @var ?string
     */
    protected $secret;

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

    /**
     * Set the client's secret.
     *
     * @param string $secret
     */
    public function setClientSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * Get the client's secret.
     * This method was added to resolve the fatal error.
     *
     * @return string|null
     */
    public function getSecret()
    {
        return $this->secret;
    }
    
    public function isConfidential()
    {
        // For this server, all clients that have a secret are confidential.
        return !empty($this->secret);
    }
}
