# OTA

OTA is a Laravel-based online travel booking application focused on the flight-search-to-booking lifecycle. It exposes public search and controlled guest booking flows while keeping booking, payment, document, and cancellation operations behind validation and authorization boundaries.

## Core workflows

- Public flight search
- Booking submission and confirmation
- Guest booking access and retrieval
- Customer document downloads
- Payment-proof submission
- Cancellation requests

## Engineering focus

- Clear separation between public discovery and state-changing booking operations
- Validation and authorization around customer actions
- Booking lifecycle workflows backed by Laravel services and models
- Environment-based deployment configuration
- Automated application and browser-flow testing

## Stack

- PHP 8.3
- Laravel 13
- Blade / Vite
- Relational database
- Playwright for browser-flow verification

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

Configure the required database and supplier/integration settings in `.env` before running environment-dependent workflows.

## Testing

```bash
php artisan test
```

Where browser-flow dependencies are configured, the repository also includes Playwright-based verification for end-to-end application behavior.
