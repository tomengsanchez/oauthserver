/* This is the data installer */-- install_data.sql

--
-- Insert a sample client
-- This client will be used by your applications to authenticate against the OAuth server.
-- It's a "confidential" client, meaning it has a client_id and a client_secret.
-- It's allowed to use the 'password' and 'client_credentials' grant types.
--
INSERT INTO `oauth_clients` (`client_id`, `client_secret`, `name`, `redirect_uri`, `grant_types`, `is_confidential`) VALUES
('testclient', 'testsecret', 'Test Client App', 'https://ithelp.ecosyscorp.ph/etc-backend/callback', 'password,client_credentials,refresh_token', 1);

--
-- Insert a sample user
-- The password is 'testpass', hashed with PHP's password_hash() using PASSWORD_BCRYPT.
--
INSERT INTO `oauth_users` (`username`, `password`, `first_name`, `last_name`, `email`) VALUES
('testuser', '$2y$10$KK897gGGEAqKmnKiWl938uMZd1/M2wWr7ESfiB2Hs4LSsM8NYdiwe', 'Test', 'User', 'test@example.com');

--
-- Insert some sample scopes
-- Scopes are used to limit an application's access to a user's account.
--
INSERT INTO `oauth_scopes` (`scope`, `description`) VALUES
('basic', 'Grants basic read access'),
('profile', 'Grants access to user profile information'),
('email', 'Grants access to user email address');



INSERT INTO `oauth_scopes` (`scope`, `description`)
VALUES ('users:create', 'Allows the creation of new users');