-- This migration updates the access tokens table to use the JWT ID (jti) as the primary key.
-- This is essential for the token revocation (logout) feature to work correctly with the
-- league/oauth2-server library, which checks for revocation using the token's 'jti' claim.

-- Step 1: Remove all existing tokens. In a live environment, a more careful data migration
-- would be needed, but for development, clearing them is the simplest way to avoid
-- constraint violations.
TRUNCATE TABLE `oauth_access_tokens`;
TRUNCATE TABLE `oauth_refresh_tokens`; -- Also truncate refresh tokens as they are linked

-- Step 2: Drop the existing primary key.
-- We check if the primary key exists before dropping to avoid errors on re-runs.
SET @pk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oauth_access_tokens' AND INDEX_NAME = 'PRIMARY');
SET @sql = IF(@pk_exists > 0, 'ALTER TABLE `oauth_access_tokens` DROP PRIMARY KEY;', 'SELECT "Primary key does not exist, skipping drop.";');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 3: Change the primary key column from `access_token` to `jti` and set it as the new primary key.
ALTER TABLE `oauth_access_tokens`
CHANGE `access_token` `jti` VARCHAR(191) NOT NULL,
ADD PRIMARY KEY (`jti`);
