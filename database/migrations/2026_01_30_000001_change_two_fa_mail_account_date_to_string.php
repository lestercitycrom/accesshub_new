<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
	public function up(): void
	{
		Schema::table('accounts', function (Blueprint $table): void {
			$table->string('two_fa_mail_account_date', 255)->nullable()->change();
		});
	}

	public function down(): void
	{
		Schema::table('accounts', function (Blueprint $table): void {
			$table->date('two_fa_mail_account_date')->nullable()->change();
		});
	}
};
