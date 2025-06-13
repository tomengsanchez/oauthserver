<?php

namespace Ecosys\OAuth\Model\Repository;

use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use PDO;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewRefreshToken()
    {
        // It's okay to return null here. The grant will create the entity.
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
        $sql = 'INSERT INTO oauth_refresh_tokens (refresh_token, access_token, client_id, user_id, expires, scope) VALUES (:refresh_token, :access_token, :client_id, :user_id, :expires, :scope)';
        $stmt = $this->db->prepare($sql);

        $refreshToken = $refreshTokenEntity->getIdentifier();
        $accessToken = $refreshTokenEntity->getAccessToken()->getIdentifier();
        $clientId = $refreshTokenEntity->getAccessToken()->getClient()->getIdentifier();
        $userId = $refreshTokenEntity->getAccessToken()->getUserIdentifier();
        $expires = $refreshTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s');
        
        $scopes = $refreshTokenEntity->getAccessToken()->getScopes();
        $scopeString = implode(' ', array_map(function($scope) {
            return $scope->getIdentifier();
        }, $scopes));


        $stmt->bindParam(':refresh_token', $refreshToken);
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
    public function revokeRefreshToken($tokenId)
    {
        $sql = 'DELETE FROM oauth_refresh_tokens WHERE refresh_token = :refresh_token';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':refresh_token', $tokenId);
        $stmt->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function isRefreshTokenRevoked($tokenId)
    {
        $sql = 'SELECT COUNT(*) FROM oauth_refresh_tokens WHERE refresh_token = :refresh_token';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':refresh_token', $tokenId);
        $stmt->execute();

        return $stmt->fetchColumn() == 0;
    }
}
