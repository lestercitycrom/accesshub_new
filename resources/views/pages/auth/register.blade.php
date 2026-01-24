<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="'Создать аккаунт'" :description="'Введите данные для регистрации'" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="'Имя'"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="'Полное имя'"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="'Email адрес'"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
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
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    Создать аккаунт
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>Уже есть аккаунт?</span>
            <flux:link :href="route('login')" wire:navigate>Войти</flux:link>
        </div>
    </div>
</x-layouts::auth>
