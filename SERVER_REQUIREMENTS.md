# Server requirements and safe cPanel deployment

## Runtime requirements

- **Minimum PHP:** 8.3.0.
- **Recommended PHP:** latest maintained PHP 8.3 release available in cPanel. PHP 8.4 also works.
- **Composer:** 2.2.0 or later (Composer 2.x recommended).
- **Database:** MySQL or MariaDB, with the database, user, and credentials supplied through the production `.env` file. The default configuration uses `mysql` with database-backed cache, sessions, and queues; run migrations before serving traffic.
- **Required PHP extensions:** `ctype`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `PDO`, `pdo_mysql`, `session`, and `tokenizer`.

For the full development dependency check and automated tests, also enable `pdo_sqlite`, `xml`, and `xmlwriter`. `redis`, `memcached`, `gd`, `imagick`, and `pcntl` are optional unless their corresponding Laravel driver or feature is enabled.

The project stays on Laravel 13, which requires PHP 8.3. Symfony 8 is intentionally blocked in `composer.json`: its current releases require PHP 8.4.1, while Symfony 7.4 is compatible with Laravel 13 and PHP 8.3. This is a dependency-resolution guard, not a `config.platform` override.

## Check the CLI PHP selected by cPanel

Open the cPanel Terminal or SSH session in the project directory and run:

```bash
php -v
which php
readlink -f "$(command -v php)"
php check-server.php
```

The final command must report `Overall status: PASS`. A `FAIL` exit status means do not run Composer or Artisan until the selected PHP binary/extensions are corrected.

On servers using EasyApache, the PHP 8.3 CLI binary is often at `/opt/cpanel/ea-php83/root/usr/bin/php`; on other hosts it may be named `php83`. Confirm the exact path with the hosting provider, then use that same binary for every command, for example:

```bash
/opt/cpanel/ea-php83/root/usr/bin/php check-server.php
/opt/cpanel/ea-php83/root/usr/bin/php /usr/local/bin/composer install --no-dev --prefer-dist --optimize-autoloader
```

Do not change the account-wide default PHP merely for this application. In cPanel, set PHP 8.3 for this domain only through **MultiPHP Manager** or **Select PHP Version**, then enable the required extensions for that selected version.

## Verify the PHP used by the website

`php -v` reports the **CLI** PHP only. PHP-FPM, LiteSpeed, or Apache can run the domain with a different PHP version.

1. In cPanel, verify the domain's PHP version in MultiPHP Manager / Select PHP Version.
2. Temporarily create a file inside the configured document root containing `<?php echo PHP_VERSION;`, request it through the domain over HTTPS, and remove the file immediately after checking it.
3. The browser result must be PHP 8.3.0 or later and must match the extension configuration selected for the domain.

Never leave `phpinfo()` or a version diagnostic file publicly accessible.

## Build the deployment ZIP

Build frontend assets locally first if the server will not run Node.js:

```bash
npm ci
npm run build
```

From the project root, create a source deployment archive. It excludes secrets, Git metadata, local dependencies, tests, IDE/agent files, and other development-only files while retaining `public/build`:

```bash
zip -r ../realisasi-fisik-kominfo-deploy.zip . \
  -x '.env' '.env.production' '.env.local' '.git/*' 'node_modules/*' 'vendor/*' \
  'tests/*' '.github/*' '.agents/*' '.codex/*' '.cursor/*' \
  '.idea/*' '.vscode/*' '.phpunit.cache/*' 'storage/logs/*' \
  'storage/framework/cache/*' 'storage/framework/sessions/*' \
  'storage/framework/views/*' 'database/*.sqlite*'
```

Do not place a production `.env` in this ZIP. Create it directly on the server from `.env.example` and enter the server-specific secrets there.

## Deployment commands

Extract the archive outside the web root where possible, configure the domain document root to `<project>/public`, and create the production `.env`. Ensure `storage/` and `bootstrap/cache/` are writable by the web-server user.

Then use the PHP 8.3 CLI binary verified above:

```bash
php -v
which php
php check-server.php
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Run `php artisan db:seed --class=AdminUserSeeder --force` only when creating the intended initial admin account; do not run it blindly on an established production database. If database queues are used, configure a persistent queue worker through the host's process manager or cron policy.

After deployment, verify the site over HTTPS. If assets are missing, confirm `public/build` was included in the ZIP; otherwise rebuild with `npm run build` before packaging.
