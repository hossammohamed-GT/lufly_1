# ADR 004 - Service Layer as the Only Home for Business Logic

## Context

Multiple consumers exist for the same behaviours: web controllers, admin controllers,
API controllers and future CLI jobs all need products, uploads, settings, mail, etc.

## Problem

Allowing logic to accumulate in controllers duplicates rules across web/API surfaces
and makes auditing/logging inconsistent.

## Decision

Mandate a service layer:

- Controllers translate HTTP ⇄ service calls only.
- Services own validation orchestration, slugging, translation syncing, hashing,
  activity/audit/security logging and exceptions.
- Platform services live in `app/Services`; domain services in `modules/*/Services`.
- Cross-module reuse goes through services or interfaces
  (`App\Services\LocalizationService` abstract → Languages module implementation;
  `UserProviderInterface` → Users module repository).
- Services receive collaborators via constructor injection; none touches
  superglobals.

## Alternatives

1. **Fat controllers** - rejected: duplication across web/api/admin.
2. **Domain-layered hexagonal architecture with per-use-case classes** - considered;
   rejected for now as ceremony outweighs benefit at this stage (services remain the
   seam if the codebase grows).
3. **Active-record-only (logic in models)** - rejected: couples persistence to policy.

## Consequences

- Consistent behaviour across HTTP surfaces; single place to add future queues/jobs.
- Service count grows with features - mitigated by strict one-responsibility naming.
- Integration tests target services directly, without HTTP.
