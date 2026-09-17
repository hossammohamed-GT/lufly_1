# Architecture

Lufly Platform is an enterprise foundation written in **native PHP 8.2+** — no Laravel,
Symfony or CodeIgniter. It runs on XAMPP (Apache + PHP + MySQL) and borrows proven
ideas from modern frameworks while staying dependency-free.

## Layered overview

```
HTTP request
   │  Apache (.htaccess) → public/index.php (front controller)
   ▼
bootstrap.php            autoloader, .env, config, exception handler, providers
   ▼
Core\Http\Kernel         binds Request, dispatches via Router, renders exceptions
   ▼
Core\Http\Router         route matching, groups, params, localized routes
   ▼
Middleware pipeline      web: locale → csrf → security · api: api.log
   ▼
Module Controller        thin HTTP layer; delegates to services
   ▼
Service layer            business logic, validation orchestration, logging
   ▼
Repository layer         data access over Model / QueryBuilder
   ▼
MySQL (PDO)              core/Database connection + query log → database.log
```

## Directory map

| Path | Responsibility |
| --- | --- |
| `core/` | Internal framework: container, router, http, db, validation, logging, exceptions, view, auth, localization, console |
| `app/` | Application composition root: providers, base controller, shared services, platform models, repository base |
| `modules/` | Feature modules (Products, Users, Languages, Media, Settings, SEO, Authentication, Permissions, Notifications) |
| `config/` | Configuration files, consumed through `config('file.key')` |
| `database/` | `migrations/`, `seeders/`, `schema/` (declarative table definitions), `erd/` |
| `resources/` | `lang/{en,tr,cs}` static translations, `views/` shared layouts, components, error pages |
| `frontend/` | Design system (`design-system/style.css`) + per-component CSS/JS |
| `admin/` | Admin panel assets + docs |
| `routes/` | `web.php`, `admin.php`, `api.php` |
| `storage/` | `logs/`, `cache/`, `uploads/` (git-ignored) |
| `docs/` | This documentation + ADRs |

## Module anatomy

```
modules/Products/
├── Controllers/        ProductController.php, Api/ProductApiController.php
├── Services/           ProductService.php
├── Repositories/       ProductRepository.php
├── Models/             Product.php, ProductTranslation.php
├── Routes/routes.php   registers web + admin + api routes
├── Requests/           StoreProductRequest.php, UpdateProductRequest.php
└── Views/              index.php, show.php, Admin/index.php, Admin/form.php
```

Modules are enabled in `config/modules.php`. Their routes are auto-loaded by
`Core\Foundation\ModuleManager`; their view folders are registered as namespaces
(`products::Admin.form`).

## Key principles

- **SOLID** — single-purpose classes, interfaces in `core/Contracts`, DI everywhere.
- **Dependency Injection** — `Core\Container\Container` autowires constructors;
  bindings registered in `AppServiceProvider`.
- **Repository pattern** — `App\Repositories\Repository` base + module repositories.
- **Service layer** — every reusable behaviour lives in a service (`app/Services`,
  `modules/*/Services`).
- **Clean direction** — modules depend on core contracts, never the other way round
  (Auth depends on `UserProviderInterface`, implemented by the Users module).

## Request lifecycle example

`GET /tr/urunler` → Router detects locale `tr` → translates route key
`products.index` through `resources/lang/tr/routes.php` (`urunler`) → matches
`ProductController::index` inside the `web` middleware group → service queries the
repository → view `products::index` renders with the `layouts.frontend` layout.

## CLI

`php cli <command>` — migrate, rollback, seed, schema:dump, erd, docs:api, serve,
key:generate, list. See `docs/Database.md` and `docs/API.md`.
