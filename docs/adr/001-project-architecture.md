# ADR 001 - Project Architecture: Frameworkless Modular Monolith

## Context

The platform must run on XAMPP (Apache + PHP + MySQL) with PHP 8.2+, with no external
framework allowed. It must still support a growing set of business systems (company
website, admin panel, CRM) with enterprise qualities: testability, DI, clear layers.

## Problem

How to get Laravel-grade structure (routing, container, middleware, migrations,
modules) without taking a framework dependency?

## Decision

Build an internal micro-framework in `core/` (container, router, request/response,
middleware pipeline, ORM-lite, schema builder, view, validation, logging) and compose
the application from vertical **modules** in `modules/`, glued by providers in `app/`.

Layering rules: `modules → core` via contracts only; `app` owns cross-module
composition; HTTP flows through a single front controller (`public/index.php`).

## Alternatives

1. **Adopt Laravel/Symfony** - rejected: explicit requirement of native PHP/XAMPP,
   zero dependencies, minimal footprint.
2. **Flat procedural PHP** - rejected: fails maintainability/SOLID requirements.
3. **Micro-framework (Slim etc.)** - rejected: still an external dependency.

## Consequences

- Full control and zero supply-chain risk; every class is auditable.
- The framework surface must be maintained by the team (documented in `docs/`).
- PSR-style contracts (`ContainerInterface`, `LoggerInterface`, `RepositoryInterface`,
  `MiddlewareInterface`) keep future swaps (e.g. PSR-7 adoption) cheap.
