<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Войти в аккаунт')" :description="__('Введите ваш email и пароль ниже для входа')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email адрес')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@пример.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Пароль')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Пароль')"
                viewable
            />

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Запомнить меня')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Войти') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>
