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
		return (string) config('delivery.working_hours.timezone', 'Europe/Kyiv');
	}

	public function label(): string
	{
		return (string) config('delivery.working_hours.label', 'EET');
	}

	public function isOpen(?CarbonImmutable $now = null): bool
	{
		try {
			$now = ($now ?? CarbonImmutable::now())->setTimezone($this->timezone());
		} catch (\Throwable) {
			// A bad timezone config must never break the public order form:
			// fail open (treat as within hours = no night block).
			return true;
		}

		$hour = (int) $now->format('G');
		$start = $this->start();
		$end = $this->end();

		// Same-day window (start < end), incl. end == 24 for "open until midnight".
		if ($start < $end) {
			return $hour >= $start && $hour < $end;
		}

		// Overnight window that wraps past midnight (e.g. 09:00 → 03:00):
		// open from start until midnight, then from midnight until end.
		return $hour >= $start || $hour < $end;
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
