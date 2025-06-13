<?php

namespace Ecosys\OAuth\Model\Repository;

use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Ecosys\OAuth\Model\Entity\UserEntity;
use PDO;

class UserRepository implements UserRepositoryInterface
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials(
        $username,
        $password,
        $grantType,
        ClientEntityInterface $clientEntity
    ) {
        $sql = 'SELECT * FROM oauth_users WHERE username = :username';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        // Verify the password
        if (password_verify($password, $user['password'])) {
            $userEntity = new UserEntity();
            $userEntity->setIdentifier($user['username']);
            $userEntity->setUsername($user['username']);
            return $userEntity;
        }

        return null;
    }
    
    /**
     * Get a user entity by its identifier.
     * This method is required by the resource server to fetch user details from a token.
     *
     * @param string $identifier The user's identifier (username)
     * @return UserEntityInterface|null
     */
    public function getUserEntityByIdentifier($identifier)
    {
        $sql = 'SELECT username, first_name, last_name, email FROM oauth_users WHERE username = :username';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $identifier);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }
        
        $userEntity = new UserEntity();
        $userEntity->setIdentifier($user['username']);
        $userEntity->setUsername($user['username']);
        // You would typically set more properties here
        // $userEntity->setFirstName($user['first_name']);

        return $userEntity;
    }
}
