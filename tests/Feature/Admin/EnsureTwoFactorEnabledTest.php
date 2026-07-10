<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Models\User;

it('flags a signed-in user without 2FA as needing setup', function (): void {
	$user = User::factory()->create(); // no two_factor_secret/confirmed_at

	expect((new EnsureTwoFactorEnabled())->needsSetup($user))->toBeTrue();
});

it('does not flag a user who has enabled 2FA', function (): void {
	$user = User::factory()->withTwoFactor()->create();

	expect((new EnsureTwoFactorEnabled())->needsSetup($user))->toBeFalse();
});

it('does not flag a guest (no user)', function (): void {
	expect((new EnsureTwoFactorEnabled())->needsSetup(null))->toBeFalse();
});
