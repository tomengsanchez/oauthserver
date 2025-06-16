-- install_table.sql

--
-- Table structure for table `oauth_clients`
--
CREATE TABLE `oauth_clients` (
  `client_id` varchar(80) NOT NULL,
  `client_secret` varchar(255) DEFAULT NULL,
  `redirect_uri` varchar(2000) DEFAULT NULL,
  `grant_types` varchar(80) DEFAULT NULL,
  `scope` varchar(4000) DEFAULT NULL,
  `user_id` varchar(80) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `is_confidential` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `oauth_access_tokens`
--
CREATE TABLE `oauth_access_tokens` (
  `access_token` varchar(40) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` varchar(80) DEFAULT NULL,
  `expires` timestamp NOT NULL,
  `scope` varchar(4000) DEFAULT NULL,
  PRIMARY KEY (`access_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `oauth_refresh_tokens`
--
CREATE TABLE `oauth_refresh_tokens` (
  `refresh_token` varchar(40) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` varchar(80) DEFAULT NULL,
  `expires` timestamp NOT NULL,
  `scope` varchar(4000) DEFAULT NULL,
  PRIMARY KEY (`refresh_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `oauth_authorization_codes`
--
CREATE TABLE `oauth_authorization_codes` (
  `authorization_code` varchar(40) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` varchar(80) DEFAULT NULL,
  `redirect_uri` varchar(2000) DEFAULT NULL,
  `expires` timestamp NOT NULL,
  `scope` varchar(4000) DEFAULT NULL,
  `id_token` text,
  PRIMARY KEY (`authorization_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `oauth_scopes`
--
CREATE TABLE `oauth_scopes` (
  `scope` varchar(80) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `oauth_users`
-- Note: This table structure might already exist.
-- If so, you can adapt it, but it must contain at least a username and a hashed password column.
--
CREATE TABLE IF NOT EXISTS `oauth_users` (
  `username` varchar(80) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- Step 1: Drop the existing primary key, as it's invalid for a TEXT column.
ALTER TABLE `oauth_access_tokens` DROP PRIMARY KEY;

-- Step 2: Modify the column type to TEXT and re-add the primary key with a specified length.
-- A length of 191 is used as it's a safe maximum for utf8mb4 encoded columns.
ALTER TABLE `oauth_access_tokens`
MODIFY `access_token` TEXT NOT NULL,
ADD PRIMARY KEY (`access_token`(191));