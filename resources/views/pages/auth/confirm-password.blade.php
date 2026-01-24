<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="'Подтверждение пароля'"
            :description="'Это защищённая зона. Подтвердите пароль для продолжения.'"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="password"
                :label="'Пароль'"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="'Пароль'"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                Подтвердить
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
