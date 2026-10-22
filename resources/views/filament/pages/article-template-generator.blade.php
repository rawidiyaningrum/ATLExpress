<x-filament-panels::page>
    @if ($batchProgress !== '')
        <div
            @if ($batchActive) wire:poll.5s @endif
            class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
        >
            {!! $batchProgress !!}
        </div>
    @endif

    <form wire:submit="generateArticles">
        {{ $this->form }}

        <x-filament-actions::actions
            :actions="$this->getFormActions()"
            class="mt-6 flex justify-end"
        />
    </form>
</x-filament-panels::page>
