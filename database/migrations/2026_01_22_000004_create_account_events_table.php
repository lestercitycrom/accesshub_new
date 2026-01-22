<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
	public function up(): void
	{
		Schema::create('account_events', function (Blueprint $table): void {
			$table->id();

			$table->foreignId('account_id')
				->constrained('accounts')
				->cascadeOnUpdate()
				->cascadeOnDelete();

			$table->unsignedBigInteger('telegram_id')->nullable();
			$table->foreign('telegram_id')
				->references('telegram_id')
				->on('telegram_users')
				->cascadeOnUpdate()
				->nullOnDelete();

			$table->string('type', 50);
			$table->json('payload')->nullable();

			$table->timestamps();

			$table->index(['account_id', 'created_at']);
			$table->index(['type', 'created_at']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('account_events');
	}
};