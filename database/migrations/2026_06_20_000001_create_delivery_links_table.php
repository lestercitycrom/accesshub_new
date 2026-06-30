<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unique delivery links ("stock keys").
 *
 * The marketplace (difmark) sells via live-stock and rejects duplicate keys.
 * Our "key" is a delivery URL, so the single shared /take-order link cannot be
 * uploaded many times. This table holds pre-generated UNIQUE codes that produce
 * links like /take-order/{code}. Each code is single-use: it is consumed when a
 * delivery order is created from it (used_at + delivery_order_id are set).
 *
 * Codes are bulk-generated in batches (admin) and exported as a CSV of full URLs
 * for upload to marketplace offers.
 */
return new class () extends Migration
{
	public function up(): void
	{
		Schema::create('delivery_links', function (Blueprint $table): void {
			$table->id();

			$table->string('code', 64)->unique();
			$table->string('batch', 64)->nullable();
			$table->string('note', 255)->nullable();

			$table->timestamp('used_at')->nullable();
			$table->foreignId('delivery_order_id')->nullable()
				->constrained('delivery_orders')->cascadeOnUpdate()->nullOnDelete();

			$table->timestamps();

			$table->index(['batch']);
			$table->index(['used_at']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('delivery_links');
	}
};
