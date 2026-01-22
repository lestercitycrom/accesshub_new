<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
	public function up(): void
	{
		Schema::create('accounts', function (Blueprint $table): void {
			$table->id();

			$table->string('game', 50);
			$table->string('platform', 50);

			$table->string('login', 191);
			$table->text('password');

			$table->string('status', 20)->default('ACTIVE');

			$table->unsignedBigInteger('assigned_to_telegram_id')->nullable();
			$table->timestamp('status_deadline_at')->nullable();

			$table->json('flags')->nullable();
			$table->json('meta')->nullable();

			$table->timestamps();

			$table->unique(['game', 'platform', 'login']);
			$table->index(['status', 'game', 'platform']);
			$table->index(['assigned_to_telegram_id', 'status']);
			$table->index(['status_deadline_at']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('accounts');
	}
};