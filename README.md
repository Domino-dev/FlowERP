# FlowERP

Simple mini ERP web application for managing customers, products, prices and invoices.
The project demonstrates typical business workflows used in small companies.

> **Status:** still in development.

## Features

- **Customers** – contact details, billing and delivery address, default price list and due days
- **Products** – catalogue codes, names and VAT rates
- **Price lists & prices** – multiple currencies, prices valid for a given period, one default price list
- **Invoices** – draft / issued / paid / cancelled states, items with discounts and VAT, customer data snapshot stored on the invoice
- **Invoice PDF** – generated on the fly (A4, UTF-8 font, VAT recapitulation, multi-page tables)
- **Company & users** – company details used on invoices, user accounts with roles
- AJAX search and pagination in all lists, inline client-side form validation

## Tech stack

| Area | Technology |
|---|---|
| Language | PHP 8.2+ |
| Framework | [Nette](https://nette.org) 3 (Application, Forms, Latte, Tracy) |
| ORM | Doctrine ORM via [Nettrine](https://contributte.org/packages/nettrine/) |
| Database | MySQL 5.7+ / MariaDB 10.6+ |
| Frontend | Latte templates, jQuery, [Naja](https://naja.js.org), nette-forms |
| PDF | [tFPDF](https://github.com/Setasign/tFPDF) with DejaVu fonts |

## Getting started

### 1. Install dependencies

```bash
composer install
```

### 2. Prepare the database

Create an empty database `flow_erp` (utf8mb4). You can either use a local server (e.g. WAMP/XAMPP with MariaDB)
or run MySQL in Docker:

```bash
docker compose -f docker-compose-without-web.yml up -d   # MySQL on :3306, phpMyAdmin on :8090
```

The default connection in `config/doctrine.neon` is `root` / `password` on `localhost:3306` (matches the Docker setup).
To use different credentials, create **`config/local.neon`** (it is git-ignored and loaded last, so it overrides anything):

```neon
# e.g. WAMP with MariaDB, root without password
nettrine.dbal:
	connections:
		default:
			password: ''
			serverVersion: '11.4.9-MariaDB'
```

### 3. Create the schema and load a user

```bash
php bin/console.php orm:schema-tool:create
php bin/console.php orm:generate-proxies
php bin/console.php doctrine:fixtures:load     # purges the DB and creates the admin user
```

Default login: **`admin@admin.cz`** / **`tester123`**

### 4. Run the web server

The document root is the **`www/`** directory. The app must be reached on host **`localhost`** –
the development config (`config/development.neon`) is selected only for this host.

**WAMP / XAMPP (Apache)** – add an alias, e.g. `C:\wamp64\alias\flowerp.conf`, and restart Apache:

```apache
Alias /flowerp "C:/projects/FlowERP/www"
<Directory "C:/projects/FlowERP/www">
	Options +FollowSymLinks
	AllowOverride All
	Require local
</Directory>

# <Location> is merged after .htaccess, so this keeps the app local-only
<LocationMatch "^/flowerp(/|$)">
	Require local
</LocationMatch>
```

The app is then available at **http://localhost/flowerp/** (`.htaccess` detects the base path automatically).

**Docker web container** – the `web` service in `docker-compose.yml` is not maintained (a local Apache is used instead).
Running the app in it would need the port mapping fixed (Apache listens on `80`), the database host set to `db`
in `config/local.neon` and the environment detection in `app/Bootstrap.php` adjusted for a host other than `localhost`.

## After pulling changes

The project has no migrations yet – the schema is defined by Doctrine attributes in `app/Database/`.
After pulling, update dependencies, the schema and the proxy classes:

```bash
composer install
php bin/console.php orm:schema-tool:update --dump-sql   # review the SQL first
php bin/console.php orm:schema-tool:update --force
php bin/console.php orm:generate-proxies
```

Proxy classes are not generated automatically – regenerate them whenever you clear `temp/cache`.

## Tests

Tests use [Nette Tester](https://tester.nette.org):

```bash
composer tester
```

- `tests/Unit/` – calculations, entities and invoice PDF, no database needed
- `tests/Integration/` – invoice flows, deleting master data and authentication; they run against a separate
  database **`flow_erp_test`** (created automatically, dropped and recreated before every test, your `flow_erp` data is untouched).
  When the database server is not reachable, these tests are skipped.

The test database uses the same connection as the app (`config/doctrine.neon` + `config/local.neon`),
only the database name is overridden in `tests/config.neon`.

## Project structure

```
app/
├── Core/            router
├── Database/        Doctrine entities + repositories
├── Fixtures/        data fixtures (admin user)
├── Forms/           form factories
├── Helpers/         UUID generator, country helper
├── Presentation/    presenters, facades and Latte templates (one folder per module)
├── Resources/       countries.json, currencies.json
├── Security/        authenticator
├── Services/        domain services (creating entities, calculations)
└── Templates/       invoice PDF
config/              Nette + Doctrine configuration
tests/               Nette Tester tests (unit + integration)
www/                 document root – index.php, scripts, styles
```

Request flow: **Presenter → Facade → Service / Repository → Entity**. Facades, services and factories
are registered automatically (`config/services.neon`).

## Notes

- Public identifiers in URLs and JavaScript are UUIDs (`internalID`), never numeric IDs.
- Invoices keep their own copy of customer and item data, so editing or deleting a customer or product
  does not change existing invoices. A price list used on an invoice cannot be deleted.
- The PDF uses DejaVu fonts shipped with tFPDF. tFPDF caches font metrics in
  `vendor/setasign/tfpdf/font/unifont/` – keep it writable for faster generation.

## License

See [LICENSE](LICENSE).
