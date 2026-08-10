<?php

declare(strict_types=1);

use App\Domain\Accounts\Services\PlatformCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
	public function up(): void
	{
		DB::table('accounts')
			->select(['id', 'platform'])
			->orderBy('id')
			->chunkById(500, static function ($accounts): void {
				foreach ($accounts as $account) {
					$current = json_decode((string) $account->platform, true);
					$current = is_array($current) ? $current : [(string) $account->platform];
					$normalized = PlatformCatalog::normalizeList($current);

					if ($normalized === null || $normalized === [] || $normalized === $current) {
						continue;
					}

					DB::table('accounts')->where('id', $account->id)->update([
						'platform' => json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
					]);
				}
			}, 'id');
	}

	public function down(): void
	{
		// Canonical names intentionally remain canonical. The pre-deploy database
		// backup is the lossless rollback for this one-way data cleanup.
	}
};
