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
        // Here you can filter the scopes based on grant type, client, or user.
        // For example, a certain user might not be allowed to grant a 'delete' scope.
        // For this example, we'll return all valid requested scopes.
        
        $validatedScopes = [];
        foreach ($scopes as $scope) {
            if ($this->getScopeEntityByIdentifier($scope->getIdentifier())) {
                 $validatedScopes[] = $scope;
            }
        }

        return $validatedScopes;
    }
}
