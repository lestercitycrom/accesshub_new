<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Telegram\Models;

use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelegramUser>
 */
final class TelegramUserFactory extends Factory
{
	protected $model = TelegramUser::class;

	public function definition(): array
	{
		return [
			'telegram_id' => $this->faker->unique()->numberBetween(100000, 999999999),
			'username' => $this->faker->userName(),
			'first_name' => $this->faker->firstName(),
			'last_name' => $this->faker->lastName(),
			'role' => TelegramRole::OPERATOR,
			'is_active' => true,
		];
	}

	public function admin(): self
	{
		return $this->state(fn (): array => [
			'role' => TelegramRole::ADMIN,
		]);
	}

	public function inactive(): self
	{
		return $this->state(fn (): array => [
			'is_active' => false,
		]);
	}
}