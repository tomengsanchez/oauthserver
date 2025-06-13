<?php

namespace Ecosys\OAuth\Model\Repository;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Ecosys\OAuth\Model\Entity\ClientEntity;
use PDO;

class ClientRepository implements ClientRepositoryInterface
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientEntity($clientIdentifier)
    {
        $sql = 'SELECT * FROM oauth_clients WHERE client_id = :client_id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':client_id', $clientIdentifier);
        $stmt->execute();

        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$client) {
            return null;
        }

        $clientEntity = new ClientEntity($clientIdentifier);
        $clientEntity->setName($client['name']);
        $clientEntity->setRedirectUri($client['redirect_uri']);
        
        if ($client['is_confidential']) {
            $clientEntity->setClientSecret($client['client_secret']);
        }
        
        // Read scopes from the database, split the string into an array, and set it.
        $scopes = $client['scope'] ? explode(' ', $client['scope']) : [];
        $clientEntity->setScopes($scopes);


        return $clientEntity;
    }

    /**
     * {@inheritdoc}
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        $client = $this->getClientEntity($clientIdentifier);

        if (!$client) {
            return false;
        }

        if ($client->isConfidential()) {
             if ($clientSecret !== $client->getSecret()) {
                return false;
             }
        }
        
        return true;
    }

    /**
     * Creates a new client in the database.
     */
    public function createClient(array $clientData)
    {
        $clientId = bin2hex(random_bytes(20));
        $clientSecret = $clientData['is_confidential'] ? bin2hex(random_bytes(40)) : null;

        $sql = 'INSERT INTO oauth_clients (client_id, client_secret, name, redirect_uri, grant_types, is_confidential, scope) VALUES (:client_id, :client_secret, :name, :redirect_uri, :grant_types, :is_confidential, :scope)';
        $stmt = $this->db->prepare($sql);
        
        // Note: You could add 'scope' to the client creation API as well.
        // For now, we set it to an empty string.
        $scope = '';

        $stmt->bindParam(':client_id', $clientId);
        $stmt->bindParam(':client_secret', $clientSecret);
        $stmt->bindParam(':name', $clientData['client_name']);
        $stmt->bindParam(':redirect_uri', $clientData['redirect_uri']);
        $stmt->bindParam(':grant_types', $clientData['grant_types']);
        $stmt->bindParam(':is_confidential', $clientData['is_confidential'], PDO::PARAM_BOOL);
        $stmt->bindParam(':scope', $scope);

        if ($stmt->execute()) {
            return [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'client_name' => $clientData['client_name'],
                'redirect_uri' => $clientData['redirect_uri'],
                'grant_types' => $clientData['grant_types']
            ];
        }

        return false;
    }
}
