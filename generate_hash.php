<?php

// The password to hash
$password = 'testpass';

// Generate the hash
$hash = password_hash($password, PASSWORD_DEFAULT);

// Output the hash
echo 'Password: ' . $password . '<br>';
echo 'Generated Hash: ' . $hash;

?>