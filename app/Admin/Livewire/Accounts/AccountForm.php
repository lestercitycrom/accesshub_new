<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Accounts;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Settings\Services\SettingsService;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class AccountForm extends Component
{
	public ?Account $account = null;

	public string $game = '';
	/** @var array<int, string> */
	public array $platformSelected = [];
	public string $login = '';
	public string $password = '';
	public string $status = 'ACTIVE';
	public int $maxUses = 3;
	public int $availableUses = 3;
	public string $cooldownDays = '';
	public int $defaultCooldownDays = 30;
	public ?string $assignedToTelegramId = null;
	public ?string $statusDeadlineAt = null;
	public bool $flagActionRequired = false;
	public bool $flagPasswordUpdateRequired = false;
	public ?string $mailAccountLogin = null;
	public ?string $mailAccountPassword = null;
	public ?string $comment = null;
	public ?string $twoFaMailAccountDate = null;
	public ?string $recoverCode = null;
	public bool $isAllKeyShop = false;

	public function mount(?Account $account = null): void
	{
		Gate::authorize($account === null ? 'hub-create-account' : 'hub-supply');

		$this->account = $account;
		$this->defaultCooldownDays = app(SettingsService::class)->getInt(
			'cooldown_days',
			(int) config('accesshub.issuance.operator_cooldown_days', (int) config('accesshub.issuance.cooldown_days', 14)),
		);

		if ($account !== null) {
			$this->game = $account->game;
			$raw = is_array($account->platform) ? $account->platform : (array) $account->platform;
			$this->platformSelected = array_values(array_filter(
				array_map(fn($p) => trim((string) $p), $raw),
				fn($p) => $p !== ''
			));
			$this->login = $account->login;
			$this->password = (string) ($account->password ?? '');
			$this->status = $account->status->value;
			$this->maxUses = (int) ($account->max_uses ?? 1);
			$this->availableUses = (int) ($account->available_uses ?? 1);
			$this->cooldownDays = $account->cooldown_days !== null ? (string) $account->cooldown_days : '';
			$this->assignedToTelegramId = $account->assigned_to_telegram_id ? (string) $account->assigned_to_telegram_id : null;
			$this->statusDeadlineAt = $account->status_deadline_at?->format('Y-m-d\TH:i');

			$this->flagActionRequired = ($account->flags['ACTION_REQUIRED'] ?? false) === true;
			$this->flagPasswordUpdateRequired = ($account->flags['PASSWORD_UPDATE_REQUIRED'] ?? false) === true;

			$this->mailAccountLogin = $account->mail_account_login;
			$this->mailAccountPassword = (string) ($account->mail_account_password ?? '');
			$this->comment = $account->comment;
			$this->twoFaMailAccountDate = $account->two_fa_mail_account_date
				? (is_object($account->two_fa_mail_account_date) ? $account->two_fa_mail_account_date->format('Y-m-d') : (string) $account->two_fa_mail_account_date)
				: null;
			$this->recoverCode = $account->recover_code;
			$this->isAllKeyShop = $account->isAllKeyShop();
		}
	}

	public function save(): void
	{
		$canSupply = Gate::allows('hub-supply');
		Gate::authorize($this->account === null ? 'hub-create-account' : 'hub-supply');

		// Normalize empty strings to null so nullable validation passes
		$statusDeadlineAt = trim((string) $this->statusDeadlineAt) !== '' ? $this->statusDeadlineAt : null;
		$twoFaMailAccountDate = trim((string) $this->twoFaMailAccountDate) !== '' ? $this->twoFaMailAccountDate : null;
		$cooldownDays = trim($this->cooldownDays) !== '' ? (int) $this->cooldownDays : null;

		$platformList = $this->getEffectivePlatformList();
		$this->normalizePlatformSelected($platformList);

		$this->validate([
			'game' => ['required', 'string'],
			'platformSelected' => ['required', 'array', 'min:1'],
			'platformSelected.*' => ['string', Rule::in($platformList)],
			'login' => ['required', 'string'],
			'password' => ['required_if:account,null', 'string', 'min:1'],
			'status' => ['required', 'in:' . implode(',', array_map(fn($s) => $s->value, AccountStatus::cases()))],
			'maxUses' => ['required', 'integer', 'min:0'],
			'availableUses' => ['required', 'integer', 'min:0'],
			'cooldownDays' => ['nullable', 'integer', 'min:0', 'max:3650'],
			'assignedToTelegramId' => ['nullable', 'string'],
			'mailAccountLogin' => ['nullable', 'string'],
			'mailAccountPassword' => ['nullable', 'string'],
			'comment' => ['nullable', 'string'],
			'twoFaMailAccountDate' => ['nullable', 'string'],
			'recoverCode' => ['nullable', 'string'],
			'isAllKeyShop' => ['boolean'],
		]);

		// Re-validate optional date/datetime after normalization
		if ($statusDeadlineAt !== null && !strtotime($statusDeadlineAt)) {
			$this->addError('statusDeadlineAt', 'Некорректная дата и время.');
			return;
		}

		// Build flags array
		$flags = [];
		if ($this->flagActionRequired) {
			$flags['ACTION_REQUIRED'] = true;
		}
		if ($this->flagPasswordUpdateRequired) {
			$flags['PASSWORD_UPDATE_REQUIRED'] = true;
		}

		$platforms = array_values(array_filter(array_map('trim', $this->platformSelected), fn($p) => $p !== ''));

		if (empty($platforms)) {
			$this->addError('platformSelected', 'Выберите хотя бы одну платформу.');
			return;
		}

		$data = [
			'game' => trim((string) $this->game),
			'platform' => $platforms,
			'login' => trim((string) $this->login),
			'status' => $this->status,
			'max_uses' => $this->maxUses,
			'available_uses' => $this->availableUses,
			'cooldown_days' => $cooldownDays,
			'assigned_to_telegram_id' => ($this->assignedToTelegramId !== null && $this->assignedToTelegramId !== '') ? (int) $this->assignedToTelegramId : null,
			'status_deadline_at' => $statusDeadlineAt,
			'flags' => !empty($flags) ? $flags : null,
			'mail_account_login' => trim((string) ($this->mailAccountLogin ?? '')) ?: null,
			'comment' => trim((string) ($this->comment ?? '')) ?: null,
			'two_fa_mail_account_date' => $twoFaMailAccountDate,
			'recover_code' => trim((string) ($this->recoverCode ?? '')) ?: null,
			'source_label' => $this->isAllKeyShop ? Account::SOURCE_ALLKEYSHOP : null,
		];

		// Operators may add inventory, but may not smuggle supply-level state
		// changes through Livewire payloads.
		if (!$canSupply) {
			$data['status'] = AccountStatus::ACTIVE->value;
			$data['assigned_to_telegram_id'] = null;
			$data['status_deadline_at'] = null;
			$data['flags'] = null;
		}

		if (trim((string) ($this->password ?? '')) !== '') {
			$data['password'] = $this->password;
		}

		if (trim((string) ($this->mailAccountPassword ?? '')) !== '') {
			$data['mail_account_password'] = $this->mailAccountPassword;
		}

		if ($this->account !== null) {
			$this->account->update($data);
		} else {
			Account::query()->create($data);
			session()->flash('status', 'Аккаунт создан.');
		}

		$this->redirect(route('admin.accounts.index'), navigate: true);
	}

	public function getStatusOptionsProperty(): array
	{
		return array_map(fn($status) => $status->value, AccountStatus::cases());
	}

	public function getOperatorsProperty(): \Illuminate\Support\Collection
	{
		return TelegramUser::query()
			->where('is_active', true)
			->orderBy('username')
			->get();
	}

	/** @return list<string> */
	private function getPlatformList(): array
	{
		$list = config('accesshub.platforms', \App\Domain\Accounts\Services\PlatformCatalog::OPTIONS);

		return array_values($list);
	}

	/**
	 * The customer requested a strict catalog. Existing aliases are normalized by
	 * the data migration, and unknown values cannot be reintroduced by the form.
	 * @return list<string>
	 */
	private function getEffectivePlatformList(): array
	{
		return $this->getPlatformList();
	}

	/**
	 * Нормализует platformSelected: обрезка пробелов, приведение индексов к названиям платформ.
	 * @param list<string> $platformList
	 */
	private function normalizePlatformSelected(array $platformList): void
	{
		$normalized = [];
		foreach ($this->platformSelected as $v) {
			$v = trim((string) $v);
			if ($v === '') {
				continue;
			}
			if (in_array($v, $platformList, true)) {
				$normalized[] = $v;
			} elseif (is_numeric($v) && isset($platformList[(int) $v])) {
				$normalized[] = $platformList[(int) $v];
			}
		}
		$this->platformSelected = array_values(array_unique($normalized));
	}

	public function getPlatformOptionsProperty(): array
	{
		return $this->getEffectivePlatformList();
	}

	public function render()
	{
		$canSupply = Gate::allows('hub-supply');

		return view('admin.accounts.form', [
			'isEdit' => $this->account !== null,
			'account' => $this->account,
			'statuses' => $this->statusOptions,
			'operators' => $canSupply ? $this->operators : collect(),
			'platformOptions' => $this->platformOptions,
			'canSupply' => $canSupply,
		])->layout('layouts.admin');
	}
}
