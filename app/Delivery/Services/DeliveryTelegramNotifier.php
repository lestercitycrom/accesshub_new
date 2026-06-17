<?php

declare(strict_types=1);

namespace App\Delivery\Services;

use App\Delivery\Models\DeliveryOrder;
use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use App\Telegram\Services\TelegramClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final class DeliveryTelegramNotifier
{
	public function __construct(
		private readonly TelegramClient $telegramClient,
	) {}

	public function notifyNewOrder(DeliveryOrder $order): void
	{
		$order->refresh();

		$message = implode("\n", array_filter([
			'<b>New delivery order</b>',
			'Order: <code>' . $this->escape($order->order_number) . '</code>',
			'Platform: <b>' . $this->escape($order->platform) . '</b>',
			'Customer email: <code>' . $this->escape($order->customer_email) . '</code>',
			'Created: ' . $this->escape($order->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? ''),
			'',
			'Open Mini App to verify payment, choose game, and issue account.',
		]));

		$this->sendToOperators($message, [
			'inline_keyboard' => [
				[
					[
						'text' => 'Open order',
						'web_app' => [
							'url' => $this->webAppOrderUrl($order),
						],
					],
				],
			],
		]);
	}

	public function notifyConnectionCodeSubmitted(DeliveryOrder $order): void
	{
		$order->refresh();

		$message = implode("\n", array_filter([
			'<b>Connection code submitted</b>',
			'Order: <code>' . $this->escape($order->order_number) . '</code>',
			'Platform: <b>' . $this->escape($order->platform) . '</b>',
			$order->game ? 'Game: <b>' . $this->escape($order->game) . '</b>' : null,
			'Code: <code>' . $this->escape($order->last_connection_code) . '</code>',
			sprintf(
				'Attempt: %d / %d',
				(int) $order->connection_attempts_used,
				(int) $order->connection_attempts_limit,
			),
			'',
			'Open the platform connection page and process this code.',
		]));

		$this->sendToOperators($message, [
			'inline_keyboard' => [
				[
					[
						'text' => 'Connecting',
						'callback_data' => 'delivery:connecting:' . $order->id,
					],
					[
						'text' => 'Connected',
						'callback_data' => 'delivery:connected:' . $order->id,
					],
				],
				[
					[
						'text' => 'Failed',
						'callback_data' => 'delivery:failed:' . $order->id,
					],
					[
						'text' => '+1 attempt',
						'callback_data' => 'delivery:extra:' . $order->id . ':1',
					],
				],
				[
					[
						'text' => 'Open order',
						'web_app' => [
							'url' => $this->webAppOrderUrl($order),
						],
					],
				],
			],
		]);
	}

	/**
	 * @param array<string, mixed>|null $replyMarkup
	 */
	private function sendToOperators(string $message, ?array $replyMarkup = null): void
	{
		foreach ($this->recipients() as $recipient) {
			try {
				$this->telegramClient->sendMessage(
					(string) $recipient->telegram_id,
					$message,
					disableLinkPreview: true,
					replyMarkup: $replyMarkup,
				);
			} catch (\Throwable $e) {
				Log::warning('Delivery Telegram notification failed.', [
					'telegram_id' => $recipient->telegram_id,
					'message' => $e->getMessage(),
				]);
			}
		}
	}

	/**
	 * @return Collection<int, TelegramUser>
	 */
	private function recipients(): Collection
	{
		return TelegramUser::query()
			->where('is_active', true)
			->whereIn('role', [
				TelegramRole::OPERATOR->value,
				TelegramRole::DELIVERY_OPERATOR->value,
				TelegramRole::ADMIN->value,
			])
			->orderBy('id')
			->get();
	}

	private function escape(mixed $value): string
	{
		return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	private function webAppOrderUrl(DeliveryOrder $order): string
	{
		return route('webapp', [
			'tab' => 'delivery',
			'delivery_order' => $order->id,
		], true);
	}
}
