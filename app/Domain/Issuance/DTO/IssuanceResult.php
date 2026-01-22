<?php

declare(strict_types=1);

namespace App\Domain\Issuance\DTO;

final class IssuanceResult
{
	private function __construct(
		public readonly bool $success,
		public readonly ?int $accountId,
		public readonly ?string $login,
		public readonly ?string $password,
		public readonly ?string $error,
	) {}

	public static function success(int $accountId, string $login, string $password): self
	{
		return new self(true, $accountId, $login, $password, null);
	}

	public static function error(string $error): self
	{
		return new self(false, null, null, null, $error);
	}
}