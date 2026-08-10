<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <div
            class="relative w-full h-auto"
            x-cloak
            x-data="{
                showRecoveryInput: @js($errors->has('recovery_code')),
                code: '',
                recovery_code: '',
                toggleInput() {
                    this.showRecoveryInput = !this.showRecoveryInput;

                    this.code = '';
                    this.recovery_code = '';

                    $nextTick(() => {
                        this.showRecoveryInput
                            ? this.$refs.recovery_code?.focus()
                            : this.$refs.code?.focus();
                    });
                },
            }"
        >
            <div x-show="!showRecoveryInput">
                <x-auth-header
                    :title="'Код аутентификации'"
                    :description="'Введите код из приложения-аутентификатора.'"
                />
            </div>

            <div x-show="showRecoveryInput">
                <x-auth-header
                    :title="'Резервный код'"
                    :description="'Подтвердите доступ, введя один из резервных кодов восстановления.'"
                />
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}">
                @csrf

                <div class="space-y-5 text-center">
                    <div x-show="!showRecoveryInput">
                        <div class="flex items-center justify-center my-5">
                            <label for="two-factor-code" class="sr-only">OTP код</label>
                            <input
                                id="two-factor-code"
                                data-test="two-factor-code"
                                x-ref="code"
                                x-model="code"
                                name="code"
                                type="text"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                enterkeyhint="done"
                                minlength="6"
                                maxlength="6"
                                pattern="[0-9]{6}"
                                required
                                autofocus
                                autocorrect="off"
                                spellcheck="false"
                                placeholder="000000"
                                aria-describedby="two-factor-code-hint"
                                @input="
                                    code = $event.target.value.replace(/\D/g, '').slice(0, 6);
                                    $event.target.value = code;
                                "
                                style="letter-spacing: 0.45em;"
                                class="block w-full max-w-64 rounded-xl border border-zinc-200 bg-white px-4 py-3 text-center text-2xl font-semibold text-zinc-900 shadow-sm outline-none transition placeholder:text-zinc-300 focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder:text-zinc-600 dark:focus:border-zinc-500 dark:focus:ring-zinc-700"
                            >
                        </div>

                        <p id="two-factor-code-hint" class="sr-only">Введите шесть цифр из приложения-аутентификатора.</p>

                        @error('code')
                            <flux:text color="red">
                                {{ $message }}
                            </flux:text>
                        @enderror
                    </div>

                    <div x-show="showRecoveryInput">
                        <div class="my-5">
                            <flux:input
                                type="text"
                                name="recovery_code"
                                x-ref="recovery_code"
                                x-bind:required="showRecoveryInput"
                                autocomplete="one-time-code"
                                x-model="recovery_code"
                            />
                        </div>

                        @error('recovery_code')
                            <flux:text color="red">
                                {{ $message }}
                            </flux:text>
                        @enderror
                    </div>

                    <flux:button
                        variant="primary"
                        type="submit"
                        class="w-full"
                    >
                        Продолжить
                    </flux:button>
                </div>

                <div class="mt-5 space-x-0.5 text-sm leading-5 text-center">
                    <span class="opacity-50">или можно</span>
                    <div class="inline font-medium underline cursor-pointer opacity-80">
                        <span x-show="!showRecoveryInput" @click="toggleInput()">войти с резервным кодом</span>
                        <span x-show="showRecoveryInput" @click="toggleInput()">войти с кодом аутентификации</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>
