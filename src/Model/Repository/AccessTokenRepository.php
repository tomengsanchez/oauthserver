<?php

namespace Ecosys\OAuth\Model\Repository;

use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Ecosys\OAuth\Model\Entity\AccessTokenEntity;
use PDO;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }
        $accessToken->setUserIdentifier($userIdentifier);

        return $accessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        $sql = 'INSERT INTO oauth_access_tokens (access_token, client_id, user_id, expires, scope) VALUES (:access_token, :client_id, :user_id, :expires, :scope)';
        $stmt = $this->db->prepare($sql);

        $accessToken = $accessTokenEntity->getIdentifier();
        $clientId = $accessTokenEntity->getClient()->getIdentifier();
        $userId = $accessTokenEntity->getUserIdentifier();
        $expires = $accessTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s');
        
        $scopes = $accessTokenEntity->getScopes();
        $scopeString = implode(' ', array_map(function($scope) {
            return $scope->getIdentifier();
        }, $scopes));

        $stmt->bindParam(':access_token', $accessToken);
        $stmt->bindParam(':client_id', $clientId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':expires', $expires);
        $stmt->bindParam(':scope', $scopeString);
        
        $stmt->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessToken($tokenId)
    {
        // This can be implemented by deleting the token or adding an is_revoked flag
        $sql = 'DELETE FROM oauth_access_tokens WHERE access_token = :access_token';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':access_token', $tokenId);
        $stmt->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function isAccessTokenRevoked($tokenId)
    {
        // If the token doesn't exist, it's considered revoked
        $sql = 'SELECT COUNT(*) FROM oauth_access_tokens WHERE access_token = :access_token';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':access_token', $tokenId);
        $stmt->execute();

        return $stmt->fetchColumn() == 0;
    }
}
