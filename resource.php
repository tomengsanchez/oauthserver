<?php

use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Validates a PSR-7 request object against the OAuth2 server.
 *
 * @param ServerRequestInterface $request
 * @return ServerRequestInterface The request with validated OAuth attributes.
 * @throws OAuthServerException
 */
function validate_token(ServerRequestInterface $request)
{
    // We need our database connection and repositories
    $database = new Database();
    $db = $database->connect();
    
    $accessTokenRepository = new \Ecosys\OAuth\Model\Repository\AccessTokenRepository($db);

    // Setup the resource server
    $server = new ResourceServer(
        $accessTokenRepository,
        'file://' . APP_PATH . 'public.key' // The public key to verify the token signature
    );

    // Try to validate the request
    return $server->validateAuthenticatedRequest($request);
}
