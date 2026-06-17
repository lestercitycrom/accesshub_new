<?php

declare(strict_types=1);

it('rejects telegram webhook without the secret header when a secret is configured', function (): void {
	config()->set('services.telegram.webhook_secret', 's3cr3t-token');

	$this->postJson('/api/telegram/webhook', ['update_id' => 1])
		->assertStatus(403)
		->assertJsonPath('status', 'forbidden');
});

it('rejects telegram webhook with a wrong secret header', function (): void {
	config()->set('services.telegram.webhook_secret', 's3cr3t-token');

	$this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 'wrong'])
		->postJson('/api/telegram/webhook', ['update_id' => 1])
		->assertStatus(403);
});

it('accepts telegram webhook with the correct secret header', function (): void {
	config()->set('services.telegram.webhook_secret', 's3cr3t-token');

	// Empty/unparseable update is ignored with 200 — enough to prove the
	// secret gate let the request through.
	$this->withHeaders(['X-Telegram-Bot-Api-Secret-Token' => 's3cr3t-token'])
		->postJson('/api/telegram/webhook', ['update_id' => 1])
		->assertStatus(200);
});

it('skips the secret check when no secret is configured (default)', function (): void {
	config()->set('services.telegram.webhook_secret', '');

	$this->postJson('/api/telegram/webhook', ['update_id' => 1])
		->assertStatus(200);
});
