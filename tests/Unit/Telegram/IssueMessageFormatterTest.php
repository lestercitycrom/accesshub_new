<?php

declare(strict_types=1);

use App\Domain\Issuance\DTO\IssuanceResult;
use App\Telegram\Services\IssueMessageFormatter;

it('formats credentials in English and builds one copy button', function (): void {
	$result = IssuanceResult::success([
		[
			'account_id' => 10,
			'login' => 'operator@example.test',
			'password' => 'secret-123',
		],
	], 'ORDER-10', 'Test Game', 'Steam');

	$formatter = new IssueMessageFormatter();
	$message = $formatter->format($result);
	$keyboard = $formatter->copyCredentialsKeyboard($result);

	expect($message)
		->toContain('Login: <code>operator@example.test</code>')
		->toContain('Password: <code>secret-123</code>')
		->not->toContain('Логин:')
		->not->toContain('Пароль:')
		->and($keyboard)->toBe([
			'inline_keyboard' => [[[
				'text' => '📋 Copy credentials',
				'copy_text' => [
					'text' => "Login: operator@example.test\nPassword: secret-123",
				],
			]]],
		]);
});

it('builds a separate copy button for every account in an x2 issue', function (): void {
	$result = IssuanceResult::success([
		[
			'account_id' => 11,
			'login' => 'first@example.test',
			'password' => 'first-pass',
		],
		[
			'account_id' => 12,
			'login' => 'second@example.test',
			'password' => 'second-pass',
		],
	]);

	$keyboard = (new IssueMessageFormatter())->copyCredentialsKeyboard($result);

	expect($keyboard)->toBe([
		'inline_keyboard' => [
			[[
				'text' => '📋 Copy credentials #1',
				'copy_text' => [
					'text' => "Login: first@example.test\nPassword: first-pass",
				],
			]],
			[[
				'text' => '📋 Copy credentials #2',
				'copy_text' => [
					'text' => "Login: second@example.test\nPassword: second-pass",
				],
			]],
		],
	]);
});

it('does not truncate credentials that exceed the Telegram copy limit', function (): void {
	$result = IssuanceResult::success([
		[
			'account_id' => 13,
			'login' => str_repeat('a', 250),
			'password' => 'password',
		],
	]);

	expect((new IssueMessageFormatter())->copyCredentialsKeyboard($result))->toBeNull();
});
