<?php

declare(strict_types=1);

use App\Domain\Telegram\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders telegram users index for admin', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);

	TelegramUser::factory()->count(3)->create();

	$this->actingAs($admin);

	Livewire::test(\App\Admin\Livewire\TelegramUsers\TelegramUsersIndex::class)
		->assertOk()
		->assertSee('Пользователи Telegram');
})->group('Stage5.2');

it('can deactivate selected users', function (): void {
	$admin = User::factory()->create(['is_admin' => true]);
	$this->actingAs($admin);

	$u1 = TelegramUser::factory()->create(['is_active' => true]);
	$u2 = TelegramUser::factory()->create(['is_active' => true]);

	Livewire::test(\App\Admin\Livewire\TelegramUsers\TelegramUsersIndex::class)
		->set('selected', [$u1->id, $u2->id])
		->call('toggleActive', false);

	expect($u1->refresh()->is_active)->toBeFalse();
	expect($u2->refresh()->is_active)->toBeFalse();
})->group('Stage5.2');
