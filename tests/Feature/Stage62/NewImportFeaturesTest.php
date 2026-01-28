<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createAdminUser(): User
{
	return User::factory()->create([
		'is_admin' => true,
		'email_verified_at' => now(),
	]);
}

function createCsvFile(string $content): UploadedFile
{
	$tmpDir = storage_path('framework/testing');
	if (!is_dir($tmpDir)) {
		mkdir($tmpDir, 0777, true);
	}
	$tmpPath = tempnam($tmpDir, 'ah_csv_');
	if ($tmpPath === false) {
		throw new RuntimeException('Failed to create temp file for CSV import.');
	}
	file_put_contents($tmpPath, $content);
	return new UploadedFile($tmpPath, 'import_test.csv', 'text/csv', null, true);
}

function fakeTelegram(): void
{
	config()->set('services.telegram.bot_token', 'test');
	Http::fake([
		'https://api.telegram.org/bottest/sendMessage' => Http::response(['ok' => true], 200),
	]);
}

it('imports accounts with new fields', function (): void {
	$admin = createAdminUser();
	$this->actingAs($admin);

	$csv = implode("\n", [
		'Game Name,Game Name .1,Console Account Login,Console Account Password,Mail Account Login,Mail Account Password,Comment,2-fa Mail Account Date,Recover Code',
		'cs2,PS4,user1@test.com,pass123,mail1@test.com,mailpass1,Test comment,2024-01-15,recover123',
		'cs2,PS5,user2@test.com,pass456,mail2@test.com,mailpass2,Another comment,2024-01-20,recover456',
	]);

	$file = createCsvFile($csv);
	$this->post('/admin/accounts/import', ['file' => $file])
		->assertSessionHas('status');

	expect(Account::query()->count())->toBe(2);

	$account1 = Account::query()->where('login', 'user1@test.com')->first();
	expect($account1)->not->toBeNull();
	expect($account1->mail_account_login)->toBe('mail1@test.com');
	expect($account1->mail_account_password)->toBe('mailpass1');
	expect($account1->comment)->toBe('Test comment');
	expect($account1->two_fa_mail_account_date->format('Y-m-d'))->toBe('2024-01-15');
	expect($account1->recover_code)->toBe('recover123');
	expect($account1->platform)->toBe(['PS4']);

	$account2 = Account::query()->where('login', 'user2@test.com')->first();
	expect($account2)->not->toBeNull();
	expect($account2->platform)->toBe(['PS5']);
})->group('Stage62');

it('imports accounts with multiple platforms separated by &', function (): void {
	$admin = createAdminUser();
	$this->actingAs($admin);

	$csv = implode("\n", [
		'Game Name,Game Name .1,Console Account Login,Console Account Password',
		'cs2,PS4&PS5,user1@test.com,pass123',
		'cs2,PS3&PS4&PS5,user2@test.com,pass456',
	]);

	$file = createCsvFile($csv);
	$this->post('/admin/accounts/import', ['file' => $file])
		->assertSessionHas('status');

	expect(Account::query()->count())->toBe(2);

	$account1 = Account::query()->where('login', 'user1@test.com')->first();
	expect($account1->platform)->toBe(['PS4', 'PS5']);

	$account2 = Account::query()->where('login', 'user2@test.com')->first();
	expect($account2->platform)->toBe(['PS3', 'PS4', 'PS5']);
})->group('Stage62');

it('updates existing account on re-import', function (): void {
	$admin = createAdminUser();
	$this->actingAs($admin);

	// First import
	$csv1 = implode("\n", [
		'Game Name,Game Name .1,Console Account Login,Console Account Password,Comment',
		'cs2,PS4,user1@test.com,pass123,Old comment',
	]);
	$file1 = createCsvFile($csv1);
	$this->post('/admin/accounts/import', ['file' => $file1]);

	expect(Account::query()->count())->toBe(1);
	$account = Account::query()->where('login', 'user1@test.com')->first();
	expect($account->comment)->toBe('Old comment');
	expect($account->platform)->toBe(['PS4']);

	// Re-import with updated data
	$csv2 = implode("\n", [
		'Game Name,Game Name .1,Console Account Login,Console Account Password,Comment',
		'cs2,PS4&PS5,user1@test.com,newpass456,New comment',
	]);
	$file2 = createCsvFile($csv2);
	$this->post('/admin/accounts/import', ['file' => $file2]);

	expect(Account::query()->count())->toBe(1); // Still 1 account
	$account->refresh();
	expect($account->comment)->toBe('New comment');
	expect($account->password)->toBe('newpass456');
	expect($account->platform)->toBe(['PS4', 'PS5']);
})->group('Stage62');

it('handles CSV with column name synonyms', function (): void {
	$admin = createAdminUser();
	$this->actingAs($admin);

	$csv = implode("\n", [
		'Game Name,Platform,Login,Password',
		'cs2,steam,user1@test.com,pass123',
	]);

	$file = createCsvFile($csv);
	$this->post('/admin/accounts/import', ['file' => $file])
		->assertSessionHas('status');

	expect(Account::query()->count())->toBe(1);
	$account = Account::query()->where('login', 'user1@test.com')->first();
	expect($account->game)->toBe('cs2');
	expect($account->platform)->toBe(['steam']);
})->group('Stage62');

it('reports unrecognized columns', function (): void {
	$admin = createAdminUser();
	$this->actingAs($admin);

	$csv = implode("\n", [
		'Game Name,Game Name .1,Console Account Login,Console Account Password,Unknown Column,Another Unknown',
		'cs2,PS4,user1@test.com,pass123,value1,value2',
	]);

	$file = createCsvFile($csv);
	$response = $this->post('/admin/accounts/import', ['file' => $file])
		->assertSessionHas('status');

	$status = session('status');
	expect($status)->toContain('нераспознанные колонки');
	expect($status)->toContain('Unknown Column');
	expect($status)->toContain('Another Unknown');

	expect(Account::query()->count())->toBe(1); // Account still imported
})->group('Stage62');

it('validates required fields', function (): void {
	$admin = createAdminUser();
	$this->actingAs($admin);

	$csv = implode("\n", [
		'Game Name,Console Account Login,Console Account Password',
		'cs2,user1@test.com,pass123',
	]);

	$file = createCsvFile($csv);
	$response = $this->post('/admin/accounts/import', ['file' => $file]);

	$status = session('status');
	expect($status)->toContain('Отсутствуют обязательные поля');

	expect(Account::query()->count())->toBe(0);
})->group('Stage62');

it('handles multiline fields in CSV', function (): void {
	$admin = createAdminUser();
	$this->actingAs($admin);

	$csv = "Game Name,Game Name .1,Console Account Login,Console Account Password,Comment\n";
	$csv .= '"cs2","PS4","user1@test.com","pass123","Line 1\nLine 2\nLine 3"';

	$file = createCsvFile($csv);
	$this->post('/admin/accounts/import', ['file' => $file])
		->assertSessionHas('status');

	$account = Account::query()->where('login', 'user1@test.com')->first();
	expect($account)->not->toBeNull();
	expect($account->comment)->toContain('Line 1');
	expect($account->comment)->toContain('Line 2');
	expect($account->comment)->toContain('Line 3');
})->group('Stage62');

it('truncates data that is too long to prevent database errors', function (): void {
	$admin = createAdminUser();
	$this->actingAs($admin);

	$longGame = str_repeat('a', 70000); // Very long string
	$csv = implode("\n", [
		'Game Name,Game Name .1,Console Account Login,Console Account Password',
		$longGame . ',PS4,user1@test.com,pass123',
	]);

	$file = createCsvFile($csv);
	$this->post('/admin/accounts/import', ['file' => $file])
		->assertSessionHas('status');

	$account = Account::query()->where('login', 'user1@test.com')->first();
	expect($account)->not->toBeNull();
	expect(strlen($account->game))->toBeLessThanOrEqual(60000);
})->group('Stage62');

it('finds accounts by platform when platform is in array', function (): void {
	$admin = createAdminUser();
	$this->actingAs($admin);

	// Create account with multiple platforms
	Account::factory()->create([
		'game' => 'cs2',
		'platform' => ['PS4', 'PS5'],
		'login' => 'user1@test.com',
		'password' => 'pass123',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);

	fakeTelegram();
	$operator = TelegramUser::factory()->create([
		'telegram_id' => 7001,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);

	$service = app(IssueService::class);

	// Should find account when requesting PS4
	$result = $service->issue($operator->telegram_id, 'ORD-1', 'cs2', 'PS4', 1);
	expect($result->ok())->toBeTrue();
	expect(count($result->items))->toBe(1);
	expect($result->items[0]['login'])->toBe('user1@test.com');

	// Reset available_uses
	$account = Account::query()->where('login', 'user1@test.com')->first();
	$account->available_uses = 1;
	$account->save();

	// Should also find account when requesting PS5
	$result = $service->issue($operator->telegram_id, 'ORD-2', 'cs2', 'PS5', 1);
	expect($result->ok())->toBeTrue();
	expect(count($result->items))->toBe(1);
	expect($result->items[0]['login'])->toBe('user1@test.com');
})->group('Stage62');

it('includes comment in telegram message when present', function (): void {
	$admin = createAdminUser();
	$this->actingAs($admin);

	Account::factory()->create([
		'game' => 'cs2',
		'platform' => ['steam'],
		'login' => 'user1@test.com',
		'password' => 'pass123',
		'comment' => 'Important note: Check email first',
		'status' => AccountStatus::ACTIVE,
		'available_uses' => 1,
	]);

	fakeTelegram();
	$operator = TelegramUser::factory()->create([
		'telegram_id' => 7001,
		'role' => TelegramRole::OPERATOR,
		'is_active' => true,
	]);

	$service = app(IssueService::class);
	$result = $service->issue($operator->telegram_id, 'ORD-1', 'cs2', 'steam', 1);

	expect($result->ok())->toBeTrue();
	expect($result->items[0]['comment'])->toBe('Important note: Check email first');

	$formatter = app(\App\Telegram\Services\IssueMessageFormatter::class);
	$message = $formatter->format($result);

	expect($message)->toContain('Комментарий');
	expect($message)->toContain('Important note: Check email first');
})->group('Stage62');

it('deletes all accounts when deleteAllAccounts is called', function (): void {
	$admin = createAdminUser();
	$this->actingAs($admin);

	Account::factory()->count(5)->create();

	expect(Account::query()->count())->toBe(5);

	Livewire::actingAs($admin)
		->test(\App\Admin\Livewire\Accounts\AccountsIndex::class)
		->call('deleteAllAccounts')
		->assertSet('alertMessage', 'Удалено аккаунтов: 5.');

	expect(Account::query()->count())->toBe(0);
})->group('Stage62');

it('filters accounts by platform using JSON contains', function (): void {
	$admin = createAdminUser();
	$this->actingAs($admin);

	Account::factory()->create([
		'game' => 'cs2',
		'platform' => ['PS4', 'PS5'],
		'login' => 'user1@test.com',
	]);

	Account::factory()->create([
		'game' => 'cs2',
		'platform' => ['PS3'],
		'login' => 'user2@test.com',
	]);

	Account::factory()->create([
		'game' => 'cs2',
		'platform' => ['PS5'],
		'login' => 'user3@test.com',
	]);

	// Filter by PS4 - should find user1
	Livewire::actingAs($admin)
		->test(\App\Admin\Livewire\Accounts\AccountsIndex::class)
		->set('platformFilter', 'PS4')
		->assertSee('user1@test.com')
		->assertDontSee('user2@test.com')
		->assertDontSee('user3@test.com');

	// Filter by PS5 - should find user1 and user3
	Livewire::actingAs($admin)
		->test(\App\Admin\Livewire\Accounts\AccountsIndex::class)
		->set('platformFilter', 'PS5')
		->assertSee('user1@test.com')
		->assertDontSee('user2@test.com')
		->assertSee('user3@test.com');
})->group('Stage62');

it('handles empty optional fields gracefully', function (): void {
	$admin = createAdminUser();
	$this->actingAs($admin);

	$csv = implode("\n", [
		'Game Name,Game Name .1,Console Account Login,Console Account Password,Mail Account Login,Mail Account Password,Comment,2-fa Mail Account Date,Recover Code',
		'cs2,PS4,user1@test.com,pass123,,,',
	]);

	$file = createCsvFile($csv);
	$this->post('/admin/accounts/import', ['file' => $file])
		->assertSessionHas('status');

	$account = Account::query()->where('login', 'user1@test.com')->first();
	expect($account)->not->toBeNull();
	expect($account->mail_account_login)->toBeNull();
	expect($account->mail_account_password)->toBeNull();
	expect($account->comment)->toBeNull();
	expect($account->two_fa_mail_account_date)->toBeNull();
	expect($account->recover_code)->toBeNull();
})->group('Stage62');

it('parses date field correctly', function (): void {
	$admin = createAdminUser();
	$this->actingAs($admin);

	$csv = implode("\n", [
		'Game Name,Game Name .1,Console Account Login,Console Account Password,2-fa Mail Account Date',
		'cs2,PS4,user1@test.com,pass123,2024-01-15',
	]);

	$file = createCsvFile($csv);
	$this->post('/admin/accounts/import', ['file' => $file])
		->assertSessionHas('status');

	$account = Account::query()->where('login', 'user1@test.com')->first();
	expect($account->two_fa_mail_account_date)->not->toBeNull();
	expect($account->two_fa_mail_account_date->format('Y-m-d'))->toBe('2024-01-15');
})->group('Stage62');
