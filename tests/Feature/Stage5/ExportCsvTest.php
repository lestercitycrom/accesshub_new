<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Models\Issuance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exports accounts csv with filters for admin', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	Account::factory()->create([
		'login' => 'alpha_login',
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::ACTIVE,
	]);

	Account::factory()->create([
		'login' => 'beta_login',
		'game' => 'cs2',
		'platform' => 'steam',
		'status' => AccountStatus::DEAD,
	]);

	$response = $this->get('/admin/export/accounts.csv?status=DEAD');

	$response->assertOk();
	$response->assertHeader('content-type');

	$csv = (string) $response->getContent();

	expect($csv)->toContain("id,game,platform,login,status");
	expect($csv)->toContain('beta_login');
	expect($csv)->not->toContain('alpha_login');
})->group('Stage5.7');

it('exports issuances csv and filters by order_id', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$account = Account::factory()->create([
		'game' => 'cs2',
		'platform' => 'steam',
	]);

	Issuance::factory()->create([
		'order_id' => 'ORD-AAA',
		'telegram_id' => 111,
		'account_id' => $account->id,
		'game' => 'cs2',
		'platform' => 'steam',
		'issued_at' => now(),
	]);

	Issuance::factory()->create([
		'order_id' => 'ORD-BBB',
		'telegram_id' => 111,
		'account_id' => $account->id,
		'game' => 'cs2',
		'platform' => 'steam',
		'issued_at' => now(),
	]);

	$response = $this->get('/admin/export/issuances.csv?order_id=AAA');

	$response->assertOk();

	$csv = (string) $response->getContent();

	expect($csv)->toContain("id,issued_at,order_id,telegram_id,account_id,game,platform,qty,cooldown_until,created_at");
	expect($csv)->toContain('ORD-AAA');
	expect($csv)->not->toContain('ORD-BBB');
})->group('Stage5.7');

it('forbids non-admin on export routes', function (): void {
	$user = User::factory()->create(['is_admin' => false]);
	$this->actingAs($user);

	$this->get('/admin/export/accounts.csv')->assertForbidden();
})->group('Stage5.7');