# Stateless Authentication Architecture

This application uses a fully stateless authentication architecture with Keycloak as the identity provider.

## Overview

- **No server-side sessions**: All authentication state is stored in JWT tokens within httpOnly cookies
- **Stateless API**: Every request is validated independently by decoding the JWT
- **Multi-tenant**: Tenant ID is extracted from the JWT on each request
- **Scalable**: Horizontal scaling is trivial since no session state needs to be shared

## How It Works

### Authentication Flow

```
1. User clicks "Login"
   ↓
2. Redirect to Keycloak authorization endpoint
   ↓
3. User authenticates with Keycloak
   ↓
4. Keycloak redirects back with authorization code
   ↓
5. Server exchanges code for tokens
   ↓
6. Tokens stored in httpOnly cookies:
   - access_token (short-lived, ~5 min)
   - refresh_token (longer-lived, ~30 min)
   - id_token (for frontend use)
   ↓
7. Subsequent requests include cookies automatically
   ↓
8. Middleware validates JWT and extracts user/tenant context
```

### Token Structure

The JWT access token contains:

```json
{
  "sub": "user-uuid",           // User ID
  "tenant_id": "tenant-uuid",   // Custom claim for tenant
  "email": "user@example.com",
  "name": "John Doe",
  "realm_access": {
    "roles": ["customer"]
  },
  "resource_access": {
    "laravel-app": {
      "roles": ["vendor"]
    }
  }
}
```

## Keycloak Configuration

### Adding Tenant ID to Tokens

1. Go to Keycloak Admin Console
2. Select your realm
3. Navigate to Client Scopes → Create
4. Create a new scope named `tenant`
5. Add a mapper:
   - Name: `tenant_id`
   - Mapper Type: `User Attribute`
   - User Attribute: `tenant_id`
   - Token Claim Name: `tenant_id`
   - Claim JSON Type: `String`
   - Add to ID token: Yes
   - Add to access token: Yes

### Alternative: Hardcoded Mapper

For development, you can use a Hardcoded claim mapper:
- Name: `tenant_id`
- Mapper Type: `Hardcoded claim`
- Token Claim Name: `tenant_id`
- Claim value: `default`
- Claim JSON Type: `String`

### Client Configuration

Ensure your Keycloak client has:
- Access Type: `confidential`
- Standard Flow Enabled: `ON`
- Valid Redirect URIs: `https://your-app.com/auth/callback`
- Web Origins: `https://your-app.com`

## Laravel Configuration

### Environment Variables

```env
KEYCLOAK_BASE_URL=http://keycloak:8080
KEYCLOAK_REALM=your-realm
KEYCLOAK_CLIENT_ID=laravel-app
KEYCLOAK_CLIENT_SECRET=your-secret
SESSION_DRIVER=array
```

### Session Driver

Set `SESSION_DRIVER=array` to use in-memory sessions. This:
- Allows Laravel's session facade to work for flash messages
- Doesn't persist anything to disk/database
- Makes the application truly stateless

## Key Components

### VerifyKeycloakToken Middleware

Located at `app/Http/Middleware/VerifyKeycloakToken.php`

- Reads JWT from `access_token` cookie (or Authorization header)
- Validates token against Keycloak's public keys (cached)
- Extracts user_id, tenant_id, and roles
- Attaches context to request attributes

### StatelessAuthService

Located at `app/Services/StatelessAuthService.php`

- Handles token exchange and refresh
- Creates/clears auth cookies
- Provides helper methods for auth checks

### TenantContext

Located at `app/Services/TenantContext.php`

- Provides access to current tenant and user IDs
- Can be used in models, services, and jobs

## Usage in Controllers

```php
use App\Services\StatelessAuthService;

class MyController extends Controller
{
    public function __construct(
        protected StatelessAuthService $authService
    ) {}

    public function index(Request $request)
    {
        $userId = $this->authService->getUserId($request);
        $tenantId = $this->authService->getTenantId($request);
        $roles = $this->authService->getRoles($request);
        
        if ($this->authService->isAdmin($request)) {
            // Admin logic
        }
    }
}
```

## Usage in Models

Apply the `BelongsToTenant` trait for automatic tenant scoping:

```php
use App\Models\Concerns\BelongsToTenant;

class Product extends Model
{
    use BelongsToTenant;
}
```

This will:
- Automatically set `tenant_id` on creation
- Automatically filter queries by current tenant

## Token Refresh

Tokens are refreshed automatically:

1. Frontend auth store schedules refresh before expiry
2. On 401 with `TokenExpired` error, axios interceptor triggers refresh
3. Refresh endpoint exchanges refresh_token for new access_token
4. New cookies are set in response

## Security Considerations

- Tokens are stored in httpOnly cookies (not accessible to JavaScript)
- Cookies use `SameSite=Lax` and `Secure` flags
- JWT signature is validated against Keycloak's public keys
- Public keys are cached for 1 hour to prevent JWKS endpoint spam
- CSRF protection via OAuth state parameter
