<?php

declare(strict_types=1);

namespace App\Delivery\Services;

use Carbon\CarbonImmutable;

/**
 * Support working-hours window (mirrors the old download-games site).
 * Hour-granularity check in the configured timezone.
 */
final class WorkingHours
{
	public function enforced(): bool
	{
		return (bool) config('delivery.working_hours.enforce', false);
	}

	public function start(): int
	{
		return (int) config('delivery.working_hours.start', 9);
	}

	public function end(): int
	{
		return (int) config('delivery.working_hours.end', 23);
	}

	public function timezone(): string
	{
		return (string) config('delivery.working_hours.timezone', 'Europe/Kiev');
	}

	public function label(): string
	{
		return (string) config('delivery.working_hours.label', 'EET');
	}

	public function isOpen(?CarbonImmutable $now = null): bool
	{
		$now = ($now ?? CarbonImmutable::now())->setTimezone($this->timezone());
		$hour = (int) $now->format('G');

		// end == 24 means "open until midnight".
		if ($this->end() >= 24) {
			return $hour >= $this->start();
		}

		return $hour >= $this->start() && $hour < $this->end();
	}

	/**
	 * @return array{enforce: bool, start: int, end: int, timezone: string, label: string}
	 */
	public function toArray(): array
	{
		return [
			'enforce' => $this->enforced(),
			'start' => $this->start(),
			'end' => $this->end(),
			'timezone' => $this->timezone(),
			'label' => $this->label(),
		];
	}
}
