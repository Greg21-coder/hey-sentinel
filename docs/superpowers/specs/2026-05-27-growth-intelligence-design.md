# Growth Intelligence Module — Design Spec

**Date:** 2026-05-27
**Status:** Approved
**Branch:** feature/react-inertia-migration
**Phase:** Sprint 2 — Review Intelligence + Competitor Monitoring
**Depends on:** Discovery Crawler (completed), AI Pipeline (completed)

## Problem

HeySentinel scrapes 1000+ Shopify apps, extracts reviews, and runs AI sentiment/pain-point analysis. But the data is static — there is no way to detect when a competitor changes their pricing, when a rating drops, or how review velocity trends over time. Customers have no actionable intelligence to drive decisions.

## Solution

A snapshot-based delta tracking system that leverages the existing scraping pipeline. Every time an app is re-scraped, a snapshot captures its current state. Comparing consecutive snapshots detects changes (price, rating, name, description, category). Detected changes generate notifications for accounts following those apps.

Combined with enhanced Review Intelligence (already 80% built), this delivers a Growth Intelligence module spanning both customer and admin portals.

## Scope — This Sprint

| Module | Status | What's Built |
|--------|--------|-------------|
| Review & Sentiment Intelligence | Enhance existing | Trend charts, velocity, sentiment breakdown per app — partially done in Show page |
| Competitor Monitoring (delta tracking) | New | Snapshots, change detection, notifications, change feed |
| Keyword Intelligence | Deferred | Requires new scraping infrastructure for search results |
| Optimization Impact | Deferred | Requires A/B experiment framework |

---

## Data Model

### New table: `app_snapshots`

Captures a point-in-time copy of key app fields on every scrape.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| shopify_app_id | bigint FK | references shopify_apps |
| name | string(255) | |
| developer_name | string(255) | |
| description_hash | string(64) | sha256 of description text |
| pricing_raw | string(255), nullable | |
| pricing_min_usd | decimal(8,2), nullable | |
| pricing_has_free | boolean | |
| average_rating | decimal(3,2) | |
| total_reviews | unsigned int | |
| category_name | string(100), nullable | |
| avatar_url | string(500), nullable | |
| snapshot_at | timestamp | when the scrape occurred |

**Indexes:** `(shopify_app_id, snapshot_at DESC)` for latest-snapshot lookups.

**Storage estimate:** ~100 bytes/row. 1000 apps x 365 days = 365K rows/year.

### New table: `app_changes`

Records detected differences between consecutive snapshots.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| shopify_app_id | bigint FK | |
| field | string(50) | e.g. `pricing_raw`, `average_rating`, `name` |
| old_value | text, nullable | |
| new_value | text, nullable | |
| detected_at | timestamp | |

**Indexes:** `(shopify_app_id, detected_at DESC)`, `(field, detected_at DESC)`.

**Tracked fields:** `name`, `developer_name`, `description_hash`, `pricing_raw`, `pricing_min_usd`, `pricing_has_free`, `average_rating`, `total_reviews`, `category_name`, `avatar_url`.

### New table: `app_change_notifications`

Per-account notification when a followed app changes.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| app_change_id | bigint FK | |
| account_id | bigint FK | |
| read_at | timestamp, nullable | null = unread |
| created_at | timestamp | |

**Indexes:** `(account_id, read_at)` for unread-notifications query, `(app_change_id)`.

---

## Backend Components

### `App\Models\AppSnapshot`

Eloquent model. Belongs to ShopifyApp. Fillable: all snapshot columns plus `shopify_app_id` and `snapshot_at`.

### `App\Models\AppChange`

Eloquent model. Belongs to ShopifyApp. Has many AppChangeNotification. Fillable: `shopify_app_id`, `field`, `old_value`, `new_value`, `detected_at`.

### `App\Models\AppChangeNotification`

Eloquent model. Belongs to AppChange and Account. Fillable: `app_change_id`, `account_id`, `read_at`.

### `App\Services\Intelligence\SnapshotDiffService`

Pure logic service. No side effects.

- `createSnapshot(ShopifyApp $app): AppSnapshot` — Builds a snapshot from current app state.
- `diff(AppSnapshot $previous, AppSnapshot $current): array` — Returns array of `['field' => string, 'old' => mixed, 'new' => mixed]` for fields that changed. Ignores `avatar_url` changes (too noisy). Uses configurable threshold for rating changes (default: ignore changes < 0.05).

### Hook into `ScrapeAppPageJob`

After the existing `ShopifyApp::updateOrCreate()` succeeds:

1. Load the app fresh from DB (with updated data).
2. Fetch the latest existing `AppSnapshot` for this app.
3. Create a new snapshot via `SnapshotDiffService::createSnapshot()`.
4. If a previous snapshot exists, run `SnapshotDiffService::diff()`.
5. For each detected change: create `AppChange` record.
6. For each `AppChange`: find all `AccountFollowedApp` records for this app and create `AppChangeNotification` per account.

This runs inline in the job (lightweight — 2 queries + inserts). No separate job needed.

### Cron: Re-scrape for Change Detection

Modify `ScrapeAppsCommand` to support a `--rescrape` flag:

```
app:scrape:apps --rescrape --limit=500
```

When `--rescrape` is set, query apps where `scraping_status = 'scraped'` ordered by `last_scraped_at ASC` (stalest first). This ensures every app gets re-scraped at least every 2 days.

**Updated cron schedule:**

| Time | Command | Purpose |
|------|---------|---------|
| 2:00, 14:00 | `app:discover:apps` | Discover new apps + auto-scrape |
| 4:00 | `app:scrape:apps --rescrape --limit=500` | Re-scrape existing for delta tracking |
| 5:00 | `app:storeleads:sync` | Store enrichment |
| Configurable | `app:ai:compile-batch` | AI extraction |
| Configurable | `app:ai:poll-batches` | AI polling |

---

## Customer Portal

### Enhanced Dashboard (`/customer`)

**Existing** (keep): Stats cards, Sentiment Timeline, Pain Points Radar, Review Velocity, My Apps table.

**New — Change Feed section** below My Apps:
- Chronological list of recent changes across all followed apps.
- Each entry: app name, field that changed, old value → new value, timestamp.
- "Mark all as read" button.
- Links to app profile.

### Enhanced App Profile (`/customer/apps/{id}`)

**Existing** (keep): Header, AI summary, pain points, reviews table.

**New — Competitor Comparison** (if account follows multiple apps):
- Side-by-side cards: "My App" (kind=own) vs up to 3 competitors (kind=competitor).
- Metrics compared: rating, total reviews, review velocity (30d), negative sentiment %, top pain point.
- Only shown if the account has at least 1 `own` and 1 `competitor` app followed.

**New — Change History Timeline** for this specific app:
- Chronological list of all `app_changes` for this app.
- Displayed as timeline cards: "May 25 — Pricing changed from Free to $20/mo".

### Notification Bell

In `CustomerLayout` header:
- Bell icon with unread count badge.
- Dropdown showing latest 10 unread notifications.
- Each notification: "{App Name} — {field} changed" with link to app profile.
- "View all" links to a full notifications page or the dashboard change feed.

---

## Admin Portal

### New Page: App Changes (`/admin/app-changes`)

**Route:** `GET /admin/app-changes` → `AdminAppChangeController@index`

**Content:**
- Paginated DataTable of all `app_changes` across the marketplace.
- Columns: Date, App Name, Field, Old Value, New Value.
- Filters: field (select), app search (text), date range.

**Navigation:** Add "Changes" to admin sidebar under "Shopify Data".

### Enhanced Dashboard

Add 3 new stat cards to the existing admin dashboard:
- "Price Changes This Week" — count of `app_changes` where field = pricing_raw in last 7 days.
- "Rating Drops > 0.5" — count where field = average_rating and old - new > 0.5 in last 7 days.
- "New Apps This Week" — count of shopify_apps created in last 7 days.

---

## Configuration

In `config/scraping.php`, add:

```php
'intelligence' => [
    'rating_change_threshold' => (float) env('INTELLIGENCE_RATING_THRESHOLD', 0.05),
    'tracked_fields' => [
        'name', 'developer_name', 'description_hash', 'pricing_raw',
        'pricing_min_usd', 'pricing_has_free', 'average_rating',
        'total_reviews', 'category_name',
    ],
],
```

---

## Seeding Existing Apps

The first time `app:scrape:apps --rescrape` runs after deployment, it creates the initial snapshot for each app. No changes are detected on the first snapshot (no previous to compare). From the second scrape onward, change detection is active.

No manual seeding required — the pipeline bootstraps itself.

---

## Error Handling

- Snapshot creation failure should not block the scrape job. Wrap in try/catch, log warning, continue.
- Notification creation failure should not block change detection. Wrap in try/catch per account.
- If an app has no followers, changes are still recorded in `app_changes` but no notifications created.
- `SnapshotDiffService` is a pure function — no external calls, no failure modes beyond null data.

---

## Testing

- Unit test for `SnapshotDiffService::diff()` — verify field comparison, threshold, and ignored fields.
- Feature test for snapshot creation hook in `ScrapeAppPageJob` — verify snapshot created after scrape.
- Feature test for change detection — scrape same app twice with different data, verify `app_changes` records.
- Feature test for notification generation — followed app changes, verify `app_change_notifications` created for following accounts.
- Feature test for `AdminAppChangeController` — index renders, filters work.
- Feature test for `--rescrape` flag on `ScrapeAppsCommand` — enqueues already-scraped apps.
