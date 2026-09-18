# LUFLY Architectural Sanitary Ware | Engineering & AI Standards

> **Enterprise Architecture, Performance, Design System & Multi-Language Specification**  
> *Target Audience: Full-Stack Engineers, Tech Leads, and AI Coding Agents*  
> *Version: 2.0 | Status: Production Standard*

---

## 1. System Overview & Architectural Patterns

LUFLY is a custom-engineered, ultra-fast PHP/vanilla-JS modular platform built for high-performance architectural sanitary ware presentation, multi-market catalog browsing, and enterprise B2B lead generation.

### 1.1 Core Principles
- **Zero Heavy Framework Bloat:** Fast PHP routing and rendering without runtime compilation overhead.
- **Pure Native Frontend:** Vanilla ES6+ modules and pure CSS design tokens without bulky third-party frontend frameworks (no React/Vue runtime, no Tailwind runtime compiler, no jQuery).
- **Sub-100ms Server Response & Instant Client Navigation:** Fully localized, cache-friendly, and prefetch-optimized.
- **Strict Locale Routing:** All public pages require explicit locale prefixes (`/en`, `/tr`, `/cs`).

### 1.2 Directory Structure
```
/
├── app/                        # Application Core
│   ├── Core/                   # Request, Router, View, Container, Database
│   ├── Helpers/                # Global helper functions (t(), asset(), route(), etc.)
│   ├── Http/Controllers/       # Global controllers (HomeController, ContactController, etc.)
│   └── Services/               # SEO, Localization, Cache, and Business services
├── config/                     # Configuration files (app, database, localization, seo)
├── database/                   # SQLite database (lufly.sqlite) and Seeders
├── docs/                       # Engineering standards, architecture specifications
├── frontend/                   # Frontend assets and modular UI components
│   ├── components/             # Reusable modular UI components (navbar, footer, hero, etc.)
│   ├── css/                    # Modular layout styles
│   ├── design-system/          # Design system CSS tokens and core variables
│   └── js/                     # Core JavaScript (app.js, prefetching, theme engine)
├── modules/                    # Feature Modules (Products, Categories, Inquiries)
│   ├── Controllers/            # Module-specific controllers
│   ├── Models/                 # Data models / Repository layers
│   └── Views/                  # Module views
├── public/                     # Public webroot (index.php, sitemap.xml, robots.txt, media)
│   ├── images/                 # Optimized WebP assets (lifestyle, products, technical)
│   └── uploads/                # Dynamic media uploads
└── resources/
    ├── lang/                   # Localization dictionaries (en/, tr/, cs/)
    └── views/                  # Master layout templates and view components
```

---

## 2. Localization & Multi-Language Standards

### 2.1 Supported Locales
The application strictly supports **three languages**:
1. **English (`en`)** - Primary / Default Locale (International & European export)
2. **Turkish (`tr`)** - Regional / Domestic Manufacturing Locale
3. **Czech (`cs`)** - Central European Architectural Hub Locale

> **CRITICAL RULE:** Arabic (`ar`) has been completely decommissioned and must NEVER be re-introduced in configs, translation arrays, databases, or UI templates.

### 2.2 Routing & Fallback Behavior
- Every public route is prefixed: `/{locale}/{path}` (e.g., `/en/products`, `/tr/products`, `/cs/products`).
- Visiting the root `/` automatically detects the preferred language from session, browser `Accept-Language`, or defaults to `/en`.
- Translation lookups use `t('filename.key')`:
  ```php
  // resources/lang/en/home.php -> ['hero_title' => 'European Luxury']
  <?= t('home.hero_title') ?>
  ```
- If a translation key is missing in the active locale, the system gracefully falls back to `en`.

### 2.3 Database Localization Pattern
- Translatable entities follow the standard normalization schema:
  - Base table: `products (id, slug, sku, category_id, status, price, ...)`
  - Translation table: `product_translations (id, product_id, locale, name, description, ...)`
  - Unique constraint: `UNIQUE(product_id, locale)`
- Always query translations with a fallback join to English if the requested locale row does not exist.

---

## 3. Design System & CSS Token Architecture

All styling MUST use the centralized CSS custom properties declared in `frontend/design-system/style.css`. Never use arbitrary hex colors or hardcoded font families in inline styles or component stylesheets.

### 3.1 Color Tokens & Theming
The design system supports seamless Light and Dark modes via the `data-theme="light|dark"` attribute on `<html>`.

| CSS Variable | Role / Purpose | Light Value | Dark Value |
|---|---|---|---|
| `--ds-bg` | Main background | `#f6f8f7` | `rgb(44, 43, 40)` / `#2C2B28` |
| `--ds-surface` | Primary elevated card/panel | `#ffffff` | `#353430` |
| `--ds-surface-alt` | Secondary elevated surface | `#eef3f1` | `#3f3d38` |
| `--ds-border` | Subtle component border | `#d5e2dd` | `#57544b` |
| `--ds-text` | Primary high-contrast body text | `#132220` | `#f5f3ee` |
| `--ds-text-muted` | Secondary readable caption text | `#516f68` | `#aba79b` |
| `--ds-primary` | Brand accent (Teal / Sage) | `#14726b` | `#2ea89f` |
| `--ds-primary-text`| Text inside primary colored buttons | `#ffffff` | `#141311` |
| `--ds-primary-soft`| Tinted accent pill/badge background | `rgba(20, 114, 107, 0.1)`| `rgba(46, 168, 159, 0.18)` |
| `--ds-secondary` | Secondary brand action | `#1d8c82` | `#3ec2b7` |

### 3.2 Typography Tokens
- `--font-display`: `'Plus Jakarta Sans', system-ui, -apple-system, sans-serif` (Headings, titles, badges).
- `--font-body`: `'Plus Jakarta Sans', system-ui, -apple-system, sans-serif` (Body text, data points, labels).
- `--font-mono`: `'JetBrains Mono', ui-monospace, SFMono-Regular, monospace` (Technical specifications, CAD/BIM dimensions).

> **CRITICAL RULE:** All fonts are rendered locally via system font stacks or preloaded local files. **Never import external fonts via Google Fonts `@import` or `<link href="https://fonts.googleapis.com">`** to eliminate DNS lookup latency and render-blocking requests.

---

## 4. Performance & Zero-Jank Engineering Standards

LUFLY must maintain a **100/100 Lighthouse Performance rating** under real-world 4G network conditions.

### 4.1 Native Smart Prefetching Engine (`frontend/js/app.js`)
- All internal navigation links (`<a>` tags pointing to the same origin) are automatically prefetched when the user hovers over them (mouse) or touches them (`touchstart`).
- Uses `<link rel="prefetch" href="...">` with a debounce timer (65ms) and deduplication cache to prevent duplicate network requests.
- Never prefetch external links, mailto/tel links, anchors (`#`), or administrative action triggers.

### 4.2 In-Memory Search Caching (`frontend/components/navbar/navbar.js`)
- Live product search in the navbar uses a local LRU-like memory map `searchCache`.
- Queries are cached per locale key (`${locale}:${query}`).
- Repeated keystrokes or deletions instantly render results from memory without firing network HTTP requests.
- Debounced by 220ms on typing.

### 4.3 Back/Forward Cache (BFCache) Restoration
- `frontend/js/app.js` listens to the `pageshow` event with `event.persisted`.
- If restored from BFCache, any active loading overlays or search modals are instantly dismissed, guaranteeing zero stuck spinners upon pressing the browser's Back button.

### 4.4 Rendering & Layout Shift (CLS) Rules
- **Zero CPU Tickers:** Do not use continuous JavaScript `requestAnimationFrame` ticker loops or endless CSS translation marquees that consume CPU/GPU cycles. Use clean, static, high-impact grid cards.
- **Image Dimensions:** Every `<img>` tag MUST specify explicit `width="..."`, `height="..."`, `loading="lazy"`, `decoding="async"`, and modern format `.webp`.
- **Above-The-Fold Assets:** Hero images must use `fetchpriority="high"` and `loading="eager"` to minimize Largest Contentful Paint (LCP).
- **CSS `content-visibility: auto`:** Used on below-the-fold content sections to skip off-screen rendering until scrolled into viewport.

---

## 5. Copywriting & Content Standards

### 5.1 Strictly Prohibited Characters
> **NEVER use em-dashes (`-`) anywhere in copywriting, titles, meta tags, or descriptions.**  
> - ❌ *Incorrect:* `LUFLY - European Architectural Sanitary Ware`  
> - ✅ *Correct:* `LUFLY | European Architectural Sanitary Ware`  
> - ❌ *Incorrect:* `Precision engineering - built for luxury`  
> - ✅ *Correct:* `Precision engineering: built for luxury` or `Precision engineering. Built for luxury.`

### 5.2 Architectural Tone of Voice
- Professional, technical, authoritative, and clean.
- Highlight EN certifications (e.g., `EN 997 Class 1`, `EN 200`, `ISO 9001`), luxury PVD coatings, vitreous china durability, acoustic dampening, and architectural project specifications.

---

## 6. SEO, OpenGraph & Structured Data (JSON-LD)

### 6.1 Multilingual Hreflang Tags
Every page rendered by `resources/views/components/seo.php` must include valid alternate links for all 3 supported locales:
```html
<link rel="alternate" hreflang="en" href="https://lufly.tr/en/products/porto-rimless-toilet">
<link rel="alternate" hreflang="tr" href="https://lufly.tr/tr/products/porto-rimless-toilet">
<link rel="alternate" hreflang="cs" href="https://lufly.tr/cs/products/porto-rimless-toilet">
<link rel="alternate" hreflang="x-default" href="https://lufly.tr/en/products/porto-rimless-toilet">
```

### 6.2 Schema.org Structured Data
- **Organization Schema:** Declared on the homepage with European and Turkish export metadata.
- **Product Schema:** Declared on product detail pages with `sku`, `name`, `description`, `image`, `offers`, and `manufacturer`.
- **BreadcrumbList Schema:** Declared on all deep pages for rich snippet search hierarchy.

---

## 7. Developer & AI Agent Workflow Rules

1. **Git Branching:**
   - All work, commits, and pull requests in this environment must remain strictly on the active branch: `arena/01a0b3d0-lufly-1`.
   - Never attempt to switch branches or push to other branch names.
2. **Database Integrity:**
   - When modifying seeders, always provide valid data for all 3 supported languages (`en`, `tr`, `cs`).
   - Run seeders using `php database/seed.php`.
3. **No Regressions Policy:**
   - Before completing any task, verify that all public routes (`/en`, `/tr`, `/cs`, `/en/products`, `/tr/products`, etc.) return HTTP 200 without PHP notices or warnings.
   - Validate that dark and light themes toggle flawlessly without flashing unstyled content (FOUC).
