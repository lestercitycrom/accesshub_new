<div class="space-y-6">
    <!-- Заголовок -->
    <div>
        <h2 class="text-2xl font-bold text-center text-slate-900">
            Вход в систему
        </h2>
        <p class="mt-2 text-center text-sm text-slate-600">
            Введите свои учетные данные для доступа к админ-панели
        </p>
    </div>

    <!-- Форма -->
    <form wire:submit="authenticate" class="space-y-4">
        <!-- Email -->
        <div class="space-y-1">
            <label for="email" class="block text-sm font-medium text-slate-700">
                Email
            </label>
            <input
                wire:model="email"
                id="email"
                type="email"
                autocomplete="email"
                required
                class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                placeholder="admin@gmail.com"
            >
            @error('email')
                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div class="space-y-1">
            <label for="password" class="block text-sm font-medium text-slate-700">
                Пароль
            </label>
            <input
                wire:model="password"
                id="password"
                type="password"
                autocomplete="current-password"
                required
                class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                placeholder="••••••••"
            >
            @error('password')
                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember & Submit -->
        <div class="flex items-center justify-between">
            <label class="flex items-center">
                <input
                    wire:model="remember"
                    type="checkbox"
                    class="rounded border-slate-300 text-slate-600 focus:ring-slate-500"
                >
                <span class="ml-2 text-sm text-slate-600">Запомнить меня</span>
            </label>
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 disabled:opacity-50"
        >
            <span wire:loading.remove>Войти</span>
            <span wire:loading>Вход...</span>
        </button>
    </form>

    <!-- Дополнительная информация -->
    <div class="text-center">
        <p class="text-xs text-slate-500">
            Используйте учетные данные администратора для доступа к системе
        </p>
    </div>
</div>