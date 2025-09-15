# FreeStack - Laravel Livewire Starter Kit

[![Tests](https://github.com/inmanturbo/freestack/actions/workflows/tests.yml/badge.svg)](https://github.com/inmanturbo/freestack/actions/workflows/tests.yml)
[![Install with Herd](https://img.shields.io/badge/Install%20with%20Herd-fff?logo=laravel&logoColor=f53003)](https://herd.laravel.com/new?starter-kit=inmanturbo/freestack)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/inmanturbo/freestack.svg?style=flat-square)](https://packagist.org/packages/inmanturbo/freestack)

A modern Laravel starter kit featuring Livewire, Flux UI, Laravel Passport OAuth2, and EdgeAuth for single sign-on (SSO) behind reverse proxies.

> 🚀 **Quick Start:** Use the Laravel installer with `laravel new your-project --using=https://github.com/inmanturbo/freestack` to create a new project based on this starter kit.

> 🐎 **Try with Laravel Herd:** [Open in Laravel Herd](https://herd.laravel.com/new?starter-kit=inmanturbo/freestack)

## Features

### 🔐 Authentication & Authorization

- **Laravel Passport OAuth2** - Complete OAuth2 server implementation
- **EdgeAuth SSO** - Custom authentication system for apps behind reverse proxies
- **Personal Access Tokens** - API token management with scopes
- **Session Management** - View and manage active user sessions

### 🎨 Modern UI Stack

- **Livewire** - Dynamic interfaces without leaving Laravel
- **Flux UI Pro** - Beautiful, accessible components *(required)*
- **Tailwind CSS** - Utility-first styling
- **Alpine.js** - Minimal framework for UI interactions

### 🛠️ Developer Experience

- **Pest Testing** - Modern PHP testing framework
- **Laravel Pint** - Code style fixer
- **Comprehensive Tests** - API, Feature, and Unit test coverage

## Requirements

- PHP 8.2+
- Laravel 12.x
- MySQL/PostgreSQL
- **Flux UI Pro License** *(currently required)*

## Installation

### Option 1: Using Laravel Installer (Recommended)

If you have the Laravel installer, you can use this starter kit as a template:

```bash
# Install Laravel installer if you don't have it
composer global require laravel/installer

# Create new project using this starter kit
laravel new your-project-name --using=inmanturbo/freestack
cd your-project-name
```

### Option 2: Manual Clone

```bash
git clone <repository-url> your-project-name
cd your-project-name
composer install
npm install
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup

```bash
php artisan migrate
php artisan passport:keys
```

> **Note:** This starter kit includes Passport migrations. Use `passport:keys` to generate encryption keys only.

### 4. Flux UI Pro Setup

This starter kit requires a **Flux UI Pro license**. Please visit [fluxui.dev](https://fluxui.dev) for installation and activation instructions.

### 5. Build Assets

```bash
npm run build
# or for development
npm run dev
```

### 6. Serve the Application

```bash
php artisan serve
```

Visit `http://localhost:8000` and register your first account.

## OAuth2 Setup

### Creating OAuth Applications

1. Navigate to **Settings → OAuth Apps**
2. Click **Create New Application**
3. Enter application name and redirect URIs
4. Copy the **Client ID** and **Client Secret**

### OAuth2 Flow Example

```bash
# Authorization URL
GET /oauth/authorize?client_id={client_id}&redirect_uri={redirect_uri}&response_type=code&scope=read

# Exchange code for token
POST /oauth/token
{
    "grant_type": "authorization_code",
    "client_id": "your-client-id",
    "client_secret": "your-client-secret",
    "redirect_uri": "your-redirect-uri",
    "code": "received-authorization-code"
}
```

## EdgeAuth SSO

EdgeAuth enables SSO for applications behind reverse proxies (nginx, Traefik, etc.).

### How It Works

1. **Reverse Proxy Setup** - Configure your proxy to forward auth requests
2. **Session Tickets** - EdgeAuth issues session-based tickets
3. **Token Exchange** - Apps exchange tickets for API tokens
4. **Seamless Login** - Users authenticate once across all apps

### Reverse Proxy Configuration

#### Nginx Example
```nginx
location /auth {
    internal;
    proxy_pass http://freestack.test/oauth/introspect;
    proxy_pass_request_body off;
    proxy_set_header Content-Length "";
    proxy_set_header X-Original-URI $request_uri;
}

location /app {
    auth_request /auth;
    proxy_pass http://your-app.test;
}
```

#### Traefik Example
```yaml
middlewares:
  auth:
    forwardAuth:
      address: "http://freestack.test/oauth/introspect"
      authResponseHeaders:
        - "X-Auth-User-Id"
```

### EdgeAuth API

```bash
# Initiate SSO redirect
GET /edge/auth?host=app.test&return=/dashboard

# Introspect token (for reverse proxy)
GET /oauth/introspect
Authorization: Bearer edge-secret-token
```

## API Documentation

### Authentication
All API endpoints require authentication via Personal Access Token:

```bash
Authorization: Bearer your-api-token
```

### OAuth Applications
- `GET /api/oauth-applications` - List applications
- `POST /api/oauth-applications` - Create application
- `PUT /api/oauth-applications/{id}` - Update application
- `DELETE /api/oauth-applications/{id}` - Delete application
- `POST /api/oauth-applications/{id}/regenerate-secret` - Regenerate secret

### Personal Access Tokens
- `GET /api/access-tokens` - List tokens
- `POST /api/access-tokens` - Create token
- `PUT /api/access-tokens/{id}` - Update token
- `DELETE /api/access-tokens/{id}` - Delete token

## Development

### Running Tests

```bash
# All tests
./vendor/bin/pest

# Specific test suites
./vendor/bin/pest tests/Feature/Api/
./vendor/bin/pest tests/Feature/Livewire/
```

### Code Style

```bash
# Fix code style
./vendor/bin/pint

# Check code style
./vendor/bin/pint --test
```

### Key Directories

```
app/
├── Http/Controllers/Api/     # API endpoints
├── Http/Middleware/          # Authentication middleware
├── EdgeAuthSession.php       # EdgeAuth implementation
└── UserApiToken.php          # Token management

resources/views/livewire/
├── settings/                 # Settings pages
│   ├── oauth.blade.php      # OAuth app management
│   └── api-tokens.blade.php # Token management

tests/
├── Feature/Api/             # API tests
├── Feature/Livewire/        # Livewire component tests
└── Feature/Auth/            # Authentication tests
```

## Configuration

### OAuth2 Scopes

Configure available scopes in `AppServiceProvider`:

```php
Passport::tokensCan([
    'edge' => 'EdgeAuth gateway access',
    'read' => 'Read user data',
    'write' => 'Write user data',
    'admin' => 'Administrative access',
]);
```

### EdgeAuth Settings

```env
# Allowed hosts for EdgeAuth redirects
EDGE_ALLOWED_HOSTS=app1.test,app2.test

# EdgeAuth secret for introspection
EDGE_SECRET=your-secure-secret
```

### Session Configuration

```env
SESSION_DRIVER=database  # Required for EdgeAuth
SESSION_LIFETIME=120     # Minutes
SESSION_SECURE_COOKIE=true  # HTTPS only
```

## Production Deployment

### Security Checklist

- [ ] Set `APP_ENV=production`
- [ ] Configure HTTPS with valid certificates
- [ ] Set secure session cookies (`SESSION_SECURE_COOKIE=true`)
- [ ] Configure database sessions properly
- [ ] Configure rate limiting
- [ ] Set up log monitoring
- [ ] Regular security updates

### Performance Optimization

```bash
# Optimize for production
php artisan optimize
composer install --optimize-autoloader --no-dev
```

## Disclaimer

This is an independent, community-maintained starter kit. We are not affiliated with, endorsed by, or in any way officially connected to Laravel, Livewire, Flux UI, or any of their subsidiaries or affiliates. The names Laravel, Livewire, and Flux UI as well as related names, marks, emblems, and images are registered trademarks of their respective owners.

## License

This starter kit is open-sourced software licensed under the [MIT license](LICENSE).

**Note:** Flux UI Pro requires a separate commercial license from [Flux UI](https://fluxui.dev).

## Support

- [Laravel Documentation](https://laravel.com/docs)
- [Livewire Documentation](https://livewire.laravel.com)
- [Flux UI Documentation](https://fluxui.dev/docs)
- [Laravel Passport](https://laravel.com/docs/passport)

For issues specific to this starter kit, please open an issue in this repository.