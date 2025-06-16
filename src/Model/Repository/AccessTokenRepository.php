<?php

namespace Ecosys\OAuth\Model\Repository;

use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Ecosys\OAuth\Model\Entity\AccessTokenEntity;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
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
     * Persists a new access token to the database.
     * The primary key is now the JWT ID ('jti') claim.
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        $sql = 'INSERT INTO oauth_access_tokens (jti, client_id, user_id, expires, scope) VALUES (:jti, :client_id, :user_id, :expires, :scope)';
        $stmt = $this->db->prepare($sql);

        // The library uses the 'jti' claim as the unique identifier for the token.
        $jti = $accessTokenEntity->getIdentifier();
        $clientId = $accessTokenEntity->getClient()->getIdentifier();
        $userId = $accessTokenEntity->getUserIdentifier();
        $expires = $accessTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s');
        
        $scopes = $accessTokenEntity->getScopes();
        $scopeString = implode(' ', array_map(function($scope) {
            return $scope->getIdentifier();
        }, $scopes));

        $stmt->bindParam(':jti', $jti);
        $stmt->bindParam(':client_id', $clientId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':expires', $expires);
        $stmt->bindParam(':scope', $scopeString);
        
        $stmt->execute();
    }

    /**
     * Revokes an access token.
     * The incoming $tokenId from the logout route is the full JWT string.
     * We need to decode it to get the 'jti' to find it in the database.
     */
    public function revokeAccessToken($tokenId)
    {
        try {
            // Use the Lcobucci parser to decode the token string without validation
            $parser = new Parser(new JoseEncoder());
            $token = $parser->parse($tokenId);
            
            // Assert that it is a Plain token (as created by the server)
            if (!$token instanceof Plain) {
                // Could not parse or is not a plain token, cannot get jti.
                return;
            }

            $jti = $token->claims()->get('jti');

            if ($jti) {
                $sql = 'DELETE FROM oauth_access_tokens WHERE jti = :jti';
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':jti', $jti);
                $stmt->execute();
            }

        } catch (\Exception $e) {
            // Could not parse the token, so we can't revoke it.
            // Log this error in a real application. For now, we fail silently.
            return;
        }
    }

    /**
     * Checks if an access token has been revoked.
     * The $tokenId parameter is the 'jti' claim from the JWT,
     * passed by the ResourceServer during validation.
     */
    public function isAccessTokenRevoked($tokenId)
    {
        $sql = 'SELECT COUNT(*) FROM oauth_access_tokens WHERE jti = :jti';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':jti', $tokenId);
        $stmt->execute();

        // If the count is 0, the JTI does not exist in our DB, meaning it has been revoked (deleted).
        // Therefore, we return true (is revoked).
        // If the count is > 0, it exists and is not revoked, so we return false.
        return $stmt->fetchColumn() == 0;
    }
}
