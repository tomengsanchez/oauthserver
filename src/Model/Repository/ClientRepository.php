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
        
        // The league's ClientTrait needs the secret to be set for confidential clients
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

        // If it's a confidential client, we must validate the secret.
        // For public clients (like a single-page app), the secret is null.
        if ($client->isConfidential()) {
             if (!password_verify($clientSecret, $client->getSecret())) {
                 // The above check will fail because secrets in the DB are not hashed.
                 // We will do a direct string comparison for this example.
                 // IN A PRODUCTION ENVIRONMENT, CLIENT SECRETS SHOULD BE HASHED.
                 if ($clientSecret !== $client->getSecret()) {
                    return false;
                 }
            }
        }
        
        // You might want to check if the client is allowed to use the requested grant type.
        // $allowedGrantTypes = explode(',', $client->getGrantTypes());
        // if (!in_array($grantType, $allowedGrantTypes)) {
        //     return false;
        // }

        return true;
    }
}
