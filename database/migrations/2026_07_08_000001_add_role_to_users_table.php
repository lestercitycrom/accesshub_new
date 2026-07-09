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
		Schema::table('users', function (Blueprint $table): void {
			// Nullable on purpose: null = no Hub access (preserves the old rule
			// that only is_admin users could enter the panel).
			$table->string('role')->nullable()->after('is_admin')->index();
		});

		// Backfill: existing admins keep full access. Non-admins had no panel
		// access before, so they stay role=null (no access) — never escalate.
		DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);
	}

	public function down(): void
	{
		Schema::table('users', function (Blueprint $table): void {
			$table->dropIndex(['role']);
			$table->dropColumn('role');
		});
	}
};
