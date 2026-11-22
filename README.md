# Laravel 12 Admin Panel (RBAC & Yajra DataTables) — SPA Edition

[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-round&logo=laravel)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.2-777BB4?style=flat-round&logo=php)](https://php.net)
[![Vue](https://img.shields.io/badge/Vue-3.x-4FC08D?style=flat-round&logo=vuedotjs)](https://vuejs.org)
[![Inertia](https://img.shields.io/badge/Inertia-3.x-9553E9?style=flat-round)](https://inertiajs.com)
[![AdminLTE](https://img.shields.io/badge/AdminLTE-4.0.0-blue?style=flat-round)](https://adminlte.io)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-round)](https://opensource.org/licenses/MIT)

A professional, feature-rich admin template and starter kit built on **Laravel 12**, styled with **AdminLTE 4 (Bootstrap 5)**, featuring full Role-Based Access Control (RBAC) via **Spatie Laravel Permission**, and server-side paginated tables via **Yajra DataTables**.

> **This branch (`spa`) is the single-page application edition**: the interface is built with **Inertia.js + Vue 3** on top of the same Laravel backend — no jQuery, no full page reloads. The classic server-rendered Blade edition lives on the `master` branch. Both editions share the same controllers, models, permissions, and database structure; they differ in the presentation layer only.

---

## 🚀 Key Features

*   **Modern Admin Interface**: Beautiful layout built using **stock AdminLTE 4** (no custom CSS) with a fully responsive sidebar, header, and dark/light theme toggle.
*   **SPA Experience**: Pages are Vue 3 components served through **Inertia.js** — navigation happens without full page reloads while authentication stays on Laravel's standard session guard.
*   **Robust RBAC System**: Full role and permission management using **Spatie Laravel Permission**.
*   **High Performance Data Handling**: Server-side pagination, search, and sorting via **Yajra DataTables** endpoints, rendered by a built-in Vue table component speaking the standard DataTables JSON protocol.
*   **Secure Authentication**: Streamlined user login and logout flows out-of-the-box.
*   **User Profile & Avatar Settings**: Independent profile management page allowing users to update their personal details, upload a profile picture, and change passwords securely.
*   **Audit Activity Logging**: Comprehensive activity tracking utilizing **Spatie Laravel Activitylog** to monitor all creations, edits, and deletions of key models, complete with a detail modal comparing old and new values and highlighting the changed rows.
*   **Developer Friendly Scripting**: Custom automated project installation commands and concurrency-enabled multi-service local development runner.
*   **Clean & Secure Codebase**: Strict security checks preventing self-deletion of logged-in accounts, safeguarding seed accounts, and validating all requests.

---

## 🛠️ Tech Stack

*   **Backend**: Laravel 12 (PHP 8.2+)
*   **Frontend**: Vue 3 + Inertia.js 3 + Bootstrap 5 + Bootstrap Icons + AdminLTE 4 (stock styling, no custom CSS, no jQuery)
*   **Asset Bundler**: Vite (with `@vitejs/plugin-vue`)
*   **SPA Bridge Package**: `inertiajs/inertia-laravel`
*   **RBAC Package**: `spatie/laravel-permission`
*   **DataTables Package**: `yajra/laravel-datatables-oracle`
*   **Activity Logging Package**: `spatie/laravel-activitylog`

---

## 📋 Prerequisites

Before setting up the repository, make sure you have the following installed on your machine:

*   **PHP** >= 8.2 (with required extensions: `pdo`, `mbstring`, `openssl`, `xml`, etc.)
*   **Composer**
*   **Node.js** (LTS version recommended) & **NPM**
*   **Database** (MySQL, SQLite, or PostgreSQL)

---

## ⚙️ Installation & Setup

Follow these steps to set up the project locally:

### 1. Clone the Repository
```bash
git clone https://github.com/ainulrokhman/laravel-admin.git
cd laravel-admin
git checkout spa
```

### 2. Fast-Track Installation (Recommended)
This repository includes a custom Composer script that automates the setup process (installs composer/npm dependencies, creates `.env`, generates app keys, runs migrations, and builds assets).
```bash
composer run setup
```

*Or perform the steps manually:*
```bash
# Install PHP dependencies
composer install

# Create environment configuration file
cp .env.example .env

# Generate application key
php artisan key:generate

# Install NPM dependencies
npm install

# Build static assets
npm run build
```

### 3. Configure Database
Update the database connection details in your `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_admin
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Database Migrations & Seeding
Run migrations and seed default roles, permissions, and demo users to the database:
```bash
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
```

### 5. Running the Application
Start the development server. The repository includes a helper script that starts the Artisan local server, Laravel queue listener, Pail logging output, and Vite hot reload server concurrently:
```bash
composer run dev
```

---

## 🔑 Default Credentials

The database seeder configures three roles and demo users with the default password `password`:

| User Role | Email | Password | Allowed Permissions |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `superadmin@example.com` | `password` | *All privileges (bypasses checks)* |
| **Admin** | `admin@example.com` | `password` | User CRUD, Role CRUD, view permissions, view dashboard |
| **Regular User** | `user@example.com` | `password` | View dashboard only |

> [!WARNING]
> **Demo accounts — never use them in production.** These seeded accounts, including the quick-login buttons on the login page, exist for local development and demos only, and their credentials are public in this README. Do **not** run the seeder on a production server. If you already did, change these passwords immediately or delete the demo accounts.

---

## 🛡️ Architecture & Guidelines

To maintain code security, authorization uniformity, and project style consistency, please follow these rules during development:

### 1. Permission Naming Convention
*   CRUD permissions follow the `<resource>-list`, `<resource>-create`, `<resource>-edit`, `<resource>-delete`, `<resource>-show` pattern.
*   Custom action permissions use `view-<name>` or `manage-<action>`.
*   Ensure all new permissions are registered in `$permissions` within [RolesAndPermissionsSeeder.php](database/seeders/RolesAndPermissionsSeeder.php) via `firstOrCreate`.

### 2. Strict Access Checks
*   **Controller Level**: Authorize methods at the top using `Gate::authorize()`:
    ```php
    public function index()
    {
        Gate::authorize('user-list');
        // ...
    }
    ```
*   **Routes Level**: Protect routes using Spatie permission middleware:
    ```php
    Route::resource('users', UserController::class)->middleware('permission:user-list');
    ```
*   **Vue components**: The current user's permissions are shared with every page through Inertia props (`auth.permissions`). Hide unauthorized actions and navigation links with the `can()` helper:
    ```vue
    <Link v-if="can('user-create')" href="/admin/users/create" class="btn btn-primary">Add User</Link>
    ```

### 3. Server-Side DataTables Implementation
All main data lists load dynamically and asynchronously from Yajra DataTables endpoints:
*   In the controller, load relationships eagerly to prevent N+1 queries, construct DataTables using `datatables()->of($query)`, and return **structured row data** (plain values plus an `actions` payload with URLs and disabled reasons) — never pre-rendered HTML columns. The Vue `ServerTable` component renders the cells.
*   **Inertia visits are also AJAX requests.** The DataTables branch must exclude them, otherwise SPA navigation to a list page receives table JSON instead of the page:
    ```php
    if ($request->ajax() && ! $request->header('X-Inertia')) {
        // return the DataTables JSON response
    }

    return Inertia::render('Users/Index');
    ```

### 4. Security Rules
*   **Self-Deletion Safeguard**: Users must not be allowed to delete their own accounts:
    ```php
    if (auth()->id() === $user->id) {
        return back()->with('error', 'You cannot delete your own account.');
    }
    ```
*   **Super Admin Protection**: The email or record of `superadmin@example.com` must be protected from deletion or modifications.
*   **Hashing**: Always hash user passwords during creation or updates using `Hash::make()`.
 
 ### 5. Audit Logging & Custom Models
 *   For core database models (e.g. `User`, `Role`, `Permission`) requiring change-tracking, implement the `Spatie\Activitylog\Traits\LogsActivity` trait and define the `getActivitylogOptions()` method.
 *   To track third-party vendor models like Spatie's `Role` and `Permission`, extend Spatie's base models under `App\Models\Role` and `App\Models\Permission` and point to them in `config/permission.php`.
 *   Access to Activity Logs is restricted using the `activity-log-list` permission.

---

## 🧪 Testing

The test suite runs against its own MySQL database, as configured in `phpunit.xml` (database `laravel-admin-testing`, user `root`, password `root`). Create that database first, then run:

```bash
# Install PHP dependencies
composer install

# Build the frontend assets (required: tests that render views
# fail without the Vite manifest)
npm ci
npm run build

# Run the full test suite
php artisan test
```

The same suite also runs automatically on GitHub Actions for every push and pull request to `master`.

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).
