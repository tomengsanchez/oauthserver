-- install_data.sql (Idempotent Version)
-- This version uses `INSERT IGNORE` to prevent errors if the script is run more than once.
-- It will simply skip inserting rows where the primary key already exists.

--
-- Insert a sample client
--
INSERT IGNORE INTO `oauth_clients` (`client_id`, `client_secret`, `name`, `redirect_uri`, `grant_types`, `is_confidential`) VALUES
('testclient', 'testsecret', 'Test Client App', 'https://ithelp.ecosyscorp.ph/etc-backend/callback', 'password,client_credentials,refresh_token', 1);

--
-- Insert a sample user
-- The password is 'testpass', hashed with PHP's password_hash() using PASSWORD_BCRYPT.
--
INSERT IGNORE INTO `oauth_users` (`username`, `password`, `first_name`, `last_name`, `email`) VALUES
('testuser', '$2y$10$KK897gGGEAqKmnKiWl938uMZd1/M2wWr7ESfiB2Hs4LSsM8NYdiwe', 'Test', 'User', 'test@example.com');

--
-- Insert some sample scopes
--
INSERT IGNORE INTO `oauth_scopes` (`scope`, `description`) VALUES
('basic', 'Grants basic read access'),
('profile', 'Grants access to user profile information'),
('email', 'Grants access to user email address'),
('users:create', 'Allows the creation of new users'),
('clients:create', 'Allows the creation of new client applications'),
('users:read', 'Allows reading user information'),
('users:update', 'Allows updating user information'),
('users:delete', 'Allows deleting users'),
('users:list', 'Allows the listing of users. Just filtered via the API');


--
-- Update the test client to include all the new scopes.
--
UPDATE `oauth_clients`
SET `scope` = 'profile users:create clients:create users:read users:update users:delete users:list'
WHERE `client_id` = 'testclient';