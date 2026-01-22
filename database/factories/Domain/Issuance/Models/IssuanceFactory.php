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
			'telegram_id' => 1, // Will be overridden by state
			'account_id' => 1, // Will be overridden by state
			'order_id' => 'ORD-' . $this->faker->unique()->numberBetween(1, 999999),
			'game' => $this->faker->randomElement(['cs2', 'dota2', 'pubg']),
			'platform' => $this->faker->randomElement(['steam', 'epic']),
			'qty' => 1,
			'issued_at' => $issuedAt,
			'cooldown_until' => null,
		];
	}

	public function configure(): static
	{
		return $this->afterMaking(function (Issuance $issuance): void {
			if (!$issuance->telegramUser) {
				$telegramUser = TelegramUser::factory()->create();
				$issuance->telegram_id = $telegramUser->telegram_id;
			}
			if (!$issuance->account) {
				$account = Account::factory()->create();
				$issuance->account_id = $account->id;
			}
		})->afterCreating(function (Issuance $issuance): void {
			if (!$issuance->telegramUser) {
				$telegramUser = TelegramUser::factory()->create();
				$issuance->telegram_id = $telegramUser->telegram_id;
				$issuance->save();
			}
			if (!$issuance->account) {
				$account = Account::factory()->create();
				$issuance->account_id = $account->id;
				$issuance->save();
			}
		});
	}
}