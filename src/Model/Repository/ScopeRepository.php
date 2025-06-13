<?php

namespace Ecosys\OAuth\Model\Repository;

use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Ecosys\OAuth\Model\Entity\ScopeEntity;
use PDO;

class ScopeRepository implements ScopeRepositoryInterface
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getScopeEntityByIdentifier($scopeIdentifier)
    {
        $sql = 'SELECT * FROM oauth_scopes WHERE scope = :scope';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':scope', $scopeIdentifier);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $scope = new ScopeEntity();
            $scope->setIdentifier($scopeIdentifier);
            return $scope;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    ) {
        // Get the list of scopes this client is allowed to request.
        $allowedScopes = $clientEntity->getScopes();

        // If the client has no scopes defined, deny all for security.
        if (empty($allowedScopes)) {
            return [];
        }

        $validatedScopes = [];
        foreach ($scopes as $requestedScope) {
            // Check if the requested scope is in the client's list of allowed scopes.
            if (in_array($requestedScope->getIdentifier(), $allowedScopes)) {
                 $validatedScopes[] = $requestedScope;
            }
        }

        return $validatedScopes;
    }
}
