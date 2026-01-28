<?php

declare(strict_types=1);

use App\Admin\Livewire\Problems\ProblemsIndex;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Telegram\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function importTestAccounts($testCase): void
{
	$csv = implode("\n", [
		'game,platform,login,password',
		'cs2,steam,cs2_s1,pass_s1',
		'cs2,steam,cs2_s2,pass_s2',
		'cs2,steam,cs2_s3,pass_s3',
		'cs2,xbox,cs2_x1,pass_x1',
		'minecraft,steam,mc_s1,pass_ms1',
		'minecraft,steam,mc_s2,pass_ms2',
	]);

	$tmpDir = storage_path('framework/testing');
	if (!is_dir($tmpDir)) {
		mkdir($tmpDir, 0777, true);
	}
	$tmpPath = tempnam($tmpDir, 'ah_csv_');
	if ($tmpPath === false) {
		throw new RuntimeException('Failed to create temp file for CSV import.');
	}
	file_put_contents($tmpPath, $csv);
	$file = new UploadedFile($tmpPath, 'import_test.csv', 'text/csv', null, true);
	$testCase->post('/admin/accounts/import', ['file' => $file])
		->assertSessionHas('status');
}

function fakeTelegramStage60(): void
{
	config()->set('services.telegram.bot_token', 'test');
	Http::fake([
		'https://api.telegram.org/bottest/sendMessage' => Http::response(['ok' => true], 200),
	]);
}

it('covers issuance flow and admin logs end-to-end', function (): void {
	config()->set('accesshub.issuance.max_qty', 2);

	$admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
	$this->actingAs($admin);

	importTestAccounts($this);

	// Platform is now JSON array, use whereJsonContains
	expect(Account::query()->where('game', 'cs2')->whereJsonContains('platform', 'steam')->count())->toBe(3);

	$operator = TelegramUser::factory()->create(['telegram_id' => 111]);
	$service = app(IssueService::class);

	$result = $service->issue(111, 'ORD-1001', 'cs2', 'steam', 1);
	expect($result->ok())->toBeTrue();
	expect(Issuance::query()->where('order_id', 'ORD-1001')->count())->toBe(1);

	$result = $service->issue(111, 'ORD-1002', 'cs2', 'steam', 2);
	expect($result->ok())->toBeTrue();
	expect(Issuance::query()->where('order_id', 'ORD-1002')->count())->toBe(2);
	expect(Issuance::query()->where('order_id', 'ORD-1002')->where('qty', 1)->count())->toBe(2);

	$result = $service->issue(111, 'ORD-1003', 'cs2', 'steam', 2);
	expect($result->ok())->toBeTrue();
	expect(Issuance::query()->where('order_id', 'ORD-1003')->count())->toBe(2);
});

it('covers problem buttons and admin problems tabs', function (): void {
	$admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
	$this->actingAs($admin);

	importTestAccounts($this);
	fakeTelegramStage60();

	$operator = TelegramUser::factory()->create(['telegram_id' => 222]);
	$service = app(IssueService::class);

	$result = $service->issue(222, 'ORD-2001', 'minecraft', 'steam', 1);
	expect($result->ok())->toBeTrue();

	$issuance = Issuance::query()->where('order_id', 'ORD-2001')->firstOrFail();
	$account = Account::query()->findOrFail($issuance->account_id);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);
	$this->postJson('/webapp/api/problem', [
		'account_id' => $account->id,
		'reason' => 'wrong_password',
	])->assertOk();

	$account->refresh();
	expect($account->status)->toBe(AccountStatus::TEMP_HOLD);
	expect(AccountEvent::query()->where('account_id', $account->id)->where('type', 'MARK_PROBLEM')->exists())->toBeTrue();

	Livewire::actingAs($admin)
		->test(ProblemsIndex::class)
		->set('tab', 'TEMP_HOLD')
		->assertSee($account->login);
});

it('covers stolen flow: postpone and recover', function (): void {
	$admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
	$this->actingAs($admin);
	fakeTelegramStage60();

	$operator = TelegramUser::factory()->create(['telegram_id' => 333]);

	$account = Account::factory()->create([
		'status' => AccountStatus::STOLEN,
		'assigned_to_telegram_id' => $operator->telegram_id,
		'status_deadline_at' => now(),
		'login' => 'stolen_login',
		'password' => 'stolen_pass',
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);
	$oldDeadline = $account->status_deadline_at;

	$this->postJson('/webapp/api/postpone-stolen', [
		'account_id' => $account->id,
	])->assertOk();

	$account->refresh();
	expect($account->status_deadline_at)->not->toEqual($oldDeadline);

	$this->postJson('/webapp/api/recover-stolen', [
		'account_id' => $account->id,
		'password' => 'new_stolen_pass',
	])->assertOk();

	$account->refresh();
	expect($account->status)->toBe(AccountStatus::ACTIVE);
	expect($account->assigned_to_telegram_id)->toBeNull();
	expect(AccountEvent::query()->where('account_id', $account->id)->where('type', 'STOLEN_RECOVERED')->exists())->toBeTrue();
});
