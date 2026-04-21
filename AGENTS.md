# Repository Guidelines

## Project Structure & Module Organization
`index.php`, `login.php`, `register.php`, and related auth pages live at the repository root. Role-specific screens are separated into `buyer/`, `farmer/`, and `da/`. Shared PHP utilities and layout partials are in `includes/`, while request handlers are grouped under `action/DA`, `action/Message`, and `action/Notification`. Frontend assets are split into `css/`, `js/`, `modal/`, and `pic/`. The SMS gateway is an auxiliary Node service in `sms/`. Treat `uploads/` and `node_modules/` as generated/runtime content.

## Build, Test, and Development Commands
Use a local PHP/Apache stack with the repo root as the web directory.

```powershell
php -l .\index.php
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

Run quick syntax checks before committing; CI runs the same idea across all PHP files on PHP 8.2. For the SMS worker:

```powershell
cd .\sms
npm install
node .\sms_server.js
```

Use `run_sms_server.bat` on Windows when the serial modem service should run in its expected local wrapper.

## Coding Style & Naming Conventions
Follow the existing style: 4-space indentation in PHP, braces on the same line, and mixed PHP/HTML templates for page files. Keep filenames lowercase with underscores for multiword scripts such as `reset_password.php` and `send_notification.php`. Put reusable classes in `includes/` with PascalCase names like `Logger.php` and `SMSModel.php`. Prefer small action endpoints in `action/` and keep role-specific UI logic inside its matching directory.

## Testing Guidelines
There is no formal unit test suite yet. Minimum requirement: run PHP lint on every changed `.php` file and manually verify the affected flow in the browser. Focus checks on login, role redirects, messaging/notifications, and any schema-touching code in `includes/db.php`. If you add automated tests later, place them in a top-level `tests/` directory and name files `*Test.php`.

## Commit & Pull Request Guidelines
Recent history uses imperative, sentence-style commit messages, for example `Implement Super Admin role, System Logging, and Profile/Registration UI enhancements`. Keep commits scoped to one feature or fix. PRs should include a short summary, impacted roles or pages, database changes, setup steps, and screenshots for UI updates. Link the related issue when one exists.

## Security & Configuration Tips
Database credentials are currently defined in `includes/db.php`; do not commit real production secrets. Review self-healing schema changes in that file carefully because they run on page load. Keep uploaded files and SMS device settings environment-specific.
