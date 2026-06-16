<?php

declare(strict_types=1);

namespace App\Delivery\Services;

use Illuminate\Support\Str;

final class FakePasswordFactory
{
	public function make(): string
	{
		$prefix = trim((string) config('delivery.fake_password_prefix', 'QR'));
		$prefix = $prefix !== '' ? strtoupper($prefix) : 'QR';

		return sprintf('%s-%s-%s', $prefix, strtoupper(Str::random(4)), random_int(100, 999));
	}
}

