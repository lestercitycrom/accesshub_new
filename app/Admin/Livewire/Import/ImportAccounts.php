<?php

declare(strict_types=1);

namespace App\Admin\Livewire\Import;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class ImportAccounts extends Component
{
	use WithFileUploads;

	public string $csvText = '';

	/** @var TemporaryUploadedFile|null */
	public ?TemporaryUploadedFile $file = null;

	public array $preview = [];
	public array $parseErrors = [];
	public bool $showPreview = false;

	public function mount(): void
	{
		Gate::authorize('admin');

		$prefill = session()->get('import.csvText');
		if (is_string($prefill) && trim($prefill) !== '') {
			$this->csvText = $prefill;
			$this->parseCsv();
		}
	}

	public function parseCsv(): void
	{
		Gate::authorize('admin');

		$this->reset(['preview', 'parseErrors', 'showPreview']);

		// If file is uploaded, read its content
		if ($this->file !== null) {
			$content = file_get_contents($this->file->getRealPath());

			if ($content === false) {
				$this->parseErrors[] = 'Не удалось прочитать загруженный файл';
				return;
			}

			$this->csvText = $content;
		}

		if (trim($this->csvText) === '') {
			$this->parseErrors[] = 'CSV-текст обязателен';
			return;
		}

		$lines = explode("\n", trim($this->csvText));
		$headerLine = array_shift($lines);

		if ($headerLine === null) {
			$this->parseErrors[] = 'CSV пуст';
			return;
		}

		$header = str_getcsv($headerLine);

		if (count($header) < 4) {
			$this->parseErrors[] = 'CSV должен содержать минимум 4 колонки: game, platform, login, password';
			return;
		}

		$this->preview = [];
		$existingLogins = [];

		foreach ($lines as $lineNumber => $line) {
			if (trim($line) === '') {
				continue;
			}

			$data = str_getcsv($line);

			if (count($data) < 4) {
				$this->parseErrors[] = 'Строка ' . ($lineNumber + 2) . ': недостаточно колонок';
				continue;
			}

			[$game, $platform, $login, $password] = array_map('trim', $data);

			if ($game === '' || $platform === '' || $login === '' || $password === '') {
				$this->parseErrors[] = 'Строка ' . ($lineNumber + 2) . ': пустые обязательные поля';
				continue;
			}

			$key = $game . '|' . $platform . '|' . $login;

			if (isset($existingLogins[$key])) {
				$this->parseErrors[] = 'Строка ' . ($lineNumber + 2) . ': дубликат логина в CSV';
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

		if ($this->parseErrors === []) {
			$this->showPreview = true;
		}
	}

	public function applyImport(): void
	{
		Gate::authorize('admin');

		if ($this->preview === []) {
			return;
		}

		$imported = 0;
		$skipped = 0;

		foreach ($this->preview as $item) {
			if (($item['exists'] ?? false) === true) {
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

		session()->flash('message', "Импорт завершён: добавлено {$imported}, пропущено {$skipped} (уже существуют)");

		// Reset only existing component properties (avoid non-existing 'errors')
		$this->reset(['csvText', 'file', 'preview', 'parseErrors', 'showPreview']);
	}

	public function getStatsProperty(): array
	{
		if (!$this->showPreview || $this->preview === []) {
			return [
				'parsed' => 0,
				'create' => 0,
				'update' => 0,
				'skipped' => 0,
				'errors' => $this->parseErrors,
			];
		}

		$create = 0;
		$update = 0;
		$skipped = 0;

		foreach ($this->preview as $item) {
			if (($item['exists'] ?? false) === true) {
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
			'errors' => $this->parseErrors,
		];
	}

	public function getPreviewRowsProperty(): array
	{
		if (!$this->showPreview || $this->preview === []) {
			return [];
		}

		return array_map(static function (array $item): array {
			return [
				'game' => $item['game'],
				'platform' => $item['platform'],
				'login' => $item['login'],
				'password' => $item['password'],
				'action' => ($item['exists'] ?? false) ? 'skip' : 'create',
				'reason' => ($item['exists'] ?? false) ? 'Уже существует' : 'Новый аккаунт',
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
		$this->reset(['csvText', 'file', 'preview', 'parseErrors', 'showPreview']);
	}

	public function render()
	{
		return view('admin.import.accounts')->layout('layouts.admin');
	}
}
