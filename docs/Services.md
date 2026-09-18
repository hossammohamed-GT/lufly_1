# Service Layer

All reusable business logic lives in services; controllers stay thin.
Services are autowired through the container.

## Platform services (`app/Services/`)

| Service | Responsibility | Key methods |
| --- | --- | --- |
| `UploadService` | validate + store uploads | `store($file, $dir)`, `delete($path)` |
| `MediaService` | media library orchestration | `storeFromUpload`, `all`, `find`, `delete` |
| `MailService` | mail dispatch (log/smtp transports) | `send`, `sendToMany` |
| `SEOService` | meta/OG/canonical/JSON-LD state | `setTitle`, `setFromEntity`, `addStructuredData` |
| `SettingsService` | key/value settings + cache + audit | `get`, `set`, `setMany`, `all` |
| `NotificationService` | user notifications | `notify`, `unreadFor`, `markRead`, `markAllRead` |
| `LocalizationService` | languages + dynamic translations (abstract; impl. in Languages module) | `languages`, `syncTranslations`, … |
| `SlugService` | unique slug generation | `generate($value, $table, $col, $ignoreId)` |
| `CacheService` | file cache | `get`, `set`, `remember`, `forget`, `flush` |
| `LogService` | logging aggregator | `activity()`, `audit()`, `security()` |
| `ActivityLogger` | `activity_logs` writer | `log`, `created`, `updated`, `deleted`, `login`, `settingsChanged` |
| `AuditService` | before/after audit writer | `record` |
| `SecurityLogger` | security channel writer | `failedLogin`, `permissionDenied`, `event` |

## Module services (`modules/*/Services/`)

`ProductService`, `UserService`, `PermissionService` - orchestrate their
repositories + platform services, own slug generation, translation syncing,
password hashing and activity logging.

## Rules

1. A service receives dependencies through its constructor (DI).
2. Services throw typed exceptions (`NotFoundException`, `UploadException`, …);
   the global handler converts them into translated responses.
3. Services never read superglobals - `Request` data arrives as arguments.
4. Cross-module access goes through the service, not another module's repository.

## Usage

```php
$products = app(\Modules\Products\Services\ProductService::class);
$product = $products->create($coreFields, $translations);

setting('company_name');                     // settings helper
\Log::info('...');                            // logging facade
```
