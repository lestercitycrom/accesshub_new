<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
	public function up(): void
	{
		// The role set changed from admin/operator/viewer to admin/manager/operator.
		// Any legacy 'viewer' (read-only) becomes 'operator' — the lowest role now.
		// Production has no viewers; this is a defensive no-op there.
		DB::table('users')->where('role', 'viewer')->update(['role' => 'operator']);
	}

	public function down(): void
	{
		// Irreversible on purpose — we cannot tell which operators were viewers.
	}
};
