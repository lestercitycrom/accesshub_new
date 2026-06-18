<?php

declare(strict_types=1);

namespace Database\Factories\Delivery\Models;

use App\Delivery\Enums\DeliveryOrderStatus;
use App\Delivery\Models\DeliveryOrder;
use App\Delivery\Models\DeliveryOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryOrderItem>
 */
final class DeliveryOrderItemFactory extends Factory
{
	protected $model = DeliveryOrderItem::class;

	public function definition(): array
	{
		return [
			'delivery_order_id' => DeliveryOrder::factory(),
			'position' => 0,
			'platform' => $this->faker->randomElement(['Xbox', 'PlayStation', 'Nintendo', 'Steam', 'Epic Games']),
			'issue_platform' => null,
			'game' => null,
			'status' => DeliveryOrderStatus::WAITING_FOR_OPERATOR,
			'connection_attempts_limit' => 3,
		];
	}
}
