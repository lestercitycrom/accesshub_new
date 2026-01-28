<?php

declare(strict_types=1);

use App\Telegram\Services\Parsers\TextIssueParser;
use App\Telegram\DTO\IncomingIssueRequest;

it('parses valid text format', function (): void {
	$parser = new TextIssueParser();

	$result = $parser->parse('123456789', '987654321', "ORD-12345\ncs2 steam x2");

	expect($result)->toBeInstanceOf(IncomingIssueRequest::class);
	expect($result->chatId)->toBe('123456789');
	expect($result->telegramId)->toBe(987654321);
	expect($result->orderId)->toBe('ORD-12345');
	expect($result->game)->toBe('cs2');
	expect($result->platform)->toBe('steam');
	expect($result->qty)->toBe(2);
});

it('parses text format without qty', function (): void {
	$parser = new TextIssueParser();

	$result = $parser->parse('123456789', '987654321', "ORD-12345\ncs2 steam");

	expect($result)->toBeInstanceOf(IncomingIssueRequest::class);
	expect($result->qty)->toBe(1);
});

it('parses game name with spaces', function (): void {
	$parser = new TextIssueParser();

	$result = $parser->parse('123456789', '987654321', "ORDER-12345\nAC Liberation PS3");

	expect($result)->toBeInstanceOf(IncomingIssueRequest::class);
	expect($result->game)->toBe('AC Liberation');
	expect($result->platform)->toBe('PS3');
	expect($result->qty)->toBe(1);
});

it('parses game name with spaces and qty', function (): void {
	$parser = new TextIssueParser();

	$result = $parser->parse('123456789', '987654321', "ORDER-001\n7 Days To Die PS4 x2");

	expect($result)->toBeInstanceOf(IncomingIssueRequest::class);
	expect($result->game)->toBe('7 Days To Die');
	expect($result->platform)->toBe('PS4');
	expect($result->qty)->toBe(2);
});

it('parses complex game name with colons', function (): void {
	$parser = new TextIssueParser();

	$result = $parser->parse('123456789', '987654321', "ORDER-002\nA Plague Tale: Requiem PS5");

	expect($result)->toBeInstanceOf(IncomingIssueRequest::class);
	expect($result->game)->toBe('A Plague Tale: Requiem');
	expect($result->platform)->toBe('PS5');
	expect($result->qty)->toBe(1);
});

it('returns null for invalid format', function (): void {
	$parser = new TextIssueParser();

	$result = $parser->parse('123456789', '987654321', "single line only");

	expect($result)->toBeNull();
});

it('returns null for incomplete second line', function (): void {
	$parser = new TextIssueParser();

	$result = $parser->parse('123456789', '987654321', "ORD-12345\ngameonly");

	expect($result)->toBeNull();
});