<?php

declare(strict_types=1);

namespace App\Admin\Http\Controllers\Import;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

		$filePath = $file->getRealPath();
		if ($filePath === false) {
			return redirect()->back()->with('status', 'Не удалось прочитать файл.');
		}

		// Use fgetcsv for proper CSV parsing with multiline support
		$handle = fopen($filePath, 'r');
		if ($handle === false) {
			return redirect()->back()->with('status', 'Не удалось открыть файл.');
		}

		try {
			// Read header row
			$header = fgetcsv($handle);
			if ($header === false || empty($header)) {
				fclose($handle);
				return redirect()->back()->with('status', 'Файл пуст или неверный формат.');
			}

			// Normalize header: trim and remove BOM
			$header = array_map(function ($col) {
				$col = trim($col);
				// Remove BOM if present
				if (str_starts_with($col, "\xEF\xBB\xBF")) {
					$col = substr($col, 3);
				}
				return $col;
			}, $header);

			// Map CSV columns to internal field names
			$fieldMapping = config('import.field_mapping', []);
			$columnMap = [];
			$unrecognizedColumns = [];

			foreach ($header as $index => $columnName) {
				$columnName = trim($columnName);
				$mapped = false;

				foreach ($fieldMapping as $fieldName => $synonyms) {
					foreach ($synonyms as $synonym) {
						if (strcasecmp($columnName, $synonym) === 0) {
							$columnMap[$fieldName] = $index;
							$mapped = true;
							break 2;
						}
					}
				}

				if (!$mapped) {
					$unrecognizedColumns[] = $columnName;
				}
			}

			// Check required fields
			$requiredFields = config('import.required_fields', []);
			$missingFields = [];
			foreach ($requiredFields as $requiredField) {
				if (!isset($columnMap[$requiredField])) {
					$missingFields[] = $requiredField;
				}
			}

			if (!empty($missingFields)) {
				fclose($handle);
				$fieldNames = implode(', ', array_map(fn($f) => config("import.field_mapping.{$f}.0", $f), $missingFields));
				return redirect()->back()->with('status', "Отсутствуют обязательные поля: {$fieldNames}");
			}

			$imported = 0;
			$updated = 0;
			$errors = 0;
			$errorsList = [];

			// Process data rows
			$rowNumber = 1;
			while (($row = fgetcsv($handle)) !== false) {
				$rowNumber++;

				// Skip empty rows
				if (empty(array_filter($row, fn($v) => trim($v) !== ''))) {
					continue;
				}

				try {
					// Extract values using column mapping
					$game = trim($row[$columnMap['game_name']] ?? '');
					$platformRaw = trim($row[$columnMap['platform']] ?? '');
					$login = trim($row[$columnMap['console_account_login']] ?? '');
					$password = trim($row[$columnMap['console_account_password']] ?? '');

					// Validate required fields
					if ($game === '' || $platformRaw === '' || $login === '' || $password === '') {
						$errors++;
						$errorsList[] = "Строка {$rowNumber}: пропущены обязательные поля";
						continue;
					}

					// Process platforms: split by "&" and trim
					$platforms = array_map('trim', explode('&', $platformRaw));
					$platforms = array_filter($platforms, fn($p) => $p !== '');
					if (empty($platforms)) {
						$errors++;
						$errorsList[] = "Строка {$rowNumber}: платформа не может быть пустой";
						continue;
					}
					$platforms = array_values($platforms); // Re-index array

					// Extract optional fields (only if column exists in CSV)
					$mailAccountLogin = isset($columnMap['mail_account_login']) ? trim($row[$columnMap['mail_account_login']] ?? '') : '';
					$mailAccountLogin = $mailAccountLogin !== '' ? $mailAccountLogin : null;
					
					$mailAccountPassword = isset($columnMap['mail_account_password']) ? trim($row[$columnMap['mail_account_password']] ?? '') : '';
					$mailAccountPassword = $mailAccountPassword !== '' ? $mailAccountPassword : null;
					
					$comment = isset($columnMap['comment']) ? trim($row[$columnMap['comment']] ?? '') : '';
					$comment = $comment !== '' ? $comment : null;
					
					$twoFaDate = isset($columnMap['two_fa_mail_account_date']) ? trim($row[$columnMap['two_fa_mail_account_date']] ?? '') : '';
					$twoFaDate = $twoFaDate !== '' ? $twoFaDate : null;
					
					$recoverCode = isset($columnMap['recover_code']) ? trim($row[$columnMap['recover_code']] ?? '') : '';
					$recoverCode = $recoverCode !== '' ? $recoverCode : null;

					// Validate and parse date
					$twoFaDateParsed = null;
					if ($twoFaDate !== null && $twoFaDate !== '') {
						try {
							$twoFaDateParsed = \Carbon\Carbon::parse($twoFaDate)->format('Y-m-d');
						} catch (\Exception $e) {
							// Invalid date, skip
							$twoFaDateParsed = null;
						}
					}

					// Validate data length to prevent "data too long" errors
					// Use reasonable limits (MySQL TEXT can store up to 65KB)
					$maxLength = 60000; // Safe limit
					if (strlen($game) > $maxLength) {
						$game = substr($game, 0, $maxLength);
					}
					if (strlen($login) > $maxLength) {
						$login = substr($login, 0, $maxLength);
					}
					if ($mailAccountLogin !== null && strlen($mailAccountLogin) > $maxLength) {
						$mailAccountLogin = substr($mailAccountLogin, 0, $maxLength);
					}
					if ($comment !== null && strlen($comment) > $maxLength) {
						$comment = substr($comment, 0, $maxLength);
					}
					if ($recoverCode !== null && strlen($recoverCode) > $maxLength) {
						$recoverCode = substr($recoverCode, 0, $maxLength);
					}

					// Check if account exists (by game + login, as platform is now array)
					$existingAccount = Account::query()
						->where('game', $game)
						->where('login', $login)
						->first();

					$accountData = [
						'game' => $game,
						'platform' => $platforms, // Array of platforms
						'login' => $login,
						'password' => $password,
						'mail_account_login' => $mailAccountLogin,
						'mail_account_password' => $mailAccountPassword,
						'comment' => $comment,
						'two_fa_mail_account_date' => $twoFaDateParsed,
						'recover_code' => $recoverCode,
						'status' => AccountStatus::ACTIVE,
					];

					if ($existingAccount !== null) {
						// Update existing account
						$existingAccount->update($accountData);
						$updated++;
					} else {
						// Create new account
						Account::query()->create($accountData);
						$imported++;
					}
				} catch (\Exception $e) {
					$errors++;
					$errorsList[] = "Строка {$rowNumber}: " . $e->getMessage();
					Log::error('CSV import error', [
						'row' => $rowNumber,
						'error' => $e->getMessage(),
						'trace' => $e->getTraceAsString(),
					]);
				}
			}

			fclose($handle);

			// Build result message
			$messageParts = [];
			if ($imported > 0) {
				$messageParts[] = "добавлено: {$imported}";
			}
			if ($updated > 0) {
				$messageParts[] = "обновлено: {$updated}";
			}
			if ($errors > 0) {
				$messageParts[] = "ошибок: {$errors}";
			}
			if (!empty($unrecognizedColumns)) {
				$messageParts[] = "нераспознанные колонки: " . implode(', ', $unrecognizedColumns);
			}

			$message = "Импорт завершён. " . implode(', ', $messageParts) . ".";

			if ($errors > 0 && count($errorsList) <= 10) {
				$message .= "\n\nОшибки:\n" . implode("\n", array_slice($errorsList, 0, 10));
			}

			return redirect()->back()->with('status', $message);
		} catch (\Exception $e) {
			if (isset($handle) && is_resource($handle)) {
				fclose($handle);
			}
			Log::error('CSV import fatal error', [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			]);
			return redirect()->back()->with('status', 'Ошибка импорта: ' . $e->getMessage());
		}
	}
}
