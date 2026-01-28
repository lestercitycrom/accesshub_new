<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
	public function up(): void
	{
		Schema::table('accounts', function (Blueprint $table): void {
			// Drop old unique index and indexes that use platform
			$table->dropUnique(['game', 'platform', 'login']);
			$table->dropIndex(['status', 'game', 'platform']);

			// Change existing columns to TEXT
			$table->text('game')->change();
			$table->text('login')->change();

			// Change platform to JSON for storing array of platforms
			$table->json('platform')->change();

			// Add new fields
			$table->text('mail_account_login')->nullable()->after('password');
			$table->text('mail_account_password')->nullable()->after('mail_account_login');
			$table->text('comment')->nullable()->after('mail_account_password');
			$table->date('two_fa_mail_account_date')->nullable()->after('comment');
			$table->text('recover_code')->nullable()->after('two_fa_mail_account_date');

			// Create new unique index on game + login (platform is now array)
			$table->unique(['game', 'login']);

			// Recreate index on status + game (platform removed as it's now JSON)
			$table->index(['status', 'game']);
		});

		// Migrate existing platform data to JSON array format
		DB::table('accounts')->chunkById(100, function ($accounts): void {
			foreach ($accounts as $account) {
				$platform = $account->platform;
				// If platform is already JSON, skip
				if (json_decode($platform, true) !== null) {
					continue;
				}
				// Convert string platform to JSON array
				DB::table('accounts')
					->where('id', $account->id)
					->update(['platform' => json_encode([$platform])]);
			}
		});
	}

	public function down(): void
	{
		Schema::table('accounts', function (Blueprint $table): void {
			// Drop new unique index
			$table->dropUnique(['game', 'login']);
			$table->dropIndex(['status', 'game']);

			// Remove new fields
			$table->dropColumn([
				'mail_account_login',
				'mail_account_password',
				'comment',
				'two_fa_mail_account_date',
				'recover_code',
			]);

			// Revert platform to string
			$table->string('platform', 50)->change();

			// Revert game and login to original types
			$table->string('game', 50)->change();
			$table->string('login', 191)->change();

			// Restore old indexes
			$table->unique(['game', 'platform', 'login']);
			$table->index(['status', 'game', 'platform']);
		});

		// Migrate JSON platform data back to string (take first platform from array)
		DB::table('accounts')->chunkById(100, function ($accounts): void {
			foreach ($accounts as $account) {
				$platform = $account->platform;
				$decoded = json_decode($platform, true);
				if (is_array($decoded) && count($decoded) > 0) {
					DB::table('accounts')
						->where('id', $account->id)
						->update(['platform' => $decoded[0]]);
				}
			}
		});
	}
};
