<?php

declare(strict_types=1);

use App\Domain\Issuance\DTO\IssuanceResult;
use App\Domain\Issuance\Services\IssueService;
use App\Domain\Telegram\Models\TelegramUser;

// A5: delivery platform-fallback relies on these machine-readable reason codes
// instead of matching Russian error text. Lock the contract for the common case.
it('returns the no_accounts reason when nothing matches game and platform', function (): void {
	$operator = TelegramUser::factory()->create(['telegram_id' => 7701, 'is_active' => true]);

	$result = app(IssueService::class)->issue(
		telegramId: $operator->telegram_id,
		orderId: 'ORD-REASON-1',
		game: 'NonexistentGame',
		platform: 'Xbox',
		qty: 1,
	);

	expect($result->ok())->toBeFalse()
		->and($result->reason())->toBe(IssuanceResult::REASON_NO_ACCOUNTS);
});

it('keeps reason null for validation failures (non-retryable)', function (): void {
	$operator = TelegramUser::factory()->create(['telegram_id' => 7702, 'is_active' => true]);

	$result = app(IssueService::class)->issue(
		telegramId: $operator->telegram_id,
		orderId: '',
		game: '',
		platform: '',
		qty: 1,
	);

	expect($result->ok())->toBeFalse()
		->and($result->reason())->toBeNull();
});
