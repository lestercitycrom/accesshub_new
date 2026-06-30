<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
	public function up(): void
	{
		Schema::table('delivery_links', function (Blueprint $table): void {
			$table->string('game', 255)->nullable()->after('batch');
			$table->index(['game'], 'delivery_links_game_index');
		});
	}

	public function down(): void
	{
		Schema::table('delivery_links', function (Blueprint $table): void {
			$table->dropIndex('delivery_links_game_index');
			$table->dropColumn('game');
		});
	}
};
