<?php

declare(strict_types=1);

use App\Domain\Accounts\Enums\AccountStatus;
use App\Domain\Accounts\Models\Account;
use App\Domain\Issuance\Models\Issuance;
use App\Domain\Telegram\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────
// 1. Platform pre-selection fix (AccountForm)
// ─────────────────────────────────────────────

it('edit form saves account with platform not in current config', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    // Account stored with "Xbox One" — not in default config list
    $account = Account::factory()->create([
        'game'            => 'Halo',
        'platform'        => ['Xbox One'],
        'login'           => 'halo_user',
        'status'          => AccountStatus::ACTIVE,
        'max_uses'        => 3,
        'available_uses'  => 3,
    ]);

    Livewire::test(\App\Admin\Livewire\Accounts\AccountForm::class, ['account' => $account])
        ->assertSet('platformSelected', ['Xbox One'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.accounts.index'));

    // Platform preserved after save
    $account->refresh();
    expect($account->platform)->toBe(['Xbox One']);
})->group('Stage63');

it('edit form pre-populates platform multiselect from stored value', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $account = Account::factory()->create([
        'platform' => ['PS4', 'PS5'],
    ]);

    Livewire::test(\App\Admin\Livewire\Accounts\AccountForm::class, ['account' => $account])
        ->assertSet('platformSelected', ['PS4', 'PS5']);
})->group('Stage63');

// ─────────────────────────────────────────────
// 2. Shared history — all operators see all
// ─────────────────────────────────────────────

it('history endpoint returns issuances from all operators', function (): void {
    $operatorA = TelegramUser::factory()->create(['telegram_id' => 1001]);
    $operatorB = TelegramUser::factory()->create(['telegram_id' => 1002]);
    $account   = Account::factory()->create();

    Issuance::factory()->create([
        'telegram_id' => $operatorA->telegram_id,
        'account_id'  => $account->id,
        'order_id'    => 'ORD-A1',
    ]);
    Issuance::factory()->create([
        'telegram_id' => $operatorB->telegram_id,
        'account_id'  => $account->id,
        'order_id'    => 'ORD-B1',
    ]);

    // Operator A calls history — must see both records
    $this->withSession(['webapp.telegram_id' => $operatorA->telegram_id]);
    $response = $this->getJson('/webapp/api/history?limit=50');

    $response->assertOk();
    $orderIds = collect($response->json('items'))->pluck('order_id')->all();
    expect($orderIds)->toContain('ORD-A1');
    expect($orderIds)->toContain('ORD-B1');
    expect($response->json('total'))->toBe(2);
})->group('Stage63');

it('history response includes issuance_id and replacement flags', function (): void {
    $operator = TelegramUser::factory()->create(['telegram_id' => 2001]);
    $account  = Account::factory()->create();

    Issuance::factory()->create([
        'telegram_id' => $operator->telegram_id,
        'account_id'  => $account->id,
        'order_id'    => 'ORD-X',
        'payload'     => null,
    ]);

    $this->withSession(['webapp.telegram_id' => $operator->telegram_id]);
    $item = $this->getJson('/webapp/api/history?limit=10')->json('items.0');

    expect($item)->toHaveKey('issuance_id')
        ->and($item)->toHaveKey('is_replaced')
        ->and($item)->toHaveKey('is_replacement')
        ->and($item)->toHaveKey('replacement_reason')
        ->and($item['is_replaced'])->toBeFalse()
        ->and($item['is_replacement'])->toBeFalse();
})->group('Stage63');

// ─────────────────────────────────────────────
// 3. Account replacement
// ─────────────────────────────────────────────

it('replace endpoint issues new account and marks original as replaced', function (): void {
    $operator    = TelegramUser::factory()->create(['telegram_id' => 3001]);
    $originalAcc = Account::factory()->create([
        'game'           => 'TestGame',
        'platform'       => ['steam'],
        'status'         => AccountStatus::ACTIVE,
        'available_uses' => 2,
        'max_uses'       => 3,
    ]);
    $replacementAcc = Account::factory()->create([
        'game'           => 'TestGame',
        'platform'       => ['steam'],
        'status'         => AccountStatus::ACTIVE,
        'available_uses' => 3,
        'max_uses'       => 3,
    ]);

    $issuance = Issuance::factory()->create([
        'telegram_id' => $operator->telegram_id,
        'account_id'  => $originalAcc->id,
        'game'        => 'TestGame',
        'platform'    => 'steam',
        'order_id'    => 'ORD-REPLACE',
    ]);

    $this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

    $response = $this->postJson('/webapp/api/replace', [
        'issuance_id' => $issuance->id,
        'reason'      => 'kick',
    ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('account_id', $replacementAcc->id);

    // Original issuance marked replaced
    expect($issuance->refresh()->payload['replaced'])->toBeTrue();
    expect($issuance->refresh()->payload['replacement_reason'])->toBe('kick');

    // New issuance created
    $newIssuance = Issuance::query()
        ->where('order_id', 'ORD-REPLACE')
        ->where('account_id', $replacementAcc->id)
        ->first();
    expect($newIssuance)->not->toBeNull();
    expect($newIssuance->payload['is_replacement'])->toBeTrue();

    // Replacement account: available_uses decremented (3 → 2)
    expect($replacementAcc->refresh()->available_uses)->toBe(2);
})->group('Stage63');

it('replace restores one use to original account when reason is not dead', function (): void {
    $operator    = TelegramUser::factory()->create(['telegram_id' => 3002]);
    $originalAcc = Account::factory()->create([
        'game'           => 'TestGame',
        'platform'       => ['steam'],
        'available_uses' => 0,
        'max_uses'       => 3,
        'next_release_at' => now()->addDays(7),
    ]);
    $replacementAcc = Account::factory()->create([
        'game'           => 'TestGame',
        'platform'       => ['steam'],
        'available_uses' => 3,
        'max_uses'       => 3,
    ]);

    $issuance = Issuance::factory()->create([
        'telegram_id' => $operator->telegram_id,
        'account_id'  => $originalAcc->id,
        'game'        => 'TestGame',
        'platform'    => 'steam',
        'order_id'    => 'ORD-RESTORE',
    ]);

    $this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

    $this->postJson('/webapp/api/replace', [
        'issuance_id' => $issuance->id,
        'reason'      => 'wrong_platform',
    ])->assertOk();

    // Original account: use restored (0 → 1), cooldown cleared
    $originalAcc->refresh();
    expect($originalAcc->available_uses)->toBe(1);
    expect($originalAcc->next_release_at)->toBeNull();
})->group('Stage63');

it('replace does NOT restore use when reason is dead', function (): void {
    $operator    = TelegramUser::factory()->create(['telegram_id' => 3003]);
    $originalAcc = Account::factory()->create([
        'game'           => 'TestGame',
        'platform'       => ['steam'],
        'available_uses' => 1,
        'max_uses'       => 3,
    ]);
    $replacementAcc = Account::factory()->create([
        'game'           => 'TestGame',
        'platform'       => ['steam'],
        'available_uses' => 3,
        'max_uses'       => 3,
    ]);

    $issuance = Issuance::factory()->create([
        'telegram_id' => $operator->telegram_id,
        'account_id'  => $originalAcc->id,
        'game'        => 'TestGame',
        'platform'    => 'steam',
        'order_id'    => 'ORD-DEAD',
    ]);

    $this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

    $this->postJson('/webapp/api/replace', [
        'issuance_id' => $issuance->id,
        'reason'      => 'dead',
    ])->assertOk();

    // Original account: use NOT restored (stays 1)
    expect($originalAcc->refresh()->available_uses)->toBe(1);
})->group('Stage63');

it('replace returns 422 if issuance already replaced', function (): void {
    $operator = TelegramUser::factory()->create(['telegram_id' => 3004]);
    $account  = Account::factory()->create(['game' => 'TestGame', 'platform' => ['steam']]);

    $issuance = Issuance::factory()->create([
        'telegram_id' => $operator->telegram_id,
        'account_id'  => $account->id,
        'game'        => 'TestGame',
        'platform'    => 'steam',
        'order_id'    => 'ORD-DUP',
        'payload'     => ['replaced' => true],
    ]);

    $this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

    $this->postJson('/webapp/api/replace', [
        'issuance_id' => $issuance->id,
        'reason'      => 'kick',
    ])->assertStatus(422);
})->group('Stage63');

it('replace returns 422 when no replacement account available', function (): void {
    $operator    = TelegramUser::factory()->create(['telegram_id' => 3005]);
    $originalAcc = Account::factory()->create([
        'game'           => 'UniqueGame',
        'platform'       => ['steam'],
        'available_uses' => 2,
        'max_uses'       => 3,
    ]);

    $issuance = Issuance::factory()->create([
        'telegram_id' => $operator->telegram_id,
        'account_id'  => $originalAcc->id,
        'game'        => 'UniqueGame',
        'platform'    => 'steam',
        'order_id'    => 'ORD-NOAVAIL',
    ]);

    $this->withSession(['webapp.telegram_id' => $operator->telegram_id]);

    // No other account for UniqueGame/steam exists
    $this->postJson('/webapp/api/replace', [
        'issuance_id' => $issuance->id,
        'reason'      => 'kick',
    ])->assertStatus(422);
})->group('Stage63');
