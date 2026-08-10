<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
	public function up(): void
	{
		Schema::table('accounts', static function (Blueprint $table): void {
			$table->string('source_label', 50)->nullable()->index();
		});
	}

	public function down(): void
	{
		Schema::table('accounts', static function (Blueprint $table): void {
			$table->dropIndex(['source_label']);
			$table->dropColumn('source_label');
		});
	}
};
