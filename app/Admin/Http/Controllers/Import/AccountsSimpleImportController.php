<?php

declare(strict_types=1);

namespace App\Admin\Http\Controllers\Import;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AccountsSimpleImportController
{
	public function __invoke(Request $request): RedirectResponse
	{
		$request->validate([
			'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
		]);

		$file = $request->file('file');
		if ($file === null) {
			return redirect()->back()->with('status', 'Файл не загружен.');
		}

		$content = file_get_contents($file->getRealPath());
		if ($content === false) {
			return redirect()->back()->with('status', 'Не удалось прочитать файл.');
		}

		$lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $content)));
		if ($lines === []) {
			return redirect()->back()->with('status', 'Файл пуст.');
		}

		$header = str_getcsv(array_shift($lines));
		if (count($header) < 4) {
			return redirect()->back()->with('status', 'CSV должен содержать минимум 4 колонки: game, platform, login, password.');
		}

		$imported = 0;
		$duplicates = 0;
		$seen = [];

		foreach ($lines as $line) {
			if (trim($line) === '') {
				continue;
			}

			$data = str_getcsv($line);
			if (count($data) < 4) {
				continue;
			}

			[$game, $platform, $login, $password] = array_map('trim', $data);
			if ($game === '' || $platform === '' || $login === '' || $password === '') {
				continue;
			}

			$key = $game . '|' . $platform . '|' . $login;
			if (isset($seen[$key])) {
				$duplicates++;
				continue;
			}
			$seen[$key] = true;

			$exists = Account::query()
				->where('game', $game)
				->where('platform', $platform)
				->where('login', $login)
				->exists();

			if ($exists) {
				$duplicates++;
				continue;
			}

			Account::query()->create([
				'game' => $game,
				'platform' => $platform,
				'login' => $login,
				'password' => $password,
				'status' => AccountStatus::ACTIVE,
			]);

			$imported++;
		}

		return redirect()->back()->with(
			'status',
			"Импорт завершён: добавлено {$imported}, дубликатов {$duplicates}."
		);
	}
}
