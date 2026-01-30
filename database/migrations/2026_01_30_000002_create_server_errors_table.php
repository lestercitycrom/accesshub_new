<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
	public function up(): void
	{
		Schema::create('server_errors', function (Blueprint $table): void {
			$table->id();
			$table->unsignedBigInteger('telegram_id')->nullable();
			$table->string('context', 32); // webhook, webapp
			$table->string('path', 255)->nullable();
			$table->json('request_summary')->nullable();
			$table->text('exception_message');
			$table->text('exception_class')->nullable();
			$table->longText('exception_trace')->nullable();
			$table->timestamps();

			$table->index(['context', 'created_at']);
			$table->index('telegram_id');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('server_errors');
	}
};
