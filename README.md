# ZoneTec

The ZoneTec online store — a Laravel e-commerce application with a custom
storefront theme, brand management, and a configurable homepage.

## Stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.3+, Laravel 12 |
| Frontend | Vue 3, Tailwind CSS 3, Vite |
| Database | MySQL 8 |

## Requirements

- PHP **8.3** or **8.4** with `calendar`, `curl`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `gd`, `zip`
- Composer 2
- Node.js 18+
- MySQL 8

## Local setup

```bash
composer install

cp .env.example .env
php artisan key:generate
```

Set `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`, then:

```bash
php artisan migrate --seed     # schema, base data, and the default admin user
php artisan storage:link       # exposes uploaded media under public/storage
```

Build the storefront theme:

```bash
cd resources/themes/zonetec
npm ci
npm run build
```

Then start the server:

```bash
php artisan serve
```

The storefront is at `/`; the admin panel is at the path set by
`APP_ADMIN_URL` (`/admin` by default).

> Run `php artisan optimize:clear` after changing anything under `config/`
> or editing service providers — stale caches are the usual cause of
> "my change did nothing".

## Storefront theme

The custom theme lives in [resources/themes/zonetec/](resources/themes/zonetec/)
and is registered in [config/themes.php](config/themes.php) under the code
`zonetec`. It inherits from the default theme, so it only needs to contain
the views it actually overrides — anything absent falls back automatically.

```
resources/themes/zonetec/
├── views/                    # Blade overrides (home, header, footer, carousels)
├── src/Resources/assets/     # CSS, JS, images, fonts
├── tailwind.config.js
└── vite.config.js
```

| Command | Purpose |
| --- | --- |
| `npm run dev` | Vite dev server with hot reload |
| `npm run build` | Production build → `public/themes/shop/zonetec/build` |

Compiled output is committed so deployments do not require a Node
toolchain on the server. Rebuild and commit it whenever theme assets change.

The theme is selected per sales channel in the admin panel under
**Settings → Channels**.

## Project layout

```
app/                  Application-level service providers
config/               Framework and store configuration
database/seeders/     Catalog, CMS page, and theme customization seeders
packages/             Self-contained feature modules (catalog, checkout,
                      sales, customer, CMS, payment, shipping, tax, …)
public/               Web root — point the web server here
resources/themes/     Storefront themes
```

Each module under `packages/` follows the same shape: models and their
proxies, repositories for data access, admin and shop controllers, routes,
Blade views, translations, and a service provider. Database access goes
through repositories rather than models directly.

Adding a module means registering it in both
[bootstrap/providers.php](bootstrap/providers.php) and
[config/concord.php](config/concord.php), then running
`composer dump-autoload && php artisan optimize:clear`.

## Testing

```bash
vendor/bin/pest                    # full suite
vendor/bin/pest --filter="name"    # a single test
vendor/bin/pint                    # fix code style
vendor/bin/pint --test             # check style without writing
```

End-to-end tests use Playwright and live in each package's
`tests/e2e-pw/` directory. They need a running server and a seeded
database.

## Translations

The store ships with 22 locales. When adding a translation key, add it to
every locale in the relevant package's `Resources/lang/` directory — a key
present in only some locales falls back to the raw key string in the UI.

## Deployment

Copy [.env.production.example](.env.production.example) to `.env` on the
server and replace every `CHANGE_ME`. It documents the values that differ
from local development — notably `APP_ENV`, `APP_DEBUG`, the store
timezone, the admin URL path, and the queue driver.

Production also needs:

- A web server with its document root set to `public/`
- HTTPS
- A queue worker: `php artisan queue:work --tries=3`
- A cron entry: `* * * * * php artisan schedule:run`
- Write access to `storage/` and `bootstrap/cache/`

Deploy sequence:

```bash
composer install --no-dev --optimize-autoloader
php artisan storage:link
php artisan migrate --force
php artisan optimize
```

## License

See [LICENSE](LICENSE).
