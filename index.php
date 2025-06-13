<?php

// Set the error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Include dependencies and configuration
require_once __DIR__ . '/vendor/autoload.php';
require_once 'config.php';
require_once 'database.php';
require_once 'resource.php';

spl_autoload_register(function ($class) {
    $prefix = 'Ecosys\\OAuth\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) { return; }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) { require $file; }
});

use Ecosys\OAuth\Controller\TokenController;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Response;

$database = new Database();
$dbConnection = $database->connect();
$request = ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
$path = $request->getUri()->getPath();
$basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
$route = str_replace($basePath, '', $path);
$response = new Response();

try {
    switch (true) {
        case ($route === '/token'):
            $controller = new TokenController($dbConnection);
            $response = $controller->issueToken($request);
            break;

        case ($route === '/api/profile'):
            $request = validate_token($request);
            $userId = $request->getAttribute('oauth_user_id');
            $scopes = $request->getAttribute('oauth_scopes');
            if (!in_array('profile', $scopes)) {
                throw new \League\OAuth2\Server\Exception\OAuthServerException('The token is missing the required "profile" scope.', 6, 'insufficient_scope', 403);
            }
            $userRepo = new \Ecosys\OAuth\Model\Repository\UserRepository($dbConnection);
            $user = $userRepo->getUserEntityByIdentifier($userId);
            $response->getBody()->write(json_encode(['user_id' => $user->getIdentifier(), 'username' => $user->getUsername(), 'scopes' => $scopes, 'message' => 'Successfully accessed protected profile data.']));
            break;
        
        case ($route === '/api/users' && $request->getMethod() === 'POST'):
            // 1. Authenticate the request
            $request = validate_token($request);

            // 2. Authorize: Check for the required scope
            $scopes = $request->getAttribute('oauth_scopes');
            if (!in_array('users:create', $scopes)) {
                throw new \League\OAuth2\Server\Exception\OAuthServerException('The token is missing the required "users:create" scope.', 6, 'insufficient_scope', 403);
            }
            
            // 3. Get and validate the request body
            $body = json_decode((string) $request->getBody(), true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($body['username'], $body['password'], $body['email'])) {
                $response->getBody()->write(json_encode(['error' => 'Bad Request', 'message' => 'Invalid JSON or missing required fields: username, password, email.']));
                $response = $response->withStatus(400);
                break;
            }

            // 4. Call the repository to create the user
            $userRepo = new \Ecosys\OAuth\Model\Repository\UserRepository($dbConnection);
            $userData = [
                'username' => $body['username'],
                'password' => $body['password'],
                'first_name' => $body['first_name'] ?? null,
                'last_name' => $body['last_name'] ?? null,
                'email' => $body['email']
            ];
            $result = $userRepo->createUser($userData);

            // 5. Handle the result
            if (is_array($result)) {
                $response->getBody()->write(json_encode(['status' => 'success', 'message' => 'User created successfully.', 'user' => $result]));
                $response = $response->withStatus(201);
            } elseif ($result === 'username_exists' || $result === 'email_exists') {
                $response->getBody()->write(json_encode(['error' => 'Conflict', 'message' => 'A user with that username or email already exists.']));
                $response = $response->withStatus(409);
            } else {
                $response->getBody()->write(json_encode(['error' => 'Internal Server Error', 'message' => 'Could not create user due to a database error.']));
                $response = $response->withStatus(500);
            }
            break;

        default:
            $response->getBody()->write(json_encode(['Application' => 'EcosysOAuthServer', 'status' => 'Running']));
            break;
    }
} catch (\League\OAuth2\Server\Exception\OAuthServerException $e) {
    $response = $e->generateHttpResponse(new Response());
} catch (\Exception $e) {
    $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
    $response = $response->withStatus(500);
}

// Emit the response
if (!headers_sent()) {
    header(sprintf('HTTP/%s %s %s', $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase()), true, $response->getStatusCode());
    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header(sprintf('%s: %s', $name, $value), false);
        }
    }
}
echo $response->getBody();
