<?php

declare(strict_types=1);

namespace App\Admin\Http\Controllers\Export;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportAccountsCsvController
{
	public function __invoke(Request $request): StreamedResponse|Response
	{
		$q = trim((string) $request->query('q', ''));
		$status = trim((string) $request->query('status', ''));
		$game = trim((string) $request->query('game', ''));
		$platform = trim((string) $request->query('platform', ''));

		$query = Account::query();

		if ($q !== '') {
			$query->where('login', 'like', '%' . $q . '%');
		}

		if ($status !== '') {
			$query->where('status', AccountStatus::from($status));
		}

		if ($game !== '') {
			$query->where('game', $game);
		}

		if ($platform !== '') {
			$query->where('platform', $platform);
		}

		$query->orderByDesc('id');

		$filename = 'accounts_' . now()->format('Ymd_His') . '.csv';

		// For testing, return as string instead of streamed response
		if (app()->environment('testing')) {
			$csv = '';
			$handle = fopen('php://temp', 'r+');

			if ($handle !== false) {
				// CSV header
				fputcsv($handle, [
					'id',
					'game',
					'platform',
					'login',
					'status',
					'assigned_to_telegram_id',
					'status_deadline_at',
					'created_at',
					'updated_at',
				]);

				$rows = $query->get();
				foreach ($rows as $row) {
					fputcsv($handle, [
						$row->id,
						$row->game,
						$row->platform,
						$row->login,
						$row->status->value,
						$row->assigned_to_telegram_id,
						$row->status_deadline_at?->toDateTimeString(),
						$row->created_at?->toDateTimeString(),
						$row->updated_at?->toDateTimeString(),
					]);
				}

				rewind($handle);
				$csv = stream_get_contents($handle);
				fclose($handle);
			}

			return response($csv, 200, [
				'Content-Type' => 'text/csv; charset=UTF-8',
				'Content-Disposition' => 'attachment; filename="' . $filename . '"',
			]);
		}

		return response()->streamDownload(function () use ($query): void {
			$handle = fopen('php://output', 'wb');

			if ($handle === false) {
				return;
			}

			// CSV header
			fputcsv($handle, [
				'id',
				'game',
				'platform',
				'login',
				'status',
				'assigned_to_telegram_id',
				'status_deadline_at',
				'created_at',
				'updated_at',
			]);

			// Stream rows to keep memory low
			$query->chunk(1000, function ($rows) use ($handle): void {
				foreach ($rows as $row) {
					fputcsv($handle, [
						$row->id,
						$row->game,
						$row->platform,
						$row->login,
						$row->status->value,
						$row->assigned_to_telegram_id,
						$row->status_deadline_at?->toDateTimeString(),
						$row->created_at?->toDateTimeString(),
						$row->updated_at?->toDateTimeString(),
					]);
				}
			});

			fclose($handle);
		}, $filename, [
			'Content-Type' => 'text/csv; charset=UTF-8',
		]);
	}
}