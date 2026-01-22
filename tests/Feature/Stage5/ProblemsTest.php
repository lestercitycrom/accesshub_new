<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows stolen tab by default and filters by tab', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	Account::factory()->create(['login' => 'st1', 'status' => AccountStatus::STOLEN]);
	Account::factory()->create(['login' => 'rc1', 'status' => AccountStatus::RECOVERY]);

	Livewire::test(\App\Admin\Livewire\Problems\ProblemsIndex::class)
		->assertSee('st1')
		->assertDontSee('rc1')
		->set('tab', 'RECOVERY')
		->assertSee('rc1')
		->assertDontSee('st1');
})->group('Stage5.8');

it('release to pool clears assignment/deadline/flags and writes event', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$a = Account::factory()->create([
		'status' => AccountStatus::STOLEN,
		'assigned_to_telegram_id' => 999,
		'status_deadline_at' => now()->addDays(3),
		'flags' => ['ACTION_REQUIRED' => true, 'PASSWORD_UPDATE_REQUIRED' => true],
	]);

	Livewire::test(\App\Admin\Livewire\Problems\ProblemsIndex::class)
		->set('selected', [$a->id])
		->call('releaseToPool');

	$a->refresh();

	expect($a->status)->toBe(AccountStatus::ACTIVE);
	expect($a->assigned_to_telegram_id)->toBeNull();
	expect($a->status_deadline_at)->toBeNull();
	expect($a->flags)->toBe([]);

	expect(AccountEvent::query()->where('account_id', $a->id)->where('type', 'RELEASE_TO_POOL')->exists())->toBeTrue();
})->group('Stage5.8');

it('extend deadline works only for stolen and writes event', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$stolen = Account::factory()->create([
		'status' => AccountStatus::STOLEN,
		'status_deadline_at' => now()->addDays(1),
	]);

	$recovery = Account::factory()->create([
		'status' => AccountStatus::RECOVERY,
		'status_deadline_at' => now()->addDays(1),
	]);

	$oldStolen = $stolen->status_deadline_at;

	Livewire::test(\App\Admin\Livewire\Problems\ProblemsIndex::class)
		->set('selected', [$stolen->id, $recovery->id])
		->set('extendDays', 2)
		->call('extendDeadline');

	$stolen->refresh();
	$recovery->refresh();

	expect($stolen->status_deadline_at)->not->toEqual($oldStolen);
	expect($recovery->status_deadline_at?->toDateTimeString())->toEqual($recovery->getOriginal('status_deadline_at'));

	expect(AccountEvent::query()->where('account_id', $stolen->id)->where('type', 'EXTEND_DEADLINE')->exists())->toBeTrue();
	expect(AccountEvent::query()->where('account_id', $recovery->id)->where('type', 'EXTEND_DEADLINE')->exists())->toBeFalse();
})->group('Stage5.8');

it('set status from problems writes SET_STATUS event', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$a = Account::factory()->create(['status' => AccountStatus::TEMP_HOLD]);

	Livewire::test(\App\Admin\Livewire\Problems\ProblemsIndex::class)
		->set('tab', 'ALL')
		->set('selected', [$a->id])
		->call('setStatus', 'DEAD');

	$a->refresh();

	expect($a->status)->toBe(AccountStatus::DEAD);
	expect(AccountEvent::query()->where('account_id', $a->id)->where('type', 'SET_STATUS')->exists())->toBeTrue();
})->group('Stage5.8');