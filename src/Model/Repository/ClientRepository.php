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
     *
     * @param array $clientData
     * @return array|false Returns the new client's data or false on error.
     */
    public function createClient(array $clientData)
    {
        // Generate a new client ID and secret
        $clientId = bin2hex(random_bytes(20));
        $clientSecret = $clientData['is_confidential'] ? bin2hex(random_bytes(40)) : null;

        // In a production environment, client secrets should be hashed.
        // For this example, we are storing them in plain text.

        $sql = 'INSERT INTO oauth_clients (client_id, client_secret, name, redirect_uri, grant_types, is_confidential) VALUES (:client_id, :client_secret, :name, :redirect_uri, :grant_types, :is_confidential)';
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':client_id', $clientId);
        $stmt->bindParam(':client_secret', $clientSecret);
        $stmt->bindParam(':name', $clientData['client_name']);
        $stmt->bindParam(':redirect_uri', $clientData['redirect_uri']);
        $stmt->bindParam(':grant_types', $clientData['grant_types']);
        $stmt->bindParam(':is_confidential', $clientData['is_confidential'], PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            return [
                'client_id' => $clientId,
                'client_secret' => $clientSecret, // Return the plain secret only on creation
                'client_name' => $clientData['client_name'],
                'redirect_uri' => $clientData['redirect_uri'],
                'grant_types' => $clientData['grant_types']
            ];
        }

        return false;
    }
}
