## Quick orientation for AI coding agents

This repository is a Laravel 9 application with custom helper-driven conventions and an asset pipeline managed by Laravel Mix / Tailwind. The goal of this doc is to capture the minimal, concrete knowledge an AI needs to be productive without changing project intent.

Key places to read first
- `composer.json` — packages in use (Spatie permissions, Livewire, Stripe, PayPal, FPDI, yajra datatables, google2fa). Use this to understand 3rd-party APIs.
- `routes/*.php` (notably `routes/web.php`) — routes are split and required from `web.php`; most controllers are wired via `Route::resource` and many middleware use `XSS` and `auth`.
- `app/Helper/helper.php` — single-file collection of globally used helpers (settings(), settingsKeys(), parentId(), smtpDetail(), assignSubscription(), etc). These functions mutate runtime config and are central to multi-tenant / owner behaviors — read before refactors.
- `app/Services/*` — business logic collocated in services (e.g. `ScannerService.php`, `FileIndexingBatchService.php`, `SUAFileNumberService.php`). Prefer calling/using these services rather than duplicating logic.
- `resources/views/**/*.blade.php` — UI is blade-based; many legacy view files exist. Check for inline JS that ties to server routes.

Architecture / big picture (brief)
- Laravel MVC web app extended with a set of Services for domain logic (app/Services). Controllers orchestrate HTTP + services.
- Multi-tenant-ish behavior: `parentId()` and `settings()` read DB-stored settings scoped by `parent_id` (owner fallback = 1). Helpers change config() values at runtime (e.g., mail settings via `smtpDetail()`).
- Routes are modularized: `routes/web.php` `require`s many supporting route files (e.g. `file_numbers.php`, `recertification_routes.php`). When adding routes, follow that pattern.

Developer workflows & concrete commands
- Install PHP deps: `composer install` then `php artisan key:generate` (composer post-create hooks try to copy .env automatically). On Windows PowerShell use: `composer install; php artisan key:generate`.
- Frontend build: `npm install` then `npm run dev` (or `npm run production` for prod). Assets compiled with `webpack.mix.js` and Tailwind.
- Run tests: `vendor/bin/phpunit` or `php artisan test`. `phpunit.xml` config sets testing env; DB in-memory is commented — enable either a test sqlite DB or set test DB env vars before running tests.
- Common artisan tasks: `php artisan migrate` (migrations in `database/migrations`), `php artisan db:seed` or custom seeders if present. Check `database_scripts/` for raw SQL the project sometimes relies on.
- Debugging: logs in `storage/logs/laravel.log`. Controllers and helpers use `Log::` and `dd()` in places — follow existing patterns.

Project-specific conventions and gotchas (explicit)
- Many global helper functions are used directly across codebase (see `app/Helper/helper.php`). Do not replace these with dependency-injected services unless you update all call sites.
- Settings are stored in DB and merged into runtime via `settings()` on most requests. Changing configuration files (config/*.php) without reconciling with `settings()` can produce surprising behavior.
- Authorization uses `spatie/laravel-permission` and role types stored on `users`. The helper `parentId()` is used heavily to scope queries — respect that when writing queries.
- Middleware `XSS` is applied in many route groups. Search for `XSS` middleware implementation if sanitization behavior is relevant to your changes.
- Routes: new route files are `require`d into `routes/web.php` rather than added inline. Follow existing grouping for consistency.

Integration & external systems to note
- Payments: Stripe, PayPal, Flutterwave integrations exist — sensitive keys are expected in DB settings. See `subscriptionPaymentSettings()` and payment controller routes.
- Mail: runtime mailer configuration is set by `smtpDetail()` using DB settings — don't hardcode mail config changes without considering this override.
- PDF generation: uses `setasign/fpdf` & `setasign/fpdi` (PDF composition). Be careful with binary-safe output and headers.

How to make safe, useful changes (rule-of-thumb)
- Small, incremental changes: prefer adding unit/feature tests (where possible) and run `php artisan test`.
- When modifying a helper (like `settings()`), search the repo for call sites first. These functions drive UI defaults and runtime config.
- Prefer to call methods from `app/Services/*` instead of copying their logic into controllers.
- If updating routes, add them as separate route files and require them from `routes/web.php` like the existing pattern.

Examples to reference
- Look at `routes/web.php` lines that `require __DIR__ . '/recertification_routes.php'` — follow that pattern when adding grouped routes.
- See `app/Helper/helper.php::settings()` — it fetches settings by `parent_id` and then calls `config()` to set runtime mail/captcha options.
- Asset build: `package.json` scripts include `npm run dev` -> `mix` (webpack.mix.js compiles `resources/js/app.js` and `resources/css/app.css`).

If something is missing
- Ask for the specific environment details (DB MS, seed data, expected owner id) if your change touches DB or auth.

Thanks — I added this file to the repo. Tell me any unclear points or other files you want the instructions to reference and I will iterate.
