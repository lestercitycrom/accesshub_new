<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Accounts\Models;

use App\Domain\Accounts\Models\Account;
use App\Domain\Accounts\Models\AccountEvent;
use App\Domain\Telegram\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountEvent>
 */
final class AccountEventFactory extends Factory
{
	protected $model = AccountEvent::class;

	public function definition(): array
	{
		return [
			'account_id' => Account::factory(),
			'telegram_id' => TelegramUser::factory(),
			'type' => $this->faker->randomElement(['ISSUED', 'MARK_PROBLEM', 'PASSWORD_UPDATED']),
			'payload' => [
				'note' => $this->faker->sentence(),
			],
		];
	}
}