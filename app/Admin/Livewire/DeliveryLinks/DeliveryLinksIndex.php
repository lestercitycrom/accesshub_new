<?php

declare(strict_types=1);

namespace App\Admin\Livewire\DeliveryLinks;

use App\Delivery\Models\DeliveryLink;
use App\Domain\Accounts\Models\Account;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Bulk-generate unique single-use delivery links ("stock keys") and export them
 * as a CSV for upload to marketplace offers (difmark live-stock).
 */
final class DeliveryLinksIndex extends Component
{
	/** Per-generation cap — large enough for a full offer batch, small enough to stay snappy. */
	public const MAX_PER_BATCH = 50000;

	public int $count = 50;
	public string $game = '';
	public string $note = '';

	public function mount(): void
	{
		Gate::authorize('hub-delivery-links');
	}

	public function generate(): void
	{
		Gate::authorize('hub-delivery-links');

		$data = $this->validate([
			'count' => ['required', 'integer', 'min:1', 'max:' . self::MAX_PER_BATCH],
			'game' => ['required', 'string', 'min:2', 'max:255'],
			'note' => ['nullable', 'string', 'max:255'],
		]);

		$count = (int) $data['count'];
		$game = trim((string) $data['game']);
		$note = trim((string) ($data['note'] ?? '')) ?: null;
		$batch = now()->format('Ymd-His') . '-' . Str::lower(Str::random(4));
		$now = now();

		// Chunked insert keeps memory flat for large batches. Codes are random
		// 16-char tokens; the unique index is the backstop against collisions.
		$remaining = $count;
		while ($remaining > 0) {
			$size = min(1000, $remaining);
			$rows = [];
			for ($i = 0; $i < $size; $i++) {
				$rows[] = [
					'code' => DeliveryLink::generateCode(),
					'batch' => $batch,
					'game' => $game,
					'note' => $note,
					'created_at' => $now,
					'updated_at' => $now,
				];
			}
			DB::table('delivery_links')->insert($rows);
			$remaining -= $size;
		}

		session()->flash('message', "Generated {$count} links (batch {$batch}).");
	}

	public function deleteUnused(string $batch): void
	{
		Gate::authorize('hub-supply');

		// Only ever remove links nobody has redeemed — keep the audit trail intact.
		DeliveryLink::query()
			->where('batch', $batch)
			->whereNull('used_at')
			->delete();

		session()->flash('message', "Removed unused links from batch {$batch}.");
	}

	/**
	 * @return Collection<int, object{batch: ?string, game: ?string, total: int, used: int, unused: int, created_at: ?string}>
	 */
	public function getBatchesProperty(): Collection
	{
		return DeliveryLink::query()
			->selectRaw('batch, MAX(game) as game, COUNT(*) as total, SUM(used_at IS NOT NULL) as used, MAX(created_at) as created_at')
			->groupBy('batch')
			->orderByRaw('MAX(created_at) DESC')
			->limit(100)
			->get()
			->map(static function (object $row): object {
				$total = (int) $row->total;
				$used = (int) $row->used;

				return (object) [
					'batch' => $row->batch,
					'game' => $row->game,
					'total' => $total,
					'used' => $used,
					'unused' => $total - $used,
					'created_at' => $row->created_at,
				];
			});
	}

	public function getGameOptionsProperty(): array
	{
		return Account::query()
			->distinct()
			->orderBy('game')
			->pluck('game')
			->filter()
			->values()
			->all();
	}

	public function getTotalsProperty(): object
	{
		$total = (int) DeliveryLink::query()->count();
		$used = (int) DeliveryLink::query()->used()->count();

		return (object) [
			'total' => $total,
			'used' => $used,
			'unused' => $total - $used,
		];
	}

	public function render()
	{
		return view('admin.delivery-links.index', [
			'batches' => $this->batches,
			'gameOptions' => $this->gameOptions,
			'totals' => $this->totals,
		])->layout('layouts.admin');
	}
}
