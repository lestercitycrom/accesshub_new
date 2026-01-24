<?php

declare(strict_types=1);

use App\Domain\Telegram\Enums\TelegramRole;
use App\Domain\Telegram\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('can create telegram user', function (): void {
	$admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);

	Livewire::actingAs($admin)
		->test(\App\Admin\Livewire\TelegramUsers\TelegramUserForm::class)
		->set('telegramId', 123456789)
		->set('username', 'testuser')
		->set('firstName', 'Test')
		->set('lastName', 'User')
		->set('role', TelegramRole::OPERATOR->value)
		->set('isActive', true)
		->call('save')
		->assertRedirect('/admin/telegram-users');

	expect(TelegramUser::where('telegram_id', 123456789)->exists())->toBeTrue();
})->group('Stage5.telegram-users');

it('can update telegram user', function (): void {
	$admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
	$user = TelegramUser::factory()->create(['telegram_id' => 987654321]);

	Livewire::actingAs($admin)
		->test(\App\Admin\Livewire\TelegramUsers\TelegramUserForm::class, ['telegramUser' => $user])
		->set('username', 'updateduser')
		->set('firstName', 'Updated')
		->set('lastName', 'Name')
		->set('role', TelegramRole::ADMIN->value)
		->set('isActive', false)
		->call('save')
		->assertRedirect('/admin/telegram-users');

	$user->refresh();
	expect($user->username)->toBe('updateduser');
	expect($user->first_name)->toBe('Updated');
	expect($user->last_name)->toBe('Name');
	expect($user->role->value)->toBe(TelegramRole::ADMIN->value);
	expect($user->is_active)->toBeFalse();
})->group('Stage5.telegram-users');

it('validates telegram id required', function (): void {
	$admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);

	Livewire::actingAs($admin)
		->test(\App\Admin\Livewire\TelegramUsers\TelegramUserForm::class)
		->set('telegramId', 0) // Invalid telegram ID (must be min:1)
		->call('save')
		->assertHasErrors(['telegramId']);
})->group('Stage5.telegram-users');