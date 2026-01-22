<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Import;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class ImportAccounts extends Component
{
	public string $csvText = '';
	public ?string $file = null;
	public array $preview = [];
	public array $errors = [];
	public bool $showPreview = false;

	public function mount(): void
	{
		Gate::authorize('admin');
	}

	public function parseCsv(): void
	{
		Gate::authorize('admin');

		$this->reset(['preview', 'errors', 'showPreview']);

		// If file is uploaded, read its content
		if ($this->file) {
			$this->csvText = file_get_contents($this->file->getRealPath());
		}

		if (empty(trim($this->csvText))) {
			$this->errors[] = 'CSV text is required';
			return;
		}

		$lines = explode("\n", trim($this->csvText));
		$header = str_getcsv(array_shift($lines));

		if (count($header) < 4) {
			$this->errors[] = 'CSV must have at least 4 columns: game, platform, login, password';
			return;
		}

		$this->preview = [];
		$existingLogins = [];

		foreach ($lines as $lineNumber => $line) {
			if (empty(trim($line))) {
				continue;
			}

			$data = str_getcsv($line);

			if (count($data) < 4) {
				$this->errors[] = "Line " . ($lineNumber + 2) . ": Not enough columns";
				continue;
			}

			[$game, $platform, $login, $password] = array_map('trim', $data);

			if (empty($game) || empty($platform) || empty($login) || empty($password)) {
				$this->errors[] = "Line " . ($lineNumber + 2) . ": Empty required fields";
				continue;
			}

			$key = $game . '|' . $platform . '|' . $login;

			if (isset($existingLogins[$key])) {
				$this->errors[] = "Line " . ($lineNumber + 2) . ": Duplicate login in CSV";
				continue;
			}

			$existingLogins[$key] = true;

			$exists = Account::query()
				->where('game', $game)
				->where('platform', $platform)
				->where('login', $login)
				->exists();

			$this->preview[] = [
				'game' => $game,
				'platform' => $platform,
				'login' => $login,
				'password' => $password,
				'exists' => $exists,
				'line' => $lineNumber + 2,
			];
		}

		if (empty($this->errors)) {
			$this->showPreview = true;
		}
	}

	public function applyImport(): void
	{
		Gate::authorize('admin');

		if (empty($this->preview)) {
			return;
		}

		$imported = 0;
		$skipped = 0;

		foreach ($this->preview as $item) {
			if ($item['exists']) {
				$skipped++;
				continue;
			}

			Account::query()->create([
				'game' => $item['game'],
				'platform' => $item['platform'],
				'login' => $item['login'],
				'password' => $item['password'],
				'status' => AccountStatus::ACTIVE,
			]);

			$imported++;
		}

		session()->flash('message', "Import completed: {$imported} imported, {$skipped} skipped (already exist)");

		$this->reset(['csvText', 'preview', 'errors', 'showPreview']);
	}

	public function getStatsProperty(): array
	{
		if (!$this->showPreview || empty($this->preview)) {
			return [
				'parsed' => 0,
				'create' => 0,
				'update' => 0,
				'skipped' => 0,
				'errors' => $this->errors,
			];
		}

		$create = 0;
		$update = 0;
		$skipped = 0;

		foreach ($this->preview as $item) {
			if ($item['exists']) {
				$skipped++;
			} else {
				$create++;
			}
		}

		return [
			'parsed' => count($this->preview),
			'create' => $create,
			'update' => $update,
			'skipped' => $skipped,
			'errors' => $this->errors,
		];
	}

	public function getPreviewRowsProperty(): array
	{
		if (!$this->showPreview || empty($this->preview)) {
			return [];
		}

		return array_map(function ($item) {
			return [
				'game' => $item['game'],
				'platform' => $item['platform'],
				'login' => $item['login'],
				'password' => $item['password'],
				'action' => $item['exists'] ? 'skip' : 'create',
				'reason' => $item['exists'] ? 'Already exists' : 'New account',
			];
		}, $this->preview);
	}

	public function preview(): void
	{
		$this->parseCsv();
	}

	public function apply(): void
	{
		$this->applyImport();
	}

	public function resetAll(): void
	{
		$this->csvText = '';
		$this->file = null;
		$this->preview = [];
		$this->errors = [];
		$this->showPreview = false;
	}

	public function render()
	{
		return view('admin.import.accounts')->layout('layouts.admin');
	}
}