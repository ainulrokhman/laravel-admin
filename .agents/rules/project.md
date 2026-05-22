---
trigger: always_on
glob: "**/*.{php,js,blade.php,json,md}"
description: Core rules for laravel-admin (Laravel 12, Spatie RBAC, Yajra DataTables)
---

# laravel-admin Agent Rules

Always adhere to these architectural, security, and RBAC rules:

## 1. Tech Stack
- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Blade + Bootstrap 5 + Bootstrap Icons. Custom vanilla CSS.
- **RBAC**: Spatie Laravel Permission (`spatie/laravel-permission`)
- **DataTables**: Server-side Yajra DataTables (`yajra/laravel-datatables-oracle`)

## 2. Strict RBAC (Permissions)
- **Convention**: `<resource>-list`, `<resource>-create`, `<resource>-edit`, `<resource>-delete`, `<resource>-show`, `view-<name>`, `manage-<action>`.
- **Controllers**: Authorize methods at the top: `Gate::authorize('<resource>-<action>')`.
- **Routes**: Protect using permission middleware: `->middleware('permission:<resource>-list')`.
- **Blade**: Wrap actions/navigation links in `@can('<permission>') ... @endcan`.
- **Seeders**: Add new permissions to `$permissions` in [RolesAndPermissionsSeeder.php](file:///d:/laragon/www/laravel-admin/database/seeders/RolesAndPermissionsSeeder.php) via `firstOrCreate`.
- **SuperAdmin**: Automatically bypasses checks (`Gate::before` in [AppServiceProvider.php](file:///d:/laragon/www/laravel-admin/app/Providers/AppServiceProvider.php)).

## 3. Yajra DataTables
- Use AJAX loading on empty HTML tables with `#id`.
- Controller: Detect `$request->ajax()`, fetch query (`with` relationships to avoid N+1), use `datatables()->of($query)`, declare `addColumn` and `rawColumns`, end with `->make(true)`.

## 4. Security & Quality
- Block self-deletion (`auth()->id() === $user->id`).
- Prevent changing email/deleting seed account `superadmin@example.com`.
- Always hash passwords with `Hash::make()`.
- Use CSRF `@csrf` and validate requests.
