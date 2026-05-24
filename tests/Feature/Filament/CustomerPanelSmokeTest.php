<?php

use App\Enums\FollowedAppKind;
use App\Models\Account;
use App\Models\AccountFollowedApp;
use App\Models\AccountUser;
use App\Models\ShopifyApp;
use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\PlanSeeder::class);

    $this->user = User::factory()->create();
    $this->account = Account::factory()->create(['owner_user_id' => $this->user->id]);
    AccountUser::create([
        'account_id' => $this->account->id,
        'user_id' => $this->user->id,
        'role' => \App\Enums\UserRole::Owner->value,
        'invitation_accepted_at' => now(),
    ]);

    $this->shopifyApp = ShopifyApp::factory()->create();

    // try/finally so a create() failure does not leak the disabled trait
    // state into the rest of the Pest worker process.
    AccountFollowedApp::disable();
    try {
        AccountFollowedApp::create([
            'account_id' => $this->account->id,
            'shopify_app_id' => $this->shopifyApp->id,
            'kind' => FollowedAppKind::Mine->value,
            'followed_at' => now(),
        ]);
    } finally {
        AccountFollowedApp::enable();
    }

    $this->actingAs($this->user);
});

it('customer dashboard renders', function () {
    // Widget content lazy-loads via Livewire x-intersect; assert on the page
    // title (server-rendered) instead of stat labels.
    $this->get('/customer')
        ->assertStatus(200)
        ->assertSee('Dashboard');
});

it('customer apps index loads and shows Browse', function () {
    $this->get('/customer/apps')
        ->assertStatus(200)
        ->assertSee('Browse Apps');
});

it('customer app view page renders with Reviews tab', function () {
    $this->get("/customer/apps/{$this->shopifyApp->id}")
        ->assertStatus(200)
        ->assertSee('Reviews');
});

it('non-superadmin gets redirected to admin login when hitting /admin', function () {
    auth()->logout();
    $this->get('/admin')
        ->assertRedirect('/admin/login');
});
