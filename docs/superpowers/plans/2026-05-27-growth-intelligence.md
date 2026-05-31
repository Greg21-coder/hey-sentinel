# Growth Intelligence Module — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Snapshot-based delta tracking that detects app listing changes, generates notifications for followers, and adds growth intelligence to both customer and admin portals.

**Architecture:** Every successful app scrape creates a snapshot and diffs it against the previous one. Detected changes are stored in `app_changes` and fan out as notifications to following accounts. The customer portal gets a change feed + notification bell; the admin portal gets a marketplace-wide change log.

**Tech Stack:** Laravel 11, PHP 8.3, Pest, Inertia.js, React 18, TypeScript, Recharts, Tailwind CSS

---

### Task 1: Migrations — app_snapshots, app_changes, app_change_notifications

**Files:**
- Create: `database/migrations/2026_05_27_200001_create_app_snapshots_table.php`
- Create: `database/migrations/2026_05_27_200002_create_app_changes_table.php`
- Create: `database/migrations/2026_05_27_200003_create_app_change_notifications_table.php`

- [ ] **Step 1: Create the three migration files**

`database/migrations/2026_05_27_200001_create_app_snapshots_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopify_app_id')->constrained('shopify_apps')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('developer_name', 255);
            $table->string('description_hash', 64)->nullable();
            $table->string('pricing_raw', 255)->nullable();
            $table->decimal('pricing_min_usd', 8, 2)->nullable();
            $table->boolean('pricing_has_free')->default(false);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->string('category_name', 100)->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->timestamp('snapshot_at');

            $table->index(['shopify_app_id', 'snapshot_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_snapshots');
    }
};
```

`database/migrations/2026_05_27_200002_create_app_changes_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopify_app_id')->constrained('shopify_apps')->cascadeOnDelete();
            $table->string('field', 50);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('detected_at');

            $table->index(['shopify_app_id', 'detected_at']);
            $table->index(['field', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_changes');
    }
};
```

`database/migrations/2026_05_27_200003_create_app_change_notifications_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_change_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_change_id')->constrained('app_changes')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['account_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_change_notifications');
    }
};
```

- [ ] **Step 2: Run migrations**

```bash
cd /home/greg/projects/HeySentinel && ./vendor/bin/sail artisan migrate
```

- [ ] **Step 3: Commit**

```bash
git add database/migrations/*app_snapshots* database/migrations/*app_changes* database/migrations/*app_change_notifications*
git commit -m "feat: add app_snapshots, app_changes, app_change_notifications migrations"
```

---

### Task 2: Eloquent Models

**Files:**
- Create: `app/Models/AppSnapshot.php`
- Create: `app/Models/AppChange.php`
- Create: `app/Models/AppChangeNotification.php`
- Modify: `app/Models/ShopifyApp.php` — add `snapshots()`, `changes()` relationships

- [ ] **Step 1: Create AppSnapshot model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'shopify_app_id',
        'name',
        'developer_name',
        'description_hash',
        'pricing_raw',
        'pricing_min_usd',
        'pricing_has_free',
        'average_rating',
        'total_reviews',
        'category_name',
        'avatar_url',
        'snapshot_at',
    ];

    protected function casts(): array
    {
        return [
            'pricing_min_usd' => 'decimal:2',
            'pricing_has_free' => 'boolean',
            'average_rating' => 'decimal:2',
            'total_reviews' => 'integer',
            'snapshot_at' => 'datetime',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(ShopifyApp::class, 'shopify_app_id');
    }
}
```

- [ ] **Step 2: Create AppChange model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppChange extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'shopify_app_id',
        'field',
        'old_value',
        'new_value',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(ShopifyApp::class, 'shopify_app_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppChangeNotification::class);
    }
}
```

- [ ] **Step 3: Create AppChangeNotification model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppChangeNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'app_change_id',
        'account_id',
        'read_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function change(): BelongsTo
    {
        return $this->belongsTo(AppChange::class, 'app_change_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
```

- [ ] **Step 4: Add relationships to ShopifyApp model**

In `app/Models/ShopifyApp.php`, add after the `followers()` method:

```php
public function snapshots(): HasMany
{
    return $this->hasMany(AppSnapshot::class);
}

public function changes(): HasMany
{
    return $this->hasMany(AppChange::class);
}
```

- [ ] **Step 5: Commit**

```bash
git add app/Models/AppSnapshot.php app/Models/AppChange.php app/Models/AppChangeNotification.php app/Models/ShopifyApp.php
git commit -m "feat: add AppSnapshot, AppChange, AppChangeNotification models"
```

---

### Task 3: SnapshotDiffService + Config

**Files:**
- Create: `app/Services/Intelligence/SnapshotDiffService.php`
- Create: `tests/Unit/Intelligence/SnapshotDiffServiceTest.php`
- Modify: `config/scraping.php` — add `intelligence` config block

- [ ] **Step 1: Add intelligence config**

In `config/scraping.php`, add after the `'cooldown'` block:

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

- [ ] **Step 2: Write the failing test**

Create `tests/Unit/Intelligence/SnapshotDiffServiceTest.php`:

```php
<?php

use App\Models\AppSnapshot;
use App\Models\ShopifyApp;
use App\Services\Intelligence\SnapshotDiffService;

it('detects field changes between two snapshots', function () {
    $service = new SnapshotDiffService;

    $previous = new AppSnapshot([
        'name' => 'Old Name',
        'developer_name' => 'Dev',
        'pricing_raw' => 'Free',
        'pricing_min_usd' => 0,
        'pricing_has_free' => true,
        'average_rating' => 4.50,
        'total_reviews' => 100,
        'category_name' => 'Marketing',
        'description_hash' => 'abc123',
        'avatar_url' => null,
    ]);

    $current = new AppSnapshot([
        'name' => 'New Name',
        'developer_name' => 'Dev',
        'pricing_raw' => '$20/mo',
        'pricing_min_usd' => 20.00,
        'pricing_has_free' => false,
        'average_rating' => 4.50,
        'total_reviews' => 110,
        'category_name' => 'Marketing',
        'description_hash' => 'abc123',
        'avatar_url' => null,
    ]);

    $changes = $service->diff($previous, $current);

    $fields = array_column($changes, 'field');
    expect($fields)->toContain('name');
    expect($fields)->toContain('pricing_raw');
    expect($fields)->toContain('pricing_min_usd');
    expect($fields)->toContain('pricing_has_free');
    expect($fields)->toContain('total_reviews');
    expect($fields)->not->toContain('developer_name');
    expect($fields)->not->toContain('category_name');
    expect($fields)->not->toContain('description_hash');
});

it('ignores rating changes below threshold', function () {
    $service = new SnapshotDiffService;

    $previous = new AppSnapshot([
        'name' => 'App', 'developer_name' => 'Dev', 'pricing_raw' => null,
        'pricing_min_usd' => null, 'pricing_has_free' => false,
        'average_rating' => 4.50, 'total_reviews' => 100,
        'category_name' => null, 'description_hash' => null, 'avatar_url' => null,
    ]);

    $current = new AppSnapshot([
        'name' => 'App', 'developer_name' => 'Dev', 'pricing_raw' => null,
        'pricing_min_usd' => null, 'pricing_has_free' => false,
        'average_rating' => 4.53, 'total_reviews' => 100,
        'category_name' => null, 'description_hash' => null, 'avatar_url' => null,
    ]);

    $changes = $service->diff($previous, $current);
    expect($changes)->toBeEmpty();
});

it('detects rating changes above threshold', function () {
    $service = new SnapshotDiffService;

    $previous = new AppSnapshot([
        'name' => 'App', 'developer_name' => 'Dev', 'pricing_raw' => null,
        'pricing_min_usd' => null, 'pricing_has_free' => false,
        'average_rating' => 4.50, 'total_reviews' => 100,
        'category_name' => null, 'description_hash' => null, 'avatar_url' => null,
    ]);

    $current = new AppSnapshot([
        'name' => 'App', 'developer_name' => 'Dev', 'pricing_raw' => null,
        'pricing_min_usd' => null, 'pricing_has_free' => false,
        'average_rating' => 4.20, 'total_reviews' => 100,
        'category_name' => null, 'description_hash' => null, 'avatar_url' => null,
    ]);

    $changes = $service->diff($previous, $current);
    $fields = array_column($changes, 'field');
    expect($fields)->toContain('average_rating');
});

it('creates snapshot from ShopifyApp model', function () {
    $app = ShopifyApp::factory()->create([
        'name' => 'Test App',
        'developer_name' => 'Dev Co',
        'description' => 'A test app description',
        'pricing_raw' => 'Free',
        'average_rating' => 4.80,
        'total_reviews' => 500,
    ]);

    $service = new SnapshotDiffService;
    $snapshot = $service->createSnapshot($app);

    expect($snapshot->shopify_app_id)->toBe($app->id);
    expect($snapshot->name)->toBe('Test App');
    expect($snapshot->description_hash)->toBe(hash('sha256', 'A test app description'));
    expect($snapshot->average_rating)->toBe('4.80');
    expect($snapshot->total_reviews)->toBe(500);
});
```

- [ ] **Step 3: Run test to verify it fails**

```bash
cd /home/greg/projects/HeySentinel && ./vendor/bin/sail test tests/Unit/Intelligence/SnapshotDiffServiceTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 4: Implement SnapshotDiffService**

Create `app/Services/Intelligence/SnapshotDiffService.php`:

```php
<?php

namespace App\Services\Intelligence;

use App\Models\AppSnapshot;
use App\Models\ShopifyApp;

class SnapshotDiffService
{
    public function createSnapshot(ShopifyApp $app): AppSnapshot
    {
        return AppSnapshot::create([
            'shopify_app_id' => $app->id,
            'name' => $app->name ?? '',
            'developer_name' => $app->developer_name ?? '',
            'description_hash' => $app->description ? hash('sha256', $app->description) : null,
            'pricing_raw' => $app->pricing_raw,
            'pricing_min_usd' => $app->pricing_min_usd,
            'pricing_has_free' => (bool) $app->pricing_has_free,
            'average_rating' => $app->average_rating ?? 0,
            'total_reviews' => $app->total_reviews ?? 0,
            'category_name' => $app->category?->name,
            'avatar_url' => $app->avatar_url,
            'snapshot_at' => now(),
        ]);
    }

    /** @return list<array{field: string, old: mixed, new: mixed}> */
    public function diff(AppSnapshot $previous, AppSnapshot $current): array
    {
        $trackedFields = config('scraping.intelligence.tracked_fields', []);
        $ratingThreshold = (float) config('scraping.intelligence.rating_change_threshold', 0.05);

        $changes = [];

        foreach ($trackedFields as $field) {
            $oldVal = $previous->getAttribute($field);
            $newVal = $current->getAttribute($field);

            if ($field === 'average_rating') {
                if (abs((float) $oldVal - (float) $newVal) < $ratingThreshold) {
                    continue;
                }
            }

            if ($this->valuesAreDifferent($oldVal, $newVal)) {
                $changes[] = [
                    'field' => $field,
                    'old' => $this->castToString($oldVal),
                    'new' => $this->castToString($newVal),
                ];
            }
        }

        return $changes;
    }

    protected function valuesAreDifferent(mixed $old, mixed $new): bool
    {
        if ($old === null && $new === null) {
            return false;
        }

        return (string) $old !== (string) $new;
    }

    protected function castToString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
```

- [ ] **Step 5: Run tests**

```bash
cd /home/greg/projects/HeySentinel && ./vendor/bin/sail test tests/Unit/Intelligence/SnapshotDiffServiceTest.php
```

Expected: 4 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/Intelligence/SnapshotDiffService.php tests/Unit/Intelligence/SnapshotDiffServiceTest.php config/scraping.php
git commit -m "feat: add SnapshotDiffService with configurable field tracking and rating threshold"
```

---

### Task 4: Hook SnapshotDiffService into ScrapeAppPageJob

**Files:**
- Modify: `app/Jobs/Scraping/ScrapeAppPageJob.php`
- Create: `tests/Feature/Intelligence/SnapshotHookTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Intelligence/SnapshotHookTest.php`:

```php
<?php

use App\Enums\ScrapingStatus;
use App\Jobs\Scraping\ScrapeAppPageJob;
use App\Jobs\Scraping\ScrapeReviewPageJob;
use App\Models\Account;
use App\Models\AccountFollowedApp;
use App\Models\AppChange;
use App\Models\AppChangeNotification;
use App\Models\AppSnapshot;
use App\Models\ShopifyApp;
use App\Services\Scraping\ShopifyAppPageParser;
use App\Services\Scraping\ShopifyAppScraper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake(ScrapeReviewPageJob::class);
    $this->seed(\Database\Seeders\PlanSeeder::class);
});

it('creates a snapshot after successful scrape', function () {
    $html = file_get_contents(base_path('tests/Fixtures/Scraping/app-klaviyo.html'));
    Http::fake(['apps.shopify.com/*' => Http::response($html, 200)]);

    (new ScrapeAppPageJob('klaviyo-email-marketing'))
        ->handle(app(ShopifyAppScraper::class), app(ShopifyAppPageParser::class));

    $app = ShopifyApp::where('shopify_app_handle', 'klaviyo-email-marketing')->first();
    expect(AppSnapshot::where('shopify_app_id', $app->id)->count())->toBe(1);
});

it('detects changes on second scrape and creates notifications', function () {
    $app = ShopifyApp::create([
        'shopify_app_handle' => 'test-change-app',
        'name' => 'Old Name',
        'developer_name' => 'Dev',
        'pricing_raw' => 'Free',
        'average_rating' => 4.50,
        'total_reviews' => 100,
        'scraping_status' => ScrapingStatus::Scraped->value,
    ]);

    AppSnapshot::create([
        'shopify_app_id' => $app->id,
        'name' => 'Old Name',
        'developer_name' => 'Dev',
        'pricing_raw' => 'Free',
        'pricing_has_free' => true,
        'average_rating' => 4.50,
        'total_reviews' => 100,
        'snapshot_at' => now()->subDay(),
    ]);

    $account = Account::factory()->create();
    AccountFollowedApp::create([
        'account_id' => $account->id,
        'shopify_app_id' => $app->id,
        'kind' => 'competitor',
        'followed_at' => now(),
    ]);

    $html = file_get_contents(base_path('tests/Fixtures/Scraping/app-klaviyo.html'));
    Http::fake(['apps.shopify.com/*' => Http::response($html, 200)]);

    (new ScrapeAppPageJob('test-change-app'))
        ->handle(app(ShopifyAppScraper::class), app(ShopifyAppPageParser::class));

    expect(AppSnapshot::where('shopify_app_id', $app->id)->count())->toBe(2);
    expect(AppChange::where('shopify_app_id', $app->id)->count())->toBeGreaterThan(0);
    expect(AppChangeNotification::where('account_id', $account->id)->count())->toBeGreaterThan(0);
});

it('does not create changes on first scrape', function () {
    $html = file_get_contents(base_path('tests/Fixtures/Scraping/app-klaviyo.html'));
    Http::fake(['apps.shopify.com/*' => Http::response($html, 200)]);

    (new ScrapeAppPageJob('first-time-app'))
        ->handle(app(ShopifyAppScraper::class), app(ShopifyAppPageParser::class));

    $app = ShopifyApp::where('shopify_app_handle', 'first-time-app')->first();
    expect(AppSnapshot::where('shopify_app_id', $app->id)->count())->toBe(1);
    expect(AppChange::where('shopify_app_id', $app->id)->count())->toBe(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd /home/greg/projects/HeySentinel && ./vendor/bin/sail test tests/Feature/Intelligence/SnapshotHookTest.php
```

Expected: FAIL — no snapshot created.

- [ ] **Step 3: Add the hook to ScrapeAppPageJob**

In `app/Jobs/Scraping/ScrapeAppPageJob.php`, add imports:

```php
use App\Models\AccountFollowedApp;
use App\Models\AppChange;
use App\Models\AppChangeNotification;
use App\Services\Intelligence\SnapshotDiffService;
```

After the `Log::info('ScrapeAppPageJob success', ...)` call (line ~107), add:

```php
try {
    $diffService = app(SnapshotDiffService::class);
    $app->load('category');
    $previousSnapshot = $app->snapshots()->orderByDesc('snapshot_at')->first();
    $currentSnapshot = $diffService->createSnapshot($app);

    if ($previousSnapshot) {
        $changes = $diffService->diff($previousSnapshot, $currentSnapshot);
        $followerAccountIds = AccountFollowedApp::withoutGlobalScope('account')
            ->where('shopify_app_id', $app->id)
            ->pluck('account_id');

        foreach ($changes as $change) {
            $appChange = AppChange::create([
                'shopify_app_id' => $app->id,
                'field' => $change['field'],
                'old_value' => $change['old'],
                'new_value' => $change['new'],
                'detected_at' => now(),
            ]);

            foreach ($followerAccountIds as $accountId) {
                AppChangeNotification::create([
                    'app_change_id' => $appChange->id,
                    'account_id' => $accountId,
                    'created_at' => now(),
                ]);
            }
        }
    }
} catch (\Throwable $e) {
    Log::warning('ScrapeAppPageJob: snapshot/diff failed', [
        'handle' => $this->handle,
        'error' => $e->getMessage(),
    ]);
}
```

- [ ] **Step 4: Run tests**

```bash
cd /home/greg/projects/HeySentinel && ./vendor/bin/sail test tests/Feature/Intelligence/SnapshotHookTest.php
```

Expected: 3 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/Scraping/ScrapeAppPageJob.php tests/Feature/Intelligence/SnapshotHookTest.php
git commit -m "feat: hook snapshot creation and change detection into ScrapeAppPageJob"
```

---

### Task 5: --rescrape flag on ScrapeAppsCommand + Cron Update

**Files:**
- Modify: `app/Console/Commands/ScrapeAppsCommand.php`
- Modify: `routes/console.php`

- [ ] **Step 1: Update ScrapeAppsCommand**

Replace the full content of `app/Console/Commands/ScrapeAppsCommand.php`:

```php
<?php

namespace App\Console\Commands;

use App\Enums\ScrapingStatus;
use App\Jobs\Scraping\ScrapeAppPageJob;
use App\Models\ShopifyApp;
use Illuminate\Console\Command;

class ScrapeAppsCommand extends Command
{
    protected $signature = 'app:scrape:apps {--limit=300 : Maximum apps to enqueue} {--rescrape : Re-scrape already-scraped apps (stalest first)}';

    protected $description = 'Enqueue scraping jobs for pending or stale Shopify apps.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        if ($this->option('rescrape')) {
            $apps = ShopifyApp::query()
                ->where('scraping_status', ScrapingStatus::Scraped->value)
                ->orderBy('last_scraped_at')
                ->limit($limit)
                ->get();
        } else {
            $apps = ShopifyApp::query()
                ->whereIn('scraping_status', [ScrapingStatus::Pending->value, ScrapingStatus::Error->value])
                ->orderBy('last_scraped_at')
                ->limit($limit)
                ->get();
        }

        $delaySeconds = 0;
        $apps->chunk(200)->each(function ($chunk) use (&$delaySeconds) {
            foreach ($chunk as $app) {
                ScrapeAppPageJob::dispatch($app->shopify_app_handle)->delay(now()->addSeconds($delaySeconds));
            }
            $delaySeconds += 90;
        });

        $this->info("Dispatched {$apps->count()} ScrapeAppPageJob(s).");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Update cron schedule**

In `routes/console.php`, replace the safety-net line:

```php
// Before:
Schedule::command('app:scrape:apps --limit=1000')->dailyAt('04:00')->withoutOverlapping();

// After:
Schedule::command('app:scrape:apps --rescrape --limit=500')->dailyAt('04:00')->withoutOverlapping();
```

- [ ] **Step 3: Verify command**

```bash
cd /home/greg/projects/HeySentinel && ./vendor/bin/sail artisan app:scrape:apps --help
```

Expected: shows `--rescrape` option.

- [ ] **Step 4: Commit**

```bash
git add app/Console/Commands/ScrapeAppsCommand.php routes/console.php
git commit -m "feat: add --rescrape flag for daily delta tracking of existing apps"
```

---

### Task 6: Admin App Changes Page

**Files:**
- Create: `app/Http/Controllers/Admin/AdminAppChangeController.php`
- Create: `resources/js/Pages/Admin/AppChanges/Index.tsx`
- Modify: `routes/web.php` — add admin routes
- Modify: `resources/js/Layouts/AdminLayout.tsx` — add sidebar link

- [ ] **Step 1: Create AdminAppChangeController**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppChange;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAppChangeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AppChange::query()->with('app:id,name,shopify_app_handle');

        if ($request->filled('field')) {
            $query->where('field', $request->input('field'));
        }

        if ($request->filled('search')) {
            $query->whereHas('app', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('search') . '%');
            });
        }

        $changes = $query->orderByDesc('detected_at')->paginate(20)->withQueryString();

        return Inertia::render('Admin/AppChanges/Index', [
            'changes' => $changes,
            'filters' => $request->only(['field', 'search']),
        ]);
    }
}
```

- [ ] **Step 2: Add routes**

In `routes/web.php`, add the import and route inside the admin group:

Import:
```php
use App\Http\Controllers\Admin\AdminAppChangeController;
```

Route (after the discovery routes):
```php
Route::get('/app-changes', [AdminAppChangeController::class, 'index'])->name('app-changes.index');
```

- [ ] **Step 3: Create React page**

Create `resources/js/Pages/Admin/AppChanges/Index.tsx`:

```tsx
import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import DataTable, { Column } from '@/Components/tables/DataTable';
import Badge from '@/Components/ui/Badge';
import { PageProps, PaginatedResponse } from '@/types';

interface AppRef {
    id: number;
    name: string;
    shopify_app_handle: string;
}

interface ChangeRow {
    id: number;
    shopify_app_id: number;
    field: string;
    old_value: string | null;
    new_value: string | null;
    detected_at: string;
    app: AppRef | null;
}

interface Props extends PageProps {
    changes: PaginatedResponse<ChangeRow>;
    filters: Record<string, string>;
}

const fieldColor = (field: string): 'danger' | 'warning' | 'info' | 'gray' => {
    if (field.startsWith('pricing')) return 'danger';
    if (field === 'average_rating') return 'warning';
    if (field === 'total_reviews') return 'info';
    return 'gray';
};

export default function AppChangesIndex({ changes, filters }: Props) {
    const columns: Column<ChangeRow>[] = [
        {
            key: 'detected_at',
            label: 'Date',
            render: (row) => new Date(row.detected_at).toLocaleString(),
        },
        {
            key: 'app',
            label: 'App',
            render: (row) => row.app?.name ?? '—',
        },
        {
            key: 'field',
            label: 'Field',
            render: (row) => <Badge color={fieldColor(row.field)}>{row.field}</Badge>,
        },
        {
            key: 'old_value',
            label: 'Old Value',
            render: (row) => (
                <span className="text-xs text-red-600 max-w-[200px] block truncate">
                    {row.old_value ?? '—'}
                </span>
            ),
        },
        {
            key: 'new_value',
            label: 'New Value',
            render: (row) => (
                <span className="text-xs text-green-600 max-w-[200px] block truncate">
                    {row.new_value ?? '—'}
                </span>
            ),
        },
    ];

    return (
        <AdminLayout>
            <Head title="App Changes" />

            <div className="space-y-4">
                <h1 className="text-2xl font-bold text-gray-900">App Changes</h1>

                <DataTable
                    columns={columns}
                    pagination={changes}
                    filters={[
                        { key: 'search', label: 'App Name', type: 'text' },
                        {
                            key: 'field',
                            label: 'Field',
                            type: 'select',
                            options: [
                                { value: 'name', label: 'Name' },
                                { value: 'pricing_raw', label: 'Pricing' },
                                { value: 'average_rating', label: 'Rating' },
                                { value: 'total_reviews', label: 'Reviews' },
                                { value: 'developer_name', label: 'Developer' },
                                { value: 'category_name', label: 'Category' },
                            ],
                        },
                    ]}
                    currentFilters={filters}
                    emptyMessage="No changes detected yet. Changes appear after apps are re-scraped."
                />
            </div>
        </AdminLayout>
    );
}
```

- [ ] **Step 4: Add sidebar link**

In `resources/js/Layouts/AdminLayout.tsx`, add to the "Shopify Data" section items after the Discovery entry:

```typescript
{
    href: '/admin/app-changes',
    label: 'Changes',
    icon: 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
},
```

- [ ] **Step 5: Build and commit**

```bash
cd /home/greg/projects/HeySentinel && ./vendor/bin/sail npm run build
git add app/Http/Controllers/Admin/AdminAppChangeController.php resources/js/Pages/Admin/AppChanges/Index.tsx resources/js/Layouts/AdminLayout.tsx routes/web.php
git commit -m "feat: add admin App Changes page with field filtering"
```

---

### Task 7: Admin Dashboard — Intelligence Stat Cards

**Files:**
- Modify: `app/Http/Controllers/Admin/AdminDashboardController.php`
- Modify: `resources/js/Pages/Admin/Dashboard.tsx`

- [ ] **Step 1: Add intelligence stats to controller**

In `AdminDashboardController::__invoke()`, before the `return Inertia::render(...)`, add:

```php
$weekAgo = now()->subWeek();

$priceChangesWeek = \App\Models\AppChange::where('field', 'pricing_raw')
    ->where('detected_at', '>=', $weekAgo)->count();

$ratingDropsWeek = \App\Models\AppChange::where('field', 'average_rating')
    ->where('detected_at', '>=', $weekAgo)
    ->whereRaw('CAST(old_value AS DECIMAL(3,2)) - CAST(new_value AS DECIMAL(3,2)) > 0.5')
    ->count();

$newAppsWeek = ShopifyApp::where('created_at', '>=', $weekAgo)->count();
```

Add to the Inertia props:

```php
'intelligence' => [
    'price_changes_week' => $priceChangesWeek,
    'rating_drops_week' => $ratingDropsWeek,
    'new_apps_week' => $newAppsWeek,
],
```

- [ ] **Step 2: Add stat cards to Dashboard.tsx**

In the admin Dashboard React page, add 3 new stat cards in a row after the existing stats section. Add the `intelligence` prop to the Props interface:

```typescript
intelligence: {
    price_changes_week: number;
    rating_drops_week: number;
    new_apps_week: number;
};
```

Render a new row:

```tsx
{/* Growth Intelligence */}
<div className="grid grid-cols-3 gap-4">
    <StatCard label="Price Changes This Week" value={intelligence.price_changes_week} />
    <StatCard label="Rating Drops > 0.5" value={intelligence.rating_drops_week} />
    <StatCard label="New Apps This Week" value={intelligence.new_apps_week} />
</div>
```

- [ ] **Step 3: Build and commit**

```bash
cd /home/greg/projects/HeySentinel && ./vendor/bin/sail npm run build
git add app/Http/Controllers/Admin/AdminDashboardController.php resources/js/Pages/Admin/Dashboard.tsx
git commit -m "feat: add growth intelligence stat cards to admin dashboard"
```

---

### Task 8: Customer Notification Bell + Change Feed

**Files:**
- Create: `app/Http/Controllers/Customer/NotificationController.php`
- Modify: `routes/web.php` — add customer notification routes
- Modify: `resources/js/Layouts/CustomerLayout.tsx` — add bell icon with unread count
- Modify: `resources/js/Pages/Customer/Dashboard.tsx` — add change feed section
- Modify: `app/Http/Controllers/Customer/CustomerDashboardController.php` — add changeFeed data

- [ ] **Step 1: Create NotificationController**

```php
<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\AppChangeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function unreadCount(Request $request): JsonResponse
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;

        $count = AppChangeNotification::where('account_id', $accountId)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function recent(Request $request): JsonResponse
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;

        $notifications = AppChangeNotification::where('account_id', $accountId)
            ->whereNull('read_at')
            ->with(['change.app:id,name,shopify_app_handle'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'app_name' => $n->change?->app?->name ?? 'Unknown',
                'app_id' => $n->change?->app?->id,
                'field' => $n->change?->field,
                'old_value' => $n->change?->old_value,
                'new_value' => $n->change?->new_value,
                'created_at' => $n->created_at?->toISOString(),
            ]);

        return response()->json(['notifications' => $notifications]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $accountId = $request->user()->currentAccount?->id ?? 0;

        AppChangeNotification::where('account_id', $accountId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
```

- [ ] **Step 2: Add customer routes**

In `routes/web.php`, inside the customer group, add:

```php
use App\Http\Controllers\Customer\NotificationController;

Route::get('/notifications/count', [NotificationController::class, 'unreadCount'])->name('notifications.count');
Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
Route::post('/notifications/mark-read', [NotificationController::class, 'markAllRead'])->name('notifications.markRead');
```

- [ ] **Step 3: Add change feed to CustomerDashboardController**

In `CustomerDashboardController::__invoke()`, before the `return Inertia::render(...)`, add:

```php
$changeFeed = [];
if ($followedIds !== []) {
    $changeFeed = \App\Models\AppChange::query()
        ->with('app:id,name')
        ->whereIn('shopify_app_id', $followedIds)
        ->orderByDesc('detected_at')
        ->limit(20)
        ->get()
        ->map(fn ($c) => [
            'id' => $c->id,
            'app_name' => $c->app?->name ?? 'Unknown',
            'app_id' => $c->shopify_app_id,
            'field' => $c->field,
            'old_value' => $c->old_value,
            'new_value' => $c->new_value,
            'detected_at' => $c->detected_at->toISOString(),
        ])
        ->toArray();
}
```

Add `'changeFeed' => $changeFeed` to the Inertia props.

- [ ] **Step 4: Add notification bell to CustomerLayout**

In `resources/js/Layouts/CustomerLayout.tsx`, add a notification bell in the header area (next to the account info/logout). The bell fetches `/customer/notifications/count` on mount and shows an unread badge. Clicking opens a dropdown with recent notifications from `/customer/notifications/recent`.

Add state and effect:

```tsx
const [unreadCount, setUnreadCount] = useState(0);
const [bellOpen, setBellOpen] = useState(false);
const [notifications, setNotifications] = useState<any[]>([]);

useEffect(() => {
    fetch('/customer/notifications/count')
        .then(r => r.json())
        .then(d => setUnreadCount(d.count))
        .catch(() => {});
}, [url]);

const openBell = () => {
    if (!bellOpen) {
        fetch('/customer/notifications/recent')
            .then(r => r.json())
            .then(d => { setNotifications(d.notifications); setBellOpen(true); })
            .catch(() => {});
    } else {
        setBellOpen(false);
    }
};

const markAllRead = () => {
    fetch('/customer/notifications/mark-read', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' } })
        .then(() => { setUnreadCount(0); setNotifications([]); setBellOpen(false); })
        .catch(() => {});
};
```

Render the bell icon in the header bar:

```tsx
<div className="relative">
    <button onClick={openBell} className="relative p-2 text-gray-500 hover:text-gray-700">
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        {unreadCount > 0 && (
            <span className="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-4 h-4 rounded-full bg-red-500 text-white text-[10px] font-bold">
                {unreadCount > 9 ? '9+' : unreadCount}
            </span>
        )}
    </button>
    {bellOpen && (
        <div className="absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-xl shadow-lg z-50">
            <div className="p-3 border-b border-gray-100 flex items-center justify-between">
                <span className="text-sm font-semibold text-gray-900">Notifications</span>
                {notifications.length > 0 && (
                    <button onClick={markAllRead} className="text-xs text-shopify-500 hover:underline">Mark all read</button>
                )}
            </div>
            <div className="max-h-64 overflow-y-auto">
                {notifications.length === 0 ? (
                    <p className="p-4 text-sm text-gray-400 text-center">No new notifications</p>
                ) : (
                    notifications.map((n: any) => (
                        <a key={n.id} href={`/customer/apps/${n.app_id}`} className="block px-3 py-2 hover:bg-gray-50 border-b border-gray-50">
                            <p className="text-sm font-medium text-gray-900">{n.app_name}</p>
                            <p className="text-xs text-gray-500">{n.field}: {n.old_value ?? '—'} → {n.new_value ?? '—'}</p>
                        </a>
                    ))
                )}
            </div>
        </div>
    )}
</div>
```

- [ ] **Step 5: Add change feed section to Dashboard.tsx**

In `resources/js/Pages/Customer/Dashboard.tsx`, add the `changeFeed` prop and render a section after My Apps:

Add to Props interface:

```typescript
changeFeed: {
    id: number;
    app_name: string;
    app_id: number;
    field: string;
    old_value: string | null;
    new_value: string | null;
    detected_at: string;
}[];
```

Render section:

```tsx
{/* Change Feed */}
{changeFeed.length > 0 && (
    <Card title="Recent Changes">
        <div className="divide-y divide-gray-100 -mx-6 -mb-5">
            {changeFeed.map((change) => (
                <a key={change.id} href={`/customer/apps/${change.app_id}`} className="flex items-center justify-between px-6 py-3 hover:bg-gray-50">
                    <div>
                        <p className="text-sm font-medium text-gray-900">{change.app_name}</p>
                        <p className="text-xs text-gray-500">
                            <span className="font-medium">{change.field}</span>: {change.old_value ?? '—'} → {change.new_value ?? '—'}
                        </p>
                    </div>
                    <span className="text-xs text-gray-400">{new Date(change.detected_at).toLocaleDateString()}</span>
                </a>
            ))}
        </div>
    </Card>
)}
```

- [ ] **Step 6: Build and commit**

```bash
cd /home/greg/projects/HeySentinel && ./vendor/bin/sail npm run build
git add app/Http/Controllers/Customer/NotificationController.php app/Http/Controllers/Customer/CustomerDashboardController.php resources/js/Layouts/CustomerLayout.tsx resources/js/Pages/Customer/Dashboard.tsx routes/web.php
git commit -m "feat: add customer notification bell, change feed, and mark-all-read"
```

---

### Task 9: Customer App Profile — Change History Timeline

**Files:**
- Modify: `app/Http/Controllers/Customer/CustomerAppController.php`
- Modify: `resources/js/Pages/Customer/AppDetail.tsx`

- [ ] **Step 1: Add change history to controller**

In the `show()` method of `CustomerAppController`, add change history data:

```php
$changeHistory = \App\Models\AppChange::where('shopify_app_id', $shopifyApp->id)
    ->orderByDesc('detected_at')
    ->limit(30)
    ->get()
    ->map(fn ($c) => [
        'id' => $c->id,
        'field' => $c->field,
        'old_value' => $c->old_value,
        'new_value' => $c->new_value,
        'detected_at' => $c->detected_at->toISOString(),
    ]);
```

Pass `'changeHistory' => $changeHistory` in the Inertia response.

- [ ] **Step 2: Add timeline section to AppDetail.tsx**

Add to Props interface:

```typescript
changeHistory: {
    id: number;
    field: string;
    old_value: string | null;
    new_value: string | null;
    detected_at: string;
}[];
```

Render a timeline section before the Reviews table:

```tsx
{changeHistory.length > 0 && (
    <Card title="Change History">
        <div className="space-y-3">
            {changeHistory.map((change) => (
                <div key={change.id} className="flex items-start gap-3">
                    <div className="mt-1 w-2 h-2 rounded-full bg-shopify-500 flex-shrink-0" />
                    <div>
                        <p className="text-sm text-gray-900">
                            <span className="font-medium">{change.field}</span> changed
                        </p>
                        <p className="text-xs text-gray-500">
                            {change.old_value ?? '—'} → {change.new_value ?? '—'}
                        </p>
                        <p className="text-xs text-gray-400">{new Date(change.detected_at).toLocaleDateString()}</p>
                    </div>
                </div>
            ))}
        </div>
    </Card>
)}
```

- [ ] **Step 3: Build and commit**

```bash
cd /home/greg/projects/HeySentinel && ./vendor/bin/sail npm run build
git add app/Http/Controllers/Customer/CustomerAppController.php resources/js/Pages/Customer/AppDetail.tsx
git commit -m "feat: add change history timeline to customer app profile"
```

---

### Task 10: Full Test Suite + Build Verification

- [ ] **Step 1: Run all tests**

```bash
cd /home/greg/projects/HeySentinel && docker compose stop horizon 2>/dev/null
./vendor/bin/sail test
docker compose start horizon 2>/dev/null
```

Expected: all tests pass including the new Intelligence tests.

- [ ] **Step 2: TypeScript build**

```bash
cd /home/greg/projects/HeySentinel && ./vendor/bin/sail npm run build
```

Expected: build succeeds.

- [ ] **Step 3: Browser verification**

1. `/admin/app-changes` — renders with empty table and filters
2. `/admin` dashboard — shows 3 new intelligence stat cards
3. Admin sidebar — "Changes" link appears under Shopify Data
4. `/customer` dashboard — change feed section appears (empty if no changes yet)
5. Customer layout — bell icon in header
6. Click bell — dropdown shows "No new notifications"

- [ ] **Step 4: Final commit if adjustments needed**

```bash
git add -A && git commit -m "chore: final adjustments for growth intelligence module"
```
