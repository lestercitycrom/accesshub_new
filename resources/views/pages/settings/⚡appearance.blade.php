<?php

use Livewire\Component;

new class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">Настройки внешнего вида</flux:heading>

    <x-pages::settings.layout :heading="'Внешний вид'" :subheading="'Настройте внешний вид вашего аккаунта'">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">Светлая</flux:radio>
            <flux:radio value="dark" icon="moon">Тёмная</flux:radio>
            <flux:radio value="system" icon="computer-desktop">Системная</flux:radio>
        </flux:radio.group>
    </x-pages::settings.layout>
</section>
