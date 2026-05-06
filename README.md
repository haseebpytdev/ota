# White-label OTA (Laravel)

**Hayat Travel Solutions — White-Label OTA Demo** is a Laravel application that demonstrates how a platform owner can launch a branded travel experience: a public flight storefront, a demo checkout, and an **operator console** (admin) for bookings, agents, staff, commercial rules, supplier settings, roles, and reports.

## Demo domain

**https://ota.haseebasif.com**

Local development: `php artisan serve` (typically `http://127.0.0.1:8000`).

Branding, support contacts, demo copy, and the public flight-search disclaimer are centralized in **`config/demo-brand.php`**. **Client-preview white-label** fields for sales walkthroughs live in **`config/demo-client.php`** (agency name, tagline, logo text, primary color, support channels, “Powered by Hayat Travel Solutions”).

## Product purpose

- **Public storefront** — TourNest-styled Blade UI: home, flight search/results/detail, passenger step, confirmation. **Mock inventory** and **PKR** pricing for stakeholder walkthroughs.
- **Operator console** — Tabler-based **admin** area with KPIs, module screens driven by **`config/demo-*.php`** (no database for these demos).
- **Portal placeholders** — `/staff`, `/agent`, and `/customer` are labelled **planned** areas (no authentication) so clients see where separate experiences will live.

## Client package positioning

This repository is intended as a **sales and scoping asset**: a credible UI shell plus realistic screen inventory, not a production booking engine. It helps align buyers on **modules**, **integrations** (Sabre, PIA, airline direct, payments), and **operations** (agents, staff, markups) before engineering estimates and contracts. Upgrade work is scoped separately (auth, persistence, live APIs, payments, multi-tenant branding).

## 10/10 client demo flow

Use this sequence when closing a white-label OTA conversation (all mock / no live APIs):

1. **Open** `/` — hero, trust metrics, three fare preview cards, mini admin preview, white-label section; nav shows **client-preview** branding from `demo-client` plus subtle **Powered by Hayat Travel Solutions**.
2. **Request demo** `/request-demo` — polished form (disabled / demo-only) to show lead capture intent.
3. **Search** `/flights/search` → **Results** `/flights/results` — filter sidebar (visual), rich result cards (airline placeholder, times, duration, baggage, fare family, refundable badge, seats-left demo, price breakdown, pay-later label).
4. **Select** a flight → **Passengers** `/booking/passengers` → **Review** `/booking/review` (flight recap, contact, fare breakdown, booking method: pay later / bank transfer / office) → **Confirmation** `/booking/confirmation`.
5. **Admin** `/admin` — command center (quick actions, today’s operations, supplier readiness, revenue snapshot) then **Branding** `/admin/branding` and **Go-live checklist** `/admin/go-live-checklist` (demo-only screens).

## Client walkthrough script (short)

- Position the **starter package** as the scope that ships this UI shell, config-driven modules, and integration *readiness* (not production ticketing).
- Show **how the agency brand** would appear on the public site (`demo-client`) vs **Hayat** as platform partner in the footer.
- Walk the **booking path** through review so finance and ops see **payment / confirmation options** before engineering quotes payment gateways.
- Land in **admin** to show **operational** and **go-live** narratives without claiming live Sabre/PIA/airline data.

## PKR 250,000 starter package (illustrative)

**Included in the starter scope** (as represented by this demo):

- Public **white-label storefront** patterns (home, search, results, detail, passenger, review, confirmation) with **mock inventory and PKR pricing**.
- **Operator console** layouts: bookings, agents, staff, markups, API settings, roles, reports, command-center widgets, **branding** and **go-live checklist** screens (all **config-backed**, no persistence).
- **Portal placeholders** (`/staff`, `/agent`, `/customer`) for roadmap alignment.
- **Documentation** in this README for demo flow, limitations, and upgrade path.

**Requires a separate quote** (typical production work, not implied by the starter):

- **Authentication**, RBAC, and audited admin access.
- **Database** models, migrations, and real booking lifecycle.
- **Live** Sabre, PIA, airline-direct, or other **GDS/API** integrations and certification.
- **Payments** (gateway, reconciliation, refunds) and **ticketing** automation.
- **Multi-tenant** domains, per-client secrets, SLAs, and **managed hosting** beyond a demo deploy.

## Go-live checklist (product)

The screen **`/admin/go-live-checklist`** mirrors the narrative you want signed off before production: domain, branding, supplier credentials, API docs review, payment method, staff roles, agent commission rules, test bookings, deployment readiness. Items are **demo state only** in this repository (`config/demo-go-live.php`).

## Included modules

| Module | Route / area | Data |
| --- | --- | --- |
| Public home & flight flow | `/`, `/flights/*`, `/booking/*`, `/request-demo` | `demo-brand`, `demo-client`, `demo-routes`, `demo-flights`, mock services |
| Admin overview | `/admin` | `demo-bookings`, `demo-command-center` |
| Bookings | `/admin/bookings` (`?preview=` ref) | `demo-bookings` |
| Agents | `/admin/agents` (`?preview=` code) | `demo-agents` |
| Staff | `/admin/staff` (`?preview=` code) | `demo-staff` |
| Markups | `/admin/markups` | `demo-markups` |
| API settings | `/admin/api-settings` | `demo-suppliers` |
| Roles & permissions | `/admin/roles-permissions` | `demo-roles` |
| Reports | `/admin/reports` | `demo-reports` |
| Branding (demo) | `/admin/branding` | `demo-client` |
| Go-live checklist (demo) | `/admin/go-live-checklist` | `demo-go-live` |
| Staff / Agent / Customer shells | `/staff`, `/agent`, `/customer` | Placeholder views |

## What is demo-only

- **All admin data** (bookings, agents, staff, fares in reports, supplier rows, roles matrix) is **sample config**, not synced to a backend.
- **Disabled actions** (filters, save, configure, staff/agent workflow buttons) are **non-functional** and labelled **Demo only** in the UI.
- **Public fares** are **generated for demonstration**; there is no live look-to-book, ticketing, or settlement.
- **Trust strip** messaging (24/7 support, pay later, APIs, etc.) describes **product intent**, not guaranteed availability in this deployment.

## What is not included yet

- User **authentication** or **authorization** (RBAC / policies).
- **Database persistence** for bookings, CRM, or settings in these screens.
- **Live Sabre, PIA, or airline-direct** APIs (supplier classes exist as stubs).
- **Payment gateway** or merchant reconciliation.
- **Ticketing automation** (ETKT issuance, schedule-change ingestion).
- **Wallet / credit limit** for agents (may be referenced in copy as a future feature).
- **Multi-tenant** provisioning (per-client domains, isolated themes, billing).

## Future upgrade path

1. **Auth & permissions** — Breeze/Fortify or SSO; middleware; policy-gated admin modules.
2. **Database persistence** — Eloquent models aligned with current `demo-*` shapes; migrations; seeds from fixtures.
3. **Live Sabre / PIA / airline APIs** — Credential vault, production `FlightSearchService` wiring, error handling, logging.
4. **Payment gateway** — Hosted fields or redirect; reconciliation in reporting.
5. **Ticketing automation** — Queues, webhooks, document delivery.
6. **Wallet / credit limit** — Agent ledger, statements, approvals (extends admin agent workflows).
7. **Multi-tenant white-label** — Domain routing, per-tenant `demo-brand` equivalents, feature flags.

## Layout conventions

| Area | Layout | Assets |
| --- | --- | --- |
| Public OTA | `resources/views/layouts/frontend.blade.php` | `public/vendor/tournest/` |
| Dashboards | `resources/views/layouts/dashboard.blade.php` | `public/vendor/tabler/` |

Keep vendor themes under `templates-source/`; copy distilled assets into `public/vendor/` and reference them from Blade.

## Template workflow

1. Place **TourNest** and **Tabler** sources under `templates-source/`.
2. Copy required CSS, JS, fonts, and images into `public/vendor/`.
3. Build views under `resources/views/` extending the layouts above.

## Primary routes

| Path | Purpose |
| --- | --- |
| `/` | Home (client-preview + Hayat powered-by) |
| `/request-demo` | Request demo form (demo-only / disabled submit) |
| `/flights/search`, `/flights/results`, `/flights/details/{id}` | Search and mock offers |
| `/booking/passengers`, `/booking/review`, `/booking/confirmation` | Demo checkout with review step |
| `/admin`, `/admin/bookings`, … | Operator console |
| `/admin/branding`, `/admin/go-live-checklist` | Demo white-label and launch checklist |
| `/staff`, `/agent`, `/customer` | Planned portal shells |

## Services (overview)

- `App\Services\FlightSearch\FlightSearchService` — mock supplier.
- `App\Services\Suppliers\Sabre\SabreFlightSupplier` — placeholder for future APIs.
- `App\Services\Pricing\FlightPricingService` — demo taxes, markup, service fee.
- `App\Services\Booking\BookingDraftService` — session-backed booking demo.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
```

## Testing

```bash
php artisan test
```

`tests/Feature/FrontendRoutesTest.php` includes a **client demo navigation** check for the primary public and admin URLs.

## Deployment

Production/staging deployment runbook is documented in `docs/deployment.md` (environment templates, queue/scheduler setup, storage, caching, backup readiness, and shared-hosting caveats).

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT). Theme licenses for TourNest and Tabler are governed by their respective vendors.
