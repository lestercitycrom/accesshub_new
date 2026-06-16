<?php

declare(strict_types=1);

namespace Database\Factories\Delivery\Models;

use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Models\DeliveryOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DeliveryOrder>
 */
final class DeliveryOrderFactory extends Factory
{
	protected $model = DeliveryOrder::class;

	public function definition(): array
	{
		return [
			'token' => Str::random(64),
			'order_number' => 'ORD-' . $this->faker->unique()->numberBetween(1000, 999999),
			'customer_email' => $this->faker->safeEmail(),
			'platform' => $this->faker->randomElement(['Xbox', 'PlayStation', 'Nintendo', 'Steam', 'Epic Games']),
			'status' => DeliveryOrderStatus::WAITING_FOR_OPERATOR,
			'token_expires_at' => now()->addHours(72),
			'connection_attempts_limit' => 3,
		];
	}
}

