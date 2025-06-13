<?php

// Set the error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Include dependencies and configuration
require_once __DIR__ . '/vendor/autoload.php';
require_once 'config.php';
require_once 'database.php'; // Our PDO database handler
require_once 'resource.php'; // The new resource server logic

/**
 * Manual Autoloader
 */
spl_autoload_register(function ($class) {
    $prefix = 'Ecosys\\OAuth\\';
    $base_dir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});


use Ecosys\OAuth\Controller\TokenController;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Response;

// 2. Initialize Database Connection
$database = new Database();
$dbConnection = $database->connect();

// 3. Create a PSR-7 request object
$request = ServerRequestFactory::fromGlobals(
    $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
);

// 4. Basic Routing
$path = $request->getUri()->getPath();
$basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
$route = str_replace($basePath, '', $path);

$response = new Response();

try {
    switch (true) {
        // Route for issuing tokens
        case ($route === '/token'):
            $controller = new TokenController($dbConnection);
            $response = $controller->issueToken($request);
            break;

        // A new protected route
        case ($route === '/api/profile'):
            // First, validate the token
            $request = validate_token($request);

            // If validation is successful, get user details from the token
            $userId = $request->getAttribute('oauth_user_id');
            $scopes = $request->getAttribute('oauth_scopes');

            // Optional: Check for specific scopes
            if (!in_array('profile', $scopes)) {
                throw new \League\OAuth2\Server\Exception\OAuthServerException(
                    'The token is missing the required "profile" scope.', 6, 'insufficient_scope', 403
                );
            }
            
            // Fetch user info from the database
            $userRepo = new \Ecosys\OAuth\Model\Repository\UserRepository($dbConnection);
            $user = $userRepo->getUserEntityByIdentifier($userId); 

            $response->getBody()->write(json_encode([
                'user_id' => $user->getIdentifier(),
                'username' => $user->getUsername(),
                'scopes' => $scopes,
                'message' => 'Successfully accessed protected profile data.'
            ]));
            break;

        // Default route
        default:
            $response->getBody()->write(json_encode([
                'Application' => 'EcosysOAuthServer',
                'status' => 'Running'
            ]));
            break;
    }
} catch (\League\OAuth2\Server\Exception\OAuthServerException $e) {
    // Catch any OAuth exceptions and generate a proper JSON response
    $response = $e->generateHttpResponse(new Response());
} catch (\Exception $e) {
    // Catch any other exceptions
    $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
    $response = $response->withStatus(500);
}


// Final step: Emit the response
if (!headers_sent()) {
    header(sprintf('HTTP/%s %s %s', $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase()), true, $response->getStatusCode());
    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header(sprintf('%s: %s', $name, $value), false);
        }
    }
}
echo $response->getBody();

