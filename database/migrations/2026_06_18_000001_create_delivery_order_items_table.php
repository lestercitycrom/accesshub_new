<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P.4 — multi-game delivery orders.
 *
 * A delivery order becomes a "header"; each purchased game is a row here with
 * its own account, credentials and connection lifecycle. Existing assigned
 * orders are backfilled as a single item so nothing is lost.
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

		// Backfill: every order that already has an assigned account becomes one
		// item (position 0). Waiting/unassigned orders get items on first issue.
		$now = now();
		DB::table('delivery_orders')
			->whereNotNull('account_id')
			->orderBy('id')
			->each(function ($order) use ($now): void {
				DB::table('delivery_order_items')->insert([
					'delivery_order_id' => $order->id,
					'position' => 0,
					'platform' => $order->platform,
					'issue_platform' => $order->issue_platform,
					'game' => $order->game,
					'status' => $order->status,
					'account_id' => $order->account_id,
					'issuance_id' => $order->issuance_id,
					'operator_telegram_id' => $order->operator_telegram_id,
					'display_login' => $order->display_login,
					'display_password' => $order->display_password,
					'display_password_type' => $order->display_password_type,
					'connection_attempts_used' => $order->connection_attempts_used,
					'connection_attempts_limit' => $order->connection_attempts_limit,
					'connection_locked_until' => $order->connection_locked_until,
					'last_connection_code' => $order->last_connection_code,
					'last_connection_code_submitted_at' => $order->last_connection_code_submitted_at,
					'connected_at' => $order->connected_at,
					'created_at' => $order->created_at ?? $now,
					'updated_at' => $now,
				]);
			});
	}

	public function down(): void
	{
		Schema::dropIfExists('delivery_order_items');
	}
};
