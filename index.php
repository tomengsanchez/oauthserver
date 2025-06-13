<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
$route = trim(str_replace($basePath, '', $path), '/');
$response = new Response();

try {
    // This regex matching allows for dynamic routes like /api/users/{username}
    switch (true) {
        case ($route === 'token'):
            $controller = new TokenController($dbConnection);
            $response = $controller->issueToken($request);
            break;

        case ($route === 'api/profile' && $request->getMethod() === 'GET'):
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
        
        case ($route === 'api/users' && $request->getMethod() === 'POST'):
            $request = validate_token($request);
            $scopes = $request->getAttribute('oauth_scopes');
            if (!in_array('users:create', $scopes)) {
                throw new \League\OAuth2\Server\Exception\OAuthServerException('The token is missing the required "users:create" scope.', 6, 'insufficient_scope', 403);
            }
            $body = json_decode((string) $request->getBody(), true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($body['username'], $body['password'], $body['email'])) {
                $response = $response->withStatus(400)->withBody((new \Laminas\Diactoros\StreamFactory())->createStream(json_encode(['error' => 'Bad Request', 'message' => 'Invalid JSON or missing required fields.'])));
                break;
            }
            $userRepo = new \Ecosys\OAuth\Model\Repository\UserRepository($dbConnection);
            $result = $userRepo->createUser($body);
            if (is_array($result)) {
                $response = $response->withStatus(201)->withBody((new \Laminas\Diactoros\StreamFactory())->createStream(json_encode(['status' => 'success', 'user' => $result])));
            } else {
                $response = $response->withStatus(409)->withBody((new \Laminas\Diactoros\StreamFactory())->createStream(json_encode(['error' => 'Conflict', 'message' => 'Username or email already exists.'])));
            }
            break;
        
        case ($route === 'api/users' && $request->getMethod() === 'GET'):
            $request = validate_token($request);
            $scopes = $request->getAttribute('oauth_scopes');
            if (!in_array('users:read', $scopes)) {
                throw new \League\OAuth2\Server\Exception\OAuthServerException('The token is missing the required "users:read" scope.', 6, 'insufficient_scope', 403);
            }
            $userRepo = new \Ecosys\OAuth\Model\Repository\UserRepository($dbConnection);
            $users = $userRepo->getAllUsers();
            $response->getBody()->write(json_encode(['status' => 'success', 'users' => $users]));
            break;

        case (preg_match('/^api\/users\/([a-zA-Z0-9_.-]+)$/', $route, $matches) && $request->getMethod() === 'GET'):
            $request = validate_token($request);
            $scopes = $request->getAttribute('oauth_scopes');
            if (!in_array('users:read', $scopes)) {
                throw new \League\OAuth2\Server\Exception\OAuthServerException('The token is missing the required "users:read" scope.', 6, 'insufficient_scope', 403);
            }
            $username = $matches[1];
            $userRepo = new \Ecosys\OAuth\Model\Repository\UserRepository($dbConnection);
            $user = $userRepo->getUserEntityByIdentifier($username);
            if ($user) {
                // FIX: Wrapped the user object in a 'user' key to make the response consistent.
                $response->getBody()->write(json_encode(['status' => 'success', 'user' => $user]));
            } else {
                $response = $response->withStatus(404);
            }
            break;

        case (preg_match('/^api\/users\/([a-zA-Z0-9_.-]+)$/', $route, $matches) && $request->getMethod() === 'PATCH'):
            $request = validate_token($request);
            $scopes = $request->getAttribute('oauth_scopes');
            if (!in_array('users:update', $scopes)) {
                throw new \League\OAuth2\Server\Exception\OAuthServerException('The token is missing the required "users:update" scope.', 6, 'insufficient_scope', 403);
            }
            $username = $matches[1];
            $body = json_decode((string) $request->getBody(), true);
            $userRepo = new \Ecosys\OAuth\Model\Repository\UserRepository($dbConnection);
            $result = $userRepo->updateUser($username, $body);
            if (is_object($result)) {
                $response->getBody()->write(json_encode(['status' => 'success', 'user' => $result]));
            } else {
                $response = $response->withStatus(400); // Or 500 for database_error
            }
            break;

        case (preg_match('/^api\/users\/([a-zA-Z0-9_.-]+)$/', $route, $matches) && $request->getMethod() === 'DELETE'):
            $request = validate_token($request);
            $scopes = $request->getAttribute('oauth_scopes');
            if (!in_array('users:delete', $scopes)) {
                throw new \League\OAuth2\Server\Exception\OAuthServerException('The token is missing the required "users:delete" scope.', 6, 'insufficient_scope', 403);
            }
            $username = $matches[1];
            $userRepo = new \Ecosys\OAuth\Model\Repository\UserRepository($dbConnection);
            if ($userRepo->deleteUser($username)) {
                $response = $response->withStatus(204);
            } else {
                $response = $response->withStatus(500);
            }
            break;

        // CLIENTS CRUD can be added here...

        default:
            $response->getBody()->write(json_encode(['Application' => 'EcosysOAuthServer', 'status' => 'Running']));
            break;
    }
} catch (\League\OAuth2\Server\Exception\OAuthServerException $e) {
    $response = $e->generateHttpResponse(new Response());
} catch (\Exception $e) {
    $response = $response->withStatus(500)->withBody((new \Laminas\Diactoros\StreamFactory())->createStream(json_encode(['error' => $e->getMessage()])));
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

