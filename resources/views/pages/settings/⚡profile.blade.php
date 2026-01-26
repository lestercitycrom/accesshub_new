<?php

use App\Concerns\ProfileValidationRules;
use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    use ProfileValidationRules;
    use PasswordValidationRules;

    public string $name = '';
    public string $email = '';
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <x-admin.page-header
        title="Профиль"
        subtitle="Редактирование данных аккаунта и смена пароля."
    >
        <x-slot:breadcrumbs>
            <span class="text-slate-500">Админ</span>
            <span class="px-1 text-slate-300">/</span>
            <span class="font-semibold text-slate-700">Профиль</span>
        </x-slot:breadcrumbs>
    </x-admin.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-admin.card title="Данные аккаунта">
            <form wire:submit="updateProfileInformation" class="space-y-6">
                <x-admin.input
                    label="Имя"
                    type="text"
                    wire:model="name"
                    required
                    autofocus
                    autocomplete="name"
                    :error="$errors->first('name')"
                />

                <div>
                    <x-admin.input
                        label="Email"
                        type="email"
                        wire:model="email"
                        required
                        autocomplete="email"
                        :error="$errors->first('email')"
                    />

                    @if ($this->hasUnverifiedEmail)
                        <div class="mt-4">
                            <x-admin.alert variant="warning" :autohide="false" :dismissible="false">
                                <div class="font-semibold">Email не подтверждён</div>
                                <div class="mt-1 text-sm">
                                    <button type="button" class="underline" wire:click.prevent="resendVerificationNotification">
                                        Отправить письмо подтверждения повторно
                                    </button>
                                </div>
                            </x-admin.alert>

                            @if (session('status') === 'verification-link-sent')
                                <x-admin.alert class="mt-3" variant="success" :autohide="false" :dismissible="false" message="Новая ссылка подтверждения отправлена на ваш email." />
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-4">
                    <x-admin.button variant="primary" type="submit" data-test="update-profile-button">
                        Сохранить
                    </x-admin.button>

                    <x-action-message class="text-sm text-slate-600" on="profile-updated">
                        Сохранено.
                    </x-action-message>
                </div>
            </form>
        </x-admin.card>

        <x-admin.card title="Смена пароля">
            <form wire:submit="updatePassword" class="space-y-6">
                <x-admin.input
                    label="Текущий пароль"
                    type="password"
                    wire:model="current_password"
                    required
                    autocomplete="current-password"
                    :error="$errors->first('current_password')"
                />
                <x-admin.input
                    label="Новый пароль"
                    type="password"
                    wire:model="password"
                    required
                    autocomplete="new-password"
                    :error="$errors->first('password')"
                />
                <x-admin.input
                    label="Подтвердите пароль"
                    type="password"
                    wire:model="password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <div class="flex items-center gap-4">
                    <x-admin.button variant="primary" type="submit" data-test="update-password-button">
                        Сохранить
                    </x-admin.button>

                    <x-action-message class="text-sm text-slate-600" on="password-updated">
                        Сохранено.
                    </x-action-message>
                </div>
            </form>
        </x-admin.card>
    </div>
</div>
@endsection
