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

    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        $sql = 'SELECT * FROM oauth_users WHERE username = :username';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) { return null; }
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
        if (!$user) { return null; }
        $userEntity = new UserEntity();
        $userEntity->setIdentifier($user['username']);
        $userEntity->setUsername($user['username']);
        $userEntity->setFirstName($user['first_name']);
        $userEntity->setLastName($user['last_name']);
        $userEntity->setEmail($user['email']);
        return $userEntity;
    }

    public function createUser(array $userData)
    {
        $checkSql = 'SELECT username, email FROM oauth_users WHERE username = :username OR email = :email';
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bindParam(':username', $userData['username']);
        $checkStmt->bindParam(':email', $userData['email']);
        $checkStmt->execute();
        $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($existingUser) {
            if ($existingUser['username'] === $userData['username']) { return 'username_exists'; }
            if ($existingUser['email'] === $userData['email']) { return 'email_exists'; }
        }
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        $sql = 'INSERT INTO oauth_users (username, password, first_name, last_name, email) VALUES (:username, :password, :first_name, :last_name, :email)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $userData['username']);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':first_name', $userData['first_name']);
        $stmt->bindParam(':last_name', $userData['last_name']);
        $stmt->bindParam(':email', $userData['email']);
        if ($stmt->execute()) {
            unset($userData['password']);
            return $userData;
        }
        return 'database_error';
    }

    public function getAllUsers()
    {
        $sql = 'SELECT username, first_name, last_name, email FROM oauth_users';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update a user's details.
     * This method is now fixed to handle password updates.
     * @param string $username
     * @param array $data
     * @return object|string
     */
    public function updateUser($username, array $data)
    {
        $fields = [];
        if (!empty($data['first_name'])) $fields['first_name'] = $data['first_name'];
        if (!empty($data['last_name'])) $fields['last_name'] = $data['last_name'];
        if (!empty($data['email'])) $fields['email'] = $data['email'];

        // FIX: Check for and hash a new password if provided
        if (!empty($data['password'])) {
            $fields['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) {
            return 'no_fields_to_update';
        }

        $setClauses = [];
        foreach ($fields as $key => $value) {
            $setClauses[] = "$key = :$key";
        }
        $sql = 'UPDATE oauth_users SET ' . implode(', ', $setClauses) . ' WHERE username = :username';
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        foreach ($fields as $key => &$value) {
            $stmt->bindParam(":$key", $value);
        }

        if ($stmt->execute()) {
            return $this->getUserEntityByIdentifier($username);
        }

        return 'database_error';
    }

    public function deleteUser($username)
    {
        $sql = 'DELETE FROM oauth_users WHERE username = :username';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        return $stmt->execute();
    }
}
