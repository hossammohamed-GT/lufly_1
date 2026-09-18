# ADR 005 - Logging: Channel Files + Structured Database Logs

## Context

Operations need diagnosability (app errors, SQL, API traffic), compliance needs
security and audit trails, and XAMPP offers no external log infrastructure.

## Problem

One undifferentiated log file makes incidents untriageable; DB-only logging dies with
the DB; a PSR-3 library is not allowed.

## Decision

1. **File channels** - `app`, `error`, `database`, `api`, `security`, `mail` under
   `storage/logs`, PSR-3-shaped API (`Log::info/warning/error/critical`,
   `Log::channel('security')`), RFC 5424 levels, level threshold via `.env`.
2. **Structured DB logs** where querying matters: `activity_logs` (user actions),
   `audits` (before/after mutations), `api_logs` (endpoint, method, status, response
   time, IP).
3. **Fail-safe writing** - log writers never throw into the request path; DB log
   failures degrade to the file channel.
4. `LogService` aggregates `ActivityLogger`, `AuditService`, `SecurityLogger` behind
   one injectable facade.

## Alternatives

1. **error_log only** - rejected: no channels, no structure.
2. **Monolog** - rejected (dependency constraint); the internal Logger mirrors its
   interface to keep future migration trivial.
3. **Syslog/Journald** - rejected: not portable across XAMPP OSes.

## Consequences

- Incident triage per channel; security/compliance queries via SQL.
- Log rotation is the operator's responsibility (documented).
- API logging adds one DB insert per API request (acceptable; asynchronous option
  later).
