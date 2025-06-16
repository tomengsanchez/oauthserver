<?php

/**
 * EcosysOAuthServer Database Migration Runner
 *
 * This script functions like a simple version of Laravel's migration system.
 * It scans a 'migrations' directory for .sql files, executes any that have
 * not yet been run, and tracks the executed migrations in the database.
 *
 * This version removes explicit transaction handling to avoid conflicts with
 * SQL commands (like CREATE TABLE) that cause implicit commits.
 *
 * Usage:
 * 1. Create a 'migrations' directory in the project root.
 * 2. Place your .sql files in the 'migrations' directory, prefixed with numbers
 * to control the execution order (e.g., '001_create_tables.sql').
 * 3. Run this script from your web browser.
 *
 * IMPORTANT: For security, it is still recommended to delete or protect
 * this file on a production server after use.
 */

header('Content-Type: text/plain; charset=utf-8');

// --- SETUP AND CONFIGURATION ---

set_time_limit(300);

define('CONFIG_FILE', 'config.php');
define('DATABASE_FILE', 'database.php');
define('MIGRATIONS_DIR', __DIR__ . '/migrations');

// Check for required files and directory
if (!file_exists(CONFIG_FILE)) die("FATAL ERROR: config.php not found.\n");
if (!file_exists(DATABASE_FILE)) die("FATAL ERROR: database.php not found.\n");
if (!is_dir(MIGRATIONS_DIR)) die("FATAL ERROR: 'migrations' directory not found.\n");

require_once CONFIG_FILE;
require_once DATABASE_FILE;


// --- DATABASE CONNECTION ---

echo "Attempting to connect to the database...\n";
echo "Host: " . DB_HOST . " | Database: " . DB_NAME . "\n\n";

try {
    $dsn = DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    echo "✅ SUCCESS: Database connection established.\n\n";
} catch (PDOException $e) {
    http_response_code(500);
    die("❌ DATABASE CONNECTION FAILED: " . $e->getMessage() . "\n");
}


// --- MIGRATION LOGIC ---

try {
    // 1. Create migrations tracking table if it doesn't exist
    echo "Ensuring 'migrations' tracking table exists...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL,
        `ran_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY (`migration`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ Tracking table is ready.\n\n";

    // 2. Get the list of migrations that have already been run
    $ran_migrations_stmt = $pdo->query("SELECT `migration` FROM `migrations`");
    $ran_migrations = $ran_migrations_stmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Get all available .sql migration files from the directory
    $all_migration_files = glob(MIGRATIONS_DIR . '/*.sql');
    sort($all_migration_files); // Ensure alphabetical/numerical order

    if (empty($all_migration_files)) {
        echo "No migration files found in the 'migrations' directory.\n";
    }

    $migrations_to_run = [];
    foreach ($all_migration_files as $file) {
        $filename = basename($file);
        if (!in_array($filename, $ran_migrations)) {
            $migrations_to_run[] = $file;
        }
    }

    // 4. Run all pending migrations
    if (empty($migrations_to_run)) {
        echo "Database is already up to date. No new migrations to run.\n";
    } else {
        echo "Found " . count($migrations_to_run) . " new migration(s) to run.\n";
        
        foreach ($migrations_to_run as $file) {
            $filename = basename($file);
            echo "\n--------------------------------------------------\n";
            echo "Running migration: $filename\n";
            echo "--------------------------------------------------\n";

            try {
                // Execute the entire SQL file.
                $sql = file_get_contents($file);
                if ($sql === false) {
                    throw new Exception("Could not read file: $filename");
                }
                $pdo->exec($sql);
                
                // If execution was successful, log it in the migrations table.
                $insert_stmt = $pdo->prepare("INSERT INTO `migrations` (`migration`) VALUES (?)");
                $insert_stmt->execute([$filename]);

                echo "✅ SUCCESS: Executed and logged '$filename'.\n";

            } catch (Exception $e) {
                echo "❌ ERROR: Failed to run migration '$filename'.\n";
                echo "Error Details: " . $e->getMessage() . "\n\n";
                // Stop execution on failure
                exit;
            }
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    die("❌ A FATAL ERROR OCCURRED: " . $e->getMessage() . "\n");
}


echo "\n==================================================\n";
echo "🎉 MIGRATION PROCESS COMPLETE! 🎉\n";
echo "==================================================\n";
echo "SECURITY WARNING: Please delete or protect this migration.php file from your server.\n";

?>
