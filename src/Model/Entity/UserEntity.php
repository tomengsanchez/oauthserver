<?php

namespace Ecosys\OAuth\Model\Entity;

use League\OAuth2\Server\Entities\UserEntityInterface;
use JsonSerializable;

class UserEntity implements UserEntityInterface, JsonSerializable
{
    protected $identifier;
    protected $username;
    protected $firstName;
    protected
    $lastName;
    protected $email;

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Specify data which should be serialized to JSON.
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'username' => $this->getIdentifier(),
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'email' => $this->getEmail(),
        ];
    }
}
