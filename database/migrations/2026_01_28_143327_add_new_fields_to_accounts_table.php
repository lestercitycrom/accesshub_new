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
		});

		// Create new unique index on game + login with key length for TEXT columns
		// MySQL requires key length for TEXT/BLOB columns in indexes
		// SQLite doesn't support key length specification, so we check the driver
		$driver = DB::connection()->getDriverName();
		if ($driver === 'mysql') {
			DB::statement('ALTER TABLE `accounts` ADD UNIQUE `accounts_game_login_unique` (`game`(255), `login`(255))');
			DB::statement('ALTER TABLE `accounts` ADD INDEX `accounts_status_game_index` (`status`, `game`(255))');
		} else {
			// For SQLite and other databases, use standard Laravel methods
			// Note: SQLite doesn't support unique indexes on TEXT columns with length specification
			Schema::table('accounts', function (Blueprint $table): void {
				$table->unique(['game', 'login'], 'accounts_game_login_unique');
				$table->index(['status', 'game'], 'accounts_status_game_index');
			});
		}

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
		// Drop indexes by name
		$driver = DB::connection()->getDriverName();
		if ($driver === 'mysql') {
			DB::statement('ALTER TABLE `accounts` DROP INDEX `accounts_game_login_unique`');
			DB::statement('ALTER TABLE `accounts` DROP INDEX `accounts_status_game_index`');
		} else {
			Schema::table('accounts', function (Blueprint $table): void {
				$table->dropUnique('accounts_game_login_unique');
				$table->dropIndex('accounts_status_game_index');
			});
		}

		Schema::table('accounts', function (Blueprint $table): void {
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
