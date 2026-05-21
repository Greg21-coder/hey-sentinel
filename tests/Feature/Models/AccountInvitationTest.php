<?php

use App\Models\AccountInvitation;

it('auto-generates token and expires_at on create', function () {
    $invitation = AccountInvitation::factory()->create([
        'token' => null,
        'expires_at' => null,
    ]);

    expect(strlen($invitation->token))->toBe(64);
    expect($invitation->expires_at)->not->toBeNull();
    expect($invitation->expires_at->isFuture())->toBeTrue();
});

it('detects expired vs pending invitations', function () {
    $pending = AccountInvitation::factory()->pending()->create();
    $expired = AccountInvitation::factory()->expired()->create();
    $accepted = AccountInvitation::factory()->accepted()->create();

    expect($pending->isPending())->toBeTrue();
    expect($pending->isExpired())->toBeFalse();

    expect($expired->isExpired())->toBeTrue();
    expect($expired->isPending())->toBeFalse();

    expect($accepted->isPending())->toBeFalse();
});
