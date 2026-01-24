<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="'Забыли пароль'" :description="'Введите email, чтобы получить ссылку для сброса пароля'" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="'Email адрес'"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                Отправить ссылку для сброса пароля
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>Или вернуться к</span>
            <flux:link :href="route('login')" wire:navigate>входу</flux:link>
        </div>
    </div>
</x-layouts::auth>
