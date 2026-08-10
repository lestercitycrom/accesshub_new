<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		$this->replaceGameLoginUniqueWithReviewIndexes();

		if (DB::connection()->getDriverName() === 'mysql') {
			return;
		}

		Schema::table('accounts', static function (Blueprint $table): void {
			$table->unsignedSmallInteger('cooldown_days')
				->nullable()
				->after('available_uses');
		});
	}

	public function down(): void
	{
		if (DB::connection()->getDriverName() === 'mysql') {
			$this->restoreGameLoginUnique();

			return;
		}

		Schema::table('accounts', static function (Blueprint $table): void {
			$table->dropColumn('cooldown_days');
		});

		$this->restoreGameLoginUnique();
	}

	private function replaceGameLoginUniqueWithReviewIndexes(): void
	{
		// The old unique game+login key rejected legitimate rows for the same
		// login on different platforms. Keep it as a lookup index and add an
		// indexed normalized identity for the manual duplicate-review filter.
		if (DB::connection()->getDriverName() === 'mysql') {
			DB::statement(<<<'SQL'
ALTER TABLE `accounts`
    DROP INDEX `accounts_game_login_unique`,
    ADD INDEX `accounts_game_login_index` (`game`(255), `login`(255)),
    ADD COLUMN `duplicate_identity` VARCHAR(255)
        GENERATED ALWAYS AS (LOWER(COALESCE(NULLIF(TRIM(`mail_account_login`), ''), TRIM(`login`)))) STORED,
    ADD INDEX `accounts_duplicate_identity_index` (`duplicate_identity`),
    ADD COLUMN `cooldown_days` SMALLINT UNSIGNED NULL AFTER `available_uses`
SQL);

			return;
		}

		Schema::table('accounts', static function (Blueprint $table): void {
			$table->dropUnique('accounts_game_login_unique');
			$table->index(['game', 'login'], 'accounts_game_login_index');
		});
	}

	private function restoreGameLoginUnique(): void
	{
		if (DB::connection()->getDriverName() === 'mysql') {
			DB::statement(<<<'SQL'
ALTER TABLE `accounts`
    DROP COLUMN `cooldown_days`,
    DROP INDEX `accounts_duplicate_identity_index`,
    DROP COLUMN `duplicate_identity`,
    DROP INDEX `accounts_game_login_index`,
    ADD UNIQUE `accounts_game_login_unique` (`game`(255), `login`(255))
SQL);

			return;
		}

		Schema::table('accounts', static function (Blueprint $table): void {
			$table->dropIndex('accounts_game_login_index');
			$table->unique(['game', 'login'], 'accounts_game_login_unique');
		});
	}
};
