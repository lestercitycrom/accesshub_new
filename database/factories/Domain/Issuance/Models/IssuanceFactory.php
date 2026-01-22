<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Issuance\Models;

use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Issuance>
 */
final class IssuanceFactory extends Factory
{
	protected $model = Issuance::class;

	public function definition(): array
	{
		$issuedAt = Carbon::now()->subMinutes($this->faker->numberBetween(1, 500));

		return [
			'telegram_id' => TelegramUser::factory(),
			'account_id' => Account::factory(),
			'order_id' => 'ORD-' . $this->faker->unique()->numberBetween(1, 999999),
			'game' => $this->faker->randomElement(['cs2', 'dota2', 'pubg']),
			'platform' => $this->faker->randomElement(['steam', 'epic']),
			'qty' => 1,
			'issued_at' => $issuedAt,
			'cooldown_until' => null,
		];
	}
}