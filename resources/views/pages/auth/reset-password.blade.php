<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="'Сброс пароля'" :description="'Введите новый пароль ниже'" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Email Address -->
            <flux:input
                name="email"
                value="{{ request('email') }}"
                :label="'Email'"
                type="email"
                required
                autocomplete="email"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="'Пароль'"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="'Пароль'"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="'Подтвердите пароль'"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="'Подтвердите пароль'"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="reset-password-button">
                    Сбросить пароль
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>
