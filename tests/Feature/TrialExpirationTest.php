<?php

use App\Models\Account;

it('isOnTrial is true when free_trial_ends_at is in the future', function () {
    $account = Account::factory()->onTrial()->create();

    expect($account->isOnTrial())->toBeTrue();
    expect($account->trialExpired())->toBeFalse();
});

it('trialExpired is true when free_trial_ends_at is in the past', function () {
    $account = Account::factory()->trialExpired()->create();

    expect($account->isOnTrial())->toBeFalse();
    expect($account->trialExpired())->toBeTrue();
});
