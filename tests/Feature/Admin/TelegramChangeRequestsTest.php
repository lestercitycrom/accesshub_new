<?php

declare(strict_types=1);

use App\Admin\Livewire\Accounts\AccountForm;
use App\Admin\Livewire\Accounts\AccountsIndex;
use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

it('shows same-login overlapping-platform duplicates despite a title suffix', function (): void {
	$admin = User::factory()->admin()->create();

	$first = Account::factory()->create([
		'game' => 'Tomb Raider I-III Remastered',
		'platform' => ['Epic Games'],
		'login' => 'ufc6@mailhub.uno',
		'mail_account_login' => 'ufc6@mailhub.uno',
	]);
	$second = Account::factory()->create([
		'game' => 'Tomb Raider I-III Remastered Epic',
		'platform' => ['Epic Games'],
		'login' => 'UFC6@mailhub.uno',
		'mail_account_login' => 'UFC6@mailhub.uno',
	]);
	$differentPlatform = Account::factory()->create([
		'game' => 'Tomb Raider I-III Remastered',
		'platform' => ['Steam'],
		'login' => 'ufc6@mailhub.uno',
		'mail_account_login' => 'ufc6@mailhub.uno',
	]);

	Livewire::actingAs($admin)
		->test(AccountsIndex::class)
		->call('toggleDuplicates')
		->assertSet('duplicatesOnly', true)
		->assertViewHas('rows', function ($rows) use ($first, $second, $differentPlatform): bool {
			$ids = collect($rows->items())->pluck('id');

			return $ids->contains($first->id)
				&& $ids->contains($second->id)
				&& !$ids->contains($differentPlatform->id)
				&& $ids->count() === 2;
		});
});

it('lets an operator create an active account without supply-level mutations', function (): void {
	$operator = User::factory()->operator()->create();

	Livewire::actingAs($operator)
		->test(AccountForm::class, ['account' => null])
		->set('game', 'Cyberpunk 2077')
		->set('platformSelected', ['Steam'])
		->set('login', 'operator-created@example.com')
		->set('password', 'secret-value')
		->set('maxUses', 1)
		->set('availableUses', 1)
		->set('cooldownDays', '30')
		->set('status', AccountStatus::TEMP_HOLD->value)
		->set('flagActionRequired', true)
		->call('save')
		->assertHasNoErrors()
		->assertRedirect(route('admin.accounts.index'));

	$account = Account::query()->where('login', 'operator-created@example.com')->firstOrFail();

	expect($account->status)->toBe(AccountStatus::ACTIVE)
		->and($account->cooldown_days)->toBe(30)
		->and($account->flags)->toBeNull()
		->and($account->assigned_to_telegram_id)->toBeNull();
});

it('imports the same game login as separate accounts on different platforms', function (): void {
	$admin = User::factory()->admin()->create();
	$csv = implode("\n", [
		'Game Name,Platform,Console Account Login,Console Account Password',
		'Tomb Raider I-III Remastered,Epic Games,shared@mailhub.uno,epic-password',
		'Tomb Raider I-III Remastered,Steam,shared@mailhub.uno,steam-password',
	]);

	$this->actingAs($admin)
		->post(route('admin.accounts.import'), [
			'file' => UploadedFile::fake()->createWithContent('accounts.csv', $csv),
		])
		->assertSessionHas('status');

	$accounts = Account::query()
		->where('game', 'Tomb Raider I-III Remastered')
		->where('login', 'shared@mailhub.uno')
		->get();

	expect($accounts)->toHaveCount(2)
		->and($accounts->pluck('platform')->all())->toContain(['Epic Games'], ['Steam']);
});

it('uses an account cooldown override when its issuance limit is reached', function (): void {
	$now = CarbonImmutable::parse('2026-08-10 12:00:00');
	CarbonImmutable::setTestNow($now);

	try {
		$user = TelegramUser::factory()->create([
			'telegram_id' => 20260810,
			'role' => TelegramRole::OPERATOR,
			'is_active' => true,
		]);
		$account = Account::factory()->create([
			'game' => 'Cyberpunk 2077',
			'platform' => ['Steam'],
			'max_uses' => 1,
			'available_uses' => 1,
			'cooldown_days' => 30,
		]);

		$result = app(IssueService::class)->issue(
			$user->telegram_id,
			'ORDER-CUSTOM-COOLDOWN',
			'Cyberpunk 2077',
			'Steam',
			1,
		);

		expect($result->ok())->toBeTrue()
			->and($account->refresh()->available_uses)->toBe(0)
			->and($account->next_release_at?->equalTo($now->addDays(30)))->toBeTrue();
	} finally {
		CarbonImmutable::setTestNow();
	}
});
