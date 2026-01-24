<?php

declare(strict_types=1);

use App\Domain\Accounts\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates accounts with default usage limits', function (): void {
	$account = Account::factory()->create();

	expect($account->max_uses)->toBeInt();
	expect($account->available_uses)->toBeInt();
	expect($account->max_uses)->toBeGreaterThan(0);
	expect($account->available_uses)->toBeGreaterThan(0);
})->group('StageA');
