<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
	public function up(): void
	{
		Schema::create('delivery_platform_instructions', function (Blueprint $table): void {
			$table->id();

			$table->string('platform', 50)->unique();
			$table->string('title', 255);
			$table->text('body')->nullable();
			$table->boolean('is_active')->default(true);
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('delivery_platform_instructions');
	}
};

