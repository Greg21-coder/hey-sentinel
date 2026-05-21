<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\BillingCycle;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\AccountUser;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $free = Plan::where('slug', 'free')->firstOrFail();
        $premium = Plan::where('slug', 'premium')->firstOrFail();
        $agency = Plan::where('slug', 'agency')->firstOrFail();

        $admin = $this->ensureUser('admin@heysentinel.test', 'Greg Altuve');
        $internal = $this->ensureAccount('heysentinel-internal', 'HeySentinel Internal', $agency->id, AccountStatus::Active, BillingCycle::None, $admin->id);
        $this->attach($internal, $admin, UserRole::Owner);

        $acmeOwner = $this->ensureUser('acme.owner@example.test', 'Alice Acme');
        $acmeAdmin = $this->ensureUser('acme.admin@example.test', 'Andy Acme');
        $acme = $this->ensureAccount('acme-corp', 'Acme Corp', $premium->id, AccountStatus::Active, BillingCycle::Monthly, $acmeOwner->id);
        $this->attach($acme, $acmeOwner, UserRole::Owner);
        $this->attach($acme, $acmeAdmin, UserRole::Admin);

        $betaOwner = $this->ensureUser('beta.owner@example.test', 'Bob Beta');
        $betaMember1 = $this->ensureUser('beta.m1@example.test', 'Brenda Beta');
        $betaMember2 = $this->ensureUser('beta.m2@example.test', 'Brian Beta');
        $beta = $this->ensureAccount('beta-industries', 'Beta Industries', $premium->id, AccountStatus::Trial, BillingCycle::None, $betaOwner->id);
        $beta->forceFill([
            'trial_started_at' => now(),
            'free_trial_ends_at' => now()->addDays(7),
        ])->save();
        $this->attach($beta, $betaOwner, UserRole::Owner);
        $this->attach($beta, $betaMember1, UserRole::Member);
        $this->attach($beta, $betaMember2, UserRole::Member);

        $gammaOwner = $this->ensureUser('gamma.owner@example.test', 'Gina Gamma');
        $gammaMember1 = $this->ensureUser('gamma.m1@example.test', 'Gabe Gamma');
        $gammaMember2 = $this->ensureUser('gamma.m2@example.test', 'Greg Gamma');
        $gamma = $this->ensureAccount('gamma-llc', 'Gamma LLC', $free->id, AccountStatus::Active, BillingCycle::None, $gammaOwner->id);
        $this->attach($gamma, $gammaOwner, UserRole::Owner);
        $this->attach($gamma, $gammaMember1, UserRole::Member);
        $this->attach($gamma, $gammaMember2, UserRole::Member);

        $deltaOwner = $this->ensureUser('delta.owner@example.test', 'Donna Delta');
        $deltaMember = $this->ensureUser('delta.m1@example.test', 'Dan Delta');
        $delta = $this->ensureAccount('delta-co', 'Delta Co', $free->id, AccountStatus::Cancelled, BillingCycle::None, $deltaOwner->id);
        $this->attach($delta, $deltaOwner, UserRole::Owner);
        $this->attach($delta, $deltaMember, UserRole::Member);

        // Cross-account memberships to exercise the multi-account invariant
        $cross1 = $this->ensureUser('cross.1@example.test', 'Chris Cross');
        $this->attach($acme, $cross1, UserRole::Member);
        $this->attach($beta, $cross1, UserRole::Member);

        $cross2 = $this->ensureUser('cross.2@example.test', 'Carla Cross');
        $this->attach($gamma, $cross2, UserRole::Owner);
        $this->attach($beta, $cross2, UserRole::Member);
    }

    protected function ensureUser(string $email, string $name): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
    }

    protected function ensureAccount(string $slug, string $name, int $planId, AccountStatus $status, BillingCycle $cycle, int $ownerId): Account
    {
        return Account::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'plan_id' => $planId,
                'status' => $status->value,
                'billing_cycle' => $cycle->value,
                'owner_user_id' => $ownerId,
            ]
        );
    }

    protected function attach(Account $account, User $user, UserRole $role): void
    {
        AccountUser::updateOrCreate(
            ['account_id' => $account->id, 'user_id' => $user->id],
            [
                'role' => $role->value,
                'invitation_accepted_at' => now(),
            ]
        );
    }
}
