<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
	public function up(): void
	{
		Schema::create('delivery_events', function (Blueprint $table): void {
			$table->id();

			$table->foreignId('delivery_order_id')
				->constrained('delivery_orders')
				->cascadeOnUpdate()
				->cascadeOnDelete();

			$table->string('type', 80);
			$table->string('actor_type', 40)->nullable();
			$table->string('actor_id', 100)->nullable();
			$table->json('payload')->nullable();
			$table->timestamp('created_at')->useCurrent();

			$table->index(['delivery_order_id', 'created_at']);
			$table->index(['type', 'created_at']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('delivery_events');
	}
};

