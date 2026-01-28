<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Accounts\Services\AccountStatusService;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Settings\Models\Setting;
use App\Domain\Telegram\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function importBaselineAccounts($testCase): void
{
	$admin = User::factory()->create([
		'is_admin' => true,
		'email_verified_at' => now(),
	]);

	$testCase->actingAs($admin);

	$csv = implode("\n", [
		'game,platform,login,password',
		'cs2,steam,cs2_s1,pass_s1',
		'cs2,steam,cs2_s2,pass_s2',
		'cs2,steam,cs2_s3,pass_s3',
		'dota2,steam,dota_s1,pass_d1',
		'dota2,steam,dota_s2,pass_d2',
		'cs2,xbox,cs2_x1,pass_x1',
		'minecraft,steam,mc_s1,pass_m1',
		'cs2,steam,cs2_s1,pass_s1',
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

function fakeTelegramStage61(): void
{
	config()->set('services.telegram.bot_token', 'test');
	Http::fake([
		'https://api.telegram.org/bottest/sendMessage' => Http::response(['ok' => true], 200),
	]);
}

it('imports baseline accounts and ignores duplicates', function (): void {
	importBaselineAccounts($this);

	// Check session status for debugging
	$status = session('status');
	if ($status === null) {
		$this->fail("Import returned no status message");
	}
	
	if (str_contains($status, 'Ошибка') || str_contains($status, 'Отсутствуют')) {
		$this->fail("Import failed: {$status}");
	}

	// Old format CSV has 8 rows, but last one is duplicate (cs2_s1 appears twice)
	// With new logic: game+login is unique, so duplicates are updated, not ignored
	// Expected: 7 unique accounts (cs2_s1, cs2_s2, cs2_s3, dota_s1, dota_s2, cs2_x1, mc_s1)
	$count = Account::query()->count();
	if ($count === 0) {
		$this->fail("No accounts imported. Status: {$status}");
	}
	expect($count)->toBe(7);
})->group('Stage61');

it('issues accounts per order and avoids reuse within the same order', function (): void {
	importBaselineAccounts($this);

	$operator = TelegramUser::factory()->create(['telegram_id' => 7001]);
	$service = app(IssueService::class);

	$result = $service->issue($operator->telegram_id, 'ORD-OK', 'cs2', 'steam', 2);
	expect($result->ok())->toBeTrue();
	expect(Issuance::query()->where('order_id', 'ORD-OK')->count())->toBe(2);

	$result = $service->issue($operator->telegram_id, 'ORD-OK', 'cs2', 'steam', 1);
	expect($result->ok())->toBeTrue();
	expect(Issuance::query()->where('order_id', 'ORD-OK')->count())->toBe(3);

	$issuedIds = Issuance::query()->where('order_id', 'ORD-OK')->pluck('account_id')->all();
	expect(count(array_unique($issuedIds)))->toBe(3);

	$result = $service->issue($operator->telegram_id, 'ORD-OK', 'cs2', 'steam', 1);
	expect($result->ok())->toBeFalse();
})->group('Stage61');

it('fails issuance when no accounts exist for the requested game/platform', function (): void {
	importBaselineAccounts($this);

	$operator = TelegramUser::factory()->create(['telegram_id' => 7002]);
	$service = app(IssueService::class);

	$result = $service->issue($operator->telegram_id, 'ORD-NONE', 'valorant', 'steam', 1);
	expect($result->ok())->toBeFalse();
	expect(Issuance::query()->where('order_id', 'ORD-NONE')->count())->toBe(0);
})->group('Stage61');

it('webapp issue respects delivery mode and returns items when enabled', function (): void {
	importBaselineAccounts($this);
	fakeTelegramStage61();

	$operator = TelegramUser::factory()->create(['telegram_id' => 7003]);
	Setting::query()->create([
		'key' => 'webapp_issue_delivery',
		'value' => ['v' => 'webapp'],
		'updated_by_user_id' => 1,
	]);

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);
	$response = $this->postJson('/webapp/api/issue', [
		'order_id' => 'ORD-WEB',
		'game' => 'cs2',
		'platform' => 'steam',
		'qty' => 1,
	]);

	$response->assertOk()
		->assertJson([
			'ok' => true,
			'show_in_webapp' => true,
			'sent_to_chat' => false,
		]);

	expect($response->json('items'))->toHaveCount(1);
})->group('Stage61');

it('marks wrong password, issues replacement, and allows password update', function (): void {
	importBaselineAccounts($this);
	fakeTelegramStage61();

	$operator = TelegramUser::factory()->create(['telegram_id' => 7004]);
	$service = app(IssueService::class);

	$result = $service->issue($operator->telegram_id, 'ORD-PW', 'dota2', 'steam', 1);
	expect($result->ok())->toBeTrue();

	$accountId = (int) $result->items[0]['account_id'];

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);
	$this->postJson('/webapp/api/problem', [
		'account_id' => $accountId,
		'reason' => 'wrong_password',
	])->assertOk()->assertJson(['ok' => true]);

	$account = Account::query()->findOrFail($accountId);
	expect($account->status)->toBe(AccountStatus::TEMP_HOLD);
	expect($account->flags)->toHaveKey('PASSWORD_UPDATE_REQUIRED', true);

	$issuedIds = Issuance::query()->where('order_id', 'ORD-PW')->pluck('account_id')->all();
	expect(count($issuedIds))->toBe(2);
	expect(count(array_unique($issuedIds)))->toBe(2);

	$this->postJson('/webapp/api/update-password', [
		'account_id' => $accountId,
		'password' => 'new_pass_123',
	])->assertOk()->assertJson(['ok' => true]);

	$account->refresh();
	expect($account->status)->toBe(AccountStatus::ACTIVE);
	expect($account->flags ?? [])->not->toHaveKey('PASSWORD_UPDATE_REQUIRED');
})->group('Stage61');

it('marks no_email as recovery and returns no replacement when none available', function (): void {
	importBaselineAccounts($this);
	fakeTelegramStage61();

	$operator = TelegramUser::factory()->create(['telegram_id' => 7005]);
	$service = app(IssueService::class);

	$result = $service->issue($operator->telegram_id, 'ORD-NOEMAIL', 'cs2', 'xbox', 1);
	expect($result->ok())->toBeTrue();

	$accountId = (int) $result->items[0]['account_id'];

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);
	$this->postJson('/webapp/api/problem', [
		'account_id' => $accountId,
		'reason' => 'no_email',
	])->assertOk()->assertJson(['ok' => false]);

	$account = Account::query()->findOrFail($accountId);
	expect($account->status)->toBe(AccountStatus::RECOVERY);
})->group('Stage61');

it('handles stolen flow with postpone and recovery', function (): void {
	importBaselineAccounts($this);
	fakeTelegramStage61();

	$operator = TelegramUser::factory()->create(['telegram_id' => 7006]);
	$service = app(IssueService::class);

	$result = $service->issue($operator->telegram_id, 'ORD-STOLEN', 'cs2', 'steam', 1);
	expect($result->ok())->toBeTrue();

	$accountId = (int) $result->items[0]['account_id'];

	$this->withSession(['webapp.telegram_id' => $operator->telegram_id]);
	$this->postJson('/webapp/api/problem', [
		'account_id' => $accountId,
		'reason' => 'stolen',
	])->assertOk()->assertJson(['ok' => true]);

	$account = Account::query()->findOrFail($accountId);
	expect($account->status)->toBe(AccountStatus::STOLEN);
	expect($account->assigned_to_telegram_id)->toBe($operator->telegram_id);
	expect($account->status_deadline_at)->not->toBeNull();
	expect($account->flags)->toHaveKey('ACTION_REQUIRED', true);

	$oldDeadline = $account->status_deadline_at;
	$this->postJson('/webapp/api/postpone-stolen', [
		'account_id' => $accountId,
	])->assertOk()->assertJson(['ok' => true]);

	$account->refresh();
	expect($account->status_deadline_at)->not->toEqual($oldDeadline);

	$this->postJson('/webapp/api/recover-stolen', [
		'account_id' => $accountId,
		'password' => 'stolen_new_pass',
	])->assertOk()->assertJson(['ok' => true]);

	$account->refresh();
	expect($account->status)->toBe(AccountStatus::ACTIVE);
	expect($account->assigned_to_telegram_id)->toBeNull();
	expect($account->flags ?? [])->not->toHaveKey('ACTION_REQUIRED');
	expect(AccountEvent::query()
		->where('account_id', $account->id)
		->where('type', 'STOLEN_RECOVERED')
		->exists())->toBeTrue();
})->group('Stage61');

it('allows dead status only for admin telegram users', function (): void {
	$account = Account::factory()->create(['status' => AccountStatus::ACTIVE]);

	$operator = TelegramUser::factory()->create(['telegram_id' => 7010]);
	$admin = TelegramUser::factory()->admin()->create(['telegram_id' => 7011]);

	$service = app(AccountStatusService::class);

	$service->markProblem($account->id, $operator->telegram_id, 'dead');
	$account->refresh();
	expect($account->status)->toBe(AccountStatus::TEMP_HOLD);

	$service->markProblem($account->id, $admin->telegram_id, 'dead');
	$account->refresh();
	expect($account->status)->toBe(AccountStatus::DEAD);
})->group('Stage61');
