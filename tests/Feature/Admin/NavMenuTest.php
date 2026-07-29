<?php
declare(strict_types=1);
use App\Models\User;

it('renders the restructured admin header menu', function (): void {
    $admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
    $this->actingAs($admin);
    $this->get(route('admin.accounts.index'))
        ->assertOk()
        ->assertSee('Аккаунты')
        ->assertSee('Доставки')
        ->assertSee('Ссылки')
        ->assertSee('Профиль')
        ->assertSee('Telegram пользователи');
});
