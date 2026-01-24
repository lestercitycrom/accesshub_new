<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Accounts\Models;

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
final class AccountFactory extends Factory
{
	protected $model = Account::class;

	public function definition(): array
	{
		return [
			'game' => $this->faker->randomElement(['cs2', 'dota2', 'pubg']),
			'platform' => $this->faker->randomElement(['steam', 'epic']),
			'login' => $this->faker->unique()->userName(),
			'password' => 'secret123', // Will be encrypted by cast
			'status' => AccountStatus::ACTIVE,
			'max_uses' => 3,
			'available_uses' => 3,
			'next_release_at' => null,
			'assigned_to_telegram_id' => null,
			'status_deadline_at' => null,
			'flags' => null,
			'meta' => null,
		];
	}

	public function withCooldown(): self
	{
		return $this->state(fn (): array => [
			// Note: cooldown_until is on issuances, not accounts
		]);
	}

	public function status(AccountStatus $status): self
	{
		return $this->state(fn (): array => [
			'status' => $status,
		]);
	}
}