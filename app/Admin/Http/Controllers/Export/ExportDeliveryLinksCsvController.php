<?php

declare(strict_types=1);

namespace App\Admin\Http\Controllers\Export;

use App\Delivery\Models\DeliveryLink;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports delivery links as a plain newline-separated list of full URLs — the
 * format marketplaces expect for bulk "key" upload. One URL per line, no header.
 */
final class ExportDeliveryLinksCsvController
{
	public function __invoke(Request $request): StreamedResponse|Response
	{
		Gate::authorize('hub-manage');

		$batch = trim((string) $request->query('batch', ''));
		$only = (string) $request->query('only', 'unused'); // unused|all

		$query = DeliveryLink::query()->orderBy('id');

		if ($batch !== '') {
			$query->where('batch', $batch);
		}

		if ($only !== 'all') {
			$query->whereNull('used_at');
		}

		$suffix = $batch !== '' ? $batch : 'all';
		$filename = 'delivery-links_' . $suffix . '_' . now()->format('Ymd_His') . '.csv';

		$line = static fn (string $code): string => route('delivery.take-order.coded', ['code' => $code]) . "\n";

		if (app()->environment('testing')) {
			$body = '';
			foreach ($query->cursor() as $link) {
				$body .= $line((string) $link->code);
			}

			return response($body, 200, [
				'Content-Type' => 'text/csv; charset=UTF-8',
				'Content-Disposition' => 'attachment; filename="' . $filename . '"',
			]);
		}

		return response()->streamDownload(function () use ($query, $line): void {
			$handle = fopen('php://output', 'wb');
			if ($handle === false) {
				return;
			}

			$query->chunk(2000, function ($rows) use ($handle, $line): void {
				foreach ($rows as $row) {
					fwrite($handle, $line((string) $row->code));
				}
			});

			fclose($handle);
		}, $filename, [
			'Content-Type' => 'text/csv; charset=UTF-8',
		]);
	}
}
