<?php

declare(strict_types=1);

namespace App\Domain\Issuance\DTO;

final class IssuanceResult
{
	/**
	 * @param array<int, array{account_id:int, login:string, password:string}> $items
	 */
	private function __construct(
		private readonly bool $ok,
		private readonly ?string $message,
		public readonly array $items = [],
		public readonly ?string $orderId = null,
	) {
	}

	/**
	 * Success result.
	 *
	 * @param array<int, array{account_id:int, login:string, password:string}> $items
	 */
	public static function success(array $items, ?string $orderId = null): self
	{
		return new self(true, null, $items, $orderId);
	}

	/**
	 * Fail result.
	 */
	public static function fail(string $message): self
	{
		return new self(false, $message, []);
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
}
