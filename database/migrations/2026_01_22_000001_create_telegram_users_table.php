<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
	public function up(): void
	{
		Schema::create('telegram_users', function (Blueprint $table): void {
			$table->id();

			$table->unsignedBigInteger('telegram_id')->unique();

			$table->string('username')->nullable();
			$table->string('first_name')->nullable();
			$table->string('last_name')->nullable();

			$table->string('role', 20)->default('operator');
			$table->boolean('is_active')->default(true);

			$table->timestamps();

			$table->index(['role', 'is_active']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('telegram_users');
	}
};