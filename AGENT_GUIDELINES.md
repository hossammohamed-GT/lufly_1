# LUFLY AGENT & DEVELOPER GUIDELINES

Welcome to the **LUFLY Architectural Sanitary Ware** codebase.

All software engineers and AI coding agents working on this repository **must strictly adhere** to the established architectural, localization, performance, and design system rules documented below.

For complete in-depth specifications, refer to [`docs/ENGINEERING_STANDARDS.md`](docs/ENGINEERING_STANDARDS.md).

---

## ⚡ Golden Rules for AI Agents & Developers

1. **Active Supported Locales (`en`, `tr`, `cs` ONLY):**
   - **English (`en`)**: Primary / Global market.
   - **Turkish (`tr`)**: Manufacturing / Domestic market.
   - **Czech (`cs`)**: Central European architectural market.
   - ⚠️ **Arabic (`ar`) is completely removed.** Never add Arabic translation files, database records, or language switchers.

2. **No Em-Dashes (`-`):**
   - Never use em-dashes (`-`) in UI copy, titles, meta tags, or commit descriptions. Use pipes (`|`), colons (`:`), hyphens (`-`), or standard punctuation.

3. **Performance First (Sub-100ms & Zero-Jank):**
   - Internal links use native hover/touch prefetching (`frontend/js/app.js`).
   - Live search in navbar uses in-memory caching (`frontend/components/navbar/navbar.js`).
   - BFCache restoration is handled via `pageshow` listener.
   - No external Google Fonts CDN requests (pure local system typography).
   - No continuous JavaScript ticker loops or marquee animations.

4. **Design System & CSS Tokens:**
   - Always use standard CSS variables (`--ds-bg`, `--ds-surface`, `--ds-primary`, `--ds-text`, `--ds-border`, etc.) from `frontend/design-system/style.css`.
   - Never inject arbitrary hex colors or inline style overrides.
   - Both Dark Mode (`rgb(44, 43, 40)` base) and Light Mode (`#f6f8f7` base) are supported via `data-theme`.

5. **Branch & Git Protocol:**
   - Always work and commit directly on `arena/01a0b3d0-lufly-1`.
   - Push to `origin arena/01a0b3d0-lufly-1`.
