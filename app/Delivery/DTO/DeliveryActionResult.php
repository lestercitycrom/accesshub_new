<?php

declare(strict_types=1);

namespace App\Delivery\DTO;

final class DeliveryActionResult
{
	private function __construct(
		private readonly bool $ok,
		private readonly ?string $message = null,
	) {}

	public static function ok(?string $message = null): self
	{
		return new self(true, $message);
	}

	public static function fail(string $message): self
	{
		return new self(false, $message);
	}

	public function successful(): bool
	{
		return $this->ok;
	}

	public function failed(): bool
	{
		return !$this->ok;
	}

	public function message(): ?string
	{
		return $this->message;
	}
}

