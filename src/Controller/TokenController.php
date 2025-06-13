<?php

namespace Ecosys\OAuth\Controller;

use Ecosys\OAuth\Model\Repository\ClientRepository;
use Ecosys\OAuth\Model\Repository\AccessTokenRepository;
use Ecosys\OAuth\Model\Repository\RefreshTokenRepository;
use Ecosys\OAuth\Model\Repository\ScopeRepository;
use Ecosys\OAuth\Model\Repository\UserRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response as Psr7Response;

class TokenController
{
    private $server;
    private $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;

        // 1. Initialize all repositories
        $clientRepository = new ClientRepository($this->db);
        $accessTokenRepository = new AccessTokenRepository($this->db);
        $scopeRepository = new ScopeRepository($this->db);
        $userRepository = new UserRepository($this->db);
        $refreshTokenRepository = new RefreshTokenRepository($this->db);

        // 2. Setup the authorization server
        $this->server = new AuthorizationServer(
            $clientRepository,
            $accessTokenRepository,
            $scopeRepository,
            // Private key path
            'file://' . APP_PATH . 'private.key',
            // Encryption key
            'lxZFUEsBCJ2Yb14IF2eFn6ovK6g5H92P' // Example key - MUST BE KEPT SECRET
        );

        // 3. Enable the Password Grant for issuing tokens with username/password
        $passwordGrant = new PasswordGrant(
            $userRepository,
            $refreshTokenRepository
        );
        $passwordGrant->setRefreshTokenTTL(new \DateInterval('P1M')); // Refresh tokens expire in 1 month

        // 4. Enable the Refresh Token Grant to refresh expired access tokens
        $refreshTokenGrant = new RefreshTokenGrant($refreshTokenRepository);
        $refreshTokenGrant->setRefreshTokenTTL(new \DateInterval('P1M'));

        // Add the grants to the server
        $this->server->enableGrantType(
            $passwordGrant,
            new \DateInterval('PT1H') // Access tokens expire in 1 hour
        );

        $this->server->enableGrantType(
            $refreshTokenGrant,
            new \DateInterval('PT1H') // Refreshed access tokens expire in 1 hour
        );
    }

    /**
     * The main action to issue an access token.
     */
    public function issueToken(ServerRequestInterface $request)
    {
        try {
            // Try to respond to the access token request
            return $this->server->respondToAccessTokenRequest($request, new Psr7Response());

        } catch (OAuthServerException $exception) {
            // All OAuth2 related errors are caught here
            return $exception->generateHttpResponse(new Psr7Response());

        } catch (\Exception $exception) {
            // Any other error
            $response = new Psr7Response();
            $response->getBody()->write(json_encode(['error' => 'An unexpected error occurred.']));
            return $response->withStatus(500);
        }
    }
}
