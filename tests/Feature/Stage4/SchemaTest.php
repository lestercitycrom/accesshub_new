<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns webapp schema', function (): void {
	$response = $this->get('/webapp/api/schema');

	$response->assertOk();
	$response->assertJsonStructure(['version', 'tabs']);
})->group('Stage4');