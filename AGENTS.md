<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

# AssetKOM

AssetKOM is a Laravel 13 application backed by SQLite.

## Stack

- PHP 8.4, Laravel 13
- Database: SQLite (`database/database.sqlite`, `DB_CONNECTION=sqlite`)
- Frontend/build: Vite + npm
- Tests: PHPUnit (`php artisan test`)

## Setup

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
```

## Common commands

```sh
php artisan serve          # run the app
php artisan test           # run the test suite
php artisan migrate        # run migrations
./vendor/bin/pint          # format PHP (Laravel Pint)
npm run dev                # Vite dev server
```

## Conventions

- Follow standard Laravel conventions (Eloquent models, Form Requests, queues, events).
- Run `./vendor/bin/pint` before committing PHP changes.
- Keep the SQLite database local; do not commit `database/*.sqlite` or `.env`.
