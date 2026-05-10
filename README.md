# Digital Farming Platform System (DFPS)

## Overview
DFPS is a web-based marketplace designed to connect local farmers directly with buyers, overseen by the Department of Agriculture (DA). The platform streamlines produce listings, facilitates direct communication, and provides the DA with monitoring and reporting tools to ensure fair market pricing.

## Features
- **Farmers:** Create and manage produce listings (multi-photo support), receive buyer interests, and communicate via direct messaging.
- **Buyers:** Browse and filter local produce, express interest in products, and message farmers directly.
- **Department of Agriculture (DA):**
    - Monitor users, listings, and market trends.
    - Manage Produce Master List and Suggested Retail Prices (SRP).
    - Post announcements and broadcast alerts (including SMS support).
    - Comprehensive system reports and user management.
    - Database backup and restore (Super Admin only).

## Tech Stack
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend:** PHP 8.2 (using [Flight PHP](https://flightphp.com/)), MySQL
- **SMS Gateway:** Node.js auxiliary service

## Getting Started

### Local Development (PHP built-in server)
1. Ensure PHP 8.2 and MySQL are installed.
2. Clone the repository.
3. Import `db.sql` into your MySQL database named `dfps`.
4. Configure database credentials in `includes/db.php`.
5. Run `composer install`.
6. Start the server:
   ```bash
   php -S localhost:8000 index.php
   ```
7. Access at `http://localhost:8000`.

### Docker Setup
1. Run `docker-compose up --build`.
2. The application will be available at `http://localhost:8000`.
3. The database will be automatically initialized (ensure `db.sql` is imported if not handled by the volume).

## Project Structure
- `da/`, `farmer/`, `buyer/`: Role-specific modules.
- `includes/`: Shared utilities (database connection, security, models, language loaders).
- `action/`: AJAX request handlers for notifications, messaging, and DA actions.
- `css/`, `js/`, `pic/`, `modal/`: Frontend assets and components.
- `sms/`: Node.js SMS gateway source.

## Documentation
- `USER_MANUAL.md`: Detailed guide for system users.
- `INFRASTRUCTURE_ARCHITECTURE.md`: Technical details on system design.
- `PILOT_TESTING.md`: Summary of testing phases and results.

---
*Developed for the Department of Agriculture.*
