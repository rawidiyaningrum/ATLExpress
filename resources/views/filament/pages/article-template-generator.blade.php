<x-filament-panels::page>
    <form wire:submit="generateArticles">
        {{ $this->form }}

        <x-filament-actions::actions
            :actions="$this->getFormActions()"
            class="mt-6 flex justify-end"
        />
    </form>
</x-filament-panels::page>
