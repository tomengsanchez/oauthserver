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

        if (password_verify($password, $user['password'])) {
            $userEntity = new UserEntity();
            $userEntity->setIdentifier($user['username']);
            $userEntity->setUsername($user['username']);
            return $userEntity;
        }

        return null;
    }
    
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

        return $userEntity;
    }

    /**
     * Creates a new user in the database.
     *
     * @param array $userData
     * @return array|string Returns the new user's data or an error string.
     */
    public function createUser(array $userData)
    {
        // Check for existing username or email
        $checkSql = 'SELECT username, email FROM oauth_users WHERE username = :username OR email = :email';
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bindParam(':username', $userData['username']);
        $checkStmt->bindParam(':email', $userData['email']);
        $checkStmt->execute();
        $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            if ($existingUser['username'] === $userData['username']) {
                return 'username_exists';
            }
            if ($existingUser['email'] === $userData['email']) {
                return 'email_exists';
            }
        }
        
        // Hash the password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);

        $sql = 'INSERT INTO oauth_users (username, password, first_name, last_name, email) VALUES (:username, :password, :first_name, :last_name, :email)';
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':username', $userData['username']);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':first_name', $userData['first_name']);
        $stmt->bindParam(':last_name', $userData['last_name']);
        $stmt->bindParam(':email', $userData['email']);

        if ($stmt->execute()) {
            unset($userData['password']); // Don't return the password
            return $userData;
        }

        return 'database_error';
    }
}
