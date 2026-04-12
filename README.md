<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Project Setup: PayMongo Billing (GCash, Maya, Cards)

The project uses PayMongo Checkout Sessions for billing.

### 1. Fill PayMongo values in .env

Set the following values in .env:

- PAYMONGO_PUBLIC_KEY
- PAYMONGO_SECRET_KEY
- PAYMONGO_API_BASE
- PAYMONGO_PAYMENT_METHOD_TYPES
- PAYMONGO_WEBHOOK_TOKEN

Default payment methods value:

- gcash,paymaya,grab_pay,card

### 2. Start the Laravel app

From this project folder:

- php artisan optimize:clear
- php artisan serve --host=127.0.0.1 --port=8000

### 3. Expose local app to the internet for webhooks

PayMongo cannot call localhost directly. Use a tunnel first, for example ngrok:

- ngrok http 8000

Then copy the HTTPS forwarding URL, for example:

- https://your-ngrok-subdomain.ngrok-free.app

### 4. Configure PayMongo webhook endpoint

In PayMongo dashboard, add a webhook endpoint to:

- https://your-ngrok-subdomain.ngrok-free.app/paymongo/webhook?token=YOUR_WEBHOOK_TOKEN

Set YOUR_WEBHOOK_TOKEN to the same value as PAYMONGO_WEBHOOK_TOKEN in .env.

Enable successful payment events, including:

- checkout_session.payment.paid
- payment.paid

### 5. Test checkout

- Sign in as a Barbershop Admin.
- Open Manager dashboard.
- Click any plan Choose button.
- Complete checkout using GCash, Maya, or card in PayMongo test mode.

After a successful payment, webhook processing updates the tenant plan tier and activates the local plan record.

## Versioning and Updates

- Versioning policy: see VERSIONING.md
- Project change history: see CHANGELOG.md
- This repository follows Semantic Versioning (MAJOR.MINOR.PATCH)

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

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
