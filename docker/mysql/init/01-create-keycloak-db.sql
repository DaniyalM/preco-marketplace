-- Create Keycloak database
-- This runs on first MySQL container startup

CREATE DATABASE IF NOT EXISTS keycloak 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

-- Grant permissions (uses the same root user as Laravel app)
-- In production, create a separate user for Keycloak
GRANT ALL PRIVILEGES ON keycloak.* TO 'root'@'%';
FLUSH PRIVILEGES;
