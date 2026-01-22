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

	public function render()
	{
		return view('admin.import.accounts')->layout('layouts.admin');
	}
}