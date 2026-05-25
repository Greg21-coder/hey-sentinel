# HeySentinel — React + Inertia Migration & Phase 6 M2 Design Spec

**Date:** 2026-05-25
**Approach:** Cascada por superficie (A)
**Stack:** React 18 + TypeScript + Inertia.js + Tailwind CSS + Vite

## Overview

Complete migration of HeySentinel from Blade/Filament to React + Inertia.js, incorporating Phase 6 M2 features (Saved Searches, Review Velocity, CSV Export, Feature Gates) and a new Unlist mechanism for Shopify entities.

Three surfaces migrated in order:
1. Landing page (public)
2. Customer portal (authenticated)
3. Admin panel (superadmin)

## Constraints

- **GPU friendly:** No CSS animations (no animate-*, no @keyframes). No filter blur, no mix-blend-mode. Transitions limited to hover states (opacity, background-color only).
- **No ASO features:** Keyword Tracking, AI Listing Audit, Search Volume API are excluded from this spec. Deferred to a future sprint pending contractor discussion.
- **Filament coexistence:** During migration, Filament admin panel continues to function at `/admin` until the React admin is complete. Customer Filament panel is removed once React customer portal is deployed.

## 1. Foundation: React + Inertia Setup

### Dependencies

```
npm install react react-dom @inertiajs/react @types/react @types/react-dom
npm install -D @vitejs/plugin-react typescript
composer require inertiajs/inertia-laravel
```

### Vite Configuration

Add `@vitejs/plugin-react` to `vite.config.js`. Entry point: `resources/js/app.tsx`.

### Inertia Middleware

Add `HandleInertiaRequests` middleware to the web middleware group. Shared props:
- `auth.user` (current user with account and plan)
- `auth.account` (current account)
- `featureGates` (plan-based feature limits for the current account)
- `flash` (session flash messages)

### File Structure

```
resources/js/
├── app.tsx
├── ssr.tsx (optional, deferred)
├── types/
│   ├── index.d.ts
│   └── models.d.ts
├── Layouts/
│   ├── PublicLayout.tsx
│   ├── CustomerLayout.tsx
│   └── AdminLayout.tsx
├── Pages/
│   ├── Landing.tsx
│   ├── Auth/
│   │   ├── Login.tsx
│   │   └── Register.tsx
│   ├── Customer/
│   │   ├── Dashboard.tsx
│   │   ├── BrowseApps.tsx
│   │   ├── AppDetail.tsx
│   │   ├── SavedSearches.tsx
│   │   └── Settings.tsx
│   └── Admin/
│       ├── Dashboard.tsx
│       ├── Accounts/
│       │   ├── Index.tsx
│       │   ├── Create.tsx
│       │   └── Edit.tsx
│       ├── Users/
│       │   ├── Index.tsx
│       │   ├── Create.tsx
│       │   └── Edit.tsx
│       ├── Plans/
│       │   ├── Index.tsx
│       │   ├── Create.tsx
│       │   └── Edit.tsx
│       ├── ShopifyApps/
│       │   ├── Index.tsx
│       │   └── Show.tsx
│       ├── ShopifyStores/
│       │   └── Index.tsx
│       ├── StoreReviews/
│       │   └── Index.tsx
│       └── PainPointsAnalytics.tsx
└── Components/
    ├── ui/
    │   ├── Button.tsx
    │   ├── Input.tsx
    │   ├── Select.tsx
    │   ├── Badge.tsx
    │   ├── Modal.tsx
    │   ├── Card.tsx
    │   ├── Toggle.tsx
    │   └── UpgradePrompt.tsx
    ├── charts/
    │   ├── SentimentTimeline.tsx
    │   ├── PainPointsRadar.tsx
    │   └── ReviewVelocity.tsx
    └── tables/
        └── DataTable.tsx
```

### TypeScript Types

Type definitions generated from Laravel models:
- `ShopifyApp`, `ShopifyStore`, `StoreReview`
- `Account`, `User`, `Plan`, `PlanFeature`
- `AccountFollowedApp`, `SavedSearch`
- `AiPainPoint`, `AiTag`, `AiBatch`
- Paginated response type: `PaginatedResponse<T>`

## 2. Surface 1: Landing Page

### Route

```php
Route::get('/', LandingController::class)->name('home');
```

The controller returns an Inertia response with:
- `plans`: active public plans with features
- `stats`: real counts from DB (apps tracked, stores indexed, reviews analyzed)

### Page: Landing.tsx

Sections (top to bottom):
1. **Nav** — Logo, anchor links (Features, How it works, Pricing), Login link, CTA button
2. **Hero** — Headline "Stop guessing. Build what's missing.", subtitle, CTA, "No credit card required"
3. **Stats bar** — 4 metrics from real DB data (apps, stores, reviews, SLA)
4. **Features** — 3 cards: Track competitors, AI pain points, Market gaps
5. **How it works** — 3 numbered steps
6. **Pricing** — Dynamic cards from `plans` prop. Highlighted "Most popular" plan. Feature checklist per plan.
7. **CTA final** — "Ready to know what to build next?" + signup button
8. **Footer** — Logo, copyright

GPU-friendly: Static layout. No entrance animations. Hover transitions on buttons and cards (opacity/background only, max 150ms).

### Page: Auth/Register.tsx

Form fields: name, company name, email, password, password confirmation, plan selection (radio).
POST to existing `RegisteredAccountController::store`.

### Page: Auth/Login.tsx

Form fields: email, password, remember me.
Custom `LoginController` using `Auth::attempt()`. Replaces Filament's built-in login. Redirects to `/customer` on success, `/admin` if superadmin.

## 3. Surface 2: Customer Portal

### Layout: CustomerLayout.tsx

Sidebar navigation:
- Dashboard (home icon)
- Browse Apps (search icon)
- Saved Searches (bookmark icon)
- Settings (gear icon)

Topbar: Account name, user avatar/initials, logout.

### Page: Customer/Dashboard.tsx

5 widgets in a responsive grid:

1. **MyAppsStatsOverview** — 4 stat cards:
   - Apps followed (count)
   - Total pain points (across followed apps)
   - Negative reviews (1-2 star, last 30 days)
   - Review velocity (reviews/week trend, NEW)

2. **SentimentTimelineChart** — Recharts LineChart. X-axis: months (6mo). Y-axis: average sentiment score. One line per followed app. Data from `store_reviews` aggregated by month.

3. **PainPointsRadar** — Recharts RadarChart. Axes: top pain point categories. One polygon per followed app. Compares pain point distribution.

4. **ReviewVelocityWidget** (NEW) — Recharts BarChart. X-axis: weeks (12 weeks). Y-axis: review count. Grouped bars per followed app. Trend indicator (up/down arrow).

5. **MyAppsList** — Table of followed apps. Columns: name, rating, reviews count, velocity trend, AI summary (modal trigger), actions (unfollow, view detail).

### Page: Customer/BrowseApps.tsx

DataTable with server-side pagination and filters:
- Filters: category (select), rating min (number), pricing (free/paid/all), pain point tags (multi-select), keyword search (text)
- Columns: name, developer, category, rating, reviews, pricing, pain points count
- Row action: Follow/Unfollow toggle
- Action bar: "Save this search" button, "Export CSV" button (gated)

### Page: Customer/AppDetail.tsx

Infolist-style layout:
- App header: name, developer, category badge, rating, reviews count, pricing, follow button
- AI Summary card (expandable)
- Pain Points widget: top pain points for this app with counts
- Reviews tab: DataTable of reviews with filters (rating, sentiment, date range)
- Export CSV button for reviews (gated)

### Page: Customer/SavedSearches.tsx (NEW — Phase 6 M2)

List of saved searches with:
- Name (editable inline)
- Filter summary (badges showing active filters)
- "New results" indicator (count of new apps since last viewed)
- Actions: Load (navigates to BrowseApps with filters applied), Edit, Delete
- Toggle: "Notify me of new results" (boolean, future email notification)

**Model: SavedSearch**
```
saved_searches
├── id (bigint, PK)
├── account_id (FK → accounts)
├── user_id (FK → users)
├── name (string)
├── filters (JSON: {category_id, rating_min, rating_max, pricing, pain_point_ids, keyword})
├── notify_on_new (boolean, default false)
├── last_viewed_at (timestamp, nullable)
├── created_at
└── updated_at
```

Migration: `create_saved_searches_table`

### Page: Customer/Settings.tsx

- Profile: name, email, password change
- Team: list members, invite by email (uses existing AccountInvitation model)
- Plan: current plan display, upgrade prompt

### Feature Gates (Phase 6 M2)

**Middleware: CheckFeatureGate**

Laravel middleware that checks the current account's plan features before allowing access to gated resources.

**Feature limits (configured per plan via plan_features table):**

| Feature Key | Free | Pro | Premium |
|-------------|------|-----|---------|
| `followed_apps` | 5 | 25 | unlimited |
| `saved_searches` | 0 | 10 | unlimited |
| `csv_export` | false | true | true |
| `review_velocity` | false | true | true |

**Frontend enforcement:**
- `featureGates` shared prop contains the account's current limits and usage counts
- `<UpgradePrompt>` component renders when a user attempts a gated action
- Gated UI elements show a lock icon with "Upgrade to Pro" tooltip
- Server-side validation as source of truth (frontend is UX only)

### CSV Export (Phase 6 M2)

- Button on BrowseApps and AppDetail (reviews)
- POST request triggers `ExportCsvJob` (async, queued)
- Job generates CSV file, stores in `storage/app/exports/{account_id}/`
- Returns download URL via flash message or polling
- Files auto-deleted after 24 hours (scheduled cleanup)
- Gated by `csv_export` feature key

## 4. Surface 3: Admin Panel

### Layout: AdminLayout.tsx

Sidebar navigation groups:
- **Overview:** Dashboard
- **Management:** Accounts, Users, Plans
- **Shopify Data:** Apps, Stores, Reviews
- **Analytics:** Pain Points

Topbar: "Admin" badge, user name, logout.

### Access Control

Middleware `EnsureSuperAdmin` on all `/admin/*` routes. Checks `User::isSuperAdmin()`.

### Pages

**Admin/Dashboard.tsx** — Stats overview:
- Total accounts (active, trial, cancelled)
- Total users
- Total apps scraped
- Total reviews processed
- AI batches (pending, completed, failed)
- Last scrape timestamp

**Admin/Accounts/** — Full CRUD + Delete (soft delete)
- List: DataTable with columns (name, slug, owner, plan, status, users count, created_at)
- Create/Edit: ResourceForm (name, slug, status, plan selection, owner assignment)
- Delete: soft delete with confirmation modal. Validates no active subscriptions.
- Relation: Users list (inline table showing account members)

**Admin/Users/** — Full CRUD + Delete
- List: DataTable (name, email, accounts count, last login, created_at)
- Create/Edit: ResourceForm (name, email, password, superadmin toggle)
- Delete: hard delete with confirmation. Cannot delete self.

**Admin/Plans/** — Full CRUD + Delete
- List: DataTable (name, price, status, accounts count)
- Create/Edit: ResourceForm (name, description, monthly_price, slug, public, active, sort_order)
- Delete: validation — cannot delete plan with active accounts. Must reassign first.
- Relation: Features (inline CRUD for plan_features: feature_key, feature_value, value_type)

**Admin/ShopifyApps/** — Read-only + Unlist
- List: DataTable (name, developer, category, rating, reviews, scraping status, unlisted badge)
- Toggle "Show unlisted" filter
- Row actions: View, **Unlist/Relist**
- View: Infolist (all fields) + Reviews relation table

**Admin/ShopifyStores/** — Read-only + Unlist
- List: DataTable (store fields, unlisted badge)
- Row actions: **Unlist/Relist**

**Admin/StoreReviews/** — Read-only
- List: DataTable with filters (app, rating, sentiment, AI status, date range)

**Admin/PainPointsAnalytics.tsx** — Dashboard page with 3 widgets:
- StatsOverview: total pain points, total tags, coverage %
- TopPainPointsTable: ranked list of pain points by occurrence count
- TopAppsByPainPointsTable: apps with most pain point occurrences

### Reusable Components

**DataTable.tsx:**
- Props: columns definition, data (paginated), filters config, row actions, bulk actions
- Server-side pagination via Inertia (preserves URL state)
- Sortable columns
- Filter bar (renders based on filter config: select, text, date, toggle)
- Responsive: horizontal scroll on mobile

**ResourceForm.tsx:**
- Props: fields definition, initial values, submit URL, method (POST/PUT)
- Field types: text, textarea, email, password, number, select, toggle, date
- Inline relation managers (e.g., plan features as sub-table within plan form)
- Server-side validation errors displayed per field
- Submit via Inertia form helper

## 5. Unlist Mechanism

### Database

Migration: `add_unlisted_at_to_shopify_apps_and_stores`

```sql
ALTER TABLE shopify_apps ADD COLUMN unlisted_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE shopify_stores ADD COLUMN unlisted_at TIMESTAMP NULL DEFAULT NULL;
```

### Model Scope

Trait `HasUnlisting`:
- `scopeActive($query)` — `whereNull('unlisted_at')`
- `scopeWithUnlisted($query)` — removes the scope
- `unlist()` — sets `unlisted_at = now()`
- `relist()` — sets `unlisted_at = null`
- `isUnlisted(): bool`

Apply as global scope on `ShopifyApp` and `ShopifyStore` so all queries exclude unlisted by default.

### Impact

- Customer portal BrowseApps: automatically excludes unlisted apps (global scope)
- Customer dashboard widgets: pain points and sentiment exclude reviews from unlisted apps
- AI batch processing: `CompileBatchJob` skips reviews belonging to unlisted apps
- Admin panel: toggle "Show unlisted" overrides global scope for visibility
- Reviews are NOT individually unlisted — they are excluded transitively via their parent app's unlisted status

## 6. Routes Structure

```php
// Public (Inertia)
Route::get('/', LandingController::class)->name('home');
Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// Customer Portal (authenticated)
Route::middleware(['auth', 'verified'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/', CustomerDashboardController::class)->name('dashboard');
    Route::get('/apps', [CustomerAppController::class, 'index'])->name('apps.index');
    Route::get('/apps/{shopifyApp}', [CustomerAppController::class, 'show'])->name('apps.show');
    Route::post('/apps/{shopifyApp}/follow', [FollowAppController::class, 'store'])->name('apps.follow');
    Route::delete('/apps/{shopifyApp}/follow', [FollowAppController::class, 'destroy'])->name('apps.unfollow');
    Route::resource('saved-searches', SavedSearchController::class)->except(['show']);
    Route::post('/export/apps', [ExportController::class, 'apps'])->name('export.apps');
    Route::post('/export/reviews/{shopifyApp}', [ExportController::class, 'reviews'])->name('export.reviews');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

// Admin (superadmin)
Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::resource('accounts', AdminAccountController::class);
    Route::resource('users', AdminUserController::class);
    Route::resource('plans', AdminPlanController::class);
    Route::get('/shopify-apps', [AdminShopifyAppController::class, 'index'])->name('shopify-apps.index');
    Route::get('/shopify-apps/{shopifyApp}', [AdminShopifyAppController::class, 'show'])->name('shopify-apps.show');
    Route::post('/shopify-apps/{shopifyApp}/unlist', [AdminShopifyAppController::class, 'unlist'])->name('shopify-apps.unlist');
    Route::post('/shopify-apps/{shopifyApp}/relist', [AdminShopifyAppController::class, 'relist'])->name('shopify-apps.relist');
    Route::get('/shopify-stores', [AdminShopifyStoreController::class, 'index'])->name('shopify-stores.index');
    Route::post('/shopify-stores/{shopifyStore}/unlist', [AdminShopifyStoreController::class, 'unlist'])->name('shopify-stores.unlist');
    Route::post('/shopify-stores/{shopifyStore}/relist', [AdminShopifyStoreController::class, 'relist'])->name('shopify-stores.relist');
    Route::get('/store-reviews', [AdminStoreReviewController::class, 'index'])->name('store-reviews.index');
    Route::get('/pain-points', AdminPainPointsController::class)->name('pain-points');
});
```

## 7. Migration Order (Cascada)

### Block 1: Foundation (~2h)
- Install React, Inertia, TypeScript dependencies
- Configure Vite with React plugin
- Create `HandleInertiaRequests` middleware
- Create `app.tsx` bootstrap
- Create TypeScript type definitions
- Create base layouts (PublicLayout, CustomerLayout, AdminLayout)
- Create reusable UI components (Button, Input, Select, Badge, Modal, Card, Toggle)

### Block 2: Landing Page (~2h)
- Create `LandingController` with real stats
- Create `Landing.tsx` page (all sections)
- Create `Auth/Login.tsx` and `Auth/Register.tsx`
- Create `LoginController` and update `RegisteredAccountController` for Inertia
- Remove Blade landing view dependency from routes

### Block 3: Customer Portal + M2 Features (~10h)
- Migration: `create_saved_searches_table`
- Migration: `add_unlisted_at_to_shopify_apps_and_stores`
- Model: `SavedSearch` with `BelongsToAccount`
- Trait: `HasUnlisting` on ShopifyApp and ShopifyStore
- Feature gate middleware and seeder updates
- Recharts integration for charts
- DataTable component
- All 5 customer pages (Dashboard, BrowseApps, AppDetail, SavedSearches, Settings)
- All 5 dashboard widgets
- CSV export job and controller
- UpgradePrompt component
- Remove Filament customer panel provider

### Block 4: Admin Panel (~10h)
- DataTable and ResourceForm reusable components (if not already from Block 3)
- All admin controllers (7 resources)
- All admin pages (Dashboard + CRUD pages)
- Unlist/Relist actions for ShopifyApps and ShopifyStores
- Pain Points Analytics page with 3 widgets
- EnsureSuperAdmin middleware
- Remove Filament admin panel provider

## 8. Out of Scope

- SSR (server-side rendering) — deferred, not needed for MVP
- Real-time notifications (WebSocket) — future enhancement
- Stripe billing integration — schema ready, implementation deferred
- ASO features (Keyword Tracking, AI Listing Audit, Search Volume API)
- Email notification for saved searches — toggle present in UI, email delivery deferred
- Mobile app
