<?php

declare(strict_types=1);

namespace App\Domain\Issuance\DTO;

final class IssuanceResult
{
	// Machine-readable failure reasons (optional). Used e.g. by the delivery
	// platform-fallback so it does not depend on matching error text. Absent
	// reason (null) means "unspecified" — callers should treat as non-retryable.
	public const REASON_NO_ACCOUNTS = 'no_accounts';        // нет аккаунтов под игру/платформу
	public const REASON_NO_AVAILABLE = 'no_available';      // есть, но все на cooldown/заняты
	public const REASON_INSUFFICIENT = 'insufficient';      // меньше, чем запрошено qty
	public const REASON_STOLEN = 'stolen';                  // аккаунты в статусе «Украден»
	public const REASON_ALREADY_ISSUED = 'already_issued';  // по этому заказу уже всё выдано

	/**
	 * @param array<int, array{account_id:int, login:string, password:string}> $items
	 */
	private function __construct(
		private readonly bool $ok,
		private readonly ?string $message,
		public readonly array $items = [],
		public readonly ?string $orderId = null,
		public readonly ?string $game = null,
		public readonly ?string $platform = null,
		private readonly ?string $reason = null,
	) {
	}

	/**
	 * Success result.
	 *
	 * @param array<int, array{account_id:int, login:string, password:string}> $items
	 */
	public static function success(array $items, ?string $orderId = null, ?string $game = null, ?string $platform = null): self
	{
		return new self(true, null, $items, $orderId, $game, $platform);
	}

	/**
	 * Fail result. Optional machine-readable $reason (see REASON_* constants).
	 */
	public static function fail(string $message, ?string $reason = null): self
	{
		return new self(false, $message, [], null, null, null, $reason);
	}

	/**
	 * Backward-compatible alias for older code that called ::error().
	 */
	public static function error(string $message): self
	{
		return self::fail($message);
	}

	public function ok(): bool
	{
		return $this->ok;
	}

	public function message(): ?string
	{
		return $this->message;
	}

	public function reason(): ?string
	{
		return $this->reason;
	}
}
