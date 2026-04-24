<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## A2A Educational CRM

Multi-tenant CRM for educational institutions built on Laravel 13. Covers lead management, application pipeline, counselling, alumni, analytics, and AI-assisted workflows.

### Required Environment Variables

```env
APP_KEY=base64:...
APP_ENV=production
DB_CONNECTION=mysql
DB_DATABASE=a2a_crm
DB_USERNAME=...
DB_PASSWORD=...
REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
ANTHROPIC_API_KEY=sk-ant-...
```

### Deployment Checklist

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=Database\\Seeders\\CRM\\Compliance\\ComplianceRolePermissionSeeder --force
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan horizon:terminate   # then restart Horizon via supervisor
```

### Queue Workers (Laravel Horizon)

Start Horizon to process queued jobs (AI transcription, email campaigns, notifications):

```bash
php artisan horizon
```

Horizon dashboard is available at `/horizon` (requires super-admin role). Supervisor configuration is in `config/horizon.php` with 11 queue supervisors tuned per workload.

### API Documentation

Interactive API docs (Scribe) are available at `/docs` after running:

```bash
php artisan scribe:generate
```

OpenAPI spec and Postman collection are in `storage/app/private/scribe/`.

### Health Endpoint

`GET /health` — returns JSON with database/Redis/queue status. Returns `200 ok` or `503 degraded`. No authentication required (for load balancer probes).

### MFA

Admins (`institution-admin`, `admissions_manager`, `super-admin`) are required to set up TOTP MFA on first login. Use `DELETE /crm/mfa/disable/{user}` to disable MFA for a user (requires super-admin authorization). Emergency IP whitelist reset: `php artisan crm:admin:clear-ip-whitelist --institution={id}`.

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
