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

## Docker environment — avoid `touch(): Utime failed: Operation not permitted`

This app runs inside Docker (container `dpmptsp-app`), and the host has **no PHP/Composer binary at all** — `php`/`composer` on the bare host will fail with "command not found". Every PHP-related command (artisan, composer, tinker, `php -l`) MUST be run via `docker exec dpmptsp-app <command>`, never directly on the host path.

**Root cause of the `touch()` / `Utime failed: Operation not permitted` error**: cache/compiled files under `storage/framework/views/*`, `storage/framework/cache/*`, and `bootstrap/cache/*` end up owned by **mixed UIDs** — some created by `www-data` (real HTTP requests served by php-fpm/nginx inside the container) and some by `root` (whenever a command is run via `docker exec` as root, or a host-side file tool writes/touches the same path directly). When a later `php artisan view:cache`/`config:cache`/`optimize` (or Laravel's own view compiler on a request) tries to update the mtime of a file it does not own, `utime()` can fail with `EPERM` even though the process looks privileged — this is especially likely across the host-bind-mount boundary used by this sandbox, where "root" on the host side does not have the same effective capabilities as root inside the container.

**How to avoid it going forward:**

1. Never run `php artisan ...`, `composer ...`, or write directly into `storage/` or `bootstrap/cache/` from host-side file tools (`Write`/`Edit`/raw `touch`/`rm` on the host path). Always go through `docker exec dpmptsp-app php artisan ...`.
2. If cache needs refreshing after code changes, do it **inside the container** and prefer the clear-then-rebuild pair, not manual deletion of individual cache files:
   ```sh
   docker exec dpmptsp-app php artisan optimize:clear
   docker exec dpmptsp-app php artisan view:cache
   ```
3. If the error still appears, it means ownership is already mixed — fix it once from inside the container (which has real root) rather than from the host side:
   ```sh
   docker exec dpmptsp-app chown -R www-data:www-data storage bootstrap/cache
   ```
4. Do not `rm`/`touch` files under `storage/framework/views`, `storage/framework/cache`, or `bootstrap/cache` from host tools as a "fix" — that recreates the same mixed-ownership problem with whatever UID the host tool runs as.
