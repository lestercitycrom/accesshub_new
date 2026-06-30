<?php

declare(strict_types=1);

namespace Database\Factories\Delivery\Models;

use App\Delivery\Models\DeliveryLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryLink>
 */
final class DeliveryLinkFactory extends Factory
{
	protected $model = DeliveryLink::class;

	public function definition(): array
	{
		return [
			'code' => DeliveryLink::generateCode(),
			'batch' => null,
			'game' => null,
			'note' => null,
			'used_at' => null,
			'delivery_order_id' => null,
		];
	}

	public function used(): self
	{
		return $this->state(fn (): array => ['used_at' => now()]);
	}
}
