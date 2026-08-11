<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
	public function up(): void
	{
		Schema::table('issuances', static function (Blueprint $table): void {
			$table->string('game', 255)->change();
		});
	}

	public function down(): void
	{
		Schema::table('issuances', static function (Blueprint $table): void {
			// In strict MySQL mode this rollback safely refuses to truncate rows
			// that already contain a game name longer than the former limit.
			$table->string('game', 50)->change();
		});
	}
};
