-- Create Keycloak database
-- This runs on first PostgreSQL container startup

CREATE DATABASE keycloak;

-- Grant permissions to the pcommerce user
GRANT ALL PRIVILEGES ON DATABASE keycloak TO pcommerce;

-- Connect to keycloak database and set up schema permissions
\c keycloak
GRANT ALL ON SCHEMA public TO pcommerce;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO pcommerce;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO pcommerce;
