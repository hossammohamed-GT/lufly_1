# API Standards

All endpoints live under `/api`, use the `api` middleware group (request logging)
and respond with the standard envelope.

## Envelopes

```json
// success
{ "success": true, "message": "", "data": {} }

// error
{ "success": false, "message": "", "errors": {}, "error_code": "validation_failed" }

// paginated
{
  "success": true,
  "data": [],
  "meta": { "page": 1, "per_page": 10, "total": 100, "last_page": 10 }
}
```

Built with `Core\Http\ApiResponse::success() | error() | paginated()`.

## Error codes

| HTTP | error_code | Meaning |
| --- | --- | --- |
| 401 | `unauthenticated` | session/login required |
| 403 | `forbidden` | missing permission |
| 404 | `not_found` | missing resource |
| 405 | `method_not_allowed` | wrong HTTP verb |
| 419 | `csrf_token_invalid` | CSRF mismatch (web group) |
| 422 | `validation_failed` | field errors in `errors` |
| 422 | `upload_failed` | upload rejected |
| 500 | `server_error` / `database_error` | unexpected failure |

## Endpoints overview

| Method | Endpoint | Auth | Permission |
| --- | --- | --- | --- |
| GET | `/api/health` | - | - |
| GET | `/api/locales` | - | - |
| POST | `/api/auth/login` | - | - |
| POST | `/api/auth/logout` | session | - |
| GET | `/api/auth/me` | session | - |
| GET | `/api/products` | - | - |
| GET | `/api/products/{id}` | - | - |
| POST | `/api/products` | session | `products.manage` |
| PUT | `/api/products/{id}` | session | `products.manage` |
| DELETE | `/api/products/{id}` | session | `products.manage` |
| GET | `/api/media` | session | `media.view` |
| POST | `/api/media` | session | `media.manage` |
| DELETE | `/api/media/{id}` | session | `media.manage` |
| GET | `/api/notifications` | session | - |
| POST | `/api/notifications/{id}/read` | session | - |
| POST | `/api/notifications/read-all` | session | - |

Full per-endpoint documentation (request fields, validation rules, response
examples) is generated from the route `->doc()` metadata:

```bash
php cli docs:api     # writes docs/API-Endpoints.md
```

## Example

```http
POST /api/products
Content-Type: application/json

{
  "sku": "LUFLY-100",
  "price": 29.9,
  "status": "active",
  "translations": {
    "en": { "name": "Nova Lamp", "description": "..." },
    "tr": { "name": "Nova Lamba" }
  }
}
```

```json
{ "success": true, "message": "Saved successfully.", "data": { "product": { "id": 4, "sku": "LUFLY-100" } } }
```
