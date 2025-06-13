<?php
// token.php

// Ensure this script is not accessed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    die(header('HTTP/1.0 403 Forbidden'));
}

// Include the database handler
require_once 'database.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.0 405 Method Not Allowed');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the POST data
$grant_type = $_POST['grant_type'] ?? null;
$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;
$client_id = $_POST['client_id'] ?? null;
$client_secret = $_POST['client_secret'] ?? null;


// Handle the "password" grant type
if ($grant_type === 'password') {
    // --- Database-driven User Authentication ---

    // 1. Instantiate Database and get connection
    $database = new Database();
    $db = $database->connect();

    // 2. Find the user by username
    try {
        $query = "SELECT username, password, first_name, last_name FROM oauth_users WHERE username = :username";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. Verify user and password
        // The password in the database should be hashed. We use password_verify().
        if ($user && password_verify($password, $user['password'])) {
            
            // --- Token Generation ---
            // This is a simplified token generation. In a full OAuth2 implementation,
            // you would also validate client_id, client_secret and handle scopes.
            // You would also generate a more secure, standard JWT (JSON Web Token).
            
            $access_token = bin2hex(random_bytes(32));
            
            // For now, we will just return the token
            echo json_encode([
                'access_token' => $access_token,
                'token_type'   => 'Bearer',
                'expires_in'   => 3600, // 1 hour
                'user_info'    => [
                    'username'   => $user['username'],
                    'first_name' => $user['first_name'],
                    'last_name'  => $user['last_name']
                ]
            ]);

        } else {
            // Invalid credentials
            header('HTTP/1.0 401 Unauthorized');
            echo json_encode(['error' => 'Invalid credentials']);
        }

    } catch (PDOException $e) {
        header('HTTP/1.0 500 Internal Server Error');
        echo json_encode(['error' => 'A database error occurred.']);
    }

} else {
    // Unsupported grant type
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(['error' => 'Unsupported grant type']);
}