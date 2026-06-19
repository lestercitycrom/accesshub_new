<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P.4 — multi-game delivery orders (additive model).
 *
 * The first game stays on `delivery_orders` (unchanged flow). This table holds
 * ADDITIONAL games (2nd, 3rd…) for the same order — each with its own account,
 * credentials and connection lifecycle. The public payload/UI present the first
 * game + these items as one uniform list of tabs. No backfill needed.
 */
return new class () extends Migration
{
	public function up(): void
	{
		Schema::create('delivery_order_items', function (Blueprint $table): void {
			$table->id();

			$table->foreignId('delivery_order_id')
				->constrained('delivery_orders')
				->cascadeOnUpdate()
				->cascadeOnDelete();

			$table->unsignedInteger('position')->default(0);

			$table->string('platform', 50);
			$table->string('issue_platform', 50)->nullable();
			$table->string('game', 100)->nullable();
			$table->string('status', 50)->default('waiting_for_operator');

			$table->foreignId('account_id')->nullable()
				->constrained('accounts')->cascadeOnUpdate()->nullOnDelete();
			$table->foreignId('issuance_id')->nullable()
				->constrained('issuances')->cascadeOnUpdate()->nullOnDelete();

			$table->unsignedBigInteger('operator_telegram_id')->nullable();
			$table->foreign('operator_telegram_id')
				->references('telegram_id')->on('telegram_users')
				->cascadeOnUpdate()->nullOnDelete();

			$table->string('display_login')->nullable();
			$table->text('display_password')->nullable();
			$table->string('display_password_type', 20)->nullable();

			$table->unsignedInteger('connection_attempts_used')->default(0);
			$table->unsignedInteger('connection_attempts_limit')->default(3);
			$table->timestamp('connection_locked_until')->nullable();
			$table->string('last_connection_code', 16)->nullable();
			$table->timestamp('last_connection_code_submitted_at')->nullable();
			$table->timestamp('connected_at')->nullable();

			$table->timestamps();

			$table->index(['delivery_order_id', 'position']);
			$table->index(['status']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('delivery_order_items');
	}
};
