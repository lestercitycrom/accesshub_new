<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Accounts;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
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
	public ?string $assignedToTelegramId = null;
	public ?string $statusDeadlineAt = null;
	public bool $flagActionRequired = false;
	public bool $flagPasswordUpdateRequired = false;
	public ?string $mailAccountLogin = null;
	public ?string $mailAccountPassword = null;
	public ?string $comment = null;
	public ?string $twoFaMailAccountDate = null;
	public ?string $recoverCode = null;

	public function mount(?Account $account = null): void
	{
		Gate::authorize('admin');

		$this->account = $account;

		if ($account !== null) {
			$this->game = $account->game;
			$raw = is_array($account->platform) ? $account->platform : (array) $account->platform;
			$allowed = $this->getPlatformList();
			$this->platformSelected = array_values(array_filter(
				array_map(fn($p) => trim((string) $p), $raw),
				fn($p) => $p !== '' && in_array($p, $allowed, true)
			));
			$this->login = $account->login;
			$this->password = (string) ($account->password ?? '');
			$this->status = $account->status->value;
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
		}
	}

	public function save(): void
	{
		Gate::authorize('admin');

		// Normalize empty strings to null so nullable validation passes
		$statusDeadlineAt = trim((string) $this->statusDeadlineAt) !== '' ? $this->statusDeadlineAt : null;
		$twoFaMailAccountDate = trim((string) $this->twoFaMailAccountDate) !== '' ? $this->twoFaMailAccountDate : null;

		$platformList = $this->getPlatformList();
		$this->normalizePlatformSelected($platformList);

		$this->validate([
			'game' => ['required', 'string'],
			'platformSelected' => ['required', 'array', 'min:1'],
			'platformSelected.*' => ['string', Rule::in($platformList)],
			'login' => ['required', 'string'],
			'password' => ['required_if:account,null', 'string', 'min:1'],
			'status' => ['required', 'in:' . implode(',', array_map(fn($s) => $s->value, AccountStatus::cases()))],
			'assignedToTelegramId' => ['nullable', 'string'],
			'mailAccountLogin' => ['nullable', 'string'],
			'mailAccountPassword' => ['nullable', 'string'],
			'comment' => ['nullable', 'string'],
			'twoFaMailAccountDate' => ['nullable', 'string'],
			'recoverCode' => ['nullable', 'string'],
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
			'assigned_to_telegram_id' => ($this->assignedToTelegramId !== null && $this->assignedToTelegramId !== '') ? (int) $this->assignedToTelegramId : null,
			'status_deadline_at' => $statusDeadlineAt,
			'flags' => !empty($flags) ? $flags : null,
			'mail_account_login' => trim((string) ($this->mailAccountLogin ?? '')) ?: null,
			'comment' => trim((string) ($this->comment ?? '')) ?: null,
			'two_fa_mail_account_date' => $twoFaMailAccountDate,
			'recover_code' => trim((string) ($this->recoverCode ?? '')) ?: null,
		];

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
		$list = config('accesshub.platforms', ['Steam', 'PS4', 'PS5', 'Xbox', 'Epic', 'Origin', 'Battle.net', 'GOG', 'Nintendo', 'Другое']);

		return array_values($list);
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
		return $this->getPlatformList();
	}

	public function render()
	{
		return view('admin.accounts.form', [
			'isEdit' => $this->account !== null,
			'account' => $this->account,
			'statuses' => $this->statusOptions,
			'operators' => $this->operators,
			'platformOptions' => $this->platformOptions,
		])->layout('layouts.admin');
	}
}