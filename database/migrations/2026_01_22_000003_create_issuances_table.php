<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
	public function up(): void
	{
		Schema::create('issuances', function (Blueprint $table): void {
			$table->id();

			$table->unsignedBigInteger('telegram_id');
			$table->foreign('telegram_id')
				->references('telegram_id')
				->on('telegram_users')
				->cascadeOnUpdate()
				->restrictOnDelete();

			$table->foreignId('account_id')
				->constrained('accounts')
				->cascadeOnUpdate()
				->restrictOnDelete();

			$table->string('order_id', 100);
			$table->string('game', 50);
			$table->string('platform', 50);

			$table->unsignedInteger('qty')->default(1);

			$table->timestamp('issued_at');
			$table->timestamp('cooldown_until')->nullable();

			$table->timestamps();

			$table->index(['telegram_id', 'issued_at']);
			$table->index(['order_id']);
			$table->index(['game', 'platform', 'issued_at']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('issuances');
	}
};