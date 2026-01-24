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
		Schema::table('accounts', static function (Blueprint $table): void {
			$table->unsignedSmallInteger('max_uses')->default(3)->after('password');
			$table->unsignedSmallInteger('available_uses')->default(3)->after('max_uses');
			$table->timestamp('next_release_at')->nullable()->after('available_uses');
		});

		// Backfill existing accounts to comply with TЗ v3
		DB::table('accounts')
			->whereNull('max_uses')
			->update(['max_uses' => 3]);

		DB::table('accounts')
			->whereNull('available_uses')
			->update(['available_uses' => DB::raw('max_uses')]);
	}

	public function down(): void
	{
		Schema::table('accounts', static function (Blueprint $table): void {
			$table->dropColumn(['max_uses', 'available_uses', 'next_release_at']);
		});
	}
};
