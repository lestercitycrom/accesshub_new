<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
	public function up(): void
	{
		Schema::create('delivery_orders', function (Blueprint $table): void {
			$table->id();

			$table->string('token', 96)->unique();
			$table->string('order_number', 100);
			$table->string('customer_email', 255);
			$table->string('platform', 50);
			$table->string('issue_platform', 50)->nullable();
			$table->string('game', 100)->nullable();
			$table->string('status', 50)->default('waiting_for_operator');

			$table->timestamp('token_expires_at');
			$table->timestamp('connected_at')->nullable();
			$table->timestamp('cancelled_at')->nullable();

			$table->foreignId('account_id')
				->nullable()
				->constrained('accounts')
				->cascadeOnUpdate()
				->nullOnDelete();

			$table->foreignId('issuance_id')
				->nullable()
				->constrained('issuances')
				->cascadeOnUpdate()
				->nullOnDelete();

			$table->unsignedBigInteger('operator_telegram_id')->nullable();
			$table->foreign('operator_telegram_id')
				->references('telegram_id')
				->on('telegram_users')
				->cascadeOnUpdate()
				->nullOnDelete();

			$table->string('display_login')->nullable();
			$table->text('display_password')->nullable();
			$table->string('display_password_type', 20)->nullable();

			$table->unsignedInteger('connection_attempts_used')->default(0);
			$table->unsignedInteger('connection_attempts_limit')->default(3);
			$table->timestamp('connection_locked_until')->nullable();
			$table->string('last_connection_code', 16)->nullable();
			$table->timestamp('last_connection_code_submitted_at')->nullable();

			$table->json('meta')->nullable();
			$table->timestamps();

			$table->index(['status', 'created_at']);
			$table->index(['order_number']);
			$table->index(['customer_email']);
			$table->index(['platform', 'status']);
			$table->index(['operator_telegram_id', 'status']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('delivery_orders');
	}
};

