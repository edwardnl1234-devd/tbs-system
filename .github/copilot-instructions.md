## Quick orientation — what this repo is

- This is a Laravel (v12) application skeleton. PHP ^8.2, PSR-4 autoloading (`App\\` → `app/`). See `composer.json` for dependencies and scripts.
- Frontend assets are built with Vite + Tailwind configured in `package.json` / `vite.config.js`.

## Big-picture architecture (how code is organized)

- HTTP layer: routes live in `routes/web.php` and map to controllers in `app/Http/Controllers` (controller base: `app/Http/Controllers/Controller.php`).
- Models live in `app/Models` (e.g. `User.php` shows common casts and `HasFactory`).
- Database migrations are under `database/migrations` and factories under `database/factories`.
- Views are in `resources/views` and frontend JS/CSS in `resources/js` and `resources/css`.
- Entry points: `public/index.php` (web) and `artisan` (CLI).

When making changes: add routes to `routes/web.php`, create controllers under `app/Http/Controllers`, update views in `resources/views`, and migrate schema with files under `database/migrations`.

## Developer workflows & commands (concrete)

- Install & full setup (creates .env, runs migrations and builds assets):

```powershell
composer run-script setup
```

- Run the development stack (server, queue listener, logs, vite watcher) — uses Composer `dev` script which runs `concurrently`:

```powershell
composer run dev
```

- Frontend only:

```powershell
npm install
npm run dev   # dev server / watcher
npm run build # production build
```

- Tests (this project configures PHPUnit to use in-memory SQLite; no DB file required):

```powershell
composer test      # runs: artisan config:clear && php artisan test
# or
php artisan test
```

Note: check `phpunit.xml` — tests run with `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` by default.

## Project-specific patterns & conventions

- PSR-4 namespace root: `App\\` → `app/`. Use this for new classes and tests.
- Models use `HasFactory` (see `app/Models/User.php`) — prefer factories for creating test data.
- Tests live in `tests/Feature` and `tests/Unit`. The base test class is `tests/TestCase.php`.
- Configuration, service registration and bootstrapping follow standard Laravel conventions; `app/Providers/AppServiceProvider.php` is present but empty — add bindings there if you need application-level service wiring.

## Integration points & environment

- Env-driven: database, mailer, queue and other integrations are controlled via env variables (see `phpunit.xml` for the testing defaults). Typical env keys: `DB_CONNECTION`, `QUEUE_CONNECTION`, `MAIL_MAILER`.
- Frontend builds depend on Vite/Tailwind (see `package.json` and `vite.config.js`).

## Files to reference when coding

- Routing / controllers: `routes/web.php`, `app/Http/Controllers/Controller.php`
- Models / factories: `app/Models/*`, `database/factories/*`
- Migrations: `database/migrations/*`
- Frontend: `resources/js`, `resources/css`, `vite.config.js`, `package.json`
- Scripts & setup: `composer.json` (scripts: `setup`, `dev`, `test`), `artisan`
- Tests: `phpunit.xml`, `tests/Feature`, `tests/Unit`

## How Copilot/AI should behave here (short rules)

1. When modifying HTTP behavior, update `routes/web.php` and add a controller in `app/Http/Controllers`; also add or update a Blade view in `resources/views` when UI changes are involved.
2. For data model changes, add a migration in `database/migrations` and a factory in `database/factories` for tests. Prefer using factories in new tests.
3. Run `composer test` after any backend change. Tests use an in-memory SQLite DB — avoid instructions that assume MySQL or an existing DB unless user adds a connection.
4. For frontend changes, update `resources/js`/`resources/css` and run `npm run dev` for local iteration; run `npm run build` in pull-requests that change assets.
5. Keep changes small and run the relevant tests (unit/feature) locally. If you change service bindings, update `app/Providers/*` and add tests that assert behavior.

---

If anything here looks incomplete or you'd like more detail on dev environment notes (Docker/Sail, CI commands, or how to run the concurrent dev script on Windows), tell me which parts to expand and I'll iterate.
