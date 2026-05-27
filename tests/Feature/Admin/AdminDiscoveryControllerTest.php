<?php

use App\Jobs\Scraping\DiscoverAppsFromSitemapJob;
use App\Models\Account;
use App\Models\AccountUser;
use App\Models\DiscoveryRun;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(\Database\Seeders\PlanSeeder::class);
    $this->admin = User::factory()->superadmin()->create();
});

it('renders the discovery index page', function () {
    DiscoveryRun::create([
        'source' => 'sitemap',
        'status' => 'completed',
        'triggered_by' => 'cron',
        'apps_found' => 100,
        'apps_new' => 50,
        'apps_existing' => 50,
    ]);

    $response = $this->actingAs($this->admin)->get('/admin/discovery');

    $response->assertStatus(200);
});

it('dispatches discovery job on manual run', function () {
    Queue::fake();

    $response = $this->actingAs($this->admin)->post('/admin/discovery/run');

    $response->assertRedirect();
    $response->assertSessionHas('success');

    Queue::assertPushed(DiscoverAppsFromSitemapJob::class, function ($job) {
        return $job->run->triggered_by === 'manual';
    });
});

it('rejects non-admin access', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['owner_user_id' => $user->id]);
    AccountUser::create([
        'account_id' => $account->id,
        'user_id' => $user->id,
        'role' => \App\Enums\UserRole::Owner->value,
        'invitation_accepted_at' => now(),
    ]);

    $this->actingAs($user)->get('/admin/discovery')->assertStatus(403);
    $this->actingAs($user)->post('/admin/discovery/run')->assertStatus(403);
});
