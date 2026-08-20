# Deployment handover

Everything needed to take this store live. Written for whoever is doing the
server setup and database provisioning.

## What you receive

| Item | How it arrives |
| --- | --- |
| Application code | This git repository |
| `zonetec-db.sql` | Database dump — sent separately (~735 KB) |
| `zonetec-media.tar.gz` | Uploaded images — sent separately (~28 MB, 454 files) |
| Secrets | Sent separately via password manager — see [Secrets](#secrets) |

The database dump and media archive are **not** in the repository and cannot be
regenerated from it. The seeders build the category tree, brands, CMS pages and
homepage layout, but they deliberately create **no products** — the 21 products
and all product imagery exist only in the dump and the archive.

## Server requirements

- PHP **8.3** or **8.4** with `calendar`, `curl`, `intl`, `mbstring`, `openssl`,
  `pdo_mysql`, `tokenizer`, `gd`, `zip`
- Composer 2
- MySQL 8
- Nginx or Apache, document root set to **`public/`**
- HTTPS

A VPS is expected rather than shared hosting: the store needs a queue worker and
a cron entry, and imports run well past a typical shared-hosting
`max_execution_time`. 2 vCPU / 4 GB RAM is a reasonable starting point.

## Deploy

```bash
git clone <repo-url> && cd <dir>
composer install --no-dev --optimize-autoloader
```

Copy `.env.production.example` to `.env` and fill in every `CHANGE_ME`. That
file documents each value and why it differs from development.

```bash
php artisan storage:link
```

### Restore the data

```bash
mysql -u <user> -p <database> < zonetec-db.sql
tar -xzf zonetec-media.tar.gz -C storage/app/
```

The archive expands to `storage/app/public/`. Confirm `public/storage` resolves
to it after `storage:link` — if product images 404, this is why.

The dump already contains the full schema, so **do not** run `migrate --seed`
over a restored database. Run `php artisan migrate --force` only to apply
migrations added after the dump was taken.

```bash
php artisan optimize
```

> **`APP_KEY` matters.** Use the key supplied with the secrets — do not generate
> a new one. Encrypted values in the restored `core_config` (payment gateway
> credentials) become undecryptable under a different key. If a new key is
> unavoidable, payment credentials must be re-entered in the admin panel.

### Runtime

```bash
php artisan queue:work --tries=3          # under supervisor/systemd
* * * * * php artisan schedule:run        # cron
```

`storage/` and `bootstrap/cache/` must be writable by the web server user.

## Change immediately after restore

These carry development values and are wrong on a live host.

| What | Current value | Where |
| --- | --- | --- |
| Channel hostname | `http://localhost:8000` | Admin → Settings → Channels |
| Admin account | `admin@example.com` | Admin → Settings → Users |
| Store timezone | framework default `Asia/Kolkata` | `APP_TIMEZONE` in `.env` |
| Admin panel path | `admin` | `APP_ADMIN_URL` in `.env` |

The channel hostname feeds generated URLs, transactional email links and the
sitemap. Left as-is, all three point at localhost.

The admin account is the stock default and its password is unchanged.

## Store configuration as shipped

Verified against the dump — so nothing here comes as a surprise:

- **Theme:** `zonetec` (compiled assets are committed; no Node build needed on
  the server)
- **Payment:** Cash on Delivery only. Stripe, PayPal, Razorpay, PayU, PhonePe
  and Money Transfer are all inactive and hold no credentials.
- **Shipping:** Free and Flat Rate active
- **Currency / locale:** USD, English
- **Catalog:** 21 products (all in stock), 52 categories, 8 CMS pages
- **Orders / customers:** empty — development data was removed before the dump,
  and order numbering starts at 1

If card payments are wanted, that is gateway credentials plus configuration in
Admin → Configuration → Sales → Payment Methods.

## Outstanding — needed from the store owner

**The 8 CMS pages contain unfilled placeholders** and will publish literal text
like `[COMPANY LEGAL NAME]` on the privacy policy, terms and contact pages:

```
about-us         privacy-policy    terms-conditions   return-policy
refund-policy    payment-policy    shipping-policy    customer-service
```

Required values: company legal name, support email, support phone, WhatsApp
number, store address, commercial register number.

Once known, fill the constants at the top of
`database/seeders/ZoneTecCmsPagesSeeder.php` and run:

```bash
php artisan db:seed --class=Database\\Seeders\\ZoneTecCmsPagesSeeder --force
```

The seeder is idempotent — it matches pages by `url_key` and refreshes all
eight without creating duplicates. Editing the pages in the admin panel works
too, but the values then live only in the database.

## Secrets

Supplied out of band. Never commit these.

- [ ] `APP_KEY` — copy verbatim, see the warning above
- [ ] Database name, user, password
- [ ] SMTP host, port, username, password
- [ ] Sender address — must be on the store's own domain with SPF and DKIM
      configured in DNS, or transactional mail will be filtered as spam

## Smoke test

1. Homepage renders with the ZoneTec theme (logo, hero, promo banners)
2. Category page lists products; product images load
3. Product page → add to cart → checkout → place order
4. Order confirmation email arrives
5. Order appears in the admin panel
6. Admin login works at the configured path
7. `storage/logs/laravel.log` is free of errors after the run
